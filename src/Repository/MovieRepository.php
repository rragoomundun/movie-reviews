<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function isUserProprietaryOfMovie(int $movieId, User $user): bool
    {
        return (bool) $this->createQueryBuilder('m')
            ->where('m.id = :id')
            ->andWhere('m.proprietary = :proprietary')
            ->setParameter('id', $movieId)
            ->setParameter('proprietary', $user)
            ->getQuery()
            ->getResult();
    }
}
