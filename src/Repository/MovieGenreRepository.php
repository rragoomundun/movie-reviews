<?php

namespace App\Repository;

use App\Entity\MovieGenre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieGenreRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, MovieGenre::class);
  }

  //    /**
  //     * @return Movie[] Returns an array of Movie objects
  //     */
  //    public function findByExampleField($value): array
  //    {
  //        return $this->createQueryBuilder('m')
  //            ->andWhere('m.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->orderBy('m.id', 'ASC')
  //            ->setMaxResults(10)
  //            ->getQuery()
  //            ->getResult()
  //        ;
  //    }

  //    public function findOneBySomeField($value): ?Movie
  //    {
  //        return $this->createQueryBuilder('m')
  //            ->andWhere('m.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->getQuery()
  //            ->getOneOrNullResult()
  //        ;
  //    }
}
