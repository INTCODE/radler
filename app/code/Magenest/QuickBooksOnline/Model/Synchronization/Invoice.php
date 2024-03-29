<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\Synchronization;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;
use Magento\Framework\Exception\LocalizedException;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magenest\QuickBooksOnline\Model\Config;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Action\Context;

/**
 * Class Invoice using to sync Invoice
 * @package Magenest\QuickBooksOnline\Model\Sync
 * @method InvoiceModel getModel()
 */
class Invoice extends Synchronization
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var Customer
     */
    protected $_syncCustomer;

    /**
     * @var Item
     */
    protected $_item;

    /**
     * @var InvoiceModel
     */
    protected $_invoice;

    /**
     * @var InvoiceModel
     */
    protected $_currentModel;

    /**
     * @var TaxFactory
     */
    protected $tax;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var PaymentMethods
     */
    protected $_paymentMethods;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var InvoiceModel\ItemFactory
     */
    protected $itemInvoice;

    /**
     * Invoice constructor.
     *
     * @param Client $client
     * @param Log $log
     * @param InvoiceModel $invoice
     * @param Item $item
     * @param Customer $customer
     * @param TaxFactory $taxFactory
     * @param Config $config
     * @param \Magenest\QuickBooksOnline\Model\PaymentMethodsFactory $paymentMethods
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param InvoiceModel\ItemFactory $invoiceItemFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param OrderFactory $orderFactory
     * @param Context $context
     */
    public function __construct(
        Client $client,
        Log $log,
        InvoiceModel $invoice,
        Item $item,
        Customer $customer,
        TaxFactory $taxFactory,
        Config $config,
        \Magenest\QuickBooksOnline\Model\PaymentMethodsFactory $paymentMethods,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Psr\Log\LoggerInterface $logger,
        OrderFactory $orderFactory,
        Context $context
    ) {
        parent::__construct($client, $log, $context);
        $this->_invoice        = $invoice;
        $this->_item           = $item;
        $this->_syncCustomer   = $customer;
        $this->tax             = $taxFactory;
        $this->type            = 'invoice';
        $this->config          = $config;
        $this->_paymentMethods = $paymentMethods;
        $this->_orderFactory   = $orderFactory;
        $this->logger          = $logger;
        $this->product         = $product;
        $this->itemInvoice     = $invoiceItemFactory;
    }

    /**
     * @param $incrementId
     *
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     */
    public function sync($incrementId)
    {
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
        $registryObject->unregister('skip_log');
        try {
//            $model            = $this->_invoice->loadByIncrementId($incrementId);
            $model            = ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Invoice')->loadByIncrementId($incrementId);
            $modelOrder       = $this->_orderFactory->create()->load($model->getOrderId());
            $orderIncrementId = $modelOrder->getIncrementId();
            $checkInvoice     = $this->checkInvoice($orderIncrementId);
            if (!isset($checkInvoice['Id'])) {
                $this->addLog($incrementId, null, __('We can\'t find the Order #%1 on QBO to map with this invoice #%2', $orderIncrementId, $incrementId));
            } else {
                if (!$model->getId()) {
                    throw new LocalizedException(__('We can\'t find the Invoice #%1', $incrementId));
                }

                /**
                 * check the case delete customer before sync their invoice
                 */
                $customerIsGuest = true;
                if ($modelOrder->getCustomerId()) {
                    $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\ResourceModel\Customer\Collection')->addFieldToFilter('entity_id', $modelOrder->getCustomerId());
                    if (!$customerCollection->getData()) {
                        $customerIsGuest = true;
                    } else $customerIsGuest = false;
                }

                $this->setModel($model);
                $this->prepareParams($checkInvoice['Id'], $customerIsGuest);
                $params   = $this->getParameter();
                $response = $this->sendRequest(\Zend_Http_Client::POST, 'payment', $params);
                $qboId    = @$response['Payment']['Id'];
                if (!empty($qboId)) {
                    $this->addLog($incrementId, $qboId);
                }
                $this->parameter = [];

                return $qboId;

            }

            $this->parameter = [];
        } catch (LocalizedException $e) {
            $this->addLog($incrementId, null, $e->getMessage());
        }
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareParams($id, $customerIsGuest = null)
    {
        $model  = $this->getModel();
        $params = [
            'TxnDate'     => $model->getCreatedAt(),
            'CustomerRef' => $this->prepareCustomerId($customerIsGuest),
            'Line'        => $this->prepareLineInvoice($id),
            'TotalAmt'    => $model->getGrandTotal() - $model->getShippingAmount(),
        ];

        /** @var \Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting $preferenceSetting */
        $preferenceSetting = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting');
        if ($preferenceSetting->getShippingAllow() == true) {
            $params['TotalAmt'] = $model->getGrandTotal();
        }
        $this->setParameter($params);
        $this->preparePaymentMethod();

        return $this;
    }

    /**
     *  get payment method
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function preparePaymentMethod()
    {
        $modelOrder    = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
        $code          = $modelOrder->getPayment()->getMethodInstance()->getCode();
        $paymentMethod = $this->_paymentMethods->create()->load($code, 'payment_code');
        if ($paymentMethod->getId()) {
            $params['PaymentMethodRef'] = [
                'value' => $paymentMethod->getQboId(),
            ];
            $this->setParameter($params);
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function prepareCustomerId($customerIsGuest = null)
    {
        try {
            $modelOrder = $this->_orderFactory->create()->load($this->getModel()->getOrderId());
            $customerId = $modelOrder->getCustomerId();
            if ($customerId and $customerIsGuest == false) {
                $cusRef = $this->_syncCustomer->sync($customerId, false);
            } else {
                $cusRef = $this->_syncCustomer->syncGuest(
                    $modelOrder->getBillingAddress(),
                    $modelOrder->getShippingAddress()
                );
            }

            return ['value' => $cusRef];
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Can\'t sync customer on Invoice to QBO')
            );
        }
    }

    /**
     * Add Item to Order
     * @return array
     */
    public function prepareLineInvoice($id)
    {
        $invoice[] = [
            'TxnId'   => $id,
            'TxnType' => 'Invoice'
        ];

        $preferenceSetting = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting');
        if ($preferenceSetting->getShippingAllow() == true) {
            $amount = $this->getModel()->getGrandTotal();
        }
        else {
            $amount = $this->getModel()->getGrandTotal() - $this->getModel()->getShippingAmount();
        }

        $lines[]   = [
            'Amount'    => $amount,
            'LinkedTxn' => $invoice,
        ];

        return $lines;
    }


    /**
     * Check invoice by Increment Id
     *
     * @param $id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkInvoice($id)
    {
        $prefix = $this->config->getPrefix('order');
        $name   = $prefix . $id;
        $query  = "SELECT Id, SyncToken FROM invoice WHERE DocNumber='{$name}'";

        return $this->query($query);
    }

    /**
     * @param $name
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInvoice($name)
    {
        $query = "SELECT * FROM invoice WHERE DocNumber='{$name}'";

        return $this->query($query);
    }
}
