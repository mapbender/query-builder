<?php

namespace Mapbender\QueryBuilderBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryBuilderAdminType extends AbstractType
{
    protected $dataStoreNames = array();

    /**
     * @param mixed[]|null $dataStores
     */
    public function __construct($dataStores)
    {
        $this->dataStoreNames = array_keys($dataStores ?: array());
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'queryBuilder';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'element'     => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataStoreSelectValues = array_combine($this->dataStoreNames, $this->dataStoreNames);

        $builder
            ->add('source', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                    'choices'     => $dataStoreSelectValues,
                    'required'    => true,
                    'empty_value' => null
                )
            )
            ->add('sqlFieldName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => true))
            ->add('orderByFieldName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => true))
            ->add('titleFieldName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => true))
            ->add('connectionFieldName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array('required' => true))

            ->add('allowCreate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowEdit', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowSave', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowRemove', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowExecute', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowPrint', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowExport', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false))
            ->add('allowSearch', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array('required' => false));
    }
}
