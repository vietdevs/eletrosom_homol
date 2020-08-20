<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup\Operation;

use Amasty\Base\Model\Serializer;
use Amasty\Storelocator\Helper\Data as locatorHelper;
use Amasty\Storelocator\Model\ImageProcessor;
use Amasty\Storelocator\Model\ScheduleFactory;
use Amasty\Storelocator\Setup\InstallSchema;
use Amasty\Storelocator\Ui\DataProvider\Form\ScheduleDataProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Escaper;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeTo200
 */
class UpgradeTo200
{
    const META_DESCRIPTION_LIMIT = 500;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var locatorHelper
     */
    private $helper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Escaper
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var array
     */
    private $zeroSchedule = ['00', '00'];

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    public function __construct(
        Serializer $serializer,
        ScheduleFactory $scheduleFactory,
        locatorHelper $helper,
        Filesystem $filesystem,
        Escaper $escaper,
        ImageProcessor $imageProcessor
    ) {
        $this->serializer = $serializer;
        $this->scheduleFactory = $scheduleFactory;
        $this->helper = $helper;
        $this->filesystem = $filesystem;
        $this->escaper = $escaper;
        $this->imageProcessor = $imageProcessor;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function execute(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $locationsData = $this->getCurrentLocations($setup);
        $i = 1;
        foreach ($locationsData as $location) {
            $this->updateMedia($setup, $location);
            $scheduleArray = $this->serializer->unserialize($location['schedule']);
            $scheduleArray = $this->prepareOldSchedule($scheduleArray);
            $dataForUpdate = [
                'url_key'  => $this->prepareLocationUrl($location['name']),
                'meta_description' => substr(
                    $this->escaper->escapeHtml($location['description']),
                    0,
                    self::META_DESCRIPTION_LIMIT
                ),
                'meta_title' => $location['name'],
                'meta_robots' => 'NOINDEX,NOFOLLOW'
            ];
            if (!is_array($scheduleArray) || !$this->needSaveSchedule($scheduleArray)) {
                $this->updateLocation($setup, $location['id'], $dataForUpdate);
                continue;
            }
            $scheduleModel = $this->scheduleFactory->create();
            $newSchedule = $this->prepareSchedule($scheduleArray);
            $scheduleModel = $scheduleModel->load($newSchedule, 'schedule');
            if (!$scheduleModel->getId()) {
                $scheduleModel->setName('Schedule ' . $i)
                    ->setSchedule($newSchedule);
                $scheduleModel->save();
                $i++;
            }
            $dataForUpdate['schedule'] = $scheduleModel->getId();
            $this->updateLocation($setup, $location['id'], $dataForUpdate);
        }
        $this->moveSettings($setup);
    }

    /**
     * @param array $scheduleArray
     *
     * @return bool
     */
    private function needSaveSchedule($scheduleArray)
    {
        foreach ($this->helper->getDaysNames() as $dayKey => $day) {
            if (isset($scheduleArray[$dayKey])
                && ($scheduleArray[$dayKey][ScheduleDataProvider::OPEN_TIME] !== $this->zeroSchedule
                    || $scheduleArray[$dayKey][ScheduleDataProvider::CLOSE_TIME] !== $this->zeroSchedule)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $scheduleArray
     *
     * @return array
     */
    private function prepareOldSchedule($scheduleArray)
    {
        $preparedOldSchedule = [];
        $dayNames = $this->helper->getDaysNames();
        $dayKeyNames = array_keys($dayNames);

        if (!empty($scheduleArray)) {
            foreach ($scheduleArray as $scheduleDay => $schedule) {
                if (is_numeric($scheduleDay)) {
                    $dayCode = $dayKeyNames[$scheduleDay - 1];

                    if ($dayCode) {
                        $preparedOldSchedule[$dayCode] = $schedule;
                    }
                } else {
                    $preparedOldSchedule[$scheduleDay] = $schedule;
                }
            }
        }

        return $preparedOldSchedule;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param array $location
     */
    private function updateMedia($setup, $location)
    {
        if (isset($location['marker_img']) && $location['marker_img']) {
            $this->moveFile(
                $location['marker_img'],
                ImageProcessor::AMLOCATOR_MEDIA_PATH . DIRECTORY_SEPARATOR . $location['id'],
                true
            );
        }

        if (isset($location['store_img']) && $location['store_img']) {
            $this->moveFile(
                $location['store_img'],
                ImageProcessor::AMLOCATOR_GALLERY_MEDIA_PATH . DIRECTORY_SEPARATOR . $location['id']
            );
            $imageData = [
                'location_id' => $location['id'],
                'image_name'  => $location['store_img'],
                'is_base'     => 1
            ];
            $setup->getConnection()->insert(
                $setup->getTable(CreateGalleryTable::TABLE_NAME),
                $imageData
            );
        }
    }

    /**
     * @param string $fileName
     * @param string $path
     * @param bool $needResize
     */
    private function moveFile($fileName, $path, $needResize = false)
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if ($mediaDirectory->isExist('amasty/amlocator/' . $fileName)) {
            $mediaDirectory->copyFile(
                $mediaDirectory->getAbsolutePath('amasty/amlocator/' . $fileName),
                $mediaDirectory->getAbsolutePath(
                    $path . DIRECTORY_SEPARATOR . $fileName
                )
            );
            if ($needResize) {
                $this->imageProcessor->prepareImage(
                    $mediaDirectory->getAbsolutePath(
                        $path . DIRECTORY_SEPARATOR . $fileName
                    ),
                    ImageProcessor::MARKER_IMAGE_TYPE,
                    $needResize
                );
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string $url
     */
    private function prepareLocationUrl($name)
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    }

    /**
     * @param $oldSchedule
     *
     * @return string
     */
    private function prepareSchedule($oldSchedule)
    {
        $newSchedule = [];

        foreach ($oldSchedule as $dayKey => $schedule) {
            $newSchedule[$dayKey][$dayKey . '_status'] = 1;
            $newSchedule[$dayKey][ScheduleDataProvider::OPEN_TIME][ScheduleDataProvider::HOURS] =
                $schedule[ScheduleDataProvider::OPEN_TIME][0];
            $newSchedule[$dayKey][ScheduleDataProvider::OPEN_TIME][ScheduleDataProvider::MINUTES] =
                $schedule[ScheduleDataProvider::OPEN_TIME][1];

            $newSchedule[$dayKey][ScheduleDataProvider::START_BREAK_TIME][ScheduleDataProvider::HOURS] = '00';
            $newSchedule[$dayKey][ScheduleDataProvider::START_BREAK_TIME][ScheduleDataProvider::MINUTES] = '00';

            $newSchedule[$dayKey][ScheduleDataProvider::END_BREAK_TIME][ScheduleDataProvider::HOURS] = '00';
            $newSchedule[$dayKey][ScheduleDataProvider::END_BREAK_TIME][ScheduleDataProvider::MINUTES] = '00';

            $newSchedule[$dayKey][ScheduleDataProvider::CLOSE_TIME][ScheduleDataProvider::HOURS] =
                $schedule[ScheduleDataProvider::CLOSE_TIME][0];
            $newSchedule[$dayKey][ScheduleDataProvider::CLOSE_TIME][ScheduleDataProvider::MINUTES] =
                $schedule[ScheduleDataProvider::CLOSE_TIME][1];
        }

        return $this->serializer->serialize($newSchedule);
    }

    /**
     * @param $setup
     *
     * @return array
     */
    private function getCurrentLocations($setup)
    {
        return $setup->getConnection()->fetchAll(
            $setup->getConnection()->select()
                ->from(
                    ['locations' => $setup->getTable(InstallSchema::LOCATION_TABLE_NAME)]
                )
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function moveSettings(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $values = [
            'amlocator/general/api' => 'amlocator/locator/api',
            'amlocator/general/linktext' => 'amlocator/locator/linktext',
            'amlocator/general/new_page' => 'amlocator/locator/new_page',
            'amlocator/geoip/zoom' => 'amlocator/locator/zoom',
            'amlocator/geoip/clustering' => 'amlocator/locator/clustering',
            'amlocator/locator/main_settings/url' => 'amlocator/general/url',
            'amlocator/locator/main_settings/meta_title' => 'amlocator/general/meta_title',
            'amlocator/locator/main_settings/meta_description' => 'amlocator/general/meta_description',
            'amlocator/locator/main_settings/pagination_limit' => 'amlocator/locator/pagination_limit',
            'amlocator/locator/main_settings/allowed_countries' => 'amlocator/locator/allowed_countries',
            'amlocator/locator/visual_settings/distance' => 'amlocator/locator/distance',
            'amlocator/locator/visual_settings/radius_type' => 'amlocator/locator/radius_type',
            'amlocator/locator/visual_settings/radius_max_value' => 'amlocator/locator/radius_max_value',
            'amlocator/locator/visual_settings/radius' => 'amlocator/locator/radius',
            'amlocator/locator/visual_settings/template' => 'amlocator/locator/template',
            'amlocator/locator/visual_settings/store_list_template' => 'amlocator/locator/store_list_template',
            'amlocator/locator/store_list_settings/close_text' => 'amlocator/locator/close_text',
            'amlocator/locator/store_list_settings/break_time_text' => 'amlocator/locator/break_time_text',
            'amlocator/locator/store_list_settings/convert_time' => 'amlocator/locator/convert_time',
            'amlocator/locator/store_list_settings/count_distance' => 'amlocator/locator/count_distance'
        ];
        foreach ($values as $newValue => $oldValue) {
            $connection->update(
                $setup->getTable('core_config_data'),
                ['path' => $newValue],
                ['path = ?' => $oldValue]
            );
        }
    }

    /**
     * @param $setup
     * @param $locationId
     * @param $dataForUpdate
     */
    private function updateLocation($setup, $locationId, $dataForUpdate)
    {
        $setup->getConnection()->update(
            $setup->getTable(InstallSchema::LOCATION_TABLE_NAME),
            $dataForUpdate,
            ['id = ?' => $locationId]
        );
    }

    /**
     * @param $setup
     */
    public function addMetaData($setup)
    {
        $locationTable = $setup->getTable(InstallSchema::LOCATION_TABLE_NAME);

        $setup->getConnection()
            ->addColumn(
                $locationTable,
                'url_key',
                [
                    'type' => Table::TYPE_TEXT,
                    'default' => null,
                    'length' => 255,
                    'comment' => 'Url Key'
                ]
            );
        $setup->getConnection()
            ->addColumn(
                $locationTable,
                'meta_title',
                [
                    'type' => Table::TYPE_TEXT,
                    'default' => null,
                    'length' => 255,
                    'comment' => 'Meta Title'
                ]
            );
        $setup->getConnection()
            ->addColumn(
                $locationTable,
                'meta_description',
                [
                    'type' => Table::TYPE_TEXT,
                    'default' => null,
                    'length' => null,
                    'comment' => 'Meta Description'
                ]
            );
        $setup->getConnection()
            ->addColumn(
                $locationTable,
                'meta_robots',
                [
                    'type' => Table::TYPE_TEXT,
                    'default' => null,
                    'length' => null,
                    'comment' => 'Meta Robots'
                ]
            );
        $setup->getConnection()
            ->addColumn(
                $locationTable,
                'short_description',
                [
                    'type' => Table::TYPE_TEXT,
                    'default' => null,
                    'length' => null,
                    'comment' => 'Short Description'
                ]
            );
    }
}
