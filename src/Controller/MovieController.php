<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieActors;
use App\Entity\Photo;
use App\Entity\Review;
use App\Entity\Video;
use App\Form\MovieFormType;
use App\Form\PhotoFormType;
use App\Form\ReviewFormType;
use App\Form\VideoFormType;
use App\Repository\MovieActorsRepository;
use App\Repository\MovieRepository;
use App\Repository\PhotoRepository;
use App\Repository\ReviewRepository;
use App\Repository\VideoRepository;
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
    private readonly PhotoRepository $photoRepository;
    private readonly VideoRepository $videoRepository;

    public function __construct(
        MovieRepository $movieRepository,
        MovieActorsRepository $movieActorsRepository,
        ReviewRepository $reviewRepository,
        PhotoRepository $photoRepository,
        VideoRepository $videoRepository,
        S3Uploader $s3Uploader
    ) {
        $this->movieRepository = $movieRepository;
        $this->movieActorsRepository = $movieActorsRepository;
        $this->reviewRepository = $reviewRepository;
        $this->photoRepository = $photoRepository;
        $this->videoRepository = $videoRepository;

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
        $photos = $this->photoRepository->findFirstFourPhotosOfMovie($movie);
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
            'photos' => $photos,
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

    #[Route('/movie/{slug}-{id}/photos', name: 'movie.photos', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function showPhotos(Request $request, string $slug, int $id): Response
    {
        $file = $request->query->get('file');
        $movie = $this->movieRepository->find($id);
        $photos = $this->photoRepository->findPhotosForMovie($movie);

        if ($file !== null && $this->photoRepository->exists($file, $movie) === false) {
            $file = null;
        }

        if ($file === null && empty($photos) === false) {
            return $this->redirectToRoute('movie.photos', [
                'id' => $id,
                'slug' => $slug,
                'file' => $photos[0]->getUrl()
            ]);
        }

        $nbPhotos = count($photos);
        $currentPhotoIndex = 0;
        $previousFile = null;
        $nextFile = null;

        for ($currentPhotoIndex = 0; $currentPhotoIndex < $nbPhotos; $currentPhotoIndex++) {
            if ($photos[$currentPhotoIndex]->getUrl() === $file) {
                break;
            }
        }

        if ($currentPhotoIndex > 0) {
            $previousFile = $photos[$currentPhotoIndex - 1]->getUrl();
        }

        if ($currentPhotoIndex + 1 < $nbPhotos) {
            $nextFile = $photos[$currentPhotoIndex + 1]->getUrl();
        }

        $currentPhotoIndex += 1;

        return $this->render('movie/photos.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'photos',
            'title' => $movie->getTitle(),
            'isUserProprietary' => ($this->getUser() ? $this->movieRepository->isUserProprietaryOfMovie($id, $this->getUser()) : false),
            'file' => $file,
            'previousFile' => $previousFile,
            'nextFile' => $nextFile,
            'currentPhotoIndex' => $currentPhotoIndex,
            'nbPhotos' => $nbPhotos
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/movie/{slug}-{id}/photos/upload', name: 'movie.photos.upload', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function uploadPhotos(Request $request, EntityManagerInterface $entityManager, string $slug, int $id): Response
    {
        if ($this->movieRepository->isUserProprietaryOfMovie($id, $this->getUser()) === false) {
            return $this->redirectToRoute('movie.photos', [
                'id' => $id,
                'slug' => $slug
            ]);
        }

        $movie = $this->movieRepository->find($id);
        $form = $this->createForm(PhotoFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get('photo_form')['photos'];

            foreach ($files as $file) {
                $url = $this->s3Uploader->upload($file);
                $photo = new Photo();

                $photo->setUrl($url);
                $photo->setMovie($movie);

                $entityManager->persist($photo);
            }

            $entityManager->flush();

            return $this->redirectToRoute('movie.photos', [
                'id' => $id,
                'slug' => $slug,
                'file' => $url
            ]);
        }

        return $this->render('movie/photos-upload.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'photos',
            'title' => $movie->getTitle(),
            'photoForm' => $form
        ]);
    }

    #[Route('/movie/{slug}-{id}/videos', name: 'movie.videos', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function showVideos(Request $request, string $slug, int $id): Response
    {
        $file = $request->query->get('file');
        $movie = $this->movieRepository->find($id);
        $videos = $this->videoRepository->findVideosForMovie($movie);

        if ($file !== null && $this->videoRepository->exists($file, $movie) === false) {
            $file = null;
        }

        if ($file === null && empty($videos) === false) {
            return $this->redirectToRoute('movie.videos', [
                'id' => $id,
                'slug' => $slug,
                'file' => $videos[0]->getUrl()
            ]);
        }

        $nbVideos = count($videos);
        $currentVideoIndex = 0;
        $previousFile = null;
        $nextFile = null;
        $videoTitle = null;
        $videoId = null;
        $isPageVideo = false;

        for ($currentVideoIndex = 0; $currentVideoIndex < $nbVideos; $currentVideoIndex++) {
            if ($videos[$currentVideoIndex]->getUrl() === $file) {
                $videoTitle = $videos[$currentVideoIndex]->getTitle();
                $videoId = $videos[$currentVideoIndex]->getId();
                break;
            }
        }

        if ($currentVideoIndex > 0) {
            $previousFile = $videos[$currentVideoIndex - 1]->getUrl();
        }

        if ($currentVideoIndex + 1 < $nbVideos) {
            $nextFile = $videos[$currentVideoIndex + 1]->getUrl();
        }

        if ($movie->getPageVideo() !== null && $movie->getPageVideo()->getId() === $videos[$currentVideoIndex]->getId()) {
            $isPageVideo = true;
        }

        $currentVideoIndex += 1;

        return $this->render('movie/videos.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'videos',
            'title' => $movie->getTitle(),
            'isUserProprietary' => ($this->getUser() ? $this->movieRepository->isUserProprietaryOfMovie($id, $this->getUser()) : false),
            'file' => $file,
            'videoId' => $videoId,
            'videoTitle' => $videoTitle,
            'previousFile' => $previousFile,
            'nextFile' => $nextFile,
            'currentVideoIndex' => $currentVideoIndex,
            'nbVideos' => $nbVideos,
            'isPageVideo' => $isPageVideo
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/movie/{slug}-{id}/videos/upload', name: 'movie.videos.upload', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+'])]
    public function uploadVideo(Request $request, EntityManagerInterface $entityManager, string $slug, int $id): Response
    {
        if ($this->movieRepository->isUserProprietaryOfMovie($id, $this->getUser()) === false) {
            return $this->redirectToRoute('movie.videos', [
                'id' => $id,
                'slug' => $slug
            ]);
        }

        $movie = $this->movieRepository->find($id);
        $form = $this->createForm(VideoFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $request->files->get('video_form')['video'];
            $url = $this->s3Uploader->upload($file);
            $video = new Video();

            $video->setTitle($form['title']->getData());
            $video->setUrl($url);
            $video->setMovie($movie);

            $entityManager->persist($video);
            $entityManager->flush();

            return $this->redirectToRoute('movie.videos', [
                'id' => $id,
                'slug' => $slug,
                'file' => $url
            ]);
        }

        return $this->render('movie/videos-upload.html.twig', [
            'id' => $id,
            'slug' => $slug,
            'section' => 'videos',
            'title' => $movie->getTitle(),
            'videoForm' => $form
        ]);
    }

    #[Route('/movie/{slug}-{id}/videos/set-page-video/{videoId}', name: 'movie.videos.set-page-video', methods: ['PUT'], requirements: ['slug' => '[a-z0-9-]+', 'id' => '\d+', 'videoId' => '\d+'])]
    public function setPageVideo(EntityManagerInterface $entityManager, string $slug, int $id, int $videoId)
    {
        $movie = $this->movieRepository->find($id);
        $video = $this->videoRepository->find($videoId);

        $movie->setPageVideo($video);

        $entityManager->persist($movie);
        $entityManager->flush();

        return $this->redirectToRoute('movie.page', [
            'slug' => $slug,
            'id' => $id
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
