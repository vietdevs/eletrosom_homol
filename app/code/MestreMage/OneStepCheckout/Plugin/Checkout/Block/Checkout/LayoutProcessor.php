<?php

namespace MestreMage\OneStepCheckout\Plugin\Checkout\Block\Checkout;

class LayoutProcessor
{

    public function afterProcess($subject, $jsLayout)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($objectManager->get('MestreMage\OneStepCheckout\Helper\Config')->isEnabledOneStep()) {

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['telephone']['sortOrder'] = 40;

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['company']['sortOrder'] = 42;




            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['fax'] = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.fax',
                    'customEntry' => null,
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/input',
                    'tooltip' => [
                        'description' => 'Para questões de entrega.',
                    ],
                ],
                'dataScope' => 'shippingAddress.fax',
                'label' =>  __('Numero do Celular'),
                'provider' => 'checkoutProvider',
                'sortOrder' => 41,
                'validation' => [
                    'required-entry' => true
                ],
                'options' => [],
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true,
            ];


        }
        return $jsLayout;
    }
}