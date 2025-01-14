<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieActors;
use App\Form\MovieFormType;
use App\Repository\MovieActorsRepository;
use App\Repository\MovieGenreRepository;
use App\Repository\MovieRepository;
use App\Repository\PersonRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MovieController extends AbstractController
{
    private readonly S3Uploader $s3Uploader;
    private readonly MovieRepository $movieRepository;
    private readonly MovieActorsRepository $movieActorsRepository;

    public function __construct(
        MovieRepository $movieRepository,
        MovieActorsRepository $movieActorsRepository,
        S3Uploader $s3Uploader
    ) {
        $this->movieRepository = $movieRepository;
        $this->movieActorsRepository = $movieActorsRepository;

        $this->s3Uploader = $s3Uploader;
    }

    #[Route('/movie/{slug}-{id}', name: 'show', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function show(string $slug, int $id): Response
    {
        $movie = $this->movieRepository->find($id);
        $director = $movie->getDirector();
        $movieActors = $this->movieActorsRepository->findBy(['movie' => $id]);
        $actors = array_map(function ($movieActor) {
            $person = $movieActor->getPerson();

            return $person->getFirstName() . ' ' . $person->getLastName();
        }, $movieActors);

        return $this->render('movie/show.html.twig', [
            'id' => $id,
            'title' => $movie->getTitle(),
            'coverImage' => $movie->getCoverImage(),
            'releaseDate' => $movie->getReleaseDate()->format('d F Y'),
            'duration' => $movie->getDuration(),
            'synopsis' => $movie->getSynopsis(),
            'genre' => $movie->getGenre()->getLabel(),
            'director' => $director->getFirstName() . ' ' . $director->getLastName(),
            'actors' => $actors
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
        }

        return $this->render('movie/add.html.twig', [
            'movieForm' => $form
        ]);
    }
}
