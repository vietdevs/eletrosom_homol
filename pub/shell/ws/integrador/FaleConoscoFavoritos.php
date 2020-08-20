<?php 
// PATH DA APLICAÇÃO
use Magento\Framework\App\Bootstrap;

require '/var/www/html/app/bootstrap.php';


$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);

$obj = $bootstrap->getObjectManager();

$resource   = $obj->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();


$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

$storeManager   = $obj->get('\Magento\Store\Model\StoreManagerInterface');
$store          = $storeManager->getStore();	
$currentStoreId = $store->getId();

$url =  $store->getBaseUrl();


$wishListObj        = $obj->get('\Magento\Wishlist\Model\WishlistFactory');
$wishlistObj2       = $obj->get('\Magento\Wishlist\Model\Wishlist');
$categoryFactory    = $obj->get('\Magento\Catalog\Model\CategoryFactory');        
$categoryHelper     = $obj->get('\Magento\Catalog\Helper\Category');
$categoryRepository = $obj->get('\Magento\Catalog\Model\CategoryRepository');
$stockRegistry      = $obj->create('Magento\CatalogInventory\Api\StockRegistryInterface');
$_imageHelper       = $obj->get('Magento\Catalog\Helper\Image');
$productCollection  = $obj->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');



if($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/faleConosco'):
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("Retorno faleConosco: ".var_export($retorno, true));
	
	$post['name'] = $nome = $retorno["nome"];
	$post['email'] = $email = $retorno["email"];
	$post['telephone'] = $telefone = $retorno["telefone"];
	$post['comment'] = $comentario = $retorno["comentario"];

	$postObject = new Varien_Object();
	$postObject->setData($post);
	
	if (!Zend_Validate::is(trim($nome) , 'NotEmpty')) {
		ReturnValidation(325, 'Por favor, preencha os campos obrigatórios: Nome');
	}
	
	if (!Zend_Validate::is(trim($telefone) , 'NotEmpty')) {
		ReturnValidation(325, 'Por favor, preencha os campos obrigatórios: Telefone');
	}
	
	if (!Zend_Validate::is(trim($email), 'EmailAddress')) {
		ReturnValidation(325, 'Por favor, preencha o campo corretamente: E-mail');
	}
	
	if (!Zend_Validate::is(trim($comentario), 'NotEmpty')) {
		ReturnValidation(325, 'Por favor, preencha os campos obrigatórios: Comentário');
	}
	
	$mailTemplate = Mage::getModel('core/email_template');
	/* @var $mailTemplate Mage_Core_Model_Email_Template */
	$mailTemplate->setDesignConfig(array('area' => 'frontend'))
				 ->setReplyTo($email)
				 ->sendTransactional(
			Mage::getStoreConfig('contacts/email/email_template'),
			Mage::getStoreConfig('contacts/email/sender_email_identity'),
			Mage::getStoreConfig('contacts/email/recipient_email'),
			null,
			array('data' => $postObject)
	);
	
	if (!$mailTemplate->getSentSuccess()) {
		ReturnValidation(339, 'Não foi possível enviar E-mail.');
	}
	
	ReturnValidation(200, 'Processo realizado com sucesso.');
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/bannersClient'):

	$sql = "SELECT banner_main_id, image_product
					FROM banner_management_main
				WHERE status_banner_main = 1 and banner_description = 'Visão Cliente'
					ORDER BY banner_id ASC";
	
	$resource = Mage::getSingleton('core/resource');
	
	// Write do magento
	$writeConnection = $resource->getConnection('core_write');
	
	// Executa a query
	$result = $writeConnection->query($sql);
	
	$i=0;
	foreach($result AS $dados):
		$banner['banners'][$i]['codigoBanner'] 	= $dados['banner_main_id'];
		$banner['banners'][$i]['img'] 			= $dados['image_product'];
		$i++;
	endforeach;
	
	Report("Retorno banners: ".var_export($banner, true));
	
	echo stripslashes(json_encode($banner, JSON_UNESCAPED_UNICODE));

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/banners'):

	$sql = "SELECT banner_main_id, image_product 
					FROM banner_management_main 
				WHERE status_banner_main = 1 and banner_description = 'Produtos Home'
					ORDER BY banner_id ASC";
	
	$resource = Mage::getSingleton('core/resource');
	
	// Write do magento
	$writeConnection = $resource->getConnection('core_write');
	
	// Executa a query
	$result = $writeConnection->query($sql);
	
	$i=0;
	foreach($result AS $dados):
			$banner['banners'][$i]['codigoBanner'] 	= $dados['banner_main_id'];
			$banner['banners'][$i]['img'] 			= $dados['image_product'];
		$i++;
	endforeach; 

	Report("Retorno banners: ".var_export($banner, true));
	
	echo stripslashes(json_encode($banner, JSON_UNESCAPED_UNICODE));
	
elseif(substr($_SERVER['REQUEST_URI'], 0, 28) == '/shell/ws/integrador/banners'):
	
	$codigoBanner = $_GET['codigoBanner'];

	Report("Retorno banners Produtos: ".$codigoBanner);

	$model = Mage::getModel('bannermanagermain/bannermanagermain')->load($codigoBanner, 'banner_main_id');
	$dadosBusca = $model->getSkuProduct();
	$tipoBannerMain = $model->getTipoBannerMain();
	
	if($tipoBannerMain == 'sku'):
		
		$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $dadosBusca);
		
		$i = 0;
			
		$listaProduto[$i] = ListaUmProduto($_product, $i);
		
		Report("Retorno banners listaProduto: ".$listaProduto);
		
		echo stripslashes(json_encode($listaProduto, JSON_UNESCAPED_UNICODE));
		
	elseif($tipoBannerMain == 'categoria'):
		
		$_products =   Mage::getModel('catalog/product')->getCollection()
														->addAttributeToSelect('*')
														->addCategoryFilter(Mage::getModel('catalog/category')->load($dadosBusca));
	
		ListaProdutos($_products);
	
	elseif($tipoBannerMain == 'pesquisa'):

		$query = Mage::getModel('catalogsearch/query')->setQueryText($descricaoItem)->prepare();
		$fulltextResource = Mage::getResourceModel('catalogsearch/fulltext')->prepareResult(Mage::getModel('catalogsearch/fulltext'), $dadosBusca, $query);
		
		$collection = Mage::getResourceModel('catalog/product_collection');
		$collection->getSelect()->joinInner(array('search_result' => $collection->getTable('catalogsearch/result')),
				$collection->getConnection()->quoteInto(
						'search_result.product_id=e.entity_id AND search_result.query_id=?',
						$query->getId()),
				array('relevance' => 'relevance'));
		$collection->setStore(Mage::app()->getStore());
		$collection->addMinimalPrice();
		$collection->addFinalPrice();
		$collection->addTaxPercents();
		$collection->addStoreFilter();
		$collection->addUrlRewrite();
		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
		
		$_products = $collection->getData();
		
		ListaProdutos($_products);
		
	else:
		ReturnValidation(340, 'Nenhum banner encontrado!');
	endif;

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/addFavoritos'):

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("Retorno banners addFavoritos: ".var_export($retorno, true));
	
	$customer_id 	= $retorno["cliente"];
	$sku 			= $retorno["sku"];
	
	Report("Retorno addFavoritos id Cliente: " . $customer_id);
	
	//$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $_product = $obj->get('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);
	
	//$wishlist = Mage::getModel('wishlist/wishlist');
	
	try{
		if($_product <> false):
			//$customer = Mage::getModel('customer/customer')->load($customer_id);
                        $customer = $obj->create('Magento\Customer\Model\Customer')->load($customer_id);

			//$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer_id, true);
                        $wishlist   = $wishListObj->create()->loadByCustomerId($customer_id, true);
			//$buyRequest = new Varien_Object(array());
			//$res = $wishlist->addNewItem($_product, $buyRequest);
                        $res = $wishlist->addNewItem($_product);
			$wishlist->save();
			
			if($res) { ReturnValidation(200, 'Processo realizado com sucesso'); }
			else { ReturnValidation(341, 'Não foi possível inserir Produto nos Favoritos.'); }
		else:
			ReturnValidation(340, 'Nenhum produto foi encontrado!'); 
		endif;
			
	}catch( Exception $e ){
		ReturnValidation(342, 'Cliente não encontrado em nossa base de Dados.');
	}

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/listaFavoritos'):
	
	
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.

	Report("Retorno banners listaFavoritos JSON: ". var_export($jsonStr, true));

	$retorno = json_decode($jsonStr, true);	
	
	Report("Retorno banners listaFavoritos: ". var_export($retorno, true));
	
	$customer_id = $retorno["cliente"];

	
	try{
                
                $sql = "SELECT wi.product_id,w.customer_id FROM `wishlist_item` as wi left join `wishlist` as w on wi.wishlist_id = w.wishlist_id WHERE w.`customer_id` = $customer_id";
                
                $wishList = $connection->fetchAll($sql);
                
                Report("Retorno => " . var_export($wishList,true));
                
		$i = 0;
		foreach($wishList AS $_itemswishlist):
                            
                        $_product = $obj->get('Magento\Catalog\Model\Product')->load($_itemswishlist['product_id']);
                            
			$listaProduto[$i] = ListaUmProduto($_product, $i);
			$i++;
		endforeach;

		
		echo stripslashes(json_encode($listaProduto, JSON_UNESCAPED_UNICODE));
		
				
		//echo $retorno;
		
		
	}catch( Exception $e ){
		Report("Exception Retorno listaFavoritos => " . $e );
		ReturnValidation(342, $e);
	}
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/excluirFavoritos'):

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("Retorno banners excluirFavoritos: ".var_export($retorno, true));
	
	$customer_id = $retorno["cliente"];
	$sku = $retorno["sku"];
	
	try{
	
		//$wishList = $wishlistObj2->loadByCustomerId($customer_id)->getItemCollection();
                 //$wishList = $wishlistObj2->loadByCustomerId($customer_id, true)->getItemCollection();
                 
            $wishList = $whish->getWishilist($customer_id);
                 
                 
                Report("Retorno => " . var_export($wishList,true));
	
                exit;
                
		$i = 0;
		foreach($wishList AS $_itemswishlist):
			$_product = $_itemswishlist->getProduct();
			if($_product->getSku() == $sku):
				$wishlistItemId = $_itemswishlist->getWishlistItemId();
			endif;
		endforeach;
		
		if($wishlistItemId):
			$item = Mage::getModel('wishlist/item')->load($wishlistItemId);
			$item->delete();
			$wishList->save();
		else:
			ReturnValidation(343, "Produto não encontrado na Lista de Favoritos");
		endif;
		
		ReturnValidation(200, "Processo realizado com sucesso.");
	
	}catch( Exception $e ){
	
		ReturnValidation(342, $e);
	}

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/informacoesEmpresa'):
	$informacoes['informacoes'] = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('central_relacionamento_aplicativo')->toHtml();

	echo stripslashes($informacoes['informacoes']);

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/termoUso'):
	$informacoes['informacoes'] = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('termo_uso_app')->toHtml();

	echo stripslashes($informacoes['informacoes']);
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/bannerDois'):
$informacoes['informacoes'] = strip_tags(Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('banner_home_aplicativo')->toHtml());
	
