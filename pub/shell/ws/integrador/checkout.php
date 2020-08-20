<?php

// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

$jsonStr = file_get_contents("php://input"); //read the HTTP body.
$retorno = json_decode($jsonStr, true);

Report("json" . $jsonStr);
Report("Retorno Carrinho Checkout: ".var_export($retorno, true));

$apiUser 		= $retorno["carrinho"]["usuario"];
$apiKey 		= $retorno["carrinho"]["chaveApi"];
$idCliente 		= $retorno["carrinho"]["idCliente"];
$addressId 		= $retorno["carrinho"]["idEndereco"];
$produtos 		= $retorno["carrinho"]["produtos"];
$entrega		= $retorno['carrinho']['frete'][0]['codigo'];
$valorFrete 	= $retorno['carrinho']['frete'][0]['valor'];
$prazoEntrega 	= $retorno['carrinho']['frete'][0]['prazo'];
$tipoPagamento 	= $retorno["carrinho"]["formaPagamento"][0]["tipoPagamento"];
$entreguePor 	= $retorno['carrinho']['frete'][0]['descricao'];

/*
 * Valida a Forma de Pagamento e se o Produto é de Parceiro
 * Se a forma de Pagamento for diferente de Um cartão ou boleto retornará a mensagem que o Pedido não poderá ser processado.
 */
//$parceiro = Mage::getModel('akhilleus/carrier_akhilleusapp')->getValidacaoParceiros($produtos);
$parceiro = FALSE;
enviaMensagem("LOG WS ELETROSOM - CHECKOUT: Iniciando ...");
enviaMensagem("LOG WS ELETROSOM - CHECKOUT: Dados recebidos:* \n\n ". var_export($retorno["carrinho"],true));

