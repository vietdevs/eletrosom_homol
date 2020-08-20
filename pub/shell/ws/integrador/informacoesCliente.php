<?php

// Inclui a biblioteca do PHPExcel
$caminhoPHPExcel = dirname ( dirname ( __FILE__ ) ) . DIRECTORY_SEPARATOR . "relatorios" . DIRECTORY_SEPARATOR;
require_once $caminhoPHPExcel . "PHPExcel.php";
require_once $caminhoPHPExcel . "PHPExcel/Writer/Excel2007.php";
require_once $caminhoPHPExcel . "class.phpmailer.php";

// PATH DA APLICAÇÃO
$appMagento = dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento . 'Mage.php';

// Incializa a aplicação
Mage::app ( 'default' );

if ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/minhaconta') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( $jsonStr );
	// Report("Retorno banners minhaconta: ". var_export($retorno, true));

	$customerData ['firstname'] = $nome = $retorno ['cadastro'] ['nome'];
	$customerData ['lastname'] = $sobrenome = $retorno ['cadastro'] ['sobrenome'];
	$customerData ['email'] = $email = $retorno ['cadastro'] ['email'];
	$data = explode ( "/", $retorno ['cadastro'] ['data_nascimento'] );
	// $customerData['dob'] = $dataNascimento = $data[2]."-".$data[1]."-".$data[0]; // Invertido para corrigir erro do app
	$customerData ['dob'] = $dataNascimento = $data [2] . "-" . $data [0] . "-" . $data [1];
	$customerData ['gender'] = $sexo = $retorno ['cadastro'] ['sexo'];
	$customerData ['foto_cliente'] = $foto = $retorno ['cadastro'] ['foto_cliente'];
	$tipoPessoa = $retorno ['cadastro'] ['tipo_pessoa'];
	if ($tipoPessoa == 'F') :
		$customerData ['tipopessoa'] = 526;
	else :
		$customerData ['tipopessoa'] = 527;
	endif;
	$customerData ['cpf'] = $cpf = $retorno ['cadastro'] ['cpf'];
	$customerData ['rg'] = $rg = $retorno ['cadastro'] ['rg'];
	$customerData ['is_subscribed'] = $retorno ['cadastro'] ['receber_ofertas'];
	$customerData ['is_subscribedcelular'] = $retorno ['cadastro'] ['ofertas_celular'];
	$senha = $retorno ['cadastro'] ['senha'];
	$confirmarSenha = $retorno ['cadastro'] ['confirmar_senha'];

	if (strlen ( $senha ) < 8) { // Validação tamanho de senha
		ReturnValidation ( 304, "Senha deve conter pelo menos 8 caracteres.", 0 );
	}

	Report ( "Retorno customerdata minhaconta: " . var_export ( $customerData, true ) );

	if ($email != "" && $senha != "" && $confirmarSenha != "" && $nome != "" && $sobrenome != "" && $cpf != "" /*&& $rg <> ""*/ && $sexo != "" && $dataNascimento != "") {
		if ($tipoPessoa == 'F') :
			// --------------- Validação do CPF ou CNPJ ----------------
			$cpf = str_pad ( preg_replace ( '/[^0-9_]/', '', $cpf ), 11, '0', STR_PAD_LEFT );
			$cpf = str_replace ( ".", "", $cpf );
			$cpf = str_replace ( "-", "", $cpf );
			$cpf = str_replace ( "/", "", $cpf );

			if (strlen ( $cpf ) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') :
				ReturnValidation ( 321, "CPF inválido." );
			else :

				for($t = 9; $t < 11; $t ++) :
					for($d = 0, $c = 0; $c < $t; $c ++) :
						$d += $cpf {$c} * (($t + 1) - $c);
					endfor
					;
					$d = ((10 * $d) % 11) % 10;

					if ($cpf {$c} != $d) :
						ReturnValidation ( 321, "CPF inválido." );
					endif;

				endfor
				;
			endif;

			Report ( "Validacao de CPF Register: " . $cpf );

		else :
			// Verifiva se o número digitado contém todos os digitos
			$cnpj = preg_replace ( '/[^0-9]/', '', $cpf );
			$cnpj = str_replace ( ".", "", $cnpj );
			$cnpj = str_replace ( "-", "", $cnpj );
			$cnpj = str_replace ( "/", "", $cnpj );

			if (strlen ( $cnpj ) != 14) :
				ReturnValidation ( 322, "CNPJ inválido." );
			endif;

			$calcular = 0;
			$calcularDois = 0;

			for($i = 0, $x = 5; $i <= 11; $i ++, $x --) :
				$x = ($x < 2) ? 9 : $x;
				$number = substr ( $cnpj, $i, 1 );
				$calcular += $number * $x;
			endfor
			;

			for($i = 0, $x = 6; $i <= 12; $i ++, $x --) :
				$x = ($x < 2) ? 9 : $x;
				$numberDois = substr ( $cnpj, $i, 1 );
				$calcularDois += $numberDois * $x;
			endfor
			;

			$digitoUm = (($calcular % 11) < 2) ? 0 : 11 - ($calcular % 11);
			$digitoDois = (($calcularDois % 11) < 2) ? 0 : 11 - ($calcularDois % 11);

			if ($digitoUm != substr ( $cnpj, 12, 1 ) || $digitoDois != substr ( $cnpj, 13, 1 )) :
				ReturnValidation ( 322, "CNPJ inválido." );
			endif;

			Report ( "Validacao de CNPJ Register: " . $cpf );
		endif;

		// Tira a pontuação para salvar no banco de dados
		$cpf = preg_replace ( "/[.-\/]/", "", $cpf );
		$cpf = str_replace ( ".", "", $cpf );
		$cpf = str_replace ( "-", "", $cpf );
		$cpf = str_replace ( "/", "", $cpf );

		/* TESTE DE DUPLICIDADE DE CPF */
		$readonce = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
		$rows = $readonce->fetchAll ( "SELECT * FROM customer_entity_varchar where value ='{$cpf}'" );
		Report ( "Teste de Duplicidade Register: " . count ( $rows ) );
		if ($rows) :
			if ($tipoPessoa == 'F') :
				ReturnValidation ( 323, "O CPF informado já esta sendo utilizado." );
			else :
				ReturnValidation ( 324, "O CNPJ informado já esta sendo utilizado." );
			endif;
		endif;

			/* FIM DO TESTE DE DUPLICIDADE DE CPF */

			/* TESTE DE DUPLICIDADE DE CPF */
		$readonce = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
		$rows = $readonce->fetchAll ( "SELECT email FROM customer_entity where email ='{$email}'" );

		Report ( "Teste de Duplicidade Register E-mail: " . count ( $rows ) );
		if ($rows) :
			ReturnValidation ( 326, "O Email informado já esta sendo utilizado." );
		endif;

			/* FIM DO TESTE DE DUPLICIDADE DE CPF */

		$customer = Mage::getModel ( 'customer/customer' )->setId ( null );
		$customerForm = Mage::getModel ( 'customer/form' );
		$customerForm->setFormCode ( 'customer_account_create' )->setEntity ( $customer );
		$customerErrors = $customerForm->validateData ( $customerData );

		Report ( "Retorno Customer: " . var_export ( $customer, true ) );
		Report ( "Retorno customerErrors: " . var_export ( $customerErrors, true ) );

		if ($customerErrors !== true) {
			$errors = array_merge ( $customerErrors, $errors );
		} else {
			$customerForm->compactData ( $customerData );
			$customer->setPassword ( $senha );
			$customer->setConfirmation ( $confirmarSenha );
			$customerErrors = $customer->validate ();
			if (is_array ( $customerErrors )) {
				$errors = array_merge ( $customerErrors, $errors );
			}
			Report ( "Error: " . $errors );
			$validationResult = count ( $errors ) == 0;
			Report ( "Validar: " . $validationResult );
			Report ( "Foto: " . $foto );

			if (true === $validationResult) {

				if (strpos ( $foto, 'base64' ) > 0) {
					$foto = base64_to_jpeg ( $foto );
					$customer->setFotoCliente ( $foto );
					Report ( "Foto Convertida: " . $foto );
				}

				Report ( "Passou CPF: " . $cpf );

				$customer->setCpf ( $cpf );

				$customer->save ();

				Mage::dispatchEvent ( 'customer_register_success', array (
						'account_controller' => $customerData,
						'customer' => $customer
				) );
				$customer->sendNewAccountEmail ( 'confirmed', '', Mage::app ()->getStore ()->getId () );

				$dados ['codigoMensagem'] = 200;
				$dados ['mensagem'] = 'Processo realizado com sucesso.';
				$dados ['IdCliente'] = $customer->getId ();

				echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
				Report ( $mensagem );
				die ();
				exit ();
			}
		}
	} else {
		if ($email == "")
			$campos .= ', Email';

		if ($senha == "")
			$campos .= ', Senha';

		if ($confirmarSenha == "")
			$campos .= ', Confirmação Senha';

		if ($nome == "")
			$campos .= ', Nome';

		if ($sobrenome == "")
			$campos .= ', Sobrenome';

		if ($cpf == "")
			$campos .= ', CPF';

		// if($rg == "")
		// $campos .= ', RG';

		if ($sexo == "")
			$campos .= ', Sexo';

		if ($dataNascimento == "")
			$campos .= ', Data Nascimento';

		ReturnValidation ( 325, "Por favor, preencha os campos obrigatórios: " . $campos );
	}

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 39 ) == '/shell/ws/integrador/alteraCadastro') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	// Report($jsonStr);

	// Report('Imagem: '. $retorno['cadastro']['foto_cliente'] );
	// Report('imagem: ' . var_export(base64_to_jpeg($retorno['cadastro']['foto_cliente'], $retorno['cadastro']['idCliente'])));
	// Report("Retorno banners minhaconta: ". var_export($retorno, true));

	$customerData ['id'] = $idCliente = $retorno ['cadastro'] ['idCliente'];
	$data = explode ( "/", $retorno ['cadastro'] ['data_nascimento'] );
	$customerData ['dob'] = $dataNascimento = $data [2] . "-" . $data [1] . "-" . $data [0]; // Invertido para corrigir erro do app
	$customerData ['gender'] = $sexo = $retorno ['cadastro'] ['sexo'];
	$customerData ['foto_cliente'] = $foto = $retorno ['cadastro'] ['foto_cliente'];

	// Report("Retorno customerdata Altera Cadastro: ". var_export($customerData, true));

	if ($sexo == 'M') {
		$sexo = 1;
	} else {
		$sexo = 2;
	}

	$customer = Mage::getModel ( 'customer/customer' )->load ( $idCliente );

	if (strpos ( $foto, 'base64' ) > 0) {
		$foto = base64_to_jpeg ( $foto );
		$customer->setFotoCliente ( $foto );
		Report ( "Foto Convertida: " . $foto );
	}

	$customer->setDob ( $dataNascimento );
	$customer->setGender ( $sexo );

	try {
		$customer->save ();

		$dados ['codigoMensagem'] = 200;
		$dados ['mensagem'] = 'Processo realizado com sucesso.';
		$dados ['IdCliente'] = $customer->getId ();
		$dados ['foto_cliente'] = 'https://carrinhohomologacao.eletrosom.com/media/clientes/' . $foto;

		echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
		Report ( $mensagem );
	} catch ( Exception $e ) {
		ReturnValidation ( 304, "Erro ao alterar cadastro, tente novamente.", $idCliente );
	}

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 39 ) == '/shell/ws/integrador/listaMeusEnderecos') :

	$idCliente = $_GET ['idCliente'];
	$lista = $_GET ['lista'];

	Report ( "Retorno banners listaMeusEnderecos: " . $idCliente . "  -  " . $lista );

	if ($lista == NULL) {
		$customer = Mage::getModel ( 'customer/customer' )->load ( $idCliente );
		foreach ( $customer->getAddresses () as $address ) {
			if ($address->getEntityId () == $customer->getDefaultBilling ()) :

				$region = Mage::getModel ( 'directory/region' )->load ( $address->getRegionId () );
				$regionId = $region->getName ();

				$addressReturn ['nomeEndereco'] = $address->getNomeEndereco ();
				$addressReturn ['nome'] = $address->getFirstname ();
				$addressReturn ['cep'] = $address->getPostcode ();
				$addressReturn ['endereco'] = $address->getStreet ( 1 );
				$addressReturn ['numero'] = $address->getStreet ( 2 );
				$addressReturn ['bairro'] = $address->getStreet ( 3 );
				$addressReturn ['complemento'] = $address->getStreet ( 4 );
				$addressReturn ['cidade'] = $address->getCity ();
				$addressReturn ['estado'] = converteufSigla ( $regionId );
				$addressReturn ['telefone'] = $address->getTelephone ();
				$addressReturn ['celular'] = $address->getFax ();
				$addressReturn ['idEndereco'] = $address->getEntityId ();
			endif;

		}
	} elseif ($lista == 'Todos') {

		$customer = Mage::getModel ( 'customer/customer' )->load ( $idCliente );

		$i = 0;
		foreach ( $customer->getAddresses () as $address ) {
			$region = Mage::getModel ( 'directory/region' )->load ( $address->getRegionId () );
			$regionId = $region->getName ();

			$addressReturn [$i] ['nomeEndereco'] = $address->getNomeEndereco ();
			$addressReturn [$i] ['nome'] = $address->getFirstname ();
			$addressReturn [$i] ['cep'] = $address->getPostcode ();
			$addressReturn [$i] ['endereco'] = $address->getStreet ( 1 );
			$addressReturn [$i] ['numero'] = $address->getStreet ( 2 );
			$addressReturn [$i] ['bairro'] = $address->getStreet ( 3 );
			$addressReturn [$i] ['complemento'] = $address->getStreet ( 4 );
			$addressReturn [$i] ['cidade'] = $address->getCity ();
			$addressReturn [$i] ['estado'] = converteufSigla ( $regionId );
			$addressReturn [$i] ['telefone'] = $address->getTelephone ();
			$addressReturn [$i] ['celular'] = $address->getFax ();
			$addressReturn [$i] ['idEndereco'] = $address->getEntityId ();

			$i ++;
		}
	}

	if (! $addressReturn) {
		ReturnValidation ( 304, "Cliente não possui endereços cadastrados", $idCliente );
	}

	echo $mensagem = json_encode ( $addressReturn, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );
	die ();

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 39 ) == '/shell/ws/integrador/dadosEndereco') :
	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Json dados Endereco: " . $jsonStr );
	Report ( "Retorno dados Endereco: " . var_export ( $retorno, true ) );

	$idCliente = $retorno ['cadastroEndereco'] ['idCliente'];
	$customer = Mage::getModel ( 'customer/customer' )->load ( $idCliente );

	$addressData ['firstname'] = $customer->getFirstname ();
	$addressData ['lastname'] = $customer->getLastname ();
	$addressData ['postcode'] = $cep = $retorno ['cadastroEndereco'] ['cep'];
	$addressData ['nome_endereco'] = $retorno ['cadastroEndereco'] ['nomeEndereco'];
	;
	// Valida se existe o CEP
	$cep = str_replace ( "-", "", trim ( $cep ) );
	$returnCEP = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->execute ( "SELECT UFE_sg AS uf FROM cep_log_localidade AS localidade WHERE localidade.CEP_DECOD = '$cep'" );

	if ($returnCEP == FALSE) {
		$returnCEP = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->execute ( "SELECT UFE_sg AS uf FROM cep_log_logradouro AS logradouro WHERE logradouro.CEP_DECOD = '$cep'" );
	}

	if ($returnCEP == FALSE) {
		$returnCEP = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->execute ( "SELECT UFE_sg AS uf FROM cep_log_grande_usuario AS grande_usuario WHERE grande_usuario.CEP_DECOD = '$cep'" );
	}

	if ($returnCEP == FALSE) {
		ReturnValidationEndereco ( 304, "Por favor insira um CEP válido." );
	}

	$addressData ['street'] [0] = $endereco = $retorno ['cadastroEndereco'] ['endereco'];
	$addressData ['street'] [1] = $numero = $retorno ['cadastroEndereco'] ['numero'];
	$addressData ['street'] [2] = $bairro = $retorno ['cadastroEndereco'] ['bairro'];
	$addressData ['street'] [3] = $complemento = $retorno ['cadastroEndereco'] ['complemento'];
	$addressData ['city'] = $cidade = $retorno ['cadastroEndereco'] ['cidade'];
	$addressData ['country_id'] = "BR";
	$addressData ['region'] = $estado = converteuf ( $retorno ['cadastroEndereco'] ['estado'] );

	// var_dump($estado); exit;

	$regionModel = Mage::getResourceModel ( 'directory/region_collection' )->addFieldToFilter ( 'default_name', $estado );
	$regionId = $regionModel->getFirstItem ()->getRegionId ();

	// Verifica se o Estado está correto
	if ($regionId == NULL) {
		ReturnValidationEndereco ( 304, "Estado inválido. Por favor insira o Estado correto." );
	}

	$addressData ['region_id'] = $regionId;
	$addressData ['telephone'] = $telefone = $retorno ['cadastroEndereco'] ['telefone'];

	// Valida o Telefone
	if (! preg_match ( '/(\(?\d{2}\)?) ?9?\d{4}-?\d{4}/', $telefone )) {
		ReturnValidationEndereco ( 304, "Telefone inválido. Favor inserir o telefone correto (xx) xxxxx-xxxx" );
	}

	$addressData ['fax'] = $celular = $retorno ['cadastroEndereco'] ['celular'];

	// Valida o Celular
	if (! preg_match ( '/(\(?\d{2}\)?) ?9?\d{4}-?\d{4}/', $celular )) {
		ReturnValidationEndereco ( 304, "Celular inválido. Favor inserir o celular correto (xx) xxxxx-xxxx" );
	}

	$addressData ['vat_id'] = false;
	$addressData ['ramal'] = false;
	$addressData ['tipo_endereco'] = false;

	if ($idCliente != "" && $cep != "" && $endereco != "" && $numero != "" && $bairro != "" && $cidade != "" && $estado != "" && $telefone != "") {
		$address = Mage::getModel ( 'customer/address' );

		$addressId = $retorno ['cadastroEndereco'] ['idEndereco'];

		if ($addressId) {
			$existsAddress = $customer->getAddressById ( $addressId );
			if ($existsAddress->getId ()) {
				$address->setId ( $existsAddress->getId () );
			} else {
				ReturnValidationEndereco ( 304, "Id não encontrado. Favor Verificar" );
			}
		}

		$addressForm = Mage::getModel ( 'customer/form' );
		$addressForm->setFormCode ( 'customer_address_edit' )->setEntity ( $address );
		$addressErrors = $addressForm->validateData ( $addressData );

		if ($addressErrors !== true) {
			Report ( var_export($addressErrors[0], true) );
			ReturnValidationEndereco ( 304, $addressErrors );
		}

		$addressForm->compactData ( $addressData );
		$address->setCustomerId ( $idCliente )->setIsDefaultBilling ( 1 )->setIsDefaultShipping ( 1 );
		$addressErrors = $address->validate ();

		if ($addressErrors !== true) {
			Report ( $addressErrors );
			ReturnValidationEndereco ( 304, $addressErrors[1] );
		}

		$customer->setAtualizaerp ( '511' );
		$customer->save ();

		if (count ( $errors ) === 0) {
			$address->save ();
			$dados ['codigoMensagem'] = 200;
			$dados ['mensagem'] = 'Processo realizado com sucesso.';
			$dados ['idEndereco'] = $address->getId ();
			$dados ['idCliente'] = $idCliente;
			echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
			Report ( $mensagem );
			die ();
		} else {
			Report ( $addressErrors );
			ReturnValidationEndereco ( 304, "Dados do Endereço incorreto." );
		}
	} else {
		if ($idCliente == "")
			$campos .= ', Id do Cliente';

		if ($cep == "")
			$campos .= ', CEP';

		if ($endereco == "")
			$campos .= ', Endereço';

		if ($numero == "")
			$campos .= ', Número';

		if ($bairro == "")
			$campos .= ', Bairro';

		if ($cidade == "")
			$campos .= ', Cidade';

		if ($estado == "")
			$campos .= ', Estado';

		if ($telefone == "")
			$campos .= ', Telefone';

		ReturnValidation ( 325, "Por favor, preencha os campos obrigatórios: " . $campos );
		Report ( "Por favor, preencha os campos obrigatórios: " . $campos );
	}

