<?php

namespace App\Exceptions;

use RuntimeException;

class MailPanelException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
    ) {
        parent::__construct($message);
    }
}
