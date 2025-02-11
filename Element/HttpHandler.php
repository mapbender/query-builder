<?php


namespace Mapbender\QueryBuilderBundle\Element;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Component\Factory\DataStoreFactory;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Mapbender\QueryBuilderBundle\Permission\QueryBuilderPermissionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig;

class HttpHandler implements ElementHttpHandlerInterface
{
    public function __construct(
        protected ConnectionRegistry            $doctrineRegistry,
        protected Twig\Environment              $templateEngine,
        protected DataStoreFactory              $dataStoreFactory,
        protected AuthorizationCheckerInterface $security,
    )
    {
    }

    public function handleRequest(Element $element, Request $request)
    {
        if (!$this->checkAccess($element, $request)) {
            throw new AccessDeniedHttpException();
        }

        return match ($request->attributes->get('action')) {
            'select' => $this->selectAction($element),
            'execute' => $this->executeAction($element, $request),
            'remove' => $this->removeAction($element, $request),
            'export' => $this->exportAction($element, $request),
            'exportHtml' => $this->exportHtmlAction($element, $request),
            'save' => $this->saveAction($element, $request),
            default => throw new NotFoundHttpException(),
        };
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
        $query = $this->requireQuery($element, $request->request->get('id'));
        $rows = $this->executeQuery($element, $query);
        $exportFormat = match ($this->getSafeConfiguration($element)['export_format']) {
            'csv' => ExportResponse::TYPE_CSV,
            'xls' => ExportResponse::TYPE_XLS,
            default => ExportResponse::TYPE_XLSX,
        };
        return new ExportResponse($rows, 'export-list', $exportFormat);
    }

    protected function exportHtmlAction(Element $element, Request $request)
    {
        $titleFieldName = $this->getSafeConfiguration($element)['titleFieldName'];
        $query = $this->requireQuery($element, $request->query->get('id'));
        $content = $this->templateEngine->render('@MapbenderQueryBuilder/export.html.twig', array(
            'title' => $query->getAttribute($titleFieldName),
            'rows' => $this->executeQuery($element, $query),
        ));
        return new Response($content);
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
        $config = $this->getSafeConfiguration($element);
        $sql = $query->getAttribute($config['sqlFieldName']);
        $connectionName = $query->getAttribute($config['connectionFieldName']);
        if (!$connectionName) {
            $connectionName = $config['connection'];
        }
        /** @var Connection $connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        return $connection->fetchAllAssociative($sql);
    }

    protected function getDataStore(Element $element)
    {
        return $this->dataStoreFactory->fromConfig($this->getSafeConfiguration($element));
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
                // TODO: genauer checken
                return ($config['allowCreate'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_CREATE))
                    || ($config['allowEdit'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_EDIT));
            case 'remove':
                return $config['allowRemove'] && $this->security->isGranted(QueryBuilderPermissionProvider::PERMISSION_DELETE);
            case 'export':
                return $config['allowFileExport'];
            case 'exportHtml':
                return $config['allowHtmlExport'];
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

    private function getSafeConfiguration(Element $element)
    {
        return $element->getConfiguration()['configuration'] + QueryBuilderElement::getYamlConfigurationDefaults();
    }
}
