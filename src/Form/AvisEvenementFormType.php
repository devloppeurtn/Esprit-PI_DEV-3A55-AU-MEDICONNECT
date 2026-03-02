<?php

namespace App\Form;

use App\Entity\AvisEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AvisEvenementFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Évaluation de l\'événement',
                'choices' => [
                    '⭐ 1 étoile - Pas satisfait' => 1,
                    '⭐⭐ 2 étoiles - Peu satisfait' => 2,
                    '⭐⭐⭐ 3 étoiles - Satisfait' => 3,
                    '⭐⭐⭐⭐ 4 étoiles - Très satisfait' => 4,
                    '⭐⭐⭐⭐⭐ 5 étoiles - Excellent!' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Vos commentaires (Optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Partagez votre expérience... Qu\'avez-vous aimé? Qu\'aurais-je pu améliorer?',
                    'maxlength' => 1500
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvisEvenement::class,
        ]);
    }
}
