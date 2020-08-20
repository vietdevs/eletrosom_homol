<?php 

// PATH DA APLICAÇÃO
$appMagento = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR;

// Inclui a biblioteca do Magento
require_once $appMagento.'Mage.php';

// Incializa a aplicação
Mage::app('default');

if($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/login'):
	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);
	
	Report("Retorno banners login: ".var_export($retorno,true));
	
	$username = $retorno['cliente']['login'];
	$password = $retorno['cliente']['senha'];
	
	if($username != NULL && $password  != NULL):
		
		try{
			$login = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->authenticate($username, $password);
			
			$customer = Mage::getModel("customer/customer")->setWebsiteId(Mage::app()->getWebsite()->getId());
			$customer = $customer->loadByEmail($username);
			
			$dados['dados_cliente']['IdCliente'] 	= $customer->getId();
			$dados['dados_cliente']['nome'] 		= $customer->getName();			
			$dados['dados_cliente']['email'] 		= $customer->getEmail();
			$dados['dados_cliente']['data_nasc']	= $customer->getDob();
			
			if( $customer->getGender() == 1) {
				$dados['dados_cliente']['sexo']			= "M";			
			} else {
				$dados['dados_cliente']['sexo']			= "F";
			}
			
			if($customer->getTipopessoa() == "526") {
				$dados['dados_cliente']['tipo_pessoa']	= "F";
				$dados['dados_cliente']['cpf']		= $customer->getCpf();
				$dados['dados_cliente']['rg']		= $customer->getRg();
				
			} else {
				$dados['dados_cliente']['tipo_pessoa']	= "J";
				$dados['dados_cliente']['cnpj']		= $customer->getCpf();
				$dados['dados_cliente']['ie']		= $customer->getRg();
			}
			
// 			$attrIdProfissao 	= $customer->getData('profissao');
// 			$attrProfissao 		= $customer->getResource()->getAttribute('profissao');			
// 			$dados['dados_cliente']['profissao'] 	= $attrProfissao->getSource()->getOptionText($attrIdProfissao);
			
// 			if(!$attrProfissao->getSource()->getOptionText($attrIdProfissao)) { 
// 				$dados['dados_cliente']['profissao'] = '';
// 			}
			
						
			$attrIdEstaCivil 	= $customer->getData('estado_civil');
			$attrEstaCivil 		= $customer->getResource()->getAttribute('estado_civil');
			
			
			$attrIdFoto 	= $customer->getData('foto_cliente');
			$attrFoto 		= $customer->getResource()->getAttribute('foto_cliente');
			
// 			$dados['dados_cliente']['estado_civil'] 	= 	  $attrEstaCivil->getSource()->getOptionText($attrIdEstaCivil);
			
// 			if(!$attrEstaCivil->getSource()->getOptionText($attrIdEstaCivil)) {
// 				$dados['dados_cliente']['estado_civil'] = '';
// 			}
			
			$foto = '';			
			if($customer->getFotoCliente()) {
				$foto = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). "clientes/". $customer->getFotoCliente();
			}			
			
			$dados['dados_cliente']['foto_cliente']		= $foto;
			
			$customer = Mage::getModel('customer/customer')->load($customer->getId());
			foreach ($customer->getAddresses() as $address)		
			
			{
				if($address->getEntityId() == $customer->getDefaultBilling()):				
					$dados['dados_cliente']['endereco']['endereco'] = $address->getStreet(1);
					$dados['dados_cliente']['endereco']['numero'] = $address->getStreet(2);
					$dados['dados_cliente']['endereco']['bairro'] = $address->getStreet(3);
					$dados['dados_cliente']['endereco']['complemento'] = $address->getStreet(4);
					$dados['dados_cliente']['endereco']['cidade'] = $address->getCity();
					$dados['dados_cliente']['endereco']['telefone'] = $address->getTelephone();
					$dados['dados_cliente']['endereco']['celular'] = $address->getFax();
					$dados['dados_cliente']['endereco']['idEndereco'] = $address->getEntityId();
				endif;
			}
			
			
			
			$dados['codigoMensagem'] = 200;
			$dados['mensagem'] = "Processo realizado com sucesso";			
			$dados['atualizaDepartamentos'] = 0;
			
		}catch( Exception $e ){
			$dados['codigoMensagem'] = 317;
			$dados['mensagem'] = "Login ou Senha Inválidos";
			$dados['IdCliente'] = 0;
			$dados['atualizaDepartamentos'] = 0;
		}
	
	else:
		$dados['codigoMensagem'] = 318;
		$dados['mensagem'] = "Login e Senha são Obrigatórios";
		$dados['IdCliente'] = 0;
		$dados['atualizaDepartamentos'] = 0;
	endif;
	
elseif($_SERVER['REQUEST_URI'] == '/shell/ws/integrador/esqueceuSenha'):

	$jsonStr = file_get_contents("php://input"); //read the HTTP body.
	$retorno = json_decode($jsonStr, true);

	Report("Retorno banners esqueceuSenha: " .var_export($retorno,true));
	
	$email = $retorno['cliente']['email'];
	
	if($email):
		$customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
		
		if ($customer->getId()) {
			$newPassword = $customer->generatePassword();
			$customer->changePassword($newPassword, false);
			$customer->sendPasswordReminderEmail();
			
			$dados['codigoMensagem'] = 200;
			$dados['mensagem'] = "Em instantes,  você receberá um e-mail com instruções sobre como recuperar sua senha.";
		} else {
			$dados['codigoMensagem'] = 319;
			$dados['mensagem'] = "Não há contas relacionada com esse e-mail. Por favor, verifique se e-mail está correto.";
		}
	else:
		$dados['codigoMensagem'] = 320;
		$dados['mensagem'] = "E-mail é obrigatório.";
	endif;
endif;

echo $mensagem = json_encode($dados, JSON_UNESCAPED_UNICODE);
Report($mensagem);

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
