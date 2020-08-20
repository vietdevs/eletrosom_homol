<?php 
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/bootstrap.php';

$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);

$obj = $bootstrap->getObjectManager();

$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$productCollection = $obj->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

$collection = $productCollection->create()
->addAttributeToSelect('*')
->load();

foreach ($collection as $product){
	$nome = $product->getName();
	$sku = $product->getSku();
	var_dump($nome);
	var_dump($sku);
}



// var_dump($product); 