elseif (substr ( $_SERVER ['REQUEST_URI'], 0, 36 ) == '/shell/ws/integrador/excluirEndereco') :
	$idEndereco = $_GET ['idEndereco'];

	Report ( "Retorno banners excluirEndereco: " . $idEndereco );

	if ($idEndereco) :
		$address = Mage::getModel ( 'customer/address' )->load ( $idEndereco );
		try {
			$address->delete ();

			$dados ['codigoMensagem'] = 200;
			$dados ['mensagem'] = 'Processo realizado com sucesso.';
		} catch ( Exception $e ) {
			$dados ['codigoMensagem'] = 328;
			$dados ['mensagem'] = 'Não foi possível Excluir o Endereço.';
		}
	else :
		ReturnValidation ( 327, "Id Endereço Obrigatório." );
	endif;

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );
	die ();

elseif ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/listaPedidosLoja') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Retorno listaPedidosLoja: " . var_export ( $retorno, true ) );

	$cpf = $retorno ['CPF'];

	Report ( "CPF: " . var_export ( $cpf, true ) );
	if ($cpf) :
		// Chamar webservice ...
		$retval = ExecutaWebservice ( 'http://10.0.0.4/ws_recbtos_aberto_semear.php?cpf=' . $cpf );

		
		Report ( "Retorno ws: " . $retval);
		$retorno = explode ( "|", $retval );
		$i = 0;

		Report ( "Retorno Semear: " . var_export ( strpos ( $retorno [0], "0"  ), true ) );
		Report ( "CPF: " . var_export ( $retorno, true ) );
		
		if ( $retorno [0] == '0') {
			$pedidos = '';
			//$pedidos ['pedidos']['0']['msn'] = '';
		} else {
			foreach ( $retorno as $_retorno ) {
				$return = explode ( ";", $_retorno );
				if ($return [0] != '' && $return [0] != '1') :
					$pedidos ['pedidos'] [$i] ['loja'] = $return [0];
					$pedidos ['pedidos'] [$i] ['numeroPedido'] = $return [1];
					$pedidos ['pedidos'] [$i] ['valorParcela'] = 'R$ '.$return [3];
					$pedidos ['pedidos'] [$i] ['boleto'] = $return [4];
					$i ++;
					endif;
			}
		}
		
		$collection = Mage::getModel ( 'sales/order' )
			->getCollection ()
			->addFieldToFilter ( 'customer_cpf', $cpf )
			->addFieldToFilter ( 'status', 'pending' );
		
		$i = 0;
		
		foreach($collection as $order){
			if($order->getPayment()->getData('method') == 'boleto_bradesco'){
				
				$configmodulo = Mage::getSingleton ( 'boleto/method_bradesco' );
				
				$data_pedido = Mage::helper ( 'core' )->formatDate ( $order->getCreatedAtDate (), 'medium' );
				// DADOS DO BOLETO PARA O SEU CLIENTE - SETUP number field 3
				$dias_de_prazo_para_pagamento = ( string ) $configmodulo->getConfigData ( 'due_date', 0 );
				
				$data_boleto = strtotime ( str_replace ( "'", "", $dataPedido ) );
				
				// Pegando datas para comparar e verificar se o boleto já esta vencido
				$data_now = date ( "Y/m/d" );
				$dataTime2 = date ( "Y/m/d", $data_boleto + ($dias_de_prazo_para_pagamento * 86400) );
				$timestamp1 = str_replace ( "/", "", $dataTime2 );
				$timestamp2 = str_replace ( "/", "", $data_now );
				
				$data_vencimento = Mage::getModel ( 'sales/order' )->getDataVencimentoBoleto ( $data_pedido, $dias_de_prazo_para_pagamento );
				$data_vencimento1 = str_replace("/", "-", $data_vencimento);
				$data_vencimento1 =  date("Y-m-d", strtotime($data_vencimento1));
				
				$timestamp1 = strtotime ( $data_vencimento1);
				
				// Pegando datas para comprar e verificar se o boleto já esta vencido
				$data_now = date ( "Y-m-d" );
				$timestamp2 = strtotime ( $data_now );
				
				if ($timestamp1 < $timestamp2) {
					//Report ( "boleto vencido:");
					continue;
				}
				
				$pedidos ['pedidosecommerce'] [$i] ['numeroPedido'] = $order->getIncrementId();
				$pedidos ['pedidosecommerce'] [$i] ['boleto'] = $order->getPayment()->getData('boleto_linha_digitavel');
				$pedidos ['pedidosecommerce'] [$i] ['valorParcela'] = 'R$ '. number_format ( $order->getGrandTotal(), 2, ',', '' );
				$i++;

			}
		}
		
		if($i == 0){
			$pedidos ['pedidosecommerce']['0'] = array (0 => '0',
												   1 => 'Nao encontrado titulos em aberto para o CPF');
		}
		
		
		echo $mensagem = json_encode ( $pedidos, JSON_UNESCAPED_UNICODE );
		Report ( "mensagem:" . $mensagem );
		
		
	else :
		ReturnValidation ( 346, "CPF obrigatório." );
	endif;

