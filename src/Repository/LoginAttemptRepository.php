<?php

namespace WebEtDesign\UserBundle\Repository;

use DateTime;
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
    private const DELETE_BATCH_SIZE = 50000;

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

    /**
     * Par lots : la commande de purge n'ayant jamais pu s'exécuter — elle lisait un paramètre
     * inexistant — son premier passage porte sur l'arriéré complet, plusieurs centaines de milliers
     * de lignes en production. Un DELETE unique les tiendrait dans une seule transaction.
     *
     * Le try/catch précédent relançait `new $e`, ce qui reconstruisait l'exception sans son
     * message : il masquait la cause au lieu de la propager.
     */
    public function deleteOldLoginAttempts(int $delay): int
    {
        $timeAgo = new \DateTimeImmutable(sprintf('-%d minutes', $delay));
        $deleted = 0;

        do {
            $ids = array_column(
                $this->createQueryBuilder('la')
                    ->select('la.id')
                    ->where('la.date < :date')->setParameter('date', $timeAgo)
                    ->setMaxResults(self::DELETE_BATCH_SIZE)
                    ->getQuery()
                    ->getScalarResult(),
                'id'
            );

            if ([] === $ids) {
                break;
            }

            $deleted += (int) $this->createQueryBuilder('la')
                ->delete()
                ->where('la.id IN (:ids)')->setParameter('ids', $ids)
                ->getQuery()
                ->execute();
        } while (count($ids) === self::DELETE_BATCH_SIZE);

        return $deleted;
    }

    public function countAttemptSince(string $ip, string $username, DateTime $since): int
    {
        return $this
            ->createQueryBuilder('la')
            ->select('count(la.id)')
            ->where('la.ipAddress = :ipAddress')->setParameter('ipAddress', $ip)
            ->andWhere('la.username = :username')->setParameter('username', $username)
            ->andWhere('la.date >= :since')->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
