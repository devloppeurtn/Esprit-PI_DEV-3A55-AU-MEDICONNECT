<?php

namespace App\Form;

use App\Entity\DocumentPatient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DocumentPatientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichier', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un fichier.']),
                    new File([
                        'maxSize' => '25M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Formats autorisés : PDF, JPEG, PNG, GIF (max 25 Mo).',
                    ]),
                ],
            ])
            ->add('typeDocument', ChoiceType::class, [
                'label' => 'Type de document',
                'choices' => [
                    'Analyse' => 'analyse',
                    'Radio / Imagerie' => 'radio',
                    'Certificat médical' => 'certificat',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisir',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir le type de document.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => ['rows' => 2],
                'constraints' => [
                    new Length(['max' => 500, 'maxMessage' => 'Maximum {{ limit }} caractères.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentPatient::class,
        ]);
    }
}


