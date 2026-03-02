<?php

namespace App\Form;

use App\Entity\EventFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFeedbackFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Votre note',
                'choices' => [
                    '⭐ 1 étoile - Très insatisfait' => 1,
                    '⭐⭐ 2 étoiles - Insatisfait' => 2,
                    '⭐⭐⭐ 3 étoiles - Moyen' => 3,
                    '⭐⭐⭐⭐ 4 étoiles - Satisfait' => 4,
                    '⭐⭐⭐⭐⭐ 5 étoiles - Très satisfait' => 5,
                ],
                'expanded' => false,
                'placeholder' => 'Choisissez une note',
                'attr' => [
                    'class' => 'form-select form-select-lg'
                ],
                'required' => true,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Votre commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Partagez votre expérience sur cet événement...',
                    'maxlength' => 1000
                ],
                'help' => 'Maximum 1000 caractères'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventFeedback::class,
        ]);
    }
}
