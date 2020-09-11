<?php
/**
* *
*  @author DCKAP Team
*  @copyright Copyright (c) 2018 DCKAP (https://www.dckap.com)
*  @package Dckap_CustomFields
*/

namespace Dckap\CustomFields\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
* Class InstallSchema
* @package Dckap\CustomFields\Setup
*/
class InstallSchema implements InstallSchemaInterface
{

   /**
    * {@inheritdoc}
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
   public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
   {
       $installer = $setup;

       $installer->startSetup();

       /* While module install, creates columns in quote_address and sales_order_address table */

       $eavTable1 = $installer->getTable('quote');
       $eavTable2 = $installer->getTable('sales_order');

       $columns = [
           'pedido_erp' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => 255,
               'nullable' => true,
               'comment' => 'Pedido ERP',
           ],

           'nota_fiscal' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			   'length' => 255,
               'nullable' => true,
               'comment' => 'Nota Fiscal',
           ],

           'prazo_entrega' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
               'length' => 10,
               'nullable' => true,
               'comment' => 'Select option',
           ],
           'customer_rg' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => 255,
               'nullable' => true,
               'comment' => 'Customer RG',
           ],
           'customer_cpf' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => 255,
               'nullable' => true,
               'comment' => 'Customer CPF',
           ],
           'valorfretecalc' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
               'nullable' => true,
               'comment' => 'Valor Frete Calc',
           ],
           'prazo' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			   'length' => 11,
               'nullable' => true,
               'comment' => 'Prazo',
           ],
           'pedido_fornecedor' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
			   'length' => 15,
               'nullable' => true,
               'comment' => 'Pedido Forncedor',
           ],
           'pedido_cappi' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	       'length' => 15,
               'nullable' => true,
               'comment' => 'Pedido Cappi',
           ],
           'referrer' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => 255,
               'nullable' => true,
               'comment' => 'Select option',
           ],
           'pincode' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
               'nullable' => true,
               'comment' => 'Pin Code',
           ],
           'valorparc' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
	       'length' => '12,4',
               'nullable' => true,
               'comment' => 'Valor Parcela',
           ],	
           'userorder' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
               'length' => 11,
               'nullable' => true,
               'comment' => 'Select option',
           ],
           'tipo_consumo' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
               'nullable' => true,
               'comment' => 'Tipo Consumo',
           ],
           'clearsale_pedido_enviado' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => '2M',
               'nullable' => true,
               'comment' => 'ClearSale Pedido Enviado',
           ],		
           'clearsale_pedido_enviado2' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => '2M',
               'nullable' => true,
               'comment' => 'ClearSale Pedido Enviado Cartao2',
           ],	
           'clearsale_pedido_aprovado' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => '2M',
               'nullable' => true,
               'comment' => 'ClearSale Pedido Aprovado',
           ],	
           'clearsale_pedido_enviado' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => '2M',
               'nullable' => true,
               'comment' => 'ClearSale Pedido Enviado',
           ],	
           'clearsale_pedido_aprovado2' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'length' => '2M',
               'nullable' => true,
               'comment' => 'ClearSale Pedido Aprovado Cartao2',
           ],	
           'clearsale_data_enviado' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
               'nullable' => true,
               'comment' => 'ClearSale Data Enviado',
           ],	
           'clearsale_data_enviado2' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
               'nullable' => true,
               'comment' => 'ClearSale Data Enviado Cartao2',
           ],	
           'clearsale_data_aprovado' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
               'nullable' => true,
               'comment' => 'ClearSale Data Aprovado',
           ],	
           'clearsale_data_aprovado2' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
               'nullable' => true,
               'comment' => 'ClearSale Data Aprovado Cartao2',
           ],	
           'clearsale_status' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'ClearSale Status',
           ],	
           'clearsale_status2' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'ClearSale Status Cartao2',
           ],		
           'referencia' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Referencia',
           ],	
           'responsavel' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Responsavel',
           ],	
           'documento_responsavel' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Documento Responsavel',
           ],	
           'partner' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Parceiro',
           ],		
           'partner_link_boleto' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Link Boleto Parceiro',
           ],
           'partner_boleto_venc' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Vencimento Boleto Parceiro',
           ],
           'ordem_compra' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Ordem de Compra',
           ],
           'reanalise_clearsale' => [
               'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
               'nullable' => true,
               'comment' => 'Envia Reanalise ClearSale',
           ],		   
		   
		   
       ];

       $connection = $installer->getConnection();
       foreach ($columns as $name => $definition) {
          $connection->addColumn($eavTable1, $name, $definition);
          $connection->addColumn($eavTable2, $name, $definition);
       }
       $installer->endSetup();
   }
}

