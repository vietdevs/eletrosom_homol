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
$categoryFactory    = $obj->get('\Magento\Catalog\Model\CategoryFactory');        
$categoryHelper     = $obj->get('\Magento\Catalog\Helper\Category');
$categoryRepository = $obj->get('\Magento\Catalog\Model\CategoryRepository');
$stockRegistry      = $obj->create('Magento\CatalogInventory\Api\StockRegistryInterface');
$_imageHelper       = $obj->get('Magento\Catalog\Helper\Image');
$productCollection  = $obj->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

if ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/listaDepartamentos') :

//	$_helper = Mage::helper ( 'catalog/category' );
//	$_categories = $_helper->getStoreCategories ();

    $categoryHelper = $obj->get('\Magento\Catalog\Helper\Category');
    $_categories = $categoryHelper->getStoreCategories();
    

	Report ( "RETORNO listaDepartamentos => " . $_GET ['sku'] );
	
	if (count ( $_categories ) > 0) :
		$i = 1;
		foreach ( $_categories as $_category ) :
			//$_helper->getCategoryUrl ( $_category );

//                    var_dump($_category->getName()); exit;
			$categoriasCat ['nomeCat'] = $_category->getName ();
			$categoriasCat ['idCat'] = $_category->getId ();                       
                        
                        
			if ($_category->getId () == 13) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/AREVENTILACAO.png';
			} else if ($_category->getId () == 20) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/AUTOMOTIVO.png';
			} else if ($_category->getId () == 24) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/BBS.png';
			} else if ($_category->getId () == 67) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/COLCHAO.png';
			} else if ($_category->getId () == 129) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/ELETRODOMESTICOS.png';
			} else if ($_category->getId () == 212) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/FERRAMENTAS.png';
			} else if ($_category->getId () == 240) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/INFO.png';
			} else if ($_category->getId () == 254) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/MOVEIS.png';
			} else if ($_category->getId () == 87) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/PORTATEIS.png';
			} else if ($_category->getId () == 311) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/RELOGIOS.png';
			} else if ($_category->getId () == 113) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/SAUDEEBLZ.png';
			} else if ($_category->getId () == 322) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/SMARTPHONE.png';
			} else if ($_category->getId () == 181) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/SPORTELAZER.png';
			} else if ($_category->getId () == 335) {
				$categoriasCat ['urlCat'] = $store->getBaseUrl() . 'media/categorias/TV.png';
			} else {
				$categoriasCat ['urlCat'] = '';
			}

//			$_category = Mage::getModel ( 'catalog/category' )->load ( $_category->getId () );
//			$_subcategories = $_category->getChildrenCategories ();
                        
                        $categoryFactory = $obj->get('\Magento\Catalog\Model\CategoryFactory');// Instance of Category Model 
                        $_category = $categoryFactory->create()->load($_category->getId ());
                        $_subcategories = $_category->getChildrenCategories();
                        
			if (count ( $_subcategories ) > 0) :
				$subCategoriasArray = array();
				foreach ( $_subcategories as $_subcategory ) :
					$subCategorias ['nomeSub']  = $_subcategory->getName ();
					$subCategorias ['idSub']    = $_subcategory->getId ();
					$subCategoriasArray[]       = $subCategorias;
				endforeach
				;
			endif;

			$categoriasCat ['sub'] = $subCategoriasArray;
			$categorias ['categorias'] ['cat'] [] = $categoriasCat;
		endforeach
		;
	endif;

	echo $mensagem = stripslashes ( json_encode ( $categorias, JSON_UNESCAPED_UNICODE ) );

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 34 ) == '/shell/ws/integrador/listaProdutos') :

	Report ( "Categoria: ". var_export($_REQUEST, true) );
	
        $categoryId = $_GET ['departamento'];
	$dir        = $_GET ['dir'];
	$order      = $_GET ['order'];
	$idCliente  = $_GET ['idCliente'];

        
	if ($idCliente) {
            //$wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( $idCliente, true );
            $wishlist = $wishListObj->create()->loadByCustomerId($idCliente, true);
                
	}


	if (!$categoryId){
            $categoryId = 402;
            $category   = $categoryFactory->create()->load($categoryId);
            $_products  = $category->getProductCollection() ->addAttributeToSelect('*');
		//$_products = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToSelect ( '*' )->addCategoryFilter ( Mage::getModel ( 'catalog/category' )->load ( 495 ) );
   
	}

	//$_products = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToSelect ( '*' )->addCategoryFilter ( Mage::getModel ( 'catalog/category' )->load ( $categoryId ) );
        $category   = $categoryFactory->create()->load($categoryId);
        $_products  = $category->getProductCollection() ->addAttributeToSelect('*');
        
	// Validação para mostrrar apenas produtos com estoque - Amaro JR - Tarefa 13533 - 07/02/2020
	/*$_products->joinField('is_in_stock',
			'cataloginventory/stock_item',
			'is_in_stock',
			'product_id=entity_id',
			'is_in_stock=1',
			'{{table}}.stock_id=1',
			'left');      
	*/
	// ->setPage(1, 24);
	if ($order == 'name') :
		$_products->addAttributeToSort ( 'name', $dir );
	 elseif ($order == 'stock') :
		$_products->joinField ( 'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left' )->addAttributeToSort ( 'qty', $dir );
	 elseif ($order) :
		$_products->addAttributeToSort ( $order, $dir );
	endif;

	$i = 0;
	foreach ( $_products as $_product ) :
 
            
		//$parcelas = Mage::helper ( 'catalog/output' )->getParcelasList ( $_product );
                //$parcelas = $_product->getResource()->getAttribute('parcelamento_visa')->getFrontend()->getValue($_product);
                $parcelas = getParcelasList ( $_product );
        
		$productId = $_product->getId ();
		
                $desconto_boleto = $_product->getResource()->getAttribute('desconto_boleto')->getFrontend()->getValue($_product);
                
