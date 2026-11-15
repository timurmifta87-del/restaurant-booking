<?php

namespace App\Exceptions;

use RuntimeException;

/** Нарушение бизнес-правила: отдаётся клиенту как 409 с полем message */
class BusinessException extends RuntimeException
{
    public function __construct(string $message, private int $status = 409)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
