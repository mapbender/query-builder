<?php
namespace Mapbender\QueryBuilderBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\DataSourceBundle\MapbenderDataSourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Mapbender Query Builder Bundle.
 *
 * @author Andriy Oblivantsev
 */
class MapbenderQueryBuilderBundle extends MapbenderBundle
{
    public function build(ContainerBuilder $container)
    {
        // Ensure DataSourceBundle services exist (independent of kernel registration)
        $dsBundle = new MapbenderDataSourceBundle();
        $dsBundle->build($container);

        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\QueryBuilderBundle\Element\QueryBuilderElement'
        );
    }
}
