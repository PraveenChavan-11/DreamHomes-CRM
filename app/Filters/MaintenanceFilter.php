<?php

namespace App\Filters;

use App\Models\WebsiteSettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        // DHIS CRM subdomain or CRM routes have their own session guard and isolated database
        if (str_contains($host, 'dhiscrm') ||
            str_starts_with($uri, 'admin') || str_starts_with($uri, '/admin') ||
            str_starts_with($uri, 'api/v1/admin') || str_starts_with($uri, '/api/v1/admin') ||
            str_starts_with($uri, 'assets') || str_starts_with($uri, '/assets') ||
            str_starts_with($uri, 'crm') || str_starts_with($uri, '/crm')) {
            return $request;
        }

        try {
            $settingModel = new WebsiteSettingModel();
            $isMaintenance = (int) ($settingModel->getSetting('maintenance_mode', '0'));
        } catch (\Throwable $e) {
            return $request;
        }

        if ($isMaintenance === 1) {
            $headline = $settingModel->getSetting('maintenance_headline', 'System Maintenance Underway');
            $message = $settingModel->getSetting('maintenance_message', 'TalentHub Consultant is currently undergoing scheduled platform upgrades to serve you better. Our recruitment advisors remain reachable via phone.');
            $returnTime = $settingModel->getSetting('maintenance_return_time', 'Shortly (Within 1 Hour)');
            $supportEmail = $settingModel->getSetting('contact_email', 'hr@talenthubconsultant.com');
            $supportPhone = $settingModel->getSetting('contact_phone', '+91 72761 46573');

            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '/api/')) {
                return service('response')
                    ->setStatusCode(503)
                    ->setHeader('Retry-After', '3600')
                    ->setJSON([
                        'success' => false,
                        'status'  => 503,
                        'code'    => 'SERVICE_MAINTENANCE',
                        'message' => $message,
                        'data'    => [
                            'headline'     => $headline,
                            'return_time'  => $returnTime,
                            'support_email'=> $supportEmail,
                            'support_phone'=> $supportPhone,
                        ],
                    ]);
            }

            return service('response')
                ->setStatusCode(503)
                ->setHeader('Retry-After', '3600')
                ->setBody(view('errors/html/error_503', [
                    'headline'     => $headline,
                    'message'      => $message,
                    'returnTime'   => $returnTime,
                    'supportEmail' => $supportEmail,
                    'supportPhone' => $supportPhone,
                ]));
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