elseif ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/segundaViaBoleto') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Retorno banners segundaViaBoleto: " . var_export ( $retorno, true ) );

	$loja = $retorno ['loja'];
	$pedido = $retorno ['pedido'];
	$cpf = $retorno ['cpf'];

	if ($cpf) :
		$customer = Mage::getModel ( 'customer/customer' )->getCollection ()->addAttributeToSelect ( 'firstname' )->addFieldToFilter ( 'cpf', $cpf )->load ()->getFirstItem ();

		if ($customer->getFirstname ()) :
			// Fazer o Download
			$retval = ExecutaWebservice ( 'http://10.0.0.4/ws_impressao_boletos_semear.php?loja=' . $loja . '&pedido=' . $pedido );
			$grvarq = fopen ( '/var/www/html/ecommerce/public-html/boleto_loja_fisica/' . $pedido . '.pdf', "a+" );
			fwrite ( $grvarq, $retval );
			fclose ( $grvarq );
			$assunto = "Segunda Via do Boleto Referente ao Pedido {$pedido}";

			$linkBoleto = Mage::getUrl ( '', array (
					'_secure' => true
			) ) . 'boleto_loja_fisica/' . $pedido . '.pdf';

			$body = enviarSegundaViaBoleto ( $customer->getFirstname (), $linkBoleto );
			$arquivo = '/var/www/html/ecommerce/public-html/boleto_loja_fisica/' . $pedido . '.pdf';

			$destinatario = $customer->getEmail ();

			// Envio email para o Cliente;
			$mail = new PHPMailer ();
			$mail->IsSMTP ();
			$mail->Host = "10.0.0.35";
			$mail->SetFrom ( "falecom@eletrosom.com", "Eletrosom" );
			$mail->Subject = $assunto;
			$mail->AltBody = "Mensagem enviada em HTML";
			$mail->MsgHTML ( $body );
			$mail->AddAddress ( $destinatario );

			// Insere um anexo
			if ($arquivo != "") {
				$mail->addStringAttachment ( file_get_contents ( $linkBoleto ), $pedido . '.pdf' );
			}

			if (! $mail->Send ()) {
				echo "Erro de envio " . $mail->ErrorInfo;
			}

			$pedidos ['pedidos'] ['linkBoleto'] = $linkBoleto;

			echo $mensagem = stripslashes ( json_encode ( $pedidos, JSON_UNESCAPED_UNICODE ) );
			Report ( $mensagem );
		else :
			ReturnValidation ( 345, "Cliente não encontrado." );
		endif;
	else :
		ReturnValidation ( 346, "CPF obrigatório." );
	endif;

