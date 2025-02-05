<?php

namespace RKW\RkwMailer\Mail;

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

use RKW\RkwMailer\Cache\MailCache;
use RKW\RkwMailer\Domain\Model\MailingStatistics;
use RKW\RkwMailer\Domain\Repository\MailingStatisticsRepository;
use RKW\RkwMailer\Persistence\MarkerReducer;
use RKW\RkwMailer\Utility\QueueMailUtility;
use RKW\RkwMailer\Utility\QueueRecipientUtility;
use RKW\RkwMailer\View\MailStandaloneView;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use RKW\RkwBasics\Utility\FrontendSimulatorUtility;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwMailer\Domain\Repository\QueueRecipientRepository;
use RKW\RkwMailer\Domain\Repository\BounceMailRepository;
use RKW\RkwMailer\Validation\QueueMailValidator;
use RKW\RkwMailer\Validation\QueueRecipientValidator;
use RKW\RkwMailer\Validation\EmailValidator;

/**
 * Mailer
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Mailer
{

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;


    /**
     * configurationManager
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;


    /**
     * persistenceManager
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
     * mailingStatisticsRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\MailingStatisticsRepository
     * @inject
     */
    protected $mailingStatisticsRepository;


    /**
     * queueMailValidator
     *
     * @var \RKW\RkwMailer\Validation\QueueMailValidator
     * @inject
     */
    protected $queueMailValidator;


    /**
     * queueMailValidator
     *
     * @var \RKW\RkwMailer\Validation\QueueRecipientValidator
     * @inject
     */
    protected $queueRecipientValidator;

    
    /**
     * mailCache
     *
     * @var \RKW\RkwMailer\Cache\MailCache
     * @inject
     */
    protected $mailCache;
  
    
    /**
     * markerReducer
     *
     * @var \RKW\RkwMailer\Persistence\MarkerReducer
     * @inject
     */
    protected $markerReducer;
    
    
    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;
    
    
    /**
     * mailStandaloneView
     *
     * @var \RKW\RkwMailer\View\MailStandaloneView
     */
    protected $view;

    
    /**
     * Gets queueMails from queue and send mails to associated recipients
     *
     * @param integer $emailsPerJob How many queueMails are to be processed during one processing of the queue
     * @param integer $emailsPerInterval How may emails are to be sent for each queueMail
     * @param int $settingsPid
     * @param float $sleep how many seconds the script will sleep after each e-mail sent
     * @return array processed queueMails
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function processQueueMails (
        int $emailsPerJob = 5, 
        int $emailsPerInterval = 10,
        int $settingsPid = 0,
        float $sleep = 0.0
    ): array {

        self::debugTime(__LINE__, __METHOD__);
        $processedQueueMails = [];

        // get mails with status "waiting" (2) or "sending" (3)
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMails = $this->queueMailRepository->findByStatusWaitingOrSending($emailsPerJob);
        foreach ($queueMails as $queueMail) {

            try {

                // migrate values for backwards compatibility
                if (!$queueMail->getMailingStatistics()) {

                    /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatistics */
                    $mailingStatistics = GeneralUtility::makeInstance(MailingStatistics::class);
                    $mailingStatistics->setTstampFavSending($queueMail->getTstampFavSending());
                    $mailingStatistics->setTstampRealSending($queueMail->getTstampRealSending());
                    $mailingStatistics->setTstampFinishedSending($queueMail->getTstampSendFinish());
                    $mailingStatistics->setQueueMail($queueMail);
                    $queueMail->setMailingStatistics($mailingStatistics);
                }

                // validate queueMail
                if (! $this->queueMailValidator->validate($queueMail)) {
                    throw new \RKW\RkwMailer\Exception(
                        sprintf(
                            'Invalid data or missing data in queueMail with uid %s.',
                            $queueMail->getUid()
                        ),
                        1540186577
                    );
                }

                // if there is no configuration set, we use the one given as param
                if (!$queueMail->getSettingsPid()) {
                    $queueMail->setSettingsPid($settingsPid);
                }
                
                // set important default values for statistics
                $queueMail->getMailingStatistics()->setSubject($queueMail->getSubject());
                $queueMail->getMailingStatistics()->setType($queueMail->getType());
                
                // set status to sending and set sending time
                if ($queueMail->getStatus() != 3) {
                    $queueMail->setStatus(3);
                    $queueMail->getMailingStatistics()->setStatus(3);
                    $queueMail->getMailingStatistics()->setTstampRealSending(time());
                    $queueMail->getMailingStatistics()->setTstampFinishedSending(0);
                }

                // send mails to recipients
                // set QueueMail status as "sent" (4), if there are no more recipients
                // except for the queueMail is used as pipeline
                if (! count($this->processQueueRecipients($queueMail, $emailsPerInterval, $sleep)) > 0) {

                    if (!$queueMail->getPipeline()) {
                        $queueMail->setStatus(4);
                        $queueMail->getMailingStatistics()->setStatus(4);
                        $queueMail->getMailingStatistics()->setTstampFinishedSending(time());
                        $this->getLogger()->log(
                            \TYPO3\CMS\Core \Log\LogLevel::INFO, 
                            sprintf(
                                'Successfully finished queueMail with uid %s.', 
                                $queueMail->getUid()
                            )
                        );
                    } else {
                        $this->getLogger()->log(
                            LogLevel::INFO, 
                            sprintf(
                                'Currently no recipients for queueMail with uid %s, but marked for pipeline-usage.',
                                $queueMail->getUid()
                            )
                        );
                    }
                }
            
            // try to catch error and set status to 99
            } catch (\Exception $e) {

                $this->getLogger()->log(
                    LogLevel::ERROR, 
                    sprintf('
                        An unexpected error occurred while trying to send e-mails. QueueMail with uid %s has not been sent. Error: %s.', 
                        $queueMail->getUid(), 
                        str_replace(array("\n", "\r"), '', $e->getMessage())
                    )
                );

                $queueMail->getMailingStatistics()->setStatus(99);
                $queueMail->setStatus(99);
            }

            // persist changes
            $this->queueMailRepository->update($queueMail);
            if ($queueMail->getMailingStatistics()) {
                if ($queueMail->getMailingStatistics()->_isNew()) {
                    $this->mailingStatisticsRepository->add($queueMail->getMailingStatistics());
                } else {
                    $this->mailingStatisticsRepository->update($queueMail->getMailingStatistics());
                }
            }            
            $this->persistenceManager->persistAll();
            $processedQueueMails[] = $queueMail;
        }

        self::debugTime(__LINE__, __METHOD__);
        return $processedQueueMails;
    }

    
    
    /**
     * Gets queueRecipients for a given queueMail from queue and send mails to associated recipients
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param integer $emailsPerInterval How may emails are to be sent for each queueMail
     * @param float $sleep how many seconds the script will sleep after each e-mail sent
     * @return array processed queueRecipients
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function processQueueRecipients (
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail,
        int $emailsPerInterval = 10,
        float $sleep = 0.0
    ): array {

        self::debugTime(__LINE__, __METHOD__);

        $processedQueueRecipients = [];
        $queueRecipients = $this->queueRecipientRepository->findAllByQueueMailWithStatusWaiting($queueMail, $emailsPerInterval);
        if (count($queueRecipients) > 0) {

            // send mails
            foreach ($queueRecipients as $queueRecipient) {
                try {

                    // check if email of recipient has bounced recently - but only for pipeline mailings
                    if (
                        ($this->bounceMailRepository->countByEmailAndType($queueRecipient->getEmail()) < 3)
                        || (! $queueMail->getPipeline())
                    ){

                        // try to send message
                        try {

                            /** @var  \TYPO3\CMS\Core\Mail\MailMessage $message */
                            $message = $this->prepareEmailBody($queueMail, $queueRecipient);
                            $message->send();

                            // set recipient status 4 for "sent" and remove marker
                            $queueRecipient->setStatus(4);

                            $this->getLogger()->log(
                                LogLevel::INFO, 
                                sprintf('Successfully sent e-mail to "%s" (recipient-uid %s) for queueMail id %s.', 
                                    $queueRecipient->getEmail(), 
                                    $queueRecipient->getUid(), 
                                    $queueMail->getUid()
                                )
                            );
                            
                        } catch (\Exception $e) {
                            
                            // set recipient status to error
                            $queueRecipient->setStatus(99);

                            $this->getLogger()->log(
                                LogLevel::WARNING, 
                                sprintf(
                                    'An error occurred while trying to send an e-mail to "%s" (recipient-uid %s). Message: %s', 
                                    $queueRecipient->getEmail(), 
                                    $queueRecipient->getUid(),
                                    str_replace(array("\n", "\r"), '', $e->getMessage()))
                            );
                        }

                    } else {

                        // set status to deferred - we don't sent emails to this address again
                        $queueRecipient->setStatus(97);
                        $this->getLogger()->log(
                            LogLevel::WARNING, sprintf(
                                'E-mail "%s" (recipient-uid %s) blocked for further mailings because of bounces detected during processing of queueMail width uid %s.', 
                                $queueRecipient->getEmail(), 
                                $queueRecipient->getUid(), 
                                $queueMail->getUid()
                            )
                        );
                    }
                                        
                } catch (\Exception $e) {
                    $queueRecipient->setStatus(99);
                    $this->getLogger()->log(
                        LogLevel::WARNING, 
                        sprintf(
                            'An error occurred while trying to send an e-mail to queueRecipient with uid %s. Error: %s.',
                            $queueRecipient->getUid(), 
                            str_replace(array("\n", "\r"), '', $e->getMessage())
                        )
                    );
                }

                // persist
                $this->queueRecipientRepository->update($queueRecipient);
                $this->persistenceManager->persistAll();
                $processedQueueRecipients[] = $queueRecipient;

                // sleep
                usleep(intval($sleep * 1000000));
            }
        }

        self::debugTime(__LINE__, __METHOD__);
        return $processedQueueRecipients;
    }

   
    
    
    /**
     *  prepares email object for given recipient user
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     * @throws \RKW\RkwMailer\Exception
     */
    public function prepareEmailBody (
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail,
        \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
    ): \TYPO3\CMS\Core\Mail\MailMessage {

        self::debugTime(__LINE__, __METHOD__);

        // validate queueMail
        if (! $this->queueMailValidator->validate($queueMail)) {
            throw new \RKW\RkwMailer\Exception(
                sprintf(
                    'Invalid data or missing data in queueMail with uid %s.',
                    $queueMail->getUid()
                ),
                1438249330
            );
        }
        
        // validate queueRecipient
        if (! $this->queueRecipientValidator->validate($queueRecipient)) {
            throw new \RKW\RkwMailer\Exception(
                sprintf(
                    'Invalid data or missing data in queueRecipient with uid %s.',
                    $queueRecipient->getUid()
                ), 
                1552485792
            );
        }
        
        // render templates
        $this->renderTemplates($queueMail, $queueRecipient);

        /** @var \TYPO3\CMS\Core\Mail\MailMessage $message */
        $message = GeneralUtility::makeInstance(MailMessage::class);

        // Set message parts based on cache
        if (
            $this->mailCache->getPlaintextBody($queueRecipient)
            || $this->mailCache->getHtmlBody($queueRecipient)
        ) {

            // build e-mail
            foreach (
                array(
                    'html'     => 'html',
                    'plain'    => 'plaintext',
                ) as $shortName => $longName
            ) {

                $getter = 'get' . ucFirst($longName) . 'Body';
                if ($template = $this->mailCache->$getter($queueRecipient)) {
                    
                    $message->addPart($template, 'text/' . $shortName);
                    $this->getLogger()->log(
                        LogLevel::DEBUG, 
                        sprintf(
                            'Setting %s-body for recipient with uid=%s in queueMail with uid=%s.', 
                            $longName, 
                            $queueRecipient->getUid(), 
                            $queueMail->getUid()
                        )
                    );
                }
            }

        // set raw body-text from queueMail
        } else {
            $emailBody = $queueMail->getBodyText();
            $message->setBody($emailBody, 'text/plain');
            $this->getLogger()->log(
                LogLevel::DEBUG, 
                sprintf(
                    'Setting default body for recipient with uid %s in queueMail with uid %s.', 
                    $queueRecipient->getUid(), 
                    $queueMail->getUid()
                )
            );
        }

        // set calendar attachment
        if ($template = $this->mailCache->getCalendarBody($queueRecipient)) {

            // replace line breaks according to RFC 5545 3.1.
            $emailString = preg_replace('/\n/', "\r\n", $template);
            $attachment = \Swift_Attachment::newInstance($emailString, 'meeting.ics', 'text/calendar');
            $message->attach($attachment);
            $this->getLogger()->log(
                LogLevel::DEBUG, 
                sprintf(
                    'Setting calendar-body for recipient with uid %s in queueMail with uid %s.', 
                    $queueRecipient->getUid(), 
                    $queueMail->getUid()
                )
            );
        }

        
        // add attachment if set - old versions first
        if ($queueMail->getAttachment()) {
            $this->getLogger()->log(
                LogLevel::WARNING,
                'This method to add attachments is deprecated. Please use $this->setAttachmentPath to add attachments.'
            );
            GeneralUtility::deprecationLog(__CLASS__ . ': Please use $this->setAttachmentPath to add attachments.');

            // via BLOB: old version from Max
            /** @deprecated */
            if (
                (is_string($queueMail->getAttachment()) 
                && (! json_decode($queueMail->getAttachment(), true)))
            ) {

                $attachment = \Swift_Attachment::newInstance(
                    $queueMail->getAttachment(), 
                    $queueMail->getAttachmentName(), 
                    $queueMail->getAttachmentType()
                );
                $message->attach($attachment);
            }
            
            // via array - old version from Christian
            /** @deprecated */
            if (is_array($attachments = json_decode($queueMail->getAttachment(), true))) {
                foreach ($attachments as $attachment) {
                    $file = \Swift_Attachment::fromPath(
                        $attachment['path'], 
                        $attachment['type']
                    );
                    $message->attach($file);
                }
            }            
        }
        
        // add attachments - new version
        if ($attachments = $queueMail->getAttachmentPaths()) {
            foreach ($attachments as $attachment) {
                $file = \Swift_Attachment::fromPath(
                    $attachment,
                    mime_content_type($attachment)
                );
                $message->attach($file);
            }
        }
        
        // add mailing list header if type > 0
        if ($queueMail->getType() > 0) {
            $message->getHeaders()->addTextHeader('List-Unsubscribe', '<mailto:' . EmailValidator::cleanUpEmail($queueMail->getFromAddress()) . '>');
        }

        // ====================================================
        // Send mail
        // build message based on given data
        $recipientAddress = [EmailValidator::cleanUpEmail($queueRecipient->getEmail()) => null];
        $recipientName = ucfirst($queueRecipient->getTitle()) . ' ';
        $recipientName .= trim(ucfirst($queueRecipient->getFirstName()) . ' ' . ucfirst($queueRecipient->getLastName()));
        if ($recipientName) {
            $recipientAddress = [EmailValidator::cleanUpEmail($queueRecipient->getEmail()) => trim($recipientName)];
        }

        $message->setFrom([EmailValidator::cleanUpEmail($queueMail->getFromAddress()) => $queueMail->getFromName()])
            ->setReplyTo([EmailValidator::cleanUpEmail($queueMail->getReplyToAddress()) => $queueMail->getReplyToName() ?: $queueMail->getFromName()])
            ->setReturnPath(EmailValidator::cleanUpEmail($queueMail->getReturnPath()))
            ->setPriority($queueMail->getPriority())
            ->setTo($recipientAddress)
            ->setSubject($queueRecipient->getSubject() ?: $queueMail->getSubject());

        self::debugTime(__LINE__, __METHOD__);
        return $message;
    }


    /**
     * rendering of templates
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @return void
     * @throws \RKW\RkwMailer\Exception
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderTemplates(
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail,
        \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
    ): void
    {

        self::debugTime(__LINE__, __METHOD__);

        // check if queueMail is persisted
        if ($queueMail->_isNew()) {
            throw new \RKW\RkwMailer\Exception(
                'The queueMail-object has to be persisted before it can be used.',
                1540294117
            );
        }

        // check if queueRecipient is persisted
        if ($queueRecipient->_isNew()) {
            throw new \RKW\RkwMailer\Exception(
                'The queueRecipient-object has to be persisted before it can be used.',
                1540294116
            );
        }

        // build HTML- or Plaintext- Template if set!
        foreach (['html', 'plaintext', 'calendar'] as $type) {

            $templateGetter = 'get' . ucFirst($type) . 'Template';
            $propertySetter = 'set' . ucFirst($type) . 'Body';
            $propertyGetter = 'get' . ucFirst($type) . 'Body';

            if ($queueMail->$templateGetter()) {

                // check if templates have already been rendered and stored in cache
                if (! $this->mailCache->$propertyGetter($queueRecipient)) {

                    // load EmailStandaloneView with configuration of queueMail
                    /** @var \RKW\RkwMailer\View\MailStandaloneView $emailView */
                    $emailView = GeneralUtility::makeInstance(
                        MailStandaloneView::class,
                        $queueMail->getSettingsPid()
                    );

                    $emailView->setQueueMail($queueMail);
                    $emailView->setQueueRecipient($queueRecipient);
                    $emailView->setTemplateType($type);
                    $emailView->assignMultiple($queueRecipient->getMarker());
                    $renderedTemplate = $emailView->render();

                    // cache rendered templates
                    $this->mailCache->$propertySetter($queueRecipient, $renderedTemplate);

                    $this->getLogger()->log(
                        LogLevel::DEBUG,
                        sprintf(
                            'Added %s-template-property for recipient with email "%s" (queueMail uid=%s).',
                            ucFirst($type),
                            $queueRecipient->getEmail(),
                            $queueMail->getUid()
                        )
                    );
                } else {
                    $this->getLogger()->log(
                        LogLevel::DEBUG,
                        sprintf(
                            '%s-template-property is already set for recipient with email "%s" (queueMail uid=%s).',
                            ucFirst($type),
                            $queueRecipient->getEmail(),
                            $queueMail->getUid()
                        )
                    );
                }
            } else {
                $this->getLogger()->log(
                    LogLevel::DEBUG,
                    sprintf(
                        '%s-template is not set for recipient with email "%s" (queueMail uid=%s).',
                        ucFirst($type),
                        $queueRecipient->getEmail(),
                        $queueMail->getUid()
                    )
                );
            }
        }

        self::debugTime(__LINE__, __METHOD__);
    }
    
    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): \TYPO3\CMS\Core\Log\Logger
    {
        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
        return $this->logger;
    }
    

    /**
     * Does debugging of runtime
     *
     * @param integer $line
     * @param string  $function
     */
    private static function debugTime(int $line, string $function): void
    {
        if (GeneralUtility::getApplicationContext()->isDevelopment()) {

            $path = PATH_site . '/typo3temp/var/logs/tx_rkwmailer_runtime.txt';
            file_put_contents($path, microtime() . ' ' . $line . ' ' . $function . "\n", FILE_APPEND);
        }
    }


}