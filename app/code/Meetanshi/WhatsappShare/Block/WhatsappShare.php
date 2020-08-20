<?php

namespace Meetanshi\WhatsappShare\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Meetanshi\WhatsappShare\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\Helper\Data as priceHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

class WhatsappShare extends Template
{
	protected $registry;
    protected $helper;
    protected $priceHelper;
    protected $category;
    protected $product;
    protected $storeManager;

    public function __construct(
		Context $context, 
		Registry $registry,
        priceHelper $priceHelper,
        Data $helper,
		Category $category,
		Product $product,
        StoreManagerInterface $storeManager,
		array $data = []
	) {
        $this->helper    = $helper;
        $this->priceHelper = $priceHelper;
		$this->registry = $registry;
		$this->category = $category;
		$this->product = $product;
		$this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getCurrentStoreId(){
        try {
            return $this->storeManager->getStore()->getId();
        }catch (\Exception $e){
            ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
        }
    }
    public function getCategoryEnable($ids){
        $flag =false;
        foreach ($ids as $id){
        $category = $this->category->load($id);
        if($category->getData('whatsapp_share')):
            $flag = true;
            break;
        endif;
        }
        if($flag == true):
            return true;
        endif;
    }
    public function getProductEnable($id){
        $product = $this->product->load($id);
        if($product->getData('whatsapp_share')):
            return true;
        else:
            return false;
        endif;
    }
    public function getMessage($currentProduct)
    {
        try{
            $whatsappData = $this->helper->getConfigValue($this->getCurrentStoreId());
         // $currentProduct = $this->product->load($currentProduct->getId());
            $productUrl = trim($currentProduct->getProductUrl());
            $productName  = $currentProduct->getName();
            if( $currentProduct->getShortDescription() !=''):
                $productDesc1 = $currentProduct->getShortDescription();
            else:
                $productDesc1 = $currentProduct->getDescription();
            endif;
            $productDesc  = strip_tags($productDesc1);
            $productDesc = substr($productDesc,0,250);

            $productType = $currentProduct->getTypeId();
            if($productType === "grouped"):
                $associatedProducts = $currentProduct->getTypeInstance(true)->getAssociatedProducts($currentProduct);
                $productCount=0;
                foreach ($associatedProducts as $item){
                    $id = $item->getId();
                    $associatedItem = $this->product->load($id);
                    $prices = [];
                    $specialPrices = [];
                    $productCount++;
                    if ($associatedItem->getPrice() && $associatedItem->getSpecialPrice()):
                        $prices[] = $associatedItem->getPrice();
                        $specialPrices[] = $associatedItem->getSpecialPrice();
                    else:
                        $prices[] = $associatedItem->getPrice();
                    endif;
                }
                sort($prices);
                if(isset($specialPrices) && $productCount == sizeof($specialPrices)):
                    sort($specialPrices);
                    $price = $prices[0];
                    $specialPrice = $specialPrices[0];
                else:
                    $specialPrice = '';
                    $price = $prices[0];
                endif;
            else:
                $price      = $currentProduct->getFinalPrice();
                $specialPrice = $currentProduct->getSpecialPrice();
            endif;
            $price = $this->priceHelper->currency($price,true,false);
            $specialPrice = $this->priceHelper->currency($specialPrice,true,false);
            if ($whatsappData['utm_enable']) {
                $productUrl = $this->getUtmTrackUrl($productUrl);
            }
            if ($whatsappData['bitly_enable']) {
                $productUrl = $this->getBitlyUrl($productUrl);
            }
            $dataText = "";
            $dataText .= $productUrl."\r\n\r\n";
            $dataText .= $whatsappData['custom_message']."\r\n\r\n";
            if($whatsappData['product_name']):
                $dataText .= $productName."\r\n\r\n";
            endif;
            if($whatsappData['product_description']):
                $dataText .= $productDesc."\r\n\r\n";
            endif;
            if($whatsappData['product_price']):
                if($productType === "grouped"):
                    $dataText .= 'Starting at : '.$price."\r\n\r\n";
                else:
                    $dataText .= $price."\r\n\r\n";

                endif;
            endif;
            if($specialPrice != '' && ($whatsappData['deal_on']=='1')):
                $specialPriceMessage = $whatsappData['special_price_message'];
                $message = str_split($specialPriceMessage);
                $startPosition = 0;
                $length = 0;
                foreach($message as $key => $letter){
                    if($letter == '{'):
                        $startPosition = $key;
                        break;
                    endif;
                }
                foreach($message as $key => $letter){
                    if($letter == '}'):
                        $length = ($key + 3) - $startPosition;
                        break;
                    endif;
                }
                if(($startPosition) && ($length)):
                    $specialPriceMessage = substr_replace($specialPriceMessage,$specialPrice,$startPosition,$length);
                endif;
                $dataText .= $specialPriceMessage."\r\n\r\n";
            elseif($whatsappData['deal_on'] == '2'):
                $dataText .= $whatsappData['discount_message']."\r\n\r\n";
            endif;
            return str_replace('+',' ',urlencode($dataText));
        }catch (\Exception $e){
            $this->helper->printLog($e->getMessage());
            return "";
        }
    }
    public function getUtmTrackUrl($productUrl){
        $whatsappData = $this->helper->getConfigValue($this->getCurrentStoreId());
        $productUrl .= "?utm_source=".$whatsappData['campaign_source'];
        $utmMedium = $whatsappData['campaign_medium'];
        $utmCampaign = $whatsappData['campaign_name'];
        if($utmMedium != ''):
            $productUrl .= "&utm_medium=".$utmMedium;
        endif;
        if($utmCampaign != ''):
            $productUrl .= "&utm_campaign=".$utmCampaign;
        endif;
        return $productUrl;
    }
    public function getBitlyUrl($productUrl){
        $whatsappData = $this->helper->getConfigValue($this->getCurrentStoreId());
        $query = array(
            "version" => "2.0.1",
            "longUrl" => $productUrl,
            "login" => $whatsappData['login_name'],
            "apiKey" => $whatsappData['api_key']
        );
        $query = http_build_query($query);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.bitly.com/v3/shorten?".$query);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);
        if( $response->status_txt == "OK") {
            return $response->data->url;
        } else {
            return null;
        }
    }
	public function getCurrentProduct()
    {
		return $this->registry->registry('product');
    }
    public function getConfigValue($storeId = null)
    {
       return $this->helper->getConfigValue($storeId);
    }
}
