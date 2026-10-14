<?php

namespace App\EventSubscriber;

use App\Http\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = 500;
        $code = 'INTERNAL_ERROR';
        $message = 'Une erreur interne est survenue.';

        if ($throwable instanceof ApiException) {
            $status = $throwable->getStatusCode();
            $code = $throwable->getErrorCode();
            $message = $throwable->getMessage();
        } elseif ($throwable instanceof UnauthorizedHttpException || $throwable instanceof AuthenticationException) {
            $status = 401;
            $code = 'UNAUTHORIZED';
            $message = 'Authentification requise.';
        } elseif ($throwable instanceof AccessDeniedHttpException || $throwable instanceof AccessDeniedException) {
            // Accès anonyme sur route protégée → 401 (CDC), pas 403.
            $hasBearer = str_starts_with((string) $request->headers->get('Authorization', ''), 'Bearer ');
            if (!$hasBearer) {
                $status = 401;
                $code = 'UNAUTHORIZED';
                $message = 'Authentification requise.';
            } else {
                $status = 403;
                $code = 'FORBIDDEN';
                $message = 'Accès refusé.';
            }
        } elseif ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $code = match ($status) {
                400 => 'VALIDATION_ERROR',
                401 => 'UNAUTHORIZED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                409 => 'DRAW_ALREADY_DONE',
                422 => 'VALIDATION_ERROR',
                429 => 'RATE_LIMITED',
                default => 'HTTP_ERROR',
            };
            $message = $throwable->getMessage() ?: $message;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status));
    }
}
