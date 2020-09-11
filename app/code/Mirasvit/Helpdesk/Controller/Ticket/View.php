<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.127
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Ticket;

use Magento\Framework\Controller\ResultFactory;

class View extends \Mirasvit\Helpdesk\Controller\Ticket
{
    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect|void
     */
    public function execute()
    {
        if ($ticket = $this->_initTicket()) {
            if ($this->redirectMergedTicket($ticket)) {
                return;
            }
            $this->markAsRead($ticket);

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            if ($navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation')) {
                $navigationBlock->setActive('helpdesk');
            }

            return $resultPage;
        } else {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->redirectFactory->create();
            return $resultRedirect->setUrl($this->context->getUrl()->getUrl('*/*/index'));
        }
    }
}
