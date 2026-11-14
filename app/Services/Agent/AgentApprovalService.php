<?php

namespace App\Services\Agent;

use App\Models\AuditLogModel;
use App\Services\Social\SocialMediaLibraryService;
use App\Services\Social\SocialSecurityService;
use CodeIgniter\Database\BaseConnection;

/**
 * Human review of agent-created drafts. Every transition is one atomic conditional UPDATE and is audit-logged
 * with the acting administrator. Approval is the ONLY way an agent post becomes publishable.
 *
 *   draft | pending_approval  --approve-->           approved   (approved_by/approved_at set)
 *   draft | pending_approval  --reject-->            cancelled  (note required)
 *   draft | pending_approval  --request changes-->   draft      (note required; agent sees decision + note)
 *   approved                  --edit-->              pending_approval  (approval is void once content changes)
 */
class AgentApprovalService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Queue + counts for the review screen. */
    public function queue(string $filter = 'pending', int $limit = 100): array
    {
        $b = $this->db->table('social_posts')->select('social_posts.*, ai_agents.name AS agent_name, ai_agents.provider AS agent_provider')
            ->join('ai_agents', 'ai_agents.id = social_posts.agent_id', 'left')->where('social_posts.origin', 'agent');
        match ($filter) {
            'pending'  => $b->whereIn('social_posts.status', ['draft', 'pending_approval'])->where('social_posts.review_decision IS NULL'),
            'approved' => $b->where('social_posts.review_decision', 'approved'),
            'closed'   => $b->whereIn('social_posts.review_decision', ['rejected', 'changes_requested']),
            default    => null,
        };
        return $b->orderBy('social_posts.id', 'DESC')->limit($limit)->get()->getResultArray();
    }

    public function pendingCount(): int
    {
        try {
            // Never run a query that can fail: before the additive migration `origin` does not exist.
            if (!$this->db->tableExists('ai_agents') || !$this->db->fieldExists('origin', 'social_posts')) {
                return 0;
            }
            return (int) $this->db->table('social_posts')->where('origin', 'agent')->whereIn('status', ['draft', 'pending_approval'])->where('review_decision IS NULL')->countAllResults();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function find(int $id): ?array
    {
        $post = $this->db->table('social_posts')->select('social_posts.*, ai_agents.name AS agent_name, ai_agents.provider AS agent_provider')
            ->join('ai_agents', 'ai_agents.id = social_posts.agent_id', 'left')->where('social_posts.id', $id)->where('social_posts.origin', 'agent')->get()->getRowArray();
        if (!$post) {
            return null;
        }
        $post['platforms'] = $this->db->table('social_post_platforms')->where('post_id', $id)->get()->getResultArray();
        $post['media']     = $this->db->table('social_post_media')->where('post_id', $id)->get()->getResultArray();
        return $post;
    }

    public function approve(int $id, int $adminId, ?string $note = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('social_posts')->where('id', $id)->where('origin', 'agent')->whereIn('status', ['draft', 'pending_approval'])->where('review_decision IS NULL')
            ->update(['status' => 'approved', 'review_decision' => 'approved', 'review_note' => self::note($note), 'approved_by' => $adminId, 'approved_at' => $now,
                      'reviewed_by' => $adminId, 'reviewed_at' => $now, 'updated_at' => $now]);
        return $this->done($this->db->affectedRows() === 1, 'AGENT_DRAFT_APPROVED', $id, $adminId);
    }

    public function reject(int $id, int $adminId, string $note): bool
    {
        return $this->decide($id, $adminId, 'cancelled', 'rejected', $note, 'AGENT_DRAFT_REJECTED');
    }

    public function requestChanges(int $id, int $adminId, string $note): bool
    {
        return $this->decide($id, $adminId, 'draft', 'changes_requested', $note, 'AGENT_DRAFT_CHANGES_REQUESTED');
    }

    /**
     * Edit captions/hashtags/cta/media. Editing approved content voids the approval (it goes back to review) so the
     * approved text is always exactly what the reviewer saw. Platform limits are re-validated.
     * @return array{ok:bool,errors:array<string,string>}
     */
    public function edit(int $id, int $adminId, array $in): array
    {
        $post = $this->db->table('social_posts')->where('id', $id)->where('origin', 'agent')->get()->getRowArray();
        if (!$post || in_array($post['status'], ['published', 'processing', 'cancelled'], true)) {
            return ['ok' => false, 'errors' => ['post' => 'This post can no longer be edited.']];
        }
        $platform = $post['primary_platform'];
        $v = (new AgentDraftValidator($this->db))->validate([
            'title' => $in['title'] ?? $post['title'], 'platform' => $platform, 'caption' => $in['caption'] ?? $post['base_content'],
            'hashtags' => $in['hashtags'] ?? ($post['base_hashtags'] ?? ''), 'cta' => $in['cta'] ?? ($post['base_cta'] ?? ''),
            'destination_url' => $in['destination_url'] ?? ($post['base_destination_url'] ?? ''),
            'media_ids' => array_map('intval', (array) ($in['media_ids'] ?? [])),
        ]);
        if (!$v['ok']) {
            return ['ok' => false, 'errors' => $v['errors']];
        }
        $d   = $v['data'];
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $upd = ['title' => $d['title'], 'base_content' => $d['caption'], 'base_hashtags' => implode(' ', $d['hashtags']) ?: null, 'base_cta' => $d['cta'] ?: null,
                'base_destination_url' => $d['destination_url'] ?: null, 'content_hash' => AgentDraftValidator::contentHash($platform, $d['caption']), 'updated_at' => $now];
        $wasApproved = $post['review_decision'] === 'approved';
        if ($wasApproved || $post['status'] === 'scheduled') {
            $upd += ['status' => 'pending_approval', 'review_decision' => null, 'review_note' => null, 'approved_by' => null, 'approved_at' => null, 'reviewed_by' => null, 'reviewed_at' => null];
            // Any queued publish job for this content is withdrawn together with the approval.
            $this->db->table('social_scheduled_jobs')->where('post_id', $id)->where('status', 'queued')->update(['status' => 'cancelled', 'updated_at' => $now]);
        }
        $this->db->table('social_posts')->where('id', $id)->update($upd);
        foreach ($d['targets'] as $p) {
            $this->db->table('social_post_platforms')->where('post_id', $id)->where('platform', $p)
                ->update(['caption' => $d['caption'], 'hashtags' => implode(' ', $d['hashtags']) ?: null, 'cta' => $d['cta'] ?: null, 'destination_url' => $d['destination_url'] ?: null, 'updated_at' => $now]);
        }
        if (isset($in['media_ids'])) {
            $this->db->table('social_post_media')->where('post_id', $id)->delete();
            if ($d['media_ids']) {
                (new SocialMediaLibraryService($this->db, new SocialSecurityService($this->db)))->attachMediaToPost($id, $d['media_ids']);
            }
        }
        $this->db->transComplete();
        $ok = $this->db->transStatus();
        $this->audit($ok ? 'AGENT_DRAFT_EDITED' : 'AGENT_DRAFT_EDIT_FAILED', $id, $adminId, ['approval_voided' => $wasApproved,
            'old_caption_sha' => substr(hash('sha256', (string) $post['base_content']), 0, 12), 'new_caption_sha' => substr(hash('sha256', $d['caption']), 0, 12)]);
        return ['ok' => (bool) $ok, 'errors' => $ok ? [] : ['post' => 'Saving failed.']];
    }

    // ── internals ────────────────────────────────────────────────────────────
    private function decide(int $id, int $adminId, string $status, string $decision, string $note, string $auditAction): bool
    {
        $note = self::note($note);
        if ($note === null || $note === '') {
            return false; // a reason is mandatory for reject / request-changes
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('social_posts')->where('id', $id)->where('origin', 'agent')->whereIn('status', ['draft', 'pending_approval'])->where('review_decision IS NULL')
            ->update(['status' => $status, 'review_decision' => $decision, 'review_note' => $note, 'approved_by' => null, 'approved_at' => null,
                      'reviewed_by' => $adminId, 'reviewed_at' => $now, 'updated_at' => $now]);
        return $this->done($this->db->affectedRows() === 1, $auditAction, $id, $adminId);
    }

    private function done(bool $ok, string $action, int $id, int $adminId): bool
    {
        $this->audit($ok ? $action : $action . '_REJECTED', $id, $adminId);
        return $ok;
    }

    private function audit(string $action, int $postId, int $adminId, ?array $data = null): void
    {
        (new AuditLogModel())->log($action, 'social_post', $postId, null, $data, $adminId);
    }

    private static function note(?string $n): ?string
    {
        return $n === null ? null : mb_substr(AgentDraftValidator::text($n), 0, 500);
    }
}
