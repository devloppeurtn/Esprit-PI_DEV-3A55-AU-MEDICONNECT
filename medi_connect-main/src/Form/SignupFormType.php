<?php

namespace App\Form;

use App\Entity\RoleUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                'attr' => [
                    'placeholder' => 'Email',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email',
                    ]),
                ],
            ])
            ->add('nomComplet', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom complet',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom complet',
                    ]),
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
            ]);

        // Champs spécifiques selon le rôle
        if ($role === RoleUtilisateur::PATIENT) {
            $builder
                ->add('telephone', TelType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Téléphone',
                        'class' => 'form-control',
                    ],
                ])
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
                    'attr' => [
                        'placeholder' => 'Adresse',
                        'class' => 'form-control',
                        'rows' => 3,
                    ],
                ]);
        } elseif ($role === RoleUtilisateur::MEDECIN) {
            $builder
                ->add('specialite', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Spécialité',
                        'class' => 'form-control',
                    ],
                ])
                ->add('adresseCabinet', TextareaType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Adresse du cabinet',
                        'class' => 'form-control',
                        'rows' => 3,
                    ],
                ])
                ->add('numeroLicence', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Numéro de licence',
                        'class' => 'form-control',
                    ],
                ]);
        } elseif ($role === RoleUtilisateur::SECRETAIRE) {
            $builder
                ->add('telephone', TelType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Téléphone',
                        'class' => 'form-control',
                    ],
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
