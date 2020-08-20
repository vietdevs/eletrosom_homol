<?php

//require '/var/www/html/app/bootstrap.php';
//
//// PATH DA APLICAÇÃO
//use Magento\Framework\App\Bootstrap;  

class Wishlist extends \Magento\Framework\View\Element\Template {

 
    
    /**
     * @var CollectionFactory
     */
    private $wishlist;

    public function __construct(
            \Magento\Wishlist\Model\Wishlist $wishlist
    ) {
        $this->wishlist = $wishlist;
    }

    public function getWishilist($customer_id) {
        //$customer_id = 1;
        $wishlist_collection = $this->wishlist->loadByCustomerId($customer_id, true)->getItemCollection();

        return $wishlist_collection;
    }

}
