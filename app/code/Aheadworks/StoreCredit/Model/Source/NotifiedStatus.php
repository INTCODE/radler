<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Model\Source;

use Aheadworks\StoreCredit\Model\Comment\CommentPool;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class NotifiedStatus
 *
 * @package Aheadworks\StoreCredit\Model\Source
 */
class NotifiedStatus implements ArrayInterface
{
    /**#@+
     * Notified status values
     */
    const YES = 1;
    const NO = 2;
    const NOT_SUBSCRIBED = 3;
    /**#@-*/

    /**
     *  {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::YES,
                'label' => __('Yes')
            ],
            [
                'value' => self::NO,
                'label' => __('No')
            ],
            [
                'value' => self::NOT_SUBSCRIBED,
                'label' => __('Not Subscribed')
            ]
        ];
    }
}
