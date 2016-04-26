<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Mapbender\DataSourceBundle\Entity\QueryBuilderConfig;
use Mapbender\DataSourceBundle\Util\HtmlExportResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
class QueryBuilderElement extends BaseElement
{
    /**
     * The constructor.
     *
     * @param Application        $application The application object
     * @param ContainerInterface $container   The container object
     * @param Element            $entity
     */
    public function __construct(Application $application, ContainerInterface $container, Element $entity)
    {
        parent::__construct($application, $container, $entity);
    }

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Query builder";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
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
    static public function getTags()
    {
        return array();
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
        return 'QueryBuilderBundle:ElementAdmin:queryBuilder.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return /** @lang XHTML */
            '<div
                id="' . $this->getId() . '"
                class="mb-element mb-element-queryBuilder modal-body"
                title="' . _($this->getTitle()) . '"></div>';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'css'   => array(
                '@MapbenderQueryBuilderBundle/Resources/styles/queryBuilder.element.scss'
            ),
            'js'    => array(
                '@MapbenderQueryBuilderBundle/Resources/public/queryBuilder.element.js'
            ),
            'trans' => array(
                'MapbenderQueryBuilderBundle:Element:queryBuilder.json.twig'
            )
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
        return new QueryBuilderConfig(parent::getConfiguration());
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var DataItem $dataItem */
        /** @var $requestService Request */
        /** @var Registry $doctrine */
        /** @var Connection $connection */
        $configuration   = $this->getConfig();
        $requestService  = $this->container->get('request');
        $defaultCriteria = array();
        $payload         = json_decode($requestService->getContent(), true);
        $request         = $requestService->getContent() ? array_merge($defaultCriteria, $payload ? $payload : $_REQUEST) : array();

        switch ($action) {
            case 'select':
                $results   = array();
                $dataStore = $this->getDataStore($configuration);
                foreach ($dataStore->search($request) as $dataItem) {
                    $results[] = $dataItem->toArray();
                }
                break;

            case 'export':
                if (!$configuration->allowExport) {
                    throw new \Error("Permission denied!");
                }

                $results = $this->executeQuery(intval($request["id"]));
                return new ExportResponse($results, 'export-list', ExportResponse::TYPE_XLS);

                break;

            case 'exportHtml':
                if (!$configuration->allowExport) {
                    throw new \Error("Permission denied!");
                }
                $id      = intval($_REQUEST["id"]);
                $results = $this->executeQuery($id);
                $query   = $this->getQuery($id);
                $title   = $query->getAttribute($configuration->titleFieldName);
                $htmlExportResponse = new HtmlExportResponse($results, $title);
                die($htmlExportResponse->getContent());
                break;

            case 'execute':

                if (!$configuration->allowExecute) {
                    throw new \Error("Permission denied!");
                }
                $results = $this->executeQuery(intval($request["id"]));
                break;

            case 'save':
                if (!$configuration->allowCreate && !$configuration->allowSave) {
                    throw new \Error("Permission denied!");
                }
                $dataStore = $this->getDataStore($configuration);
                $dataItem = $dataStore->save($request["item"]);
                if (!$dataItem) {
                    throw new \Error("Can't get object by new ID. Wrong sequence setup?");
                }
                $results[] = $dataItem;
                break;

            case 'remove':
                if (!$configuration->allowRemove) {
                    throw new \Error("Permission denied!");
                }
                $dataStore = $this->getDataStore($configuration);
                $results[] = $dataStore->remove($request["id"]);
                break;

            case 'connections':
                $doctrine        = $this->container->get("doctrine");
                $connectionNames = $doctrine->getConnectionNames();
                $names           = array_keys($connectionNames);
                $results         = array_combine($names, $names);
                break;

            default:
                $results = array(
                    array('errors' => array(
                        array('message' => $action . " not defined!")
                    ))
                );
        }

        return new JsonResponse($results);
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