if(count($produtos) > 0)
{
	if($tipoPagamento != "pagamento_dois_cartoes")
	{
		try{
			Report(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
			Report("Usuario => ". $apiUser);
			Report("ChaveApi => ".$apiKey);
			
			// Instancia a classe do soap
			$client = new SoapClient(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
			$sessionId = $client->login($apiUser, $apiKey);
			Report("PASSOU PELA AUTENTENTICACAO");
			
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: \n\n Usuário: $apiUser \nChave Api: $apiKey");
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: Autenticação Checkout realizada com sucesso!");
			
			Report("Retorno Carrinho Checkout sessionId: " . var_export($sessionId, true));
			
			//Seta a informação para iniciar os Pedidos com 9 específico para Vendas Aplicativo
			$shoppingCartId = $client->shoppingCartCreate($sessionId, '9');
			
			Report("SHOPPING CART ID => ". $shoppingCartId);
			
			//------------------------- Dados do Cliente ------------------------------------
			$customer = Mage::getModel('customer/customer')->load($idCliente);
			$firstName = $customer->getFirstname();
			$lastName = $customer->getLastname();
			$dob = $customer->getDob();
			$email = $customer->getEmail();
			
			// Set customer, for example guest
			$customerData = array(
					"firstname" => $firstName,
					"lastname" => $lastName,
					"customer_id" => $idCliente,
					"email" => $email,
					"store_id" => "1",
					"mode" => "customer"
			);
			
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: CustomerData: \n\n ". var_export($customerData,true));
			
			
			try{
				$resultCustomerSet = $client->shoppingCartCustomerSet($sessionId, $shoppingCartId, $customerData);
			} catch( Exception $e ){
				ReturnValidation(329, "Preencher Dados do Cliente.");
			}
			
			Report("PASSOU PELOS DADOS CLIENTE");
			
			if(!$resultCustomerSet) {ReturnValidation(336, "Verifique se o cliente está cadastrado na Base de Dados.");}
			//------------------------- Dados do Cliente ------------------------------------
			
			//------------------------- Endereço do Cliente ------------------------------------
			// Set customer addresses, for example guest's addresses
			$arrAddresses = array(
					array(
							"mode" 			=> "billing",
							"address_id" 	=> $addressId
					),
					array(
							"mode" 			=> "shipping",
							"address_id" 	=> $addressId
					)
			);
			
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: CustomerAddress: \n\n ". var_export($arrAddresses,true));
			
			
			
			try{
				$resultCustomerAddresses = $client->shoppingCartCustomerAddresses($sessionId, $shoppingCartId, $arrAddresses);
			} catch( Exception $e ){
				Report("ENDERECO:" . var_export($e,true));
				ReturnValidation(329, "Preencher Dados do Endereço do Cliente.");
			}
			Report("PASSOU PELOS DADOS DE ENDERECO CLIENTE");
			
			if(!$resultCustomerAddresses) {ReturnValidation(336, "Endereço Incorreto.");}
			//------------------------- Endereço do Cliente ------------------------------------
			
			//------------------------- Produtos no Carrinho ------------------------------------
			$p = 0;
			foreach($produtos AS $_produtos):
			$sku = $_produtos['sku'];
			$qtde = $_produtos['qtde'];
			
			Report("Processando produto => " . $sku);
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: PROCESSANDO PRODUTOS  \n\n Produto SKU => ".$sku  ."\n" . "Qtde => ". $qtde);
			
			$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			
			//Verfica o SKU
			if(!$_product){ ReturnValidation(329, "SKU Inválido."); }
			
			//Verifica o Estoque
			Report("Verificando Etoque => " . $sku);
			if(Mage::getModel('akhilleus/carrier_akhilleusapp')->returnEstoque($_product) == 0) { ReturnValidation(308, "Produto sem estoque. SKU: ".$sku); }
			
			//Verifica se a quantidade atende o solicitado pelo cliente
			$estoque = Mage::getModel('akhilleus/carrier_akhilleusapp')->returnQdeEstoque($_product);
			
			enviaMensagem("LOG WS ELETROSOM - CHECKOUT: VALIDAÇÃO DE ESTOQUE  \n\n Produto SKU => ".$sku  ."\n" . "Qtde Estoque  => ". $estoque ."\n" . "Qtde Solicitada:  ".$qtde);
			
			
			if($estoque < $qtde)
			{
				ReturnValidation(309, "A quantidade pedido é superior a que temos em estoque. SKU: ".$sku." - Disponível: ".$estoque." - Quantidade no Carrinho: ".$qtde);
			}
			Report("PASSOU PELA VALIDACAO DE ESTOQUE 2 ");
			$produc[$p]['product_id'] 	= $_product->getId();
			$produc[$p]['sku']			= $sku;
			$produc[$p]['qty'] 			= $qtde;
			
			if($_product->getId())
				$p++;
				endforeach;
				
				// add products into shopping cart
				$arrProducts = array($produc);
				try{
					$resultCartProductAdd = $client->shoppingCartProductAdd($sessionId, $shoppingCartId, $produc);
				} catch( Exception $e ){
					ReturnValidation(329, "Preencher Produto(s).");
				}
				
				Report("PASSOU PELA VALIDACAO DE ADD PRODUTO");
				
				if(!$resultCartProductAdd) {ReturnValidation(336, "Não foi Possível Inserir os Produtos no Carrinho.");}
				//------------------------- Produtos no Carrinho ------------------------------------
				
				//------------------------- Frete no Carrinho ------------------------------------
				$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
				Report("METODOS: ");
				$shipMethodCollection = new Varien_Data_Collection();
				foreach ($methods as $shippingCode => $shippingModel)
				{
					$shippingTitle = Mage::getStoreConfig('carriers/'.$shippingCode.'/title');
					$shipMethod = new Varien_Object(array(
							'code' => $shippingCode,
							'title' => $shippingTitle,
					));
					Report($shippingCode .'->'. $shippingTitle);
					$shipMethodCollection->addItem($shipMethod);
				}
				
				
				
				$shoppingCartShippingList = $client->shoppingCartShippingList($sessionId, $shoppingCartId);
				Report("RETORNO FRETE => " . var_export($shoppingCartShippingList,TRUE));
				
				try {
					$resultShippingMethod = $client->shoppingCartShippingMethod($sessionId, $shoppingCartId, $entrega);
				}catch (Exception $e){
					Report("erro metodo de entrega" . var_export($e,true));
				}
				
				Report("RETORNO METHOD => ". $resultShippingMethod);
				
				
				if(!$resultShippingMethod) {ReturnValidation(337, "Método de Entrega Inválido.");}
				Report("PASSOU PELO METODO DE ENTREGA");
				//------------------------- Frete no Carrinho ------------------------------------
				
				//------------------------- Cupom de Desconto ------------------------------------
				// add coupon
				$couponCode = $retorno['carrinho']['cupomDesconto'];
				
				if($couponCode) {
					enviaMensagem("LOG WS ELETROSOM - CHECKOUT: VALIDAÇÃO DE CUPOM  \n\n Cupom Desconto => " . $couponCode );
					try {
						$resultCartCouponAdd = $client->shoppingCartCouponAdd($sessionId, $shoppingCartId, $couponCode);
						if(!$resultCartCouponAdd) {ReturnValidation(306, "Cupom de Desconto Inválido ou Excedido.");}
					} catch (Exception $e) {
						ReturnValidation(306, "Cupom de Desconto Inválido ou Excedido.");
					}
				}
				Report("PASSOU PELO CUPOM");
				
				// remove coupon
				//$resultCartCouponRemove = $client->call($sessionId, "cart_coupon.remove", array($shoppingCartId));
				//------------------------- Cupom de Desconto ------------------------------------
				
				
				//------------------------- Forma de Pagamento no Carrinho ------------------------------------
				
				$cartao1 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao']);
				$cartao2 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao1']);
				
				Report("NUMERO CARTAO => " . $cartao1);
				Report("NUMERO CARTAO1 => " . $cartao2);
				
				$dataValidade = explode("/", $retorno['carrinho']['formaPagamento'][0]['dataValidadeCartao']);
				$mes1 = $dataValidade[0];
				$ano1 = $dataValidade[1];
				
				Report("DATA VALIDA MES CARTAO 1 => " . $mes1);
				Report("DATA VALIDA ANO CARTAO 1 => " . $ano1);
				
				$dataValidade = explode("/", $retorno['carrinho']['formaPagamento'][0]['dataValidadeCartao1']);
				$mes2 = $dataValidade[0];
				$ano2 = $dataValidade[1];
				
				Report("DATA VALIDA MES CARTAO 2 => " . $mes2);
				Report("DATA VALIDA ANO CARTAO 2 => " . $ano2);
				
				$valorTotalCartao1 = trim(str_replace("R$", "", $retorno['carrinho']['formaPagamento'][0]['valorTotal'])); //Valor Total da Compra
				$valorTotalCartao2 = trim(str_replace("R$", "", $retorno['carrinho']['formaPagamento'][0]['valorTotal1'])); //Valor Digitado pelo Cliente para Pagamento do 1° cartão
				
				$valorTotal2 = $valorTotalCartao1 - $valorTotalCartao2; //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
				
				Report("TIPO PAGAMENTO => " . $tipoPagamento);
				
				if($tipoPagamento == "boleto_bradesco"):
				//set payment method
				$paymentMethod = array("method" => "boleto_bradesco");
				$method = 'boleto_bradesco';
				elseif($tipoPagamento == "pagamento_um_cartao"):
				
				$paymentMethod = array(
						'method' 			=> 'mundipagg',
						'cc_cid' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao'],
						'cc_valor' 			=> $valorTotalCartao1,
						'cc_parcelamento' 	=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
						'cc_parcelas' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
						'cc_type' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira'],
						'cc_number' 		=> $cartao1,
						'cc_owner' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePortador'],
						'cc_exp_month' 		=> $mes1,
						'cc_exp_year' 		=> $ano1);
				$method = 'mundipagg';
				
				elseif($tipoPagamento == "venda_funcionario"):
				
				//SET PAYMENT METHOD
				$paymentMethod = array(
						'method' 			=> 'mundipaggsalesofficer',
						'cc_valor' 			=> $valorTotalCartao1,
						'cc_parcelamento'	=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
						'nome_pai' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePai'],
						'nome_mae' 			=> $retorno['carrinho']['formaPagamento'][0]['nomeMae'],
						'expedicao_rg' 		=> $retorno['carrinho']['formaPagamento'][0]['expedicaoRg'],
						'renda_mensal' 		=> $retorno['carrinho']['formaPagamento'][0]['rendaMensal']);
				$method = 'mundipaggsalesofficer';
				else:
				ReturnValidation(331, "Forma de Pagamento indisponível.");
				endif;
				Report("PASSOU PELA FORMA DE PAGAMENTO 1 ");
				try {
					$shoppingCartPaymentList = $client->shoppingCartPaymentList($sessionId, $shoppingCartId);
					foreach($shoppingCartPaymentList AS $_shoppingCartPaymentList):
					$metodos[] = $_shoppingCartPaymentList->code;
					endforeach;
				} catch (Exception $e) {
					ReturnValidation(306, "Preencha as Informações de Pagamento.");
				}
				
				enviaMensagem("LOG WS ELETROSOM - CHECKOUT: FORMA DE PAGAMENTO  \n\n Método => " . $method ."\n PaymentData => " . var_export($paymentMethod,true));
				
				Report("PASSOU PELA FORMA DE PAGAMENTO 2 " . $method);
				
				Report("METODOS DE PAGAMENTO =>  " . var_export($metodos,true));
				
				
				if(in_array($method, $metodos)){
					Report("PASSOU PELA VALIDACAO DE METODOS =>  " . var_export($paymentMethod,true));
					
					try {
						$resultPaymentMethod = $client->shoppingCartPaymentMethod($sessionId, $shoppingCartId, $paymentMethod);
					}catch(Exception $e) {
						Report(" ERRO AO INSERIR PAGAMENTO=>  " . var_export($resultPaymentMethod,true));
						ReturnValidation(333, "Não foi possível concluir o Pagamento. Favor tentar novamente ou entre em contato com a operadora do cartão de crédito para obter mais informações.");
					}
					
					Report('Resumo pagamento inserido: ' . var_export($resultPaymentMethod,true));
				} else {
					ReturnValidation(331, "Forma de Pagamento indisponível.");
				}
				Report("PASSOU PELA FORMA DE PAGAMENTO 3 ");
				//------------------------- Produtos no Carrinho ------------------------------------
				Report(" SESSION ID => ". $sessionId);
				Report(" SHOPPING ID => ". $shoppingCartId);
				
				//Report("Retorno ORDER ID => ". var_export($client->shoppingCartOrder($sessionId, $shoppingCartId, null, null),true));
				
				Report("CRIAR PEDIDO");
				// create order
				$orderId = $client->shoppingCartOrder($sessionId, $shoppingCartId, null, null);
				
				enviaMensagem("LOG WS ELETROSOM - CHECKOUT: PEDIDO FINALIZADO COM SUCESSO!  \n\n Pedido Magento => " . $orderId);
				
				Report(" ORDER ID => ". $orderId);
				
				Report("PASSOU PELA CRIACAO DO PEDIDO ");
				if(!$orderId) {
					ReturnValidation(334, "Não foi possível concluir a Compra.");
				}else {
					$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
					$status = $order->getStatus();
					Report(" STATUS => ". $status);
					Report(" tipo de pagamento => ". $tipoPagamento);
					
					// Força o envio do e-mail para o cliente - Alterado em 23/04/2019 - Amaro Junior
					$order->sendNewOrderEmail();
					$status = 'processing';
					
					if($status == 'canceled' && ($tipoPagamento == "pagamento_um_cartao" || $tipoPagamento == "pagamento_dois_cartao")){
						$mensagem = 'Não foi possível concluir o Pagamento. Favor tentar novamente ou entre em contato com a operadora do cartão de crédito para obter mais informações';
					}elseif($tipoPagamento == "boleto_bradesco"){
						$mensagem = 'Atenção: Opção válida apenas para pagamento à vista. O boleto deve ser impresso após a finalização do pedido. Este boleto tem validade de 2 dias úteis após a finalização da compra.';
					}
					
					$shoppingOrder['retorno']['numeroPedido'] = $orderId;
					$shoppingOrder['retorno']['status'] = $status;
					$shoppingOrder['retorno']['mensagem'] = $mensagem;
					$shoppingOrder['retorno']['entreguePor'] = $entreguePor;
					$shoppingOrder['retorno']['entrega'] = "Serviço de Entrega - ".$entreguePor." - Prazo: ".$prazoEntrega;
					$shoppingOrder['retorno']['prazo'] = $prazoEntrega;
					
					if($tipoPagamento == 'boleto_bradesco'):
					$shoppingOrder['retorno']['boleto'] = Mage::getUrl('',array('_secure'=>true)).'boleto/boleto_bradesco.php?cod='.$order->getIncrementId().$order->getCustomerId().'&app=1';
					endif;
					
					if($tipoPagamento == 'venda_funcionario'):
					$shoppingOrder['retorno']['venda_funcionario'] = Mage::getUrl('',array('_secure'=>true)).'contrato_venda_funcionario/'.$order->getQuoteId();
					endif;
					
					enviaMensagem("FINALIZOU O PEDIDO  =>  " . var_export($shoppingOrder,true));
					echo stripslashes(json_encode($shoppingOrder, JSON_UNESCAPED_UNICODE));
					Report("FINALIZOU O PEDIDO  =>  " . var_export($shoppingOrder,true));
					
				}
				
		} catch( Exception $e ){
			Report("RETORNO ERRO => ".  var_export($e,true));
			ReturnValidation($e->faultcode, $e->faultstring);
		}
	} elseif($tipoPagamento == "pagamento_dois_cartoes") {
		
		$storeId = 9;
		$customer = Mage::getModel('customer/customer')->load($idCliente);
		$defaultBilling  = $customer->getDefaultBillingAddress();
		
		//$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $produtos[0]['sku']);
		
		//var_dump($addressId);exit;
		// Pega o ID do site
		$websiteId = Mage::app()->getWebsite()->getId();
		
		//pega ip do cliente
		$customer_ip = Mage::helper('core/http')->getRemoteAddr();
		
		$address = Mage::getModel('customer/address')->load($addressId);
		
		//var_dump((array)$address->get);exit;
		
		// Dados do Endereço
		$street 	= $address->getStreet();
		$endereco	= $address->getStreet1();
		$numero		= $address->getStreet2();
		$bairro		= $address->getStreet3();
		$compl		= $address->getStreet4();
		$cidade		= $address->getCity();
		$cep 		= $address->getPostcode();
		$tel 		= $address->getTelephone();
		$cel 		= $address->getFax();
		$uf 		= $address->getRegion();
		$region_id 	= $address->getRegionId();
		$count 		= 0;
		
		$cartao1 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao']);
		$cartao2 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao1']);
		
		Report("NUMERO CARTAO => " . $cartao1);
		Report("NUMERO CARTAO1 => " . $cartao2);
		
		$dataValidade = explode("/", $retorno['carrinho']['formaPagamento'][0]['dataValidadeCartao']);
		$mes1 = $dataValidade[0];
		$ano1 = $dataValidade[1];
		
		Report("DATA VALIDA MES CARTAO 1 => " . $mes1);
		Report("DATA VALIDA ANO CARTAO 1 => " . $ano1);
		
		$dataValidade = explode("/", $retorno['carrinho']['formaPagamento'][0]['dataValidadeCartao1']);
		$mes2 = $dataValidade[0];
		$ano2 = $dataValidade[1];
		
		Report("DATA VALIDA MES CARTAO 2 => " . $mes2);
		Report("DATA VALIDA ANO CARTAO 2 => " . $ano2);
		
		$valorTotalCartao1 = trim(str_replace("R$", "", $retorno['carrinho']['formaPagamento'][0]['valorTotal'])); //Valor Total da Compra
		$valorTotalCartao2 = trim(str_replace("R$", "", $retorno['carrinho']['formaPagamento'][0]['valorTotal1'])); //Valor Digitado pelo Cliente para Pagamento do 1° cartão
		
		$valorTotal2 = $valorTotalCartao1 - $valorTotalCartao2; //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
		
		Report("TIPO PAGAMENTO => " . $tipoPagamento);
		
		// initialize sales quote object
		$quote = Mage::getModel('sales/quote')->setStoreId(9);
		
		// assign the customer to quote
		$quote->assignCustomer($customer);
		
		// set currency for the quote
		$quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());
		
		foreach ($produtos as $item)
		{
			
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
			
			$_product = Mage::getModel('catalog/product')->load($product->getId());
			
			
			$quote->addProduct($_product, (int)$item['qtde']);
			//var_dump($quote->getId());exit;
			
		}
		
		// set Billing Address
		
		$billingAddressData = $quote->setBillingAddress(Mage::getModel('customer/address')->load($addressId));
		
		//var_dump($billingAddressData);
		
		
		$shippingAddressData = $quote->getShippingAddress(Mage::getModel('customer/address')->load($addressId));
		
		$shippingAddressData->setCollectShippingRates(true)
		->collectShippingRates();
		
		// set shipping method and payment method on the quote
		$shippingAddressData->setShippingMethod($entrega)
		->setPaymentMethod($tipoPagamento);
		
		$paymentMethod = array(
				'method' 			=> 'mundipaggtwocards',
				'method1' 			=> 'mundipaggstonetwocards',
				'cc_valor' 			=> $valorTotalCartao1, //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
				'cc_parcelas' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
				'cc_type' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira'],
				'cc_number' 		=> $cartao1,
				'cc_owner' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePortador'],
				'cc_exp_month' 		=> $mes1,
				'cc_exp_year' 		=> $ano1,
				'cc_cid' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao'],
				'cc_valor1' 		=> $valorTotalCartao2,
				'cc_parcelas1' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'],
				'cc_type1' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira1'],
				'cc_number1' 		=> $cartao2,
				'cc_owner1' 		=> $retorno['carrinho']['formaPagamento'][0]['nomePortador1'],
				'cc_exp_month1' 	=> $mes2,
				'cc_exp_year1' 		=> $ano2,
				'cc_cid1' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1'])
				;
		
		$payment_one = array(
				'method' 			=> 'mundipaggtwocards',
				'cc_valor' 			=> $valorTotalCartao1, //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
				'cc_parcelas' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
				'cc_type' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira'],
				'cc_number' 		=> $cartao1,
				'cc_owner' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePortador'],
				'cc_exp_month' 		=> $mes1,
				'cc_exp_year' 		=> $ano1,
				'cc_cid' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao'],
				'tipo_cartao' => 1,
		);
		
		$quote->getPayment()->importData($payment_one);
		
		$payment_two = array(
				'cc_valor' 			=> $valorTotalCartao2,
				'cc_parcelas' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'],
				'cc_type' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira1'],
				'cc_number' 		=> $cartao2,
				'cc_owner' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePortador1'],
				'cc_exp_month' 		=> $mes2,
				'cc_exp_year' 		=> $ano2,
				'cc_cid' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1'],
				'tipo_cartao' 		=> 2,
		);
		
		$quote->getPayment()->importData($payment_two);
		//Mage::getSingleton('checkout/session')->setData('onestepcheckout_order_data', $payment_two);
		
		// Set payment method for the quote
		$quote->getPayment()->importData($payment_one);
		
		
		$paymentMethod1['method'][0] = 'mundipaggtwocards';
		$paymentMethod1['cc_valor'][0] = $valorTotalCartao1; //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
		$paymentMethod1['cc_parcelas'][0] = $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'];
		$paymentMethod1['cc_parcelamento'][0] = $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'];
		$paymentMethod1['cc_type'][0] = $retorno['carrinho']['formaPagamento'][0]['bandeira'];
		$paymentMethod1['cc_number'][0] = $cartao1;
		$paymentMethod1['cc_owner'][0] = $retorno['carrinho']['formaPagamento'][0]['nomePortador'];
		$paymentMethod1['cc_exp_month'][0] =$mes1;
		$paymentMethod1['cc_exp_year'][0] = $ano1;
		$paymentMethod1['cc_cid'][0] = $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1'];
		$paymentMethod1['cc_valor'][1] = $valorTotalCartao2;
		$paymentMethod1['cc_parcelas'][1] = $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'];
		$paymentMethod1['cc_parcelamento'][1] = $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'];
		$paymentMethod1['cc_type'][1] = $retorno['carrinho']['formaPagamento'][0]['bandeira1'];
		$paymentMethod1['cc_number'][1] = $cartao2;
		$paymentMethod1['cc_owner'][1] = $retorno['carrinho']['formaPagamento'][0]['nomePortador1'];
		$paymentMethod1['cc_exp_month'][1] = $mes2;
		$paymentMethod1['cc_exp_year'][1] = $ano2;
		$paymentMethod1['cc_cid'][1] = $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1'];
		
		Mage::getSingleton('core/session')->setPaymentTwoCards($paymentMethod1);
		
		
		try {
			// collect totals & save quote
			$quote->collectTotals()->save();
			
			// create order from quote
			$service = Mage::getModel('sales/service_quote', $quote);
			$service->submitAll();
			$increment_id = $service->getOrder()->getRealOrderId();

			if(!$increment_id) {
				ReturnValidation(334, "Não foi possível concluir a Compra.");
			}else {
				$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
				$status = $order->getStatus();
				Report(" STATUS => ". $status);
				Report(" tipo de pagamento => ". $tipoPagamento);
				
				// Força o envio do e-mail para o cliente - Alterado em 23/04/2019 - Amaro Junior
				$order->sendNewOrderEmail();
				
				if($status == 'canceled'){
					$mensagem = 'Não foi possível concluir o Pagamento. Favor tentar novamente ou entre em contato com a operadora do cartão de crédito para obter mais informações';
				}else{
					$mensagem = '';
				}
				
				$shoppingOrder['retorno']['numeroPedido'] = $increment_id;
				$shoppingOrder['retorno']['status'] = $status;
				$shoppingOrder['retorno']['mensagem'] = $mensagem;
				$shoppingOrder['retorno']['entreguePor'] = $entreguePor;
				$shoppingOrder['retorno']['entrega'] = "Serviço de Entrega - ".$entreguePor." - Prazo: ".$prazoEntrega;
				$shoppingOrder['retorno']['prazo'] = $prazoEntrega;
				
				enviaMensagem("FINALIZOU O PEDIDO  =>  " . var_export($shoppingOrder,true));
				echo stripslashes(json_encode($shoppingOrder, JSON_UNESCAPED_UNICODE));
				Report("FINALIZOU O PEDIDO  =>  " . var_export($shoppingOrder,true));
				
			}
			
		} catch (Exception $e) {
			Mage::logException($e);
			
			var_dump($e);
		}
		
		exit();
	
	}else{
		ReturnValidation(331, "Forma de Pagamento indisponível para Produto Selecionado.");
	}
} else {
	echo ReturnValidation(325, 'Por favor, preencha os campos obrigatórios.');
}

function Report($texto, $abort = false)
{
	$data_log = shell_exec('date +%Y-%m-%d\ %H:%M:%S');
	$data_log = str_replace("\n", "", $data_log);
	
	$log = fopen(Mage::getStoreConfig('erp/frontend/url_logs').'ws_integracao.log', "a+");
	fwrite($log, $data_log . " " . $texto . "\n");
	fclose($log);
	if ($abort) {
		exit(0);
	}
}

function ReturnValidation($codigo, $mensagem)
{
	$dados['codigoMensagem'] = $codigo;
	$dados['mensagem'] = $mensagem;
	
	echo $mensagem = json_encode($dados, JSON_UNESCAPED_UNICODE);
	Report($mensagem);
	die();
}


function enviaMensagem($log,$phone=null) {
	
	if(!$phone) {
		$phone = '5534998095659,5531996682002,919876434422,553491003815';
	}
	
	ExecutaWebservice("http://telegram/ws_telegram.php?phone=".$phone."&message=".urlencode($log));
	
	
}


function ExecutaWebservice($endereco)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $endereco);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	
	if (! $retorno = curl_exec($ch)) {
		Report("Erro ao executar\n" . $endereco."\n" . curl_error($ch));
		curl_close($ch);
		die();
	}
	
	curl_close($ch);
	return $retorno;
}
