<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    private readonly MovieRepository $movieRepository;

    public function __construct(MovieRepository $movieRepository)
    {
        $this->movieRepository = $movieRepository;
    }

    #[Route('/search', name: 'search.index')]
    public function index(Request $request): Response
    {
        $query = $request->get('query');
        $movies = $this->movieRepository->findLike($query);

        return $this->render('search/index.html.twig', [
            'movies' => $movies,
        ]);
    }
}
