<?php

namespace App\Controller;

use App\Form\UpdateEmailFormType;
use App\Form\UpdatePasswordFormType;
use App\Repository\MovieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SettingsController extends AbstractController
{
    private readonly UserRepository $userRepository;
    private readonly MovieRepository $movieRepository;
    private readonly TokenStorageInterface $tokenStorage;

    public function __construct(
        UserRepository $userRepository,
        MovieRepository $movieRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->userRepository = $userRepository;
        $this->movieRepository = $movieRepository;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/settings', name: 'settings.index')]
    public function index(): Response
    {
        $updateEmailForm = $this->createForm(UpdateEmailFormType::class);
        $updatePasswordForm = $this->createForm(UpdatePasswordFormType::class);

        return $this->render('settings/index.html.twig', [
            'updateEmailForm' => $updateEmailForm,
            'updatePasswordForm' => $updatePasswordForm,
        ]);
    }

    #[Route('/settings/email', name: 'settings.email', methods: ['PUT', 'POST'])]
    public function updateEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newEmail = $request->request->all()['update_email_form']['email'];
        $user = $this->getUser();

        $user->setEmail($newEmail);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'E-Mail address updated.');

        return $this->redirectToRoute('settings.index');
    }

    #[Route('/settings/password', name: 'settings.password', methods: ['PUT'])]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $newPassword = $request->request->all()['update_password_form']['plainPassword']['first'];
        $user = $this->getUser();

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Password updated.');

        return $this->redirectToRoute('settings.index');
    }

    #[Route('/settings/delete', name: 'settings.delete', methods: ['DELETE'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $movies = $this->movieRepository->findBy(['proprietary' => $user]);

        foreach ($movies as $movie) {
            $movie->setPageVideo(null);
            $entityManager->persist($movie);
        }

        $entityManager->flush();

        $entityManager->remove($user);
        $entityManager->flush();

        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return $this->redirectToRoute('home');
    }
}
