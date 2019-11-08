<?php

namespace RKW\RkwMailer\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class FrontendUser
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class FrontendUser extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
{
    /**
     * @var integer
     */
    protected $txRkwmailerSoftBounceCount;

    /**
     * @var integer
     */
    protected $txRkwmailerHardBounceCount;

    /**
     * @var integer
     */
    protected $txRkwmailerLastBounce;

    /**
     * Returns the txRkwmailerSoftBounceCount
     *
     * @return integer
     */
    public function getTxRkwmailerSoftBounceCount()
    {
        return $this->txRkwmailerSoftBounceCount;
    }

    /**
     * Sets the txRkwmailerSoftBounceCount
     *
     * @param integer $txRkwmailerSoftBounceCount
     */
    public function setTxRkwmailerSoftBounceCount($txRkwmailerSoftBounceCount)
    {
        $this->txRkwmailerSoftBounceCount = $txRkwmailerSoftBounceCount;
    }

    /**
     * Returns the txRkwmailerHardBounceCount
     *
     * @return integer
     */
    public function getTxRkwmailerHardBounceCount()
    {
        return $this->txRkwmailerHardBounceCount;
    }

    /**
     * Sets the txRkwmailerHardBounceCount
     *
     * @param integer $txRkwmailerHardBounceCount
     */
    public function setTxRkwmailerHardBounceCount($txRkwmailerHardBounceCount)
    {
        $this->txRkwmailerHardBounceCount = $txRkwmailerHardBounceCount;
    }

    /**
     * Returns the txRkwmailerLastBounce
     *
     * @return integer
     */
    public function getTxRkwmailerLastBounce()
    {
        return $this->txRkwmailerLastBounce;
    }

    /**
     * Sets the txRkwmailerLastBounce
     *
     * @param integer $txRkwmailerLastBounce
     */
    public function setTxRkwmailerLastBounce($txRkwmailerLastBounce)
    {
        $this->txRkwmailerLastBounce = $txRkwmailerLastBounce;
    }
}
