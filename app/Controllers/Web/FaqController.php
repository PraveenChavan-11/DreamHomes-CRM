<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\FaqModel;
use App\Models\WebsitePageModel;

class FaqController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('faq');

        $faqModel = new FaqModel();
        $faqs = $faqModel->getPublishedFaqs();

        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune';
        $metaDesc = !empty($page['meta_description']) ? $page['meta_description'] : 'Answers to common recruitment, interview coordination, candidate placement, and corporate hiring questions in Pune, Maharashtra.';

        if ($this->wantsMarkdown()) {
            $md = "# Frequently Asked Questions | TalentHub Consultant Pune\n\n"
                . "> Answers to common recruitment, interview coordination, candidate placement, and corporate hiring questions in Pune.\n\n";

            foreach ($faqs as $faq) {
                $md .= "### " . esc($faq['question']) . "\n"
                    . esc($faq['answer']) . "\n\n";
            }

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/faq', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'faqs'             => $faqs,
        ]);
    }
}

