<?php

namespace MestreMage\AutoCompleteZipCode\Block\Checkout;

class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface{

    protected $scopeConfig;

    protected $logger;

    protected $addressLineHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \MestreMage\AutoCompleteZipCode\Helper\Data $addressLineHelper
    ){
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->addressLineHelper = $addressLineHelper;
    }

    public function process($result){

        if(!$this->addressLineHelper->getModuleConfig('enabled')) {
            return $result;
        }

        if(isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form'])){
            // get billing address form at billing step
            $billingAddressForm = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form'];
            // move address form to shipping step
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form'] = $billingAddressForm;
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form']['sortOrder'] = 50000;
            // remove form from billing step
            unset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']);

        }


        if(isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form']['children']['form-fields'])){
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form']['children']['form-fields']['children']['postcode']['sortOrder'] = 50;
            $shippingFields = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form']['children']['form-fields']['children'];
            $shippingFields = $this->modifyStreetUiComponents($shippingFields);
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['billing-address-form']['children']['form-fields']['children'] = $shippingFields;

        }

        if(isset($result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset'])) {

            $shippingFields = $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'];

            $shippingFields = $this->modifyStreetUiComponents($shippingFields);

            $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'] = $shippingFields;

        }

        $result = $this->getBillingFormFields($result);

        return $result;
    }


    public function getBillingFormFields($result){

        $result['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['postcode']['sortOrder'] = 50;

        if(isset($result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['payments-list'])) {

            $paymentForms = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['payments-list']['children'];

            foreach ($paymentForms as $paymentMethodForm => $paymentMethodValue) {

                $paymentMethodCode = str_replace('-form', '', $paymentMethodForm);

                if (!isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentMethodCode . '-form'])) {
                    continue;
                }

                $billingFields = $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'][$paymentMethodCode . '-form']['children']['form-fields']['children'];

                $billingFields = $this->modifyStreetUiComponents($billingFields);

                $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'][$paymentMethodCode . '-form']['children']['form-fields']['children'] = $billingFields;

            }
        }

        return $result;

    }

    public function modifyStreetUiComponents($addressResult)
    {
        if(isset($addressResult['street']['label'])){
            unset($addressResult['street']['label']);
            unset($addressResult['street']['required']);
        }

        if(isset($addressResult['street'])){
            unset($addressResult['street']['children'][1]['validation']);
            unset($addressResult['street']['children'][2]['validation']);
        }

        if(isset($addressResult['street']['config']['template'])) {
            $addressResult['street']['config']['template'] = 'MestreMage_AutoCompleteZipCode/group/group';
        }

        if(isset($addressResult['street']['config']['additionalClasses'])) {
            $addressResult['street']['config']['additionalClasses'] = $addressResult['street']['config']['additionalClasses'] . ' mestremage-address-lines';
        }

        $lineCount = 0;

        while($lineCount < 4){

            $lineNumber = $lineCount+1;

            if(isset($addressResult['street']['children'][$lineCount])){
                $label = $this->addressLineHelper->getLineLabel($lineNumber);

                if ( $this->addressLineHelper->isLineEnabled($lineNumber)) {
                    $addressResult['street']['children'][$lineCount]['label'] = $label;
                    $addressResult['street']['children'][$lineCount]['additionalClasses'] = 'mestremage-address-line-one';
                    $addressResult['street']['children'][$lineCount]['validation'] = $this->addressLineHelper->getValidationClassesAsArrayForLine($lineNumber);
                    $addressResult['street']['children'][$lineCount]['required'] = ($this->addressLineHelper->isLineRequired($lineNumber)) ? True : False;
                }
            }

            $lineCount++;
        }

        return $addressResult;
    }


}
