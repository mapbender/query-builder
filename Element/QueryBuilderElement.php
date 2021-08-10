<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QueryBuilderElement extends Element implements ConfigMigrationInterface
{

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
    public function getWidgetName()
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

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderQueryBuilderBundle:Element:queryBuilder{$suffix}";
    }

    public function getFrontendTemplateVars()
    {
        $config = $this->entity->getConfiguration() + $this->getDefaultConfiguration();
        return array(
            'id' => $this->entity->getId(),
            'configuration' => $config,
            'connectionNames' => $this->getConnectionNames(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
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

    public function getPublicConfiguration()
    {
        $values = $this->entity->getConfiguration() + $this->getDefaultConfiguration();

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


    public static function updateEntityConfig(Entity\Element $entity)
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

    /**
     * @inheritdoc
     */
    public function handleHttpRequest(Request $request)
    {
        /** @var HttpHandler $handler */
        $handler = $this->container->get('mb.querybuilder.http_handler');
        // Ensure config is merged with defaults
        /** @ŧodo 1.2: remove config merging (no longer needed on MB >= 3.2.6) */
        $this->entity->setConfiguration($this->entity->getConfiguration() + $this->getDefaultConfiguration());
        return $handler->handleRequest($this->entity, $request);
    }

    /**
     * @return string[]
     */
    protected function getConnectionNames()
    {
        /** @var Registry $registry */
        $registry = $this->container->get("doctrine");
        return array_keys($registry->getConnectionNames());
    }
}
