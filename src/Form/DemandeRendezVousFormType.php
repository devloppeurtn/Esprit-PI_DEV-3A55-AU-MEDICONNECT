<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\RendezVous;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class DemandeRendezVousFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $medecins = $options['medecins'] ?? [];
        $builder
            ->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                'choices' => $medecins,
                'choice_label' => fn (Medecin $m) => $m->getNomComplet() . ($m->getSpecialite() ? ' - ' . $m->getSpecialite() : ''),
                'label' => 'Médecin',
                'placeholder' => 'Choisir un médecin',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un médecin.']),
                ],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date et heure souhaitées',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date est requise.']),
                    new GreaterThan('now', message: 'La date doit être dans le futur.'),
                ],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Motif / Note (optionnel)',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
            'medecins' => [],
        ]);
        $resolver->setAllowedTypes('medecins', 'array');
    }
}
