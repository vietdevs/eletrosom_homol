<?php 
// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

if($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/carrinho'):
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("Retorno Carrinho: ". var_export($retorno, true));
	
	$produtos = $retorno['checkout']['produtos'];
	$cep = $retorno['checkout']['cep'];
	
	Report("Retorno Carrinho Qtde Prod: ".count($produtos));
	
	
	if(count($produtos) > 0):
		$p = 0;
		foreach($produtos AS $_produtos):
			$sku = $_produtos['sku'];
			$qtde = $_produtos['qtde'];
			
			$_product = $_productVerificaEstoque = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			
			$pai = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());
			
			Report("Valida pai => " . $pai[0]);
			
			if($pai){
				$_product = Mage::getModel('catalog/product')->load($pai[0]);
			}
			
			
			if(!$_product){ ReturnValidation(329, "SKU Inválido."); }
			
			$productId = $_product->getId();
			
			$returnCarrinho['retorno']['produtos'][$p]['nome'] = $_product->getName();
			
			$special_price = Mage::helper('tax')->getPrice($_product, $_product->getSpecialPrice());
			$_regularPrice = Mage::helper('tax')->getPrice($_product, $_product->getPrice(), $_simplePricesTax);
			$_finalPrice = Mage::helper('tax')->getPrice($_product, $_product->getFinalPrice());
			
			if (($_finalPrice < $special_price) || ($special_price == NULL)) { $special_price = $_finalPrice; }

			$returnCarrinho['retorno']['produtos'][$p]['valorUnitario'] = $special_price;
			$returnCarrinho['retorno']['produtos'][$p]['quantidade'] = $qtde;
			$returnCarrinho['retorno']['produtos'][$p]['sku'] = $sku;
			
			if (Mage::getModel('catalog/product')->load($_product->getId())->getAttributeText('desconto_boleto') == "Yes")
			{
				$entityRuleId = $_product->getData('entity_promo_boleto');
			
				//Verifica se existe uma regra especial para a promocao
				if ($entityRuleId == null || $entityRuleId == "" || $entityRuleId == 0) {
					$entityRuleId = 1;
				}
			
				// Regra do valor de desconto a vista
				$shoppingCartPriceRule = Mage::getModel('salesrule/rule')->load($entityRuleId)->getData();
					
				//Verifica se a regra está ativa
				if ($shoppingCartPriceRule["is_active"]) {
					if ($shoppingCartPriceRule["simple_action"] == "by_percent") {
						$percentual = number_format($shoppingCartPriceRule["discount_amount"], 1) / 100.0;
						$_priceBoleto = number_format($special_price - ($percentual * $_price), 4, null, "");
					} else if ($shoppingCartPriceRule["simple_action"] == "by_fixed") {
						$_priceBoleto = number_format(($special_price - $shoppingCartPriceRule["discount_amount"]), 4, null, "");
						$desconto += ($special_price - $_priceBoleto) * $qtde; 
					}
				}
				
			} 
			$items[$p]['codigo'] = $productId;
			$items[$p]['qtde'] = $qtde;
			//$items[] = $productId;
			
			Report("Retorno Carrinho items: ". var_export($items, true)); 
			
			$total += $special_price * $qtde;
			
			Report("SPECIAL PRICE => " . $special_price);
			Report("QTDE => " . $qtde);
			Report("TOTAL => " . $total);
			
			$returnCarrinho['retorno']['produtos'][$p]['disponibilidade'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->returnEstoque($_productVerificaEstoque);
			$returnCarrinho['retorno']['produtos'][$p]['imagem'] = Mage::helper('catalog/image')->init($_product, 'small_image')->resize(135)->__toString();
			$returnCarrinho['retorno']['produtos'][$p]['sku'] = $sku; 
			
			$p++;
		endforeach;

		$returnCarrinho['retorno']['frete'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->calculaFreteProdutosCategoria($items, $cep, "C", $total);
		$returnCarrinho['retorno']['valorTotal'] = $total;
		$returnCarrinho['retorno']['desconto'] = $desconto;
		$returnCarrinho['retorno']['totalGeral'] = $total - $desconto;
		
		Report("Retorno Carrinho: ". var_export($returnCarrinho, true));
		echo stripslashes(json_encode($returnCarrinho, JSON_UNESCAPED_UNICODE));
		
	else:
		echo ReturnValidation(325, 'Por favor, preencha os campos obrigatórios.');
	endif;

elseif(substr($_SERVER['REQUEST_URI'], 0, 34) == '/shell/ws/integrador/consultaCupom'):
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);

	Report("Retorno ConsultaCupom: ". var_export($retorno, true));
	
	$cupom = $retorno["carrinho"]["cupom"];
	$produtos = $retorno["carrinho"]["produtos"];
	
	$e = 0;
	foreach($produtos AS $_produto):
		
		$sku = $_produto['sku'];
		$qty = $_produto['qtde'];
		$_rulesItemTotals = array();
		
		$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
		$productId = $_product->getId();
		
		$quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
		$quote = Mage::getSingleton('sales/quote')->load($quoteId);
		
		$fakeQuote = clone $quote;
		$fakeQuote->setId(null);
		
		$product = Mage::getModel('catalog/product')->load($productId);
		
		$special_price = Mage::helper('tax')->getPrice($_product, $_product->getSpecialPrice());
		$_regularPrice = Mage::helper('tax')->getPrice($_product, $_product->getPrice(), $_simplePricesTax);
		$_finalPrice = Mage::helper('tax')->getPrice($_product, $_product->getFinalPrice());
		if ($_finalPrice < $special_price || $special_price == NULL) { $special_price = $_finalPrice; }
		
		$valorTotal += $special_price * $qty;
		$returnCarrinho['retorno'][$e]['sku'] = $sku;
		$returnCarrinho['retorno'][$e]['estoque'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->returnEstoque($_product);
		
		$item = Mage::getModel('sales/quote_item')->setQuote($fakeQuote)->setProduct($product);
		$item->setAllItems(array($product));
		$item->getProduct()->setProductId($product->getEntityId());
		$item->setQty($qty);
		
		$item->getQuote()->setData('items_collection', array($item));
		
		$idRule = Mage::getModel('salesrule/coupon')->load($cupom, 'code');
		$rule = Mage::getModel('salesrule/rule')->load($idRule['rule_id']);
		$condition = $rule->getConditions()->validate($item);
		
		if($condition):
			switch ($rule["simple_action"]) 
			{
				case 'by_percent':
					$step = $rule->getDiscountStep(); 
					if ($step) {
						$qty = floor($qty/$step)*$step;
					}
					$rulePercent = max(0, 100-$rule->getDiscountAmount());
					
					$_rulePct = $rulePercent/100;
					$discountAmount    += ($qty * $special_price) - (($qty * $special_price) * $_rulePct);
					
				break;
				case 'to_fixed':
					$quoteAmount = $quote->getStore()->convertPrice($rule->getDiscountAmount());
					$discountAmount    = $qty * ($itemPrice - $quoteAmount);
					
				break;
				case 'by_fixed':
					$step = $rule->getDiscountStep();
					if ($step) {
						$qty = floor($qty/$step)*$step;
					}
					$discountAmount     += $qty * $quoteAmount;
					
				break;
				case 'buy_x_get_y':
					$x = $rule->getDiscountStep();
					$y = $rule->getDiscountAmount();
					if (!$x || $y > $x) {
						break;
					}
					$buyAndDiscountQty = $x + $y;
	
					$fullRuleQtyPeriod = floor($qty / $buyAndDiscountQty);
					$freeQty  = $qty - $fullRuleQtyPeriod * $buyAndDiscountQty;
	
					$discountQty = $fullRuleQtyPeriod * $y;
					if ($freeQty > $x) {
						$discountQty += $freeQty - $x;
					}
	
					$discountAmount    += $discountQty * $special_price;

				break;
			}
			
		endif;
		
		$e++;
		
	endforeach;
	
	$returnCarrinho['retorno']['valorTotal'] = number_format($valorTotal, 2, ',', '.');
	$returnCarrinho['retorno']['descontoCupom'] = number_format($discountAmount, 2, ',', '.');
	$returnCarrinho['retorno']['totalGeral'] = number_format($valorTotal - $discountAmount, 2, ',', '.');
	
	if ($returnCarrinho['retorno']['descontoCupom'] == '0,00'){
		$returnCarrinho['retorno']['message'] = 'Cupom de desconto \"'. $cupom.'\" incorreto.';
	}else{
		$returnCarrinho['retorno']['message'] = "Desconto ($cupom) R$". $returnCarrinho['retorno']['descontoCupom'];
	}
	
	Report("Retorno ConsultaCupom Resultado: ". var_export($returnCarrinho, true));
	
	echo stripslashes(json_encode($returnCarrinho, JSON_UNESCAPED_UNICODE));

elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/pagamentoDisponiveisCliente'):

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.

	Report("Retorno pagamentoDisponiveisCliente: ".$jsonStr);
	$retorno = json_decode($jsonStr, true);
	
	Report(" ".var_export($retorno, true));
	
	$idCliente = $retorno["carrinho"]["idCliente"];
	$produtos = $retorno["carrinho"]["produtos"];
	
	//------------ Verifica se é Funcionário e está habilitado para a forma de Pagamento Desconto em Folha ----------------------//
	$customer = Mage::getModel('customer/customer')->load($idCliente);
	$cpf = $customer->getCpf();
	$funcionario = Mage::getModel('akhilleus/carrier_akhilleusapp')->getValidacaoDadosFuncionario($cpf);
	//------------ Verifica se é Funcionário e está habilitado para a forma de Pagamento Desconto em Folha ----------------------//
	
	//------------------------------ Verifica se o Produto é Venda Parceiros ----------------------------------//
	$parceiro = Mage::getModel('akhilleus/carrier_akhilleusapp')->getValidacaoParceiros($produtos);
	//------------------------------ Verifica se o Produto é Venda Parceiros ----------------------------------//
	
	$payments = Mage::getSingleton('payment/config')->getActiveMethods();
	$p = 0;
// 	var_dump($produtos);
	foreach ($payments as $paymentCode => $paymentModel)
	{
		
		if($paymentCode == 'boleto_bradesco'){
			$boleto = Mage::getStoreConfig("payment/$paymentCode");
			$pagamento = Mage::getModel('akhilleus/carrier_akhilleusapp')->getPayment($produtos);
			
			$valorTotal = str_replace(",", ".", str_replace(".", "", str_replace("R$", "", $retorno["carrinho"]["valorTotalcomFrete"])));
			
			$pagamento['formaPagamento'][0]['codigoPagamento'] = 'boleto_bradesco';
			$pagamento['formaPagamento'][0]['descricaoPagamento'] = 'Boleto Bancário';
			$pagamento['formaPagamento'][0]['instruction'] =$boleto["instructions"];
			$pagamento['formaPagamento'][0]['discount'] = number_format($pagamento['discount_amount'], 2);
			$pagamento['formaPagamento'][0]['valorTotalComDesconto'] = "R$ ".number_format($valorTotal - $pagamento['desconto'], 2, ',', '.');
			
			
		} elseif($paymentCode == 'mundipagg') {
			 
			$pagamento['formaPagamento'][1]['codigoPagamento'] = 'pagamento_um_cartao';
			$pagamento['formaPagamento'][1]['descricaoPagamento'] = 'Pagamento com 1 cartão';
		
		} elseif($paymentCode == 'mundipaggtwocards' && $parceiro == FALSE) {
			
			$pagamento['formaPagamento'][2]['codigoPagamento'] = 'pagamento_dois_cartoes';
			$pagamento['formaPagamento'][2]['descricaoPagamento'] = 'Pagamento com 2 cartões';
		
		} elseif($paymentCode == 'mundipaggsalesofficer' && $funcionario == 'A' && $parceiro == FALSE) {
			
			$pagamento['formaPagamento'][3]['codigoPagamento'] = 'venda_funcionario';
			$pagamento['formaPagamento'][3]['descricaoPagamento'] = 'Desconto em Folha - Funcionário';
			
		}
	}
	Report(var_export($pagamento, true));
	
	echo stripslashes(json_encode($pagamento, JSON_UNESCAPED_UNICODE));	
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/detalhamentoPagamento'):

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	Report("json detalhementoPagento" . $jsonStr);
	
	Report("Retorno detalhamentoPagamento: ". var_export($retorno, true));
	
	$codigoPagamento = $retorno["carrinho"]["codigoPagamento"];
	//$valorTotal = str_replace(",", ".",  str_replace("R$", "", $retorno["carrinho"]["valorTotalcomFrete"]));
	$valorTotal = str_replace(",", ".", str_replace(".", "", str_replace("R$", "", $retorno["carrinho"]["valorTotalcomFrete"])));
	
	Report("Retorno detalhamentoPagamento total: ". var_export($valorTotal, true));
	
	$produtos = $retorno["carrinho"]["produtos"];
	$valorPagoCartaoUm = str_replace(",", ".", str_replace(".", "", str_replace("R$", "", $retorno["carrinho"]["valorTotalcomFrete"])));
	$idCliente = $retorno["carrinho"]["idCliente"];
	
	foreach($produtos as $item)
	{
		$productResult = Mage::getModel('catalog/product')->loadByAttribute('sku', $item['sku']);
		if($productResult->getStatus() == 2)
			ReturnValidation(332, 'Este produto está desativado: '.$item['sku']);
	}
	
	$pagamento = Mage::getModel('akhilleus/carrier_akhilleusapp')->getPayment($produtos);
	
	//------------ Verifica se é Funcionário e está habilitado para a forma de Pagamento Desconto em Folha ----------------------//
	$customer = Mage::getModel('customer/customer')->load($idCliente);
	$cpf = $customer->getCpf();
	$funcionario = Mage::getModel('akhilleus/carrier_akhilleusapp')->getValidacaoDadosFuncionario($cpf);
	//------------ Verifica se é Funcionário e está habilitado para a forma de Pagamento Desconto em Folha ----------------------//
	
	//------------------------------ Verifica se o Produto é Venda Parceiros ----------------------------------//
	$parceiro = Mage::getModel('akhilleus/carrier_akhilleusapp')->getValidacaoParceiros($produtos);
	//------------------------------ Verifica se o Produto é Venda Parceiros ----------------------------------//
	
// 	var_dump($pagamento);
	if($codigoPagamento == 'boleto_bradesco')
	{
		$payment['formaPagamento']['tipoPagamento'] = 'boleto_bradesco';
		$payment['formaPagamento']['valorDesconto'] = "R$ ".number_format($pagamento['desconto'], 2, ',', '.');
		$payment['formaPagamento']['valorTotalComDesconto'] = "R$ ".number_format($valorTotal - $pagamento['desconto'], 2, ',', '.');
		$payment['formaPagamento']['discount_amount'] = number_format($pagamento['discount_amount'], 2);
				
	} elseif($codigoPagamento == 'pagamento_um_cartao') {
				
		$typeVisa = explode(",", Mage::getStoreConfig('payment/mundipaggtwocards/cctypes'));
		$typeStone = explode(",", Mage::getStoreConfig('payment/mundipaggstonetwocards/cctypes'));
		$typeOrtobom = explode(",", Mage::getStoreConfig('payment/mundipaggortobomtwocards/cctypes'));
		$type = array_merge($typeVisa, $typeStone, $typeOrtobom);
		$type = array_unique($type);
			
		$payment['formaPagamento']['tipoPagamento'] = 'pagamento_um_cartao';
		$payment['formaPagamento']['valorTotal'] = $valorTotal;
		$payment['formaPagamento']['bandeiras'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getBandeiras($type);
		$payment['formaPagamento']['parcelas'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getParcelas($valorPagoCartaoUm, $pagamento['parcelaCartao']);
				
	} elseif($codigoPagamento == 'pagamento_dois_cartao'  && $parceiro == FALSE) {
		
		$valorPagoCartaoUm = str_replace(",", ".", str_replace(".", "", str_replace("R$", "", $retorno["carrinho"]["valorPagoCartaoUm"])));

		$typeVisa = explode(",", Mage::getStoreConfig('payment/mundipaggtwocards/cctypes'));
		$typeStone = explode(",", Mage::getStoreConfig('payment/mundipaggstonetwocards/cctypes'));
		$typeOrtobom = explode(",", Mage::getStoreConfig('payment/mundipaggortobomtwocards/cctypes'));
		$type = array_merge($typeVisa, $typeStone, $typeOrtobom);
		$type = array_unique($type);
	
		$payment['formaPagamento']['tipoPagamento'] = 'pagamento_dois_cartoes';
		$payment['formaPagamento']['valorTotal'] = $valorTotal;
		$payment['formaPagamento']['bandeiras'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getBandeiras($type);
		
		if($valorPagoCartaoUm){
			$payment['formaPagamento']['parcelas1'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getParcelas($valorPagoCartaoUm, $pagamento['parcelaCartao']);
			Report("valor cartao 1 ". var_export($valorPagoCartaoUm, true));
			Report("valor cartao 2 ". var_export($valorTotal - $valorPagoCartaoUm, true));
			$payment['formaPagamento']['parcelas2'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getParcelas($valorTotal - $valorPagoCartaoUm, $pagamento['parcelaCartao']);
		}else{
			$payment['formaPagamento']['parcelas1'] = '';
			$payment['formaPagamento']['parcelas2'] = '';
		}
		
		
	} elseif($codigoPagamento == 'venda_funcionario' && $funcionario == 'A' && $parceiro == FALSE) {
		
		$payment['formaPagamento']['tipoPagamento'] = 'venda_funcionario';
		$payment['formaPagamento']['valorTotal'] = $valorTotal;
		$payment['formaPagamento']['parcelas'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getParcelasFuncionario($valorTotal, $pagamento['parcelaFuncionario']);
	
	} elseif($codigoPagamento == 'pagamento_dois_cartoes'  && $parceiro <> FALSE) {
		ReturnValidation(331, 'Forma de Pagamento indisponível para Produto Selecionado.');
		
	} elseif($codigoPagamento == 'venda_funcionario' && $funcionario <> 'A' && $parceiro <> FALSE) {
		ReturnValidation(331, 'Forma de Pagamento indisponível para Produto Selecionado.');
		
	} else {
		
		ReturnValidation(330, 'Nenhuma forma de Pagamento foi selecionada.');
	}
	
	Report("Retorno detalhamentoPagamento: ". var_export($payment, true));
	
	Report("Retorno detalhamentoPagamento json: ". stripslashes(json_encode($payment, JSON_UNESCAPED_UNICODE)));
	
	echo stripslashes(json_encode($payment, JSON_UNESCAPED_UNICODE));	
	die();
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/validarCartao'):
	
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("json validar cartao" . $jsonStr);

	echo stripslashes(json_encode(obterBandeira($retorno['card']), JSON_UNESCAPED_UNICODE));
	die();
endif;


function ReturnValidation($codigo, $mensagem)
{
	$dados['codigoMensagem'] = $codigo;
	$dados['mensagem'] = $mensagem;

	echo $mensagem = json_encode($dados, JSON_UNESCAPED_UNICODE);
	Mage::getModel('akhilleus/carrier_akhilleusapp')->Report($mensagem);
	die();
}

function Report($texto, $abort = false)
{
	$data_log = shell_exec('date +%Y-%m-%d\ %H:%M:%S');
	$data_log = str_replace("\n", "", $data_log);

	$log = fopen(Mage::getStoreConfig('erp/frontend/url_logs').'ws_integracao.log', "a+");
	fwrite($log, $data_log . " " . print_r($texto, true) . "\n");
	fclose($log);
	if ($abort) {
		exit(0);
	}
}

function obterBandeira($numero){
	$numero = preg_replace("/[^0-9]/", "", $numero); //remove caracteres não numéricos
	if(strlen($numero) < 13 || strlen($numero) > 19)
		return false;
		//O BIN do Elo é relativamente grande, por isso a separação em outra variável
		$elo_bin = implode("|", array(636368,438935,504175,451416,636297,506699,509048,509067,509049,509069,509050,09074,509068,509040,509045,509051,509046,509066,509047,509042,509052,509043,509064,509040));
		$expressoes = array(
				"elo"           => "/^((".$elo_bin."[0-9]{10})|(36297[0-9]{11})|(5067|4576|4011[0-9]{12}))/",
				"discover"      => "/^((6011[0-9]{12})|(622[0-9]{13})|(64|65[0-9]{14}))/",
				"diners"        => "/^((301|305[0-9]{11,13})|(36|38[0-9]{12,14}))/",
				"amex"          => "/^((34|37[0-9]{13}))/",
				"hipercard"     => "/^((38|60[0-9]{11,14,17}))/",
				"aura"          => "/^((50[0-9]{14}))/",
				"jcb"           => "/^((35[0-9]{14}))/",
				"mastercard"    => "/^((5[0-9]{15}))/",
				"visa"          => "/^((4[0-9]{12,15}))/"
		);
		foreach($expressoes as $bandeira => $expressao){
			if(preg_match($expressao, $numero)){
				
				if($bandeira == 'amex'):
				$bandeiras['nome'] = 'Amex';
				$bandeiras['codigo'] = 'AE';
				elseif($bandeira == 'visa'):
				$bandeiras['nome'] = 'Visa';
				$bandeiras['codigo'] = 'VI';
				elseif($bandeira == 'mastercard'):
				$bandeiras['nome'] = 'Mastercard';
				$bandeiras['codigo'] = 'MC';
				elseif($bandeira == 'diners'):
				$bandeiras['nome'] = 'Diners';
				$bandeiras['codigo'] = 'DN';
				elseif($bandeira == 'elo'):
				$bandeiras['nome'] = 'Elo';
				$bandeiras['codigo'] = 'EL';
				elseif($bandeira == 'hipercard'):
				$bandeiras['nome'] = 'Hipercard';
				$bandeiras['codigo'] = 'HI';
				endif;
				
				return $bandeiras;
			}
		}
		return false;
}
?>