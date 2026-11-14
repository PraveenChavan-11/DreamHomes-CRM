<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Social\SocialSecurityService;
use App\Services\Social\SocialSettingsService;
use App\Services\Social\SocialPublishingEngine;
use App\Services\Social\SocialSchedulerService;
use App\Services\Social\SocialContentAssistantService;
use App\Services\Social\SocialMediaLibraryService;
use App\Services\Social\SocialAnalyticsService;
use App\Services\Social\SocialEnquiryService;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\InstagramApiService;
use Config\Database;

/**
 * Enterprise Social Media Marketing & Management Centre Controller
 * 
 * Provides unified administrative endpoints for marketing overview,
 * post studio, content calendar, scheduling queue, media library,
 * organic campaigns, social enquiries, engagement, analytics, and settings.
 */
class SocialMarketingController extends BaseController
{
    use \App\Controllers\Traits\GuardsAdminPermissions;

    /**
     * Server-side access control (fail-closed: a handler not listed here is refused for everyone but Super Admin).
     * Sidebar visibility is NOT access control. Each list means "any one of".
     */
    protected const PERMISSIONS = [
        // read-only screens
        'overview' => self::VIEW, 'posts' => self::VIEW, 'showPost' => self::VIEW, 'calendar' => self::VIEW, 'scheduled' => self::VIEW, 'drafts' => self::VIEW,
        'queue' => self::VIEW, 'media' => self::VIEW, 'campaigns' => self::VIEW, 'comments' => self::VIEW, 'logs' => self::VIEW, 'accounts' => self::VIEW,
        'analytics' => ['social.analytics', 'social.view', 'social.manage'],
        'enquiries' => ['social.enquiries', 'social.view', 'social.manage'],
        // authoring
        'createPost' => self::CREATE, 'storePost' => self::CREATE, 'assistantSuggest' => self::CREATE, 'assistantRewrite' => self::CREATE,
        'uploadMedia' => ['social.create', 'social.manage'],
        'campaignForm' => ['social.manage', 'social.create'], 'saveCampaign' => ['social.manage', 'social.create'],
        // going live / operating the queue
        'publishNow' => self::PUBLISH, 'reschedulePost' => self::PUBLISH, 'retryJob' => self::PUBLISH, 'cancelJob' => self::PUBLISH,
        // destructive
        'deletePost' => ['social.manage'], 'deleteMedia' => ['social.manage'],
        // enquiries contain personal data
        'storeEnquiry' => ['social.enquiries', 'social.manage'], 'updateEnquiryStatus' => ['social.enquiries', 'social.manage'], 'exportEnquiries' => ['social.enquiries', 'social.manage'],
        // integration credentials
        'settings' => ['social.settings'], 'saveSettings' => ['social.settings'],
    ];
    private const VIEW    = ['social.view', 'social.create', 'social.publish', 'social.manage'];
    private const CREATE  = ['social.create', 'social.publish', 'social.manage'];
    private const PUBLISH = ['social.publish', 'social.manage'];

    private $db;
    private SocialSecurityService $security;
    private SocialSettingsService $settings;
    private SocialPublishingEngine $publishingEngine;
    private SocialSchedulerService $scheduler;
    private SocialContentAssistantService $assistant;
    private SocialMediaLibraryService $mediaLibrary;
    private SocialAnalyticsService $analytics;
    private SocialEnquiryService $enquiryService;
    private LinkedInApiService $linkedIn;
    private InstagramApiService $instagram;

    public function __construct()
    {
        $this->db              = Database::connect();
        $this->security        = new SocialSecurityService($this->db);
        $this->settings        = new SocialSettingsService($this->db, $this->security);
        $this->linkedIn        = new LinkedInApiService($this->db, $this->security, $this->settings);
        $this->instagram       = new InstagramApiService($this->db, $this->security, $this->settings);
        $this->publishingEngine= new SocialPublishingEngine($this->db, $this->linkedIn, $this->instagram, $this->security, $this->settings);
        $this->scheduler       = new SocialSchedulerService($this->db, $this->publishingEngine, $this->settings);
        $this->assistant       = new SocialContentAssistantService($this->db);
        $this->mediaLibrary    = new SocialMediaLibraryService($this->db, $this->security);
        $this->analytics       = new SocialAnalyticsService($this->db, $this->linkedIn, $this->instagram);
        $this->enquiryService  = new SocialEnquiryService($this->db, $this->settings);

        // Ensure database tables exist lazily
        if (!$this->db->tableExists('social_accounts')) {
            require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';
            \App\Database\Migrations\CreateSocialMarketingTables::apply($this->db);
        }
    }

