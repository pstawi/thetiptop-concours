<?php

namespace App\Http;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class ApiException extends HttpException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($statusCode, $message, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public static function validation(string $message): self
    {
        return new self('VALIDATION_ERROR', $message, 400);
    }

    public static function unauthorized(string $message = 'Authentification requise.'): self
    {
        return new self('UNAUTHORIZED', $message, 401);
    }

    public static function forbidden(string $message = 'Accès refusé.'): self
    {
        return new self('FORBIDDEN', $message, 403);
    }

    public static function notFound(string $message = 'Ressource introuvable.'): self
    {
        return new self('NOT_FOUND', $message, 404);
    }

    public static function oauthMockDisabled(string $message = 'Le mock OAuth est désactivé.'): self
    {
        return new self('OAUTH_MOCK_DISABLED', $message, 403);
    }
}