//                var_dump($_product->getSku());
//                var_dump("Retorno funcao returnEstoque => ".returnEstoque ( $_product )); 
              
                
		if (returnEstoque ( $_product ) == 0){
			continue;
		}
                
                
                
                
		// -----------Verifica o estoque dos filhos.--------------
		//$listaProduto [$i] ['emEstoque'] = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )
                $listaProduto [$i] ['emEstoque'] = returnEstoque ( $_product );
		// -----------Verifica o estoque dos filhos.--------------
		
		Report ("Estoque: ".$listaProduto [$i] ['emEstoque']);
		


		$listaProduto [$i] ['codigo'] = $_product->getSku ();
		$listaProduto [$i] ['nome'] = str_replace ( '"', '', $_product->getName () );

//		$special_price = Mage::helper ( 'tax' )->getPrice ( $_product, $_product->getSpecialPrice () );
//		$_regularPrice = Mage::helper ( 'tax' )->getPrice ( $_product, $_product->getPrice (), $_simplePricesTax );
//		$_finalPrice = Mage::helper ( 'tax' )->getPrice ( $_product, $_product->getFinalPrice () );
                $special_price  = $_product->getSpecialPrice ();
                $_regularPrice  = $_product->getPrice ();
                $_finalPrice    = $_product->getFinalPrice ();
                
                

		if ($_finalPrice < $special_price) {
			$special_price = $_finalPrice;
		} elseif ($special_price == NULL) {
			$special_price = $_finalPrice;
		}
                

//                var_dump( $special_price );
//                var_dump( $_regularPrice );
//                var_dump( $_finalPrice );


                //exit;                
                

//		$_price = Mage::helper ( 'catalog/output' )->_calculateSpecialPrice ( $_regularPrice, $special_price, $_product->getSpecialFromDate (), $_product->getSpecialToDate () );

//		if ($_price == $_regularPrice) {
//			$listaProduto [$i] ['precoDe'] = '';
//			Report ( "por aqui 2");
//			$listaProduto [$i] ['precoPor'] = number_format ( $_price, 2, ',', '.' );
//			$listaProduto [$i] ['percentual'] = 0;
//		} else {
			$listaProduto [$i] ['precoDe'] = number_format ( $_regularPrice, 2, ',', '.' );
			Report ( "por aqui 3");
			$listaProduto [$i] ['precoPor'] = number_format ( $_price, 2, ",", "." );
			$listaProduto [$i] ['percentual'] = ( int ) - ((($_price / $_regularPrice) * 100) - 100);
//		}

		if ($desconto_boleto->getText() == "Yes") {
			$entityRuleId = $_product->getData ( 'entity_promo_boleto' );

			// Verifica se existe uma regra especial para a promocao
			if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
				$entityRuleId = 1;
			}

			// Regra do valor de desconto a vista