    private function getCommonCounts(): array
    {
        return [
            'total_posts'     => $this->db->table('social_posts')->countAllResults(),
            'scheduled_posts' => $this->db->table('social_posts')->where('status', 'scheduled')->countAllResults(),
            'draft_posts'     => $this->db->table('social_posts')->where('status', 'draft')->countAllResults(),
            'failed_posts'    => $this->db->table('social_posts')->where('status', 'failed')->countAllResults(),
            'queued_jobs'     => $this->db->table('social_scheduled_jobs')->where('status', 'queued')->countAllResults(),
            'new_enquiries'   => $this->db->table('social_enquiries')->where('lead_status', 'new')->countAllResults(),
        ];
    }

    /**
     * Marketing Overview Dashboard
     */
    public function overview()
    {
        $dateRange = (string) ($this->request->getGet('range') ?? '30_days');
        $platform  = (string) ($this->request->getGet('platform') ?? 'all');

        $kpis   = $this->analytics->getOverviewKpis($dateRange, $platform);
        $trends = $this->analytics->getPublishingTrends(30);

        $recentPosts = $this->db->table('social_posts')
            ->orderBy('id', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        $recentEnquiries = $this->db->table('social_enquiries')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $accounts = $this->db->table('social_accounts')->get()->getResultArray();

        return view('admin/social/overview', [
            'activeMenu'      => 'social_overview',
            'title'           => 'Social Media Marketing Centre — Overview',
            'kpis'            => $kpis,
            'trends'          => $trends,
            'recentPosts'     => $recentPosts,
            'recentEnquiries' => $recentEnquiries,
            'accounts'        => $accounts,
            'counts'          => $this->getCommonCounts(),
        ]);
    }

    /**
     * All Posts List
     */
    public function posts()
    {
        $status   = (string) ($this->request->getGet('status') ?? '');
        $platform = (string) ($this->request->getGet('platform') ?? '');
        $search   = trim((string) ($this->request->getGet('q') ?? ''));

        $builder = $this->db->table('social_posts');
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($platform !== '') {
            $builder->groupStart()
                ->where('primary_platform', $platform)
                ->orWhere('primary_platform', 'both')
            ->groupEnd();
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('base_content', $search)
            ->groupEnd();
        }

        $posts = $builder->orderBy('id', 'DESC')->get()->getResultArray();

        return view('admin/social/posts/index', [
            'activeMenu' => 'social_posts',
            'title'      => 'All Social Posts — TalentHub',
            'posts'      => $posts,
            'filterStatus'   => $status,
            'filterPlatform' => $platform,
            'searchQuery'    => $search,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Post Creation Studio (Composer)
     */
    public function createPost()
    {
        $campaigns = $this->db->table('social_campaigns')->where('status', 'active')->get()->getResultArray();
        $templates = $this->db->table('social_content_templates')->where('is_active', 1)->get()->getResultArray();
        $accounts  = $this->db->table('social_accounts')->where('status', 'active')->get()->getResultArray();
        $media     = $this->mediaLibrary->getMediaList([], 20)['items'];

        return view('admin/social/posts/create', [
            'activeMenu' => 'social_create_post',
            'title'      => 'Create Social Post — Content Studio',
            'campaigns'  => $campaigns,
            'templates'  => $templates,
            'accounts'   => $accounts,
            'mediaItems' => $media,
            'categories' => SocialContentAssistantService::CATEGORIES,
            'tones'      => SocialContentAssistantService::TONES,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Store new post (Draft, Scheduled, or Publish Now)
     */
    public function storePost()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $title       = trim((string) $this->request->getPost('title'));
        $platform    = (string) $this->request->getPost('platform') ?: 'both';
        $content     = trim((string) $this->request->getPost('base_content'));
        $hashtags    = trim((string) $this->request->getPost('base_hashtags'));
        $cta         = trim((string) $this->request->getPost('base_cta'));
        $destUrl     = trim((string) $this->request->getPost('base_destination_url'));
        $action      = (string) $this->request->getPost('submit_action') ?: 'draft'; // draft, schedule, publish_now
        $scheduledAt = $this->request->getPost('scheduled_at');
        $campaignId  = $this->request->getPost('campaign_id');
        $mediaIds    = $this->request->getPost('media_ids') ?: [];

        if (in_array($action, ['publish_now', 'schedule'], true) && !$this->adminHasAny(self::PUBLISH)) {
            return redirect()->back()->withInput()->with('error', 'You may save drafts, but publishing or scheduling needs the Social publish permission.');
        }

        if ($title === '' || $content === '') {
            return redirect()->back()->withInput()->with('error', 'Post internal title and caption are required.');
        }

        $status = 'draft';
        if ($action === 'publish_now') {
            $status = 'processing';
        } elseif ($action === 'schedule') {
            if (empty($scheduledAt)) {
                return redirect()->back()->withInput()->with('error', 'Scheduled date and time are required to schedule a post.');
            }
            $status = 'scheduled';
        }

        $postData = [
            'title'                => $title,
            'campaign_id'          => !empty($campaignId) ? (int) $campaignId : null,
            'primary_platform'     => $platform,
            'post_objective'       => $this->request->getPost('post_objective'),
            'base_content'         => $content,
            'base_hashtags'        => $hashtags,
            'base_cta'             => $cta,
            'base_destination_url' => $destUrl,
            'status'               => $status,
            'scheduled_at'         => !empty($scheduledAt) ? date('Y-m-d H:i:s', strtotime($scheduledAt)) : null,
            'timezone'             => 'Asia/Kolkata',
            'created_by'           => $userId,
            'created_at'           => date('Y-m-d H:i:s'),
        ];

        $this->db->table('social_posts')->insert($postData);
        $postId = (int) $this->db->insertID();

        // Platform-specific caption overrides
        $liCaption = trim((string) $this->request->getPost('linkedin_caption')) ?: $content;
        $liHashtags= trim((string) $this->request->getPost('linkedin_hashtags')) ?: $hashtags;
        $igCaption = trim((string) $this->request->getPost('instagram_caption')) ?: $content;
        $igHashtags= trim((string) $this->request->getPost('instagram_hashtags')) ?: $hashtags;

        if ($platform === 'both' || $platform === 'linkedin') {
            $this->db->table('social_post_platforms')->insert([
                'post_id'         => $postId,
                'platform'        => 'linkedin',
                'account_id'      => $this->publishingEngine->getDefaultAccountId('linkedin'),
                'caption'         => $liCaption,
                'hashtags'        => $liHashtags,
                'cta'             => $cta,
                'destination_url' => $destUrl,
                'status'          => 'pending',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        if ($platform === 'both' || $platform === 'instagram') {
            $this->db->table('social_post_platforms')->insert([
                'post_id'         => $postId,
                'platform'        => 'instagram',
                'account_id'      => $this->publishingEngine->getDefaultAccountId('instagram'),
                'caption'         => $igCaption,
                'hashtags'        => $igHashtags,
                'cta'             => $cta,
                'destination_url' => $destUrl,
                'status'          => 'pending',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        // Attach media items
        if (!empty($mediaIds) && is_array($mediaIds)) {
            $this->mediaLibrary->attachMediaToPost($postId, $mediaIds);
        }

        // Handle Immediate Publishing
        if ($action === 'publish_now') {
            $pubRes = $this->publishingEngine->publishPost($postId, $userId);
            if ($pubRes['success']) {
                return redirect()->to(base_url("admin/social/posts/{$postId}"))->with('success', 'Post published successfully across platforms!');
            }
            return redirect()->to(base_url("admin/social/posts/{$postId}"))->with('error', 'Post publishing completed with errors. See platform status.');
        }

        // Handle Scheduling
        if ($action === 'schedule') {
            $this->db->table('social_scheduled_jobs')->insert([
                'post_id'         => $postId,
                'platform'        => $platform === 'both' ? 'linkedin' : $platform,
                'scheduled_at'    => $postData['scheduled_at'],
                'status'          => 'queued',
                'attempts'        => 0,
                'max_attempts'    => 3,
                'next_attempt_at' => $postData['scheduled_at'],
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('admin/social/scheduled'))->with('success', "Post scheduled for {$postData['scheduled_at']}.");
        }

        return redirect()->to(base_url('admin/social/drafts'))->with('success', 'Post draft saved successfully.');
    }

    /**
     * Show Post Details & Publishing Attempts
     */
    public function showPost(int $id)
    {
        $post = $this->db->table('social_posts')->where('id', $id)->get()->getRowArray();
        if (!$post) {
            return redirect()->to(base_url('admin/social/posts'))->with('error', 'Post not found.');
        }

        $platforms = $this->db->table('social_post_platforms')->where('post_id', $id)->get()->getResultArray();
        $media     = $this->db->table('social_post_media')->where('post_id', $id)->get()->getResultArray();
        $attempts  = $this->db->table('social_publish_attempts')->where('post_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $analytics = $this->db->table('social_analytics_snapshots')->where('post_id', $id)->get()->getResultArray();

        return view('admin/social/posts/show', [
            'activeMenu' => 'social_posts',
            'title'      => "Post #{$id}: {$post['title']}",
            'post'       => $post,
            'platforms'  => $platforms,
            'media'      => $media,
            'attempts'   => $attempts,
            'analytics'  => $analytics,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Publish an existing draft or failed post now
     */
    public function publishNow(int $id)
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $res = $this->publishingEngine->publishPost($id, $userId);
        if ($res['success']) {
            return redirect()->to(base_url("admin/social/posts/{$id}"))->with('success', 'Post published successfully!');
        }
        return redirect()->to(base_url("admin/social/posts/{$id}"))->with('error', 'Publishing completed with failures. Please review platform errors.');
    }

    /**
     * Reschedule post
     */
    public function reschedulePost(int $id)
    {
        $newDate = $this->request->getPost('scheduled_at');
        if (empty($newDate)) {
            return $this->response->setJSON(['success' => false, 'message' => 'New scheduled date is required.']);
        }

        // Columns only exist once the additive agent migration has been applied; before that every post is human-created.
        $guard = $this->db->fieldExists('origin', 'social_posts')
            ? $this->db->table('social_posts')->select('origin, review_decision, approved_by')->where('id', $id)->get()->getRowArray()
            : null;
        if ($guard && ($guard['origin'] ?? 'admin') === 'agent' && !(($guard['review_decision'] ?? null) === 'approved' && !empty($guard['approved_by']))) {
            $msg = 'This AI-agent draft must be approved (Admin → External AI Agents → Draft approvals) before it can be scheduled.';
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(409)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        $dt = date('Y-m-d H:i:s', strtotime($newDate));
        $this->db->table('social_posts')->where('id', $id)->update([
            'status'       => 'scheduled',
            'scheduled_at' => $dt,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('social_scheduled_jobs')
            ->where('post_id', $id)
            ->whereIn('status', ['queued', 'failed'])
            ->update([
                'scheduled_at'    => $dt,
                'next_attempt_at' => $dt,
                'status'          => 'queued',
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'scheduled_at' => $dt]);
        }

        return redirect()->back()->with('success', "Post rescheduled for {$dt}.");
    }

    /**
     * Delete post
     */
    public function deletePost(int $id)
    {
        $this->db->table('social_posts')->where('id', $id)->delete();
        $this->db->table('social_post_platforms')->where('post_id', $id)->delete();
        $this->db->table('social_post_media')->where('post_id', $id)->delete();
        $this->db->table('social_scheduled_jobs')->where('post_id', $id)->delete();

        return redirect()->to(base_url('admin/social/posts'))->with('success', 'Post deleted successfully.');
    }

    /**
     * Content Calendar View
     */
    public function calendar()
    {
        $posts = $this->db->table('social_posts')
            ->whereIn('status', ['scheduled', 'published', 'draft'])
            ->orderBy('scheduled_at', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/social/calendar', [
            'activeMenu' => 'social_calendar',
            'title'      => 'Content Marketing Calendar — TalentHub',
            'posts'      => $posts,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Scheduled Posts List
     */
    public function scheduled()
    {
        $posts = $this->db->table('social_posts')
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/social/posts/index', [
            'activeMenu'     => 'social_scheduled',
            'title'          => 'Scheduled Social Posts — TalentHub',
            'posts'          => $posts,
            'filterStatus'   => 'scheduled',
            'filterPlatform' => '',
            'searchQuery'    => '',
            'counts'         => $this->getCommonCounts(),
        ]);
    }

    /**
     * Draft Posts List
     */
    public function drafts()
    {
        $posts = $this->db->table('social_posts')
            ->where('status', 'draft')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/social/posts/index', [
            'activeMenu'     => 'social_drafts',
            'title'          => 'Draft Social Posts — TalentHub',
            'posts'          => $posts,
            'filterStatus'   => 'draft',
            'filterPlatform' => '',
            'searchQuery'    => '',
            'counts'         => $this->getCommonCounts(),
        ]);
    }

    /**
     * Publishing Queue Runner View
     */
    public function queue()
    {
        $jobs = $this->db->table('social_scheduled_jobs')
            ->select('social_scheduled_jobs.*, social_posts.title as post_title')
            ->join('social_posts', 'social_posts.id = social_scheduled_jobs.post_id', 'left')
            ->orderBy('social_scheduled_jobs.id', 'DESC')
            ->get()
            ->getResultArray();

        $attempts = $this->db->table('social_publish_attempts')
            ->select('social_publish_attempts.*, social_posts.title as post_title')
            ->join('social_posts', 'social_posts.id = social_publish_attempts.post_id', 'left')
            ->orderBy('social_publish_attempts.id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return view('admin/social/queue', [
            'activeMenu' => 'social_queue',
            'title'      => 'Publishing Queue & Scheduler Status',
            'jobs'       => $jobs,
            'attempts'   => $attempts,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Retry a failed job immediately
     */
    public function retryJob(int $jobId)
    {
        $job = $this->db->table('social_scheduled_jobs')->where('id', $jobId)->get()->getRowArray();
        if (!$job) {
            return redirect()->back()->with('error', 'Job not found.');
        }

        $this->db->table('social_scheduled_jobs')->where('id', $jobId)->update([
            'status'          => 'queued',
            'attempts'        => 0,
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'locked_at'       => null,
            'locked_by'       => null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Job queued for immediate retry.');
    }

    /**
     * Cancel a queued job
     */
    public function cancelJob(int $jobId)
    {
        $this->db->table('social_scheduled_jobs')->where('id', $jobId)->update([
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Job cancelled.');
    }

    /**
     * Media Library View
     */
    public function media()
    {
        $category = (string) ($this->request->getGet('category') ?? '');
        $type     = (string) ($this->request->getGet('type') ?? '');
        $search   = (string) ($this->request->getGet('q') ?? '');

        $mediaData = $this->mediaLibrary->getMediaList([
            'category'   => $category,
            'media_type' => $type,
            'search'     => $search,
        ], 36);

        return view('admin/social/media/index', [
            'activeMenu'     => 'social_media',
            'title'          => 'Marketing Media Library — TalentHub',
            'mediaItems'     => $mediaData['items'],
            'total'          => $mediaData['total'],
            'filterCategory' => $category,
            'filterType'     => $type,
            'searchQuery'    => $search,
            'counts'         => $this->getCommonCounts(),
        ]);
    }

    /**
     * Upload Media AJAX endpoint
     */
    public function uploadMedia()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $file = $this->request->getFile('media_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please provide a valid media file.']);
        }

        $category = (string) ($this->request->getPost('category') ?? 'corporate_branding');

        try {
            $record = $this->mediaLibrary->storeUploadedFile($file, $category, null, $userId);
            return $this->response->setJSON([
                'success' => true,
                'media'   => $record,
                'message' => 'Media asset uploaded and verified.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Social media upload failed: ' . get_class($e) . ': ' . $e->getMessage());
            $internal = $e instanceof \CodeIgniter\Database\Exceptions\DatabaseException || $e instanceof \Error || $e instanceof \ErrorException;
            return $this->response->setJSON(['success' => false, 'message' => $internal ? 'The upload could not be saved. Please try again.' : $e->getMessage()]);
        }
    }

    /**
     * Delete Media
     */
    public function deleteMedia(int $id)
    {
        $this->mediaLibrary->deleteMedia($id);
        return redirect()->back()->with('success', 'Media asset removed.');
    }

    /**
     * Organic Campaigns Management
     */
    public function campaigns()
    {
        $campaigns = $this->db->table('social_campaigns')->orderBy('id', 'DESC')->get()->getResultArray();

        return view('admin/social/campaigns/index', [
            'activeMenu' => 'social_campaigns',
            'title'      => 'Marketing Campaigns — TalentHub',
            'campaigns'  => $campaigns,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Create / Edit Campaign Form
     */
    public function campaignForm(?int $id = null)
    {
        $campaign = null;
        if ($id) {
            $campaign = $this->db->table('social_campaigns')->where('id', $id)->get()->getRowArray();
        }

        return view('admin/social/campaigns/form', [
            'activeMenu' => 'social_campaigns',
            'title'      => $id ? 'Edit Campaign' : 'Create Marketing Campaign',
            'campaign'   => $campaign,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Save Campaign
     */
    public function saveCampaign(?int $id = null)
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Campaign name is required.');
        }

        $slug = url_title($name, '-', true);

        $payload = [
            'name'            => $name,
            'slug'            => $slug,
            'objective'       => $this->request->getPost('objective') ?: 'brand_awareness',
            'description'     => $this->request->getPost('description'),
            'target_audience' => $this->request->getPost('target_audience'),
            'platform'        => $this->request->getPost('platform') ?: 'all',
            'start_date'      => $this->request->getPost('start_date') ?: null,
            'end_date'        => $this->request->getPost('end_date') ?: null,
            'budget'          => (float) ($this->request->getPost('budget') ?? 0),
            'status'          => $this->request->getPost('status') ?: 'draft',
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $this->db->table('social_campaigns')->where('id', $id)->update($payload);
        } else {
            $payload['created_by'] = $userId;
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('social_campaigns')->insert($payload);
        }

        return redirect()->to(base_url('admin/social/campaigns'))->with('success', 'Campaign saved successfully.');
    }

    /**
     * Social Enquiries / Inbound Leads
     */
    public function enquiries()
    {
        $platform = (string) ($this->request->getGet('platform') ?? '');
        $status   = (string) ($this->request->getGet('status') ?? '');

        $builder = $this->db->table('social_enquiries')->orderBy('id', 'DESC');
        if ($platform !== '') $builder->where('source_platform', $platform);
        if ($status !== '')   $builder->where('lead_status', $status);

        $enquiries = $builder->get()->getResultArray();

        return view('admin/social/enquiries/index', [
            'activeMenu'     => 'social_enquiries',
            'title'          => 'Social Enquiries & Leads — TalentHub',
            'enquiries'      => $enquiries,
            'filterPlatform' => $platform,
            'filterStatus'   => $status,
            'statuses'       => SocialEnquiryService::STATUSES,
            'counts'         => $this->getCommonCounts(),
        ]);
    }

    /**
     * Store manual enquiry
     */
    public function storeEnquiry()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        try {
            $res = $this->enquiryService->captureEnquiry($this->request->getPost(), $userId);
            $msg = 'Social lead recorded successfully.';
            if ($res['is_duplicate']) {
                $msg .= ' (Note: Detected existing enquiry with same contact credentials).';
            }
            return redirect()->to(base_url('admin/social/enquiries'))->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', \App\Services\JobRunRecorder::sanitize($e->getMessage(), 200));
        }
    }

    /**
     * Update enquiry status
     */
    public function updateEnquiryStatus(int $id)
    {
        $status = (string) $this->request->getPost('lead_status');
        $notes  = $this->request->getPost('notes');

        $this->enquiryService->updateStatus($id, $status, $notes);
        return redirect()->back()->with('success', 'Lead status updated.');
    }

    /**
     * Export enquiries to CSV
     */
    public function exportEnquiries()
    {
        $csv = $this->analytics->exportEnquiriesCsv($this->request->getGet());
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="talenthub_social_leads_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
    }

    /**
     * Comments & Engagement View
     */
    public function comments()
    {
        $comments = $this->db->table('social_comments')->orderBy('comment_date', 'DESC')->get()->getResultArray();

        return view('admin/social/comments/index', [
            'activeMenu' => 'social_engagement',
            'title'      => 'Comments & Engagement — TalentHub',
            'comments'   => $comments,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Analytics & Reports View
     */
    public function analytics()
    {
        $range    = (string) ($this->request->getGet('range') ?? '30_days');
        $platform = (string) ($this->request->getGet('platform') ?? 'all');

        $kpis   = $this->analytics->getOverviewKpis($range, $platform);
        $trends = $this->analytics->getPublishingTrends(30);

        return view('admin/social/analytics/index', [
            'activeMenu'      => 'social_analytics',
            'title'           => 'Social Marketing Analytics & Reports',
            'kpis'            => $kpis,
            'trends'          => $trends,
            'selectedRange'   => $range,
            'selectedPlatform'=> $platform,
            'counts'          => $this->getCommonCounts(),
        ]);
    }

    /**
     * Social Accounts List & Permissions
     */
    public function accounts()
    {
        $accounts = $this->db->table('social_accounts')->get()->getResultArray();

        return view('admin/social/accounts/index', [
            'activeMenu' => 'social_accounts',
            'title'      => 'Connected Social Accounts — TalentHub',
            'accounts'   => $accounts,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * Integration Settings & Health Checker
     */
    public function settings()
    {
        $linkedInConfig  = $this->settings->getLinkedInConfig();
        $instagramConfig = $this->settings->getInstagramConfig();
        $healthCheck     = $this->settings->runHealthCheck();

        return view('admin/social/settings/index', [
            'activeMenu'      => 'social_settings',
            'title'           => 'Social API Integration Settings — TalentHub',
            'linkedInConfig'  => $linkedInConfig,
            'instagramConfig' => $instagramConfig,
            'healthCheck'     => $healthCheck,
            'counts'          => $this->getCommonCounts(),
        ]);
    }

    /**
     * Save API Settings
     */
    public function saveSettings()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $platform = (string) $this->request->getPost('platform');

        try {
            if ($platform === 'linkedin') {
                $this->settings->saveLinkedInConfig($this->request->getPost(), $userId);
                return redirect()->back()->with('success', 'LinkedIn API credentials saved securely.');
            }

            if ($platform === 'instagram') {
                $this->settings->saveInstagramConfig($this->request->getPost(), $userId);
                return redirect()->back()->with('success', 'Meta / Instagram API credentials saved securely.');
            }

            return redirect()->back()->with('error', 'Invalid platform selection.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error saving settings: ' . \App\Services\JobRunRecorder::sanitize($e->getMessage(), 200));
        }
    }

    /**
     * Activity Logs View
     */
    public function logs()
    {
        $logs = $this->db->table('social_activity_logs')
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('admin/social/logs/index', [
            'activeMenu' => 'social_logs',
            'title'      => 'Social Marketing Activity & Audit Logs',
            'logs'       => $logs,
            'counts'     => $this->getCommonCounts(),
        ]);
    }

    /**
     * AJAX Endpoint: Content Assistant Generate Suggestions
     */
    public function assistantSuggest()
    {
        $category = (string) ($this->request->getPost('category') ?? 'Recruitment & Hiring');
        $tone     = (string) ($this->request->getPost('tone') ?? 'Professional');
        $topic    = (string) ($this->request->getPost('topic') ?? '');
        $platform = (string) ($this->request->getPost('platform') ?? 'both');

        $suggestions = $this->assistant->generateSuggestions($category, $tone, $topic, $platform);
        return $this->response->setJSON(['success' => true, 'data' => $suggestions]);
    }

    /**
     * AJAX Endpoint: Content Assistant Rewrite
     */
    public function assistantRewrite()
    {
        $text  = (string) $this->request->getPost('text');
        $tone  = (string) ($this->request->getPost('tone') ?? 'Professional');

        $result = $this->assistant->rewriteCaption($text, $tone);
        return $this->response->setJSON(['success' => true, 'data' => $result]);
    }
}

