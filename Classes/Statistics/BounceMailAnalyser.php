<?php

namespace RKW\RkwMailer\Statistics;

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

use BounceMailHandler\BounceMailHandler;
use RKW\RkwMailer\Domain\Model\BounceMail;

/**
 * BounceMailAnalyser
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BounceMailAnalyser
{

    
    /**
     * @var \BounceMailHandler\BounceMailHandler $bounceMailHandler
     */
    protected $bounceMailHandler;


    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;


    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * bounceMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\BounceMailRepository
     * @inject
     */
    protected $bounceMailRepository;


    /**
     * Logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * BounceMail constructor.
     * @param array $params
     * @throws \RKW\RkwMailer\Exception
     */
    public function __construct($params)
    {

        $defaultParams = [
            'username' => '',
            'password' => '',
            'host' => 'localhost',
            'usePop3' => false,
            'port' => 143,
            'tlsMode' => 'notls',
            'inboxName' => 'INBOX',
            'deleteBefore' => ''
        ];
        $params = array_merge($defaultParams, $params);

        // check basics
        if (
            (! $params['username'])
            || (! $params['password'])
        ) {
            throw new \RKW\RkwMailer\Exception('No username or password for mailbox set.');
        }

        $this->bounceMailHandler = new BounceMailHandler();
        $this->bounceMailHandler->actionFunction = array($this, 'bounceMailCallback');
        $this->bounceMailHandler->verbose = BounceMailHandler::VERBOSE_QUIET;
        $this->bounceMailHandler->useFetchStructure = true;
        $this->bounceMailHandler->testMode = false;
        $this->bounceMailHandler->debugBodyRule  = false;
        $this->bounceMailHandler->debugDsnRule = false;
        $this->bounceMailHandler->purgeUnprocessed = true;
        $this->bounceMailHandler->disableDelete = false;

        // Mailbox login
        $this->bounceMailHandler->mailhost = $params['host'];
        $this->bounceMailHandler->mailboxUserName = $params['username'];
        $this->bounceMailHandler->mailboxPassword = $params['password'];
        $this->bounceMailHandler->port = $params['port'];
        $this->bounceMailHandler->boxname = $params['inboxName'];

        // deletes mails before given date
        if ($params['deleteBefore']) {
            $this->bounceMailHandler->deleteMsgDate = $params['deleteBefore'];
        }

        // set protocol
        $this->bounceMailHandler->service = 'imap';
        if ($params['usePop3']) {
            $this->bounceMailHandler->service = 'pop3';
        }

        // set connection type
        $this->bounceMailHandler->serviceOption = 'notls';
        if (in_array($params['tlsMode'], ['tls', 'ssl'])) {
            $this->bounceMailHandler->serviceOption = $params['tlsMode'];
        }

    }


    /**
     * analyseMails
     *
     * @param int|false $limit
     */
    public function analyseMails ($limit = false) {

        // now login and analyse the mails in mailbox
        $this->bounceMailHandler->openMailbox();
        $this->bounceMailHandler->processMailbox($limit);
        $this->persistenceManager->persistAll();
    }



    /**
     * bounceMailCallback
     *
     * @param int $counter  the message number returned by Bounce Mail Handler
     * @param string  $type the bounce type: 'antispam','autoreply','concurrent','content_reject','command_reject','internal_error','defer','delayed'
     *                                     => array('remove'=>0,'bounce_type'=>'temporary'),'dns_loop','dns_unknown','full','inactive','latin_only','other','oversize','outofoffice','unknown','unrecognized','user_reject','warning'
     * @param string $email the target email address
     * @param string $subject the subject, ignore now
     * @param object $header the XBounceHeader from the mail
     * @param boolean $remove remove status, 1 means removed, 0 means not removed
     * @param string|boolean $ruleNumber  Bounce Mail Handler detect rule no.
     * @param string|boolean $ruleCategory      Bounce Mail Handler detect rule category.
     * @param int  $totalFetched total number of messages in the mailbox
     * @param string $body Bounce Mail Body
     * @param string $headerFull Bounce Mail Header
     * @param string $bodyFull Bounce Mail Body (full)
     *
     * @return boolean
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    function bounceMailCallback ($counter, $type, $email, $subject, $header, $remove, $ruleNumber = false, $ruleCategory = false, $totalFetched = 0, $body = '', $headerFull = '', $bodyFull = '')
    {

        $this->cleanupData($ruleNumber, $email, $type);

        if (
            ($email)
            && ($type != 'none')
        ){

            /** @var \RKW\RkwMailer\Domain\Model\BounceMail $bounceMail */
            $bounceMail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(BounceMail::class);

            $bounceMail->setType($type);
            $bounceMail->setRuleNumber($ruleNumber);
            $bounceMail->setRuleCategory($ruleCategory);
            $bounceMail->setEmail($email);
            $bounceMail->setSubject($subject);
            $bounceMail->setHeader(json_decode(json_encode($header), true));
            $bounceMail->setBody($body);
            $bounceMail->setHeaderFull($headerFull);
            $bounceMail->setBodyFull($bodyFull);

            $this->bounceMailRepository->add($bounceMail);
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Added bounceMail for email "%s".', $email));
        }

        return true;
    }


    /**
     * cleanup data
     *
     * @param int $ruleNumber
     * @param string $email
     * @param string $type
     */
    public function cleanupData (&$ruleNumber, &$email, &$type)
    {

        $ruleNumber = intval($ruleNumber);

        $type = trim($type);
        if (trim($type) == '') {
            $type = 'none';
        }

        if (strpos($email, '<') !== false) {
            $posStart = strpos($email, '<');
            $email = substr($email, $posStart + 1);
            $posEnd = strpos($email, '>');
            if ($posEnd) {
                $email = substr($email, 0, $posEnd);
            }
        }

        // replace the < and > able so they display on screen
        $email = str_replace(array('<', '>'), array('&lt;', '&gt;'), $email);

        // replace the "TO:<" with nothing
        $email = strtolower(str_ireplace('TO:<', '', $email));
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
        }

        return $this->logger;
    }


}