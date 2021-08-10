<?php


namespace Mapbender\QueryBuilderBundle\Element;


use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Component\RepositoryRegistry;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HttpHandler
{
    /** @var RegistryInterface */
    protected $doctrineRegistry;
    /** @var EngineInterface */
    protected $templateEngine;
    /** @var RepositoryRegistry */
    protected $registry;

    public function __construct(RegistryInterface $doctrineRegistry,
                                EngineInterface $templateEngine,
                                RepositoryRegistry $registry)
    {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->templateEngine = $templateEngine;
        $this->registry = $registry;
    }

    public function handleRequest(Element $element, Request $request)
    {
        if (!$this->checkAccess($element, $request)) {
            throw new AccessDeniedHttpException();
        }

        switch ($request->attributes->get('action')) {
            case 'select':
                return $this->selectAction($element);
            case 'execute':
                return $this->executeAction($element, $request);
            case 'remove':
                return $this->removeAction($element, $request);
            case 'export':
                return $this->exportAction($element, $request);
            case 'exportHtml':
                return $this->exportHtmlAction($element, $request);
            case 'save':
                return $this->saveAction($element, $request);
            default:
                throw new NotFoundHttpException();
        }
    }

    protected function selectAction(Element $element)
    {
        $results = array();
        foreach ($this->getDataStore($element)->search() as $item) {
            $results[] = $item->toArray();
        }
        return new JsonResponse($results);
    }

    protected function executeAction(Element $element, Request $request)
    {
        $query = $this->requireQuery($element, $request->query->get('id'));
        return new JsonResponse($this->executeQuery($element, $query));
    }

    protected function saveAction(Element $element, Request $request)
    {
        $values = $request->request->get('item');
        $item = $this->getDataStore($element)->save($values);
        return new JsonResponse($item->toArray());
    }

    protected function exportAction(Element $element, Request $request)
    {
        /** @todo: fix post data usage, should be query parameter */
        $query = $this->requireQuery($element, $request->request->get('id'));
        $rows = $this->executeQuery($element, $query);
        return new ExportResponse($rows, 'export-list', ExportResponse::TYPE_XLS);
    }

    protected function exportHtmlAction(Element $element, Request $request)
    {
        $titleFieldName = ($element->getConfiguration() + QueryBuilderElement::getDefaultConfiguration())['titleFieldName'];
        $query = $this->requireQuery($element, $request->query->get('id'));
        return $this->templateEngine->renderResponse('@MapbenderQueryBuilder/export.html.twig', array(
            'title' => $query->getAttribute($titleFieldName),
            'rows' => $this->executeQuery($element, $query),
        ));
    }

    protected function removeAction(Element $element, Request $request)
    {
        $id = $request->request->get('id');
        $deleted = $this->getDataStore($element)->remove($id);
        // @todo: check if this response form is required
        return new JsonResponse(array($deleted));
    }

    /**
     * @param Element $element
     * @param DataItem $query
     * @return array[][]
     */
    protected function executeQuery(Element $element, DataItem $query)
    {
        $config = $element->getConfiguration() + QueryBuilderElement::getDefaultConfiguration();
        $sql = $query->getAttribute($config['sqlFieldName']);
        $connectionName = $query->getAttribute($config['connectionFieldName']);
        return $this->doctrineRegistry->getConnection($connectionName)->fetchAll($sql);
    }


    // @todo: make protected
    public function getDataStore(Element $element)
    {
        return $this->registry->dataStoreFactory($this->getDatastoreConfig($element));
    }

    protected function getDatastoreConfig(Element $element)
    {
        $config = $element->getConfiguration();
        if (!empty($config['source'])) {
            $dsName = $config['source'];
        } else {
            $dsName = QueryBuilderElement::getDefaultConfiguration()['source'];
        }
        return $this->registry->getDataStoreDeclarations()[$dsName];
    }

    /**
     * @param Element $element
     * @param Request $request
     * @return bool
     */
    protected function checkAccess(Element $element, Request $request)
    {
        $config = $element->getConfiguration();
        switch ($request->attributes->get('action')) {
            default:
                return true;
            case 'execute':
                return $config['allowExecute'];
            case 'save':
                return $config['allowCreate'] || $config['allowSave'];
            case 'remove':
                return $config['allowRemove'];
            case 'export':
            case 'exportHtml':
                return $config['allowExport'];
        }
    }

    /**
     * @param Element $element
     * @param mixed $id
     * @return DataItem
     * @throws NotFoundHttpException
     */
    protected function requireQuery(Element $element, $id)
    {
        $query = $this->getDataStore($element)->getById($id);
        if ($query) {
            return $query;
        } else {
            throw new NotFoundHttpException();
        }
    }
}
