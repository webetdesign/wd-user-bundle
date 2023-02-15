<?php

namespace WebEtDesign\UserBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use WebEtDesign\UserBundle\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method LoginAttempt|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginAttempt|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginAttempt[]    findAll()
 * @method LoginAttempt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    public function countRecentLoginAttempts(string $username, string $firewall, int $delay): int
    {
        try {
            $timeAgo = new \DateTimeImmutable(sprintf('-%d minutes', $delay));

            return $this->createQueryBuilder('la')
                ->select('COUNT(la)')
                ->where('la.date >= :date')
                ->andWhere('la.username = :username')
                ->andWhere('la.firewall = :firewall')
                ->getQuery()
                ->setParameters([
                    'date' => $timeAgo,
                    'firewall' => $firewall,
                    'username' => $username,
                ])
                ->getSingleScalarResult()
                ;
        } catch (\Exception $e) {
            throw new $e;
        }
    }

    public function deleteOldLoginAttempts(int $delay)
    {
        try {
            $timeAgo = new \DateTimeImmutable(sprintf('-%d minutes', $delay));

            return $this->createQueryBuilder('la')
                ->delete()
                ->where('la.date < :date')
                ->setParameters([
                    'date' => $timeAgo,
                ])
                ->getQuery()
                ->getResult()
                ;
        } catch (\Exception $e) {
            throw new $e;
        }
    }
}
