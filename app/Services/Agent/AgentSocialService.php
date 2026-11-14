<?php

namespace App\Services\Agent;

use App\Services\Social\SocialMediaLibraryService;
use App\Services\Social\SocialSecurityService;
use CodeIgniter\Database\BaseConnection;

/**
 * Business logic behind /api/v1/integrations/agents/*.
 *
 * Reuses the existing social tables and services. An agent can ONLY:
 *   - read (accounts, guidelines, templates, calendar, aggregate analytics, its OWN drafts),
 *   - create a draft  (status 'draft', origin 'agent', no scheduling, no account choice),
 *   - move its OWN draft to 'pending_approval'.
 * It can never approve, schedule, publish, edit after creation, choose a platform account, or see platform tokens.
 */
class AgentSocialService
{
    private BaseConnection $db;
    private AgentDraftValidator $validator;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db        = $db ?? \Config\Database::connect();
        $this->validator = new AgentDraftValidator($this->db);
    }

    // ── reads ────────────────────────────────────────────────────────────────
    public function capabilities(array $agent, array $token): array
    {
        $scopes = AgentScopes::parse($agent['scopes']);
        $cat    = [];
        foreach ($scopes as $s) {
            [$label, $desc, $endpoint] = AgentScopes::CATALOGUE[$s];
            $cat[] = ['scope' => $s, 'label' => $label, 'description' => $desc, 'endpoint' => $endpoint];
        }
        return [
            'api_version' => 'v1',
            'agent'       => ['id' => (int) $agent['id'], 'name' => $agent['name'], 'provider' => $agent['provider']],
            'policy'      => ['mode' => 'draft_only', 'direct_publishing' => false, 'scheduling' => false, 'human_approval_required' => true],
            'scopes'      => $cat,
            'limits'      => ['requests_per_minute' => (int) $agent['rate_limit_per_minute'], 'drafts_per_day' => (int) $agent['daily_draft_limit'], 'max_body_bytes' => 65536],
            'credential'  => ['expires_at' => $token['expires_at']],
            'workflow'    => ['create draft (status=draft)', 'optionally POST …/submit-approval (status=pending_approval)', 'a human reviews: approve / reject / request changes', 'only approved content can reach the existing publishing engine, and only a human can schedule or publish it'],
        ];
    }

    public function accounts(): array
    {
        $rows = $this->db->query("SELECT a.platform, a.account_name, a.account_type, a.status, c.token_expires_at,
                (c.access_token_encrypted IS NOT NULL AND c.access_token_encrypted <> '') AS has_token
            FROM social_accounts a LEFT JOIN social_credentials c ON c.account_id = a.id ORDER BY a.platform, a.id")->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $exp = $r['token_expires_at'] ? strtotime($r['token_expires_at']) : null;
            $health = !(int) $r['has_token'] ? 'missing' : ($exp !== null && $exp < time() ? 'expired' : ($exp !== null && $exp < time() + 7 * 86400 ? 'expiring_soon' : 'ok'));
            $out[] = ['platform' => $r['platform'], 'name' => $r['account_name'], 'type' => $r['account_type'], 'connection' => $r['status'], 'token_health' => $health];
        }
        return ['accounts' => $out];
    }

    public function guidelines(): array
    {
        $site = [];
        try {
            foreach ($this->db->table('website_settings')->select('setting_key,setting_value')->whereIn('setting_key', ['site_name', 'site_tagline'])->get()->getResultArray() as $r) {
                $site[$r['setting_key']] = $r['setting_value'];
            }
        } catch (\Throwable) {
        }
        return [
            'brand'     => ['name' => $site['site_name'] ?? 'TalentHub Consultant', 'tagline' => $site['site_tagline'] ?? null],
            'platforms' => [
                'linkedin'  => ['max_caption_chars' => 3000, 'max_hashtags' => 30, 'media_required' => false],
                'instagram' => ['max_caption_chars' => 2200, 'max_hashtags' => 30, 'media_required' => true],
            ],
            'fields'    => ['allowed' => AgentDraftValidator::ALLOWED_FIELDS, 'media' => 'ids of approved media-library items only; no paths or URLs', 'destination_url' => 'https only, talenthubconsultant.com or its subdomains'],
            'rules'     => [
                'Plain text only; HTML is stripped.',
                'Never invent customer names, logos, testimonials, awards, certifications, placement figures or other statistics.',
                'Only state facts that appear on the TalentHub website or in approved templates.',
                'Do not include contact details other than those published by TalentHub.',
                'Every draft is reviewed by a human before anything can be published; proposed_publication_at is only a suggestion.',
                'Identical captions within 30 days are rejected as duplicates.',
            ],
            'positioning' => 'TalentHub helps businesses find the right talent, build the right technology and transform the way they work.',
        ];
    }

    public function templates(): array
    {
        $rows = $this->db->table('social_content_templates')->select('id,title,category,platform,caption_template,hashtag_group,cta_template,tone')
            ->where('is_active', 1)->orderBy('category')->orderBy('title')->limit(100)->get()->getResultArray();
        return ['templates' => $rows];
    }

    public function calendar(?string $from, ?string $to): array
    {
        $f = $from ? strtotime($from) : time();
        $t = $to ? strtotime($to) : $f + 30 * 86400;
        if ($f === false || $t === false || $t < $f) {
            throw new AgentApiException('VALIDATION_FAILED', 422, 'from/to must be valid dates and to must not precede from.');
        }
        $t = min($t, $f + 92 * 86400);
        $rows = $this->db->query("SELECT id, title, primary_platform AS platform, status, origin, COALESCE(scheduled_at, published_at) AS at
            FROM social_posts WHERE status IN ('approved','scheduled','published','pending_approval')
              AND COALESCE(scheduled_at, published_at) BETWEEN ? AND ? ORDER BY at ASC LIMIT 200", [gmdate('Y-m-d H:i:s', $f), gmdate('Y-m-d H:i:s', $t)])->getResultArray();
        return ['from' => gmdate('c', $f), 'to' => gmdate('c', $t), 'timezone_note' => 'Times are UTC.', 'items' => $rows];
    }

    public function analytics(array $agent): array
    {
        $since = date('Y-m-d H:i:s', time() - 30 * 86400);
        $byStatus = [];
        foreach ($this->db->query('SELECT status, COUNT(*) c FROM social_posts WHERE created_at >= ? GROUP BY status', [$since])->getResultArray() as $r) {
            $byStatus[$r['status']] = (int) $r['c'];
        }
        $byPlatform = [];
        foreach ($this->db->query("SELECT platform, COUNT(*) c FROM social_post_platforms WHERE status='published' AND published_at >= ? GROUP BY platform", [$since])->getResultArray() as $r) {
            $byPlatform[$r['platform']] = (int) $r['c'];
        }
        $att = $this->db->query("SELECT SUM(status='success') ok, SUM(status<>'success') bad FROM social_publish_attempts WHERE created_at >= ?", [$since])->getRowArray();
        $mine = [];
        foreach ($this->db->query("SELECT status, review_decision, COUNT(*) c FROM social_posts WHERE agent_id = ? AND created_at >= ? GROUP BY status, review_decision", [(int) $agent['id'], $since])->getResultArray() as $r) {
            $mine[] = ['status' => $r['status'], 'review_decision' => $r['review_decision'], 'count' => (int) $r['c']];
        }
        return ['window_days' => 30, 'posts_by_status' => $byStatus, 'published_by_platform' => $byPlatform,
                'publish_attempts' => ['succeeded' => (int) ($att['ok'] ?? 0), 'failed' => (int) ($att['bad'] ?? 0)], 'my_drafts' => $mine,
                'note' => 'Aggregate counts only. No reach/engagement or individual post metrics are exposed.'];
    }

    /** An agent can read only drafts it created. Any other id is reported as not found (no existence leak / IDOR). */
    public function getPost(array $agent, int $id): array
    {
        $post = $this->db->table('social_posts')->where('id', $id)->where('origin', 'agent')->where('agent_id', (int) $agent['id'])->get()->getRowArray();
        if (!$post) {
            throw new AgentApiException('NOT_FOUND', 404, 'Post not found.');
        }
        return ['post' => $this->present($post, true)];
    }

    // ── writes (draft only) ──────────────────────────────────────────────────
    /** @return array{0:int,1:array} [http status, payload] */
    public function createDraft(array $agent, array $input, ?string $idemKey): array
    {
        if ($idemKey !== null && !preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $idemKey)) {
            throw new AgentApiException('INVALID_IDEMPOTENCY_KEY', 400, 'Idempotency-Key must be 8–80 characters: letters, digits, _ . : -');
        }
        $v = $this->validator->validate($input);
        if (!$v['ok']) {
            throw new AgentApiException('VALIDATION_FAILED', 422, 'The draft was rejected. See details.', $v['errors']);
        }
        $d = $v['data'];
        $requestHash = hash('sha256', json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($idemKey !== null) {
            $prior = $this->db->table('ai_agent_idempotency')->where('agent_id', (int) $agent['id'])->where('idem_key', $idemKey)->get()->getRowArray();
            if ($prior) {
                return $this->replay($agent, $prior, $requestHash);
            }
        }

        $today = $this->db->table('social_posts')->where('agent_id', (int) $agent['id'])->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults();
        if ($today >= (int) $agent['daily_draft_limit']) {
            throw new AgentApiException('DAILY_DRAFT_LIMIT', 429, 'Daily draft limit reached for this agent.', ['limit' => (int) $agent['daily_draft_limit']]);
        }

        $hash = AgentDraftValidator::contentHash($d['platform'], $d['caption']);
        if ($this->db->table('social_posts')->where('content_hash', $hash)->where('status !=', 'cancelled')->where('created_at >=', date('Y-m-d H:i:s', time() - 30 * 86400))->countAllResults()) {
            throw new AgentApiException('DUPLICATE_CONTENT', 409, 'An identical caption was already drafted in the last 30 days.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        try {
            $this->db->table('social_posts')->insert([
                'title' => $d['title'], 'campaign_id' => $d['campaign_id'], 'primary_platform' => $d['platform'],
                'base_content' => $d['caption'], 'base_hashtags' => implode(' ', $d['hashtags']) ?: null,
                'base_cta' => $d['cta'] ?: null, 'base_destination_url' => $d['destination_url'] ?: null,
                'status' => 'draft', 'scheduled_at' => null, 'timezone' => 'Asia/Kolkata', 'created_by' => null,
                'internal_notes' => 'Created by external agent "' . $agent['name'] . '" (#' . (int) $agent['id'] . '). Not approved.',
                'origin' => 'agent', 'agent_id' => (int) $agent['id'], 'content_hash' => $hash, 'proposed_at' => $d['proposed_at'],
                'created_at' => $now,
            ]);
            $postId = (int) $this->db->insertID();

            foreach ($d['targets'] as $p) {
                $acc = $this->db->table('social_accounts')->select('id')->where('platform', $p)->where('status', 'active')->orderBy('id')->limit(1)->get()->getRowArray();
                $tags = $d['overrides'][$p]['hashtags'] ?? $d['hashtags'];
                $this->db->table('social_post_platforms')->insert([
                    'post_id' => $postId, 'platform' => $p, 'account_id' => $acc['id'] ?? null,
                    'caption' => $d['overrides'][$p]['caption'] ?? $d['caption'], 'hashtags' => implode(' ', $tags) ?: null,
                    'cta' => $d['cta'] ?: null, 'destination_url' => $d['destination_url'] ?: null, 'status' => 'pending', 'created_at' => $now,
                ]);
            }
            if ($d['media_ids']) {
                (new SocialMediaLibraryService($this->db, new SocialSecurityService($this->db)))->attachMediaToPost($postId, $d['media_ids']);
            }
            if ($idemKey !== null) {
                $this->db->table('ai_agent_idempotency')->insert(['agent_id' => (int) $agent['id'], 'idem_key' => $idemKey, 'request_hash' => $requestHash, 'post_id' => $postId, 'created_at' => $now]);
            }
            $this->db->transComplete();
            if (!$this->db->transStatus()) {
                throw new \RuntimeException('Draft insert failed');
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            // A concurrent request with the same Idempotency-Key won the race: return its result instead of failing.
            if ($idemKey !== null && ($prior = $this->db->table('ai_agent_idempotency')->where('agent_id', (int) $agent['id'])->where('idem_key', $idemKey)->get()->getRowArray())) {
                return $this->replay($agent, $prior, $requestHash);
            }
            log_message('error', 'Agent draft creation failed: ' . get_class($e));
            throw new AgentApiException('SERVER_ERROR', 500, 'The draft could not be saved.');
        }

        try {
            $this->db->table('social_notifications')->insert([
                'user_id' => null, 'event_type' => 'agent_draft_created', 'title' => 'New AI-agent draft awaiting review',
                'message' => $agent['name'] . ' created draft #' . $postId . ': ' . mb_substr($d['title'], 0, 100),
                'link' => 'admin/agents/approvals/' . $postId, 'is_read' => 0, 'email_sent' => 0, 'created_at' => $now,
            ]);
        } catch (\Throwable) {
            // Notification failure must never lose the draft.
        }

        return [201, $this->getPost($agent, $postId) + ['created' => true]];
    }

    /** draft → pending_approval for the agent's OWN draft only. One atomic conditional UPDATE. */
    public function submitForApproval(array $agent, int $id): array
    {
        $this->db->table('social_posts')->where('id', $id)->where('origin', 'agent')->where('agent_id', (int) $agent['id'])->where('status', 'draft')
            ->update(['status' => 'pending_approval', 'review_decision' => null, 'review_note' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        if ($this->db->affectedRows() === 1) {
            try {
                $this->db->table('social_notifications')->insert([
                    'user_id' => null, 'event_type' => 'agent_draft_submitted', 'title' => 'AI-agent draft submitted for approval',
                    'message' => $agent['name'] . ' submitted draft #' . $id . ' for approval.', 'link' => 'admin/agents/approvals/' . $id,
                    'is_read' => 0, 'email_sent' => 0, 'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
            }
            return $this->getPost($agent, $id);
        }
        $post = $this->db->table('social_posts')->select('status')->where('id', $id)->where('origin', 'agent')->where('agent_id', (int) $agent['id'])->get()->getRowArray();
        if (!$post) {
            throw new AgentApiException('NOT_FOUND', 404, 'Post not found.');
        }
        throw new AgentApiException('INVALID_STATE', 409, 'Only a draft can be submitted for approval; this post is "' . $post['status'] . '".');
    }

    // ── helpers ──────────────────────────────────────────────────────────────
    private function replay(array $agent, array $prior, string $requestHash): array
    {
        if (!hash_equals($prior['request_hash'], $requestHash)) {
            throw new AgentApiException('IDEMPOTENCY_KEY_REUSED', 422, 'This Idempotency-Key was already used with a different request body.');
        }
        return [200, $this->getPost($agent, (int) $prior['post_id']) + ['created' => false, 'replayed' => true]];
    }

    private function present(array $p, bool $detail): array
    {
        $out = [
            'id' => (int) $p['id'], 'title' => $p['title'], 'platform' => $p['primary_platform'], 'status' => $p['status'], 'origin' => $p['origin'],
            'review' => ['decision' => $p['review_decision'] ?? null, 'note' => $p['review_note'] ?? null, 'reviewed_at' => $p['reviewed_at'] ?? null],
            'created_at' => $p['created_at'], 'proposed_publication_at' => $p['proposed_at'] ?? null,
            'can_be_published' => ($p['review_decision'] ?? null) === 'approved' && !empty($p['approved_by']),
        ];
        if ($detail) {
            $out['caption']         = $p['base_content'];
            $out['hashtags']        = $p['base_hashtags'];
            $out['cta']             = $p['base_cta'];
            $out['destination_url'] = $p['base_destination_url'];
            $out['platforms']       = $this->db->table('social_post_platforms')->select('platform,caption,hashtags,status')->where('post_id', (int) $p['id'])->get()->getResultArray();
            $out['media_ids']       = array_map('intval', array_column($this->db->table('social_post_media')->select('media_library_id')->where('post_id', (int) $p['id'])->get()->getResultArray(), 'media_library_id'));
        }
        return $out;
    }
}
