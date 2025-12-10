<?php

namespace Mapbender\QueryBuilderBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\QueryBuilderBundle\Element\Type\QueryBuilderAdminType;
use Mapbender\QueryBuilderBundle\Form\QueryType;
use Mapbender\QueryBuilderBundle\Permission\QueryBuilderPermissionProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class QueryBuilderElement extends AbstractElementService
{
    public function __construct(
        protected FormFactoryInterface          $formFactory,
        protected HttpHandler                   $httpHandler,
        protected AuthorizationCheckerInterface $security,
    )
    {
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return 'mb.querybuilder.class.title';
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return 'mb.querybuilder.class.description';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbQueryBuilder';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'allowRemove' => false,
            'allowEdit' => false,
            'allowExecute' => true,
            'allowCreate' => false,
            'allowHtmlExport' => true,
            'allowFileExport' => true,
            'allowSearch' => false,
            'configuration' => self::getYamlConfigurationDefaults(),
            'element_icon' => self::getDefaultIcon(),
        );
    }

    public static function getDefaultIcon()
    {
        return 'iconChartColumn';
    }

    public static function getYamlConfigurationDefaults(): array
    {
        return [
            'connection' => 'default',
            'table' => null, // must be given by configuration
            'uniqueId' => 'id',
            'titleFieldName' => 'name',
            'sqlFieldName' => 'sql_definition',
            'orderByFieldName' => 'id',
            'connectionFieldName' => null, // default is the connection given in 'connection'
            'filter' => null,
            'export_format' => 'xlsx',
        ];
    }

    public static function getType()
    {
        return QueryBuilderAdminType::class;
    }

    public static function getFormTemplate()
    {
        return '@MapbenderQueryBuilder/ElementAdmin/queryBuilder.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderQueryBuilder/Element/queryBuilder.html.twig');
        $view->attributes['class'] = 'mb-element-queryBuilder';
        $view->attributes['data-title'] = $element->getTitle();
        $form = $this->formFactory->createNamed('querybuilder', QueryType::class);
        $view->variables['form'] = $form->createView();
        return $view;
    }


    public function getRequiredAssets(Element $element)
    {
        return array(
            'css' => array(
                '@MapbenderQueryBuilderBundle/Resources/styles/queryBuilder.element.scss',
            ),
            'js' => array(
                '@MapbenderQueryBuilderBundle/Resources/public/MbQueryBuilder.js',
            ),
            'trans' => array(
                'mb.querybuilder.frontend.*',
            ),
        );
    }

    public function getClientConfiguration(Element $element)
    {
        $values = $element->getConfiguration() + $this->getDefaultConfiguration();
        $values['configuration'] = $values['configuration'] + self::getYamlConfigurationDefaults();

        $values['allowEdit'] = $values['allowEdit'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_EDIT);
        $values['allowCreate'] = $values['allowCreate'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_CREATE);
        $values['allowRemove'] = $values['allowRemove'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_DELETE);

        return $values;
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }
}
