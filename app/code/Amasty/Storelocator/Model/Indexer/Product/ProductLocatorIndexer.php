<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model\Indexer\Product;

use Amasty\Storelocator\Model\Indexer\AbstractIndexer;
use Magento\Catalog\Model\Product;

class ProductLocatorIndexer extends AbstractIndexer
{
    /**
     * @param int[] $ids
     */
    protected function doExecuteList($ids)
    {
        $this->indexBuilder->reindexByProductIds(array_unique($ids));
        $this->cacheContext->registerEntities(Product::CACHE_TAG, $ids);
    }

    /**
     * @param int $id
     */
    protected function doExecuteRow($id)
    {
        $this->indexBuilder->reindexByProductId($id);
    }
}
