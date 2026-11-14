<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Filters\AgentAuthFilter;
use App\Services\Agent\AgentApiException;
use App\Services\Agent\AgentAuditLogger;
use App\Services\Agent\AgentSocialService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * /api/v1/integrations/agents/*  — authenticated and scope-checked by AgentAuthFilter before any method runs.
 * Responses: {success:true,data:{…},request_id} or {success:false,error:{code,message,details?},request_id}.
 */
class AgentApiController extends BaseController
{
    private function ctx(): array
    {
        $c = AgentAuthFilter::$context;
        if ($c === null) {                     // route misconfigured without the filter → fail closed
            throw new AgentApiException('UNAUTHENTICATED', 401, 'Authentication required.');
        }
        return $c;
    }

    private function run(callable $fn): ResponseInterface
    {
        $rid = AgentAuthFilter::$context['request_id'] ?? bin2hex(random_bytes(8));
        try {
            $c = $this->ctx();
            [$status, $data] = $fn($c, new AgentSocialService());
            return $this->response->setStatusCode($status)->setJSON(['success' => true, 'data' => $data, 'request_id' => $rid]);
        } catch (AgentApiException $e) {
            $this->logOutcome($e->errorCode, $e->httpStatus, $e->details);
            $err = ['code' => $e->errorCode, 'message' => $e->getMessage()] + ($e->details ? ['details' => $e->details] : []);
            return $this->response->setStatusCode($e->httpStatus)->setJSON(['success' => false, 'error' => $err, 'request_id' => $rid]);
        } catch (\Throwable $e) {
            log_message('error', 'Agent API error: ' . get_class($e));
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'The request could not be processed.'], 'request_id' => $rid]);
        }
    }

    private function logOutcome(string $code, int $status, array $details = []): void
    {
        $c = AgentAuthFilter::$context;
        if ($c) {
            (new AgentAuditLogger())->log($code, (int) $c['agent']['id'], $c['base'] + ['status' => $status, 'detail' => $details ? implode(',', array_keys($details)) : null]);
        }
    }

    private function logEvent(string $event, string $detail): void
    {
        $c = AgentAuthFilter::$context;
        if ($c) {
            (new AgentAuditLogger())->log($event, (int) $c['agent']['id'], $c['base'] + ['status' => 200, 'detail' => $detail]);
        }
    }

    public function capabilities(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->capabilities($c['agent'], $c['token'])]);
    }

    public function accounts(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->accounts()]);
    }

    public function guidelines(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->guidelines()]);
    }

    public function templates(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->templates()]);
    }

    public function calendar(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->calendar($this->request->getGet('from'), $this->request->getGet('to'))]);
    }

    public function analytics(): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->analytics($c['agent'])]);
    }

    public function showPost(int $id): ResponseInterface
    {
        return $this->run(fn ($c, $s) => [200, $s->getPost($c['agent'], $id)]);
    }

    public function createDraft(): ResponseInterface
    {
        return $this->run(function ($c, $s) {
            $body = json_decode((string) $this->request->getBody(), true);
            if (!is_array($body) || array_is_list($body)) {
                throw new AgentApiException('INVALID_JSON', 400, 'The request body must be a JSON object.');
            }
            [$status, $data] = $s->createDraft($c['agent'], $body, $this->request->getHeaderLine('Idempotency-Key') ?: null);
            if (!empty($data['created'])) {
                $this->logEvent('DRAFT_CREATED', 'post #' . $data['post']['id']);
            }
            return [$status, $data];
        });
    }

    public function submitApproval(int $id): ResponseInterface
    {
        return $this->run(function ($c, $s) use ($id) {
            $data = $s->submitForApproval($c['agent'], $id);
            $this->logEvent('DRAFT_SUBMITTED', 'post #' . $id);
            return [200, $data];
        });
    }
}
