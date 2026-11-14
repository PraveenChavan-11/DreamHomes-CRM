<?php

namespace App\Services\Agent;

use CodeIgniter\Database\BaseConnection;

/**
 * Strict validation of an agent-submitted draft. Anything not explicitly allowed is rejected —
 * in particular status / approval / account / scheduling fields, so a request body can never change
 * workflow state or target a platform account.
 */
class AgentDraftValidator
{
    public const LIMITS = [
        'linkedin'  => ['caption' => 3000, 'hashtags' => 30, 'media_required' => false],
        'instagram' => ['caption' => 2200, 'hashtags' => 30, 'media_required' => true],
    ];
    public const ALLOWED_FIELDS = ['title', 'platform', 'caption', 'hashtags', 'cta', 'destination_url', 'media_ids', 'campaign_id', 'proposed_publication_at', 'platform_overrides'];
    public const OVERRIDE_FIELDS = ['caption', 'hashtags'];
    /** Hosts a destination URL may point to (https only). Extend via website_settings.agent_allowed_url_hosts (comma list). */
    public const DEFAULT_URL_HOSTS = ['talenthubconsultant.com'];
    public const MAX_MEDIA = 10;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * @return array{ok:bool,errors:array<string,string>,data:array}
     */
    public function validate(array $in): array
    {
        $e = [];
        foreach (array_diff(array_keys($in), self::ALLOWED_FIELDS) as $unknown) {
            $e[(string) $unknown] = 'Unknown or forbidden field. Agents cannot set workflow, approval, account or scheduling fields.';
        }

        $title = self::text($in['title'] ?? '');
        if ($title === '' || mb_strlen($title) > 191) {
            $e['title'] = 'Required, 1–191 characters.';
        }

        $platform = strtolower(trim((string) ($in['platform'] ?? '')));
        if (!in_array($platform, ['linkedin', 'instagram', 'both'], true)) {
            $e['platform'] = 'Must be one of: linkedin, instagram, both.';
        }
        $targets = $platform === 'both' ? ['linkedin', 'instagram'] : (isset(self::LIMITS[$platform]) ? [$platform] : []);

        $caption = self::text($in['caption'] ?? '', true);
        if ($caption === '') {
            $e['caption'] = 'Required.';
        }

        [$hashtags, $hErr] = self::hashtags($in['hashtags'] ?? []);
        if ($hErr) {
            $e['hashtags'] = $hErr;
        }

        $cta = self::text($in['cta'] ?? '');
        if (mb_strlen($cta) > 120) {
            $e['cta'] = 'At most 120 characters.';
        }

        $url = trim((string) ($in['destination_url'] ?? ''));
        if ($url !== '' && ($msg = $this->urlError($url)) !== null) {
            $e['destination_url'] = $msg;
        }

        // media: ids of APPROVED items in the existing media library — never paths or remote URLs
        $mediaIds = [];
        if (isset($in['media_ids'])) {
            if (!is_array($in['media_ids']) || count($in['media_ids']) > self::MAX_MEDIA) {
                $e['media_ids'] = 'Must be a list of at most ' . self::MAX_MEDIA . ' media library ids.';
            } else {
                foreach ($in['media_ids'] as $id) {
                    if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
                        $e['media_ids'] = 'Only integer media library ids are accepted (no paths or URLs).';
                        $mediaIds = [];
                        break;
                    }
                    $mediaIds[] = (int) $id;
                }
                $mediaIds = array_values(array_unique($mediaIds));
                if (!isset($e['media_ids']) && $mediaIds) {
                    $found = $this->db->table('social_media_library')->select('id')->whereIn('id', $mediaIds)->where('is_approved', 1)->get()->getResultArray();
                    if (count($found) !== count($mediaIds)) {
                        $e['media_ids'] = 'One or more media items do not exist or are not approved.';
                    }
                }
            }
        }

        $campaignId = null;
        if (isset($in['campaign_id']) && $in['campaign_id'] !== null && $in['campaign_id'] !== '') {
            if ((!is_int($in['campaign_id']) && !ctype_digit((string) $in['campaign_id'])) || !$this->db->table('social_campaigns')->where('id', (int) $in['campaign_id'])->countAllResults()) {
                $e['campaign_id'] = 'Campaign not found.';
            } else {
                $campaignId = (int) $in['campaign_id'];
            }
        }

        $proposed = null;
        if (isset($in['proposed_publication_at']) && $in['proposed_publication_at'] !== '') {
            $ts = is_string($in['proposed_publication_at']) ? strtotime($in['proposed_publication_at']) : false;
            if ($ts === false || $ts < time() || $ts > time() + 366 * 86400) {
                $e['proposed_publication_at'] = 'Must be a future ISO-8601 date-time within one year. It is only a suggestion; an administrator decides the real schedule.';
            } else {
                $proposed = gmdate('Y-m-d H:i:s', $ts);
            }
        }

