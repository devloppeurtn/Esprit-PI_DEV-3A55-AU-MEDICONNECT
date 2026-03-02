<?php

namespace App\Form;

use App\Entity\RoleUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
<<<<<<< HEAD
use Symfony\Component\Form\Extension\Core\Type\FileType;
=======
<<<<<<< HEAD
=======
use Symfony\Component\Form\Extension\Core\Type\FileType;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
<<<<<<< HEAD
=======
<<<<<<< HEAD
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
<<<<<<< HEAD
                'attr' => [
                    'placeholder' => 'Email',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email',
                    ]),
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                'attr' => ['placeholder' => 'Email', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre email']),
                    new Email(['message' => 'Email invalide.']),
                    new Length(['max' => 180, 'maxMessage' => 'Email trop long.']),
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ],
            ])
            ->add('nomComplet', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom complet',
                    'class' => 'form-control',
                ],
                'constraints' => [
<<<<<<< HEAD
                    new NotBlank(['message' => 'Veuillez entrer votre nom complet']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractÃ¨res.', 'maxMessage' => 'Le nom ne doit pas dÃ©passer 255 caractÃ¨res.']),
=======
<<<<<<< HEAD
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom complet',
                    ]),
=======
                    new NotBlank(['message' => 'Veuillez entrer votre nom complet']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractÃ¨res.', 'maxMessage' => 'Le nom ne doit pas dÃ©passer 255 caractÃ¨res.']),
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractÃ¨res',
=======
<<<<<<< HEAD
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
=======
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractÃ¨res',
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
=======
<<<<<<< HEAD
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
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
                        'maxSizeMessage' => 'L\'image ne doit pas dÃ©passer 5 Mo',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'TÃ©lÃ©phone', 'class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numÃ©ro de tÃ©lÃ©phone est requis']),
                    new Length(['max' => 20, 'maxMessage' => 'TÃ©lÃ©phone trop long.']),
                ],
            ]);

        // Champs spÃ©cifiques selon le rÃ´le
        if ($role === RoleUtilisateur::PATIENT) {
            $builder
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD
                    'attr' => ['placeholder' => 'Adresse', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
=======
<<<<<<< HEAD
                    'attr' => [
                        'placeholder' => 'Adresse',
                        'class' => 'form-control',
                        'rows' => 3,
                    ],
=======
                    'attr' => ['placeholder' => 'Adresse', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ]);
        } elseif ($role === RoleUtilisateur::MEDECIN) {
            $builder
                ->add('specialite', TextType::class, [
                    'label' => false,
<<<<<<< HEAD
=======
<<<<<<< HEAD
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Spécialité',
                        'class' => 'form-control',
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    'required' => true,
                    'attr' => ['placeholder' => 'SpÃ©cialitÃ©', 'class' => 'form-control'],
                    'constraints' => [
                        new NotBlank(['message' => 'La spÃ©cialitÃ© est requise pour les mÃ©decins']),
                        new Length(['max' => 255, 'maxMessage' => 'SpÃ©cialitÃ© trop longue.']),
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    ],
                ])
                ->add('adresseCabinet', TextareaType::class, [
                    'label' => false,
                    'required' => false,
<<<<<<< HEAD
                    'attr' => ['placeholder' => 'Adresse du cabinet', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
=======
<<<<<<< HEAD
                    'attr' => [
                        'placeholder' => 'Adresse du cabinet',
                        'class' => 'form-control',
                        'rows' => 3,
                    ],
=======
                    'attr' => ['placeholder' => 'Adresse du cabinet', 'class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ])
                ->add('numeroLicence', TextType::class, [
                    'label' => false,
                    'required' => false,
<<<<<<< HEAD
                    'attr' => ['placeholder' => 'NumÃ©ro de licence', 'class' => 'form-control'],
                    'constraints' => [new Length(['max' => 100, 'maxMessage' => 'NumÃ©ro de licence trop long.'])],
=======
<<<<<<< HEAD
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
=======
                    'attr' => ['placeholder' => 'NumÃ©ro de licence', 'class' => 'form-control'],
                    'constraints' => [new Length(['max' => 100, 'maxMessage' => 'NumÃ©ro de licence trop long.'])],
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
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
<<<<<<< HEAD

=======
<<<<<<< HEAD
=======

>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
