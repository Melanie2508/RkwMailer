<?php

namespace RKW\RkwMailer\Domain\Repository;

use \RKW\RkwMailer\Domain\Model\QueueMail;
use \TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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
 * StatisticSentRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatisticSentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function initializeObject()
    {

        $this->defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }



    /**
     * findOneBasicByQueueMail
     * fetch exactly one dataset that represents the statistic of a queueMail
     *
     * @param \RKW\RkwMailer\Domain\Model\queueMail $queueMail
     * @return \RKW\RkwMailer\Domain\Model\StatisticSent|object
     */
    public function findOneBasicByQueueMail(QueueMail $queueMail)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('queueMail', intval($queueMail->getUid())),
                $query->equals('foreignUid', 0)
            )
        );

        return $query->execute()->getFirst();
        //===
    }



    /**
     * findOneByQueueMailAndRelation
     * fetch exactly one sub-statistics of a queueMail in a certain relation
     *
     * @param \RKW\RkwMailer\Domain\Model\queueMail $queueMail
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $entityRelation
     * @param string $foreignField is 'uid' by default. Use typical database field spelling like "my_field"
     * @return \RKW\RkwMailer\Domain\Model\StatisticSent|object
     */
    public function findOneByQueueMailAndRelation(QueueMail $queueMail, AbstractEntity $entityRelation, $foreignField = 'uid')
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper */
        $dataMapper = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('queueMail', intval($queueMail->getUid())),
                $query->equals('foreignUid', intval($entityRelation->getUid())),
                $query->equals('foreignTable', filter_var($dataMapper->getDataMap(get_class($entityRelation))->getTableName(), FILTER_SANITIZE_STRING)),
                $query->equals('foreignField', strval($foreignField))
            )
        );

        return $query->execute()->getFirst();
        //===
    }



    /**
     * findAllRelationsByQueueMail
     * get all sub-statistics of a queueMail. Optional with entity to restrict the hit list
     *
     * @param \RKW\RkwMailer\Domain\Model\queueMail $queueMail
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $entityRelation
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null
     */
    public function findAllRelationsByQueueMail(QueueMail $queueMail, $entityRelation = null)
    {
        $query = $this->createQuery();
        $constraints = [];

        $constraints[] =
            $query->logicalAnd(
                $query->equals('queueMail', intval($queueMail->getUid())),
                $query->greaterThan('foreignUid', 0)
            );

        // restrict results to a specific relation
        if ($entityRelation instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {

            $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper */
            $dataMapper = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);

            $constraints[] =
                $query->logicalAnd(
                    $query->equals('foreignTable', filter_var($dataMapper->getDataMap(get_class($entityRelation))->getTableName(), FILTER_SANITIZE_STRING))
                );
        }

        return $query->matching($query->logicalAnd($constraints))->execute();
        //===
    }
}