        // per-platform captions (optional overrides) and limits
        $overrides = [];
        if (isset($in['platform_overrides'])) {
            if (!is_array($in['platform_overrides'])) {
                $e['platform_overrides'] = 'Must be an object keyed by platform.';
            } else {
                foreach ($in['platform_overrides'] as $p => $o) {
                    if (!isset(self::LIMITS[$p]) || !in_array($p, $targets, true) || !is_array($o) || array_diff(array_keys($o), self::OVERRIDE_FIELDS)) {
                        $e["platform_overrides.$p"] = 'Only caption/hashtags overrides for a targeted platform are allowed.';
                        continue;
                    }
                    $oc = isset($o['caption']) ? self::text($o['caption'], true) : $caption;
                    [$oh, $ohErr] = isset($o['hashtags']) ? self::hashtags($o['hashtags']) : [$hashtags, null];
                    if ($ohErr) {
                        $e["platform_overrides.$p.hashtags"] = $ohErr;
                    }
                    $overrides[$p] = ['caption' => $oc, 'hashtags' => $oh];
                }
            }
        }

        foreach ($targets as $p) {
            $cap = $overrides[$p]['caption'] ?? $caption;
            $tags = $overrides[$p]['hashtags'] ?? $hashtags;
            if (mb_strlen($cap) > self::LIMITS[$p]['caption']) {
                $e["caption.$p"] = ucfirst($p) . ' captions are limited to ' . self::LIMITS[$p]['caption'] . ' characters.';
            }
            if (count($tags) > self::LIMITS[$p]['hashtags']) {
                $e["hashtags.$p"] = ucfirst($p) . ' allows at most ' . self::LIMITS[$p]['hashtags'] . ' hashtags.';
            }
            if (self::LIMITS[$p]['media_required'] && !$mediaIds && !isset($e['media_ids'])) {
                $e['media_ids'] = 'Instagram posts need at least one approved media item.';
            }
        }

        return ['ok' => !$e, 'errors' => $e, 'data' => [
            'title' => $title, 'platform' => $platform, 'caption' => $caption, 'hashtags' => $hashtags, 'cta' => $cta,
            'destination_url' => $url, 'media_ids' => $mediaIds, 'campaign_id' => $campaignId, 'proposed_at' => $proposed,
            'overrides' => $overrides, 'targets' => $targets,
        ]];
    }

    /** Plain text only: tags stripped, control characters removed, whitespace normalised (newlines kept for captions). */
    public static function text(mixed $v, bool $multiline = false): string
    {
        if (!is_scalar($v)) {
            return '';
        }
        $s = strip_tags((string) $v);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        $s = $multiline ? preg_replace("/[ \t]+/u", ' ', str_replace("\r\n", "\n", $s)) : preg_replace('/\s+/u', ' ', $s);
        return trim((string) $s);
    }

    /** @return array{0:list<string>,1:?string} */
    public static function hashtags(mixed $raw): array
    {
        $parts = is_array($raw) ? $raw : (is_string($raw) ? preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) : null);
        if ($parts === null) {
            return [[], 'Must be a list of tags or a space/comma separated string.'];
        }
        $out = [];
        foreach ($parts as $p) {
            if (!is_string($p)) {
                return [[], 'Each hashtag must be a string.'];
            }
            $t = ltrim(trim($p), '#');
            if (!preg_match('/^[\p{L}\p{N}_]{1,60}$/u', $t)) {
                return [[], 'Hashtags may contain only letters, numbers and underscores.'];
            }
            $out['#' . mb_strtolower($t)] = '#' . $t;
        }
        return [array_values($out), null];
    }

    private function urlError(string $url): ?string
    {
        if (strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Must be a valid URL of at most 255 characters.';
        }
        $p = parse_url($url);
        if (strtolower((string) ($p['scheme'] ?? '')) !== 'https') {
            return 'Only https URLs are allowed.';
        }
        if (isset($p['user']) || isset($p['pass'])) {
            return 'URLs containing credentials are not allowed.';
        }
        $host = strtolower((string) ($p['host'] ?? ''));
        if (isset($p['port']) && (int) $p['port'] !== 443) {
            return 'Custom ports are not allowed.';
        }
        foreach ($this->allowedHosts() as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return null;
            }
        }
        return 'The destination host is not on the approved list (talenthubconsultant.com and its subdomains).';
    }

    private function allowedHosts(): array
    {
        $hosts = self::DEFAULT_URL_HOSTS;
        try {
            $r = $this->db->table('website_settings')->select('setting_value')->where('setting_key', 'agent_allowed_url_hosts')->get()->getRowArray();
            foreach (explode(',', strtolower((string) ($r['setting_value'] ?? ''))) as $h) {
                $h = trim($h);
                if ($h !== '' && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $h)) {
                    $hosts[] = $h;
                }
            }
        } catch (\Throwable) {
        }
        return $hosts;
    }

    /** Normalised fingerprint used for duplicate-content detection. */
    public static function contentHash(string $platform, string $caption): string
    {
        return hash('sha256', $platform . '|' . preg_replace('/\s+/u', ' ', mb_strtolower(trim($caption))));
    }
}
