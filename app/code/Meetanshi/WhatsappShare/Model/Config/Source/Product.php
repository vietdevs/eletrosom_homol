<?php
namespace Meetanshi\WhatsappShare\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Product implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Globally')],
            ['value' => '1', 'label' => __('Category Specific')],
            ['value' => '2', 'label' => __('Product Specific')]
        ];
    }
}

