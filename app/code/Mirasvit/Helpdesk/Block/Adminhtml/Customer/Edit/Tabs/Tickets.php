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


namespace Mirasvit\Helpdesk\Block\Adminhtml\Customer\Edit\Tabs;

use Magento\Customer\Controller\RegistryConstants;

class Tickets extends \Magento\Backend\Block\Template implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'tab/tickets.phtml';
    /**
     * @var \Mirasvit\Helpdesk\Api\Service\Customer\CustomerManagementInterface
     */
    private $customerManagement;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Tickets constructor.
     * @param \Mirasvit\Helpdesk\Api\Service\Customer\CustomerManagementInterface $customerManagement
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Api\Service\Customer\CustomerManagementInterface $customerManagement,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->customerManagement = $customerManagement;
        $this->registry           = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Help Desk Tickets (%1)', $this->getTicketsAmount());
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Help Desk Tickets (%1)', $this->getTicketsAmount());
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->getCustomerId();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @return int
     */
    private function getCustomerId()
    {
        return $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return int|string
     */
    private function getTicketsAmount()
    {
        return $this->getCustomerId() ? $this->customerManagement->getTicketsAmount($this->getCustomerId()) : '';
    }
}
