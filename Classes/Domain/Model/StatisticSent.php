<?php

namespace RKW\RkwMailer\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * StatisticSent
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatisticSent extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * queueMail
     *
     * @var \RKW\RkwMailer\Domain\Model\QueueMail
     */
    protected $queueMail;

    /**
     * foreignUid
     *
     * @var integer
     */
    protected $foreignUid = 0;

    /**
     * foreignTable
     *
     * @var string
     */
    protected $foreignTable = '';

    /**
     * foreignField
     *
     * @var string
     */
    protected $foreignField = '';

    /**
     * total
     *
     * @var integer
     */
    protected $total;


    /**
     * sent
     *
     * @var integer
     */
    protected $sent;


    /**
     * successful
     *
     * @var integer
     */
    protected $successful;


    /**
     * failed
     *
     * @var integer
     */
    protected $failed;

    /**
     * deferred
     *
     * @var integer
     */
    protected $deferred;

    /**
     * bounced
     *
     * @var integer
     */
    protected $bounced;


    /**
     * opened
     *
     * @var integer
     */
    protected $opened;


    /**
     * clicked
     *
     * @var integer
     */
    protected $clicked;

    /**
     * Returns the queueMail
     *
     * @return \RKW\RkwMailer\Domain\Model\QueueMail
     */
    public function getQueueMail()
    {
        return $this->queueMail;
    }

    /**
     * Sets the mailQueue
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @return void
     */
    public function setQueueMail($queueMail)
    {
        $this->queueMail = $queueMail;
    }

    /**
     * @return int
     */
    public function getForeignUid()
    {
        return $this->foreignUid;
    }

    /**
     * @param int $foreignUid
     */
    public function setForeignUid($foreignUid)
    {
        $this->foreignUid = $foreignUid;
    }

    /**
     * @return string
     */
    public function getForeignTable()
    {
        return $this->foreignTable;
    }

    /**
     * @param string $foreignTable
     */
    public function setForeignTable($foreignTable)
    {
        $this->foreignTable = $foreignTable;
    }

    /**
     * @return string
     */
    public function getForeignField()
    {
        return $this->foreignField;
    }

    /**
     * @param string $foreignField
     */
    public function setForeignField($foreignField)
    {
        $this->foreignField = $foreignField;
    }

    /**
     * Returns the total
     *
     * @return integer $total
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * Returns the sent
     *
     * @return integer $sent
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @param int $sent
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
    }

    /**
     * Returns the successful
     *
     * @return integer $successful
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * @param int $successful
     */
    public function setSuccessful($successful)
    {
        $this->successful = $successful;
    }

    /**
     * Returns the failed
     *
     * @return integer $failed
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * @param int $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }

    /**
     * Returns the deferred
     *
     * @return integer $deferred
     */
    public function getDeferred()
    {
        return $this->deferred;
    }

    /**
     * @param int $deferred
     */
    public function setDeferred($deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * Returns the bounced
     *
     * @return integer $bounced
     */
    public function getBounced()
    {
        return $this->bounced;
    }

    /**
     * @param int $bounced
     */
    public function setBounced($bounced)
    {
        $this->bounced = $bounced;
    }

    /**
     * Returns the opened
     *
     * @return integer $opened
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * @param int $opened
     */
    public function setOpened($opened)
    {
        $this->opened = $opened;
    }

    /**
     * Returns the clicked
     *
     * @return integer $clicked
     */
    public function getClicked()
    {
        return $this->clicked;
    }
    /**
     * @param int $clicked
     */
    public function setClicked($clicked)
    {
        $this->clicked = $clicked;
    }
}