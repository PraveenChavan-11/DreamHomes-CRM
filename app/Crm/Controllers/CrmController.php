<?php

namespace Crm\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Crm\Services\AccessScope;
use Crm\Services\CrmException;
use Crm\Services\DuplicateException;

/** Thin base: authentication state, rendering inside the CRM layout, and a uniform "run a service action" wrapper. */
abstract class CrmController extends Controller
{
    protected $helpers = ['url', 'form', 'text', 'crm'];

    protected function user(): array
    {
        return session()->get('crm_user') ?? [];
    }

    protected function uid(): int
    {
        return (int) ($this->user()['id'] ?? 0);
    }

    /** @param array<string,mixed> $data */
    protected function render(string $view, array $data = [], string $title = '', string $active = ''): string
    {
        $data += ['title' => $title, 'active' => $active, 'user' => $this->user()];
        $content = view('Crm\Views\\' . $view, $data);

        return view('Crm\Views\layout\main', $data + ['content' => $content]);
    }

    /** Permission gate used inside actions in addition to the route filter (defence in depth). */
    protected function authorize(string ...$perms): void
    {
        foreach ($perms as $p) {
            if (AccessScope::can($this->user(), $p)) {
                return;
            }
        }
        throw new \CodeIgniter\Exceptions\PageNotFoundException('Not found');
    }

    protected function flashBack(string $type, string $msg): RedirectResponse
    {
        return redirect()->back()->with($type, $msg);
    }

    /**
     * Run a service call; on a business-rule error go back with the message and the user's input preserved.
     * @param callable():mixed $fn  may return a string URL path (relative to site) to redirect to
     */
    protected function act(callable $fn, string $success, ?string $to = null): RedirectResponse
    {
        try {
            $r   = $fn();
            $url = is_string($r) && $r !== '' ? $r : $to;
            $res = $url ? redirect()->to(site_url($url)) : redirect()->back();

            return $res->with('success', $success);
        } catch (DuplicateException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage())->with('duplicates', $e->matches);
        } catch (CrmException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage())->with('errors', $e->errors);
        } catch (\Crm\Services\AuthException | \InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    protected function json(array $data, int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON($data);
    }

    protected function download(string $bytes, string $name, string $mime): ResponseInterface
    {
        return $this->response->setHeader('Content-Type', $mime)->setHeader('Content-Disposition', 'attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')->setBody($bytes);
    }

    /** @param array<string,mixed> $src @param list<string> $keys */
    protected function pick(array $src, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $src)) {
                $out[$k] = is_string($src[$k]) ? trim($src[$k]) : $src[$k];
            }
        }

        return $out;
    }

    protected function page(): int
    {
        return max(1, (int) $this->request->getGet('page'));
    }
}
