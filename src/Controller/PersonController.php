<?php

namespace App\Controller;

use App\Entity\Person;
use App\Form\PersonFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PersonController extends AbstractController
{
    #[Route('/person/add', name: 'person.add')]
    public function addPerson(Request $request, EntityManagerInterface $entityManager): Response
    {
        $person = new Person();
        $form = $this->createForm(PersonFormType::class, $person);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($person);
            $entityManager->flush();

            $this->addFlash('success', 'A new person has been created.');

            return $this->redirectToRoute('person.add');
        }

        return $this->render('person/add.html.twig', [
            'personForm' => $form
        ]);
    }
}
