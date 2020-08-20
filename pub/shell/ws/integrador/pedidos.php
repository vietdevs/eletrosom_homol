<?php 
// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

$jsonStr = file_get_contents("php://input");

$idCliente = $_GET['idCliente'];
$pagina = $_GET['pagina'];
$incrementId = $_GET['incrementId'];

//Lista os 5 Últimos pedidos do cliente - Faz paginação
if($pagina > 0 && $incrementId == NULL):
	$qdeOrder = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter("customer_id", $idCliente);
	$quantidade = count($qdeOrder);
	$totalPaginas = ceil($quantidade / 5);
	
	$order = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter("customer_id", $idCliente)->setOrder('created_at', 'DESC')->setPageSize(5)->setCurPage($pagina);
	
	$i = 0;
	foreach($order AS $_order):
		$pedido[$i]['incrementId'] = $_order->getIncrementId();
		$pedido[$i]['dataPedido'] = date('d/m/Y', strtotime($_order->getCreatedAt()));
		$pedido[$i]['totalPedido'] = 'R$ '.number_format($_order->getGrandTotal(), 2, ',', '.');
		$pedido[$i]['formaPagamento'] = getPagamento($_order);
		$pedido[$i]['statusPagamento'] = getStatus($_order->getStatus());
		$pedido[$i]['totalPaginas'] = $totalPaginas;
		
		$i++;
	endforeach;

//Lista Todos os pedidos do Cliente
elseif($incrementId == NULL):

	Report("Lista Todos os pedidos do Cliente.");
	$order = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter("customer_id", $idCliente)->setOrder('created_at', 'DESC');
	
	$i = 0;
	foreach($order AS $_order):
		$pedido[$i]['incrementId'] = $_order->getIncrementId();
		$pedido[$i]['dataPedido'] = date('d/m/Y', strtotime($_order->getCreatedAt()));
		$pedido[$i]['totalPedido'] = 'R$ '.number_format($_order->getGrandTotal(), 2, ',', '.');
		$pedido[$i]['formaPagamento'] = getPagamento($_order);
		$pedido[$i]['statusPagamento'] = getStatus($_order->getStatus());
		
		$i++;
	endforeach;
	
	Report("Lista Todos os pedidos do Cliente.". var_export ( $pedido['0'], true ) );
	
