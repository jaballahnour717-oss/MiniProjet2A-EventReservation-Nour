<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre *',
                'attr'  => [
                    'placeholder' => 'Titre de l\'événement',
                    'class'       => 'form-control',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'attr'  => [
                    'placeholder' => 'Description détaillée...',
                    'rows'        => 5,
                    'class'       => 'form-control',
                ],
            ])
            ->add('date', DateTimeType::class, [
                'label'  => 'Date et heure *',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu *',
                'attr'  => [
                    'placeholder' => 'Adresse ou lieu',
                    'class'       => 'form-control',
                ],
            ])
            ->add('seats', IntegerType::class, [
                'label' => 'Nombre de places *',
                'attr'  => [
                    'min'   => 1,
                    'class' => 'form-control',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image (optionnel)',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Image([
                        'maxSize'        => '5M',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}