<?php

namespace RKW\RkwMailer\Utility;

use \RKW\RkwMailer\Domain\Model\QueueMail;
use \RKW\RkwMailer\Domain\Model\QueueRecipient;
use \TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
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
class StatisticSentUtility
{
    /**
     * newsletter type
     *
     * @const integer
     */
    const NEWSLETTER_TYPE = 1;

    /**
     * available action
     * represented the available setter methods of the StatisticSent-Model
     *
     * @const array
     */
    const AVAILABLE_STATISTIC_ACTIONS = ['sent', 'successful', 'failed', 'deferred', 'bounced', 'opened', 'clicked'];

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

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
     * elevateStatistic
     * -> returns false, if the given action name does not match the const array
     * -> returns false, if the given action name does not exists as getter and setter methods in the model
     * take a look to your local log files, to get more information on crashing
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param string $action The field you want to count.
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $entityRelation Use this to handle generic sub-statistics. Not needed for RkwNewsletter
     * @param string $foreignField is 'uid' by default. Use typical database field spelling like "my_field". Only needed for generic sub statistics
     * @return bool
     */
    public function elevateStatistic(QueueMail $queueMail, QueueRecipient $queueRecipient, $action, AbstractEntity $entityRelation = null, $foreignField = 'uid')
    {
        if (!in_array($action, self::AVAILABLE_STATISTIC_ACTIONS)) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('The given action name does not match (given string=%s).', $action));
            return false;
            //===
        }

        // 1. Always count "total" on any action
        $result = $this->increaseAndPersistStatistic($queueMail, 'total');

        // escape, if something went wrong
        if (!$result) {
            return $result;
            //===
        }

        // 2. manage basic statistic for queueMail
        $result = $this->increaseAndPersistStatistic($queueMail, $action);

        // escape, if something went wrong
        if (!$result) {
            return $result;
            //===
        }

        // 3. if relation is given, manage sub-statistic
        // detect the special case "Newsletter"
        if (intval($queueMail->getType()) === self::NEWSLETTER_TYPE) {
            // 2.1 if: is newsletter: Use specific function
            $result = $this->elevateStatisticOfTxRkwNewsletterTopic($queueMail, $queueRecipient, $action);
        } else if ($entityRelation instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
            // 2.2 else: manage generic sub statistic of any kind
            $this->increaseAndPersistStatistic($queueMail, $action, $entityRelation, $foreignField);
        }
        return $result ? $result : false;
    }



    /**
     * elevateNewsletterTopicStatistic
     * Specific service function to handle RkwNewsletter data by QueueRecipient without breaking dependency
     * For minor relation management you can use the function "elevateStatistic" instead with optional $entityRelation
     * -> you would have to put every Subscription (RkwNewsletter->Topic) one after another to the function
     *
     * -> returns false, if the RkwNewsletter extension is not active
     * -> returns false, if the queueMail is not a newsletter (queueMail.type = 1)
     * -> returns false, if the given action name does not match the const array
     * -> returns false, if the given action name does not exists as getter and setter methods in the model
     * take a look to your local log files, to get more information on crashing
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param string $action The field you want to count.
     * @return bool
     */
    protected function elevateStatisticOfTxRkwNewsletterTopic(QueueMail $queueMail, QueueRecipient $queueRecipient, $action)
    {
        // important: Check some essential things
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rkw_newsletter')) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Function not available without RKW Newsletter extension (queueMail uid=%s).', $queueMail->getUid()));
            return false;
            //===
        }
        if (intval($queueMail->getType()) !== self::NEWSLETTER_TYPE) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Function is created for newsletter purpose only (queueMail uid=%s).', $queueMail->getUid()));
            return false;
            //===
        }
        if (!in_array($action, self::AVAILABLE_STATISTIC_ACTIONS)) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('The given action name does not match (given string=%s).', $action));
            return false;
            //===
        }

        try {
            // get RkwNewsletter frontendUser to get access to feUsers subscriptions
            // Alternative: Explode values from a direct SQL SELECT of the subscription field ($GLOBALS['TYPO3_DB']->exec_SELECTquery)
            /** @var \RKW\RkwNewsletter\Domain\Repository\FrontendUserRepository $rkwNewsletterFrontendUserRepository */
            $rkwNewsletterFrontendUserRepository = $this->objectManager->get('RKW\\RkwNewsletter\\Domain\\Repository\\FrontendUserRepository');
            /** @var \RKW\RkwNewsletter\Domain\Model\FrontendUser $rkwNewsletterFrontendUser */
            $rkwNewsletterFrontendUser = $rkwNewsletterFrontendUserRepository->findByIdentifier($queueRecipient->getFrontendUser());

            if ($rkwNewsletterFrontendUser instanceof \RKW\RkwNewsletter\Domain\Model\FrontendUser) {
                if ($rkwNewsletterFrontendUser->getTxRkwnewsletterSubscription()->count()) {
                    /** @var \RKW\RkwNewsletter\Domain\Model\Topic $rkwNewsletterTopic */
                    foreach ($rkwNewsletterFrontendUser->getTxRkwnewsletterSubscription() as $rkwNewsletterTopic) {
                        $this->increaseAndPersistStatistic($queueMail, $action, $rkwNewsletterTopic);
                    }
                    return true;
                    //===
                } else {
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('The queueMail with UID %s is of type newsletter. But the recipient (frontendUser with ID %s) does not have any subscriptions (RkwNewsletter->Topic)', $queueMail->getUid(), $rkwNewsletterFrontendUser));
                }
            }

        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An error occurred while trying to access and process RKW Newsletter topic data for QueueMail with UID %s: %s', $queueMail->getUid(), $e));
        }

        return true;
        //===
    }



    /**
     * increaseAndPersistStatistic
     * -> returns false, if the given action name does not match the const array
     * -> returns false, if the given action name does not exists as getter and setter methods in the model
     * take a look to your local log files, to get more information on crashing
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @param string $action The field you want to count.
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $entityRelation
     * @param string $foreignField is 'uid' by default. Use typical database field spelling like "my_field"
     * @return bool
     */
    protected function increaseAndPersistStatistic(QueueMail $queueMail, $action, AbstractEntity $entityRelation = null, $foreignField = 'uid')
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper */
        $dataMapper = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);

        try {
            // a) Either: find statistic by queueMail and relation (e.g. topic)
            if ($entityRelation instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
                // is entity based statistic in relation to queueMail
                $statisticSent = $this->statisticSentRepository->findOneByQueueMailAndRelation($queueMail, $entityRelation, $foreignField);
            } else {
                // is basic statistic
                $statisticSent = $this->statisticSentRepository->findOneBasicByQueueMail($queueMail);
            }

            // b) Or: Create, if not exists
            if (!$statisticSent instanceof \RKW\RkwMailer\Domain\Model\StatisticSent) {
                /** @var \RKW\RkwMailer\Domain\Model\StatisticSent $statisticSent */
                $statisticSent = $this->objectManager->get('RKW\\RkwMailer\\Domain\\Model\\StatisticSent');
                $statisticSent->setQueueMail($queueMail);

                // additional data, if is entity based statistic in relation to queueMail
                if ($entityRelation instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
                    $statisticSent->setForeignUid($entityRelation->getUid());
                    $statisticSent->setForeignTable(filter_var($dataMapper->getDataMap(get_class($entityRelation))->getTableName()));
                    $statisticSent->setForeignField($foreignField);
                }
            }

            // do changes
            $setter = 'set' . ucfirst($action);
            $getter = 'get' . ucfirst($action);

            if (
                !method_exists($statisticSent, $setter)
                || !method_exists($statisticSent, $getter)
            ) {
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('The given action name is matching, but the setter and / or getter method does not exist in model (given string=%s).', $action));
                return false;
                //===
            } else {
                // do the magic: Increase the count of the given action
                $statisticSent->$setter($statisticSent->$getter() + 1);
            }

            if ($statisticSent->_isNew()) {
                $this->statisticSentRepository->add($statisticSent);
            } else {
                $this->statisticSentRepository->update($statisticSent);
            }

            return true;
            //===
        } catch (\Exception $e) {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, sprintf('An error occurred while trying to update statistics of queueMail with UID %s with following error message: %s', $queueMail->getUid(), $e));
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