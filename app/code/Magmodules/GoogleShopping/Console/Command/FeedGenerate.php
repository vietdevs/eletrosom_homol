<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magmodules\GoogleShopping\Model\Feed as FeedModel;
use Magmodules\GoogleShopping\Helper\General as GeneralHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\State as AppState;

/**
 * Class FeedGenerate
 *
 * @package Magmodules\GoogleShopping\Console\Command
 */
class FeedGenerate extends Command
{

    const COMMAND_NAME = 'googleshopping:feed:generate';
    /**
     * @var FeedModel
     */
    private $feedModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var AppState
     */
    private $appState;
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * FeedGenerate constructor.
     *
     * @param FeedModel     $feedModel
     * @param GeneralHelper $generalHelper
     * @param AppState      $appState
     * @param Emulation     $appEmulation
     */
    public function __construct(
        FeedModel $feedModel,
        GeneralHelper $generalHelper,
        AppState $appState,
        Emulation $appEmulation
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        $this->appState = $appState;
        $this->appEmulation = $appEmulation;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Generate GoogleShopping XML Feed');
        $this->addOption(
            'store-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'Store ID of the export feed. If not specified all enabled stores will be exported'
        );
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getOption('store-id');
        $this->appState->setAreaCode('frontend');

        if (empty($storeId) || !is_numeric($storeId)) {
            $output->writeln('<info>Start Generating feed for all stores</info>');
            $storeIds = $this->generalHelper->getEnabledArray('magmodules_googleshopping/generate/enable');
            foreach ($storeIds as $storeId) {
                $this->generateFeed($storeId, $output);
            }
        } else {
            $output->writeln('<info>Start Generating feed for Store ID ' . $storeId . '</info>');
            $this->generateFeed($storeId, $output);
        }
    }

    /**
     * @param                 $storeId
     * @param OutputInterface $output
     */
    private function generateFeed($storeId, OutputInterface $output)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        try {
            $result = $this->feedModel->generateByStore($storeId, 'cli');
            $msg = sprintf(
                'Store ID %s: Generated feed with %s product in %s',
                $storeId,
                $result['qty'],
                $result['time']
            );
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('Generate', $e->getMessage());
            $msg = sprintf('Store ID %s: %s', $storeId, $e->getMessage());
        }
        $output->writeln($msg);
        $this->appEmulation->stopEnvironmentEmulation();
    }
}
