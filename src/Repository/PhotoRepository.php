<?php

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function exists(string $file, Movie $movie): bool
    {
        return (bool) $this->createQueryBuilder('p')
            ->andWhere('p.url = :file')
            ->andWhere('p.movie = :movie')
            ->setParameter('file', $file)
            ->setParameter('movie', $movie)
            ->getQuery()
            ->getResult();
    }

    public function findPhotosForMovie(Movie $movie): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.movie = :movie')
            ->setParameter('movie', $movie)
            ->getQuery()
            ->getResult();
    }
}
