<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiException;
use App\Service\ShopClaimService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController
{
    public function __construct(
        private readonly Security $security,
        private readonly ShopClaimService $shopClaims,
    ) {
    }

    #[Route('/api/v1/shop/claims', name: 'api_v1_shop_claims_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $this->requireShop();
        $q = (string) $request->query->get('q', '');
        $items = $this->shopClaims->search($q);

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/api/v1/shop/claims/{id}/deliver', name: 'api_v1_shop_claims_deliver', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deliver(int $id): JsonResponse
    {
        $shop = $this->requireShop();
        $claim = $this->shopClaims->deliver($shop, $id);

        return new JsonResponse($claim);
    }

    private function requireShop(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw ApiException::unauthorized();
        }
        if (!\in_array('ROLE_SHOP', $user->getRoles(), true)) {
            throw ApiException::forbidden('Compte boutique requis.');
        }

        return $user;
    }
}
