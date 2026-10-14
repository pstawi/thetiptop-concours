<?php

namespace App\Service;

use App\Entity\User;
use App\Http\ApiException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class RedeemRateLimiter
{
    private readonly RateLimiterFactory $userLimiter;
    private readonly RateLimiterFactory $ipLimiter;

    public function __construct()
    {
        $storage = new InMemoryStorage();
        $this->userLimiter = new RateLimiterFactory([
            'id' => 'redeem_user',
            'policy' => 'fixed_window',
            'limit' => 10,
            'interval' => '1 minute',
        ], $storage);
        $this->ipLimiter = new RateLimiterFactory([
            'id' => 'redeem_ip',
            'policy' => 'fixed_window',
            'limit' => 30,
            'interval' => '1 minute',
        ], $storage);
    }

    public function assertAllowed(User $user, ?string $ip): void
    {
        $userLimit = $this->userLimiter->create('user-'.(string) $user->getId())->consume(1);
        if (!$userLimit->isAccepted()) {
            throw ApiException::rateLimited();
        }

        if (null !== $ip && '' !== $ip) {
            $ipLimit = $this->ipLimiter->create('ip-'.$ip)->consume(1);
            if (!$ipLimit->isAccepted()) {
                throw ApiException::rateLimited();
            }
        }
    }
}
