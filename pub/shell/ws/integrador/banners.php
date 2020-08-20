<?php 

// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

?>