<?php

namespace App\Exceptions;

use Exception;

class MicroserviceException extends Exception
{
    protected $errorCode;
    protected $recoverable;
    protected $retryAfter;

    public function __construct(
        string $message = "",
        string $errorCode = "MICROSERVICE_ERROR",
        bool $recoverable = true,
        int $retryAfter = null,
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode;
        $this->recoverable = $recoverable;
        $this->retryAfter = $retryAfter;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function isRecoverable(): bool
    {
        return $this->recoverable;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function toArray(): array
    {
        $error = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'recoverable' => $this->recoverable
        ];

        if ($this->retryAfter !== null) {
            $error['retry_after'] = $this->retryAfter;
        }

        return $error;
    }
}














