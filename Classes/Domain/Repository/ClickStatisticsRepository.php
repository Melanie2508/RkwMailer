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
 * ClickStatisticsRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ClickStatisticsRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /** @var array $defaultOrderings */
    protected $defaultOrderings = [
        'counter' => QueryInterface::ORDER_DESCENDING,
        'url' => QueryInterface::ORDER_ASCENDING,
    ];
    
    
    /**
     * initializeObject
     */
    public function initializeObject()
    {
        $this->defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
        
    }


    /**
     * findOneByHashAndQueueMail
     *
     * @param \RKW\RkwMailer\Domain\Model\queueMail $queueMail
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @return \RKW\RkwMailer\Domain\Model\ClickStatistics
     * @comment implicitly tested
     */
    public function findOneByHashAndQueueMail(
        string $hash,
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
    ) {

        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('hash', $hash),
                $query->equals('queueMail', intval($queueMail->getUid()))
            )
        );

        return $query->execute()->getFirst();
    }


    /**
     * deleteByQueueMail
     * We use a straight-forward approach here because it may be a lot of data to delete!
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @return int
     */
    public function deleteByQueueMail(
        \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
    ): int {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rkwmailer_domain_model_clickstatistics');

        return $queryBuilder
            ->delete('tx_rkwmailer_domain_model_clickstatistics')
            ->where(
                $queryBuilder->expr()->eq(
                    'queue_mail',
                    $queryBuilder->createNamedParameter($queueMail->getUid(), \PDO::PARAM_INT))
            )
            ->execute();

    }
}