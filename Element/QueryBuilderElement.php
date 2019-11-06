<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Mapbender\QueryBuilderBundle\Entity\QueryBuilderConfig;
use Mapbender\QueryBuilderBundle\Util\HtmlExportResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class QueryBuilderElement
 *
 * TODO:
 *  * Syntax check with EXPLAIN
 *  *
 *
 * @package Mapbender\DataSourceBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QueryBuilderElement extends Element
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
        $queryBuilderConfig = new QueryBuilderConfig();
        return $queryBuilderConfig->toArray();
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
                'MapbenderQueryBuilderBundle:Element:queryBuilder.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        return $this->getConfig()->toArray();
    }

    /**
     * @return QueryBuilderConfig
     */
    public function getConfig()
    {
        return new QueryBuilderConfig($this->entity->getConfiguration());
    }

    /**
     * @inheritdoc
     */
    public function handleHttpRequest(Request $requestService)
    {
        $action = $requestService->attributes->get('action');
        /** @var Registry $doctrine */
        $configuration   = $this->getConfig();

        switch ($action) {
            case 'select':
                $results   = array();
                $dataStore = $this->getDataStore($configuration);
                foreach ($dataStore->search(array()) as $dataItem) {
                    $results[] = $dataItem->toArray();
                }
                break;

            case 'export':
                if (!$configuration->allowExport) {
                    throw new AccessDeniedHttpException();
                }
                $results = $this->executeQuery(intval($requestService->request->get('id')));
                return new ExportResponse($results, 'export-list', ExportResponse::TYPE_XLS);

                break;

            case 'exportHtml':
                if (!$configuration->allowExport) {
                    throw new AccessDeniedHttpException();
                }
                $id = $requestService->query->get('id');
                $results = $this->executeQuery($id);
                $query   = $this->getQuery($id);
                $title   = $query->getAttribute($configuration->titleFieldName);
                $htmlExportResponse = new HtmlExportResponse($results, $title);
                die($htmlExportResponse->getContent());
                break;

            case 'execute':
                if (!$configuration->allowExecute) {
                    throw new AccessDeniedHttpException();
                }
                $results = $this->executeQuery(intval($requestService->request->get('id')));
                break;

            case 'save':
                if (!$configuration->allowCreate && !$configuration->allowSave) {
                    throw new AccessDeniedHttpException();
                }
                $dataStore = $this->getDataStore($configuration);
                $dataItem = $dataStore->save($requestService->request->get('item'));
                $results[] = $dataItem;
                break;

            case 'remove':
                if (!$configuration->allowRemove) {
                    throw new AccessDeniedHttpException();
                }
                $dataStore = $this->getDataStore($configuration);
                $results[] = $dataStore->remove($requestService->request->get('id'));
                break;

            case 'connections':
                $doctrine        = $this->container->get("doctrine");
                $connectionNames = $doctrine->getConnectionNames();
                $names           = array_keys($connectionNames);
                $results         = array_combine($names, $names);
                break;

            default:
                throw new NotFoundHttpException("No such action {$action}");
        }

        return new JsonResponse($results);
    }

    public function httpAction($action)
    {
        // implementation adapter for old Mapbender < 3.0.8-beta1
        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $this->handleHttpRequest($request);
    }

    /**
     * Execute query by ID
     *
     * @param $id
     * @return array
     */
    protected function executeQuery($id)
    {
        $configuration = $this->getConfig();
        $query         = $this->getQuery($id);
        $sql           = $query->getAttribute($configuration->sqlFieldName);
        $doctrine      = $this->container->get("doctrine");
        $connection    = $doctrine->getConnection($query->getAttribute($configuration->connectionFieldName));
        $results       = $connection->fetchAll($sql);
        return $results;
    }

    /**
     * @param $configuration
     * @return \Mapbender\DataSourceBundle\Component\DataStore
     */
    protected function getDataStore($configuration)
    {
        return $this->container->get("data.source")->get($configuration->source);
    }

    /**
     * Get SQL query by id
     *
     * @param int $id
     * @return DataItem
     */
    protected function getQuery($id)
    {
        return $this->getDataStore($this->getConfig())->getById($id);
    }

}
