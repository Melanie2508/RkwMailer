<?php
namespace RKW\RkwMailer\Tests\Functional\Utility;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use RKW\RkwMailer\Utility\StatisticSentUtility;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwMailer\Domain\Repository\QueueRecipientRepository;
use RKW\RkwMailer\Domain\Repository\LinkRepository;
use RKW\RkwMailer\Domain\Repository\StatisticSentRepository;
use RKW\RkwNewsletter\Domain\Repository\TopicRepository;
use RKW\RkwNewsletter\Domain\Repository\FrontendUserRepository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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
 * StatisticSentUtilityTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatisticSentUtilityTest extends FunctionalTestCase
{

    /**
     * Signal name
     *
     * @const string
     */
    const NUMBER_OF_STATISTIC_OPENINGS = 3;

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
     * @var \RKW\RkwMailer\Utility\StatisticSentUtility
     */
    private $subject = null;

    /**
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     */
    private $queueMailRepository;

    /**
     * @var \RKW\RkwMailer\Domain\Repository\QueueRecipientRepository
     */
    private $queueRecipientRepository;

    /**
     * @var \RKW\RkwMailer\Domain\Repository\LinkRepository
     */
    private $linkRepository;

    /**
     * @var \RKW\RkwMailer\Domain\Repository\StatisticSentRepository
     */
    private $statisticSentRepository;

    /**
     * @var \RKW\RkwNewsletter\Domain\Repository\TopicRepository
     */
    private $topicRepository;

    /**
     * @var \RKW\RkwNewsletter\Domain\Repository\FrontendUserRepository
     */
    private $frontendUserRepository;

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

        $this->importDataSet(__DIR__ . '/Fixtures/Database/FeUsers.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/Pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/QueueMail.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/QueueRecipient.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/Link.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/StatisticSent.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/Database/TxRkwnewsletterTopic.xml');

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
        $this->queueRecipientRepository = $this->objectManager->get(QueueRecipientRepository::class);
        $this->linkRepository = $this->objectManager->get(LinkRepository::class);
        $this->statisticSentRepository = $this->objectManager->get(StatisticSentRepository::class);
        $this->topicRepository = $this->objectManager->get(TopicRepository::class);
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        $this->subject = $this->objectManager->get(StatisticSentUtility::class);

    }


    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailNotNewsletterAndQueueRecipientAndExistingActionAndExistingSubStatistic_ReturnsTrue()
    {
        // not a newsletter!
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(1);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        // get topic for creating sub statistic (because this queueMail ist NOT a newsletter, this topic is equivalent
        // to any ordinary generic AbstractEntity
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(1);

        // get existing action by random
        $randIndex = array_rand(StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS);
        $action = StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS[$randIndex];

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, $action, $topic);

        static::assertTrue($result);
    }



    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailIsNewsletterAndQueueRecipientAndExistingActionAndExistingSubStatistic_ReturnsTrue()
    {
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(3);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        // get topic for creating sub statistic
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(1);

        // Workaround start
        // Problem: can't build relation between queueRecipient, FrontendUser and Topic, because the feUser method is named txRkwnewsletterSubscription
        /** @var \RKW\RkwNewsletter\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(1);
        $frontendUser->addTxRkwnewsletterSubscription($topic);
        $queueRecipient->setFrontendUser($frontendUser);
        // Workaround end

        // get existing action by random
        $randIndex = array_rand(StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS);
        $action = StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS[$randIndex];

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, $action, $topic);

        static::assertTrue($result);
    }


    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailNotNewsletterAndQueueRecipientAndExistingActionAndNotExistingSubStatistic_ReturnsTrue()
    {
        // is not a newsletter!
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(4);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        // get topic for creating sub statistic
        // this topic has no existing statistic in relation to the queue mail and will be created
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(3);

        // Workaround start
        // Problem: can't build relation between queueRecipient, FrontendUser and Topic, because the feUser method is named txRkwnewsletterSubscription
        /** @var \RKW\RkwNewsletter\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(1);
        $frontendUser->addTxRkwnewsletterSubscription($topic);
        $queueRecipient->setFrontendUser($frontendUser);
        // Workaround end

        // get existing action by random
        $randIndex = array_rand(StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS);
        $action = StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS[$randIndex];

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, $action, $topic);

        static::assertTrue($result);
    }


    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailIsNewsletterAndQueueRecipientAndExistingActionAndNotExistingSubStatistic_ReturnsTrue()
    {
        // is newsletter!
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(3);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        // get topic for creating sub statistic
        // this topic has no existing statistic in relation to the queue mail and will be created
        /** @var \RKW\RkwNewsletter\Domain\Model\Topic $topic */
        $topic = $this->topicRepository->findByIdentifier(3);

        // Workaround start
        // Problem: can't build relation between queueRecipient, FrontendUser and Topic, because the feUser method is named txRkwnewsletterSubscription
        /** @var \RKW\RkwNewsletter\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(1);
        $frontendUser->addTxRkwnewsletterSubscription($topic);
        $queueRecipient->setFrontendUser($frontendUser);
        // Workaround end

        // get existing action by random
        $randIndex = array_rand(StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS);
        $action = StatisticSentUtility::AVAILABLE_STATISTIC_ACTIONS[$randIndex];

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, $action, $topic);

        static::assertTrue($result);
    }


    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailAndQueueRecipientAndFaultyAction_ReturnsFalse()
    {
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(3);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        $action = 'Das Leben ist schön.';

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, $action);

        static::assertFalse($result);
    }


    /**
     * @test
     */
    public function elevateStatistic_GivenQueueMailAndQueueRecipientAndTestAllAvailableActions_ReturnsTrue()
    {
        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findByIdentifier(3);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = $this->queueRecipientRepository->findByIdentifier(1);

        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'sent');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'successful');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'failed');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'deferred');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'bounced');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'opened');
        static::assertTrue($result);
        $result = $this->subject->elevateStatistic($queueMail, $queueRecipient, 'clicked');
        static::assertTrue($result);
    }



    /**
     * TearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}