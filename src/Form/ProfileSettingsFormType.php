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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
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
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractÃ¨res.', 'maxMessage' => 'Le nom ne doit pas dÃ©passer 255 caractÃ¨res.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control'],
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
                    'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Adresse complÃ¨te'],
                    'constraints' => [new Length(['max' => 500, 'maxMessage' => 'Adresse trop longue.'])],
                ]);
        } elseif ($user instanceof Medecin) {
            $builder
                ->add('specialite', TextType::class, [
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
                ])
                ->add('adresseCabinet', TextareaType::class, [
                    'label' => 'Adresse du cabinet',
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'rows' => 3],
                    'constraints' => [new Length(['max' => 1000, 'maxMessage' => 'Adresse trop longue.'])],
                ]);
        } elseif ($user instanceof Secretaire) {
            $builder
                ->add('telephone', TelType::class, [
                    'label' => 'TÃ©lÃ©phone',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                    'constraints' => [new Length(['max' => 30, 'maxMessage' => 'TÃ©lÃ©phone trop long.'])],
                ]);
        } elseif ($user instanceof Participation) {
            $builder
                ->add('roleDansEvenement', EnumType::class, [
                    'label' => 'RÃ´le dans l\'Ã©vÃ©nement',
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
                    'label' => 'PrÃ©sence confirmÃ©e',
                    'required' => false,
                    'attr' => ['class' => 'form-check-input'],
                ]);
        }

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

        // Optionnel : changement de mot de passe
        $builder->add('plainPassword', PasswordType::class, [
            'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
            'mapped' => false,
            'required' => false,
            'attr' => ['class' => 'form-control', 'placeholder' => 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢'],
            'constraints' => [
                new Length(['min' => 6, 'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractÃ¨res']),
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

