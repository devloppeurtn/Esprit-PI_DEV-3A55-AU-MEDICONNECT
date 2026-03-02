<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\Participation;
use App\Entity\RoleParticipation;
use App\Entity\Secretaire;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
<<<<<<< HEAD
use Symfony\Component\Form\Extension\Core\Type\FileType;
=======
<<<<<<< HEAD
=======
use Symfony\Component\Form\Extension\Core\Type\FileType;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
<<<<<<< HEAD
use Symfony\Component\Validator\Constraints\File;
=======
<<<<<<< HEAD
=======
use Symfony\Component\Validator\Constraints\File;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur $user */
        $user = $options['user'];

        $builder
            ->add('nomComplet', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['class' => 'form-control'],
<<<<<<< HEAD
=======
<<<<<<< HEAD
                'constraints' => [new NotBlank(['message' => 'Le nom est requis'])],
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractÃ¨res.', 'maxMessage' => 'Le nom ne doit pas dÃ©passer 255 caractÃ¨res.']),
                ],
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control'],
<<<<<<< HEAD
=======
<<<<<<< HEAD
                'constraints' => [new NotBlank(['message' => 'L\'email est requis'])],
            ]);

        // Champs selon le rôle
        if ($user instanceof Patient) {
            $builder
                ->add('telephone', TelType::class, [
                    'label' => 'Téléphone',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: +216 12 345 678'],
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est requis']),
                    new \Symfony\Component\Validator\Constraints\Email(['message' => 'Email invalide.']),
                ],
            ]);

        // Champs selon le rÃ´le
        if ($user instanceof Patient) {
            $builder
                ->add('telephone', TelType::class, [
                    'label' => 'TÃ©lÃ©phone',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: +216 12 345 678'],
                    'constraints' => [new Length(['max' => 30, 'maxMessage' => 'TÃ©lÃ©phone trop long.'])],
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ])
                ->add('dateNaissance', DateType::class, [
                    'label' => 'Date de naissance',
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => ['class' => 'form-control'],
                ])
                ->add('adresse', TextareaType::class, [
                    'label' => 'Adresse',
                    'required' => false,
<<<<<<< HEAD
                    'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Adresse complÃ¨te'],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
=======
<<<<<<< HEAD
                    'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Adresse complète'],
=======
                    'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Adresse complÃ¨te'],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ]);
        } elseif ($user instanceof Medecin) {
            $builder
                ->add('specialite', TextType::class, [
<<<<<<< HEAD
=======
<<<<<<< HEAD
                    'label' => 'Spécialité',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Cardiologie'],
                ])
                ->add('numeroLicence', TextType::class, [
                    'label' => 'Numéro de licence',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    'label' => 'SpÃ©cialitÃ©',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Cardiologie'],
                    'constraints' => [new Length(['max' => 255, 'maxMessage' => 'SpÃ©cialitÃ© trop longue.'])],
                ])
                ->add('numeroLicence', TextType::class, [
                    'label' => 'NumÃ©ro de licence',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                    'constraints' => [new Length(['max' => 100, 'maxMessage' => 'NumÃ©ro de licence trop long.'])],
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ])
                ->add('adresseCabinet', TextareaType::class, [
                    'label' => 'Adresse du cabinet',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'rows' => 3],
<<<<<<< HEAD
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
=======
<<<<<<< HEAD
=======
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ]);
        } elseif ($user instanceof Secretaire) {
            $builder
                ->add('telephone', TelType::class, [
<<<<<<< HEAD
=======
<<<<<<< HEAD
                    'label' => 'Téléphone',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    'label' => 'TÃ©lÃ©phone',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                    'constraints' => [new Length(['max' => 30, 'maxMessage' => 'TÃ©lÃ©phone trop long.'])],
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                ]);
        } elseif ($user instanceof Participation) {
            $builder
                ->add('roleDansEvenement', EnumType::class, [
<<<<<<< HEAD
                    'label' => 'RÃ´le dans l\'Ã©vÃ©nement',
=======
<<<<<<< HEAD
                    'label' => 'Rôle dans l\'événement',
=======
                    'label' => 'RÃ´le dans l\'Ã©vÃ©nement',
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    'class' => RoleParticipation::class,
                    'choice_label' => fn ($choice) => match ($choice) {
                        RoleParticipation::ORGANISATEUR => 'Organisateur',
                        RoleParticipation::INTERVENANT => 'Intervenant',
                        RoleParticipation::PARTICIPANT => 'Participant',
                        default => $choice->value,
                    },
                    'attr' => ['class' => 'form-select'],
                ])
                ->add('presenceConfirmee', CheckboxType::class, [
<<<<<<< HEAD
                    'label' => 'PrÃ©sence confirmÃ©e',
=======
<<<<<<< HEAD
                    'label' => 'Présence confirmée',
=======
                    'label' => 'PrÃ©sence confirmÃ©e',
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
                    'required' => false,
                    'attr' => ['class' => 'form-check-input'],
                ]);
        }

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        // Photo de profil
        $builder->add('photo', FileType::class, [
            'label' => 'Photo de profil',
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
        ]);

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        // Optionnel : changement de mot de passe
        $builder->add('plainPassword', PasswordType::class, [
            'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
            'mapped' => false,
            'required' => false,
<<<<<<< HEAD
            'attr' => ['class' => 'form-control', 'placeholder' => 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢'],
            'constraints' => [
                new Length(['min' => 6, 'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractÃ¨res']),
=======
<<<<<<< HEAD
            'attr' => ['class' => 'form-control', 'placeholder' => '••••••••'],
            'constraints' => [
                new Length(['min' => 6, 'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères']),
=======
            'attr' => ['class' => 'form-control', 'placeholder' => 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢'],
            'constraints' => [
                new Length(['min' => 6, 'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractÃ¨res']),
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'user' => null,
        ]);
        $resolver->setRequired('user');
    }
}
<<<<<<< HEAD

=======
<<<<<<< HEAD
=======

>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
