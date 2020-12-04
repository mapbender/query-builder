<?php
namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Mapbender\QueryBuilderBundle\Util\HtmlExportResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        return array(
            'source' => 'default',
            'allowRemove' => false,
            'allowExecute' => true,
            'allowSave' => false,
            'allowExport' => true,
            'allowHtmlExport' => true,
            'allowSearch' => false,
            'sqlFieldName' => 'sql_definition',
            'orderByFieldName' => 'anzeigen_reihenfolge',
            'connectionFieldName' => 'connection_name',
            'titleFieldName' => 'name',
            'publicFieldName' => 'anzeigen',
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
                '../../vendor/mapbender/vis-ui.js/src/js/jquery.form.generator.js',
                '../../vendor/mapbender/vis-ui.js/src/js/utils/fn.formData.js',
                '../../vendor/mapbender/vis-ui.js/src/js/elements/date.selector.js',    // only for legacy browsers
                '../../vendor/mapbender/vis-ui.js/src/js/elements/popup.dialog.js',
                '../../vendor/mapbender/vis-ui.js/src/js/elements/data.result-table.js',
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
        $values = $this->entity->getConfiguration() + $this->getDefaultConfiguration();
        $values = $this->fixLegacyOptions($values);

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

    /**
     * @param array $configuration
     * @return array
     */
    protected static function fixLegacyOptions(array $configuration)
    {
        // @todo: warn or throw when encountering wrong option names
        $legacyAliases = array(
            'sqlField' => 'sqlFieldName',
            'orderByField' => 'orderByFieldName',
            'connectionField' => 'connectionFieldName',
            'titleField' => 'titleFieldName',
            'publicField' => 'publicFieldName',
        );
        foreach ($legacyAliases as $before => $after) {
            if (array_key_exists($before, $configuration)) {
                $configuration[$after] = $configuration[$before];
                unset($configuration[$before]);
            }
        }
        return $configuration;
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
                $results   = array();
                $dataStore = $this->getDataStore($configuration['source']);
                foreach ($dataStore->search(array()) as $dataItem) {
                    $results[] = $dataItem->toArray();
                }
                break;

            case 'export':
                if (!$configuration['allowExport']) {
                    throw new AccessDeniedHttpException();
                }
                $results = $this->executeQuery(intval($requestService->request->get('id')));
                return new ExportResponse($results, 'export-list', ExportResponse::TYPE_XLS);

                break;

            case 'exportHtml':
                if (!$configuration['allowExport']) {
                    throw new AccessDeniedHttpException();
                }
                $id = $requestService->query->get('id');
                $results = $this->executeQuery($id);
                $query = $this->getQuery($id, $configuration['source']);
                $configuration = $this->fixLegacyOptions($configuration);
                $title   = $query->getAttribute($configuration['titleFieldName']);
                $content = $this->container->get('templating')->render('@MapbenderQueryBuilder/export.html.twig', array(
                    'title' => $title,
                    'rows' => $results,
                ));
                return new Response($content);

            case 'execute':
                if (!$configuration['allowExecute']) {
                    throw new AccessDeniedHttpException();
                }
                $results = $this->executeQuery(intval($requestService->request->get('id')));
                break;

            case 'save':
                if (!$configuration['allowSave']) {
                    throw new AccessDeniedHttpException();
                }
                $dataStore = $this->getDataStore($configuration['source']);
                $dataItem = $dataStore->save($requestService->request->get('item'));
                $results[] = $dataItem;
                break;

            case 'remove':
                if (!$configuration['allowRemove']) {
                    throw new AccessDeniedHttpException();
                }
                $dataStore = $this->getDataStore($configuration['source']);
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
        $configuration = $this->fixLegacyOptions($this->entity->getConfiguration() + $this->getDefaultConfiguration());
        $query = $this->getQuery($id, $configuration['source']);
        $sql = $query->getAttribute($configuration['sqlFieldName']);
        $doctrine      = $this->container->get("doctrine");
        $connection = $doctrine->getConnection($query->getAttribute($configuration['connectionFieldName']));
        $results       = $connection->fetchAll($sql);
        return $results;
    }

    /**
     * @param string $name
     * @return \Mapbender\DataSourceBundle\Component\DataStore
     */
    protected function getDataStore($name)
    {
        if (!is_string($name)) {
            // @todo: warn or throw
            $values = (array)$name;
            $name = $values['source'];
        }
        return $this->container->get("data.source")->get($name);
    }

    /**
     * Get SQL query by id
     *
     * @param int $id
     * @param string $dataStoreName
     * @return DataItem
     */
    protected function getQuery($id, $dataStoreName)
    {
        return $this->getDataStore($dataStoreName)->getById($id);
    }

}