$informacoes['rodape'] = strip_tags(Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('banner_rodape_home_aplicativo')->toHtml());
	
	echo json_encode($informacoes,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	
elseif(substr($_SERVER['REQUEST_URI'], 0, 32) == '/shell/ws/integrador/nossasLojas'):
	$estado = $_GET['estado'];

	$NossasLojas = Mage::getModel('nossasLojas/nossasLojas');

	if($estado){
		$informacoes[$estado] = $NossasLojas->getCity($estado);
		
		if ( count($informacoes[$estado]) == 0){
			$informacoes[$estado] = 'Sem informações para o estado!';
		}
	}else{
		$informacoes['MG'] = $NossasLojas->getCity('MG');
		$informacoes['GO'] = $NossasLojas->getCity('GO');
		$informacoes['MT'] = $NossasLojas->getCity('MT');
		$informacoes['BA'] = $NossasLojas->getCity('BA');
		$informacoes['DF'] = $NossasLojas->getCity('DF');
		$informacoes['TO'] = $NossasLojas->getCity('TO');
		$informacoes['ES'] = $NossasLojas->getCity('ES');
		
	}
	echo json_encode($informacoes,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	
endif;

function ReturnValidation($codigo, $mensagem)
{
	$dados['codigoMensagem'] = $codigo;
	$dados['mensagem'] = $mensagem;

	echo $mensagem = json_encode($dados, JSON_UNESCAPED_UNICODE);
	Report($mensagem);
	die();
}


function Report($texto, $abort = false)
{
	$data_log = shell_exec('date +%Y-%m-%d\ %H:%M:%S');
	$data_log = str_replace("\n", "", $data_log);

	$log = fopen ('/var/www/html/logs/ws_integracao.log', "a+" );
	fwrite($log, $data_log . " " . $texto . "\n");
	fclose($log);
	if ($abort) {
		exit(0);
	}
}

function returnReview($productId) {
	// -----------Retorna o Valor da Avaliação dos Produtos.--------------
	global $obj;
        global $currentStoreId;
        
        $rating = $obj->get("Magento\Review\Model\ResourceModel\Review\CollectionFactory");

        $reviews = $rating->create()->addStoreFilter(
                    $currentStoreId
                )->addStatusFilter(
                    \Magento\Review\Model\Review::STATUS_APPROVED
                )->addEntityFilter(
                    'product',
                    $productId
                )->setDateOrder();

        //print_r($collection->getData()); exit;
    
    
       // $reviews = Mage::getModel ( 'review/review' )->getResourceCollection ()->addStoreFilter ( Mage::app ()->getStore ()->getId () )->addEntityFilter ( 'product', $productId )->addStatusFilter ( Mage_Review_Model_Review::STATUS_APPROVED )->setDateOrder ()->addRateVotes ();
	$avg = 0;
	$ratings = array ();
	if (count ( $reviews ) > 0) {
		foreach ( $reviews->getItems () as $review ) {
			foreach ( $review->getRatingVotes () as $vote ) {
				$ratings [] = $vote->getPercent ();
			}
		}

		$avg = array_sum ( $ratings ) / count ( $ratings );
	}

	if (! $avg) {
		return 0;
	}

	return $avg;
	// -----------Retorna o Valor da Avaliação dos Produtos.--------------
}

function ListaProdutos($_products)
{
	if($_products):
		$i = 0;
		foreach ($_products as $product):
		
			$entityId = $product['entity_id'];
//			$_product = Mage::getModel('catalog/product')->load($entityId);
                        $_product    = $obj->get('Magento\Catalog\Model\Product') -> load($_product->getId());
			$parcelas = getParcelasList($_product);
				
			$productId = $_product->getId();
				
			$listaProduto[$i]['codigo'] = $_product->getSku();
			$listaProduto[$i]['nome']   = $_product->getName();
				
//			$_simplePricesTax = (Mage::helper('tax')->displayPriceIncludingTax() || Mage::helper('tax')->displayBothPrices());
//			$_minimalPriceValue = $_product->getMinimalPrice();
//			$_minimalPrice = Mage::helper('tax')->getPrice($_product, $_minimalPriceValue, $_simplePricesTax);
//				
//			$special_price = Mage::helper('tax')->getPrice($_product, $_product->getSpecialPrice());
//			$_regularPrice = Mage::helper('tax')->getPrice($_product, $_product->getPrice(), $_simplePricesTax);
//			$_finalPrice = Mage::helper('tax')->getPrice($_product, $_product->getFinalPrice());
//			$_price = Mage::helper('catalog/output')->_calculateSpecialPrice($_regularPrice, $_finalPrice, $special_price, $_product->getSpecialFromDate(), $_product->getSpecialToDate());

                        
                        $special_price  = $_product->getSpecialPrice ();
                        $_regularPrice  = $_product->getPrice ();
                        $_finalPrice    = $_product->getFinalPrice ();
                        $_price         = $_product->getPrice ();                        
                        
			if ($_finalPrice < $special_price) { $special_price = $_finalPrice; }
				
			if($_price == $_regularPrice)
			{
				$listaProduto[$i]['precoDe']    = '';
				$listaProduto[$i]['precoPor']   = number_format($_price, 2, ',', '.');
			} else {
				$listaProduto[$i]['precoDe']    = number_format($_regularPrice, 2, ',', '.');
				$listaProduto[$i]['precoPor']   = number_format($_price, 2, ",", ".");
			}
			
                        $desconto_boleto = $_product->getResource()->getAttribute('desconto_boleto')->getFrontend()->getValue($_product);
                        
			if ($desconto_boleto->getText() == "Yes") {
				$entityRuleId = $_product->getData('entity_promo_boleto');
					
				//Verifica se existe uma regra especial para a promocao
				if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
					$entityRuleId = 1;
				}
					
				// Regra do valor de desconto a vista
//				$shoppingCartPriceRule  = Mage::getModel('salesrule/rule')->load($entityRuleId)->getData();
                                $shoppingCartPriceRule  = $obj->create('Magento\SalesRule\Model\Rule')->load($entityRuleId);
					
				//Verifica se a regra está ativa
				if ($shoppingCartPriceRule["is_active"]) {
					if ($shoppingCartPriceRule["simple_action"] == "by_percent") {
						$percentual = number_format($shoppingCartPriceRule["discount_amount"], 1) / 100.0;
						$_priceBoleto = number_format($special_price - ($percentual * $_price), 4, null, "");
					} else if ($shoppingCartPriceRule["simple_action"] == "by_fixed") {
						$_priceBoleto = number_format(($special_price - $shoppingCartPriceRule["discount_amount"]), 4, null, "");
					}
				}
					
				$listaProduto[$i]['formaPagamento'] = "ou R$ ".number_format($_priceBoleto, 2, ',', '.')." no boleto";
					
			} else {
					
				$qtParcela      = $parcelas["qt_parcela"];
				$valorParcela   = $parcelas["parcelas"][$qtParcela];
				$listaProduto[$i]['formaPagamento'] = "em ".$qtParcela." x de R$ ". $valorParcela." s/juros";
			}
				
			//$listaProduto[$i]['imagem'] = Mage::helper('catalog/image')->init($_product, 'image')->resize(135)->__toString();
                        $listaProduto[$i]['imagem'] = $_imageHelper->init($_product, 'small_image', ['type'=>'small_image'])->keepAspectRatio(true)->resize('500')->getUrl();
				
			//-----------Retorna o Valor da Avaliação dos Produtos.--------------
			$listaProduto[$i]['avaliacao'] = returnReview($productId);
			//-----------Retorna o Valor da Avaliação dos Produtos.--------------
				
			//-----------Verifica o estoque dos filhos.--------------
			$listaProduto[$i]['emEstoque'] = returnEstoque($_product);
			//-----------Verifica o estoque dos filhos.--------------
				
			$i++;
		endforeach;
		
		echo stripslashes(json_encode($listaProduto, JSON_UNESCAPED_UNICODE));
	
	else:
		ReturnValidation(340, 'Nenhum produto foi encontrado!');
	endif;
}


function ListaUmProduto($_product, $i)
{
        global $obj;
        global $_imageHelper;
	
	//$product  = Mage::getModel('catalog/product')->load($_product->getId());
        $product    = $obj->get('Magento\Catalog\Model\Product') -> load($_product->getId());
	$listaProduto['codigo'] = $_product->getSku();
	$listaProduto['nome'] = str_replace('"',"''",$_product->getName());
	
        $productId = $_product->getId();
        
        
        
//	$_simplePricesTax = (Mage::helper('tax')->displayPriceIncludingTax() || Mage::helper('tax')->displayBothPrices());
//	$_minimalPriceValue = $_product->getMinimalPrice();
//	$_minimalPrice = Mage::helper('tax')->getPrice($_product, $_minimalPriceValue, $_simplePricesTax);
		
//	$special_price = Mage::helper('tax')->getPrice($_product, $_product->getSpecialPrice());
//	$_regularPrice = Mage::helper('tax')->getPrice($_product, $_product->getPrice(), $_simplePricesTax);
//	$_finalPrice = Mage::helper('tax')->getPrice($_product, $_product->getFinalPrice());
        $special_price  = $_product->getSpecialPrice ();
        $_regularPrice  = $_product->getPrice ();
        $_finalPrice    = $_product->getFinalPrice ();        
        
        $_price         = $_product->getPrice ();
        
        
	//$_price = Mage::helper('catalog/output')->_calculateSpecialPrice($_regularPrice, $_finalPrice, $_product->getSpecialFromDate(), $_product->getSpecialToDate());
		
	if ($_finalPrice < $special_price) { $special_price = $_finalPrice; }
		
	if($_price == $_regularPrice)
	{
		$listaProduto['precoDe'] = '';
		$listaProduto['precoPor'] = number_format($_price, 2, ',', '.');
		$special_price = $_price;
	} else {
		$listaProduto['precoDe'] = number_format($_regularPrice, 2, ',', '.');
		$listaProduto['precoPor'] = number_format($_price, 2, ",", ".");
	}
	
        $desconto_boleto = $_product->getResource()->getAttribute('desconto_boleto')->getFrontend()->getValue($_product);
        
	if ($desconto_boleto->getText() == "Yes") {
		$entityRuleId = $_product->getData('entity_promo_boleto');
	
		//Verifica se existe uma regra especial para a promocao
		if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
			$entityRuleId = 1;
		}
	
		// Regra do valor de desconto a vista
		//$shoppingCartPriceRule  = Mage::getModel('salesrule/rule')->load($entityRuleId)->getData();
                $shoppingCartPriceRule  = $obj->create('Magento\SalesRule\Model\Rule')->load($entityRuleId);
	
		//Verifica se a regra está ativa
		if ($shoppingCartPriceRule["is_active"]) {
			if ($shoppingCartPriceRule["simple_action"] == "by_percent") {
				$percentual = number_format($shoppingCartPriceRule["discount_amount"], 1) / 100.0;
				$_priceBoleto = number_format($special_price - ($percentual * $special_price), 4, null, "");
			} else if ($shoppingCartPriceRule["simple_action"] == "by_fixed") {
				$_priceBoleto = number_format(($special_price - $shoppingCartPriceRule["discount_amount"]), 4, null, "");
			}
		}
	
		$listaProduto['formaPagamento'] = "ou R$ ".number_format($_priceBoleto, 2, ',', '.')." no boleto";
	
	} else {
		
		$parcelas       = getParcelasList($_product);
		$qtParcela      = $parcelas["qt_parcela"];
		$valorParcela   = $parcelas["parcelas"][$qtParcela];
		$listaProduto['formaPagamento'] = "em ".$qtParcela." x de R$ ". $valorParcela." s/juros";
	}
		
	//$listaProduto['imagem'] = Mage::helper('catalog/image')->init($_product, 'small_image')->resize(150)->__toString();
        $listaProduto['imagem'] = $_imageHelper->init($_product, 'small_image', ['type'=>'small_image'])->keepAspectRatio(true)->resize('500')->getUrl();
		
	//-----------Retorna o Valor da Avaliação dos Produtos.--------------
	$listaProduto['avaliacao'] = returnReview($productId);
	//-----------Retorna o Valor da Avaliação dos Produtos.--------------
		
	//-----------Verifica o estoque dos filhos.--------------
	$listaProduto['emEstoque'] = returnEstoque($_product);
	//-----------Verifica o estoque dos filhos.--------------
	
	return $listaProduto;
}


