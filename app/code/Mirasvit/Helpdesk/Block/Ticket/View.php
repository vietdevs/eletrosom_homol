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

use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\User\Model\User[]
     */
    private $users;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Field
     */
    protected $helpdeskField;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Order
     */
    protected $helpdeskOrder;

    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @param \Mirasvit\Helpdesk\Helper\Field $helpdeskField
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\Field $helpdeskField,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder,
        Config $config,
        array $data = []
    ) {
        $this->helpdeskField = $helpdeskField;
        $this->registry      = $registry;
        $this->helpdeskOrder = $helpdeskOrder;
        $this->config        = $config;
        $this->context       = $context;

        parent::__construct($context, $data);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $ticket = $this->getTicket();
        $this->pageConfig->getTitle()->set(__('['.$ticket->getCode().'] '.$ticket->getSubject()));
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle(__($ticket->getSubject()));
        }
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket
     */
    public function getTicket()
    {
        return $this->registry->registry('current_ticket');
    }

    /**
     * @return View\Summary\DefaultRow[]
     */
    public function getSummary()
    {
        $rows = [];

        $names = array_intersect($this->getChildNames(), $this->getGroupChildNames('summary'));

        foreach ($names as $name) {
            $rows[$name] = $this->getChildBlock($name);
        }

        return $rows;
    }

    /**
     * @param View\Summary\DefaultRow $row
     * @param TicketInterface $item
     * @return string
     */
    public function getSummaryHtml(View\Summary\DefaultRow $row, TicketInterface $item)
    {
        $row->setItem($item);

        return $row->toHtml();
    }

    /**
     *
     * @return string
     */
    public function getPostUrl()
    {
        $ticket = $this->getTicket();
        if ($this->registry->registry('external_ticket')) {
            return $this->context->getUrlBuilder()->getUrl(
                'helpdesk/ticket/postexternal',
                ['id' => $ticket->getExternalId()]
            );
        } else {
            return $this->context->getUrlBuilder()->getUrl('helpdesk/ticket/postmessage', ['id' => $ticket->getId()]);
        }
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getCustomFields()
    {
        $collection = $this->helpdeskField->getVisibleCustomerCollection();

        return $collection;
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\Order
     */
    public function getHelpdeskData()
    {
        return $this->helpdeskOrder;
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\Field
     */
    public function getHelpdeskField()
    {
        return $this->helpdeskField;
    }

    /**
     * @return bool
     */
    public function isExternal()
    {
        return $this->getRequest()->getActionName() == 'external';
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null $allowedTags
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        //html can contain incorrect symbols which produce warrnings to log
        $internalErrors = libxml_use_internal_errors(true);
        $res = parent::escapeHtml($data, $allowedTags);
        libxml_use_internal_errors($internalErrors);
        return $res;
    }

    /**
     * @return bool
     */
    public function isAttachmentEnabled()
    {
        return $this->getConfig()->getFrontendIsActiveAttachment();
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Message $message
     * @return bool
     */
    public function getUserSignatureHTML($message)
    {
        if ($message->getUserId()) {
            $user = $this->getUser($message);
            return '<br><br>' . $user->getSignature();
        } else {
            return '';
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Message $message
     * @return \Magento\User\Model\User
     */
    private function getUser($message)
    {
        $userId = $message->getUserId();
        if (empty($this->users[$userId])) {
            $this->users[$userId] = $message->getUser();
        }

        return $this->users[$userId];
    }
}
