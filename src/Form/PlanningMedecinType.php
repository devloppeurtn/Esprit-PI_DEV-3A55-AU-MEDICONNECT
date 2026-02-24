<?php

namespace App\Form;

use App\Entity\PlanningMedecin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningMedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('heureDebutMatin', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Début Matin'])
            ->add('heureFinMatin', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Fin Matin'])
            ->add('heureDebutApresMidi', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Début Après-midi'])
            ->add('heureFinApresMidi', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Fin Après-midi'])
            ->add('dureeConsultation', ChoiceType::class, [
                'choices'  => ['15 min' => 15, '20 min' => 20, '30 min' => 30, '45 min' => 45, '60 min' => 60],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Durée de consultation',
            ])
            ->add('joursOuverture', ChoiceType::class, [
                'choices'  => ['Lundi'=>'lundi','Mardi'=>'mardi','Mercredi'=>'mercredi','Jeudi'=>'jeudi','Vendredi'=>'vendredi','Samedi'=>'samedi','Dimanche'=>'dimanche'],
                'multiple' => true, 'expanded' => true,
                'label' => 'Jours d\'ouverture',
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PlanningMedecin::class]);
    }
}