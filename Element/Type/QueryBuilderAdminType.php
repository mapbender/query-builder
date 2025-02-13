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
            ->add('configuration', YAMLConfigurationType::class, $this->createInlineHelpText([
                    'required' => true,
                    'label' => 'mb.querybuilder.admin.configuration',
                    'help' => 'mb.querybuilder.admin.configuration_help',
                ], $this->trans)
            )
            ->add('allowCreate', CheckboxType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowCreate',
                'help' => 'mb.querybuilder.admin.allowCreate_help'
            ], $this->trans))
            ->add('allowEdit', CheckboxType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowEdit',
                'help' => 'mb.querybuilder.admin.allowEdit_help'
            ], $this->trans))
            ->add('allowRemove', CheckboxType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.querybuilder.admin.allowRemove',
                'help' => 'mb.querybuilder.admin.allowRemove_help'
            ], $this->trans))
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
        ;
    }
}
