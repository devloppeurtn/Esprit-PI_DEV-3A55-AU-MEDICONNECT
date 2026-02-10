<?php

namespace App\Form;

use App\Entity\Consultation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConsultationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                'label' => 'Date de la consultation',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date est requise.']),
                ],
            ])
            ->add('diagnostic', TextareaType::class, [
                'label' => 'Diagnostic',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [
                    new Length(['max' => 2000]),
                ],
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé de la consultation',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [
                    new Length(['max' => 2000]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
