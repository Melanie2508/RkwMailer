<?php

namespace RKW\RkwMailer\Utility;

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
 * Statistics
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatisticsUtility
{

    /**
     * QueueMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     * @inject
     */
    protected $queueMailRepository;

    /**
     * QueueRecipientRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueRecipientRepository
     * @inject
     */
    protected $queueRecipientRepository;

    /**
     * LinkRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\LinkRepository
     * @inject
     */
    protected $linkRepository;

    /**
     * statisticOpeningRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\StatisticOpeningRepository
     * @inject
     */
    protected $statisticOpeningRepository;

    /**
     * statisticSentRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\StatisticSentRepository
     * @inject
     */
    protected $statisticSentRepository;

    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;



    /**
     * getRedirectLink
     * Get the redirect link and count it accordingly in statistics
     *
     * @param string $hash
     * @param int $queueMailId
     * @param int $queueMailRecipientId
     * @return false|string
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function getRedirectLink($hash, $queueMailId, $queueMailRecipientId = 0)
    {

        /** @var \RKW\RkwMailer\Domain\Model\Link $link */
        if ($link = $this->linkRepository->findOneByHash($hash)) {

            // additional params
            $additionalParams = [];

            // check for queueMail
            /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
            if (
                ($queueMail = $this->queueMailRepository->findByUid($queueMailId))
                && ($link->getQueueMail())
                && ($link->getQueueMail()->getUid() == $queueMail->getUid())
            ) {

                // set additional params
                $additionalParams[] = 'tx_rkwmailer[mid]=' . $queueMail->getUid();

                // check additionally for queueRecipient
                /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueMailRecipient */
                if (
                    ($queueMailRecipientId)
                    && ($queueMailRecipient = $this->queueRecipientRepository->findOneByUidAndQueueMail($queueMailRecipientId, $queueMail))
                ) {

                    // set additional params
                    $additionalParams[] = 'tx_rkwmailer[uid]=' . $queueMailRecipient->getUid();
                }

                // get statistics if already created
                /** @var \RKW\RkwMailer\Domain\Model\StatisticOpening $statisticOpening */
                $statisticOpening = $this->statisticOpeningRepository->findOneByLinkAndQueueRecipient($link, $queueMailRecipient);
                if (!$statisticOpening) {

                    // create new statisticOpening for mailId/recipientId-combination
                    $statisticOpening = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Domain\\Model\\StatisticOpening');
                    $statisticOpening->setClickCount($statisticOpening->getClickCount() + 1);
                    $statisticOpening->setQueueMail($queueMail);
                    $statisticOpening->setLink($link);

                    $this->statisticOpeningRepository->add($statisticOpening);
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Adding new statisticOpening for redirect (queueMail uid=%s).', $queueMail->getUid()));

                // update existing
                } else {
                    $statisticOpening->setClickCount($statisticOpening->getClickCount() + 1);
                    $this->statisticOpeningRepository->update($statisticOpening);
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Updating statisticOpening uid=%s for redirect (queueMail uid=%s).', $statisticOpening->getUid(), $queueMail->getUid()));
                }

                $this->persistenceManager->persistAll();
            }


            // build link - anchor has to be added at the end of the link
            $finalLink = $link->getUrl();
            if ($additionalParams) {

                // add queueRecipient and queueMail respectively and THEN add anchor
                if ($section = parse_url($link->getUrl(), PHP_URL_FRAGMENT)) {
                    $finalLink = str_replace('#' . $section, '', $finalLink);
                    $section = '#' . $section;
                }
                $finalLink = $finalLink . (parse_url($finalLink, PHP_URL_QUERY) ? '&' : '?') . implode('&', $additionalParams) . $section;
            }

            return $finalLink;
            //===

        }

        return false;
        //===

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