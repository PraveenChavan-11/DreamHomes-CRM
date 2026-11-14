<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class SubmissionStatusController extends BaseController
{
    /**
     * Dedicated Thank You / Success View
     */
    public function thankYou(): string
    {
        $type = strtolower(trim((string) ($this->request->getGet('type') ?? session()->getFlashdata('submission_type') ?? 'enquiry')));
        $ref = trim((string) ($this->request->getGet('ref') ?? session()->getFlashdata('submission_ref') ?? ''));
        $title = trim((string) ($this->request->getGet('title') ?? session()->getFlashdata('submission_title') ?? ''));
        $flashMsg = session()->getFlashdata('success');

        $configs = [
            'enquiry' => [
                'badge'        => 'Enquiry Registered',
                'heading'      => 'Thank You for Contacting TalentHub',
                'subheading'   => 'Your message has been assigned to our recruitment advisory desk in Katraj, Pune.',
                'timeline'     => [
                    ['step' => '1', 'title' => 'Request Logged', 'desc' => 'Your inquiry details are saved in our verified system.'],
                    ['step' => '2', 'title' => 'Consultant Assigned', 'desc' => 'A dedicated recruitment partner is reviewing your request.'],
                    ['step' => '3', 'title' => 'Direct Callback', 'desc' => 'Expect a response via Phone or WhatsApp within 2–4 business hours.'],
                ],
                'primary_cta'  => ['label' => 'Browse Active Jobs', 'url' => base_url('jobs')],
                'secondary_cta'=> ['label' => 'Explore Hiring Services', 'url' => base_url('solutions')],
            ],
            'employer' => [
                'badge'        => 'Hiring Mandate Received',
                'heading'      => 'Corporate Mandate Successfully Registered',
                'subheading'   => 'Thank you for partnering with TalentHub Consultant. Our executive search desk is on it.',
                'timeline'     => [
                    ['step' => '1', 'title' => 'Mandate Profiled', 'desc' => 'Role specifications and urgency brackets documented.'],
                    ['step' => '2', 'title' => 'Calibration Call', 'desc' => 'Lead partner connects within 24 business hours to align on candidates.'],
                    ['step' => '3', 'title' => 'Curated Shortlist', 'desc' => 'Pre-vetted technical & executive profiles shared within 7 business days.'],
                ],
                'primary_cta'  => ['label' => 'Explore Solutions', 'url' => base_url('solutions')],
                'secondary_cta'=> ['label' => 'Return to Homepage', 'url' => base_url()],
            ],
            'career' => [
                'badge'        => 'Resume Bank Submission',
                'heading'      => 'Profile Catalogued in Private Talent Bank',
                'subheading'   => 'Your CV and career preferences are securely stored in our confidential Pune repository.',
                'timeline'     => [
                    ['step' => '1', 'title' => 'CV Catalogued', 'desc' => 'Indexed against Pune tech corridors (Hinjawadi, Kharadi, Chakan).'],
                    ['step' => '2', 'title' => 'Mandate Matching', 'desc' => 'Automated alerts triggered when matching vacancies open.'],
                    ['step' => '3', 'title' => 'Zero Candidate Fees', 'desc' => '100% free placement guidance and interview scheduling.'],
                ],
                'primary_cta'  => ['label' => 'Browse Live Jobs', 'url' => base_url('jobs')],
                'secondary_cta'=> ['label' => 'Candidate Hub', 'url' => base_url('candidates')],
            ],
            'application' => [
                'badge'        => 'Job Application Submitted',
                'heading'      => 'Application Submitted Successfully',
                'subheading'   => $title ? "Your application for '{$title}' has reached our screening team." : 'Your application has been received by our Pune recruitment team.',
                'timeline'     => [
                    ['step' => '1', 'title' => 'Application Screened', 'desc' => 'Credentials evaluated against employer specifications.'],
                    ['step' => '2', 'title' => 'Shortlist Decision', 'desc' => 'Qualified candidates invited for initial recruitment screening.'],
                    ['step' => '3', 'title' => 'Status Updates', 'desc' => 'Direct notifications sent for interview rounds and feedback.'],
                ],
                'primary_cta'  => ['label' => 'View More Openings', 'url' => base_url('jobs')],
                'secondary_cta'=> ['label' => 'Career Guidance', 'url' => base_url('insights')],
            ],
            'newsletter' => [
                'badge'        => 'Subscription Initialized',
                'heading'      => 'Thank You for Subscribing!',
                'subheading'   => 'Please check your email inbox to confirm your double opt-in verification link.',
                'timeline'     => [
                    ['step' => '1', 'title' => 'Verification Email Sent', 'desc' => 'A secure double opt-in link was dispatched to your inbox.'],
                    ['step' => '2', 'title' => 'Click to Confirm', 'desc' => 'Verify within 48 hours to activate verified job alerts.'],
                    ['step' => '3', 'title' => 'Curated Intel', 'desc' => 'Weekly Hinjawadi & Chakan compensation updates with zero spam.'],
                ],
                'primary_cta'  => ['label' => 'Browse Active Jobs', 'url' => base_url('jobs')],
                'secondary_cta'=> ['label' => 'Return to Homepage', 'url' => base_url()],
            ],
        ];

        $config = $configs[$type] ?? $configs['enquiry'];
        if ($flashMsg) {
            $config['subheading'] = $flashMsg;
        }

        return $this->renderView('public/status/thank_you', [
            'meta_title'       => $config['heading'] . ' — TalentHub Consultant',
            'meta_description' => $config['subheading'],
            'config'           => $config,
            'type'             => $type,
            'ref'              => $ref,
        ]);
    }

    /** Only same-site targets are allowed (relative path or this host); anything else becomes the home page. */
    private static function safeLocalUrl(mixed $url): string
    {
        $fallback = base_url();
        if (!is_string($url) || $url === '' || strlen($url) > 300 || preg_match('/[\x00-\x1F\\\\]/', $url)) {
            return $fallback;
        }
        if ($url[0] === '/' && !str_starts_with($url, '//')) {
            return base_url(ltrim($url, '/'));
        }
        $host = parse_url($url, PHP_URL_HOST);
        $own  = parse_url(base_url(), PHP_URL_HOST);
        return ($host && $own && strcasecmp((string) $host, (string) $own) === 0 && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) ? $url : $fallback;
    }

    /**
     * Dedicated Submission Failed / Error View
     */
    public function failed(): string
    {
        $type = strtolower(trim((string) ($this->request->getGet('type') ?? session()->getFlashdata('submission_type') ?? 'submission')));
        // Text on this page must never be attacker-controlled: only server-set flash messages or fixed reason codes are shown.
        $reasons = [
            'server_error' => 'We could not complete your submission because of a problem on our side. Your information was not lost. Please try again in a few minutes or contact us directly.',
            'validation'   => 'Some of the details provided could not be accepted. Please check them and try again.',
            'rate_limited' => 'Too many attempts were made from your connection. Please wait a few minutes and try again.',
            'file_rejected' => 'The file you uploaded could not be accepted. Please upload a PDF or Word document under 5 MB.',
        ];
        $code   = (string) $this->request->getGet('reason');
        $reason = session()->getFlashdata('error') ?? ($reasons[$code] ?? 'An unexpected validation or network error occurred during your submission.');
        $returnUrl = self::safeLocalUrl(session()->getFlashdata('return_url') ?? $this->request->getGet('return_url'));

        return $this->renderView('public/status/failed', [
            'meta_title'       => 'Submission Notice — TalentHub Consultant',
            'meta_description' => 'We could not complete your submission. Please try again or reach out directly to our hiring team.',
            'type'             => $type,
            'reason'           => $reason,
            'returnUrl'        => $returnUrl,
        ]);
    }
}

