<?php

namespace RKW\RkwMailer\Controller;

use \RKW\RkwMailer\Validation\QueueMailValidator;

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
 * MailerCommandController
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MailerCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
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
     * queueMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     * @inject
     */
    protected $queueMailRepository;

    /**
     * queueRecipientRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueRecipientRepository
     * @inject
     */
    protected $queueRecipientRepository;

    /**
     * bounceMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\BounceMailRepository
     * @inject
     */
    protected $bounceMailRepository;

    /**
     * frontendUserRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository;

    /**
     * StatisticSentUtility
     *
     * @var \RKW\RkwMailer\Utility\StatisticSentUtility
     * @inject
     */
    protected $statisticSentUtility;

    /**
     * configurationManager
     */
    protected $configurationManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * The settings.
     *
     * @var array
     */
    protected $settings = array();


    /**
     * Initialize the controller.
     */
    protected function initializeController()
    {

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        // get settings
        $this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
        $this->settings = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'RkwMailer', 'user'
        );
    }

    /**
     * Creates test-emails
     *
     * @param integer $numberOfTestMails Number of test-mails to generate
     * @param string $emails Comma-separated list of email-addresses to write to
     * @param integer $settingsPid Pid to fetch TypoScript-settings from
     * @throws \Exception
     * @throws \RKW\RkwMailer\Service\MailException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function createTestEmailsCommand($numberOfTestMails = 1, $emails = '', $settingsPid = 0)
    {

        // initialize globals
        $this->initializeController();

        /** @var \RKW\RkwMailer\Service\MailService $mailService */
        $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Service\\MailService');

        $emailArray = explode(',', str_replace(' ', '', $emails));

        if (count($emailArray)) {
            foreach (range(1, $numberOfTestMails) as $mailCounter) {

                foreach ($emailArray as $email) {
                    $mailService->setTo(array('email' => trim($email), 'firstName' => 'Max Eins', 'lastName' => 'Mustermann'),
                        array(
                            'marker' => array(
                                'pageUid' => intval($GLOBALS['TSFE']->id),
                            ),
                        )
                    );
                    $mailService->setTo(array('email' => trim($email), 'firstName' => 'Max Zwei', 'lastName' => 'Mustermann'),
                        array(
                            'marker' => array(
                                'pageUid' => intval($GLOBALS['TSFE']->id),
                            ),
                        )
                    );
                }

                $mailService->getQueueMail()->setSettingsPid(intval($settingsPid));
                $mailService->getQueueMail()->setSubject('Test ' . $mailCounter);
                $mailService->getQueueMail()->setPlaintextTemplate('Email/Example');
                $mailService->getQueueMail()->setHtmlTemplate('Email/Example');
                $mailService->send();
            }
        }
    }


    /**
     * Processes queued mails and sends them
     *
     * @param integer $emailsPerJob How many queueMails are to be processed during one cronjob
     * @param integer $emailsPerInterval How may emails are to be send in each queueMail
     * @param integer $settingsPid Pid to fetch TypoScript-settings from
     * @param float $sleep how many seconds the script should sleep after each e-mail sent
     * @return void
     */
    public function sendEmailsCommand($emailsPerJob = 5, $emailsPerInterval = 10, $settingsPid = 0, $sleep = 0.0)
    {

        // initialize globals
        $this->initializeController();

        try {

            /** @var \RKW\RkwMailer\Validation\QueueMailValidator $sendMailHelper */
            $queueMailValidator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(QueueMailValidator::class);

            /** @var \RKW\RkwMailer\Service\MailService $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Service\\MailService');

            // ====================================================
            // Get QueueMail and send mails to associated recipients
            // send mails so long until the input requirement is reached
            $queueMailCount = 0;

            // get mails with status "waiting" (2) or "sending" (3)
            /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
            $queueMails = $this->queueMailRepository->findByStatusWaitingOrSending($emailsPerJob);
            foreach ($queueMails as $queueMail) {

                try {

                    // if there is no configuration set, we use the one given as param
                    if (!$queueMail->getSettingsPid()) {
                        $queueMail->setSettingsPid(intval($settingsPid));
                    }

                    // set status to sending and set sending time (if not already set)
                    $queueMail->setStatus(3);
                    if (!$queueMail->getTstampRealSending()) {
                        $queueMail->setTstampRealSending(time());
                    }

                    // validate queueMail
                    if (!$queueMailValidator->validate($queueMail)) {

                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('Mail sending aborted because of invalid data in queueMail (queueMail uid "%s").', $queueMail->getUid()));
                        $queueMail->setStatus(99);
                        $this->queueMailRepository->update($queueMail);
                        continue;
                        //===
                    }

                    // get recipients of mail with status waiting
                    $queueRecipients = $this->queueRecipientRepository->findAllByQueueMailWithStatusWaiting($queueMail, $emailsPerInterval);
                    if (count($queueRecipients) > 0) {

                        // send mails
                        $mailService->setQueueMail($queueMail);
                        foreach ($queueRecipients as $recipient) {

                            try {
                                $mailService->sendToRecipient($recipient);
                                usleep(intval($sleep * 1000000));
                            }catch (\Exception $e) {
                                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('An error occurred while trying to send an e-mail to queueRecipient with uid = %s. Error: %s.', $recipient->getUid(), str_replace(array("\n", "\r"), '', $e->getMessage())));
                            }
                        }

                        // ====================================================
                        // Set QueueMail status as "sent" (4), if there are no more recipients
                        // except for the queueMail is used as pipeline
                    } else {

                        if (!$queueMail->getPipeline()) {
                            $queueMail->setStatus(4);
                            $queueMail->setTstampSendFinish(time());
                            $this->getLogger()->log(\TYPO3\CMS\Core \Log\LogLevel::INFO, sprintf('Successfully finished queueMail with uid = %s.', $queueMail->getUid()));
                        } else {
                            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Currently no recipients for queueMail with uid = %s, but marked for pipeline-usage.', $queueMail->getUid()));
                        }
                    }

                    // set counter
                    $queueMailCount++;
                    $this->queueMailRepository->update($queueMail);
                    $this->persistenceManager->persistAll();

                    // try to catch error and set status to 99
                } catch (\Exception $e) {

                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An unexpected error occurred while trying to send e-mails. Mail with id %s has been canceled. Error: %s.', $queueMail->getUid(), str_replace(array("\n", "\r"), '', $e->getMessage())));

                    $queueMail->setStatus(99);
                    $this->queueMailRepository->update($queueMail);
                    $this->persistenceManager->persistAll();
                    continue;
                    //===
                }
            }

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An unexpected error occurred while trying to send e-mails: %s.', str_replace(array("\n", "\r"), '', $e->getMessage())));
            $this->persistenceManager->persistAll();
        }

    }


    /**
     * Clean up for mailings
     *
     * @param integer $daysFromNow Defines which old mails should be deleted (send date is reference)
     * @param string $types Defines which types of mails the cleanup should look for (comma-separated). Default: only type "0"
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function cleanupCommand($daysFromNow = 8760, $types = '0')
    {

        if ($cleanupTimestamp = time() - (intval($daysFromNow) * 24 * 60 * 60)) {
            if (
                ($queueMails = $this->queueMailRepository->findAllOldMails($cleanupTimestamp, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $types, true)))
                && (count($queueMails))
            ) {

                // delete it. dependent objects are deleted by cascade
                foreach ($queueMails as $queueMail) {
                    $this->queueMailRepository->remove($queueMail);
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Deleted queueMail with uid=%s.', $queueMail->getUid()));
                }

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, 'Successfully cleaned up database.');
            } else {
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, 'Nothing to cleanup in database.');
            }
        }
    }


    /**
     * Analyse bounced mails
     *
     * @param string $username The username for the bounce-mail account
     * @param string $password The password for the bounce-mail account
     * @param string $host The host of the bounce-mail account
     * @param bool $usePop3 Use POP3-Protocol instead of IMAP (default: false)
     * @param int $port The port for the bounce-mail account (default: 143 - IMAP)
     * @param string $tlsMode The connection mode for the bounce-mail account (none, tls, notls, ssl, etc.; default: notls)
     * @param string $inboxName The name of the inbox (default: INBOX)
     * @param string $deleteBefore If set, all mails before the given date will be deleted (format: yyyy-mm-dd)
     * @param int $maxEmails
     * @return void
     */
    public function analyseBouncedMailsCommand($username, $password, $host, $usePop3 = false, $port = 143, $tlsMode = 'notls', $inboxName = 'INBOX', $deleteBefore = '', $maxEmails = 100)
    {

        try {

            $params = [
                'username' => $username,
                'password' => $password,
                'host' => $host,
                'usePop3' => boolval($usePop3),
                'port' => intval($port),
                'tlsMode' => $tlsMode,
                'inboxName' => $inboxName,
                'deleteBefore' => $deleteBefore,
            ];

            /** @var \RKW\RkwMailer\Utility\BounceMailUtility $bounceMailUtility */
            $bounceMailUtility = $this->objectManager->get('RKW\\RkwMailer\\Utility\\BounceMailUtility', $params);
            $bounceMailUtility->analyseMails($maxEmails);

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An unexpected error occurred while trying to analyse bounced e-mails: %s.', str_replace(array("\n", "\r"), '', $e->getMessage())));
        }
    }


    /**
     * Process bounced mails
     *
     * @param int $maxMails
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function processBouncedMailsCommand($maxMails = 100)
    {
        try {
            if ($bouncedRecipients = $this->queueRecipientRepository->findAllLastBounced($maxMails)) {

                /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
                foreach ($bouncedRecipients as $queueRecipient) {

                    // set status to bounced
                    $queueRecipient->setStatus(98);
                    $this->queueRecipientRepository->update($queueRecipient);

                    // set status of bounceMail to processed for all bounces of the same email-address
                    $bounceMails = $this->bounceMailRepository->findByEmail($queueRecipient->getEmail());

                    /** @var \RKW\RkwMailer\Domain\Model\BounceMail $bounceMail */
                    foreach ($bounceMails as $bounceMail) {
                        $bounceMail->setStatus(1);
                        $this->bounceMailRepository->update($bounceMail);

                        // set additional data of bouncing to specific frontendUser
                        /** @var \RKW\RkwMailer\Domain\Model\FrontendUser $frontendUser */
                        if ($frontendUser = $queueRecipient->getFrontendUser() instanceof \RKW\RkwMailer\Domain\Model\FrontendUser) {
                            if ($bounceMail->getType() == 'hard') {
                                $frontendUser->setTxRkwmailerHardBounceCount($frontendUser->getTxRkwmailerHardBounceCount() + 1);
                            } else {
                                if ($bounceMail->getType() == 'soft') {
                                    $frontendUser->setTxRkwmailerSoftBounceCount($frontendUser->getTxRkwmailerSoftBounceCount() + 1);
                                }
                            }
                            $frontendUser->setTxRkwmailerLastBounce(time());
                            $this->frontendUserRepository->update($frontendUser);
                        }

                        if ($queueRecipient->getQueueMail() instanceof \RKW\RkwMailer\Domain\Model\QueueMail) {
                            // StatisticSent: ALTERNATIVE EVALUATION
                            $this->statisticSentUtility->elevateStatistic($queueRecipient->getQueueMail(), $queueRecipient, 'bounced');
                        }
                    }

                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Setting bounced status for queueRecipient id=%, email=%s.', $queueRecipient->getUid(), $queueRecipient->getEmail()));
                }

            } else {
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, ('No bounced mails processed.'));
            }

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An unexpected error occurred while trying to process bounced e-mails: %s.', str_replace(array("\n", "\r"), '', $e->getMessage())));
        }
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
        //===
    }


}