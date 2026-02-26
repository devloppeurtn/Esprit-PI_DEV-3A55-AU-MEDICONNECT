<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EvenementFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank()]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu/Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 6]
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu/Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Salle 101, Hôpital Central']
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Date de l\'événement',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('eventTime', TextType::class, [
                'label' => 'Heure de l\'événement',
                'required' => false,
                'attr' => ['class' => 'form-control', 'type' => 'time', 'placeholder' => 'HH:MM']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