elseif ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/validabandeira') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Valida bandeira: " . var_export ( $retorno, true ) );
	
	return 'VI';
	
elseif ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/deviceToken') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Retorno banners deviceToken: " . var_export ( $retorno, true ) );

	$idCliente = $retorno ['device'] ['idCliente'];
	$emailCliente = $retorno ['device'] ['emailCliente'];
	$token = $retorno ['device'] ['token'];
	$sitemaOperacional = $retorno ['device'] ['sistemaOperacional'];

	try {

		$returnToken = Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->execute ( "SELECT * FROM device_token WHERE token = '$token'" );

		if ($returnToken) :
			if ($returnToken ['id_customer'] == "" || $returnToken ['email_customer'] == "") :
				if ($idCliente != "" || $emailCliente != "") :
					Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->executeInsertUpdate ( "UPDATE device_token SET id_customer = '$idCliente', email_customer = '$emailCliente' WHERE token = '$token'" );
				endif;
			endif;


			$dados ['codigoMensagem'] = 200;
			$dados ['mensagem'] = "Processo realizado com sucesso";

		elseif ($token != "" && $sitemaOperacional != "") :
			if (! $idCliente) :
				$idCliente = 0;
			endif;

			Mage::getModel ( 'akhilleus/carrier_akhilleusapp' )->executeInsertUpdate ( "INSERT INTO device_token(id_customer, email_customer, token, operational_system, date_register) VALUES ($idCliente, '$emailCliente', '$token', '$sitemaOperacional', now())" );

			$dados ['codigoMensagem'] = 200;
			$dados ['mensagem'] = "Processo realizado com sucesso";

		else :
			$dados ['codigoMensagem'] = 403;
			$dados ['mensagem'] = "Não foi possível inserir os Dados. Favor verificar se estão corretos.";
		endif;
	} catch ( Exception $e ) {
		var_dump ( $e );
		die ();
		// $dados['codigoMensagem'] = 402;
		// $dados['mensagem'] = "Token e Sistema Operacional são obrigatórios";
	}

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );

