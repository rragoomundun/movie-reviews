<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieActors;
use App\Entity\Review;
use App\Form\MovieFormType;
use App\Form\ReviewFormType;
use App\Repository\MovieActorsRepository;
use App\Repository\MovieRepository;
use App\Repository\ReviewRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

class MovieController extends AbstractController
{
    private readonly S3Uploader $s3Uploader;
    private readonly MovieRepository $movieRepository;
    private readonly MovieActorsRepository $movieActorsRepository;
    private readonly ReviewRepository $reviewRepository;

    public function __construct(
        MovieRepository $movieRepository,
        MovieActorsRepository $movieActorsRepository,
        ReviewRepository $reviewRepository,
        S3Uploader $s3Uploader
    ) {
        $this->movieRepository = $movieRepository;
        $this->movieActorsRepository = $movieActorsRepository;
        $this->reviewRepository = $reviewRepository;

        $this->s3Uploader = $s3Uploader;
    }

    #[Route('/movie/{slug}-{id}', name: 'movie.page', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function showPage(string $slug, int $id): Response
    {
        $movie = $this->movieRepository->find($id);
        $director = $movie->getDirector();
        $movieActors = $this->movieActorsRepository->findBy(['movie' => $id]);
        $actors = array_map(function ($movieActor) {
            $person = $movieActor->getPerson();

            return $person->getFirstName() . ' ' . $person->getLastName();
        }, $movieActors);
        $reviews = $this->reviewRepository->findLastFiveReviews($movie);

        return $this->render('movie/show.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'page',
            'title' => $movie->getTitle(),
            'coverImage' => $movie->getCoverImage(),
            'releaseDate' => $movie->getReleaseDate()->format('d F Y'),
            'duration' => $movie->getDuration(),
            'synopsis' => $movie->getSynopsis(),
            'genre' => $movie->getGenre()->getLabel(),
            'director' => $director->getFirstName() . ' ' . $director->getLastName(),
            'actors' => $actors,
            'reviews' => $reviews
        ]);
    }

    #[Route('/movie/{slug}-{id}/reviews', name: 'movie.reviews', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function showReviews(Request $request, string $slug, int $id): Response
    {
        $movie = $this->movieRepository->find($id);
        $page = $request->query->getInt('page', 1);
        $reviews = $this->reviewRepository->findPaginatedReviews($page, $movie);

        return $this->render('movie/reviews.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'reviews',
            'title' => $movie->getTitle(),
            'hasReview' => ($this->getUser() ? $this->reviewRepository->hasMovieHasReviewFromUser($movie, $this->getUser()) : false),
            'reviews' => $reviews
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/movie/{slug}-{id}/reviews/new', name: 'movie.reviews.new', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function writeReview(Request $request, EntityManagerInterface $entityManager, string $slug, int $id): Response
    {
        $movie = $this->movieRepository->find($id);
        $review = new Review();
        $form = $this->createForm(ReviewFormType::class, $review);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setMovie($movie);
            $review->setProprietary($this->getUser());

            $entityManager->persist($review);
            $entityManager->flush();

            return $this->redirectToRoute('movie.reviews', [
                'slug' => $slug,
                'id' => $id
            ]);
        }

        return $this->render('movie/new-review.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'reviews',
            'title' => $movie->getTitle(),
            'reviewForm' => $form,
            'hasReview' => $this->reviewRepository->hasMovieHasReviewFromUser($movie, $this->getUser())
        ]);
    }

    #[Route('/movie/add', name: 'movie.add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $movie = new Movie();
        $form = $this->createForm(MovieFormType::class, $movie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formElements = $form->all();
            $file = $request->files->get('movie_form')['coverImage'];
            $url = $this->s3Uploader->upload($file);

            $movie->setProprietary($this->getUser());
            $movie->setCoverImage($url);

            $entityManager->persist($movie);

            $movieActor1 = new MovieActors();
            $movieActor2 = new MovieActors();
            $movieActor3 = new MovieActors();

            $movieActor1->setMovie($movie);
            $movieActor1->setPerson($formElements['actor1']->getData());

            $movieActor2->setMovie($movie);
            $movieActor2->setPerson($formElements['actor2']->getData());

            $movieActor3->setMovie($movie);
            $movieActor3->setPerson($formElements['actor3']->getData());

            $entityManager->persist($movieActor1);
            $entityManager->persist($movieActor2);
            $entityManager->persist($movieActor3);

            $entityManager->flush();

            $slugger = new AsciiSlugger();
            $slug = strtolower($slugger->slug($movie->getTitle())->toString());

            return $this->redirectToRoute('movie.page', [
                'slug' => $slug,
                'id' => $movie->getId()
            ]);
        }

        return $this->render('movie/add.html.twig', [
            'movieForm' => $form
        ]);
    }
}
