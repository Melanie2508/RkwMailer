<?php
namespace RKW\RkwMailer\Tests\Integration\Mail;

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

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use RKW\RkwMailer\Cache\MailCache;
use RKW\RkwMailer\Domain\Model\MailingStatistics;
use RKW\RkwMailer\Domain\Model\QueueMail;
use RKW\RkwMailer\Domain\Model\QueueRecipient;
use RKW\RkwMailer\Domain\Repository\MailingStatisticsRepository;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwMailer\Domain\Repository\QueueRecipientRepository;
use RKW\RkwMailer\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * MailerTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MailerTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/MailerTest/Fixtures';

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_mailer'
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [ ];


    /**
     * @var \RKW\RkwMailer\Mail\Mailer
     */
    private $subject;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager;

    
    /**
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     */
    private $queueMailRepository;

    
    /**
     * @var \RKW\RkwMailer\Domain\Repository\QueueRecipientRepository
     */
    private $queueRecipientRepository;

    
    /**
     * @var \RKW\RkwMailer\Domain\Repository\MailingStatisticsRepository
     */
    private $mailingStatisticsRepository;    

    
    /**
     * @var \RKW\RkwMailer\Cache\MailCache
     */
    private $mailCache;

    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {

        parent::setUp();

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_basics/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueMailRepository = $this->objectManager->get(QueueMailRepository::class);
        $this->queueRecipientRepository = $this->objectManager->get(QueueRecipientRepository::class);
        $this->mailingStatisticsRepository = $this->objectManager->get(MailingStatisticsRepository::class);
        $this->mailCache = $this->objectManager->get(MailCache::class);
        $this->subject = $this->objectManager->get(Mailer::class);

        $this->mailCache->clearCache();

    }
    

    //=============================================


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsOnlyProcessesQueueMailsWithStatusWaitingAndSending()
    {

        /**
         * Scenario:
         *
         * Given five queueMail-objects in database
         * Given one of the queueMail-objects has the status draft
         * Given one of the queueMail-objects has the status finished
         * Given one of the queueMail-objects has the status error
         * Given one of the queueMail-objects has the status waiting
         * Given one of the queueMail-objects has the status sending
         * When the method is called
         * Then two queueMail-objects are returned
         * Then the first object has the uid of the one with status waiting 
         * Then the second object has the uid of the one with status sending
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');
        
        $result = $this->subject->processQueueMails();
        
        self::assertCount(2, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);
        self::assertInstanceOf(QueueMail::class, $result[1]);

        self::assertEquals(83, $result[0]->getUid());
        self::assertEquals(84, $result[1]->getUid());
        
    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsSendsHigherPriorityFirst()
    {

        /**
         * Scenario:
         *
         * Given three queueMail-objects in database
         * Given all three queueMail-objects have the status waiting
         * Given the first of the queueMail-objects has priority 3 
         * Given the second of the queueMail-objects has priority 2
         * Given the first of the queueMail-objects has priority 1
         * When the method is called
         * Then three queueMail-objects are returned
         * Then the first object has the uid of the one with priority 1
         * Then the second object has the uid of the one with priority 2
         * Then the third object has the uid of the one with priority 3
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        $result = $this->subject->processQueueMails();

        self::assertCount(3, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);
        self::assertInstanceOf(QueueMail::class, $result[1]);
        self::assertInstanceOf(QueueMail::class, $result[2]);

        self::assertEquals(92, $result[0]->getUid());
        self::assertEquals(91, $result[1]->getUid());
        self::assertEquals(90, $result[2]->getUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsSendsPipelinedMailsLast()
    {

        /**
         * Scenario:
         *
         * Given two queueMail-objects in database
         * Given both queueMail-objects have the status waiting
         * Given the first of the queueMail-objects has the pipeline-property set to 1
         * Given the second of the queueMail-objects has the pipeline-property set to 0
         * When the method is called
         * Then two queueMail-objects are returned
         * Then the first object has the uid of the one with pipeline-property set to 0
         * Then the second object has the uid of the one with pipeline-property set to 1
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        $result = $this->subject->processQueueMails();

        self::assertCount(2, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);
        self::assertInstanceOf(QueueMail::class, $result[1]);

        self::assertEquals(101, $result[0]->getUid());
        self::assertEquals(100, $result[1]->getUid());
    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsSendsOldestMailsFirst()
    {

        /**
         * Scenario:
         *
         * Given two queueMail-objects in database
         * Given both queueMail-objects have the status sending
         * Given the first of the queueMail-objects has been processed recently and thus has a younger tstampRealSending-value
         * Given the second of the queueMail-objects has not been processed recently and thus has an older tstampRealSending-value
         * When the method is called
         * Then two queueMail-objects are returned
         * Then the first object has the uid of the one with the older tstampRealSending-value
         * Then the second object has the uid of the one with the younger tstampRealSending-value
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        $result = $this->subject->processQueueMails();

        self::assertCount(2, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);
        self::assertInstanceOf(QueueMail::class, $result[1]);

        self::assertEquals(111, $result[0]->getUid());
        self::assertEquals(110, $result[1]->getUid());
    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsValidatesQueueMailObject()
    {

        /**
         * Scenario:
         *
         * Given three queueMail-objects in database
         * Given all three queueMail-objects have the status waiting
         * Given first of the queueMail-objects has no fromName-property set, but the fromAddress-property
         * Given second of the queueMail-objects has no fromAddress-property set, but the fromName-property
         * Given third of the queueMail-objects has no subject-property set, but the fromAddress-property and the fromName-property
         * When the method is called
         * Then three queueMail-objects are returned
         * Then the first object has the uid of the one with the missing fromName-property
         * Then this object has the status 99
         * Then the corresponding mailingStatistics-object of this object has the status 99
         * Then the status change of this object is persisted
         * Then the second object has the uid of the one with the missing fromAddress-property
         * Then this object has the status 99
         * Then the corresponding mailingStatistics-object of this object has the status 99
         * Then the status change of this object is persisted
         * Then the third object has the uid of the one with the missing subject-property
         * Then this object has the status 4 
         * Then the corresponding mailingStatistics-object of this object has the status 99
         * Then the status change of this object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check120.xml');

        $result = $this->subject->processQueueMails();

        self::assertCount(3, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);
        self::assertInstanceOf(QueueMail::class, $result[1]);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailOne */
        $queueMailOne = $result[0];
        self::assertEquals(120, $queueMailOne->getUid());
        self::assertEquals(99, $queueMailOne->getStatus());
        self::assertEquals(99, $queueMailOne->getMailingStatistics()->getStatus());
        
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDbOne = $this->queueMailRepository->findByIdentifier(120);
        self::assertEquals(99, $queueMailDbOne->getStatus());
        self::assertEquals(99, $queueMailDbOne->getMailingStatistics()->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailTwo */
        $queueMailTwo = $result[1];
        self::assertEquals(121, $queueMailTwo->getUid());
        self::assertEquals(99, $queueMailTwo->getStatus());
        self::assertEquals(99, $queueMailTwo->getMailingStatistics()->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDbTwo = $this->queueMailRepository->findByIdentifier(121);
        self::assertEquals(99, $queueMailDbTwo->getStatus());
        self::assertEquals(99, $queueMailDbTwo->getMailingStatistics()->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailThree */
        $queueMailThree = $result[2];
        self::assertEquals(122, $queueMailThree->getUid());
        self::assertEquals(4, $queueMailThree->getStatus());
        self::assertEquals(4, $queueMailThree->getMailingStatistics()->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDbThree = $this->queueMailRepository->findByIdentifier(122);
        self::assertEquals(4, $queueMailDbThree->getStatus());
        self::assertEquals(4, $queueMailDbThree->getMailingStatistics()->getStatus());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsSetsSettingsPidIfEmpty()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has the status waiting
         * Given this queueMail-object has no settingsPid-property set
         * Given a settingsPid is given as parameter
         * When the method is called
         * Then one queueMail-object is returned
         * Then the given settingsPid-parameter is set as settingsPid-property of the queueMail-Object
         * Then this value for the settingsPid-property is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check130.xml');

        $result = $this->subject->processQueueMails(5, 5, 9999);

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);

        self::assertEquals(9999, $queueMail->getSettingsPid());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDb = $this->queueMailRepository->findByIdentifier(130);
        self::assertEquals(9999, $queueMailDb->getSettingsPid());
    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsDoesNotSetSettingsPidIfNotEmpty()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has the status waiting
         * Given this queueMail-object has a settingsPid-property set
         * Given a settingsPid is given as parameter
         * When the method is called
         * Then one queueMail-object is returned
         * Then the settingsPid-property of the queueMail-Object is kept unchanged
         * Then no change on the settingsPid-property is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check140.xml');

        $result = $this->subject->processQueueMails(5, 5, 9999);
        
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);

        self::assertEquals(140, $queueMail->getSettingsPid());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDb = $this->queueMailRepository->findByIdentifier(140);
        self::assertEquals($queueMail->getSettingsPid(), $queueMailDb->getSettingsPid());
    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsRespectsEmailsPerJobParameter()
    {

        /**
         * Scenario:
         *
         * Given three queueMail-objects in the database
         * Given all three queueMail-objects have the status waiting
         * Given the value for emailsPerJob given as parameter is set to one
         * When the method is called
         * Then one queueMail-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check150.xml');

        $result = $this->subject->processQueueMails(1);

        self::assertCount(1, $result);
        self::assertInstanceOf(QueueMail::class, $result[0]);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsMigratesMailStatistics()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status sending
         * Given this queueMail-object has not yet a mailingStatistic-object linked
         * Given this queueMail-object has the three timestamps for the sending set
         * Given this queueMail-object has more than one queueRecipient
         * When the method is called
         * Then one queueMail-object is returned
         * Then this queueMail-object has an mailingStatistic-object linked
         * Then this linked mailingStatistic-object has the tstampFavSending-property set according to the value in the queueMail-object
         * Then this linked mailingStatistic-object has the tstampRealSending-property set according to the value in the queueMail-object
         * Then this linked mailingStatistic-object has the tstampFinishedSending-property set to zero according to the value in the queueMail-object
         * Then this linked mailingStatistic-object has the queueMail-object set it belongs to
         * Then this linked mailingStatistic-object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check160.xml');

        $result = $this->subject->processQueueMails();

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);
        
        /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatistics */
        $mailingStatistics = $result[0]->getMailingStatistics();
        self::assertInstanceOf(MailingStatistics::class,  $mailingStatistics);

        self::assertEquals($queueMail->getTstampFavSending(), $mailingStatistics->getTstampFavSending());
        self::assertEquals($queueMail->getTstampRealSending(), $mailingStatistics->getTstampRealSending());
        self::assertEquals($queueMail->getTstampSendFinish(), $mailingStatistics->getTstampFinishedSending());
        
        self::assertEquals($queueMail->getUid(), $mailingStatistics->getQueueMail()->getUid());
        
        $dbResult = $this->mailingStatisticsRepository->findAll();
        self::assertCount(1, $dbResult);
        self::assertSame($mailingStatistics, $dbResult->getFirst());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsKeepsExistingMailStatistics()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status sending
         * Given this queueMail-object has a mailingStatistic-object linked
         * Given this queueMail-object has the three timestamps for the sending set
         * Given this queueMail-object has more than one queueRecipient
         * When the method is called
         * Then one queueMail-object is returned
         * Then this queueMail-object has an mailingStatistic-object linked
         * Then this linked mailingStatistic-object has not been changed
         * Then no changes on the linked mailingStatistic-object are persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check180.xml');

        /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatistics */
        $mailingStatistics = $this->mailingStatisticsRepository->findAll()->getFirst();
        
        // remove linkage
        $mailingStatistics = unserialize(serialize($mailingStatistics));
        
        $result = $this->subject->processQueueMails();
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);
        
        self::assertInstanceOf(MailingStatistics::class,  $result[0]->getMailingStatistics());
        self::assertEquals($mailingStatistics, $result[0]->getMailingStatistics());

        /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatisticsDb */
        $mailingStatisticsDb = $this->mailingStatisticsRepository->findByIdentifier(180);
        self::assertEquals($mailingStatistics, $mailingStatisticsDb);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsSetsSendingTimesForMailStatistics()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status waiting
         * Given this queueMail-object has more than one queueRecipient
         * Given this queueMail-object has a mailingStatistic-object linked
         * When the method is called
         * Then one queueMail-object is returned
         * Then this queueMail-object has an mailingStatistic-object linked
         * Then this linked mailingStatistic-object has the value of the tstampRealSending-property set to the current time
         * Then this linked mailingStatistic-object has the value of the tstampFinsihedSending-property set to zero
         * Then this linked mailingStatistic-object is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check190.xml');

        $timeMin = time();
        $result = $this->subject->processQueueMails();
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);

        /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatistics */
        $mailingStatistics = $result[0]->getMailingStatistics();
        self::assertInstanceOf(MailingStatistics::class, $mailingStatistics);
        self::assertGreaterThanOrEqual($timeMin, $mailingStatistics->getTstampRealSending());
        self::assertEquals(0, $mailingStatistics->getTstampFinishedSending());

        /** @var \RKW\RkwMailer\Domain\Model\MailingStatistics $mailingStatisticsDb */
        $mailingStatisticsDb = $this->mailingStatisticsRepository->findByIdentifier(190);
        self::assertSame($mailingStatistics, $mailingStatisticsDb);

    }

    
    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsUpdatesStatusToSending()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status waiting
         * Given this queueMail-object has only one queueRecipient
         * When the method is called
         * Then one queueMail-object is returned
         * Then this queueMail-object has the status sending
         * Then the corresponding mailingStatistics-object has the status sending
         * Then this status-change is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check200.xml');

        $result = $this->subject->processQueueMails();
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);
        self::assertEquals(3, $queueMail->getStatus());
        self::assertEquals(3, $queueMail->getMailingStatistics()->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDb = $this->queueMailRepository->findByIdentifier(200);
        self::assertEquals(3, $queueMailDb->getStatus());
        self::assertEquals(3, $queueMailDb->getMailingStatistics()->getStatus());

    }
    

    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsUpdatesStatusToFinished()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status sending
         * Given this queueMail-object has no queueRecipient
         * When the method is called
         * Then one queueMail-object is returned
         * Then this queueMail-object has the status finished
         * Then the corresponding mailingStatistics-object has the status finished
         * Then this status-change is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check210.xml');

        $result = $this->subject->processQueueMails();
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);
        self::assertEquals(4, $queueMail->getStatus());
        self::assertEquals(4, $queueMail->getMailingStatistics()->getStatus());
        
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDb = $this->queueMailRepository->findByIdentifier(210);
        self::assertEquals(4, $queueMailDb->getStatus());
        self::assertEquals(4, $queueMailDb->getMailingStatistics()->getStatus());

    }



    /**
     * @test
     * @throws \Exception
     */
    public function processQueueMailsUpdatesTypeAndSubject()
    {

        /**
         * Scenario:
         *
         * Given a queueMail-objects in the database
         * Given this queueMail-object has the status waiting
         * Given this queueMail-object has only one queueRecipient
         * Given this queueMail-object has a subject
         * Given this queueMail-object has a type-value of 1
         * When the method is called
         * Then one queueMail-object is returned
         * Then the corresponding mailingStatistics-object has the subject set
         * Then the corresponding mailingStatistics-object has the type-value set
         * Then this mailingStatistics is persisted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check440.xml');

        $result = $this->subject->processQueueMails();
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $result[0];
        self::assertInstanceOf(QueueMail::class, $queueMail);
        self::assertEquals($queueMail->getSubject(), $queueMail->getMailingStatistics()->getSubject());
        self::assertEquals($queueMail->getType(), $queueMail->getMailingStatistics()->getType());

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMailDb */
        $queueMailDb = $this->queueMailRepository->findByIdentifier(440);
        self::assertEquals($queueMail->getSubject(), $queueMailDb->getMailingStatistics()->getSubject());
        self::assertEquals($queueMail->getType(), $queueMailDb->getMailingStatistics()->getType());

    }
    //=============================================


    /**
     * @test
     * @throws \Exception
     */
    public function processQueueRecipientsOnlyProcessesQueueRecipientsWithStatusWaiting()
    {

        /**
         * Scenario:
         *
         * Given a queueMail with five queueRecipient-objects in database
         * Given one of the queueRecipient-objects has the status draft
         * Given one of the queueRecipient-objects has the status finished
         * Given one of the queueRecipient-objects has the status error
         * Given one of the queueRecipient-objects has the status waiting
         * Given one of the queueMRecipient-objects has the status sending
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then thus object has the uid of the one with status waiting
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check360.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(360);
        $result = $this->subject->processQueueRecipients($queueMail);

        self::assertCount(1, $result);
        self::assertInstanceOf(QueueRecipient::class, $result[0]);

        self::assertEquals(361, $result[0]->getUid());

    }

    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsRespectsEmailsPerJobParameter()
    {

        /**
         * Scenario:
         *
         * Given a queueMail with five queueRecipient-objects in database
         * Given all of the queueRecipient-objects have the status waiting
         * Given as emailsPerInterval the value 3 is given
         * When the method is called
         * Then three queueRecipient-objects are returned
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check370.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(370);
        $result = $this->subject->processQueueRecipients($queueMail, 3);

        self::assertCount(3, $result);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsRespectsSleepParameter()
    {

        /**
         * Scenario:
         *
         * Given a queueMail with five queueRecipient-objects in database
         * Given all of the queueRecipient-objects have the status waiting
         * Given as emailsPerInterval the value 3 is given
         * Given as sleepParameter the value 3 is given
         * When the method is called
         * Then three queueRecipient-objects are returned
         * Then the process takes at least nine seconds to process
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check370.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(370);
        
        $startTime = time();
        $result = $this->subject->processQueueRecipients($queueMail, 3, 3);
        $endTime = time();
        
        self::assertCount(3, $result);
        self::assertGreaterThanOrEqual(9, $endTime - $startTime);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsSetsStatusToFinished()
    {

        /**
         * Scenario:
         *
         * Given a queueMail with one queueRecipient-object in database
         * Given this queueRecipient has the status waiting
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then this object has the status finished
         * Then this status update is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check380.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(380);
        $result = $this->subject->processQueueRecipients($queueMail);
        
        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $result[0];
        self::assertEquals(4, $queueRecipient->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientDb */
        $queueRecipientDb = $this->queueRecipientRepository->findByIdentifier(380);
        self::assertEquals(4, $queueRecipientDb->getStatus());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsSetsStatusToError()
    {

        /**
         * Scenario:
         *
         * Given a queueMail with one queueRecipient-object in database
         * Given this queueRecipient has the status waiting
         * Given this queueRecipient has no email-address set
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then this object has the status error
         * Then this status update is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check390.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(390);
        $result = $this->subject->processQueueRecipients($queueMail);

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $result[0];
        self::assertEquals(99, $queueRecipient->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientDb */
        $queueRecipientDb = $this->queueRecipientRepository->findByIdentifier(390);
        self::assertEquals(99, $queueRecipientDb->getStatus());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsSetsStatusToFinishedOnHardBounceWhenNotPipeline()
    {

        /**
         * Scenario:
         *
         * Given a queueMail which is not used as pipeline
         * Given this queueMail has one queueRecipient-object in database
         * Given this queueRecipient has the status waiting
         * Given this queueRecipient has hard-bounced three times
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then this object has the status finished
         * Then this status update is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check400.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(400);
        $result = $this->subject->processQueueRecipients($queueMail);

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $result[0];
        self::assertEquals(4, $queueRecipient->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientDb */
        $queueRecipientDb = $this->queueRecipientRepository->findByIdentifier(400);
        self::assertEquals(4, $queueRecipientDb->getStatus());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsSetsStatusToDeferredOnHardBounceWhenPipeline()
    {

        /**
         * Scenario:
         *
         * Given a queueMail which is used as pipeline
         * Given this queueMail has one queueRecipient-object in database
         * Given this queueRecipient has the status waiting
         * Given this queueRecipient has hard-bounced three times
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then this object has the status deferred
         * Then this status update is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check410.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(410);
        $result = $this->subject->processQueueRecipients($queueMail);

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $result[0];
        self::assertEquals(97, $queueRecipient->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientDb */
        $queueRecipientDb = $this->queueRecipientRepository->findByIdentifier(410);
        self::assertEquals(97, $queueRecipientDb->getStatus());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function processRecipientsSetsStatusToFinishedOnSoftBounceWhenPipeline()
    {

        /**
         * Scenario:
         *
         * Given a queueMail which is used as pipeline
         * Given this queueMail has one queueRecipient-object in database
         * Given this queueRecipient has the status waiting
         * Given this queueRecipient has soft-bounced three times
         * When the method is called
         * Then one queueRecipient-objects is returned
         * Then this object has the status finished
         * Then this status update is persisted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check420.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(420);
        $result = $this->subject->processQueueRecipients($queueMail);

        self::assertCount(1, $result);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $result[0];
        self::assertEquals(4, $queueRecipient->getStatus());

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientDb */
        $queueRecipientDb = $this->queueRecipientRepository->findByIdentifier(420);
        self::assertEquals(4, $queueRecipientDb->getStatus());
    }
    
    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyThrowsExceptionOnInvalidQueueMailObject()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given the queueMail-objects has no fromName-property set
         * Given a queueRecipient-object
         * When the method is called
         * Then an exception is thrown
         * Then the code of the exception is 1438249330
         */
        self::expectException(\RKW\RkwMailer\Exception::class);
        self::expectExceptionCode(1438249330);
        
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check220.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(220);
        $queueRecipient = new QueueRecipient();
        
        $this->subject->prepareEmailBody($queueMail, $queueRecipient);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyThrowsExceptionOnInvalidQueueRecipientObject()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given the queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object is missing the email-property
         * When the method is called
         * Then an exception is thrown
         * Then the code of the exception is 1438249330
         */
        self::expectException(\RKW\RkwMailer\Exception::class);
        self::expectExceptionCode(1552485792);

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check230.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(230);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(230);

        $this->subject->prepareEmailBody($queueMail, $queueRecipient);
    }

    

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyReturnsMailMessageObject()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * When the method is called
         * Then an mailMessage-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check240.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(240);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(240);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);
        
        self::assertInstanceOf(MailMessage::class, $result);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsTemplatesToMailMessageObject()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given this queueMail-object has all three templates set (html, plaintext, calendar)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object contains three message-parts 
         * Then the first message-part is a Swift_MimePart-object
         * Then this first object has the content-type text/html
         * Then this first object contains the content of the defined html-template
         * Then the second message-part is a Swift_MimePart-object
         * Then this second object has the content-type text/plaintext
         * Then this second object contains the content of the defined plaintext-template
         * Then the third message-part is a Swift_MimePart-object
         * Then this third object has the content-type text/calendar
         * Then this third object contains the content of the defined calendar-template
         * Then this third object contains an attachment with the filename meeting.ics
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check250.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(250);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(250);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertCount(3, $result->getChildren());

        /** @var \Swift_MimePart  $mimePartHtml */
        $mimePartHtml = $result->getChildren()[0];
        self::assertEquals(\Swift_MimePart::class, get_class($mimePartHtml));
        self::assertEquals('text/html', $mimePartHtml->getContentType());
        self::assertContains('TEST-TEMPLATE-HTML', $mimePartHtml->getBody());

        /** @var \Swift_MimePart  $mimePartPlaintext */
        $mimePartPlaintext = $result->getChildren()[1];
        self::assertEquals(\Swift_MimePart::class, get_class($mimePartPlaintext));
        self::assertContains('TEST-TEMPLATE-PLAINTEXT', $mimePartPlaintext->getBody());

        /** @var \Swift_Attachment  $mimePartCalendar */
        $mimePartCalendar = $result->getChildren()[2];
        self::assertEquals(\Swift_Attachment::class, get_class($mimePartCalendar));
        self::assertEquals('text/calendar', $mimePartCalendar->getContentType());
        self::assertContains('BEGIN:VCALENDAR', $mimePartCalendar->getBody());
        self::assertEquals('meeting.ics', $mimePartCalendar->getFilename());

    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsBodyTextAsFallback()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given this queueMail-object has no templates set 
         * Given this queueMail-object has the bodyText-property set
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object contains no message-parts
         * Then this mailMessage-object contains the content of the defined bodyText-property
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check260.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(260);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(260);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertCount(0, $result->getChildren());
        self::assertContains('Test the best', $result->getBody());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsMailHeader()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object contains the basic mail-header-information as configured in the given queueMail-object
         * Then the email-addresses in the mail-header are sanitized
         * Then this mailMessage-object has the priority-header as set in the priority-property of the given queueMail-object
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check270.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(270);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(270);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);

        self::assertEquals(['test@testen.de' => 'RKW'], $result->getFrom());
        self::assertEquals(['reply@testen.de' => 'RKW Reply'], $result->getReplyTo());
        self::assertEquals('return@testen.de', $result->getReturnPath());
        self::assertEquals(1, $result->getPriority());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsListUnsubscribeMailHeader()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given this queueMail-object has the type-property set to a value greater than zero
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object contains a list-subscribe-header
         * Then the email-address in the list-subscribe-header is sanitized
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check350.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(350);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(350);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertContains('List-Unsubscribe: <mailto:test@testen.de>', $result->getHeaders()->toString());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsSubjectByQueueRecipient()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given this queueMail-object has a subject-property set
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has the subject-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the subject set according to the subject-property of the given queueRecipient-object         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check280.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(280);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(280);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertEquals('Let us test it', $result->getSubject());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsSubjectByQueueMail()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given this queueMail-object has a subject-property set
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object no subject-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the subject set according to the subject-property of the given queueMail-object
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check290.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(290);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(290);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertEquals('Test the mail', $result->getSubject());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsToHeaderWithFullRecipientNameWithTitle()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has the firstName-property set
         * Given this queueRecipient-object has the lastName-property set
         * Given this queueRecipient-object has the title-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the to-header set as array with the email of the recipient set as key
         * Then this object has the to-header set as array with the full name and title of the recipient as value
         * Then no leading or trailing spaces are added to the name
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check300.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(300);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(300);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertInternalType('array', $result->getTo());
        self::assertEquals(['debug@rkw.de' => 'Dr. Sebastian Schmidt'], $result->getTo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsToHeaderWithFullRecipientName()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has the firstName-property set
         * Given this queueRecipient-object has the lastName-property set
         * Given this queueRecipient-object has no title-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the to-header set as array with the email of the recipient set as key
         * Then this object has the to-header set as array with the full name of the recipient as value
         * Then no leading or trailing spaces are added to the name-part
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check310.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(310);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(310);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertInternalType('array', $result->getTo());
        self::assertEquals(['debug@rkw.de' => 'Sebastian Schmidt'], $result->getTo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsToHeaderWithRecipientLastNameOnly()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has the lastName-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the to-header set as array with the email of the recipient set as key
         * Then this object has the to-header set as array with the last name of the recipient as value
         * Then no leading or trailing spaces are added to the name-part
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check320.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(320);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(320);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertInternalType('array', $result->getTo());
        self::assertEquals(['debug@rkw.de' => 'Schmidt'], $result->getTo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsToHeaderWithRecipientLastNameOnlyWithTitle()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has the lastName-property set
         * Given this queueRecipient-object has the title-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the to-header set as array with the email of the recipient set as key
         * Then this object has the to-header set as array with the last name and title of the recipient as value
         * Then no leading or trailing spaces are added to the name-part
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check340.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(340);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(340);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertInternalType('array', $result->getTo());
        self::assertEquals(['debug@rkw.de' => 'Dr. Schmidt'], $result->getTo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodySetsToHeaderWithoutRecipientName()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given this queueRecipient-object has no firstName-property set
         * Given this queueRecipient-object has no lastName-property set
         * Given this queueRecipient-object has no title-property set
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this object has the to-header set as array with the email of the recipient set as key 
         * Then this object has the to-header set as array with null set as value
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check330.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(330);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(330);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);

        self::assertInstanceOf(MailMessage::class, $result);
        self::assertInternalType('array', $result->getTo());
        self::assertEquals(['debug@rkw.de' => null], $result->getTo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyAddsAttachmentViaBlob()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given an XML-file is added as attachment via BLOB-method
         * Given as mime-type is set "text/xml"
         * Given as file-name is set "Attachment.xml"
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object has one message-part 
         * Then this message-part is a Swift_Attachment-object 
         * Then this object has the mime-type "text/xml" 
         * Then this object has file-name "Attachment.xml"
         * Then this object contains the content of the attached XML-file
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check430.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(430);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(430);
        
        $queueMail->setAttachment(file_get_contents(self::FIXTURE_PATH . '/Database/Check430.xml'));
        $queueMail->setAttachmentName('Attachment.xml');
        $queueMail->setAttachmentType('text/xml');
        
        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);
        self::assertInstanceOf(MailMessage::class, $result);
        static::assertCount(1, $result->getChildren());

        $mimePartAttachment = $result->getChildren()[0];
        static::assertEquals(\Swift_Attachment::class, get_class($mimePartAttachment));
        static::assertEquals('Attachment.xml', $mimePartAttachment->getFilename());
        static::assertEquals('text/xml', $mimePartAttachment->getContentType());
        static::assertContains('tx_rkwmailer_domain_model_queuemail', $mimePartAttachment->getBody());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyAddsAttachmentViaJson()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given an XML-file is added as attachment via JSON-method
         * Given as mime-type is set "text/xml"
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object has one message-part
         * Then this message-part is a Swift_Attachment-object
         * Then this object has the mime-type "text/xml"
         * Then this object has file-name "Check430.xml"
         * Then this object contains the content of the attached XML-file
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check430.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(430);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(430);

        $json = json_encode([
            0 => [
                'path' => self::FIXTURE_PATH . '/Database/Check430.xml',
                'type' => 'text/xml'
            ]
        ]);
        $queueMail->setAttachment($json);

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);
        self::assertInstanceOf(MailMessage::class, $result);
        static::assertCount(1, $result->getChildren());

        $mimePartAttachment = $result->getChildren()[0];
        static::assertEquals(\Swift_Attachment::class, get_class($mimePartAttachment));
        static::assertEquals('Check430.xml', $mimePartAttachment->getFilename());
        static::assertEquals('text/xml', $mimePartAttachment->getContentType());
        static::assertContains('tx_rkwmailer_domain_model_queuemail', $mimePartAttachment->getBody());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function prepareEmailBodyAddsAttachment()
    {
        /**
         * Scenario:
         *
         * Given a queueMail-object in database
         * Given this queueMail-objects has all basic values for validation set (fromAddress, fromName)
         * Given a queueRecipient-object in database
         * Given this queueRecipient-object has all basic values for validation set (email)
         * Given an XML-file is added as attachment via new standard-method
         * When the method is called
         * Then an mailMessage-object is returned
         * Then this mailMessage-object has one message-part
         * Then this message-part is a Swift_Attachment-object
         * Then this object has the mime-type "text/xml"
         * Then this object has file-name "Check430.xml"
         * Then this object contains the content of the attached XML-file
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check430.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(430);
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(430);

        $queueMail->addAttachmentPath(self::FIXTURE_PATH . '/Database/Check430.xml');

        $result = $this->subject->prepareEmailBody($queueMail, $queueRecipient);
        self::assertInstanceOf(MailMessage::class, $result);
        static::assertCount(1, $result->getChildren());

        $mimePartAttachment = $result->getChildren()[0];
        static::assertEquals(\Swift_Attachment::class, get_class($mimePartAttachment));
        static::assertEquals('Check430.xml', $mimePartAttachment->getFilename());
        static::assertEquals('text/xml', $mimePartAttachment->getContentType());
        static::assertContains('tx_rkwmailer_domain_model_queuemail', $mimePartAttachment->getBody());
    }
    
    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesThrowsExceptionIfQueueMailNotPersisted()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted queueMail-object
         * Given a persisted queueRecipient-object
         * When the method is called
         * Then an exception is thrown
         * Then the code of the exception is 1540294117
         */
        self::expectException(\RKW\RkwMailer\Exception::class);
        self::expectExceptionCode(1540294117);

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = new QueueMail();

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(10);

        $this->subject->renderTemplates($queueMail, $queueRecipient);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesThrowsExceptionIfQueueRecipientNotPersisted()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueMail-object
         * Given a non-persisted queueRecipient-object
         * When the method is called
         * Then an exception is thrown
         * Then the code of the exception is 1540294116
         */
        self::expectException(\RKW\RkwMailer\Exception::class);
        self::expectExceptionCode(1540294116);


        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(10);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = new QueueRecipient();

        $this->subject->renderTemplates($queueMail, $queueRecipient);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesWithNoTemplatesSetDoesNothing()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given a persisted queueMail-object
         * Given that queueMail-object has no templates set
         * When the method is called
         * Then no templates are rendered into the cache
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(10);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(10);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        self::assertEmpty($this->mailCache->getPlaintextBody($queueRecipient));
        self::assertEmpty($this->mailCache->getHtmlBody($queueRecipient));
        self::assertEmpty($this->mailCache->getCalendarBody($queueRecipient));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesRendersAllTemplates()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given a persisted queueMail-object
         * Given that queueMail-object has templates for all three types set
         * When the method is called
         * Then all three template-types are rendered into the cache
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(20);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(20);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        self::assertNotEmpty($this->mailCache->getPlaintextBody($queueRecipient));
        self::assertNotEmpty($this->mailCache->getHtmlBody($queueRecipient));
        self::assertNotEmpty($this->mailCache->getCalendarBody($queueRecipient));

    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesRendersHtmlTemplateWithAllMarkers()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given a persisted queueMail-object
         * Given that queueMail-object has only a html-template set
         * When the method is called
         * Then the html-template is rendered only
         * Then all markers that where stored in the queueRecipient-object are replaced
         * Then all default markers are replaced
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(30);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(30);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        self::assertEmpty($this->mailCache->getPlaintextBody($queueRecipient));
        self::assertEmpty($this->mailCache->getCalendarBody($queueRecipient));
        self::assertNotEmpty($this->mailCache->getHtmlBody($queueRecipient));

        $result = $this->mailCache->getHtmlBody($queueRecipient);
        self::assertContains('TEST-TEMPLATE-HTML', $result);
        self::assertContains('ROOTPAGE', $result);
        self::assertContains('queueMail.uid: 30', $result);
        self::assertContains('queueMail.settingsPid: 0', $result);
        self::assertContains('mailType: Html', $result);
        self::assertContains('settings.redirectPid: 9999', $result);

        self::assertContains('baseUrl: http://www.example.de', $result);
        self::assertContains('baseUrlImages: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images', $result);
        self::assertContains('baseUrlLogo: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images/logo.png', $result);

        self::assertContains('queueRecipient.uid: 30', $result);
        self::assertContains('queueRecipient.firstName: Sebastian', $result);
        self::assertContains('queueRecipient.lastName: Schmidt', $result);

        self::assertContains('test1.uid: 30', $result);
        self::assertContains('test2: Hello!', $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesRendersPlaintextTemplateWithAllMarkers()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given a persisted queueMail-object
         * Given that queueMail-object has only a plaintext-template set
         * When the method is called
         * Then the plaintext-template is rendered only
         * Then all markers that where stored in the queueRecipient-object are replaced
         * Then all default markers are replaced
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(40);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(40);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        self::assertEmpty($this->mailCache->getHtmlBody($queueRecipient));
        self::assertEmpty($this->mailCache->getCalendarBody($queueRecipient));
        self::assertNotEmpty($this->mailCache->getPlaintextBody($queueRecipient));

        $result = $this->mailCache->getPlaintextBody($queueRecipient);
        self::assertContains('TEST-TEMPLATE-PLAINTEXT', $result);
        self::assertContains('ROOTPAGE', $result);
        self::assertContains('queueMail.uid: 40', $result);
        self::assertContains('queueMail.settingsPid: 0', $result);
        self::assertContains('mailType: Plaintext', $result);
        self::assertContains('settings.redirectPid: 9999', $result);

        self::assertContains('baseUrl: http://www.example.de', $result);
        self::assertContains('baseUrlImages: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images', $result);
        self::assertContains('baseUrlLogo: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images/logo.png', $result);

        self::assertContains('queueRecipient.uid: 40', $result);
        self::assertContains('queueRecipient.firstName: Sebastian', $result);
        self::assertContains('queueRecipient.lastName: Schmidt', $result);

        self::assertContains('test1.uid: 40', $result);
        self::assertContains('test2: Hello!', $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesRendersCalendarTemplateWithAllMarkers()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given a persisted queueMail-object
         * Given that queueMail-object has only a calendar-template set
         * When the method is called
         * Then the calendar-template is rendered only
         * Then all markers that where stored in the queueRecipient-object are replaced
         * Then all default markers are replaced
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(50);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(50);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        self::assertEmpty($this->mailCache->getHtmlBody($queueRecipient));
        self::assertEmpty($this->mailCache->getPlaintextBody($queueRecipient));
        self::assertNotEmpty($this->mailCache->getCalendarBody($queueRecipient));

        $result = $this->mailCache->getCalendarBody($queueRecipient);
        self::assertContains('BEGIN:VCALENDAR', $result);
        self::assertContains('ROOTPAGE', $result);
        self::assertContains('queueMail.uid: 50', $result);
        self::assertContains('queueMail.settingsPid: 0', $result);
        self::assertContains('mailType: Calendar', $result);
        self::assertContains('settings.redirectPid: 9999', $result);

        self::assertContains('baseUrl: http://www.example.de', $result);
        self::assertContains('baseUrlImages: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images', $result);
        self::assertContains('baseUrlLogo: http://www.example.de/typo3conf/ext/rkw_mailer/Resources/Public/Images/logo.png', $result);

        self::assertContains('queueRecipient.uid: 50', $result);
        self::assertContains('queueRecipient.firstName: Sebastian', $result);
        self::assertContains('queueRecipient.lastName: Schmidt', $result);

        self::assertContains('test1.uid: 50', $result);
        self::assertContains('test2: Hello!', $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesDoesNotRenderTwiceForTheSameQueueRecipient()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given to that markers the current timestamp is added
         * Given a persisted queueMail-object
         * Given that queueMail-object has templates for all three types set
         * Given the method has already been called with the same queueRecipient
         * Given the timestamp has been changed in the markers of the queueRecipient-object after that first call
         * When the method is called a second time
         * Then all three template-types were rendered into the cache
         * Then all three template-codes returned after both calls of the method are identical for each type
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(60);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(60);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientTwo */
        $queueRecipientTwo = $this->queueRecipientRepository->findByIdentifier(60);
        $queueRecipient->setMarker(['currentTime' => time()]);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        $resultPlaintextFirst = $this->mailCache->getPlaintextBody($queueRecipient);
        $resultHtmlFirst = $this->mailCache->getHtmlBody($queueRecipient);
        $resultCalendarFirst = $this->mailCache->getCalendarBody($queueRecipient);

        $queueRecipientTwo->setMarker(['currentTime' => time() + 20000]);
        $this->subject->renderTemplates($queueMail, $queueRecipientTwo);

        $resultPlaintextSecond = $this->mailCache->getPlaintextBody($queueRecipientTwo);
        $resultHtmlSecond = $this->mailCache->getHtmlBody($queueRecipientTwo);
        $resultCalendarSecond = $this->mailCache->getCalendarBody($queueRecipientTwo);

        self::assertEquals($resultPlaintextFirst, $resultPlaintextSecond);
        self::assertEquals($resultHtmlFirst, $resultHtmlSecond);
        self::assertEquals($resultCalendarFirst, $resultCalendarSecond);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesDoesRenderTwiceForTheSameQueueRecipientWhenCacheFlushed()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given to that markers the current timestamp is added
         * Given a persisted queueMail-object
         * Given that queueMail-object has templates for all three types set
         * Given the method has already been called with the same queueRecipient
         * Given the timestamp has been changed in the markers of the queueRecipient-object after that first call
         * Given the cache has been flushed after the first call of the method
         * When the method is called a second time
         * Then all three template-types were rendered into the cache
         * Then all three template-codes returned after both calls of the method are not identical for each type
         * Then all three template-codes of the first call of the method contain the first timestamp
         * Then all three template-codes of the second call of the method contain the second timestamp
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(60);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(60);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientTwo */
        $queueRecipientTwo = $this->queueRecipientRepository->findByIdentifier(60);

        $firstTimestamp = time();
        $queueRecipient->setMarker(['currentTime' => $firstTimestamp]);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        $resultPlaintextFirst = $this->mailCache->getPlaintextBody($queueRecipient);
        $resultHtmlFirst = $this->mailCache->getHtmlBody($queueRecipient);
        $resultCalendarFirst = $this->mailCache->getCalendarBody($queueRecipient);

        $this->mailCache->clearCache();

        $secondTimestamp = time() + 20000;
        $queueRecipientTwo->setMarker(['currentTime' => $secondTimestamp]);
        $this->subject->renderTemplates($queueMail, $queueRecipientTwo);

        $resultPlaintextSecond = $this->mailCache->getPlaintextBody($queueRecipientTwo);
        $resultHtmlSecond = $this->mailCache->getHtmlBody($queueRecipientTwo);
        $resultCalendarSecond = $this->mailCache->getCalendarBody($queueRecipientTwo);

        self::assertNotEquals($resultPlaintextFirst, $resultPlaintextSecond);
        self::assertNotEquals($resultHtmlFirst, $resultHtmlSecond);
        self::assertNotEquals($resultCalendarFirst, $resultCalendarSecond);

        self::assertContains("$firstTimestamp", $resultPlaintextFirst);
        self::assertContains("$firstTimestamp", $resultHtmlFirst);
        self::assertContains("$firstTimestamp", $resultCalendarFirst);

        self::assertContains("$secondTimestamp", $resultPlaintextSecond);
        self::assertContains("$secondTimestamp", $resultHtmlSecond);
        self::assertContains("$secondTimestamp", $resultCalendarSecond);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function renderTemplatesDoesRenderTwiceForDifferentQueueRecipients()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueRecipient-object
         * Given that queueRecipient-object has the marker-property set
         * Given to that markers the current timestamp is added
         * Given a persisted queueMail-object
         * Given that queueMail-object has templates for all three types set
         * Given the method has already been called with the another queueRecipient
         * Given the timestamp has been changed in the markers of the queueRecipient-object after that first call
         * When the method is called a second time
         * Then all three template-types were rendered into the cache
         * Then all three template-codes returned after both calls of the method are not identical for each type
         * Then all three template-codes of the first call of the method contain the first timestamp
         * Then all three template-codes of the second call of the method contain the second timestamp
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(70);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(70);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipientTwo */
        $queueRecipientTwo = $this->queueRecipientRepository->findByIdentifier(71);

        $firstTimestamp = time();
        $queueRecipient->setMarker(['currentTime' => $firstTimestamp]);

        $this->subject->renderTemplates($queueMail, $queueRecipient);

        $resultPlaintextFirst = $this->mailCache->getPlaintextBody($queueRecipient);
        $resultHtmlFirst = $this->mailCache->getHtmlBody($queueRecipient);
        $resultCalendarFirst = $this->mailCache->getCalendarBody($queueRecipient);

        $secondTimestamp = time() + 20000;
        $queueRecipientTwo->setMarker(['currentTime' => $secondTimestamp]);
        $this->subject->renderTemplates($queueMail, $queueRecipientTwo);

        $resultPlaintextSecond = $this->mailCache->getPlaintextBody($queueRecipientTwo);
        $resultHtmlSecond = $this->mailCache->getHtmlBody($queueRecipientTwo);
        $resultCalendarSecond = $this->mailCache->getCalendarBody($queueRecipientTwo);

        self::assertNotEquals($resultPlaintextFirst, $resultPlaintextSecond);
        self::assertNotEquals($resultHtmlFirst, $resultHtmlSecond);
        self::assertNotEquals($resultCalendarFirst, $resultCalendarSecond);

        self::assertContains("$firstTimestamp", $resultPlaintextFirst);
        self::assertContains("$firstTimestamp", $resultHtmlFirst);
        self::assertContains("$firstTimestamp", $resultCalendarFirst);

        self::assertContains("$secondTimestamp", $resultPlaintextSecond);
        self::assertContains("$secondTimestamp", $resultHtmlSecond);
        self::assertContains("$secondTimestamp", $resultCalendarSecond);
    }

    //=============================================
    /**
     * TearDown
     */
    protected function tearDown()
    {
        $this->mailCache->clearCache();
        parent::tearDown();
    }








}