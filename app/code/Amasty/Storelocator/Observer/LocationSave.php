<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Observer;

use Amasty\Storelocator\Model\Indexer\Location\IndexBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class LocationSave execute when Save Location
 */
class LocationSave implements ObserverInterface
{
    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    public function __construct(IndexBuilder $indexBuilder)
    {
        $this->indexBuilder = $indexBuilder;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($locationModel = $observer->getEvent()->getDataObject()) {
            $this->indexBuilder->reindexById($locationModel->getId());
        }
    }
}
