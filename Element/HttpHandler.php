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

    public static function getDefaults()
    {
        return array(
            'source' => 'default',
            'allowRemove' => false,
            'allowEdit' => false,
            'allowExecute' => true,
            'allowSave' => false,
            'allowCreate' => false,
            'allowExport' => true,
        );
    }

    public function handleRequest(Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            case 'select':
                return $this->selectAction($element);
            case 'execute':
                // @todo: integrate "allowExecute" check
                return $this->executeAction($element, $request);
            case 'remove':
                // @todo: integrate "allowRemove" check
                return $this->removeAction($element, $request);
            case 'export':
                // @todo: integrate "allowExport" check
                return $this->exportAction($element, $request);
            case 'exportHtml':
                // @todo: integrate "allowExport" check
                return $this->exportHtmlAction($element, $request);
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
        $query = $this->getDataStore($element)->getById($request->query->get('id'));
        // @todo: throw not found as appropriate
        return new JsonResponse($this->executeQuery($element, $query));
    }

    protected function exportAction(Element $element, Request $request)
    {
        /** @todo: fix post data usage, should be query parameter */
        $query = $this->getDataStore($element)->getById($request->request->get('id'));
        $rows = $this->executeQuery($element, $query);
        // @todo: throw not found as appropriate
        return new ExportResponse($rows, 'export-list', ExportResponse::TYPE_XLS);
    }

    protected function exportHtmlAction(Element $element, Request $request)
    {
        $titleFieldName = ($element->getConfiguration() + QueryBuilderElement::getDefaultConfiguration())['titleFieldName'];
        $query = $this->getDataStore($element)->getById($request->query->get('id'));
        // @todo: throw not found as appropriate
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
}
