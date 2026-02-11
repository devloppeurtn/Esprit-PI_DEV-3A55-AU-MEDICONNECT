<?php

namespace App\Form;

use App\Entity\RoleUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $role = $options['role'] ?? null;

        $builder
            ->add('email', EmailType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Email', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre email']),
                    new Email(['message' => 'Email invalide.']),
                    new Length(['max' => 180, 'maxMessage' => 'Email trop long.']),
                ],
            ])
            ->add('nomComplet', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom complet',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom complet']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractères.', 'maxMessage' => 'Le nom ne doit pas dépasser 255 caractères.']),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Mot de passe',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Confirmer le mot de passe',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez confirmer votre mot de passe',
                    ]),
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF ou WebP)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Téléphone', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est requis']),
                    new Length(['max' => 20, 'maxMessage' => 'Téléphone trop long.']),
                ],
            ]);

        // Champs spécifiques selon le rôle
        if ($role === RoleUtilisateur::PATIENT) {
            $builder
                ->add('dateNaissance', DateType::class, [
                    'label' => false,
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => [
                        'placeholder' => 'Date de naissance',
                        'class' => 'form-control',
                    ],
                ])
                ->add('adresse', TextareaType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => ['placeholder' => 'Adresse', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
                ]);
        } elseif ($role === RoleUtilisateur::MEDECIN) {
            $builder
                ->add('specialite', TextType::class, [
                    'label' => false,
                    'required' => true,
                    'attr' => ['placeholder' => 'Spécialité', 'class' => 'form-control'],
                    'constraints' => [
                        new NotBlank(['message' => 'La spécialité est requise pour les médecins']),
                        new Length(['max' => 255, 'maxMessage' => 'Spécialité trop longue.']),
                    ],
                ])
                ->add('adresseCabinet', TextareaType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => ['placeholder' => 'Adresse du cabinet', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
                ])
                ->add('numeroLicence', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => ['placeholder' => 'Numéro de licence', 'class' => 'form-control'],
                    'constraints' => [new Length(['max' => 100, 'maxMessage' => 'Numéro de licence trop long.'])],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'role' => null,
            'data_class' => null,
        ]);
    }
}
