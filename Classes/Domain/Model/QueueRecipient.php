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
 * QueueRecipient
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueueRecipient extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * queueMail
     *
     * @var \RKW\RkwMailer\Domain\Model\QueueMail
     */
    protected $queueMail;


    /**
     * frontendUser
     *
     * @var \RKW\RkwMailer\Domain\Model\FrontendUser
     */
    protected $frontendUser;

    /**
     * email
     *
     * @var string
     */
    protected $email = '';

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * salutation
     *
     * @var int
     */
    protected $salutation = 99;

    /**
     * firstName
     *
     * @var string
     */
    protected $firstName = '';

    /**
     * lastName
     *
     * @var string
     */
    protected $lastName = '';

    /**
     * subject
     *
     * @var string
     */
    protected $subject = '';

    /**
     * marker
     *
     * @var string
     */
    protected $marker = '';

    /**
     * markerUnserialized
     *
     * @var array
     */
    protected $markerUnserialized = '';

    /**
     * plaintextBody
     *
     * @var string
     */
    protected $plaintextBody = '';

    /**
     * htmlBody
     *
     * @var string
     */
    protected $htmlBody = '';

    /**
     * calendarBody
     *
     * @var string
     */
    protected $calendarBody = '';

    /**
     * status
     *
     * @var integer
     */
    protected $status = 0;

    /**
     * languageCode
     *
     * @var string
     */
    protected $languageCode = 'de';


    /**
     *
     * MailBodyCache
     *
     * @var \RKW\RkwMailer\Cache\MailBodyCache
     */
    protected $mailBodyCache;



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
     * Sets the queueMail
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @return void
     */
    public function setQueueMail($queueMail)
    {
        $this->queueMail = $queueMail;
    }

    /**
     * Returns the frontendUser
     *
     * @return \RKW\RkwMailer\Domain\Model\FrontendUser
     */
    public function getFrontendUser()
    {
        return $this->frontendUser;
    }

    /**
     * Sets the frontendUser
     *
     * @param \RKW\RkwMailer\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser($frontendUser)
    {
        $this->frontendUser = $frontendUser;
    }

    /**
     * Returns the email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the email
     *
     * @param string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the salutation
     *
     * @return int $salutation
     */
    public function getSalutation()
    {
        return $this->salutation;
    }


    /**
     * Returns the salutation
     *
     * @return string $salutation
     */
    public function getSalutationText()
    {
        if ($this->getSalutation() < 99) {

            return \RKW\RkwMailer\Utility\FrontendLocalizationUtility::translate(
                'tx_rkwmailer_domain_model_queuerecipient.salutation.I.' . $this->getSalutation(),
                'rkw_mailer',
                array(),
                $this->getLanguageCode()
            );
            //===

        }

        return '';
        //===
    }

    /**
     * Sets the salutation
     *
     * @param int $salutation
     * @return void
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }


    /**
     * Returns the firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Sets the firstName
     *
     * @param string $firstName
     * @return void
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Returns the lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Sets the lastName
     *
     * @param string $lastName
     * @return void
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }


    /**
     * Returns the subject
     *
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets the subject
     *
     * @param string $subject
     * @return void
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }


    /**
     * Returns the marker
     *
     * @return mixed $marker
     */
    public function getMarker()
    {
        if ($this->markerUnserialized) {
            return $this->markerUnserialized;
        }

        return unserialize($this->marker);
    }

    /**
     * Sets the marker
     *
     * @param mixed $marker
     * @return void
     */
    public function setMarker($marker)
    {
        $this->markerUnserialized = $marker;
        $this->marker = serialize($marker);
    }

    /**
     * Returns the plaintextBody
     *
     * @return string $plaintextBody
     */
    public function getPlaintextBody()
    {
        if (! empty($this->plaintextBody)) {
            return $this->plaintextBody;
            //===
        }

        return $this->getMailBodyCache()->getPlaintextBody($this);
        //===
    }

    /**
     * Sets the plaintextBody
     *
     * @param string $plaintextBody
     * @return void
     */
    public function setPlaintextBody($plaintextBody)
    {
        $this->plaintextBody = $plaintextBody;
        $this->getMailBodyCache()->setPlaintextBody($this, $plaintextBody);
    }


    /**
     * Returns the htmlBody
     *
     * @return string $htmlBody
     */
    public function getHtmlBody()
    {
        if (! empty($this->htmlBody)) {
            return $this->htmlBody;
            //===
        }

        return $this->getMailBodyCache()->getHtmlBody($this);
        //===
    }


    /**
     * Sets the htmlBody
     *
     * @param string $htmlBody
     * @return void
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;
        $this->getMailBodyCache()->setHtmlBody($this, $htmlBody);
    }


    /**
     * Returns the calendarBody
     *
     * @return string $calendarBody
     */
    public function getCalendarBody()
    {
        if (! empty($this->calendarBody)) {
            return $this->calendarBody;
            //===
        }

        return $this->getMailBodyCache()->getCalendarBody($this);
        //===
    }

    /**
     * Sets the calendarBody
     *
     * @param string $calendarBody
     * @return void
     */
    public function setCalendarBody($calendarBody)
    {
        $this->calendarBody = $calendarBody;
        $this->getMailBodyCache()->setCalendarBody($this, $calendarBody);
    }

    /**
     * Returns the status
     *
     * @return integer $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param integer $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }


    /**
     * Returns the languageCode
     *
     * @return string $languageCode
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * Sets the languageCode
     *
     * @param string $languageCode
     * @return void
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    }


    /**
     * Returns logger instance
     *
     * @return \RKW\RkwMailer\Cache\MailBodyCache
     */
    protected function getMailBodyCache()
    {

        if (!$this->mailBodyCache instanceof \TYPO3\CMS\Core\Log\Logger) {

            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            $this->mailBodyCache = $objectManager->get('RKW\\RkwMailer\\Cache\\MailBodyCache');        
        }

        return $this->mailBodyCache;
        //===
    }
    
}