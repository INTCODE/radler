<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Controller\Cart;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Remove
 *
 * @package Aheadworks\StoreCredit\Controller\Cart
 */
class Remove extends Action
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * Only logged in users can use this functionality
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * Remove Store Credit from current quote
     *
     * @return void
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getAwUseStoreCredit()) {
            $this->messageManager->addSuccess(__('Store Credit were successfully removed.'));
            $quote->setAwUseStoreCredit(false)->collectTotals()->save();
        } else {
            $this->messageManager->addError(__('You are not using Store Credit in your shopping cart.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('checkout/cart');
    }
}
