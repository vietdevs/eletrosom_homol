<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SourceType
 *
 * @package Magmodules\GoogleShopping\Model\System\Config\Source
 */
class SourceType implements ArrayInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => 'static', 'label' => __('Static Values')],
                ['value' => 'attribute', 'label' => __('Use Attribute')]
            ];
        }

        return $this->options;
    }
}
