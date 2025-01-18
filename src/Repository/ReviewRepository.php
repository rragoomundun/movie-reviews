<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\Movie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Review::class);
    }

    public function hasMovieHasReviewFromUser(Movie $movie, User $user): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.movie = :movie')
            ->andWhere('r.proprietary = :user')
            ->setParameter('movie', $movie)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastFiveReviews(Movie $movie): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.movie = :movie')
            ->setParameter('movie', $movie)
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function findPaginatedReviews(int $page)
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r'),
            $page,
            10
        );
    }
}
