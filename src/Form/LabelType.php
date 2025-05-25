<?php

namespace App\Form;

use App\Entity\Label;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de l\'étiquette',
                ],
                'label' => 'Nom',
            ])
            ->add('color', ChoiceType::class, [
                'choices' => [
                    'Rouge' => '#FF5252',
                    'Rose' => '#FF4081',
                    'Violet' => '#9C27B0',
                    'Bleu' => '#2196F3',
                    'Cyan' => '#00BCD4',
                    'Vert' => '#4CAF50',
                    'Jaune' => '#FFEB3B',
                    'Orange' => '#FF9800',
                    'Gris' => '#9E9E9E',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Couleur',
                'attr' => [
                    'class' => 'color-picker',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Label::class,
        ]);
    }
}
