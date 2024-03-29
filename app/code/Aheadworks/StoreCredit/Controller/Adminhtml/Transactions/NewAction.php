<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Aheadworks\StoreCredit\Controller\Adminhtml\Transactions\NewAction
 */
class NewAction extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Aheadworks_StoreCredit::aw_store_credit_transaction_save';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     *  {@inheritDoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage **/
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('Aheadworks_StoreCredit::aw_store_credit_transaction');

        $resultPage->addBreadcrumb(__('Aheadworks Store Credit & Refund'), __('Aheadworks Store Credit & Refund'))
            ->addBreadcrumb(__('Transactions'), __('Transactions'))
            ->addBreadcrumb(__('New Transaction'), __('New Transaction'));

        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Transactions'));
        $title->prepend(__('New Transaction'));

        return $resultPage;
    }
}
