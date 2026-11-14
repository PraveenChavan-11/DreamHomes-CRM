<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\BlogPostModel;
use App\Models\FaqModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use App\Models\TestimonialModel;
use App\Models\WebsitePageModel;

class HomeController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $jobModel = new JobModel();
        $catModel = new JobCategoryModel();
        $faqModel = new FaqModel();
        $testModel = new TestimonialModel();
        $blogModel = new BlogPostModel();

        $page = $pageModel->getPublishedBySlug('home');
        $featuredJobs = $jobModel->getPublishedJobs([], 4, 0);
        $categories = $catModel->getActiveCategories();
        $testimonials = $testModel->getPublishedTestimonials();
        $faqs = $faqModel->getPublishedFaqs();
        $recentBlogs = $blogModel->getPublishedPosts(3, 0);

        $metaTitle = $page['meta_title'] ?? ($this->siteSettings['site_name'] ?? 'TalentHub Consultant') . ' — Premier Job Consultancy in Pune';
        $metaDesc = $page['meta_description'] ?? 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises across Hinjawadi, Kharadi, and Chakan.';

        // Serve Markdown representation to AI agents when requested (SEP-1649 / Accept: text/markdown)
        if ($this->wantsMarkdown()) {
            $md = "# TalentHub Consultant — Premier Recruitment & Executive Search\n\n"
                . "> {$metaDesc}\n\n"
                . "## Overview\n"
                . "TalentHub Consultant connects exceptional technical and leadership talent with leading enterprises across Pune and Western India.\n\n"
                . "## Contact Information\n"
                . "- **Office**: Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046, India\n"
                . "- **Phone**: +91 72761 46573\n"
                . "- **Email**: hr@talenthubconsultant.com\n"
                . "- **Website**: " . base_url() . "\n"
                . "- **Hours**: Monday – Saturday, 9:30 AM – 7:00 PM IST\n\n"
                . "## Active Opportunities\n";

            foreach ($featuredJobs as $j) {
                $md .= "- **[" . esc($j['title']) . "](" . base_url('jobs/' . $j['slug']) . ")** — " . esc($j['location'] ?? 'Pune') . "\n";
            }

            $md .= "\n## Key Services\n"
                . "1. **Permanent Technical Staffing**: Zero upfront cost, 90-day replacement guarantee.\n"
                . "2. **Executive Search**: CXO and VP recruitment across IT, BFSI, and Manufacturing.\n"
                . "3. **Industrial & Plant Engineering**: Specialized placement across Chakan and Bhosari corridors.\n\n"
                . "## Links\n"
                . "- [Find Jobs](" . base_url('jobs') . ")\n"
                . "- [For Employers](" . base_url('employers') . ")\n"
                . "- [Career Enquiry](" . base_url('career-enquiry') . ")\n"
                . "- [LLM Machine Context](" . base_url('llms.txt') . ")\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/home', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
            'featuredJobs'     => $featuredJobs,
            'categories'       => $categories,
            'testimonials'     => $testimonials,
            'faqs'             => $faqs,
            'recentBlogs'      => $recentBlogs,
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('contact_enquiry'),
        ]);
    }
}

