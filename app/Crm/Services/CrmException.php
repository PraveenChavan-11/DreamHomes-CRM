<?php

namespace Crm\Services;

/** Business-rule violation (safe to show to the user). */
class CrmException extends \DomainException
{
    /** @var array<string,string> field => message */
    public array $errors = [];

    public static function validation(array $errors): self
    {
        $e = new self(implode(' ', $errors));
        $e->errors = $errors;

        return $e;
    }
}
