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

    public static function codeInvalid(string $message = 'Code ticket invalide.'): self
    {
        return new self('CODE_INVALID', $message, 422);
    }

    public static function codeAlreadyUsed(string $message = 'Ce code a déjà été utilisé.'): self
    {
        return new self('CODE_ALREADY_USED', $message, 422);
    }

    public static function contestNotOpen(string $message = 'Le concours n\'est pas ouvert à la saisie de codes.'): self
    {
        return new self('CONTEST_NOT_OPEN', $message, 422);
    }

    public static function rateLimited(string $message = 'Trop de tentatives. Réessayez plus tard.'): self
    {
        return new self('RATE_LIMITED', $message, 429);
    }

    public static function claimNotPending(string $message = 'Ce gain n\'est plus en attente de remise.'): self
    {
        return new self('CLAIM_NOT_PENDING', $message, 422);
    }

    public static function contestPhaseInvalid(string $message = 'Action impossible dans la phase actuelle du concours.'): self
    {
        return new self('CONTEST_PHASE_INVALID', $message, 422);
    }
}
