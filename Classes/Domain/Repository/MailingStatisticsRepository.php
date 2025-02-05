<?php

namespace RKW\RkwMailer\Domain\Repository;
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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * MailingStatisticsRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MailingStatisticsRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * initializeObject
     */
    public function initializeObject()
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }
    
    
    /**
     * findByTstampFavSendingAndType
     *
     * @param int $fromTime
     * @param int $toTime
     * @param int $type
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @toDo: write tests
     */
    public function findByTstampFavSendingAndType(
        int $fromTime,
        int $toTime,
        int $type = -1
    ) {

        $query = $this->createQuery();
        $constraints = [
            $query->greaterThanOrEqual('status', 3)
        ];

        if ($type > -1) {
            $constraints[] = $query->equals('type', $type);
        }

        if ($fromTime) {
            $constraints[] = $query->greaterThanOrEqual('tstampFavSending', $fromTime);
        }

        if ($toTime) {
            $constraints[] = $query->lessThanOrEqual('tstampFavSending', $toTime);
        }

        $query->matching(
            $query->logicalAnd($constraints)
        );

        $query->setOrderings(
            array(
                'status' => QueryInterface::ORDER_ASCENDING,
                'tstampFavSending' => QueryInterface::ORDER_DESCENDING,
                'tstampRealSending' => QueryInterface::ORDER_DESCENDING,
            )
        );

        return $query->execute();
    }
    
    

    /**
     * deleteByQueueMail
     * We use a straight-forward approach here because it may be a lot of data to delete!
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @return int
     * @comment implicitly tested
     */
    public function deleteByQueueMail(
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
    ): int {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rkwmailer_domain_model_mailingstatistics');

        return $queryBuilder
            ->delete('tx_rkwmailer_domain_model_mailingstatistics')
            ->where(
                $queryBuilder->expr()->eq(
                    'queue_mail',
                    $queryBuilder->createNamedParameter($queueMail->getUid(), \PDO::PARAM_INT))
            )
            ->execute();

    }
}