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
        return 'mapbender.mbQueryBuilderElement';
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
            'tableColumns' => [
                [
                    'data' => 'name',
                    'title' => 'Title',
                ],
                [
                    'data' => 'anzeigen_reihenfolge',
                    'visible' => false,
                    'title' => 'Sort',
                ],
            ],
        );
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
                '@MapbenderQueryBuilderBundle/Resources/public/queryBuilder.element.js',
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

        foreach ($values['tableColumns'] as $i => $tableColumn) {
            switch ($tableColumn['title']) {
                case 'Title':
                    $values['tableColumns'][$i]['data'] = $values['configuration']['titleFieldName'];
                    break;
                case 'Sort':
                    $values['tableColumns'][$i]['data'] = $values['configuration']['orderByFieldName'];
                    break;
            }
        }
        return $values;
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }
}
