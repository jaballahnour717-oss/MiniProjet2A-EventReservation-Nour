<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet *',
                'attr'  => [
                    'placeholder' => 'Votre nom et prénom',
                    'class'       => 'form-control',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email *',
                'attr'  => [
                    'placeholder' => 'votre@email.com',
                    'class'       => 'form-control',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone *',
                'attr'  => [
                    'placeholder' => '+216 XX XXX XXX',
                    'class'       => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}