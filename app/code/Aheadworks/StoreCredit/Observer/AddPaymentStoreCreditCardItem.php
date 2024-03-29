<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class Aheadworks\StoreCredit\Observer\AddPaymentStoreCreditCardItem
 */
class AddPaymentStoreCreditCardItem implements ObserverInterface
{
    /**
     * Merge gift card amount into discount of payment checkout totals
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $salesEntity = $cart->getSalesModel();
        $value = abs($salesEntity->getDataUsingMethod('base_aw_store_credit_amount'));
        if ($value > 0.0001) {
            $cart->addDiscount((double)$value);
        }
    }
}
