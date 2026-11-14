<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\TestimonialModel;
use App\Models\WebsitePageModel;

class AboutController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('about');

        $testModel = new TestimonialModel();
        $testimonials = $testModel->getPublishedTestimonials();

        $metaTitle = $page['meta_title'] ?? 'About TalentHub Consultant — Katraj, Pune Recruitment Experts';
        $metaDesc = $page['meta_description'] ?? 'Learn about TalentHub Consultant, our Pune recruitment directors, enterprise ethics, and high-precision placement methodology.';

        if ($this->wantsMarkdown()) {
            $md = "# About TalentHub Consultant — Pune Recruitment & Executive Search\n\n"
                . "> {$metaDesc}\n\n"
                . "## Who We Are\n"
                . "TalentHub Consultant is Pune's leading job consultancy and executive search firm, based in Katraj and serving major industrial and technology corridors including Hinjawadi, Kharadi, Magarpatta, Baner, and Chakan MIDC.\n\n"
                . "## Core Recruitment Philosophy\n"
                . "1. **Uncompromising Transparency**: Complete disclosure of CTC structures, role mandates, and client expectations.\n"
                . "2. **Deep Technical Vetting**: Real hands-on assessment of candidate engineering competencies.\n"
                . "3. **Zero Candidate Fees**: 100% free service for job seekers.\n\n"
                . "## Contact Information\n"
                . "- **Office Address**: Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046, India\n"
                . "- **Phone**: +91 72761 46573\n"
                . "- **Email**: hr@talenthubconsultant.com\n"
                . "- **Website**: " . base_url() . "\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/about', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
            'testimonials'     => $testimonials,
        ]);
    }
}

