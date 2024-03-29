<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Model\Source;

use Aheadworks\StoreCredit\Model\Comment\CommentPool;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class SubscribeStatus
 *
 * @package Aheadworks\StoreCredit\Model\Source
 */
class SubscribeStatus implements ArrayInterface
{
    /**#@+
     * Subscribe status values
     */
    const SUBSCRIBED = 1;
    const NOT_SUBSCRIBED = 2;
    const UNSUBSCRIBED = 3;
    /**#@-*/

    /**
     *  {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SUBSCRIBED,
                'label' => __('Subscribed')
            ],
            [
                'value' => self::NOT_SUBSCRIBED,
                'label' => __('Not Subscribed')
            ],
            [
                'value' => self::UNSUBSCRIBED,
                'label' => __('Unsubscribed')
            ]
        ];
    }
}
