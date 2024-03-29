<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Model;

use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Aheadworks\StoreCredit\Model\DateTime
 */
class DateTime
{
    const DATETIME_PHP_MONTH = 'm';

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Is today now
     *
     * @param string $date
     * @return boolean
     */
    public function isTodayDate($date)
    {
        if ($date == null) {
            return false;
        }
        return $this->getTodayDate(false) == $this->getDate($date, false);
    }

    /**
     * Retrieve date
     *
     * @param string $date
     * @param boolean $withTime
     * @return string
     */
    public function getDate($date, $withTime = false)
    {
        return $this->getFormatedDate($date, $withTime);
    }

    /**
     * Retrieve today date
     *
     * @param boolean $withTime
     * @return string
     */
    public function getTodayDate($withTime = false)
    {
        return $this->getFormatedDate(null, $withTime);
    }

    /**
     * Retrieve formated date
     *
     * @param string $date
     * @param boolean $withTime
     * @return string
     */
    private function getFormatedDate($date = null, $withTime = false)
    {
        $format = $withTime ? StdlibDateTime::DATETIME_PHP_FORMAT : StdlibDateTime::DATE_PHP_FORMAT;
        return $this->date($date)->format($format);
    }

    /**
     * Is current month now
     *
     * @param string $date
     * @return boolean
     */
    public function isCurrentMonthDate($date)
    {
        if ($date == null) {
            return false;
        }
        return $this->getMonthDate() == $this->date($date)->format(self::DATETIME_PHP_MONTH);
    }

    /**
     * Retrieve current month
     *
     * @return string
     */
    public function getMonthDate()
    {
        return $this->date()->format(self::DATETIME_PHP_MONTH);
    }

    /**
     * Retrieve expiration date
     *
     * @param string $expireInDays
     * @param boolean $useTimezone
     * @return string
     */
    public function getExpirationDate($expireInDays, $useTimezone = true)
    {
        return  $this->date(null, null, $useTimezone)
            ->add(new \DateInterval('P' . (int) $expireInDays . 'D'))
            ->format(StdlibDateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * Retrieve \DateTime object for current locale
     *
     * @param mixed $date
     * @param string $locale
     * @param bool $useTimezone
     * @return \DateTime
     */
    private function date($date = null, $locale = null, $useTimezone = false)
    {
        return $this->timezone->date(strtotime($date), $locale, $useTimezone);
    }
}
