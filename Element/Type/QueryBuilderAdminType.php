<?php

namespace Mapbender\QueryBuilderBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class QueryBuilderAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $trans)
    {
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('configuration', YAMLConfigurationType::class, array(
                    'required' => true,
                    'label' => 'mb.querybuilder.admin.configuration'
                )
            )
            ->add('allowCreate', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowCreate'
            ))
            ->add('allowEdit', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowEdit'
            ))
            ->add('allowRemove', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowRemove'
            ))
            ->add('allowExecute', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowExecute'
            ))
            ->add('allowFileExport', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowFileExport'
            ))
            ->add('allowHtmlExport', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowHtmlExport'
            ))
            ->add('allowSearch', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowSearch'
            ))
            // @todo: add field for implemented option 'tableColumns'? (can be set via yaml definition; check functionality)
        ;
    }
}
