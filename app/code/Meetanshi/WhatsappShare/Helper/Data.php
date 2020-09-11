<?php
namespace Meetanshi\WhatsappShare\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const SHARE_ENABLE = 'whatsappshare/configuration/enable';
    const PRODUCT_WISE = 'whatsappshare/configuration/product_wise';
    const CATEGORY_WISE = 'whatsappshare/configuration/category_wise';
    const BUTTON_TYPE = 'whatsappshare/settings/button_type';
    const CUSTOM_MESSAGE = 'whatsappshare/settings/custom_message';
    const PRODUCT_NAME = 'whatsappshare/settings/product_name';
    const PRODUCT_DESCRIPTION = 'whatsappshare/settings/product_description';
    const PRODUCT_PRICE = 'whatsappshare/settings/product_price';
    const DEAL_ON = 'whatsappshare/settings/deal_on';
    const SPECIAL_PRICE_MESSAGE = 'whatsappshare/settings/special_price_message';
    const DISCOUNT_MESSAGE = 'whatsappshare/settings/discount_message';
    const UTM_ENABLE = 'whatsappshare/utm/enable';
    const CAMPAIGN_SOURCE = 'whatsappshare/utm/campaign_source';
    const CAMPAIGN_MEDIUM = 'whatsappshare/utm/campaign_medium';
    const CAMPAIGN_NAME = 'whatsappshare/utm/campaign_name';
    const BITLY_ENABLE = 'whatsappshare/bitly/enable';
    const LOGIN_NAME = 'whatsappshare/bitly/login_name';
    const API_KEY = 'whatsappshare/bitly/api_key';

    public function getConfigValue($storeId = null)
    {
        $data[]="";
        $data['share_enable'] = $this->scopeConfig->getValue(static::SHARE_ENABLE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['product_wise'] = $this->scopeConfig->getValue(static::PRODUCT_WISE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['category_wise'] = $this->scopeConfig->getValue(static::CATEGORY_WISE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['button_type'] = $this->scopeConfig->getValue(static::BUTTON_TYPE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['custom_message'] = $this->scopeConfig->getValue(static::CUSTOM_MESSAGE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['product_name'] = $this->scopeConfig->getValue(static::PRODUCT_NAME,ScopeInterface::SCOPE_STORE,$storeId);
        $data['product_description'] = $this->scopeConfig->getValue(static::PRODUCT_DESCRIPTION,ScopeInterface::SCOPE_STORE,$storeId);
        $data['product_price'] = $this->scopeConfig->getValue(static::PRODUCT_PRICE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['deal_on'] = $this->scopeConfig->getValue(static::DEAL_ON,ScopeInterface::SCOPE_STORE,$storeId);
        $data['special_price_message'] = $this->scopeConfig->getValue(static::SPECIAL_PRICE_MESSAGE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['discount_message'] = $this->scopeConfig->getValue(static::DISCOUNT_MESSAGE,ScopeInterface::SCOPE_STORE,$storeId);
        $data['utm_enable'] = $this->scopeConfig->getValue(static::UTM_ENABLE,ScopeInterface::SCOPE_STORE,$storeId); $data['campaign_source'] = $this->scopeConfig->getValue(static::CAMPAIGN_SOURCE,ScopeInterface::SCOPE_STORE,$storeId); $data['campaign_medium'] = $this->scopeConfig->getValue(static::CAMPAIGN_MEDIUM,ScopeInterface::SCOPE_STORE,$storeId); $data['campaign_name'] = $this->scopeConfig->getValue(static::CAMPAIGN_NAME,ScopeInterface::SCOPE_STORE,$storeId); $data['bitly_enable'] = $this->scopeConfig->getValue(static::BITLY_ENABLE,ScopeInterface::SCOPE_STORE,$storeId); $data['login_name'] = $this->scopeConfig->getValue(static::LOGIN_NAME,ScopeInterface::SCOPE_STORE,$storeId); $data['api_key'] = $this->scopeConfig->getValue(static::API_KEY,ScopeInterface::SCOPE_STORE,$storeId);
        return $data;
    }
    public function printLog($log)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/whatsapp.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        if (is_array($log)):
            $logger->info(print_r($log,true));
        else:
            $logger->info($log);
        endif;
    }
}
