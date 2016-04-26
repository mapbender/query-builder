<?php
namespace Mapbender\QueryBuilderBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * Mapbender Query Builder Bundle.
 *
 * @author Andriy Oblivantsev
 */
class MapbenderQueryBuilderBundle extends MapbenderBundle
{
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
