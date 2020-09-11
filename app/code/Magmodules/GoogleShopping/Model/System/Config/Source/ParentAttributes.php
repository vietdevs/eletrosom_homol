<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magmodules\GoogleShopping\Helper\Source as SourceHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

/**
 * Class ParentAttributes
 *
 * @package Magmodules\GoogleShopping\Model\System\Config\Source
 */
class ParentAttributes implements ArrayInterface
{

    /**
     * @var SourceHelper
     */
    private $sourceHelper;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * ParentAttributes constructor.
     *
     * @param Http         $request
     * @param Emulation    $appEmulation
     * @param SourceHelper $sourceHelper
     */
    public function __construct(
        Http $request,
        Emulation $appEmulation,
        SourceHelper $sourceHelper
    ) {
        $this->sourceHelper = $sourceHelper;
        $this->appEmulation = $appEmulation;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = [];
        $storeId = $this->request->getParam('store');
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $source = $this->sourceHelper->getAttributes('parent');
        $this->appEmulation->stopEnvironmentEmulation();

        foreach ($source as $key => $attribute) {
            if (empty($attribute['parent_selection_disabled'])) {
                $label = str_replace('_', ' ', $key);
                $attributes[] = [
                    'value' => $key,
                    'label' => ucwords($label),
                ];
            }
        }

        return $attributes;
    }
}
