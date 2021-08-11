<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QueryBuilderElement extends AbstractElementService implements ConfigMigrationInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var HttpHandler */
    protected $httpHandler;

    public function __construct(FormFactoryInterface $formFactory,
                                HttpHandler $httpHandler)
    {
        $this->formFactory = $formFactory;
        $this->httpHandler = $httpHandler;
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "Query builder";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "Build, list SQL queries and display result, which can be edited to.";
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
            'source' => 'default',
            'allowRemove' => false,
            'allowEdit' => false,
            'allowExecute' => true,
            'allowSave' => false,
            'allowCreate' => false,
            'allowExport' => true,
            'allowSearch' => false,
            'sqlFieldName' => 'sql_definition',
            'orderByFieldName' => 'anzeigen_reihenfolge',
            'connectionFieldName' => 'connection_name',
            'titleFieldName' => 'name',
            'tableColumns' => array(
                0 => array(
                    'data' => 'name',
                    'title' => 'Title',
                ),
                1 => array(
                    'data' => 'anzeigen_reihenfolge',
                    'visible' => false,
                    'title' => 'Sort',
                ),
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\QueryBuilderBundle\Element\Type\QueryBuilderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderQueryBuilderBundle:ElementAdmin:queryBuilder.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderQueryBuilderBundle:Element:queryBuilder.html.twig');
        $view->attributes['class'] = 'mb-element-queryBuilder';
        $form = $this->formFactory->createNamed(null, 'Mapbender\QueryBuilderBundle\Form\QueryType');
        $view->variables['form'] = $form->createView();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'css'   => array(
                '@MapbenderQueryBuilderBundle/Resources/styles/queryBuilder.element.scss',
            ),
            'js'    => array(
                '@MapbenderQueryBuilderBundle/Resources/public/queryBuilder.element.js',
            ),
            'trans' => array(
                'mb.query.builder.*',
            ),
        );
    }

    public function getClientConfiguration(Element $element)
    {
        $values = $element->getConfiguration() + $this->getDefaultConfiguration();

        foreach ($values['tableColumns'] as $i => $tableColumn) {
            switch ($tableColumn['title']) {
                case 'Title':
                    $values['tableColumns'][$i]['data'] = $values['titleFieldName'];
                    break;
                case 'Sort':
                    $values['tableColumns'][$i]['data'] = $values['orderByFieldName'];
                    break;
            }
        }
        return $values;
    }


    public static function updateEntityConfig(Element $entity)
    {
        $configuration = $entity->getConfiguration() ?: array();
        // @todo: warn or throw when encountering wrong option names
        $legacyAliases = array(
            'sqlField' => 'sqlFieldName',
            'orderByField' => 'orderByFieldName',
            'connectionField' => 'connectionFieldName',
            'titleField' => 'titleFieldName',
        );
        foreach ($legacyAliases as $before => $after) {
            if (array_key_exists($before, $configuration)) {
                $configuration[$after] = $configuration[$before];
                unset($configuration[$before]);
            }
        }
        $entity->setConfiguration($configuration);
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }
}
