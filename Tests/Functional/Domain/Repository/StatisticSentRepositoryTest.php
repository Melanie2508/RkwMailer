<?php
namespace RKW\RkwMailer\Tests\Functional\Domain\Repository;


use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use RKW\RkwMailer\Domain\Repository\StatisticSentRepository;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwNewsletter\Domain\Repository\TopicRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
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
 * StatisticSentRepositoryTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatisticSentRepositoryTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_registration',
        'typo3conf/ext/rkw_mailer',
        'typo3conf/ext/rkw_newsletter',
    ];
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [];

    /**
     * @var \RKW\RkwMailer\Domain\Repository\StatisticSentRepository
     */
    private $subject = null;

    /**
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     */
    private $queueMailRepository;

    /**
     * @var \RKW\RkwNewsletter\Domain\Repository\TopicRepository
     */
    private $topicRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    private $persistenceManager = null;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */

    private $objectManager = null;
    
    
    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSentRepository/Pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSentRepository/QueueMail.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSentRepository/StatisticSent.xml');
        //$this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSentRepository/Link.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSentRepository/TxRkwnewsletterTopic.xml');

        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_newsletter/Configuration/TypoScript/setup.txt',
                'EXT:rkw_registration/Configuration/TypoScript/setup.txt',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.txt',
                'EXT:rkw_mailer/Tests/Functional/Utility/Fixtures/Frontend/Configuration/Rootpage.typoscript',
            ]
        );
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueMailRepository = $this->objectManager->get(QueueMailRepository::class);
        $this->topicRepository = $this->objectManager->get(TopicRepository::class);
        $this->subject = $this->objectManager->get(StatisticSentRepository::class);
    }


    /**
     * @test
     */
    public function findOneBasicByQueueMail_GivenQueueMail_IgnoresForeignUid()
    {

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(1);

        /** @var \RKW\RkwMailer\Domain\Model\StatisticSent $result */
        $result = $this->subject->findOneBasicByQueueMail($queueMail);

        static::assertInstanceOf('\RKW\RkwMailer\Domain\Model\StatisticSent', $result);
    }


    /**
     * @test
     */
    public function findOneSubByQueueMailAndRelation_GivenQueueMailAndAbstractEntityAndFieldName_RespectForeignUid()
    {

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(1);

        // principle the function works with every kind of AbstractEntity
        // in it's initial use case it's a RkwNewsletter Topic
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(1);
        static::assertInstanceOf('\RKW\RkwNewsletter\Domain\Model\Topic', $topic);

        /** @var \RKW\RkwMailer\Domain\Model\StatisticSent $result */
        $result = $this->subject->findOneByQueueMailAndRelation($queueMail, $topic);

        static::assertInstanceOf('\RKW\RkwMailer\Domain\Model\StatisticSent', $result);
    }


    /**
     * @test
     */
    public function findAllRelationsByQueueMail_GivenQueueMail_ReturnsAllSubStatisticsOfQueueMail()
    {
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(1);

        /** @var \RKW\RkwMailer\Domain\Model\StatisticSent $result */
        $result = $this->subject->findAllRelationsByQueueMail($queueMail);

        // In StatisticSent exists 4 entries with QueueMail UID 1. But only two of them are sub-statistics
        // Hint: Two are kind of "tx_rkwnewsletter_domain_model_topic". One is "tx_rkwbasics_domain_model_department"
        static::assertCount(3, $result);
    }


    /**
     * @test
     */
    public function findAllRelationsByQueueMail_GivenQueueMailAndRelation_ReturnsSpecificSubStatisticsOfQueueMail()
    {
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(1);

        // principle the function works with every kind of AbstractEntity
        // in it's initial use case it's a RkwNewsletter Topic
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(1);
        static::assertInstanceOf('\RKW\RkwNewsletter\Domain\Model\Topic', $topic);

        /** @var \RKW\RkwMailer\Domain\Model\StatisticSent $result */
        $result = $this->subject->findAllRelationsByQueueMail($queueMail, $topic);

        // In StatisticSent exists 4 entries with QueueMail UID 1. But only two of them are sub-statistics
        // Hint: Two are kind of "tx_rkwnewsletter_domain_model_topic". One is "tx_rkwbasics_domain_model_department"
        static::assertCount(2, $result);
    }


    /**
     * TearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}