//Detalhamento do Pedido	
elseif($incrementId <> NULL):

	$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

	Report("Retorno Detalhamento do Pedido: ".$incrementId);

	$pedido['edi'] = Mage::getModel('akhilleus/carrier_akhilleusapp')->getDadosEdi($order);
	
	$pedido['incrementId'] = $order->getIncrementId();
	$pedido['dataPedido'] = date('d/m/Y', strtotime($order->getCreatedAt()));
	$pedido['totalPedido'] = 'R$ '.number_format($order->getGrandTotal(), 2, ',', '.');

	$pagamento = getPagamentoPedido($order);
	$pedido['formaPagamento'] = $pagamento['formaPagamento'];
	if ($pagamento['formaPagamento'] == 'Boleto Bancário'){
		
		// Validação da linha digitável - Caso o boleto esteja vencido não envia a linha digitável para o APP - Amaro JR - Tarefa 13533 - 07/02/2020
		$configmodulo 			= Mage::getSingleton ( 'boleto/method_bradesco' );
		$storeId 				= Mage::app ()->getStore ()->getId ();
		$dias_prazo_pagamento 	= ( string ) $configmodulo->getConfigData ( 'due_date', $storeId );
		$data_pedido 			= Mage::helper ( 'core' )->formatDate ( $order->getCreatedAtDate (), 'medium' );		
		$data_vencimento 		= Mage::getModel ( 'sales/order' )->getDataVencimentoBoleto ( $data_pedido, $dias_prazo_pagamento );
		$data_vencimento1 		= str_replace("/", "-", $data_vencimento);
		$data_vencimento1 		=  date("Y-m-d", strtotime($data_vencimento1));
		$timestamp1 			= strtotime ( $data_vencimento1);
		$data_now 				= date ( "Y-m-d" );
		$timestamp2 			= strtotime ( $data_now );
		
// 		Report('Valida Boleto - Store Id => ' . $storeId);
// 		Report('Valida Boleto - Dias Prazo => ' . $dias_prazo_pagamento);
// 		Report('Valida Boleto - Data Vencimento 1 => ' . $data_vencimento);
// 		Report('Valida Boleto - Data Vencimento 2 => ' . $data_vencimento1);
		
		$pedido['boleto_linha_digitavel'] = $pagamento['codigoBoleto'];
		$pedido['linkBoleto'] = Mage::getUrl('',array('_secure'=>true)).'boleto/boleto_bradesco.php?cod='.$order->getIncrementId().$order->getCustomerId();
		
		if ($timestamp1 < $timestamp2) {		
			$pedido['boleto_linha_digitavel'] 	= '';
			$pedido['linkBoleto'] 				= '';
		}
		
	}else{
		
		$pedido['cc_card'] = '**** **** **** '.$order->getPayment()->getCcLast4();
		
		$parcelas = $order->getPayment()->getCcParcelas();
		if($parcelas > 100){
			$parcelas = $parcelas - 100;
			$juros = ' sem juros';
			$valor = $order->getPayment()->getCcValor();
			$pedido['parcelamento'] = $parcelas .'x'.number_format(($valor/$parcelas), 2, ',', '.').$juros;
		}else{
			$parcelas = $parcelas;
			$juros = ' com juros';
			$valor = $order->getPayment()->getValorTotalComJuros();
			$pedido['parcelamento'] = $parcelas .'x'.number_format(($valor/$parcelas), 2, ',', '.').$juros;
		}

		$parcelas1 = $order->getPayment()->getCcParcelas1();
		if ($parcelas1){
			
			$pedido['cc_card1'] = '**** **** **** '.$order->getPayment()->getCcLast41();
			
			if($parcelas1 > 100){
				$parcelas1 = $parcelas1 - 100; 
				$juros1 = ' sem juros'; 
				$valor1 =$order->getPayment()->getCcValor1();
				$pedido['parcelamento1'] = $parcelas1 .'x'.number_format(($valor1/$parcelas1), 2, ',', '.').$juros1;
			}else{
				$parcelas1 = $parcelas1; 
				$juros1 = ' com juros'; 
				$valor1 = $order->getPayment()->getValorTotalComJuros1();
				$pedido['parcelamento1'] = $parcelas1 .'x'.number_format(($valor1/$parcelas1), 2, ',', '.').$juros1;
			}
		}
	}
	
	//$pedido['boleto_linha_digitavel'] = $pagamento['codigoBoleto'];
	$pedido['formaPagamento'] = $pagamento['formaPagamento'];
	
	
	$pedido['statusPagamento'] = getStatus($order->getStatus());
	
	$i = 0;
	//Itens do Pedido
	foreach ($order->getAllItems() as $item):
		$items[$i]['nomeProduto'] 	= $item->getName();
		$items[$i]['sku'] 			= $item->getSku();
		$items[$i]['qtde'] 			= (int)$item->getQtyOrdered();
		$items[$i]['precoUnitario'] = 'R$ '.number_format($item->getPrice(), 2, ',', '.');

		$i++;
	endforeach;

	$pedido['produtos'] = $items;
	$pedido['frete'] = 'R$ '.number_format($order->getShippingAmount(), 2, ',', '.');
	$pedido['desconto'] = 'R$ '.number_format($order->getDiscountAmount(), 2, ',', '.');
	
	//Endereço Entrega
	$shipping_address = $order->getShippingAddress();
	$pedido['enderecoEntrega'] = $shipping_address->getStreet(1).", ".$shipping_address->getStreet(2);
	$pedido['bairroEntrega'] = $shipping_address->getStreet(3);
	$pedido['complementEntrega'] = $shipping_address->getStreet(4);
	$pedido['cidadeEntrega'] = $shipping_address->getCity()." - ".$shipping_address->getRegion();
	$pedido['cepEntrega'] = $shipping_address->getPostcode();
	$pedido['produtos'] = $items;
	
	Report("Detalhe do pedido: $incrementId ". var_export ( $pedido, true ) );
		
endif;

// var_dump($pedido);

echo str_replace("nttttt", "", stripslashes(json_encode($pedido, JSON_UNESCAPED_UNICODE)));

function getStatus($status)
{
	if($status == 'complete'):
		$retorno = 'Despachado';
	elseif($status == 'processing'):
		$retorno = 'Processando';
	elseif($status == 'pending'):
		$retorno = 'Aguardando Confirmação Pagamento';
	elseif($status == 'canceled'):
		$retorno = 'Cancelado';
	endif;
	
	return $retorno;
}

function getPagamento($order)
{
	$payment = $order->getPayment();
	$payment->getMethod();
	$method = substr($payment->getMethod(), 0, 6);
	
	if($method == 'boleto'):
		$pagamento = 'Boleto Bancário';
	else:
		$pagamento = 'Cartão de Crédito';
	endif;
	
	return $pagamento;
}

function getPagamentoPedido($order)
{
	$payment = $order->getPayment();
	$payment->getMethod();
	$method = substr($payment->getMethod(), 0, 6);
	
	$pagamento = array();
	if($method == 'boleto'):
		$pagamento['formaPagamento'] = 'Boleto Bancário';
	else:
		$pagamento['formaPagamento'] = 'Cartão de Crédito';
	endif;
	
	$pagamento['codigoBoleto'] = $payment->getBoletoLinhaDigitavel();
	
	return $pagamento;
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

?>