<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(MovieRepository $movieRepository): Response
    {
        $newestMovies = $movieRepository->find10NewMovies();

        return $this->render('home/index.html.twig', [
            'newestMovies' => $newestMovies
        ]);
    }
}
