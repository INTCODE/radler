<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Model\Synchronization;

use Magenest\QuickBooksOnline\Model\Client;
use Magenest\QuickBooksOnline\Model\Config;
use Magenest\QuickBooksOnline\Model\Log;
use Magenest\QuickBooksOnline\Model\PaymentMethodsFactory;
use Magenest\QuickBooksOnline\Model\Synchronization;
use Magenest\QuickBooksOnline\Model\TaxFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\State;

/**
 * Class Order
 * @package Magenest\QuickBooksOnline\Model\Synchronization
 */
class Order extends Synchronization
{
    /**
     * @var Customer
     */
    protected $_syncCustomer;

    /**
     * @var Item
     */
    protected $_item;

    /**
     * @var PaymentMethods
     */
    protected $_paymentMethods;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var TaxFactory
     */
    protected $tax;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var State
     */
    protected $state;

    /**
     * Order constructor.
     *
     * @param Client $client
     * @param Log $log
     * @param OrderFactory $orderFactory
     * @param PaymentMethodsFactory $paymentMethods
     * @param Item $item
     * @param Customer $customer
     * @param TaxFactory $taxFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Context $context
     * @param State $state
     */
    public function __construct(
        Client $client,
        Log $log,
        OrderFactory $orderFactory,
        PaymentMethodsFactory $paymentMethods,
        Item $item,
        Customer $customer,
        TaxFactory $taxFactory,
        Config $config,
        LoggerInterface $logger,
        Context $context,
        State $state
    ) {
        parent::__construct($client, $log, $context);
        $this->_orderFactory   = $orderFactory;
        $this->_item           = $item;
        $this->_syncCustomer   = $customer;
        $this->_paymentMethods = $paymentMethods;
        $this->type            = 'order';
        $this->tax             = $taxFactory;
        $this->config          = $config;
        $this->logger          = $logger;
        $this->state           = $state;
    }

