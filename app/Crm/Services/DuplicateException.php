<?php

namespace Crm\Services;

final class DuplicateException extends CrmException
{
    /** @var list<array<string,mixed>> */
    public array $matches = [];

    public static function with(string $message, array $matches): self
    {
        $e          = new self($message);
        $e->matches = $matches;

        return $e;
    }
}
