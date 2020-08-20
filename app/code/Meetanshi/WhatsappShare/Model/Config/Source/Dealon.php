<?php
namespace Meetanshi\WhatsappShare\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;

class Dealon implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Disable')],
            ['value' => '1', 'label' => __('Special Price')],
            ['value' => '2', 'label' => __('Discount')]
        ];
    }
}

