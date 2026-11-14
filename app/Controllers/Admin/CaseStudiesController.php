<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Services\CaseStudyService;

/**
 * Case study (portfolio) administration.
 * View = case_studies.view; every change = case_studies.manage. POSTs are CSRF protected by the global filter and
 * every mutation is written to the audit log. Nothing is seeded: an administrator publishes real work only.
 */
class CaseStudiesController extends AdminBaseController
{
    private CaseStudyService $svc;

    public function __construct()
    {
        $this->svc = new CaseStudyService();
    }

    private function adminId(): int
    {
        return (int) ($this->adminUser['id'] ?? 0);
    }

    private function canManage(): bool
    {
        return $this->isSuperAdmin() || in_array('case_studies.manage', $this->adminUser['permissions'] ?? [], true);
    }

    private function audit(string $action, int $id, ?array $old = null, ?array $new = null): void
    {
        try {
            (new AuditLogModel())->log($action, 'case_study', $id, $old, $new, $this->adminId());
        } catch (\Throwable $e) {
            log_message('error', 'Case study audit failed: ' . $e->getMessage());
        }
    }

    public function index()
    {
        if ($r = $this->requirePermission('case_studies.view')) {
            return $r;
        }
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('search'));
        $page   = max(1, (int) $this->request->getGet('page'));
        $per    = 15;
        $res    = $this->svc->adminList($status, $search, $page, $per);

        return $this->renderAdminView('admin/case_studies/index', [
            'title'        => 'Case studies — TalentHub Admin',
            'rows'         => $res['rows'],
            'total'        => $res['total'],
            'page'         => $page,
            'totalPages'   => max(1, (int) ceil($res['total'] / $per)),
            'status'       => isset(CaseStudyService::STATUSES[$status]) ? $status : '',
            'search'       => $search,
            'statusCounts' => $this->svc->counts(),
            'canManage'    => $this->canManage(),
        ], 'case_studies');
    }

    public function new()
    {
        if ($r = $this->requirePermission('case_studies.manage')) {
            return $r;
        }
        return $this->form(null);
    }

    public function edit(int $id)
    {
        if ($r = $this->requirePermission('case_studies.view')) {
            return $r;
        }
        $row = $this->svc->find($id);
        if (!$row) {
            session()->setFlashdata('error', 'Case study not found.');
            return redirect()->to(base_url('admin/case-studies'));
        }
        return $this->form($row);
    }

    private function form(?array $row)
    {
        return $this->renderAdminView('admin/case_studies/form', [
            'title'      => ($row ? 'Edit ' . $row['title'] : 'New case study') . ' — TalentHub Admin',
            'cs'         => $row,
            'images'     => $row ? $this->svc->images((int) $row['id']) : [],
            'canManage'  => $this->canManage(),
            'formErrors' => session()->getFlashdata('cs_errors') ?: [],
            'statuses'   => CaseStudyService::STATUSES,
        ], 'case_studies');
    }

    public function create()
    {
        if ($r = $this->requirePermission('case_studies.manage')) {
            return $r;
        }
        return $this->persist(null);
    }

    public function update(int $id)
    {
        if ($r = $this->requirePermission('case_studies.manage')) {
            return $r;
        }
        if (!$this->svc->find($id)) {
            session()->setFlashdata('error', 'Case study not found.');
            return redirect()->to(base_url('admin/case-studies'));
        }
        return $this->persist($id);
    }

    private function persist(?int $id)
    {
        $post = (array) $this->request->getPost();
        $res  = $this->svc->sanitize($post, $id);
        $data = $res['data'];
        $errors = $res['errors'];
        if ($data['status'] === 'published') {
            if (empty($data['summary'])) {
                $errors['summary'] = 'A summary is required before a case study can be published.';
            }
        }
        if ($errors) {
            session()->setFlashdata('cs_errors', $errors);
            return redirect()->back()->withInput()->with('error', 'Please fix the highlighted fields: ' . implode(' ', $errors));
        }

        $before = $id ? $this->svc->find($id) : null;
        $warnings = [];
        try {
            $isNew = $id === null;
            $id = $this->svc->save($id, $data, $this->adminId());

            $cover = $this->request->getFile('cover_file');
            if ($cover && $cover->getError() !== UPLOAD_ERR_NO_FILE) {
                $up = $this->svc->storeUpload($cover, $id);
                if (isset($up['path'])) {
                    $this->svc->setCover($id, $up['path']);
                } else {
                    $warnings[] = 'Cover image: ' . $up['error'];
                }
            } elseif (!empty($post['remove_cover'])) {
                $this->svc->setCover($id, null);
            }

            foreach ((array) ($post['gallery_remove'] ?? []) as $imgId) {
                $this->svc->removeImage($id, (int) $imgId);
            }
            $this->svc->updateImageMeta($id, (array) ($post['gallery'] ?? []));

            $newFiles = $this->request->getFileMultiple('gallery_new') ?: [];
            $caps = (array) ($post['gallery_new_caption'] ?? []);
            $alts = (array) ($post['gallery_new_alt'] ?? []);
            foreach ($newFiles as $i => $file) {
                if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($this->svc->imageCount($id) >= CaseStudyService::MAX_GALLERY) {
                    $warnings[] = 'Gallery is limited to ' . CaseStudyService::MAX_GALLERY . ' images.';
                    break;
                }
                $up = $this->svc->storeUpload($file, $id);
                if (isset($up['path'])) {
                    $this->svc->addImage($id, $up['path'], trim((string) ($caps[$i] ?? '')) ?: null, trim((string) ($alts[$i] ?? '')) ?: null);
                } else {
                    $warnings[] = 'Gallery image ' . ($i + 1) . ': ' . $up['error'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Case study save failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'The case study could not be saved. Please try again.');
        }

        $this->audit($isNew ? 'CASE_STUDY_CREATE' : 'CASE_STUDY_UPDATE', $id,
            $before ? ['title' => $before['title'], 'status' => $before['status']] : null,
            ['title' => $data['title'], 'status' => $data['status'], 'slug' => $data['slug']]);

        $msg = $isNew ? 'Case study created.' : 'Case study saved.';
        if ($warnings) {
            session()->setFlashdata('error', $msg . ' Some images were not saved: ' . implode(' ', $warnings));
        } else {
            session()->setFlashdata('success', $msg);
        }
        return redirect()->to(base_url('admin/case-studies/' . $id . '/edit'));
    }

    public function status(int $id)
    {
        if ($r = $this->requirePermission('case_studies.manage')) {
            return $r;
        }
        $to  = (string) $this->request->getPost('status');
        $cur = $this->svc->find($id);
        if ($cur && $to === 'published' && trim((string) $cur['summary']) === '') {
            session()->setFlashdata('error', 'Add a summary before publishing.');
            return redirect()->back();
        }
        $ok = $cur && $this->svc->setStatus($id, $to);
        if ($ok) {
            $this->audit('CASE_STUDY_STATUS', $id, ['status' => $cur['status']], ['status' => $to]);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Status changed to ' . CaseStudyService::STATUSES[$to] . '.' : 'Could not change status.');
        return redirect()->back();
    }

    public function delete(int $id)
    {
        if ($r = $this->requirePermission('case_studies.manage')) {
            return $r;
        }
        $cur = $this->svc->find($id);
        $ok  = $cur && $this->svc->softDelete($id);
        if ($ok) {
            $this->audit('CASE_STUDY_DELETE', $id, ['title' => $cur['title'], 'status' => $cur['status']], null);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Case study deleted. It is hidden everywhere and retained in the database.' : 'Case study not found.');
        return redirect()->to(base_url('admin/case-studies'));
    }
}
