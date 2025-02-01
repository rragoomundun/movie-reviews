<?php

namespace App\Form;

use App\Entity\Movie;
use App\Entity\Video;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class VideoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('thumbnail', FileType::class, [
                'mapped' => false,
                'constraints' => new NotNull(),
                'attr' => [
                    'accept' => 'image/*'
                ]
            ])
            ->add('video', FileType::class, [
                'mapped' => false,
                'constraints' => new NotNull(),
                'attr' => [
                    'accept' => 'video/*'
                ]
            ])
            ->add('upload', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
