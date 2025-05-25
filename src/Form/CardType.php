<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\BoardList;
use App\Entity\Board;
use App\Entity\Label;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $board = $options['board'];
        
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre de la carte',
                ],
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Description de la carte',
                    'rows' => 5,
                ],
                'label' => 'Description',
                'required' => false,
            ])
            ->add('completed', CheckboxType::class, [
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label' => 'Terminée',
                'required' => false,
            ])
            ->add('dueDate', DateTimeType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Date d\'échéance',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('boardList', EntityType::class, [
                'class' => BoardList::class,
                'query_builder' => function (EntityRepository $er) use ($board) {
                    return $er->createQueryBuilder('bl')
                        ->where('bl.board = :board')
                        ->setParameter('board', $board)
                        ->orderBy('bl.position', 'ASC');
                },
                'choice_label' => 'name',
                'label' => 'Liste',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('labels', EntityType::class, [
                'class' => Label::class,
                'query_builder' => function (EntityRepository $er) use ($board) {
                    return $er->createQueryBuilder('l')
                        ->where('l.board = :board')
                        ->setParameter('board', $board)
                        ->orderBy('l.name', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Étiquettes',
                'required' => false,
                'attr' => [
                    'class' => 'labels-checkboxes',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'board' => null,
        ]);
        
        $resolver->setRequired('board');
        $resolver->setAllowedTypes('board', Board::class);
    }
}
