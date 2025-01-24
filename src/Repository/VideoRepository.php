<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    public function exists(string $file, Movie $movie): bool
    {
        return (bool) $this->createQueryBuilder('v')
            ->andWhere('v.url = :file')
            ->andWhere('v.movie = :movie')
            ->setParameter('file', $file)
            ->setParameter('movie', $movie)
            ->getQuery()
            ->getResult();
    }

    public function findVideosForMovie(Movie $movie): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.movie = :movie')
            ->setParameter('movie', $movie)
            ->getQuery()
            ->getResult();
    }
}