function getParcelasList($product) 
    {
    	 
    	$special_price = $product->getSpecialPrice();
    	$_regularPrice = $product->getPrice();
        $_price        = $product->getPrice();
    	$_finalPrice   = $product->getFinalPrice();
    		
    	// Verifica o finalprice do produto
    	if($_finalPrice < $special_price) {
    		$special_price = $_finalPrice;
    	}
    
    	$parcelamento = array();
    
    	// Metodos de parcelamento
    	$metodos = array('parcelamento_visa');
    
    	// Valores do coeficiente de parcelamento pelo Zend_Config_Xml
    	$parcelaXml =  new Zend_Config_Xml('/var/www/html/app/etc/parcelamento.xml');
    
    	// var_dump($parcelaXml);
    	// Variável utilizada para armazenar a qtd do método que for maior
    	$qtdMetodo = 0;
    	$j = -1;
    	// lista os métodos de parcelamento
    	foreach($metodos as $metodo) {
    
    		// Pega o atributo do parcelamento
    		//$qtdParcmetodo = (int) Mage::getModel('catalog/product')->load($product->getId())->getAttributeText($metodo);
                $qtdParcmetodo = $product->getResource()->getAttribute('parcelamento_visa')->getFrontend()->getValue($product);
                //$qtdParcmetodo = 327;
    
    		// Verifica se a quantidade de parcelas é maior que 0 e também maior que a anterior
    		if (($qtdParcmetodo > 0) &&($qtdMetodo < $qtdParcmetodo)) {
    
    			// Cálculo das parcelas.
    			for($i = 1; $i <= $qtdParcmetodo; $i ++) {
    
    				// Parcela do produto
    				$parc = "parc" . (strlen($i) == 1 ? str_pad($i, 2, "0", STR_PAD_LEFT) : $i);
    
    				//$_price = $this->_calculateSpecialPrice($_regularPrice, $special_price, $product->getSpecialFromDate(),$product->getSpecialToDate());
    
    				//var_dump($_price." teste");
    				/* if($product->getPrice() == $product->getFinalPrice()) {
    				 $_price = $product->getPrice();
    				} else {
    				$_price = $product->getFinalPrice();
    				}
    				*/

    				// Calcula o valor da parcela do produto
    				if ((($_price / $i) >= $parcelaXml->parcela_minima)) {
    					$valorParcela[$i] = number_format(($_price / $i), 2, ",", ".");    
    				} else {
    					break;
    				} 
    			}
    
    			// Nome do método do cartão
    			$parcelamento['metodo'] = $metodo;
    
    
    			// Array de parcelas do método
    			$parcelamento['parcelas'] = $valorParcela;
    
    		}
    
    		// Atribui o valor da quantidade do de parcelas do método ao contador.
    		$qtdMetodo = (int) $qtdParcmetodo;
    		$j++;
    	}
    	// Quantidade de parcelas do método

    		$parcelamento['qt_parcela'] = count($valorParcela);

    
    
    	// Retorna o array do parcelamento
    	return $parcelamento;
    
    }   
    
    