elseif ($_SERVER ['REQUEST_URI'] == '/shell/ws/integrador/trocaSenha') :

	$jsonStr = file_get_contents ( "php://input" ); // read the HTTP body.
	$retorno = json_decode ( $jsonStr, true );

	Report ( "Retorno minhaconta TrocaSenha: " . var_export ( $jsonStr, true ) );

	$username = $retorno ['login'];
	$password = $retorno ['senha'];
	$newpassword = $retorno ['nova_senha'];

	$websiteId = Mage::app ()->getWebsite ()->getId ();
	$store = Mage::app ()->getStore ();
	$customer = Mage::getModel ( "customer/customer" );
	$customer->website_id = $websiteId;
	$customer->setStore ( $store );

	Report ( "Retorno minhaconta Username: " . $username );
	Report ( "Retorno minhaconta Password: " . $password );

	try {

		$customer->loadByEmail ( $username );
		$session = Mage::getSingleton ( 'customer/session' )->setCustomerAsLoggedIn ( $customer );
		$session->login ( $username, $password );

		// $login = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->authenticate($username, $password);
		$validate = 1;
	} catch ( Exception $ex ) {
		Report ( "Retorno minhaconta Exception: " . $ex );

		$validate = 0;
	}

	Report ( "Retorno minhaconta WebSiteId: " . Mage::app ()->getStore ()->getWebsiteId () );
	Report ( "Retorno minhaconta Validate: " . $validate );

	if ($validate == 1) {

		try {
			$customer = Mage::getModel ( "customer/customer" )->setWebsiteId ( Mage::app ()->getWebsite ()->getId () );
			$customer = $customer->loadByEmail ( $username );
			$customer = Mage::getModel ( 'customer/customer' )->load ( $customer->getId () );
			$customer->setPassword ( $newpassword );
			$customer->save ();

			$dados ['codigoMensagem'] = 200;
			$dados ['mensagem'] = "Processo realizado com sucesso";
			$dados ['IdCliente'] = $customer->getId ();
		} catch ( Exception $e ) {
			$dados ['codigoMensagem'] = 317;
			$dados ['mensagem'] = "Login ou Senha Inválidos";
			$dados ['IdCliente'] = 0;
		}
	} else {
		$dados ['codigoMensagem'] = 318;
		$dados ['mensagem'] = "Senha atual incorreta.";
		$dados ['IdCliente'] = 0;
	}

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );

