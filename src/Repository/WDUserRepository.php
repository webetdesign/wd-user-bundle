<?php

namespace WebEtDesign\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WDUser::class);
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        // set the new encoded password on the User object
        $user->setPassword($newEncodedPassword);

        // execute the queries on the database
        $this->getEntityManager()->flush();
    }

    public function loadUserByUsername($usernameOrEmail): ?WDUser
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\User\User u
                WHERE u.username = :query
                OR u.email = :query'
        )
            ->setParameter('query', $usernameOrEmail)
            ->getOneOrNullResult();
    }

}
