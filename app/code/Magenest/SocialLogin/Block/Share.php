<?php
namespace Magenest\SocialLogin\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\Product;

class Share extends Template
{
    /**
     * @var \Magenest\SocialLogin\Model\Share\Share
     */
    protected $_clientShare;

    /**
     * @var Product
     */
    protected $_product = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;


    /**
     * @param Context $context
     * @param \Magenest\SocialLogin\Model\Share\Share $clientShare
     */
    public function __construct(
        Context $context,
        \Magenest\SocialLogin\Model\Share\Share $clientShare,
        \Magento\Framework\Registry $registry
    ) {
        $this->_clientShare = $clientShare;
        $this->_coreRegistry = $registry;
        $this->getProduct();
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isShareEnabled()
    {
        return $this->_clientShare->isEnabled();
    }

    public function getSocialShare()
    {
        return $this->_clientShare->getSocialShare();
    }

    public function getDescription()
    {
        return $this->_product->getName();
    }

    public function getShareBaseUrl()
    {
        return  $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getMedia()
    {
        $url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $url .= 'catalog/product'.$this->_product->getImage();
        if ($this->_product->getImage()) {
            return $url;
        }
        return  $this->getViewFileUrl('Magento_Catalog::images/product/placeholder/thumbnail.jpg');
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }
}
