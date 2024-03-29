<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Plugin\Controller\Newsletter\Manage;

use Aheadworks\StoreCredit\Model\Service\SummaryService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\DataObject;
use Aheadworks\StoreCredit\Api\Data\SummaryInterface;
use Aheadworks\StoreCredit\Model\Source\SubscribeStatus;
use Aheadworks\StoreCredit\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Aheadworks\StoreCredit\Api\Data\TransactionInterface;

/**
 * Class SavePlugin
 *
 * @package Aheadworks\StoreCredit\Plugin\Controller\Newsletter\Manage
 */
class SavePlugin
{
    /**
     * @var SummaryService
     */
    private $summaryService;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SummaryService $summaryService
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerSession $customerSession
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param DataObject $dataObject
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SummaryService $summaryService,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerSession $customerSession,
        RequestInterface $request,
        ManagerInterface $messageManager,
        DataObject $dataObject,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->summaryService = $summaryService;
        $this->storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->dataObject = $dataObject;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Save newsletter subscriptions
     *
     * @param \Magento\Newsletter\Controller\Manage\Save\Interceptor $subject
     * @param null $result
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute($subject, $result)
    {
        if (!$this->formKeyValidator->validate($this->request) || !$this->hasCustomerTransactions()) {
            return $result;
        }

        $customerId = $this->customerSession->getCustomerId();
        if ($customerId === null) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving your Store Credit subscription.')
            );
        } else {
            try {
                $balanceUpdate = (bool)$this->request->getParam('aw_storecedit_is_balance_update_subscribed', false)
                    ? SubscribeStatus::SUBSCRIBED
                    : SubscribeStatus::UNSUBSCRIBED;
                $summaryData = $this->dataObject->setData(
                    [
                        SummaryInterface::CUSTOMER_ID => $customerId,
                        SummaryInterface::WEBSITE_ID => $this->storeManager->getStore()->getWebsiteId(),
                        SummaryInterface::BALANCE_UPDATE_NOTIFICATION_STATUS => $balanceUpdate
                    ]
                );
                $this->summaryService->updateCustomerSummary($summaryData);
                $this->messageManager->addSuccessMessage(__('Your Store Credit subscription settings were updated.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving your Store Credit subscription.')
                );
            }
        }

        return $result;
    }

    /**
     * Retrieve the customer has transactions or not
     *
     * @return bool
     */
    public function hasCustomerTransactions()
    {
        $transactions = [];
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId != null) {
            $this->searchCriteriaBuilder->addFilter(TransactionInterface::CUSTOMER_ID, $customerId);
            $transactions = $this->transactionRepository
                ->getList($this->searchCriteriaBuilder->create())
                ->getItems();
        }
        return $transactions && count($transactions);
    }
}
