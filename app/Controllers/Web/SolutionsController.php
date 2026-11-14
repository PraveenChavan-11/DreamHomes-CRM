<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\WebsitePageModel;

class SolutionsController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('solutions');

        $metaTitle = $page['meta_title'] ?? 'Recruitment Solutions & Staffing Tracks | TalentHub';
        $metaDesc = $page['meta_description'] ?? 'Specialized recruitment solutions for IT & Software, Automotive, Core Engineering, Finance, and Executive Search across Pune.';

        if ($this->wantsMarkdown()) {
            $md = "# Recruitment Solutions & Staffing Tracks | TalentHub Pune\n\n"
                . "> {$metaDesc}\n\n"
                . "## Core Practice Verticals\n"
                . "1. **IT & Enterprise Software**: Full-stack developers, cloud architects, DevOps, QA automation, and tech leads across Hinjawadi, Kharadi, and Baner.\n"
                . "2. **Automotive & Manufacturing**: Plant heads, production managers, design engineers, and quality assurance specialists in Chakan, Bhosari, and Talegaon MIDC.\n"
                . "3. **Banking, Financial Services & Insurance (BFSI)**: Senior accountants, financial controllers, credit risk analysts, and internal auditors.\n"
                . "4. **Executive Search (CXO Mandates)**: Retained and contingent leadership recruitment for Managing Directors, VP Engineering, and Chief Commercial Officers.\n\n"
                . "## Commercial Models\n"
                . "- Contingency Search: 100% success-based fee payable after joining.\n"
                . "- Replacement Assurance: 90-day comprehensive guarantee.\n"
                . "- Contact: hr@talenthubconsultant.com | +91 72761 46573\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/solutions', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
        ]);
    }
}

