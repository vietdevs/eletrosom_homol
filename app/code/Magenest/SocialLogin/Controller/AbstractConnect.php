<?php
namespace Magenest\SocialLogin\Controller;

abstract class AbstractConnect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magenest\SocialLogin\Helper\SocialLogin
     */
    protected $_helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var string
     */
    protected $clientModel;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_path;

    /**
     * @var string
     */
    protected $_exeptionMessage;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magenest\SocialLogin\Helper\SocialLogin $helperGoogle
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magenest\SocialLogin\Helper\SocialLogin $helperGoogle
    ) {
        $this->_customerSession = $customerSession;
        $this->_helper = $helperGoogle;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $this->connect();
        } catch (\Exception $e) {
            $this->_exeptionMessage =  $e->getMessage();
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sociallogin/checklogin/index');
        return $resultRedirect;
    }

    protected function connect()
    {
        $error = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');

        if (!(isset($error) || isset($code)) && !isset($state)) {
            return;
        }

        $client = $this->_objectManager->create($this->clientModel);
        if ($code) {
            $userInfo = $this->getUserInfo($client, $code);

            /** Find a customer with Google Id */
            $customer = $this->_helper->getCustomers($userInfo['id'], $this->_type);
            if ($customer->getId()) {
                $this->_customerSession->setCustomerAsLoggedIn($customer);
                return;
            }

            /** Find a customer with Google Email */
            if (isset($userInfo['email'])) {
                $customer = $this->_helper->getCustomerByEmail($userInfo['email']);
                if ($customer->getId()) {
                    $data = [
                        'magenest_sociallogin_id' => $userInfo['id'],
                        'magenest_sociallogin_type' => $this->_type
                    ];
                    $this->_helper->login($customer, $data);
                    return;
                }
            }
            
            /**
             * If don't exist customer, create new customer with this information
             *
             */
            $userInfo['email'] = isset($userInfo['email']) ? $userInfo['email'] : str_replace(' ', '', $userInfo['name'])."@facebook.com";
            $data = $this->getDataNeedSave($userInfo);
            $this->_helper->creatingAccount($data);
            return;
        }
    }

    /**
     * Save Information
     *
     * @param $userInfo
     * @return array
     */
    public function getDataNeedSave($userInfo)
    {
        $data = [
            'sendemail' => 0,
            'confirmation' => 0,
            'magenest_sociallogin_id' => $userInfo['id'],
            'magenest_sociallogin_type' => $this->_type
        ];

        return $data;
    }

    public function getUserInfo($client, $code)
    {
    }
}