//			$shoppingCartPriceRule  = Mage::getModel ( 'salesrule/rule' )->load ( $entityRuleId )->getData ();
                        $shoppingCartPriceRule  = $obj->create('Magento\SalesRule\Model\Rule')->load($entityRuleId);
                        $isActive               = $shoppingCartPriceRule->getIsActive();
                        
                        

                        
                        
			// Verifica se a regra está ativa
			if ($shoppingCartPriceRule ["is_active"]) {
				if ($shoppingCartPriceRule ["simple_action"] == "by_percent") {
					$percentual = number_format ( $shoppingCartPriceRule ["discount_amount"], 1 ) / 100.0;
					$_priceBoleto = number_format ( $special_price - ($percentual * $_price), 4, null, "" );
				} else if ($shoppingCartPriceRule ["simple_action"] == "by_fixed") {
					$_priceBoleto = number_format ( ($special_price - $shoppingCartPriceRule ["discount_amount"]), 4, null, "" );
				}
			}

			$listaProduto [$i] ['formaPagamento'] = "ou R$ " . number_format ( $_priceBoleto, 2, ',', '.' ) . " no boleto";
		} else {

			$qtParcela = $parcelas ["qt_parcela"];
			$valorParcela = $parcelas ["parcelas"] [$qtParcela];
			$listaProduto [$i] ['formaPagamento'] = "em " . $qtParcela . " x de R$ " . $valorParcela . " s/juros";
		}

                        Report ("Retorno regras IsActive => " . $isActive); 
                        Report ("Retorno regras IsActive => " . $shoppingCartPriceRule ["simple_action"]); 
                        Report ("Retorno regras IsActive => " . $shoppingCartPriceRule ["discount_amount"]);
                        
                



                
                
		//$listaProduto [$i] ['imagem'] = Mage::helper ( 'catalog/image' )->init ( $_product, 'small_image' )->resize ( 500 )->__toString ();
                $listaProduto [$i] ['imagem'] = $_imageHelper->init($_product, 'small_image', ['type'=>'small_image'])->keepAspectRatio(true)->resize('500')->getUrl();

		// -----------Retorna o Valor da Avaliação dos Produtos.--------------
		$listaProduto [$i] ['avaliacao'] = returnReview ( $productId );
		// -----------Retorna o Valor da Avaliação dos Produtos.--------------

		

		$listaProduto [$i] ['favorito'] = false;
		if ($idCliente) {
			foreach ( $wishlist->getItemCollection () as $_item ) {
				if ($_item->representProduct ( $_product )) {
					$listaProduto [$i] ['favorito'] = true;
					// break;
				}
			}
		}

		$i ++;
	endforeach;

	Report ( "RETORNO PRODUTOS 1 => " . var_export ( $listaProduto, true ) );

	echo stripslashes ( json_encode ( $listaProduto, JSON_UNESCAPED_UNICODE ) );

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 36 ) == "/shell/ws/integrador/detalhaProdutos") :

	$sku = $_GET ['sku'];
	$idCliente = $_GET ['idCliente'];
	
	Report ( "RETORNO detalhaProdutos => " . $_GET ['sku'] );
	
	try {
		//$_product = Mage::getModel ( 'catalog/product' )->loadByAttribute ( 'sku', $sku );
                $_product = $obj->get('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);

		if ($_product) :
			$parcelas = getParcelasList ( $_product );

			$productId = $_product->getId ();

			$listaProduto ['codigo'] = $_product->getSku ();
			$listaProduto ['nome'] = addslashes ( $_product->getName () );

			$special_price = $_product->getSpecialPrice () ;
			$_regularPrice = $_product->getPrice ();
                        $_price        = $_product->getPrice ();
			$_finalPrice   = $_product->getFinalPrice ();


			Report ( "Regular Price => " . $_regularPrice );
			Report ( "Special Price 1 => " . $special_price );

			// Verifica o finalprice do produto
			if ($_finalPrice < $special_price) {
				$special_price = $_finalPrice;
			}

			Report ( "Special Price 2 => " . $special_price );

			//$_price = Mage::helper ( 'catalog/output' )->_calculateSpecialPrice ( $_regularPrice, $special_price, $_product->getSpecialFromDate (), $_product->getSpecialToDate () );

			Report ( "Price => " . $_price );

			// if ($_finalPrice < $special_price) { $special_price = $_finalPrice; } elseif($special_price == NULL) {$special_price = $_finalPrice; }

//			if ($special_price == $_regularPrice) {
//				$listaProduto ['precoDe'] = number_format ( $_price, 2, ',', '.' );
//				Report ( "por aqui 4");
//				$listaProduto ['precoPor'] = number_format ( $_price, 2, ',', '.' );;
//				$listaProduto [$i] ['percentual'] = 0;
//			} else {
				$listaProduto ['precoDe'] = number_format ( $_regularPrice, 2, ',', '.' );
				Report ( "por aqui 5");
				$listaProduto ['precoPor'] = number_format ( $_price, 2, ",", "." );
				$listaProduto [$i] ['percentual'] = ( int ) - ((($_price / $_regularPrice) * 100) - 100);
//			}

                        $desconto_boleto = $_product->getResource()->getAttribute('desconto_boleto')->getFrontend()->getValue($_product);        
                        
                      
                        
			if ($desconto_boleto->getText() == "Yes") {
				$entityRuleId = $_product->getData ( 'entity_promo_boleto' );

				// Verifica se existe uma regra especial para a promocao
				if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
					$entityRuleId = 1;
				}

				// Regra do valor de desconto a vista
//				$shoppingCartPriceRule = Mage::getModel ( 'salesrule/rule' )->load ( $entityRuleId )->getData ();
                                $shoppingCartPriceRule  = $obj->create('Magento\SalesRule\Model\Rule')->load($entityRuleId);
                                $isActive               = $shoppingCartPriceRule->getIsActive();                                

				// Verifica se a regra está ativa
				if ($isActive) {
					if ($shoppingCartPriceRule ["simple_action"] == "by_percent") {
						$percentual = number_format ( $shoppingCartPriceRule ["discount_amount"], 1 ) / 100.0;
						$_priceBoleto = number_format ( $special_price - ($percentual * $_price), 4, null, "" );
					} else if ($shoppingCartPriceRule ["simple_action"] == "by_fixed") {
						$_priceBoleto = number_format ( ($special_price - $shoppingCartPriceRule ["discount_amount"]), 4, null, "" );
					}
				}

				$listaProduto ['formaPagamento'] = "ou R$ " . number_format ( $_priceBoleto, 2, ',', '.' ) . " no boleto";
			} else {

				$qtParcela = $parcelas ["qt_parcela"];
				$valorParcela = $parcelas ["parcelas"] [$qtParcela];
				$listaProduto ['formaPagamento'] = "em " . $qtParcela . " x de R$ " . $valorParcela . " s/juros";
			}

			// Lista as Resenhas
			//$reviews = Mage::getModel ( 'review/review' )->getResourceCollection ()->addStoreFilter ( Mage::app ()->getStore ()->getId () )->addEntityFilter ( 'product', $productId )->addStatusFilter ( Mage_Review_Model_Review::STATUS_APPROVED )->setDateOrder ();
			//$summaryData = Mage::getModel('review/review_summary')->setStoreId(Mage::app ()->getStore ()->getId ())->load($productId);

                       // $reviewFactory->getEntitySummary($_product, $storeId);

                        $summaryData = $_product->getRatingSummary();
                        
                        $rating = $obj->get("Magento\Review\Model\ResourceModel\Review\CollectionFactory");

                        $reviews = $rating->create()->addStoreFilter(
                                    $currentStoreId
                                )->addStatusFilter(
                                    \Magento\Review\Model\Review::STATUS_APPROVED
                                )->addEntityFilter(
                                    'product',
                                    $productId
                                )->setDateOrder();                        
                        
                        
			// -----------Retorna o Valor da Avaliação dos Produtos.--------------
			$listaProduto ['avaliacao'] = (5 * $summaryData['rating_summary']) / 100;
			
			$_items = $reviews->getItems ();

			if (count ( $_items )) :
				$r = 0;
			
				foreach ( $_items as $_review ) {
					
					
					//var_dump($_review->getRatingVotes()); exit;
					$resenhas [$r] ['nickname'] = $_review->getNickname ();
					$resenhas [$r] ['titulo'] = $_review->getTitle ();
					$resenhas [$r] ['descricao'] = $_review->getDetail ();
					$resenhas [$r] ['vote'] = (5 * $summaryData['rating_summary']) / 100;

					$r ++;
				}

				$listaProduto ['resenhas'] = $resenhas;
			else :
				$listaProduto ['resenhas'] = '';
			endif;

			// ----------- Retorna o Valor da Avaliação dos Produtos.--------------

			// ---------------------------- Lista as imagens.---------------------------
			//$getMediaGalleryImages = Mage::getModel ( 'catalog/product' )->load ( $productId )->getMediaGalleryImages ();
                        $getMediaGalleryImages = $_product->getMediaGalleryImages ();
			$l = 0;
			foreach ( $getMediaGalleryImages as $_image ) :
				//$listaProduto ['imagem'] ['img' . $l] = Mage::helper ( 'catalog/image' )->init ( $_product, 'small_image', $_image->getFile () )->resize ( 500 )->__toString ();
                                $listaProduto ['imagem'] ['img' . $l] = $_imageHelper->init($_product, 'small_image', ['type'=>'small_image'])->keepAspectRatio(true)->resize('500')->getUrl();                        
                        
				$l ++;
			endforeach
			;
			// ---------------------------- Lista as imagens.---------------------------

			// ----------- Verifica o estoque dos Produtos Configuráveis e Simples. --------------
			$listaProduto ['emEstoque'] = returnEstoque ( $_product );
			// ----------- Verifica o estoque dos Produtos Configuráveis e Simples. --------------

			$listaProduto ['tipoProduto'] = $_product->getTypeId ();

			if ($_product->getTypeId () == 'configurable') :
				//$parentIds = Mage::getResourceSingleton ( 'catalog/product_type_configurable' )->getChildrenIds ( $productId );
                                $parentIds =  $obj->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->getParentIdsByChild($productId);

				$f = 0;
				foreach ( $parentIds as $value ) :
					foreach ( $value as $_value ) :
						$idProductFilho = $_value;
						
//						$productConfigurable = Mage::getModel ( 'catalog/product' )->load ( $idProductFilho );
                                                $productConfigurable    = $obj->get('Magento\Catalog\Model\Product')->load($idProductFilho);
						
						Report ( "RETORNO detalhaProdutos Filho => " . $idProductFilho );
						
						$listaProduto ['filhos'] [$f] ['stock'] = returnEstoque ( $productConfigurable );
						
						if ($listaProduto ['filhos'] [$f] ['stock'] == 0){
							continue;
						}

						

						$optionId = $productConfigurable->getVoltagem ();

						if (! $optionId) {
							$optionId = $productConfigurable->getCorVestuario ();
						}

						Report ( "RETORNO detalhaProdutos optionId => " . $optionId );

						//$label = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->execute ( "SELECT value FROM eav_attribute_option_value WHERE option_id =  $optionId" );
                                                
                                                $sql = "SELECT value FROM eav_attribute_option_value WHERE option_id =  $optionId";
                                                
                                                $result = $connection->fetchAll($sql);

                                                $label  = $result[0]['value'];
                                                
						$listaProduto ['filhos'] [$f] ['voltagem'] = $label;

						$listaProduto ['filhos'] [$f] ['sku'] = $productConfigurable->getSku ();
						
						$listaProduto ['filhos'] [$f] ['stock'] = returnEstoque ( $productConfigurable );						
						

						$f ++;
					endforeach
					;
				endforeach
				;
			else :
				$listaProduto ['filhos'] = '';
			endif;

			// ---------------------------- Parcelas -----------------------------------
			// Valores do coeficiente de parcelamento pelo Zend_Config_Xml
			$parc_xml = simplexml_load_file ( '/var/www/html/app/etc/parcelamento.xml' );

			$coeficiente [1] = ( float ) $parc_xml->parcelas->parc01;
			$coeficiente [2] = ( float ) $parc_xml->parcelas->parc02;
			$coeficiente [3] = ( float ) $parc_xml->parcelas->parc03;
			$coeficiente [4] = ( float ) $parc_xml->parcelas->parc04;
			$coeficiente [5] = ( float ) $parc_xml->parcelas->parc05;
			$coeficiente [6] = ( float ) $parc_xml->parcelas->parc06;
			$coeficiente [7] = ( float ) $parc_xml->parcelas->parc07;
			$coeficiente [8] = ( float ) $parc_xml->parcelas->parc08;
			$coeficiente [9] = ( float ) $parc_xml->parcelas->parc09;
			$coeficiente [10] = ( float ) $parc_xml->parcelas->parc10;
			$coeficiente [11] = ( float ) $parc_xml->parcelas->parc11;
			$coeficiente [12] = ( float ) $parc_xml->parcelas->parc12;
			$parcela_minima = ( float ) $parc_xml->parcela_minima;

			// Quantidade de parcelas sem juros
//			$parcelas = Mage::getModel ( 'mundipagg/api' )->parcelasSemJuros ();
                        //$parcelas = parcelasSemJuros ();
                        $parcelas = $_product->getResource()->getAttribute('parcelamento_visa')->getFrontend()->getValue($_product);  
			if ($_price == $_regularPrice) {
				$preco = $_price;
			} else {
				$preco = $_regularPrice;
			}

			for($p = 1; $p <= 12; $p ++) {
//				$ValorParcela = str_replace ( ".", "", $parcelas );
//				$ValorParcela = str_replace ( ",", ".", $ValorParcela );
//				$ValorParcela = number_format ( $ValorParcela, 2, ".", "" );
				if ($p <= $parcelas) {
					$listaProduto ['parcelamento'] ['par' . $p] = $p . " x de R$ " . number_format ( $preco / $p, 2, ',', '.' ) . " s/ juros";
				} else {
					$listaProduto ['parcelamento'] ['par' . $p] = $p . " x de R$ " . number_format ( $preco * $coeficiente [$p], 2, ',', '.' ) . " c/ juros";
				}
			}
			// ---------------------------- Parcelas -----------------------------------

			$listaProduto ['caracteristicas'] = $url . 'shell/ws/integrador/caracteristicasProduto?codigoProduto=' . $sku;
			$n = 0;
                        

                        
			foreach (explode("\n",$_product->getAtributosTemporarios ()) as $carac){
					
				$especifica = explode(":",$carac);
				//var_dump($especifica); exit;	
				$listaProduto ['especificacoes'][$n]['campo'] = addslashes(trim($especifica[0]));
				$listaProduto ['especificacoes'][$n]['descricao'] = addslashes(trim($especifica[1]));
				$n++;					
			}

			$listaProduto ['dimensoes'] ['altura'] = $_product->getAltura ();
			$listaProduto ['dimensoes'] ['largura'] = $_product->getLargura ();
			$listaProduto ['dimensoes'] ['profundidade'] = $_product->getProfundidade ();
			$listaProduto ['dimensoes'] ['peso'] = $_product->getWeight ();
			$listaProduto ['urlsocial']  = $_product->getProductUrl();

                       
                        
                        
			if ($idCliente) :
                                $wishlist   = $wishListObj->create()->loadByCustomerId($idCliente, true);
				//$wishlist   = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( $idCliente, true );
				//$collection = Mage::getModel ( 'wishlist/item' )->getCollection ()->addFieldToFilter ( 'wishlist_id', $wishlist->getId () )->addFieldToFilter ( 'product_id', $productId );
				$collection = $obj->get('Magento\Wishlist\Model\ResourceModel\Item\Collection')->addFieldToFilter ( 'wishlist_id', $wishlist->getId () )->addFieldToFilter('product_id', ['eq' => $productId]);
                                
                                $item       = $collection->getFirstItem ();

				if ($item->getId ()) :
					$listaProduto ['favoritos'] = 1;
				else :
					$listaProduto ['favoritos'] = 0;
				endif;

			else :
				$listaProduto ['favoritos'] = 0;
			endif;

                        
			//$partner = Mage::getModel ( 'catalog/product' )->load ( $_product->getId () )->getAttributeText ( 'parceiro' );
                        
                        $partner = '';
                        
			if (! $partner) {
				$partner = "Eletrosom.com";
			}
			$listaProduto ['parceiro'] = $partner;

			Report ( "RETORNO PRODUTOS => " . var_export ( $listaProduto, true ) );
			
			if($_GET['debug']){
				echo '<pre>';
				var_dump($listaProduto); exit;
			}
			
			echo stripslashes ( json_encode ( $listaProduto, JSON_UNESCAPED_UNICODE ) );
		else :
			ReturnValidation ( 329, "SKU Inválido." );
		endif;
	} catch ( Exception $e ) {
		Report ( "RETORNO detalhaProdutos => " . $e );
		ReturnValidation ( 329, "SKU Inválido." );
	}

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 34 ) == "/shell/ws/integrador/consultaFrete") :

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
 	$retorno = json_decode($jsonStr, true);
 	Report('json Frete:' .var_export($retorno, true));
 	
        //$frenet = $obj->get('\Frenet\Framework\Object');
        
        
            $post = [
                'product' => '3386',
                'selected_configurable_option' => '',
                'related_product'   => '',
                'item' => '3386',
                'qty' => '1',
                'postcode' => '38500000'
                
            ];

            $ch = curl_init('https://m2.eletrosom.com/frenet/product/quote');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

            // execute!
            $response = curl_exec($ch);

            // close the connection, release resources used
            curl_close($ch);

            // do anything you want with your response
            var_dump($response);        
        
        exit;
        
 	if($retorno['frete']){
 		
 		$count = 0;
 		
 		foreach($retorno['frete']['produtos'] as $produtos){
 			$product = Mage::getModel ( 'catalog/product' )->loadByAttribute ( 'sku', $produtos['sku'] );
 			$items[$count]['codigo'] = $product->getId();
 			$items[$count]['qtde'] = $produtos['qtde'];
 			$valorUnitario =  number_format (  $product->getFinalPrice(), 2, ".", "" ) * $produtos['qtde'];
 			$totalGeral += $valorUnitario;
 			$count++;
 		}
 		
 		Report("items ===========> " . var_export($items,true));
 		//Report("QTDE => " . $qtde);
 		Report("TOTAL => " . $retorno['frete']['total_com_desconto']);
 		
 		//$frete = Mage::getModel ( 'sales/order' )->enviaFrenet($shippingItemArray, $retorno['frete']['total_com_desconto'], $retorno['frete']['cep']);
 		$frete = Mage::getModel('akhilleus/carrier_akhilleusapp')->calculaFreteProdutosCategoria($items, $retorno['frete']['cep'], "C", $totalGeral);
		Report('Frete1:' . var_export($frete, true));
 		
 		echo stripslashes ( json_encode ( $frete, JSON_UNESCAPED_UNICODE ) );
 		exit;
 	}

	$sku = $_GET ["sku"];
	// Cep por parâmetro
	$cep = ( string ) str_replace ( "-", "", $_GET ["cep"] );

	if ($cep != "" && $sku != "" && ! $jsonStr) {
		$product = Mage::getModel ( 'catalog/product' )->loadByAttribute ( 'sku', $sku );

		if (! $product) {
			ReturnValidation ( 329, "SKU Inválido." );
		}

		Report("TOTAL => " . var_export(number_format ( $product->getFinalPrice(), 2, ".", "" ),true)); 
		
		$freteRetorno = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->calculaFreteProdutosCategoria ( $product, $cep, "P", number_format ($product->getFinalPrice(),2,".", ""));

		//var_dump($freteRetorno);
		
		Report('Frete2:' . var_export($freteRetorno, true));
		echo stripslashes ( json_encode ( $freteRetorno, JSON_UNESCAPED_UNICODE ) );
	} else {
		ReturnValidation ( 325, "Por favor, preencha os campos obrigatórios." );
	}

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 26 ) == "/shell/ws/integrador/busca") :

	Report('busca:' . var_export($_REQUEST, true));

	$descricaoItem = $_GET ['q'];
	$dir        = $_GET ['dir'];
	$order      = $_GET ['order'];
	$filtro     = $_GET ['filtro'];
	$idCliente  = $_GET ['idCliente'];

	if (! $dir) {
		$dir = 'asc';
	}

	
	// if(!$order){
	// $order = 'name';
	// }

	if ($idCliente) {
		$wishlist = Mage::getModel ( 'wishlist/wishlist' )->loadByCustomer ( $idCliente, true );
	}
 

        $layerResolver   = $obj->get(\Magento\Catalog\Model\Layer\Resolver::class);
        $layerResolvers  = $layerResolver->create('search');
        $layerResolvers1 = $layerResolver->get();
        $collection      = $layerResolvers1->getProductCollection();

        foreach ($collection as $product) {
             $produtos[] = $product->getId();  
        }
 

        
	if ($order == 'name') :
		//$collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addFinalPrice ()->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToSort ( 'name', $dir );
                $collection = $obj->get('Magento\Catalog\Model\Product')->getCollection()->addFinalPrice ()->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToSort ( 'name', $dir )->addAttributeToFilter('entity_id',$produtos);
	 elseif ($order == 'stock') :
		//$collection = Mage::getModel ( 'catalog/product' )->getCollection ()->joinField ( 'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left' )->addUrlRewrite ()->addPriceData ()->addStoreFilter ( $store_id )->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToSort ( 'qty', $dir );
                $collection = $obj->get('Magento\Catalog\Model\Product')->getCollection()->joinField ( 'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left' )->addUrlRewrite ()->addPriceData ()->addStoreFilter ( $store_id )->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToSort ( 'qty', $dir )->addAttributeToFilter('entity_id',$produtos);
	else :
		//$collection = Mage::getResourceModel ( 'catalog/product_collection' )->addFinalPrice ()->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 );
                $collection = $obj->get('Magento\Catalog\Model\Product')->getCollection()->addFinalPrice ()->addAttributeToFilter ( 'status', 1 )->addAttributeToFilter ( 'visibility', 4 )->addAttributeToFilter('entity_id',$produtos);
		/*
	 * if($order):
	 * $collection->getSelect()->order($order ." ". $dir);
	 * endif;
	 */
	endif;


	//$collection->setStore ( 9 );
	$collection->addMinimalPrice ();
	$collection->addFinalPrice ();
	$collection->addMinimalPrice ();
	$collection->addStoreFilter ();
	$collection->addUrlRewrite ();
	$collection->addAttributeToSort ( 'price', $dir );
	// $collection->addAttributeToSort('finalprice',$dir);
	//Mage::getSingleton ( 'catalog/product_status' )->addVisibleFilterToCollection ( $collection );
	//Mage::getSingleton ( 'catalog/product_visibility' )->addVisibleInSearchFilterToCollection ( $collection );

	// echo "<pre>";

	if (isset ( $filtro )) :
		// var_dump($filtro);exit;
		$dadosFiltro = explode ( "|", $filtro );
		foreach ( $dadosFiltro as $atributo ) :
			if ($atributo) {
				$_atributo = explode ( ":", $atributo );

				$variavel = $_atributo [0];
				$resultVariable = $_atributo [1];

				if ($variavel == 'price') :
					$price = explode ( "-", $resultVariable );
					$minprice = $price [0];
					$maxprice = $price [1];

					if ($minprice != '' && $maxprice != '') :
						// Valida os produtos entre os valores sugeridos
						$collection->addFieldToFilter ( 'special_price', array (
								'gteq' => $minprice
						) )->addFieldToFilter ( 'special_price', array (
								'lteq' => $maxprice
						) );

					elseif ($maxprice != '') :
						// Valida os produtos abaixo do preço sugerido
						$collection->addFieldToFilter ( 'special_price', array (
								'lteq' => $maxprice
						) );
					 elseif ($minprice != '') :
						// Valida os produtos superior do preço sugerido
						$collection->addFieldToFilter ( 'special_price', array (
								'gteq' => $minprice
						) );
					endif;
				else :
					$collection->addFieldToFilter ( $variavel, $resultVariable );
				endif;
			}
		endforeach
		;
	endif;

		// var_dump($collection); exit;
	$_products = $collection->getData ();

	$i = 0;
        
        
     
        
      
        
	foreach ( $_products as $product ) :

		$entityId = $product ['entity_id'];
		//$_product = Mage::getModel ( 'catalog/product' )->load ( $entityId );
                $_product = $obj->get('Magento\Catalog\Model\Product') -> load($entityId);
		//$parcelas = Mage::helper ( 'catalog/output' )->getParcelasList ( $_product );
                $parcelas = getParcelasList ( $_product );  
                
		$categoryIds [] = $_product->getCategoryIds ();

		$productId = $_product->getId ();

		$listaProduto [$i] ['codigo'] = $_product->getSku ();
		$listaProduto [$i] ['nome'] = addslashes ( $_product->getName () );

		$listaProduto [$i] ['favorito'] = false;
		if ($idCliente) {
			foreach ( $wishlist->getItemCollection () as $_item ) {
				if ($_item->representProduct ( $_product )) {
					$listaProduto [$i] ['favorito'] = true;
				}
			}
		}

		$special_price  = $_product->getSpecialPrice ();
		$_regularPrice  = $_product->getPrice ();
		$_finalPrice    = $_product->getFinalPrice ();
		
		if ($_finalPrice < $special_price) {
			$special_price = $_finalPrice;
		} elseif ($special_price == NULL) {
			$special_price = $_finalPrice;
		}
		
		//$_price = Mage::helper ( 'catalog/output' )->_calculateSpecialPrice ( $_regularPrice, $special_price, $_product->getSpecialFromDate (), $_product->getSpecialToDate () );
		
		/*if ($_price == $_regularPrice) {
			$listaProduto [$i] ['precoDe'] = '';
			Report ( "por aqui ");
			$listaProduto [$i] ['precoPor'] = number_format ( $_price, 2, ',', '.' );
			$listaProduto [$i] ['percentual'] = 0;
		} else {*/
			$listaProduto [$i] ['precoDe'] = number_format ( $_regularPrice, 2, ',', '.' );
			Report ( "por aqui 1");
			$listaProduto [$i] ['precoPor'] = number_format ( $_price, 2, ",", "." );
			$listaProduto [$i] ['percentual'] = ( int ) - ((($_price / $_regularPrice) * 100) - 100);
		//}

                $desconto_boleto = $_product->getResource()->getAttribute('desconto_boleto')->getFrontend()->getValue($_product);
                        
		if ($desconto_boleto->getText() == "Yes") {
			$entityRuleId = $_product->getData ( 'entity_promo_boleto' );

			// Verifica se existe uma regra especial para a promocao
			if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
				$entityRuleId = 1;
			}

			// Regra do valor de desconto a vista
			//$shoppingCartPriceRule = Mage::getModel ( 'salesrule/rule' )->load ( $entityRuleId )->getData ();
                        $shoppingCartPriceRule  = $obj->create('Magento\SalesRule\Model\Rule')->load($entityRuleId);
                      

			// Verifica se a regra está ativa
			if ($shoppingCartPriceRule ["is_active"]) {
				if ($shoppingCartPriceRule ["simple_action"] == "by_percent") {
					$percentual = number_format ( $shoppingCartPriceRule ["discount_amount"], 1 ) / 100.0;
					$_priceBoleto = number_format ( $special_price - ($percentual * $_price), 4, null, "" );
				} else if ($shoppingCartPriceRule ["simple_action"] == "by_fixed") {
					$_priceBoleto = number_format ( ($special_price - $shoppingCartPriceRule ["discount_amount"]), 4, null, "" );
				}
			}

			$listaProduto [$i] ['formaPagamento'] = "ou R$ " . number_format ( $_priceBoleto, 2, ',', '.' ) . " no boleto";
		} else {

			$qtParcela = $parcelas ["qt_parcela"];
			$valorParcela = $parcelas ["parcelas"] [$qtParcela];
			$listaProduto [$i] ['formaPagamento'] = "em " . $qtParcela . " x de R$ " . $valorParcela . " s/juros";
		}

		//$listaProduto [$i] ['imagem'] = Mage::helper ( 'catalog/image' )->init ( $_product, 'image' )->resize ( 500 )->__toString ();
                $listaProduto [$i] ['imagem'] = $_imageHelper->init($_product, 'small_image', ['type'=>'small_image'])->keepAspectRatio(true)->resize('500')->getUrl();

		// -----------Retorna o Valor da Avaliação dos Produtos.--------------
		$listaProduto [$i] ['avaliacao'] = returnReview ( $productId );
		// -----------Retorna o Valor da Avaliação dos Produtos.--------------

		// -----------Verifica o estoque dos filhos.--------------
		$listaProduto [$i] ['emEstoque'] = returnEstoque ( $_product );
		// -----------Verifica o estoque dos filhos.--------------

		$i ++;
	endforeach
	;

          
        
	$categoryIdsArray = array_unique ( call_user_func_array ( 'array_merge', $categoryIds ) );
        
       

	$m = 0;
	foreach ( $categoryIdsArray as $categoryId ) :
            
		//$layer = Mage::getModel ( "catalog/layer" );
                $filterableAttributes = $obj->get(\Magento\Catalog\Model\Layer\Category\FilterableAttributeList::class);
                $layerResolver = $obj->get(\Magento\Catalog\Model\Layer\Resolver::class);
                $filterList = $obj->create(\Magento\Catalog\Model\Layer\FilterList::class,
                    [
                        'filterableAttributes' => $filterableAttributes
                    ]
                );        
		//$category = Mage::getModel ( "catalog/category" )->load ( $categoryId );
                $category = $obj->get('Magento\Catalog\Model\Category')->load($categoryId);
