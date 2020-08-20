<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model\Indexer\Location;

use Amasty\Storelocator\Model\Indexer\AbstractIndexer;
use Magento\Framework\Exception\LocalizedException;

class LocationIndexer extends AbstractIndexer
{
    /**
     * @param int[] $ids
     *
     * @throws LocalizedException
     */
    protected function doExecuteList($ids)
    {
        $this->indexBuilder->reindexByIds($ids);
    }

    /**
     * @param int $id
     *
     * @throws LocalizedException
     */
    protected function doExecuteRow($id)
    {
        $this->indexBuilder->reindexById($id);
    }
}
