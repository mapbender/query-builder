<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        return HttpHandler::getDefaults() + array(
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
    public function handleHttpRequest(Request $requestService)
    {
        $action = $requestService->attributes->get('action');
        /** @var Registry $doctrine */
        $configuration = $this->entity->getConfiguration() + $this->getDefaultConfiguration();

        switch ($action) {
            case 'select':
                return $this->getHttpHandler()->handleRequest($this->entity, $requestService);
            case 'export':
            case 'exportHtml':
                if (!$configuration['allowExport']) {
                    throw new AccessDeniedHttpException();
                }
                return $this->getHttpHandler()->handleRequest($this->entity, $requestService);
            case 'execute':
                if (!$configuration['allowExecute']) {
                    throw new AccessDeniedHttpException();
                }
                return $this->getHttpHandler()->handleRequest($this->entity, $requestService);
            case 'save':
                if (!$configuration['allowCreate'] && !$configuration['allowSave']) {
                    throw new AccessDeniedHttpException();
                }
                $dataStore = $this->getHttpHandler()->getDataStore($this->entity);
                $dataItem = $dataStore->save($requestService->request->get('item'));
                $results = $dataItem->toArray();
                break;

            case 'remove':
                if (!$configuration['allowRemove']) {
                    throw new AccessDeniedHttpException();
                }
                return $this->getHttpHandler()->handleRequest($this->entity, $requestService);
            default:
                throw new NotFoundHttpException("No such action {$action}");
        }

        return new JsonResponse($results);
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

    /**
     * @return HttpHandler
     */
    private function getHttpHandler()
    {
        return $this->container->get('mb.querybuilder.http_handler');
    }

}
