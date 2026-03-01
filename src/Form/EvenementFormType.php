<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Enum\TypeEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class EvenementFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [];
        foreach (TypeEvenement::cases() as $case) {
            $typeChoices[$case->label()] = $case;
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('typeEvenement', ChoiceType::class, [
                'label' => 'Type d\'evenement',
                'required' => false,
                'choices' => $typeChoices,
                'placeholder' => 'Selectionner un type',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu/Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 6],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu/Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Salle 101, Hopital Central'],
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Date de l\'evenement',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('eventTime', TextType::class, [
                'label' => 'Heure de l\'evenement',
                'required' => false,
                'attr' => ['class' => 'form-control', 'type' => 'time', 'placeholder' => 'HH:MM'],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 1, 'placeholder' => 'Ex: 50'],
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Le nombre maximum doit etre au moins 1.',
                    ]),
                ],
            ])
            ->add('attachmentFile', FileType::class, [
                'label' => 'Document associe (PDF/Image)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => '.pdf,image/*'],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez televerser un PDF ou une image valide.',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
