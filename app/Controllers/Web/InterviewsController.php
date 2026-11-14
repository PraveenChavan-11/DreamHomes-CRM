<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\WebsitePageModel;

class InterviewsController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('interviews');

        $sessionParam = $this->request->getGet('session') ?: $this->request->getGet('invite');
        $rawTeamsLink = $this->siteSettings['global_teams_link'] ?? '';

        // Disallow placeholder/fallback links
        $isFallback = empty($rawTeamsLink) || str_contains($rawTeamsLink, 'fallback-talenthub-session') || str_contains($rawTeamsLink, 'example.com');
        $hasValidInvitation = false;
        $teamsLink = null;

        // If candidate accessed via a personalized invitation query token
        if (!empty($sessionParam) && preg_match('/^[a-zA-Z0-9_\-]{8,64}$/', (string) $sessionParam)) {
            // Valid token format - lookup or map session
            $hasValidInvitation = true;
            $teamsLink = 'https://teams.microsoft.com/l/meetup-join/' . urlencode((string) $sessionParam);
        } elseif (!$isFallback && filter_var($rawTeamsLink, FILTER_VALIDATE_URL) && str_starts_with($rawTeamsLink, 'https://teams.microsoft.com/')) {
            $hasValidInvitation = true;
            $teamsLink = $rawTeamsLink;
        }

        $instructions = $this->siteSettings['teams_meeting_instructions'] ?? 'Please join the meeting 5 minutes early. Ensure your camera and microphone are operational. Have a copy of your resume ready.';

        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : 'Interview Preparation & Career Guidance | TalentHub Pune';
        $metaDesc = !empty($page['meta_description']) ? $page['meta_description'] : 'Direct virtual interview coordination room and technical preparation instructions for TalentHub candidates in Pune.';

        if ($this->wantsMarkdown()) {
            $md = "# Virtual Interview Briefing & Guidelines | TalentHub Consultant\n\n"
                . "> Technical preparation and interview protocols for candidates undergoing screening with TalentHub Consultant Pune.\n\n"
                . "## Interview Instructions\n"
                . $instructions . "\n\n"
                . "## Access Protocols\n"
                . ($hasValidInvitation
                    ? "Your session invitation is active: " . $teamsLink . "\n"
                    : "Individual interview room invitations are dispatched directly to shortlisted candidates via email.\n")
                . "\n## Support Desk\n"
                . "For rescheduling or urgent connection assistance, contact: hr@talenthubconsultant.com | +91 72761 46573\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/interviews', [
            'meta_title'         => $metaTitle,
            'meta_description'   => $metaDesc,
            'page'               => $page,
            'teamsLink'          => $teamsLink,
            'hasValidInvitation' => $hasValidInvitation,
            'instructions'       => $instructions,
        ]);
    }
}