function returnEstoque($_product)
	{
            global $obj;
            global $stockRegistry;            
           
            $stock          = $stockRegistry->getStockItem($_product->getId());
            $status_magento = $stock->getData('is_in_stock'); 
            $qtyStock       = $stock->getData('qty');
//            Report("Retorno Status Magento => " . $status_magento);
            
            
		if($_product->getTypeId() == 'simple'):
                    
			if($status_magento == 0):
				$estoque = 0;
			else:
				$estoque = 1;
			endif;
			return $estoque;
		
		elseif($_product->getTypeId() == 'configurable'):
			//$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getChildrenIds($_product->getId());
                        $parentIds =  $obj->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->getParentIdsByChild($_product->getId());
                        
                         Report("Retorno parentIds => " . var_export($parentIds, true));
                
			$estoque = 0;
			foreach($parentIds AS $value):
				foreach($value AS $_value):
					$idProductFilho = $_value;
					//$productConfigurable = Mage::getModel('catalog/product')->load($idProductFilho);
                                        $productConfigurable	= $obj->get('Magento\Catalog\Model\Product')->load($idProductFilho);
                                        
                                        Report("Retorno getIsInStock => " . $productConfigurable->getStockItem()->getIsInStock());
                                        
                                        
					if($productConfigurable->getStockItem()->getIsInStock()):
						$estoque = 1;
					endif;
				endforeach;
			endforeach;
	
			return $estoque;
		endif;
	}    
    

?>