    /**
     * Sync Sales Order to Sales Receipt
     *
     * @param $incrementId
     * @param $newOrder
     *
     * @return mixed
     * @throws \Exception
     */
    public function sync($incrementId, $newOrder = false)
    {
        try {
            $model = $this->_orderFactory->create()->loadByIncrementId($incrementId);
            /** @var \Magento\Sales\Model\Order\Item $item */
            $checkOrder = $this->checkOrder($incrementId);
            if (isset($checkOrder['Id'])) {
                $this->addLog($incrementId, $checkOrder['Id'], 'This Order already exists.', 'skip');
            } else {
                if (!$model->getId()) {
                    throw new LocalizedException(__('We can\'t find the Order #%1', $incrementId));
                }

                /**
                 * check the case delete customer before sync their order
                 */
                $customerCollection = ObjectManager::getInstance()->create('Magento\Customer\Model\ResourceModel\Customer\Collection')->addFieldToFilter('entity_id', $model->getCustomerId());
                if (!$customerCollection->getData()) {
                    $model->setCustomerId(null);
                }

                $this->setModel($model);
                $this->prepareParams();
                $params   = $this->getParameter();
                $response = $this->sendRequest(\Zend_Http_Client::POST, 'invoice', $params);
                $qboId    = @$response['Invoice']['Id'];
                if (!empty($qboId)) {
                    $this->addLog($incrementId, $qboId);
                }
                $this->parameter = [];

                /** @var \Magento\Framework\Registry $registryObject */
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');

                $registryObject->register('skip_log', true);
                foreach ($model->getAllItems() as $orderItem) {
                    $registryObject->unregister('check_to_syn' . $orderItem->getProductId());
                    if ($orderItem->getProductType() != 'bundle' && $orderItem->getProductType() != 'grouped') {
                        if ($this->state->getAreaCode() == 'adminhtml' && ($newOrder == true)) {
                            $orderedQty = $orderItem->getBuyRequest()->getData('qty');
                        } else {
                            $orderedQty = null;
                        }
                        $this->_item->syncBySku($orderItem->getSku(), true, $orderedQty);
                    }
                }
                $registryObject->unregister('skip_log');

                return $qboId;

            }

            $this->parameter = [];
        } catch (LocalizedException $e) {
            $this->parameter = [];
            $this->addLog($incrementId, null, $e->getMessage());
        }
        if (@$qboId)
            return $qboId;
        else
            return null;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareParams()
    {
        /** @var \Magento\Sales\Model\Order $model */
        $model  = $this->getModel();
        $prefix = $this->config->getPrefix('order');
        $params = [
            'DocNumber'   => $prefix . $model->getIncrementId(),
            'TxnDate'     => $model->getCreatedAt(),
            'CustomerRef' => $this->prepareCustomerId(),
            'Line'        => $this->prepareLineItems(),
            'TotalAmt'    => $model->getGrandTotal(),
            'BillEmail'   => ['Address' => mb_substr((string)$model->getCustomerEmail(), 0, 100)],
        ];

        $this->setParameter($params);
        // st Tax
        if ($this->config->getCountry() == 'OTHER' && $model->getTaxAmount() > 0) {
            $this->prepareTax();
        }

        //set billing address
        $this->prepareBillingAddress();

        if ($this->getShippingAllow() == true) {
            $this->prepareShippingAddress();
        }

        return $this;
    }

    /**
     * Create Tax
     */
    public function prepareTax()
    {
        /** @var \Magento\Sales\Model\Order $model */
        $model      = $this->getModel();
        $taxRateRef = null;

        try {
            $taxRateRef = $this->getTaxRateRef();
        } catch (\Exception $e) {
        }

        $params['TxnTaxDetail'] = [
            'TotalTax' => $model->getTaxAmount()
        ];

        if (isset($taxRateRef)) {
            $params['TxnTaxDetail']['TxnTaxCodeRef'] = [
                'value' => $taxRateRef
            ];
//            $params['TxnTaxDetail']['TaxLine'] = [
//                [
//                    'Amount' => $model->getTaxAmount(),
//                    'DetailType' => 'TaxLineDetail',
//                    'TaxLineDetail' => [
//                        'TaxRateRef' => ['value' => $taxRateRef]
//                    ]
//                ]
//            ];
        }

        return $this->setParameter($params);
    }

    /**
     * @return null|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTaxCode()
    {
        $order  = $this->getModel();
        $objMng = ObjectManager::getInstance();
        /** @var \Magento\Tax\Api\OrderTaxManagementInterface $orderTaxManagement */
        $orderTaxManagement = $objMng->get(\Magento\Tax\Api\OrderTaxManagementInterface::class);
        $orderTaxDetails    = $orderTaxManagement->getOrderTaxDetails($order->getId())->getAppliedTaxes();
        if (isset($orderTaxDetails[0])) {
            return $orderTaxDetails[0]->getCode();
        }

        return null;
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTaxRateRef()
    {
        $taxCode = $this->getTaxCode();
        if (empty($taxCode)) {
            return null;
        }
        /** @var \Magenest\QuickBooksOnline\Model\Tax $qboTaxModel */
        $qboTaxModel = ObjectManager::getInstance()->get(\Magenest\QuickBooksOnline\Model\Tax::class);
        $qboTaxModel->loadByCode($taxCode);

        return $qboTaxModel->getQboId();
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function prepareCustomerId()
    {
        try {
            $model      = $this->getModel();
            $customerId = $model->getCustomerId();
            if ($customerId) {
                $cusRef = $this->_syncCustomer->sync($customerId, false);
            } else {
                $cusRef = $this->_syncCustomer->syncGuest(
                    $model->getBillingAddress(),
                    $model->getShippingAddress()
                );
            }

            return ['value' => $cusRef];
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Can\'t sync customer on Order to QBO')
            );
        }
    }

    /**
     * get Billing
     */
    public function prepareBillingAddress()
    {
        /** @var \Magento\Sales\Model\Order\Address $billAddress */
        $billAddress = $this->getModel()->getBillingAddress();
        if ($billAddress !== null) {
            $params['BillAddr'] = $this->getAddress($billAddress);
            $this->setParameter($params);
        }
    }

