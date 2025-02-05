<?php


namespace Mapbender\QueryBuilderBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'label' => 'mb.query.builder.sql.title',
            ))
            ->add('connection', ConnectionChoiceType::class, array(
                'label' => 'mb.query.builder.sql.connection.name',
            ))
            ->add('order', TextType::class, array(
                'label' => 'Order', /** @todo: translate? */
                'required' => false,
            ))
            ->add('sql', TextareaType::class, array(
                'label' => 'SQL',
                'attr' => array(
                    'rows' => 16,
                ),
            ))
        ;
    }
}
