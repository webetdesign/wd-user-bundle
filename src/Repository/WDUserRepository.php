<?php

namespace WebEtDesign\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use WebEtDesign\UserBundle\Entity\WDUser;


/**
 * @method WDUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method WDUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method WDUser[]    findAll()
 * @method WDUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WDUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    protected string $class;

    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag)
    {
        $this->class = $parameterBag->get('wd_user.user.class');
        parent::__construct($registry, $this->class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newEncodedPassword): void
    {
        // set the new encoded password on the User object
        $user->setPassword($newEncodedPassword);

        // execute the queries on the database
        $this->getEntityManager()->flush();
    }

    public function loadUserByUsername($username): ?WDUser
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM '.$this->class.' u
                WHERE u.username = :query
                OR u.email = :query'
        )
            ->setParameter('query', $username)
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $qb = $this->createQueryBuilder('u');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('u.email', ':identifier'),
                $qb->expr()->eq('u.username', ':identifier'),
                $qb->expr()->eq('u.azureId', ':identifier'),
            )
        );

        $qb->setParameter('identifier', $identifier);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