    /**
     * get shipping
     */
    public function prepareShippingAddress()
    {
        $shippingAddress = $this->getModel()->getShippingAddress();
        if ($shippingAddress !== null) {
            $params['ShipAddr'] = $this->getAddress($shippingAddress);
            $this->setParameter($params);
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function prepareLineItems()
    {
        try {
            $i     = 1;
            $lines = [];
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($this->getModel()->getItems() as $item) {
                $productType    = $item->getProductType();
                $total          = 0;
                $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
                if ($productType == 'configurable') {
                    $total         = $item->getRowTotal();
                    $tax           = $item->getTaxAmount() > 0 ? true : false;
                    $childrenItems = $item->getChildrenItems();
                    if (isset($childrenItems[0])) {
                        $productId = $childrenItems[0]->getProductId();
                        $sku       = $childrenItems[0]->getSku();
                        $qty       = $childrenItems[0]->getQtyOrdered();
                    } else {
                        $productId = $item->getProductId();
                        $sku       = $item->getSku();
                        $qty       = $item->getQtyOrdered();
                    }
                    $price = $qty > 0 ? $total / $qty : $item->getPrice();
                    $registryObject->unregister('check_to_syn' . $productId);
                    $itemId = $this->_item->syncBySku($sku, false);
                    if (!$itemId) throw new \Exception(
                        __('Can\'t sync Product with SKU:%1 on Order to QBO', $sku)
                    );
                } else if ($item->getParentItemId() && ($productType == 'virtual' || $productType == 'simple')) {
                    continue;
                } else if ($productType != 'bundle' && $productType != 'grouped') {
                    $productId = $item->getProductId();
                    $sku       = $item->getSku();
                    $qty       = $item->getQtyOrdered();
                    $total     = $item->getRowTotal();
                    $price     = $qty > 0 ? $total / $qty : $item->getPrice();
                    $tax       = $item->getTaxAmount() > 0 ? true : false;

                    $registryObject->unregister('check_to_syn' . $productId);
                    $itemId = $this->_item->syncBySku($sku);
                    $registryObject->unregister('check_to_syn' . $productId);
                    if (!$itemId) throw new \Exception(
                        __('Can\'t sync Product with SKU:%1 on Order to QBO', $sku)
                    );
                }
                if (!empty($itemId)) {
                    if ($this->config->getCountry() == 'OTHER') {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $tax ? 'TAX' : 'NON'],
                            ],
                        ];
                    } else {
                        $lines[] = [
                            'LineNum'             => $i,
                            'Amount'              => $total,
                            'DetailType'          => 'SalesItemLineDetail',
                            'SalesItemLineDetail' => [
                                'ItemRef'    => ['value' => $itemId],
                                'UnitPrice'  => $price,
                                'Qty'        => $qty,
                                'TaxCodeRef' => ['value' => $tax ? $this->prepareTaxCodeRef($item->getItemId()) : $this->getTaxFreeId()]
                            ],
                        ];
                    }

                    $i++;
                } else continue;
            }

            // set shipping fee
            if ($this->getShippingAllow() == true) {
                if ($this->prepareLineShippingFee())
                    $lines[] = $this->prepareLineShippingFee();
            }

            // set discount
            $lines[] = $this->prepareLineDiscountAmount();

            return $lines;
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __('Error when syncing products: %1', $exception->getMessage())
            );
        }
    }

    /**
     * @return mixed
     */
    public function getTaxFreeId()
    {
        $localId   = $this->config->getTaxFree();
        $taxCodeId = $this->tax->create()->load($localId, 'tax_id')->getQboId();

        return $taxCodeId;
    }

    /**
     * @param $itemId
     *
     * @return bool|int
     */
    public function prepareTaxCodeRef($itemId)
    {
        $taxCode = 0;
        /** @var \Magento\Sales\Model\Order\Tax\Item $modelTaxItem */
        $modelTaxItem = ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Tax\Item')->load($itemId, 'item_id');
        if ($modelTaxItem) {
            $taxId    = $modelTaxItem->getTaxId();
            $modelTax = ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Tax')->load($taxId);

            if ($modelTax && !empty($modelTax->getData())) {
                $taxCode = $modelTax->getCode();
            }
            $tax = $this->tax->create()->load($taxCode, 'tax_code');
            if ($tax->getQboId() && $tax->getQboId() > 0) {
                $taxCodeId = $tax->getQboId();

                return $taxCodeId;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    protected function prepareLineShippingFee()
    {
        /** @var \Magento\Sales\Model\Order $model */
        $model          = $this->getModel();
        $shippingAmount = $model->getShippingAmount();
        if ($this->config->getCountry() != 'OTHER') {
            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef'    => ['value' => 'SHIPPING_ITEM_ID'],
                    'TaxCodeRef' => ['value' => $this->config->getTaxShipping()],
                ],
            ];
        } else {
            $lines = [
                'Amount'              => $shippingAmount ? $shippingAmount : 0,
                'DetailType'          => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => 'SHIPPING_ITEM_ID'],
                ],
            ];
        }

        return $lines;
    }

    /**
     * @return array
     */
    protected function prepareLineDiscountAmount()
    {
        $discountAmount = $this->getModel()->getDiscountAmount();
        $lines          = [
            'Amount'             => $discountAmount ? -1 * $discountAmount : 0,
            'DetailType'         => 'DiscountLineDetail',
            'DiscountLineDetail' => [
                'PercentBased' => false,
            ]
        ];

        return $lines;
    }

    /**
     * @param $id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkOrder($id)
    {
        $prefix = $this->config->getPrefix('order');
        $name   = $prefix . $id;
        $query  = "SELECT Id, SyncToken FROM invoice WHERE DocNumber='{$name}'";

        return $this->query($query);
    }

    /**
     * @param $params
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function getOrder($params)
    {
        $query = "SELECT * FROM salesreceipt";
        if (isset($params['type']) && $params['type'] == 'id') {
            $input = $params['input'];
            $query = "select * from salesreceipt where  Id = '$input'";
        }
        $path      = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result    = $responses['QueryResponse'];

        return $result;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function getCountOrder()
    {
        $query     = "select COUNT(*) from salesreceipt ";
        $path      = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result    = $responses['QueryResponse'];

        return $result['totalCount'];
    }

    /**
     * list all order when creat new
     *
     * @param $start
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function listOrder($start)
    {
        $query     = "select * from salesreceipt startposition {$start} maxresults 10";
        $path      = 'query?query=' . rawurlencode($query);
        $responses = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $result    = $responses['QueryResponse'];

        return $result;
    }

    /**
     * @param $incrementId
     *
     * @return array|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function getSaleReceiptDelete($incrementId)
    {
        $prefix    = $this->config->getPrefix('order');
        $docNumber = $prefix . $incrementId;
        $query     = "select * from estimate where DocNumber= '$docNumber' ";
        $path      = 'query?query=' . rawurlencode($query);

        $response = $this->sendRequest(\Zend_Http_Client::GET, $path);
        $data     = '';
        if (isset($response['QueryResponse']['Estimate'])) {
            $data = [
                'id'    => $response['QueryResponse']['Estimate'][0]['Id'],
                'email' => $response['QueryResponse']['Estimate'][0]['BillEmail']['Address']
            ];
        }

        return $data;
    }

    /**
     * @param $id
     * @param $incrementId
     *
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function cancelSaleReceipt($id, $incrementId)
    {
        $prefix   = $this->config->getPrefix('order');
        $params   = [
            'Id'        => $id,
            'SyncToken' => 1,
            'DocNumber' => $prefix . $incrementId
        ];
        $response = $this->sendRequest(\Zend_Http_Client::POST, 'estimate?operation=delete', $params);

        return $response;
    }

    /**
     * @return mixed
     */
    public function getShippingAllow()
    {
        $registryObject = ObjectManager::getInstance()->get('Magento\Framework\Registry');
        try {
            $shippingAllow = $registryObject->registry('shipping_allow');
            if (isset($shippingAllow)) return $shippingAllow;
        } catch (\Exception $exception) {
            $shippingAllow = null;
        }
        /** @var \Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting $preferenceSetting */
        $preferenceSetting = ObjectManager::getInstance()->create('Magenest\QuickBooksOnline\Model\Synchronization\PreferenceSetting');
        $shippingAllow     = $preferenceSetting->getShippingAllow();
        $registryObject->register('shipping_allow', $shippingAllow);

        return $shippingAllow;
    }
}
