<?php

namespace WebEtDesign\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\UserBundle\Entity\WDGroup;

/**
 * @method WDGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method WDGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method WDGroup[]    findAll()
 * @method WDGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WDGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        $class = $parameterBag->get('wd_user.group.class');
        parent::__construct($registry, $class);
    }
}
