<?php
namespace Network\Hideblocks\Observer;

class Checklogin implements \Magento\Framework\Event\ObserverInterface
{

    protected $_redirectInterface;
    protected $_customerSession;
    protected $_state;
    protected $_messageManager;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_customerSession = $customerSession;
        $this->_redirectInterface = $redirectInterface;
        $this->_state = $state;
        $this->_messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer){
        $actionName = $observer->getEvent()->getRequest()->getFullActionName();
        $controller = $observer->getControllerAction();

        if($this->_state->getAreaCode()=="adminhtml")
            return $this;

        if($actionName == 'checkout_cart_index') {
            $this->_redirectInterface->redirect($controller->getResponse(), 'multishipping/checkout/addresses');
            return;
        }
        if($actionName == 'checkout_index_index') {
            $this->_redirectInterface->redirect($controller->getResponse(), 'multishipping/checkout/addresses');
            return;
        }


        if($this->_customerSession->isLoggedIn())
            return $this;


        /**
         *  Kod zakomentowany blokuje kontrolery za wyjątkiem tych w tablicy
         */
//        $openActions = array(
//            'customer_account_create',
//            'customer_account_createpost',
//            'customer_account_createpassword',
//            'customer_account_login',
//            'customer_account_loginpost',
//            'customer_account_logout',
//            'customer_account_logoutsuccess',
//            'customer_account_forgotpassword',
//            'customer_account_forgotpasswordpost',
//            'customer_account_resetpassword',
//            'customer_account_resetpasswordpost',
//            'customer_account_confirm',
//            'customer_account_confirmation',
//            'gus_index_index'
//        );
//
//        if (in_array($actionName, $openActions)) {
//            return $this;
//        }
//
//
        if($this->_isAccessAllow($actionName))
            return $this;

        $this->_messageManager->addNoticeMessage('Dostęp do tej strony został zablokowany. Zaloguj się za pomocą poniższego formularza.');
        $this->_redirectInterface->redirect($controller->getResponse(), 'customer/account/login');

        return $this;
    }

    protected function _isAccessAllow($actionName)
    {
        $blockedForNotLogged = [
            'checkout_index_index',
            'checkout_cart_index'
        ];

        if(in_array($actionName, $blockedForNotLogged))
            return false;

        return true;
    }
}