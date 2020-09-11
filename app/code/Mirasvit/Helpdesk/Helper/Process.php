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



namespace Mirasvit\Helpdesk\Helper;

use Mirasvit\Helpdesk\Model\Config as Config;

/**
 * Class Process.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Process
{
    /**
     * @var int|null
     */
    private $orderLength = null;

    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    protected $ticketFactory;

    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $orderAddressFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\GatewayFactory
     */
    protected $gatewayFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    protected $ticketCollectionFactory;

    /**
     * @var \Magento\User\Model\ResourceModel\User\CollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory
     */
    protected $departmentCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory
     */
    protected $quoteAddressCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Pattern\CollectionFactory
     */
    protected $patternCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory
     */
    protected $messageCollectionFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Customer
     */
    protected $helpdeskCustomer;

    /**
     * @var \Mirasvit\Helpdesk\Helper\StringUtil
     */
    protected $helpdeskString;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Field
     */
    protected $helpdeskField;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Tag
     */
    protected $helpdeskTag;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Draft
     */
    protected $helpdeskDraft;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Encoding
     */
    protected $helpdeskEncoding;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $auth;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory
     * @param \Magento\Sales\Model\Order\AddressFactory $orderAddressFactory
     * @param \Mirasvit\Helpdesk\Model\GatewayFactory $gatewayFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $departmentCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Pattern\CollectionFactory $patternCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory $messageCollectionFactory
     * @param Config $config
     * @param Customer $helpdeskCustomer
     * @param StringUtil $helpdeskString
     * @param Field $helpdeskField
     * @param Tag $helpdeskTag
     * @param Draft $helpdeskDraft
     * @param Encoding $helpdeskEncoding
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory,
        \Magento\Sales\Model\Order\AddressFactory $orderAddressFactory,
        \Mirasvit\Helpdesk\Model\GatewayFactory $gatewayFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $departmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Pattern\CollectionFactory $patternCollectionFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory $messageCollectionFactory,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Helper\Customer $helpdeskCustomer,
        \Mirasvit\Helpdesk\Helper\StringUtil $helpdeskString,
        \Mirasvit\Helpdesk\Helper\Field $helpdeskField,
        \Mirasvit\Helpdesk\Helper\Tag $helpdeskTag,
        \Mirasvit\Helpdesk\Helper\Draft $helpdeskDraft,
        \Mirasvit\Helpdesk\Helper\Encoding $helpdeskEncoding,
        \Magento\Framework\Escaper $escaper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->ticketFactory = $ticketFactory;
        $this->orderAddressFactory = $orderAddressFactory;
        $this->gatewayFactory = $gatewayFactory;
        $this->orderFactory = $orderFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->departmentCollectionFactory = $departmentCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->quoteAddressCollectionFactory = $quoteAddressCollectionFactory;
        $this->patternCollectionFactory = $patternCollectionFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->config = $config;
        $this->helpdeskCustomer = $helpdeskCustomer;
        $this->helpdeskString = $helpdeskString;
        $this->helpdeskField = $helpdeskField;
        $this->helpdeskTag = $helpdeskTag;
        $this->helpdeskDraft = $helpdeskDraft;
        $this->helpdeskEncoding = $helpdeskEncoding;
        $this->escaper = $escaper;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->customerSession = $customerSession;
        $this->auth = $auth;
        $this->context = $context;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Creates ticket from frontend.
     *
     * @param array  $post
     * @param string $channel
     *
     * @return \Mirasvit\Helpdesk\Model\Ticket
     */
    public function createFromPost($post, $channel)
    {
        $ticket = $this->ticketFactory->create();
        // если кастомер не был авторизирован, то ищем его
        $customer = $this->helpdeskCustomer->getCustomerByPost($post);

        $ticket->setCustomerId($customer->getId())
            ->setCustomerEmail($customer->getEmail())
            ->setCustomerName($customer->getName())
            ->setQuoteAddressId($customer->getQuoteAddressId())
            ->setCode($this->helpdeskString->generateTicketCode())
            ->setSubject($post['subject']);
        //->setDescription($this->getEnviromentDescription());

        // to prevent exception "undefined index 'customer_email'"
        if (!isset($post['customer_email'])) {
            $post['customer_email'] = $customer->getEmail();
        }
        if (isset($post['priority_id'])) {
            $ticket->setPriorityId((int) $post['priority_id']);
        }
        if (isset($post['department_id'])) {
            $ticket->setDepartmentId((int) $post['department_id']);
        } else {
            $ticket->setDepartmentId($this->getConfig()->getContactFormDefaultDepartment());
        }
        if (isset($post['order_id'])) {
            $ticket->setOrderId((int) $post['order_id']);
        }
        $ticket->setStoreId($this->storeManager->getStore()->getId());
        $ticket->setChannel($channel);
        if ($channel == Config::CHANNEL_FEEDBACK_TAB) {
            $url = $this->customerSession->getFeedbackUrl();
            $ticket->setChannelData(['url' => $url]);
        }

        $this->helpdeskField->processPost($post, $ticket);
        $ticket->save();
        $pattern = $this->checkPostForSpamPattern($post);
        if ($pattern) {
            $ticket->markAsSpam();
        }
        $body = $post['message'];
        if (!empty($post['current_url'])) {
            $body .= "\n\n" . __('Submitted from the page: ') . $this->escapeHtml($post['current_url']);
        }
        $ticket->addMessage(
            $body,
            $customer,
            false,
            Config::CUSTOMER,
            Config::MESSAGE_PUBLIC,
            false,
            Config::FORMAT_PLAIN
        );

        return $ticket;
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
        $res = $this->escaper->escapeHtml($data, $allowedTags);
        libxml_use_internal_errors($internalErrors);

        return $res;
    }

    /**
     * @return string
     */
    public function getEnviromentDescription()
    {
        return print_r($_SERVER, true);
    }

    /**
     * @param array $data
     * @param \Magento\User\Model\User $user
     *
     * @return \Mirasvit\Helpdesk\Model\Ticket
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @fixme
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function createOrUpdateFromBackendPost($data, $user)
    {
        $ticket = $this->ticketFactory->create();
        if (isset($data['ticket_id']) && (int) $data['ticket_id'] > 0) {
            $ticket->load((int) $data['ticket_id']);
        } else {
            unset($data['ticket_id']);
        }
        if (class_exists('\Magento\Framework\Validator\EmailAddress')) { // for m2.2.x
            $validator = new \Magento\Framework\Validator\EmailAddress();
        } else {
            $validator = new \Zend_Validate_EmailAddress();
        }
        if (!$validator->isValid($data['customer_email'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Customer Email'));
        }
        if (!isset($data['customer_id']) || !$data['customer_id']) {
            if (!$ticket->getCustomerName()) {
                $data['customer_name'] = $data['customer_email'];
            }
        }
        if (isset($data['customer_id']) && strpos($data['customer_id'], 'address_') !== false) {
            $data['quote_address_id'] = (int) str_replace('address_', '', $data['customer_id']);
            $data['customer_id'] = null;
            if ($data['quote_address_id']) {
                $quoteAddress = $this->orderAddressFactory->create();
                $quoteAddress->load($data['quote_address_id']);
                $data['customer_name'] = $quoteAddress->getName();
            }
        } else {
            $data['quote_address_id'] = null;
        }

        $ticket->addData($data);

        $this->helpdeskTag->setTags($ticket, $data['tags']);
        //set custom fields
        $this->helpdeskField->processPost($data, $ticket);
        //set ticket user and department
        if (isset($data['owner'])) {
            $data['owner'] = $this->modifyOwner($data['owner'], $user->getId(), $ticket->getId());
            $ticket->initOwner($data['owner']);
        }
        if (isset($data['fp_owner'])) {
            $ticket->initOwner($data['fp_owner'], 'fp');
        }
        if (isset($data['fp_period_unit']) && $data['fp_period_unit'] == 'custom') {
            $value = $this->localeDate->formatDate($ticket->getData('fp_execute_at'), \IntlDateFormatter::SHORT);
            $ticket->setData('fp_execute_at', $value);
        } elseif (isset($data['fp_period_value']) && $data['fp_period_value']) {
            $ticket->setData('fp_execute_at', $this->createFpDate($data['fp_period_unit'], $data['fp_period_value']));
        }
        if (!$ticket->getId()) {
            $ticket->setChannel(Config::CHANNEL_BACKEND);
        }

        $filesData = $this->context->getRequest()->getFiles();
        if (trim($data['reply']) || !empty($filesData['attachment'][0]['name'])) {
            $ticket->setMessageSender($user->getId());
        }
        $ticket->save();

        $bodyFormat = Config::FORMAT_PLAIN;
        if ($this->getConfig()->getGeneralIsWysiwyg()) {
            $bodyFormat = Config::FORMAT_HTML;
        }
        if ($ticket->getMessageSender()) {
            $ticket->addMessage($data['reply'], false, $user, Config::USER, $data['reply_type'], false, $bodyFormat);
        }
        if ($ticket->getCustomerId() && isset($data['customer_note'])) {
            $ticket->addNote($data['customer_note']);
        }

        $this->helpdeskDraft->clearDraft($ticket);

        return $ticket;
    }

    /**
     * @param string $dataOwner
     * @param int    $userId
     * @param bool   $ticketId
     *
     * @return string
     */
    public function modifyOwner($dataOwner, $userId, $ticketId)
    {
        $owner = $dataOwner;
        $owner = explode('_', $owner);
        //set owner for a new ticket
        if (!$owner[1] && !$ticketId) {
            $departmentIds = $this->getUserDepartmentIds($userId);
            if (!in_array($owner[0], $departmentIds)) {
                $owner[0] = array_shift($departmentIds);
            }
            $owner[1] = $userId;
        }
        $owner = $owner[0] . '_' . $owner[1];
        return $owner;
    }

    /**
     * @param int    $userId
     *
     * @return array
     */
    public function getUserDepartmentIds($userId)
    {
        $departments = $this->departmentCollectionFactory->create();
        $departments->addUserFilter($userId)->addFieldToFilter('is_active', true);
        return $departments->getAllIds();
    }
    /**
     * @param string $unit
     * @param int    $value
     *
     * @return string
     */
    public function createFpDate($unit, $value)
    {
        $timeshift = 0;
        switch ($unit) {
            case 'minutes':
                $timeshift = $value;
                break;
            case 'hours':
                $timeshift = $value * 60;
                break;
            case 'days':
                $timeshift = $value * 60 * 24;
                break;
            case 'weeks':
                $timeshift = $value * 60 * 24 * 7;
                break;
            case 'months':
                $timeshift = $value * 60 * 24 * 31;
                break;
        }
        $timeshift *= 60; //in seconds
        $time = strtotime((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT))
            + $timeshift;
        $time = date('Y-m-d H:i:s', $time);

        return $time;
    }

    /**
     * @return object
     */
    public function isDev()
    {
        return $this->config->getDeveloperIsActive();
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Email $email
     * @param string                         $code
     *
     * @return bool|\Mirasvit\Helpdesk\Model\Ticket
     *
     * @throws \Exception
     *
     * @fixme
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processEmail($email, $code)
    {
        $ticket = false;
        $customer = false;
        $user = false;
        $triggeredBy = Config::CUSTOMER;
        $messageType = Config::MESSAGE_PUBLIC;

        if ($code) {
            //try to find customer for this email
            $tickets = $this->ticketCollectionFactory->create();
            $tickets->addFieldToFilter('code', $code)
                ->addFieldToFilter('customer_email', $email->getFromEmail())
            ;
            if ($tickets->count()) {
                $ticket = $tickets->getFirstItem();
            } else {
                //try to find staff user for this email
                $users = $this->userCollectionFactory->create()
                    ->addFieldToFilter('email', $email->getFromEmail())
                ;

                if ($users->count()) {
                    $user = $users->getFirstItem();
                    $tickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('code', $code)
                    ;
                    if ($tickets->count()) {
                        $ticket = $tickets->getFirstItem();
                        $ticket->setUserId($user->getId());
                        $ticket->save();
                        $triggeredBy = Config::USER;
                    } else {
                        $user = false; //@temp dva for testing
                    }
                } else { //third party
                    $tickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('code', $code)
                    ;
                    if ($tickets->count()) {
                        $ticket = $tickets->getFirstItem();
                        $triggeredBy = Config::THIRD;
                        $messageType = Config::MESSAGE_PUBLIC_THIRD;
                    }
                }
                if ($ticket && $ticket->isThirdParty()) {
                    if ($ticket->isThirdPartyPublic()) {
                        $messageType = Config::MESSAGE_PUBLIC_THIRD;
                    } else {
                        $messageType = Config::MESSAGE_INTERNAL_THIRD;
                    }
                }
            }
        }

        if (!$user) {
            $customer = $this->helpdeskCustomer->getCustomerByEmail($email);
        }
        // create a new ticket
        if (!$ticket) {
            $ticket = $this->ticketFactory->create();
            if (!$code) {
                $ticket->setCode($this->helpdeskString->generateTicketCode());
            } else {
                $ticket->setCode($code);//temporary for testing to fix @dva
            }
            $gateway = $this->gatewayFactory->create()->load($email->getGatewayId());
            if ($gateway->getId()) {
                if ($gateway->getDepartmentId()) {
                    $ticket->setDepartmentId($gateway->getDepartmentId());
                } else { //if department was removed
                    $departments = $this->departmentCollectionFactory->create()
                        ->addFieldToFilter('is_active', true);
                    if ($departments->count()) {
                        $department = $departments->getFirstItem();
                        $ticket->setDepartmentId($department->getId());
                    } else {
                        $this->context->getLogger()->error(
                            'Helpdesk MX - Can\'t find any active department. Helpdesk can\'t fetch tickets correctly!'
                        );
                    }
                }
                $ticket->setStoreId($gateway->getStoreId());
            }
            $ticket
                ->setSubject($email->getSubject())
                ->setCustomerName($customer->getName())
                ->setCustomerId($customer->getId())
                ->setQuoteAddressId($customer->getQuoteAddressId())
                ->setCustomerEmail($email->getFromEmail())
                ->setChannel(Config::CHANNEL_EMAIL)
                ->setCc($email->getCc())
            ;

            $ticket->setEmailId($email->getId());
            $ticket->save();
            $pattern = $this->checkForSpamPattern($email);
            if ($pattern) {
                $ticket->markAsSpam();
                if ($email) {
                    $email->setPatternId($pattern->getId())->save();
                }
            }
        }

        if ($customer && $this->getOrderLength()) {
            $orderLength = $this->getOrderLength();
            //parse order ID from email subject
            preg_match_all('[[0-9]{' . $orderLength . '}]', $email->getSubject(), $numbers);
            foreach ($numbers[0] as $number) {
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('increment_id', $number)
                    ->addFieldToFilter('customer_id', $customer->getId());

                if (count($orders)) {
                    // Case 1: this is registered customer and has an order
                    $order = $this->orderFactory->create()->loadByAttribute('increment_id', $number);
                    $ticket->setCustomerId($order->getCustomerId());
                    $ticket->setOrderId($order->getId());
                    $ticket->save();
                    break;
                } else {
                    $order = $this->orderFactory->create()->loadByAttribute('increment_id', $number);
                    $ticket->setOrderId($order->getId());

                    // Case 2: this is known guest customer or known another email of registered customer
                    $prevTickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('customer_email', $email->getFromEmail())
                        ->addFieldToFilter('order_id', $order->getId());
                    if (count($prevTickets)) {
                        $ticket->setCustomerId($order->getCustomerId());
                        $ticket->save();
                        break;
                    }

                    // Case 3: this is generic guest customer with existing order
                    $quotes = $this->quoteAddressCollectionFactory->create();
                    $quotes
                        ->addFieldToFilter('email', $email->getFromEmail())
                        ->addFieldToFilter('customer_id', $customer->getId());
                    $quotes->getSelect()->group('email');
                    if ($quotes->count()) {
                        $ticket->setQuoteAddressId($quotes->getFirstItem()->getId());
                        $ticket->save();
                        break;
                    }
                }
            }
        }

        //add message to ticket
        $text = $email->getBody();
        $encodingHelper = $this->helpdeskEncoding;
        $text = $encodingHelper->toUTF8($text);
        $body = $this->helpdeskString->parseBody($text, $email->getFormat());
        $ticket->addMessage($body, $customer, $user, $triggeredBy, $messageType, $email);

        $this->context->getEventManager()->dispatch(
            'helpdesk_process_email',
            [
                'body'        => $body,
                'customer'    => $customer,
                'user'        => $user,
                'ticket'      => $ticket,
                'triggeredBy' => $triggeredBy,
                'email'       => $email,
            ]
        );

        return $ticket;
    }

    /**
     * @return int|null
     */
    private function getOrderLength()
    {
        if ($this->orderLength === null) {
            $lastOrderCollection = $this->orderCollectionFactory->create();
            $lastOrderCollection->getSelect()
                ->limit(1)
                ->order('entity_id DESC');
            $this->orderLength = 0;
            if ($lastOrderCollection->count()) {
                /** @var \Magento\Sales\Model\Order $lastOrder */
                $lastOrder         = $lastOrderCollection->getLastItem();
                $this->orderLength = strlen($lastOrder->getIncrementId());
            }
        }

        return $this->orderLength;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Email $email
     *
     * @return bool|\Mirasvit\Helpdesk\Model\Pattern
     */
    public function checkForSpamPattern($email)
    {
        $patterns = $this->patternCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($patterns as $pattern) {
            if ($pattern->checkEmail($email)) {
                return $pattern;
            }
        }

        return false;
    }

    /**
     * @param string $post
     *
     * @return bool|\Mirasvit\Helpdesk\Model\Pattern
     */
    public function checkPostForSpamPattern($post)
    {
        $patterns = $this->patternCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($patterns as $pattern) {
            if ($pattern->checkPost($post)) {
                return $pattern;
            }
        }

        return false;
    }

    /**
     * Merge selected tickets.
     *
     * @param array $ids Array of ticket identifiers.
     * @return void
     */
    public function mergeTickets($ids)
    {
        // Sort ids in ascending order
        sort($ids);

        $baseTicket = $this->ticketFactory->create()->load($ids[0]);

        // Get all messages, registered in selected tickets and merge it to oldest
        $mergeMessages = $this->messageCollectionFactory->create()
            ->addFieldToFilter('ticket_id', $ids);
        foreach ($mergeMessages as $msg) {
            $msg->setTicketId($baseTicket->getId());
            $msg->save();
        }

        // Add to merged tickets new message instead of moved ones
        $mergeMessage = __('Ticket was merged to %1', $baseTicket->getCode().' - ');
        /** @var \Magento\User\Model\User $user */
        $user = $this->auth->getUser();
        $mergeCodes = [];
        foreach ($ids as $id) {
            if ($id == $baseTicket->getId()) {
                continue;
            }

            $ticket = $this->ticketFactory->create()->load($id);
            $ticket
                ->setMergedTicketId($baseTicket->getId())
                ->setFolder(Config::FOLDER_ARCHIVE)
                ->addMessage(
                    $mergeMessage.'[[ticket_url__'.$baseTicket->getId().']]',
                    null,
                    $user,
                    Config::USER,
                    Config::MESSAGE_INTERNAL
                )
                ->save();
            $ticket->close();
            $mergeCodes[] = $ticket->getCode();
        }
        $baseTicket->setMergedTicketCodes($mergeCodes)
            ->save();
    }
}