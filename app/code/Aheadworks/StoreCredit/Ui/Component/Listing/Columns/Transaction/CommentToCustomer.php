<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Ui\Component\Listing\Columns\Transaction;

use Aheadworks\StoreCredit\Model\Comment\CommentPoolInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Class Aheadworks\StoreCredit\Ui\Component\Listing\Columns\Transaction\CommentToCustomer
 */
class CommentToCustomer extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var CommentPoolInterface
     */
    private $commentPool;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CommentPool $commentPool
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CommentPoolInterface $commentPool,
        array $components = [],
        array $data = []
    ) {
        $this->commentPool = $commentPool;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     *  {@inheritDoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['type'])) {
                    if ($commentInstance = $this->commentPool->get($item['type'])) {
                        $commentLabel = $commentInstance->renderComment(
                            $item['entities'],
                            null,
                            $item['comment_to_customer_placeholder'],
                            true
                        );
                    }

                    if (!empty($commentLabel)) {
                        $item['comment_to_customer'] = $commentLabel;
                    }
                }
            }
        }
        return $dataSource;
    }
}
