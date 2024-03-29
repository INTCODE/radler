<?php
/**
 * Copyright © 2017 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\QuickBooksOnline\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Account
 * @package Magenest\QuickBooksOnline\Model
 * @method int getId()
 * @method string getName()
 * @method string getType()
 */
class Account extends AbstractModel
{
    /**
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magenest\QuickBooksOnline\Model\ResourceModel\Account');
    }
}
