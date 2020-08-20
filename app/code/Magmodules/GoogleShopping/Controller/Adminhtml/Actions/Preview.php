<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magmodules\GoogleShopping\Model\Feed as FeedModel;
use Magmodules\GoogleShopping\Helper\General as GeneralHelper;
use Magmodules\GoogleShopping\Exceptions\Validation as ValidationException;

/**
 * Class Preview
 *
 * @package Magmodules\GoogleShopping\Controller\Adminhtml\Actions
 */
class Preview extends Action
{

    /**
     * @var FeedModel
     */
    private $feedModel;

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * Preview constructor.
     *
     * @param Context       $context
     * @param FeedModel     $feedModel
     * @param GeneralHelper $generalHelper
     * @param Emulation     $appEmulation
     */
    public function __construct(
        Context $context,
        FeedModel $feedModel,
        GeneralHelper $generalHelper,
        Emulation $appEmulation
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        $this->appEmulation = $appEmulation;
        parent::__construct($context);
    }

    /**
     * Execute function for preview of the GoogleShopping feed in admin.
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        if (!$this->generalHelper->getEnabled()) {
            $errorMsg = __('Please enable the extension before generating the feed.');
            $this->messageManager->addErrorMessage($errorMsg);
        } else {
            try {
                $page = $this->getRequest()->getParam('page', 1);
                $productId = $this->getRequest()->getParam('pid', []);
                $data = $this->getRequest()->getParam('data', 0);
                if ($result = $this->feedModel->generateByStore($storeId, 'preview', $productId, $page, $data)) {
                    $this->getResponse()->setHeader('Content-type', 'text/xml');
                    return $this->getResponse()->setBody(file_get_contents($result['path']));
                } else {
                    $errorMsg = __('Unkown error.');
                    $this->messageManager->addErrorMessage($errorMsg);
                }
            } catch (ValidationException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->generalHelper->addTolog('Generate', $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t generate the feed right now, please check error log')
                );
                $this->generalHelper->addTolog('Generate', $e->getMessage());
            }
        }

        $this->appEmulation->stopEnvironmentEmulation();
        $this->_redirect('adminhtml/system_config/edit/section/magmodules_googleshopping');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_GoogleShopping::config');
    }
}
