<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup;

use Amasty\Storelocator\Model\ResourceModel\ConverterFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DB\FieldToConvert;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RecurringData implements InstallDataInterface
{
    /**
     * @var ConverterFactory
     */
    private $converterFactory;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * UpgradeData constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param ConverterFactory         $converterFactory
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ConverterFactory $converterFactory
    ) {
        $this->productMetadata = $productMetadata;
        $this->converterFactory = $converterFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2', '>=')) {
            $this->prepareEmptyValues($setup);
            $this->convertSerializedDataToJson($setup);
        }
    }

    /**
     * Convert metadata from serialized to JSON format:
     *
     * @param ModuleDataSetupInterface $setup
     *
     * @return void
     */
    public function convertSerializedDataToJson($setup)
    {
        $aggregatedFieldConverter = $this->converterFactory->create();
        $aggregatedFieldConverter->convert(
            [
                new FieldToConvert(
                    \Magento\Framework\DB\DataConverter\SerializedToJson::class,
                    $setup->getTable(InstallSchema::LOCATION_TABLE_NAME),
                    'id',
                    'actions_serialized'
                ),
                new FieldToConvert(
                    \Magento\Framework\DB\DataConverter\SerializedToJson::class,
                    $setup->getTable(Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_TABLE_NAME),
                    'attribute_id',
                    'label_serialized'
                ),
                new FieldToConvert(
                    \Magento\Framework\DB\DataConverter\SerializedToJson::class,
                    $setup->getTable(Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME),
                    'value_id',
                    'options_serialized'
                )
            ],
            $setup->getConnection()
        );
    }

    /**
     * Prepare empty values before convert
     *
     * @param ModuleDataSetupInterface $setup
     *
     * @return void
     */
    public function prepareEmptyValues($setup)
    {
        $connection = $setup->getConnection();
        $connection->update(
            $setup->getTable(InstallSchema::LOCATION_TABLE_NAME),
            ['actions_serialized' => '{}'],
            $connection->quoteInto('actions_serialized = ?', '')
        );
        $connection->update(
            $setup->getTable(Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_TABLE_NAME),
            ['label_serialized' => '{}'],
            $connection->quoteInto('label_serialized = ?', '')
        );
        $connection->update(
            $setup->getTable(Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME),
            ['options_serialized' => '{}'],
            $connection->quoteInto('options_serialized = ?', '')
        );
    }
}
