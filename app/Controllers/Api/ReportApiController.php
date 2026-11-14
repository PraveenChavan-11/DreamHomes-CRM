<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\CandidateModel;
use App\Models\EmployerEnquiryModel;
use CodeIgniter\HTTP\ResponseInterface;

class ReportApiController extends BaseController
{
    public function exportApplications()
    {
        $appModel = new ApplicationModel();
        $apps = $appModel->getApplicationsWithDetails([], 1000, 0);

        $filename = 'applications_' . date('Ymd_His') . '.csv';
        $output = fopen('php://memory', 'w');

        // CSV Header
        fputcsv($output, ['Application ID', 'Candidate Name', 'Candidate Email', 'Candidate Phone', 'Experience (Years)', 'Job Title', 'Pipeline Stage', 'Resume File', 'Applied At']);

        foreach ($apps as $a) {
            fputcsv($output, [
                $a['id'],
                $a['candidate_name'],
                $a['candidate_email'],
                $a['candidate_phone'],
                $a['experience_years'],
                $a['job_title'],
                $a['status'],
                $a['resume_name'],
                $a['created_at'],
            ]);
        }

        fseek($output, 0);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }

    public function exportCandidates()
    {
        $candModel = new CandidateModel();
        $cands = $candModel->findAll(1000);

        $filename = 'candidates_' . date('Ymd_His') . '.csv';
        $output = fopen('php://memory', 'w');

        fputcsv($output, ['Candidate ID', 'Full Name', 'Email', 'Phone', 'Location', 'Experience', 'Current Role', 'Primary Skills', 'Registered At']);

        foreach ($cands as $c) {
            fputcsv($output, [
                $c['id'],
                $c['full_name'],
                $c['email'],
                $c['phone'],
                $c['current_location'],
                $c['total_experience_years'],
                $c['current_role'],
                $c['primary_skills'],
                $c['created_at'],
            ]);
        }

        fseek($output, 0);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }

    public function exportEmployers()
    {
        $empModel = new EmployerEnquiryModel();
        $enquiries = $empModel->findAll(1000);

        $filename = 'employer_enquiries_' . date('Ymd_His') . '.csv';
        $output = fopen('php://memory', 'w');

        fputcsv($output, ['Enquiry ID', 'Company Name', 'Contact Person', 'Email', 'Phone', 'Required Roles', 'Positions', 'Location', 'Urgency', 'Status', 'Received At']);

        foreach ($enquiries as $e) {
            fputcsv($output, [
                $e['id'],
                $e['company_name'],
                $e['contact_person'],
                $e['email'],
                $e['phone'],
                $e['required_roles'],
                $e['number_of_positions'],
                $e['location'],
                $e['urgency'],
                $e['status'],
                $e['created_at'],
            ]);
        }

        fseek($output, 0);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }
}

