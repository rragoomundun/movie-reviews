<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private SluggerInterface $slugger)
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

    public function find10NewMovies(): array
    {
        $movies = $this->createQueryBuilder('m')
            ->orderBy('m.releaseDate', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        foreach ($movies as $movie) {
            $movie->slug = strtolower($this->slugger->slug($movie->getTitle()));
        }

        return $movies;
    }
}
