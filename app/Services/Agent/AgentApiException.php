<?php

namespace App\Services\Agent;

/** A client-facing API error with a stable machine code. Messages are safe to show to the agent. */
class AgentApiException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, public readonly int $httpStatus, string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
