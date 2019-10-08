<?php

namespace Perla\Dedicated\Controller\Customer;

class Switchaddress extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        if($this->customerSession->isLoggedIn() && $this->getRequest()->getParam('id')) {
//            $customer = $this->customerSession->getCustomer();

            $this->setNewSessionAddress($this->getRequest()->getParam('id'));
        }
    }

    private function setNewSessionAddress($id)
    {
        $this->customerSession->setData('session_address', $id);
    }
}