//		$layer->setCurrentCategory ( $category );
//		$attributes = $layer->getFilterableAttributes ();
                
                
                $layer = $layerResolver->get();
                $layer->setCurrentCategory($category);
                $filters  = $filterList->getFilters($layer);
                $maxPrice = $layer->getProductCollection()->getMaxPrice();
                $minPrice = $layer->getProductCollection()->getMinPrice();  
                
                
		// $listaProduto = array_unique($listaProduto);

		if ($category->getIsActive () == 1 && $category->getLevel () == 2) :
		
                    /*foreach ( $attributes as $attribute ) {
                    
                    
                                var_dump($attribute->getData()); exit;
                    
				if ($attribute->getAttributeCode () == 'price') {
					$filterBlockName = 'catalog/layer_filter_price';
				} elseif ($attribute->getBackendType () == 'decimal') {
					$filterBlockName = 'catalog/layer_filter_decimal';
				} else {
					$filterBlockName = 'catalog/layer_filter_attribute';
				}

				$result = Mage::app ()->getLayout ()->createBlock ( $filterBlockName )->setLayer ( $layer )->setAttributeModel ( $attribute )->init ();

				$optionArrayDados = '';
				foreach ( $result->getItems () as $option ) {
					if (array_search ( $option->getValue (), array_column ( $filtroAnalise [$attribute->getAttributeCode ()], 'value' ) ) == FAlSE) {
						$filtroAnalise [$attribute->getAttributeCode ()] [$m] ['value'] = $option->getValue ();
						if (strripos ( $option->getLabel (), 'and above' ) == FAlSE) :
							$filtroAnalise [$attribute->getAttributeCode ()] [$m] ['label'] = strip_tags ( $option->getLabel () );
						else :
							$filtroAnalise [$attribute->getAttributeCode ()] [$m] ['label'] = 'Acima de R$' . number_format ( str_replace ( '-', '', $option->getValue () ), 2, ',', '.' );
						endif;
					}
					// $validaFiltro[] = $filtro['value'];
					$m ++;
				}

				if ($filtroAnalise) :
					$filtroArray ['busca'] = $attribute->getAttributeCode ();
					$filtroArray ['option'] = $filtroAnalise;

					$filtroProduto ['filtro'] [] = $filtroArray;
					
				endif;

			} */
                    


                                $i = 0;
                                foreach($filters as $filter)
                                {
                                    //$availablefilter = $filter->getRequestVar(); //Gives the request param name such as 'cat' for Category, 'price' for Price
                                    $availablefilter = (string)$filter->getName(); //Gives Display Name of the filter such as Category,Price etc.
                                    $items = $filter->getItems(); //Gives all available filter options in that particular filter
                                    $filterValues = array();
                                    $j  = 0;
                                    foreach($items as $item)
                                    {


                                        $filterValues[$j]['display'] = strip_tags($item->getLabel());
                                        $filterValues[$j]['value']   = $item->getValue();
                                        $filterValues[$j]['count']   = $item->getCount(); //Gives no. of products in each filter options
                                        $j++;
                                    }
                                    if(!empty($filterValues) && count($filterValues)>1)
                                    {
                                        $filterArray['availablefilter'][$availablefilter] =  $filterValues;
                                    }
                                    $i++;
                                } 
                    if($filterValues) {
                        $filtroProduto ['filtro'] [] = $filtroArray;
                    }
		endif;

	endforeach
	;

        
        
	if ($listaProduto == NULL) :
		ReturnValidation ( 303, "Nenhum Produto Disponível para a Busca." );
	else :

		$listaProduto [] = $filtroProduto;
        
        //var_dump($listaProduto); exit;
        
		Report ( "RETORNO BUSCA => " . var_export ( $listaProduto, true ) );

		echo json_encode ( $listaProduto, JSON_UNESCAPED_UNICODE );
	endif;

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 36 ) == "/shell/ws/integrador/retornoEndereco") :

	$cep = $_GET ['cep'];
	if (! $cep) {
		ReturnValidation ( 303, "Este campo é obrigatório ou CEP incorreto." );
	}

	$filter = new Zend_Filter ();
	$filter->addFilter ( new Zend_Filter_Digits () );
	$cep = $filter->filter ( $_GET ['cep'] );
        
        $urlCepApi 		= "https://viacep.com.br/ws/$cep/json/";
        $consultaCep            = ExecutaWebservice($urlCepApi);
        $consultaCep            = json_decode($consultaCep,true);        
        
        $array                  = array();
        $array['ERROR']         = false;
        $array['logradouro']    = $consultaCep['logradouro'];
        $array['bairro']        = $consultaCep['bairro'];
        $array['cidade']        = $consultaCep['localidade'];
        $array['estado']        = $consultaCep['uf'];
        $array['uf']            = getIdEstado($consultaCep['uf']);
        $array['cep']           = $cep;
        $array['nomeEstado']    = getNomeEstado($consultaCep['uf']);
        
        
	//print Cep::retornaDadosEndereco ( $cep );

        print json_encode($array);
        
elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 43 ) == '/shell/ws/integrador/caracteristicasProduto') :
	$sku = $_GET ['codigoProduto'];


	try {
                $_product = $obj->get('Magento\Catalog\Model\Product') -> loadByAttribute('sku',$sku);
		echo '<div id="principal"  >'. $_product->getDescription () . '</div> <style type="text/css"> body,html {display: table;width:95%;padding-left:5px;padding-right: 5px;}</style>';
	} catch ( Exception $e ) {
                
		ReturnValidation ( 329, "SKU Inválido." );
	}
endif;
function returnReview($productId) {
	// -----------Retorna o Valor da Avaliação dos Produtos.--------------
	global $obj;
        global $currentStoreId;
        
        $rating = $obj->get("Magento\Review\Model\ResourceModel\Review\CollectionFactory");

        $collection = $rating->create()->addStoreFilter(
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
function ReturnValidation($codigo, $mensagem) {
	$dados ['codigoMensagem'] = $codigo;
	$dados ['mensagem'] = $mensagem;

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );
	die ();
}
function Report($texto, $abort = false) {
	$data_log = shell_exec ( 'date +%Y-%m-%d\ %H:%M:%S' );
	$data_log = str_replace ( "\n", "", $data_log );

	$log = fopen ('/var/www/html/logs/ws_integracao.log', "a+" );
	fwrite ( $log, $data_log . " " . $texto . "\n" );
	fclose ( $log );
	if ($abort) {
		exit ( 0 );
	}
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
     
        function parcelasSemJuros()
        {
            global $obj;
            $parcelas = 12;

//          $quote = Mage::getSingleton('checkout/session')->getQuote();
            //$quote = $obj->get('\Magento\Checkout\Model\Cart'); 
            $quote      = $obj->get('Magento\Checkout\Model\Session')->getQuote();
//            $quoteItems = $quote->getAllVisibleItems();
            
            var_dump($quote->getAllVisibleItems()); exit;
            
                $countproduct = 0;
                $parcelamentos = array();

                foreach ($quote->getAllVisibleItems() as $item)
                {
                        //$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                        $product = $obj->get('Magento\Catalog\Model\Product') -> load($item->getProduct()->getId());
                        //$value = $product->getAttributeText('parcelamento_visa');
                        $value = $product->getResource()->getAttribute('parcelamento_visa')->getFrontend()->getValue($product);
                        $parcelamentos[$countproduct] = $value;
                        $countproduct ++;
                }

                array_multisort($parcelamentos, SORT_DESC);

                return $parcelamentos[0];

        }    
    
       function sign($method, $url, $data, $consumerSecret, $tokenSecret)
        {
                $url = urlEncodeAsZend($url);

                $data = urlEncodeAsZend(http_build_query($data, '', '&'));
                $data = implode('&', [$method, $url, $data]);

                $secret = implode('&', [$consumerSecret, $tokenSecret]);

                return base64_encode(hash_hmac('sha1', $data, $secret, true));
        }

        function urlEncodeAsZend($value)
        {
                $encoded = rawurlencode($value);
                $encoded = str_replace('%7E', '~', $encoded);
                return $encoded;
        }

        function ExecutaWebservice($endereco){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endereco);
            curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);

            if (!$retorno = curl_exec($ch)) {
                    Report("Erro ao executar\n".$endereco."\n".curl_error($ch));
                    curl_close($ch);
                    die();
            }

            curl_close($ch);
            return $retorno;
}
        

        /**
         *
         * @param string $uf
         * @return Nome do Estado
         */
        function getNomeEstado($uf)
        {
            
            global $connection;
            
                // Conecta no magento
                $db = $connection;

                // Query para listagem do CEP.
                $sql = "SELECT default_name FROM directory_country_region WHERE country_id = 'BR' AND code = '{$uf}'";

                // Executa a query
                $result = $db->query($sql);

                // Verifica se ouve erros
                if ($result) {

                        // Linhas retornadas da query
                        $rows = $result->fetch(PDO::FETCH_ASSOC);

                        // Retorna o ID da regiao
                        return $rows['default_name'];

                } else {
                        return false;
                }


        }

        
        /**
         *
         * @param string $uf
         * @return string|boolean
         */
        function getIdEstado($uf)
        {
            
                global $connection;
                
                // Conecta no magento
                $db = $connection;

                // Query para listagem do CEP.
                $sql = "SELECT region_id FROM directory_country_region WHERE country_id = 'BR' AND code = '{$uf}'";

                // Executa a query
                $result = $db->query($sql);

                // Verifica se ouve erros
                if ($result) {

                        // Linhas retornadas da query
                        $rows = $result->fetch(PDO::FETCH_ASSOC);

                        // Retorna o ID da regiao
                        return $rows['region_id'];

                } else {
                        return false;
                }
        }        




?>