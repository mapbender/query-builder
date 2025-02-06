<?php

namespace Mapbender\QueryBuilderBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class QueryBuilderAdminType extends AbstractType
{
    protected $dataStoreNames = array();

    use MapbenderTypeTrait;

    private TranslatorInterface $trans;

    #public function __construct(TranslatorInterface $trans)
    #{
    #    $this->trans = $trans;
    #}

    /**
     * @param mixed[]|null $dataStores
     */
    public function __construct(array $dataStores, TranslatorInterface $trans)
    {
        $this->trans = $trans;
        $this->dataStoreNames = array_keys($dataStores);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataStoreSelectValues = array_combine($this->dataStoreNames, $this->dataStoreNames);
        // @todo: add translatable field labels

        $builder
            ->add('source', ChoiceType::class, array(
                    'choices' => $dataStoreSelectValues,
                    'required' => true,
                    'label' => 'mb.querybuilder.admin.source'
                )
            )
            ->add('sqlFieldName', TextType::class, array(
                'required' => true,
                'label' => 'mb.querybuilder.admin.sqlFieldName'
            ))
            ->add('orderByFieldName', TextType::class, array(
                'required' => true,
                'label' => 'mb.querybuilder.admin.orderByFieldName'
            ))
            ->add('titleFieldName', TextType::class, array(
                'required' => true,
                'label' => 'mb.querybuilder.admin.titleFieldName'
            ))
            ->add('connectionFieldName', TextType::class, array(
                'required' => true,
                'label' => 'mb.querybuilder.admin.connectionFieldName'
            ))
            ->add('allowCreate', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowCreate'
            ))
            ->add('allowEdit', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowEdit'
            ))
            ->add('allowSave', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowSave'
            ))
            ->add('allowRemove', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowRemove'
            ))
            ->add('allowExecute', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowExecute'
            ))
            ->add('allowExport', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowExport'
            ))
            ->add('allowSearch', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowSearch'
            ))
            // @todo: add field for implemented option 'tableColumns'? (can be set via yaml definition; check functionality)
            // @todo: add field for implemented option 'allowHtmlExport'? (can be set via yaml definition; check functionality)
        ;
    }
}
