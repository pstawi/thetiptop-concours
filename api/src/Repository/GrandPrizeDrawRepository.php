<?php

namespace App\Repository;

use App\Entity\GrandPrizeDraw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GrandPrizeDraw>
 */
class GrandPrizeDrawRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrandPrizeDraw::class);
    }
}
