<?php

namespace App\Form;

use App\Entity\DossierMedical;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class DossierMedicalFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => ['rows' => 3],
                'constraints' => [
                    new Length(['max' => 2000, 'maxMessage' => 'Maximum {{ limit }} caractères.']),
                ],
            ])
            ->add('maladiesChroniques', TextareaType::class, [
                'label' => 'Maladies chroniques',
                'required' => false,
                'attr' => ['rows' => 3],
                'constraints' => [
                    new Length(['max' => 2000, 'maxMessage' => 'Maximum {{ limit }} caractères.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierMedical::class,
        ]);
    }
}
