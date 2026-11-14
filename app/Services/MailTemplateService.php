<?php

namespace App\Services;

use App\Models\EmailTemplateModel;

class MailTemplateService
{
    protected EmailTemplateModel $templateModel;

    public function __construct()
    {
        $this->templateModel = new EmailTemplateModel();
    }

    /**
     * Render template subject and body by substituting variables
     */
    public function render(string $templateCode, array $variables = []): ?array
    {
        $template = $this->templateModel->getByCode($templateCode);
        if (!$template) {
            return null;
        }

        // Standard default variables
        $defaults = [
            'company_name'  => 'TalentHub Consultant',
            'website_url'   => base_url(),
            'contact_email' => 'hr@talenthubconsultant.com',
            'office_phone'  => '+91 72761 46573',
            'office_address'=> 'Katraj-Kondhwa Road, Pune, Maharashtra 411048',
            'current_year'  => date('Y'),
        ];
        $vars = array_merge($defaults, $variables);

        $subject = $template['subject'];
        $html    = $template['body_html'];
        $plain   = $template['body_text'];

        foreach ($vars as $key => $val) {
            if (is_scalar($val)) {
                $placeholder = '{{' . $key . '}}';
                $subject = str_replace($placeholder, (string) $val, $subject);
                $html    = str_replace($placeholder, htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'), $html);
                $plain   = str_replace($placeholder, (string) $val, $plain);
            }
        }

        return [
            'code'       => $template['code'],
            'name'       => $template['name'],
            'subject'    => $subject,
            'body_html'  => $html,
            'body_plain' => $plain,
        ];
    }
}

