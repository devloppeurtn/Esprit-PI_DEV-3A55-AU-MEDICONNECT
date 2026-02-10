<?php

namespace App\Form;

use App\Entity\CoursEducatif;
use App\Entity\CategorieSante;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CoursEducatifFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du cours',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Introduction à la cardiologie',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire'),
                    new Assert\Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 12,
                    'placeholder' => 'Contenu éducatif du cours...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le contenu est obligatoire'),
                    new Assert\Length(['max' => 50000]),
                ],
            ])
            ->add('scorePourBadge', IntegerType::class, [
                'label' => 'Score requis pour le badge (points)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le score pour badge est obligatoire'),
                    new Assert\Range(['min' => 0, 'max' => 1000]),
                ],
            ])
        ;

        if ($options['show_categorie']) {
            $builder->add('categorieSante', EntityType::class, [
                'class' => CategorieSante::class,
                'label' => 'Catégorie',
                'choice_label' => 'nom',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(message: 'La catégorie est obligatoire'),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CoursEducatif::class,
            'show_categorie' => true,
        ]);
    }
}
