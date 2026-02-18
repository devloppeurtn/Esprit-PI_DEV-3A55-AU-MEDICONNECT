<?php

namespace App\Form;

use App\Entity\Ordonnance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrdonnanceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('medicament', TextType::class, [
                'label' => 'Médicament',
                'attr' => ['placeholder' => 'Nom du médicament'],
                'constraints' => [
                    new NotBlank(['message' => 'Le médicament est requis.']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('methodeUtilisation', TextareaType::class, [
                'label' => 'Méthode d\'utilisation (posologie)',
                'attr' => ['rows' => 3, 'placeholder' => 'Ex: 1 comprimé matin et soir pendant 7 jours'],
                'constraints' => [
                    new NotBlank(['message' => 'La méthode d\'utilisation est requise.']),
                    new Length(['max' => 1000]),
                ],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions complémentaires (optionnel)',
                'required' => false,
                'attr' => ['rows' => 2],
                'constraints' => [
                    new Length(['max' => 500]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ordonnance::class,
        ]);
    }
}