endif;
function ExecutaWebservice($endereco) {
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $endereco );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 15 );

	if (! $retorno = curl_exec ( $ch )) {
		ReturnValidation ( 344, 'Não foi possível conectar ao ws.' );
		curl_close ( $ch );
		die ();
	}

	curl_close ( $ch );
	return remove_utf8_bom($retorno);
}
function Report($texto, $abort = false) {
	$data_log = shell_exec ( 'date +%Y-%m-%d\ %H:%M:%S' );
	$data_log = str_replace ( "\n", "", $data_log );

	$log = fopen ( Mage::getStoreConfig ( 'erp/frontend/url_logs' ) . 'ws_integracao.log', "a+" );
	fwrite ( $log, $data_log . " " . $texto . "\n" );
	fclose ( $log );
	if ($abort) {
		exit ( 0 );
	}
}
function ReturnValidation($codigo, $mensagem, $idCliente = 0) {
	$dados ['codigoMensagem'] = $codigo;
	$dados ['mensagem'] = $mensagem;
	$dados ['IdCliente'] = $idCliente;

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );
	die ();
}
function ReturnValidationEndereco($codigo, $mensagem) {
	$dados ['codigoMensagem'] = $codigo;
	$dados ['mensagem'] = $mensagem;
	$dados ['idEndereco'] = 0;
	$dados ['idCliente'] = 0;

	echo $mensagem = json_encode ( $dados, JSON_UNESCAPED_UNICODE );
	Report ( $mensagem );
	die ();
}
function enviarSegundaViaBoleto($cliente, $linkBoleto) {
	$body = '<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td style="padding:20px 0">
					<table width="660" border="0" align="center" cellpadding="0" cellspacing="0" id="Table_01">
						<tr>
							<td colspan="3">
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/side-top-null.jpg" width="380" height="98" alt="" /></td>
							<td colspan="12"><a href="http://www.eletrosom.com/" target="_blank"><img src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/eletrosom-top.jpg" alt="Eletrosom.com" name="confirmacao_cadastro_newsletter_02" width="280" height="98" border=0 id="confirmacao_cadastro_newsletter_02" style="display:block" /></a></td>
						</tr>
						<tr>
							<td colspan="15">
								<img style="display:block" border=0 id="confirmacao_cadastro_newsletter_03" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/acompanhe.png" width="660" height="35" alt="Acompanhe a Eletrosom.com" /></td>
						</tr>
						<tr>
							<td style="background:#107ccc" width="21" height="28">&nbsp;</td>
							<td colspan="3" style="background:#fff" width="436" height="28">&nbsp;</td>
							<td><a href="http://www.twitter.com/eletrosom" target="_blank"><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/social1.jpg" width="28" height="28" alt="no Twitter" /></a></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><a href="http://www.facebook.com/eletrosom" target="_blank"><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/social2.jpg" width="28" height="28" alt="no Facebook" /></a></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="28" alt="" /></td>
							<td colspan="2"><img style="display:block" border=0 id="confirmacao_cadastro_newsletter_15" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/canto.png" width="50" height="28" alt="" /></td>
						</tr>
						<tr>
							<td style="background:#107ccc"><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="21" height="7" alt="" /></td>
							<td colspan="14">
								<img style="display:block" border=0 id="confirmacao_cadastro_newsletter_17" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/line.png" width="639" height="7" alt="" /></td>
						</tr>
						<tr>
							<td style="background:#107ccc" width="21">&nbsp;</td>
							<td colspan="13" style="padding:0 31px" width="618">
								<h2 style="color:#4b6281; margin:0 0 15px; font:100 18px Arial, Helvetica, sans-serif">' . $cliente . ',</h2>
								<p style="color:#4b6281; margin:0; font:100 11px Arial, Helvetica, sans-serif">
									Agradecemos por escolher a <b>Eletrosom</b> para realizar sua compra. &Eacute; um prazer ter voc&ecirc; como cliente!<br /><br />
									Abaixo est&atilde;o dispon&iacute;veis os Boletos realizados nas <b>Lojas F&iacute;sicas Eletrosom</b>. Para acess&aacute;-los, clique no link abaixo:<br /><br />
									<p><center>
										<a href="' . $linkBoleto . '" target="_blank">
											<img src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/bot_imprimir.jpg" title="Imprimir Boleto" value="Imprimir Boleto" />
										</a>
									</center></p>
									<br /><br />
									<p style="color:#4b6281; margin:0; font:100 11px Arial, Helvetica, sans-serif">
									
									</p>
									<p style="color:#4b6281; margin:0; padding:30px 0; font:100 11px Arial, Helvetica, sans-serif">Agradecemos a sua prefer&ecirc;ncia.</p>
								</p>
							</td>
							<td style="background:#107ccc" width="21">&nbsp;</td>
						</tr>
						<tr>
							<td style="background:#107ccc" width="21">&nbsp;</td>
							<td  colspan="13" style="padding:0 31px" width="618">
								<p style="color:#4b6281; margin:0; padding:0 0; font:100 11px Arial, Helvetica, sans-serif">Atenciosamente,<br /><br />
									<strong style="color:#4b6281; font:700 14px Arial, Helvetica, sans-serif">Equipe Eletrosom</strong><br />
									Central de Atendimento Eletrosom<br />
								</p>
							</td>
							<td style="background:#107ccc" width="21">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="15"><img style="display:block" border=0 id="confirmacao_cadastro_newsletter_21" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/bottom.png" width="660" height="69" alt="" /></td>
						</tr>
						<tr>
							<td colspan="2">
								<img style="display:block" border=0 id="confirmacao_cadastro_newsletter_22" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/top-logo-bottom.jpg" width="226" height="47" alt="" /></td>
							<td colspan="13">
								<img src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/blindado.jpg" alt="" width="434" height="47" border=0 usemap="#confirmacao_cadastro_newsletter_23Map" id="confirmacao_cadastro_newsletter_23" style="display:block" /></td>
						</tr>
						<tr>
							<td colspan="2"><a href="http://www.eletrosom.com/" target="_blank"><img src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/logo-bottom.jpg" alt="Eletrosom.com" name="confirmacao_cadastro_newsletter_24" width="226" height="63" border=0 id="confirmacao_cadastro_newsletter_24" style="display:block" /></a></td>
							<td colspan="13">
								<img style="display:block" border=0 id="confirmacao_cadastro_newsletter_25" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/privacidade-seguranca.jpg" width="434" height="63" alt="A privacidade e a seguran&ccedil;a de nossos clientes s&atilde;o compromissos da eletrosom.com. Investimos nas tecnologias mais modernas e avan&ccedil;adas de prote&ccedil;&atilde;o de seus dados para que voc&ecirc; possa ter seguran&ccedil;a e tranquilidade." /></td>
						</tr>
						<tr>
							<td colspan="2">
								<img style="display:block" border=0 id="confirmacao_cadastro_newsletter_26" src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/bottom-logo-bottom.jpg" width="226" height="16" alt="" /></td>
							<td colspan="13" ><img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="434" height="16"  alt="" /></td>
						</tr>
						<tr>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="21" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="205" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="154" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="77" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="28" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="28" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="29" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="28" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="3" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="28" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="29" height="1" alt="" /></td>
							<td>
								<img style="display:block" border=0 src="http://www.eletrosom.com/skin/frontend/eletrosom/default/img/emails/spacer.gif" width="21" height="1" alt="" /></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<map name="confirmacao_cadastro_newsletter_23Map">
		  <area shape="rect" coords="-2,13,119,50" href="#" target="_blank" alt="Site Blindado">
		</map>';

	return $body;
}
function base64_to_jpeg($base64_string, $id = false) {
	define ( 'UPLOAD_DIR', '/var/www/html/ecommerce/public-html/media/clientes/' );

	$image_parts = explode ( ";base64,", $base64_string );
	$image_type_aux = explode ( "image/", $image_parts [0] );
	$image_type = $image_type_aux [1];
	$image_base64 = base64_decode ( $image_parts [1] );
	$file_name_orig = uniqid () . '.jpg';
	$file_name_dest = "profile_" . $file_name_orig;
	$file_dir_orig = UPLOAD_DIR . $file_name_orig;
	// $file_dir_dest = UPLOAD_DIR . "profile_".$id.'.jpg';
	$file_dir_dest = UPLOAD_DIR . $file_name_dest;
	file_put_contents ( $file_dir_dest, $image_base64 );
	resize ( $file_dir_dest, $file_dir_dest, 240, 240 ); // Redimensionar foto

	return $file_name_dest;
}
function resize($origem, $destino, $wid, $hei) {
	require_once ('/var/www/html/ecommerce/public-html/shell/ws/integrador/ImageManipulator.php');

	$manipulator = new ImageManipulator ( $origem );
	$width = $manipulator->getWidth ();
	$height = $manipulator->getHeight ();
	$centreX = round ( $width / 2 );
	$centreY = round ( $height / 2 );
	// our dimensions will be 200x130
	$x1 = $centreX - 100; // 200 / 2
	$y1 = $centreY - 65; // 130 / 2

	$x2 = $centreX + 100; // 200 / 2
	$y2 = $centreY + 65; // 130 / 2

	// center cropping to 200x130
	$newImage = $manipulator->resample ( $wid, $hei );
	// saving file to uploads folder
	$manipulator->save ( $origem );
}
function converteuf($estado) {
	switch ($estado) {
		case 'AC' :
			$estado = 'Acre';
			break;
		case 'AL' :
			$estado = 'Alagoas';
			break;
		case 'AP' :
			$estado = 'Amapá';
			break;
		case 'AM' :
			$estado = 'Amazonas';
			break;
		case 'BA' :
			$estado = 'Bahia';
			break;
		case 'CE' :
			$estado = 'Ceará';
			break;
		case 'DF' :
			$estado = 'Distrito Federal';
			break;
		case 'ES' :
			$estado = 'Espírito Santo';
			break;
		case 'GO' :
			$estado = 'Goiás';
			break;
		case 'MA' :
			$estado = 'Maranhão';
			break;
		case 'MT' :
			$estado = 'Mato Grosso';
			break;
		case 'MS' :
			$estado = 'Mato Grosso do Sul';
			break;
		case 'MG' :
			$estado = 'Minas Gerais';
			break;
		case 'PA' :
			$estado = 'Pará';
			break;
		case 'PB' :
			$estado = 'Paraíba';
			break;
		case 'PR' :
			$estado = 'Paraná';
			break;
		case 'PE' :
			$estado = 'Pernambuco';
			break;
		case 'PI' :
			$estado = 'Piauí';
			break;
		case 'RJ' :
			$estado = 'Rio de Janeiro';
			break;
		case 'RN' :
			$estado = 'Rio Grande do Norte';
			break;
		case 'RS' :
			$estado = 'Rio Grande do Sul';
			break;
		case 'RO' :
			$estado = 'Rondônia';
			break;
		case 'RR' :
			$estado = 'Roraima';
			break;
		case 'SC' :
			$estado = 'Santa Catarina';
			break;
		case 'SP' :
			$estado = 'São Paulo';
			break;
		case 'SE' :
			$estado = 'Sergipe';
			break;
		case 'TO' :
			$estado = 'Tocantins';
			break;
		default :
			$estado = 'Não informado.';
			break;
	}
	return $estado;
}
function converteufSigla($estado) {
	switch ($estado) {
		case 'Acre' :
			$estado = 'AC';
			break;
		case 'Alagoas' :
			$estado = 'AL';
			break;
		case 'Amapá' :
			$estado = 'AP';
			break;
		case 'Amazonas' :
			$estado = 'AM';
			break;
		case 'Bahia' :
			$estado = 'BA';
			break;
		case 'Ceará' :
			$estado = 'CE';
			break;
		case 'Distrito Federal' :
			$estado = 'DF';
			break;
		case 'Espírito Santo' :
			$estado = 'ES';
			break;
		case 'Goiás' :
			$estado = 'GO';
			break;
		case 'Maranhão' :
			$estado = 'MA';
			break;
		case 'Mato Grosso' :
			$estado = 'MT';
			break;
		case 'Mato Grosso do Sul' :
			$estado = 'MS';
			break;
		case 'Minas Gerais' :
			$estado = 'MG';
			break;
		case 'Pará' :
			$estado = 'PA';
			break;
		case 'Paraíba' :
			$estado = 'PB';
			break;
		case 'Paraná' :
			$estado = 'PR';
			break;
		case 'Pernambuco' :
			$estado = 'PE';
			break;
		case 'Piauí' :
			$estado = 'PI';
			break;
		case 'Rio de Janeiro' :
			$estado = 'RJ';
			break;
		case 'Rio Grande do Norte' :
			$estado = 'RN';
			break;
		case 'Rio Grande do Sul' :
			$estado = 'RS';
			break;
		case 'Rondônia' :
			$estado = 'RO';
			break;
		case 'Roraima' :
			$estado = 'RR';
			break;
		case 'Santa Catarina' :
			$estado = 'SC';
			break;
		case 'São Paulo' :
			$estado = 'SP';
			break;
		case 'Sergipe' :
			$estado = 'SE';
			break;
		case 'Tocantins' :
			$estado = 'TO';
			break;
		default :
			$estado = 'Não informado.';
			break;
	}
	return $estado;
}

function remove_utf8_bom($text)
{
	$bom = pack('H*','EFBBBF');
	$text = preg_replace("/^$bom/", '', $text);
	return $text;
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
				return $bandeira;
			}
		}
		return false;
}
