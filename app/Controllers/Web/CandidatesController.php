<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\WebsitePageModel;

class CandidatesController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('candidates');

        $metaTitle = $page['meta_title'] ?? 'Candidate Career Guidance & Pune Job Opportunities | TalentHub';
        $metaDesc = $page['meta_description'] ?? 'Explore confidential career opportunities across Pune tech parks and industrial manufacturing corridors with zero candidate fees.';

        if ($this->wantsMarkdown()) {
            $md = "# For Candidates — Confidential Career Placement in Pune | TalentHub\n\n"
                . "> {$metaDesc}\n\n"
                . "## Why Candidates Choose TalentHub\n"
                . "1. **100% Free Placement**: Zero placement fees, no hidden registration charges ever.\n"
                . "2. **Confidential Representation**: Your resume and details are never shared without prior consent.\n"
                . "3. **Direct Corporate Access**: Interview directly with verified hiring authorities in Pune.\n\n"
                . "## How to Apply\n"
                . "- Browse Active Jobs: " . base_url('jobs') . "\n"
                . "- Submit Career Profile & Resume: " . base_url('career-enquiry') . "\n"
                . "- Candidate Desk: hr@talenthubconsultant.com\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/candidates', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
        ]);
    }
}

