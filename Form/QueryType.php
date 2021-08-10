<?php


namespace Mapbender\QueryBuilderBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.query.builder.sql.title',
            ))
            ->add('connection', 'Mapbender\QueryBuilderBundle\Form\ConnectionChoiceType', array(
                'label' => 'mb.query.builder.sql.connection.name',
            ))
            ->add('order', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Order', /** @todo: translate? */
                'required' => false,
            ))
            ->add('sql', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'label' => 'SQL',
                'attr' => array(
                    'rows' => 16,
                ),
            ))
        ;
    }
}
