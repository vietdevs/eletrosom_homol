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


namespace Mirasvit\Helpdesk\Block\Ticket;

use Mirasvit\Helpdesk\Model\Config as Config;

class Create extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\PriorityFactory
     */
    protected $priorityFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\DepartmentFactory
     */
    protected $departmentFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    protected $ticketCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Field
     */
    protected $helpdeskField;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Order
     */
    protected $helpdeskOrder;
    /**
     * @var \Magento\Framework\Url
     */
    private $urlManager;

    /**
     * @param \Mirasvit\Helpdesk\Model\PriorityFactory $priorityFactory
     * @param \Mirasvit\Helpdesk\Model\DepartmentFactory $departmentFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Mirasvit\Helpdesk\Helper\Field $helpdeskField
     * @param \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Url $urlManager
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\PriorityFactory $priorityFactory,
        \Mirasvit\Helpdesk\Model\DepartmentFactory $departmentFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Helper\Field $helpdeskField,
        \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Url $urlManager,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->priorityFactory = $priorityFactory;
        $this->departmentFactory = $departmentFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->config = $config;
        $this->helpdeskField = $helpdeskField;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->helpdeskOrder = $helpdeskOrder;
        $this->urlManager = $urlManager;
        $this->context = $context;

        parent::__construct($context, $data);
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('Create Ticket'));
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer()
    {
        return $this->customerFactory->create()->load($this->customerSession->getCustomerId());
    }

    /**
     * @return object
     */
    public function getPriorityCollection()
    {
        return $this->priorityFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore());
    }

    /**
     * @return object
     */
    public function getDepartmentCollection()
    {
        return $this->departmentFactory->create()->getPreparedCollection($this->context->getStoreManager()->getStore())
            ->addFieldToFilter('is_show_in_frontend', true);
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderCollection()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('customer_id', (int)$this->getCustomer()->getId());

        return $collection;
    }

    /**
     * @return object
     */
    public function getCustomFields()
    {
        $collection = $this->helpdeskField->getEditableCustomerCollection();

        return $collection;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field $field
     * @return string
     */
    public function getInputHtml($field)
    {
        return $this->helpdeskField->getInputHtml($field);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowPriority()
    {
        return $this->getConfig()->getFrontendIsAllowPriority();
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowDepartment()
    {
        return $this->getConfig()->getFrontendIsAllowDepartment();
    }

    /**
     * @return object
     */
    public function getFrontendIsAllowOrder()
    {
        return $this->getConfig()->getFrontendIsAllowOrder();
    }

    /**
     * @param \Magento\Sales\Model\Order|int $order
     * @param bool|string $url
     *
     * @return string
     */
    public function getOrderLabel($order, $url = false)
    {
        return $this->helpdeskOrder->getOrderLabel($order, $url);
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        $urlManager = clone $this->urlManager;
        if ($id = $this->context->getStoreManager()->getStore()->getId()) {
            $urlManager->setScope($id);
        }

        return $urlManager->getUrl('helpdesk/ticket/postmessage');
    }

    /**
     * @return string
     */
    public function getOrdersUrl()
    {
        $urlManager = clone $this->urlManager;
        if ($id = $this->context->getStoreManager()->getStore()->getId()) {
            $urlManager->setScope($id);
        }

        return $urlManager->getUrl('helpdesk/ticket/loadorders');
    }

    /**
     * @return bool
     */
    public function isAttachmentEnabled()
    {
        return $this->getConfig()->getFrontendIsActiveAttachment();
    }
}
