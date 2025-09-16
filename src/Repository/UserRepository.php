<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Search users with filters
     */
    public function findWithFilters(?string $search = null, ?\DateTimeInterface $createdAfter = null, ?\DateTimeInterface $updatedAfter = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.profile', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('
                u.email LIKE :search OR 
                p.username LIKE :search OR 
                p.displayName LIKE :search OR 
                p.job LIKE :search
            ')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($createdAfter) {
            $qb->andWhere('p.createdAt >= :createdAfter')
                ->setParameter('createdAfter', $createdAfter);
        }

        if ($updatedAfter) {
            $qb->andWhere('p.updatedAt >= :updatedAfter')
                ->setParameter('updatedAfter', $updatedAfter);
        }

        return $qb->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
