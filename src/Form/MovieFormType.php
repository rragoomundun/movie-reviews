<?php

namespace App\Form;

use App\Entity\Movie;
use App\Entity\MovieGenre;
use App\Entity\Person;
use App\Entity\User;
use App\Repository\MovieGenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MovieFormType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('coverImage', FileType::class)
            ->add('releaseDate', null, [
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('duration')
            ->add('synopsis')
            ->add('genre', EntityType::class, [
                'class' => MovieGenre::class,
                'choice_label' => 'label',
                'query_builder' => function (MovieGenreRepository $movieGenreRepository) {
                    return $movieGenreRepository
                        ->createQueryBuilder('g');
                }
            ])
            ->add('director', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'id',
            ])
            ->add('add', SubmitType::class, [
                'label' => 'Add Movie'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Movie::class,
        ]);
    }
}
