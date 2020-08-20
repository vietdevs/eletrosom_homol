<?php 

// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

$jsonStr = file_get_contents("php://input"); //read the HTTP body.
$retorno = json_decode($jsonStr, true);

Report("Retorno Carrinho Checkout: ".var_export($retorno, true));

$apiUser	 	= $retorno["carrinho"]["usuario"];
$apiKey 		= $retorno["carrinho"]["chaveApi"];
$idCliente	 	= $retorno["carrinho"]["idCliente"];
$addressId 		= $retorno["carrinho"]["idEndereco"];
$produtos 		= $retorno["carrinho"]["produtos"];
$entrega 		= $retorno['carrinho']['frete'][0]['codigo'];
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

if($parceiro == FALSE)
{
	try{
		Report(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
		Report("Usuario => ". $apiUser);
		Report("ChaveApi => ".$apiKey);
			
			
		// Instancia a classe do soap
		$client = new SoapClient(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
		//$client = new SoapClient('https://carrinhohomologacao.eletrosom.com/index.php/api/v2_soap?wsdl=1');
		Report("PASSOU PELA AUTENTENTICACAO");
		$sessionId = $client->login($apiUser, $apiKey);
		

				
		// Pega o ID do site
		$websiteId = 1;
			
		// Pega o ID da Loja
		$storeId = 9;

		$websiteId = Mage::app()->getWebsite()->getId();
		$store = 9;
		// Start New Sales Order Quote
		$quote = Mage::getModel('sales/quote')->setStoreId($store);
		
		// Set Sales Order Quote Currency
		$quote->setCurrency('BRL');
		$customer = Mage::getModel('customer/customer')
		->setWebsiteId($websiteId)
		->load($idCliente);

		
		// Assign Customer To Sales Order Quote
		$quote->assignCustomer($customer);
		
		// Configure Notification
		$quote->setSendCconfirmation(1);
		foreach ($produtos as $item)
		{
			Report("Processando produto => ". $item['sku']);
			
			$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
			$product = Mage::getModel('catalog/product')->load($_product->getId());
			$quote->addProduct($product, new Varien_Object(array('qty' => 1)));
		}
		
		// Set Sales Order Billing Address
		$billingAddress = $quote->getBillingAddress()->addData(array(
				'customer_address_id' => $addressId

		));
		
		// Set Sales Order Shipping Address
		$shippingAddress = $quote->getShippingAddress()->addData(array(
				'customer_address_id' => $addressId
		));
		if ($shipprice == 0) {
			$shipmethod = $entrega;
		}
		
		//------------------------- Forma de Pagamento no Carrinho ------------------------------------
		$cartao1 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao']);
		$cartao2 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao1']);
			
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
		session_start();
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
		
		elseif($tipoPagamento == "pagamento_dois_cartoes"):
			
			session_start();
			$paymentMethod = array(
					'method' 			=> 'mundipaggtwocards',
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
					'cc_cid1' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1']);
			$method = 'mundipaggtwocards';
		
		elseif($tipoPagamento == "venda_funcionario"):
		
		// set payment method
		$paymentMethod = array(
				'method' 			=> 'mundipaggsalesofficer',
				'cc_parcelamento'	=> $retorno['carrinho']['formaPagamento'][0]['valorTotal'],
				'nome_pai' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePai'],
				'nome_mae' 			=> $retorno['carrinho']['formaPagamento'][0]['nomeMae'],
				'expedicao_rg' 		=> $retorno['carrinho']['formaPagamento'][0]['expedicaoRg'],
				'renda_mensal' 		=> $retorno['carrinho']['formaPagamento'][0]['rendaMensal']);
		$method = 'venda_funcionario';
		else:
		ReturnValidation(331, "Forma de Pagamento indisponível.");
		endif;		
		
		Report("TIPO PAGAMENTO valores => " . var_export($paymentMethod,true));

		// Collect Rates and Set Shipping & Payment Method
		$shippingAddress->setCollectShippingRates(true)
		->collectShippingRates()
		->setShippingMethod($entrega)
		->setPaymentMethod($method);

		
		// Set Sales Order Payment
		$quote->getPayment()->importData($paymentMethod);
		
		$couponCode = $retorno['carrinho']['cupomDesconto'];
		
		if($couponCode){
			Report("Cupom de desconto => " . $couponCode);
			
			$oCoupon = Mage::getModel('salesrule/coupon')->load(trim($couponCode), 'code');
			$oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
			
			if($oRule->getRuleId() && $oRule->getRuleId() > 0){
				try{
					//$quote = $this->_getQuote($quoteid,Mage::app()->getStore()->getStoreId());
					$quote->setCouponCode($couponCode);
					$quote->setTotalsCollectedFlag(false)->collectTotals();
					$quote->collectTotals();
					$quote->save();
				}catch (Exception $e){
					echo $e->getMessage();
				}
			}else{
				Report("Cupom nao existe");
			}			
			
			
			//$quote->setCouponCode($couponCode);
		}
		
		// Collect Totals & Save Quote
		$quote->collectTotals()->save();
		
		try {
			// Create Order From Quote
			$service = Mage::getModel('sales/service_quote', $quote);
			$service->submitAll();
			$increment_id = $service->getOrder()->getRealOrderId();
		}
		catch (Exception $ex) {
			echo $ex->getMessage();
		}
		catch (Mage_Core_Exception $e) {
			echo $e->getMessage();
		}
		
		// Envia email do pedido se o pedido nao estiver cancelado
		$service->getOrder()->sendNewOrderEmail();
		//$service->getOrder()->setPrazo($prazoEntrega)->save();
		// Resource Clean-Up
		$quote = $customer = $service = null;
		
		// Finished
		Report("pedido magento " . $increment_id);

		
		
		
		
		
		
		
exit;		
		
		
		
		// Pega os dados da Sessão
		//$checkout = Mage::getSingleton('checkout/session')->getQuote();
		$customer = Mage::getModel('customer/customer');
		$customer->setWebsiteId($websiteId);
		$customer->load($idCliente);
		$firstName 	= $customer->getFirstname();
		$lastName 	= $customer->getLastname();
		$dob 		= $customer->getDob();
		$email 		= $customer->getEmail();
		
		// Carrega o model da Transação para salvar o Order
		$transaction = Mage::getModel('core/resource_transaction');
		
		// Reservao o numero do pedido a ser criado
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);			

		Report("Pedido Reservado => ".$reservedOrderId);
		
		
		// Pega o IP do cliente para gravar no Order
		$customer_ip 		= null;
		$x_forwarded_for	= null;
			

		
		// Add IP, Parceiro, Link Boleto e reserva numero do pedido
		$order = Mage::getModel('sales/order')->setState('new')
		//->setXForwardedFor($x_forwarded_for) 	// Ip do cliente
		//->setRemoteIp($customer_ip) // Ip do cliente
		//->setPartner($partner) // Seta o parceiro
		//->setPartnerLinkBoleto($partner_link_boleto) // Seta o parceiro
		//->setPartnerBoletoVenc($partner_venc_boleto) // Seta o vencimento
		->setIncrementId($reservedOrderId); // Increment_id
	

		//------------------------- Forma de Pagamento no Carrinho ------------------------------------
		$cartao1 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao']);
		$cartao2 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao1']);
			
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
		

		// Pagamento 	
		if($tipoPagamento == "boleto_bradesco")
		{
			$method = 'boleto_bradesco';
		
			$orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)
			->setCustomerPaymentId(0)
			->setMethod($method)
			->setPo_number(' - ');
		
		} elseif($tipoPagamento == "pagamento_um_cartao") {			
			
			$method				= 'mundipagg'; // Metodo
			$cc_number			= $cartao1; // Metodo
			$cc_owner			= $retorno['carrinho']['formaPagamento'][0]['nomePortador']; // Nome Titular
			$cc_type			= $retorno['carrinho']['formaPagamento'][0]['bandeira']; // Bandeira Cartao
			$cc_parcelamento	= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao']; // Parcelamento
			$cc_exp_month		= $mes1; // Validade Mes
			$cc_exp_year		= $ano1;  // Validade Ano
			$cc_parcelas 		= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'];
			$cc_valor			= $valorTotalCartao1; 
			$additional_data	= $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao']; // Codigo Segurança
		
			Mage::log(" RETORNO JSON method: ". var_export($method, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_number: ". var_export($cc_number, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_owner: ". var_export($cc_owner, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_type: ". var_export($cc_type, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_parcelamento: ". var_export($cc_parcelamento, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_month: ". var_export($cc_exp_month, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_year: ". var_export($cc_exp_year, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_cid: ". var_export($additional_data, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
		
			$status = "processing";
		
			$orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)
			->setCustomerPaymentId(0)
			->setMethod($method)
			->setCcNumber($cc_number)
			->setCcNumberEnc($cc_number)
			->setCcOwner($cc_owner)
			->setCcType($cc_type)
			->setCcParcelamento($cc_parcelamento)
			->setCcParcelas($cc_parcelamento)
			->setCcExpMonth($cc_exp_month)
			->setCcExpYear($cc_exp_year)
			->setAdditionData($additional_data)
			->setCcParcelas($cc_parcelas)
			->setCcValor($cc_valor)
			->setCcLast4(substr($cc_number,-4));
		
		} elseif($tipoPagamento == "pagamento_dois_cartoes") {			
			
			$method				= 'mundipaggtwocards'; // Metodo
			$cc_number			= $cartao1; // Metodo
			$cc_owner			= $retorno['carrinho']['formaPagamento'][0]['nomePortador']; // Nome Titular
			$cc_type			= $retorno['carrinho']['formaPagamento'][0]['bandeira']; // Bandeira Cartao
			$cc_parcelamento	= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao']; // Parcelamento
			$cc_exp_month		= $mes1; // Validade Mes
			$cc_exp_year		= $ano1;  // Validade Ano
			$cc_parcelas 		= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'];
			$cc_valor			= $valorTotalCartao1;
			$additional_data	= $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao']; // Codigo Segurança
			
			$cc_number1			= $cartao2; // Metodo
			$cc_owner1			= $retorno['carrinho']['formaPagamento'][0]['nomePortador1']; // Nome Titular
			$cc_type1			= $retorno['carrinho']['formaPagamento'][0]['bandeira1']; // Bandeira Cartao
			$cc_parcelamento1	= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1']; // Parcelamento
			$cc_exp_month1		= $mes2; // Validade Mes
			$cc_exp_year1		= $ano2;  // Validade Ano
			$cc_parcelas1 		= $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'];
			$cc_valor1			= $valorTotalCartao2;
			$additional_data1	= $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1']; // Codigo Segurança			
			
			Mage::log(" RETORNO JSON method: ". var_export($method, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_number: ". var_export($cc_number, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_owner: ". var_export($cc_owner, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_type: ". var_export($cc_type, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_parcelamento: ". var_export($cc_parcelamento, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_month: ". var_export($cc_exp_month, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_year: ". var_export($cc_exp_year, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_cid: ". var_export($additional_data, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			
			Mage::log(" RETORNO JSON cc_number1: ". var_export($cc_number1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_owner1: ". var_export($cc_owner1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_type1: ". var_export($cc_type1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_parcelamento1: ". var_export($cc_parcelamento1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_month1: ". var_export($cc_exp_month1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_exp_year1: ". var_export($cc_exp_year1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			Mage::log(" RETORNO JSON cc_cid1: ". var_export($additional_data1, TRUE), Zend_log::INFO, "formaPagamentoApp.log");
			
			$status = "processing";
			
			$orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)
			->setCustomerPaymentId(0)
			->setMethod($method)
			->setCcNumberEnc($cc_number)
			->setCcOwner($cc_owner)
			->setCcType($cc_type)
			->setCcParcelamento($cc_parcelamento)
			->setCcParcelas($cc_parcelamento)
			->setCcExpMonth($cc_exp_month)
			->setCcExpYear($cc_exp_year)
			->setAdditionData($additional_data)
			->setCcParcelas($cc_parcelas)
			->setCcValor($cc_valor)
			->setCcLast4(substr($cc_number,-4))
			->setCcNumberEnc($cc_number1)
			->setCcOwner1($cc_owner1)
			->setCcType1($cc_type1)
			->setCcParcelamento1($cc_parcelamento1)
			->setCcParcelas1($cc_parcelamento1)
			->setCcExpMonth1($cc_exp_month1)
			->setCcExpYear1($cc_exp_year1)
			->setAdditionData1($additional_data1)
			->setCcParcelas1($cc_parcelas1)
			->setCcValor1($cc_valor1)
			->setCcLast41(substr($cc_number1,-4));
						
		} elseif($tipoPagamento == "venda_funcionario") {
			
			$method 			= 'mundipaggsalesofficer';
			$cc_parcelamento	= $retorno['carrinho']['formaPagamento'][0]['valorTotal'];
			$nome_pai 			= $retorno['carrinho']['formaPagamento'][0]['nomePai'];
			$nome_mae			= $retorno['carrinho']['formaPagamento'][0]['nomeMae'];
			$expedicao_rg 		= $retorno['carrinho']['formaPagamento'][0]['expedicaoRg'];
			$renda_mensal 		= $retorno['carrinho']['formaPagamento'][0]['rendaMensal'];
			
			$orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)
			->setCustomerPaymentId(0)
			->setMethod($method)
			->setNomePai($nome_pai)
			->setNomeMae($nome_mae)
			->setExpedicaoRg($expedicao_rg)
			->setRendaMensal($renda_mensal)
			->setCcParcelamento($cc_parcelamento);
		} else {
			ReturnValidation(331, "Forma de Pagamento indisponível.");
		}

		
		$order->setPayment($orderPayment);
			
		$billingAddress = Mage::getModel('sales/order_address');
		$shippingAddress = Mage::getModel('sales/order_address');
		
		$order->setStoreId($storeId)
		->setQuoteId(0)
		->setGlobal_currency_code('BRL')
		->setBase_currency_code('BRL')
		->setStore_currency_code('BRL')
		->setOrder_currency_code('BRL')
		->setStatus($status)
		->setState($status);
		
			
		// set Customer data
		$order->setCustomer_email($customer->getEmail())
		->setCustomerFirstname($customer->getFirstname())
		->setCustomerLastname($customer->getLastname())
		->setCustomerGroupId($customer->getGroupId())
		->setCustomerRg($customer->getRg())
		->setCustomerCpf($customer->getCpf())
		->setCustomerApelido($customer->getApelido())
		->setCustomerProfissao($customer->getProfissao())
		->setCustomerGender($customer->getGender())
		->setCustomerEstadoCivil($customer->getEstadoCivil())
		->setCustomerDob($customer->getDob())
		->setCustomer_is_guest(0)
		->setCustomer($customer);
			
		
		$customerAddress = Mage::getModel('customer/address')->load($addressId); //insert cust ID		
		$_customerAddress = $customerAddress->toArray();
		
		Report("ENDEREÇO CLIENTE => " . var_export($customerAddress_1,true));

		$customerAddresss = array();
		#loop to create the array
		
		foreach ($_customerAddress as $key=>$address)
		{
			$customerAddresss[$key] = $address;
		}
		#displaying the array
		Report("Endereco => ". var_export($customerAddresss,true));
		
		///exit;
		
		
		
		// set Billing Address
		$billingAddress->setStoreId($storeId)
		->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
		//->setPrefix('mr')
		->setFirstname($customerAddresss['firstname'])
		->setLastname($customerAddresss['lastname'])
		//->setCompany('company')
		->setStreet($customerAddresss['street'])
		->setCity($customerAddresss['city'])
		->setCountry_id('BR')
		->setRegionId($customerAddresss['region_id'])
		->setRegion($customerAddresss['region'])
		->setTelephone($customerAddresss['telephone'])
		->setFax($customerAddresss['fax'])
		->setPostcode($customerAddresss['postcode']);
			
		$order->setBillingAddress($billingAddress);

		
		
		$shippingAddress->setStoreId($storeId)
		->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
		->setFirstname($customerAddresss['firstname'])
		->setLastname($customerAddresss['lastname'])
		//->setCompany('company')
		->setStreet($customerAddresss['street'])
		->setCity($customerAddresss['city'])
		->setCountry_id('BR')
		->setRegionId($customerAddresss['region_id'])
		->setRegion($customerAddresss['region'])
		->setTelephone($customerAddresss['telephone'])
		->setFax($customerAddresss['fax'])
		->setPostcode($customerAddresss['postcode']);
			
		
		
		// Add Frete
		$order->setShippingAddress($shippingAddress)
		->setShipping_method($entrega)
		->setShippingDescription('Serviço de Entrega - '. $entreguePor."<br />Prazo: ".$prazoEntrega)
		->setPrice($valorFrete);
			
		$subTotal = 0;
		$desconto = 0;
		$grandTotal = 0;
		$totalQtyOrdered = 0;

		$couponCode = $retorno['carrinho']['cupomDesconto'];
		
		// Add Item
		foreach ($produtos as $item)
		{
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
		
			$_product = Mage::getModel('catalog/product')->load($product->getId());
			//$_product = Mage::getModel('catalog/product')->load($productId);
			$valorUnitario 	= $_product->getPrice();
			$rowTotal 		= $valorUnitario * $item['qtde'];
			
			
			$orderItem = Mage::getModel('sales/order_item')->setStoreId($storeId)
			->setQuoteItemId(0)
			->setQuoteParentItemId(NULL)
			->setIsVirtual(0)
			->setProductId($product->getId())
			->setProductType($_product->getTypeId())
			->setQtyBackordered(NULL)
			->setTotalQtyOrdered($item['qtde'])
			->setQtyOrdered($item['qtde'])
			->setName($_product->getName())
			->setSku($_product->getSku())
			->setPrice($valorUnitario)
			->setBasePrice($valorUnitario)
			->setBaseOriginalPrice($valorUnitario)
			->setOriginalPrice($valorUnitario)
			->setWeight($product->getWeight())
			->setRowTotal($rowTotal)
			->setBaseRowTotal($rowTotal)
// 			->setDiscountAmount($item['desconto'])
// 			->setBaseDiscountAmount($item['desconto'])
			->setPriceInclTax($valorUnitario)
			->setBasePriceInclTax($valorUnitario)
			->setRowTotalInclTax($valorUnitario)
			->setBaseRowTotalInclTax($valorUnitario);


			
			
			//$desconto += $item['desconto'];
		
			$subTotal += $rowTotal;
			$order->addItem($orderItem);
		
			$totalQtyOrdered += $item['qtde'];
		
			// Atualiza Estoque no Magento
			$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
			$estoque = $stock->getQty() - (int) $item['qtde'];
			$stock->setQty($estoque)->save();
		}
	
		// Add grand Total
		$grandTotal = $subTotal + $entrega;
			
		// Add Totais
		$order->setSubtotal($subTotal)
		->setBaseSubtotal($subTotal)
		->setGrandTotal($grandTotal)
// 		->setDiscountAmount(0)
// 		->setBaseDiscountAmount(0)
		->setTotalQtyOrdered($totalQtyOrdered)
		->setShippingAmount($entrega)
		->setShippingIncTax($entrega)
		->setBaseShippingIncTax($entrega)
		->setPrazo($prazoEntrega)
		->setSubtotalInclTax($subTotal)
		->setTotalDue($grandTotal)
		->setBaseSubtotalInclTax($subTotal)
		->setBaseTotalDue($grandTotal)
		->setBaseGrandTotal($grandTotal)
		->setCouponCode($couponCode)
// 	    ->setDiscountAmount(-100)
//         ->setBaseDiscountAmount(-100)
        ->setDiscountDescription('Cupom de Desconto Sextou 10%');

		// Cupom de desconto
// 		if($couponCode) {
// 			$order->setCouponCode($couponCode);
// 		}
		 
		
		// Salva Order
		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));
		$transaction->save();
			
		report("chegou aqui");
		
		if($order)
		{
			// Envia email do pedido se o pedido nao estiver cancelado
			$order->sendNewOrderEmail();
			
			if(substr($order->getPayment()->getCcApproval(), 0, 6) == 'NEGADO'){
				// Inicializa o Clearsale
				$clearSale = new Inovarti_Mundipagg_Model_Api();
					
				//var_dump($order->getPayment());
				$clearSale->failureAction($order->getPayment());
			}
		}
			
			$shoppingOrder['retorno']['numeroPedido'] = $order->getIncrementId();
			$shoppingOrder['retorno']['status'] = $status;
			$shoppingOrder['retorno']['mensagem'] = $mensagem;
			$shoppingOrder['retorno']['entreguePor'] = $entreguePor;
			$shoppingOrder['retorno']['entrega'] = "Serviço de Entrega - ".$entreguePor." - Prazo: ".$prazoEntrega;
			$shoppingOrder['retorno']['prazo'] = $prazoEntrega;

			if($tipoPagamento == 'boleto_bradesco'):
			$shoppingOrder['retorno']['boleto'] = Mage::getUrl('',array('_secure'=>true)).'boleto/boleto_bradesco.php?cod='.$order->getIncrementId().$order->getCustomerId();
			endif;
			
			if($tipoPagamento == 'venda_funcionario'):
			$shoppingOrder['retorno']['venda_funcionario'] = Mage::getUrl('',array('_secure'=>true)).'contrato_venda_funcionario/'.$order->getQuoteId();
			endif;
			
			echo stripslashes(json_encode($shoppingOrder, JSON_UNESCAPED_UNICODE));			
			
			
	} catch( Exception $e ){
		Report("RETORNO ERRO => ". $e);
		ReturnValidation(329, "Usuário e Chave API são obrigatórios.");
	}
	
}













exit;



if(count($produtos) > 1)
{
	if($parceiro == FALSE)
	{
		try{
			Report(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
			Report("Usuario => ". $apiUser);
			Report("ChaveApi => ".$apiKey);
			
			
			// Instancia a classe do soap
			$client = new SoapClient(Mage::getUrl('index.php',array('_secure'=>true))."api/v2_soap?wsdl=1");
			//$client = new SoapClient('https://carrinhohomologacao.eletrosom.com/index.php/api/v2_soap?wsdl=1');
			Report("PASSOU PELA AUTENTENTICACAO");
			$sessionId = $client->login($apiUser, $apiKey);
			
			Report("Retorno Carrinho Checkout sessionId: " . var_export($sessionId, true));
			///exit;
			
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
			
			try{
				$resultCustomerAddresses = $client->shoppingCartCustomerAddresses($sessionId, $shoppingCartId, $arrAddresses);
			} catch( Exception $e ){
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
				
				$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
				
				//Verfica o SKU
				if(!$_product){ ReturnValidation(329, "SKU Inválido."); }
				
				//Verifica o Estoque
				if(Mage::getModel('akhilleus/carrier_akhilleusapp')->returnEstoque($_product) == 0) { ReturnValidation(308, "Produto sem estoque. SKU: ".$sku); }
				
				//Verifica se a quantidade atende o solicitado pelo cliente
				$estoque = Mage::getModel('akhilleus/carrier_akhilleusapp')->returnQdeEstoque($_product);
				Report("PASSOU PELA VALIDACAO DE ESTOQUE 1 ");
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
			$shoppingCartShippingList = $client->shoppingCartShippingList($sessionId, $shoppingCartId);
			Report("RETORNO FRETE => " . var_export($shoppingCartShippingList,TRUE));	 
			
			$resultShippingMethod = $client->shoppingCartShippingMethod($sessionId, $shoppingCartId, $entrega);
			
			Report("RETORNO METHOD => ". $resultShippingMethod);
			
						
			if(!$resultShippingMethod) {ReturnValidation(337, "Método de Entrega Inválido.");}
			Report("PASSOU PELO METODO DE ENTREGA");
			//------------------------- Frete no Carrinho ------------------------------------
			
			//------------------------- Cupom de Desconto ------------------------------------
			// add coupon
			$couponCode = $retorno['carrinho']['cupomDesconto'];
			try {
				$resultCartCouponAdd = $client->shoppingCartCouponAdd($sessionId, $shoppingCartId, $couponCode);
				if(!$resultCartCouponAdd) {ReturnValidation(306, "Cupom de Desconto Inválido ou Excedido.");}
			} catch (Exception $e) {
				ReturnValidation(306, "Cupom de Desconto Inválido ou Excedido.");
			}
			
			Report("PASSOU PELO CUPOM");
			
			// remove coupon
			//$resultCartCouponRemove = $client->call($sessionId, "cart_coupon.remove", array($shoppingCartId));
			//------------------------- Cupom de Desconto ------------------------------------
			
			//------------------------- Forma de Pagamento no Carrinho ------------------------------------
			$cartao1 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao']);
			$cartao2 = base64_decode($retorno['carrinho']['formaPagamento'][0]['numeroCartao1']);
			
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
				
			elseif($tipoPagamento == "pagamento_dois_cartoes"):
			
				session_start();
				$paymentMethod = array(
						'method' 			=> 'mundipaggtwocards',
						'cc_valor' 			=> $valorTotal2, //Valor da Diferença do Primeiro Cartão - Valor Total do Compra
						'cc_parcelas' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao'],
						'cc_type' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira'],
						'cc_number' 		=> $cartao1,
						'cc_owner' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePortador'],
						'cc_exp_month' 		=> $mes1,
						'cc_exp_year' 		=> $ano1,
						'cc_cid' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1'],
						'cc_valor1' 		=> $valorTotalCartao2,
						'cc_parcelas1' 		=> $retorno['carrinho']['formaPagamento'][0]['parcelasCartao1'],
						'cc_type1' 			=> $retorno['carrinho']['formaPagamento'][0]['bandeira1'],
						'cc_number1' 		=> $cartao2,
						'cc_owner1' 		=> $retorno['carrinho']['formaPagamento'][0]['nomePortador1'],
						'cc_exp_month1' 	=> $mes2,
						'cc_exp_year1' 		=> $ano2,
						'cc_cid1' 			=> $retorno['carrinho']['formaPagamento'][0]['numeroVerificacao1']);
				$method = 'mundipaggtwocards';
				
			elseif($tipoPagamento == "venda_funcionario"):
				
				// set payment method
				$paymentMethod = array(
						'method' 			=> 'mundipaggsalesofficer',
						'cc_parcelamento'	=> $retorno['carrinho']['formaPagamento'][0]['valorTotal'],
						'nome_pai' 			=> $retorno['carrinho']['formaPagamento'][0]['nomePai'],
						'nome_mae' 			=> $retorno['carrinho']['formaPagamento'][0]['nomeMae'],
						'expedicao_rg' 		=> $retorno['carrinho']['formaPagamento'][0]['expedicaoRg'],
						'renda_mensal' 		=> $retorno['carrinho']['formaPagamento'][0]['rendaMensal']);
				$method = 'venda_funcionario';
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
			
			
			Report("PASSOU PELA FORMA DE PAGAMENTO 2 ");
			
			Report("PASSOU PELA FORMA DE PAGAMENTO 2 =>  " . var_export($paymentMethod,true));
			
			
			if(in_array($method, $metodos)){
				
				$resultPaymentMethod = $client->shoppingCartPaymentMethod($sessionId, $shoppingCartId, $paymentMethod);
				if(!$resultPaymentMethod) {ReturnValidation(333, "Não foi possível concluir o Pagamento. Favor tentar novamente ou entre em contato com a operadora do cartão de crédito para obter mais informações.");}
			} else {
				ReturnValidation(331, "Forma de Pagamento indisponível.");
			}
			Report("PASSOU PELA FORMA DE PAGAMENTO 3 ");
			//------------------------- Produtos no Carrinho ------------------------------------
			

			Report(" SESSION ID => ". $sessionId);
			Report(" SHOPPING ID => ". $shoppingCartId);
			
			//Report("Retorno ORDER ID => ". var_export($client->shoppingCartOrder($sessionId, $shoppingCartId, null, null),true));

			
			// create order
			$orderId = $client->shoppingCartOrder($sessionId, $shoppingCartId, null, null);
			
			Report(" ORDER ID => ". $orderId);
			
			
			Report("PASSOU PELA CRIACAO DO PEDIDO ");
			if(!$orderId) {ReturnValidation(334, "Não foi possível concluir a Compra.");} 
			else {
				$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				$status = $order->getStatus();
				
				// Força o envio do e-mail para o cliente - Alterado em 23/04/2019 - Amaro Junior
				$order->sendNewOrderEmail();
				
				if($status == 'canceled' && ($tipoPagamento == "pagamento_um_cartao" || $tipoPagamento == "pagamento_dois_cartao")):
					$mensagem = 'Não foi possível concluir o Pagamento. Favor tentar novamente ou entre em contato com a operadora do cartão de crédito para obter mais informações';
				elseif($tipoPagamento == "boleto_bradesco"):
					$mensagem = 'Atenção: Opção válida apenas para pagamento à vista. O boleto deve ser impresso após a finalização do pedido. Este boleto tem validade de 2 dias úteis após a finalização da compra.';
				endif;
				
				$shoppingOrder['retorno']['numeroPedido'] = $orderId;
				$shoppingOrder['retorno']['status'] = $status;
				$shoppingOrder['retorno']['mensagem'] = $mensagem;
				$shoppingOrder['retorno']['entreguePor'] = $entreguePor;
				$shoppingOrder['retorno']['entrega'] = "Serviço de Entrega - ".$entreguePor." - Prazo: ".$prazoEntrega;
				$shoppingOrder['retorno']['prazo'] = $prazoEntrega;
				
				if($tipoPagamento == 'boleto_bradesco'):
					$shoppingOrder['retorno']['boleto'] = Mage::getUrl('',array('_secure'=>true)).'boleto/boleto_bradesco.php?cod='.$order->getIncrementId().$order->getCustomerId();
				endif;
				
				if($tipoPagamento == 'venda_funcionario'):
					$shoppingOrder['retorno']['venda_funcionario'] = Mage::getUrl('',array('_secure'=>true)).'contrato_venda_funcionario/'.$order->getQuoteId();
				endif;
				
				echo stripslashes(json_encode($shoppingOrder, JSON_UNESCAPED_UNICODE));
			}
		
		} catch( Exception $e ){
			Report("RETORNO ERRO => ". $e);
			ReturnValidation(329, "Usuário e Chave API são obrigatórios.");
		}
	} else {
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






?>