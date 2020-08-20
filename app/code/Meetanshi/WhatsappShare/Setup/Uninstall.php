<?php

namespace Meetanshi\WhatsappShare\Setup {
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
    class Uninstall implements UninstallInterface
    {

        protected $eavSetupFactory;

        public function __construct(EavSetupFactory $eavSetupFactory)
        {
            $this->eavSetupFactory = $eavSetupFactory;
        }

        public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
        {
            $setup->startSetup();
            $eavSetup = $this->eavSetupFactory->create();
            $eavSetup->removeAttribute(4, 'whatsapp_share');
            $eavSetup->removeAttribute(3, 'whatsapp_share');
            $setup->endSetup();

        }
    }

}