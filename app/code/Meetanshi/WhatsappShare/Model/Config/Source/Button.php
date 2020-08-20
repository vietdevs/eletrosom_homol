<?php
namespace Meetanshi\WhatsappShare\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;
class Button implements ArrayInterface
{
    public function toOptionArray()
    {
        return [['value' => '1', 'label' => __('Icon')],['value' => '2', 'label' => __('Image')]];
    }
}