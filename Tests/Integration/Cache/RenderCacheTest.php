<?php
namespace RKW\RkwMailer\Tests\Integration\Cache;

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
use RKW\RkwMailer\Cache\RenderCache;
use RKW\RkwMailer\Domain\Model\QueueMail;
use RKW\RkwMailer\Domain\Model\QueueRecipient;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwMailer\Domain\Repository\QueueRecipientRepository;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * RenderCacheTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderCacheTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/RenderCacheTest/Fixtures';

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
     * @var \RKW\RkwMailer\Cache\RenderCache
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
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {

        parent::setUp();

        $this->importDataSet(static::FIXTURE_PATH . '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_basics/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.typoscript',
                static::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $this->objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueMailRepository = $this->objectManager->get(QueueMailRepository::class);
        $this->subject = $this->objectManager->get(RenderCache::class);
        $this->subject->clearCache();

    }
    
    
    //=============================================
    
    /**
     * @test
     * @throws \Exception
     */
    public function securityCheckWritesDirectoryProtection()
    {

        /**
         * Scenario:
         *
         * Given the cache is configured as SimpleFileBackend
         * When the method is called
         * Then true is returned
         * Then the htaccess-file is written to the cache dir
         * Then the nginx-file is written to the cache dir
         */

        $this->subject= $this->objectManager->get(MailCache::class, SimpleFileBackend::class);
        $cacheDir = $this->subject->getCache()->getBackend()->getCacheDirectory();
        self::assertTrue($this->subject->securityCheck());
        
        self::assertFileExists($cacheDir . '.htaccess');
        self::assertFileExists($cacheDir . 'conf.nginx');

    }

    //=============================================
    /**
     * @test
     * @throws \Exception
     */
    public function replaceMarkersReplacesMarkersWithValues()
    {

        /**
         * Scenario:
         *
         * Given a ---markerOne--- in the string
         * Given a ###markerTwo### in the string
         * Given a marker-array with the real values
         * When the method is called
         * Then the two markers are replaced by their corresponding values
         */

        $string = '---markerOne--- is ###markerTwo### time of the most bullshit in the week.';
        $expected = 'Monday is mostly the time of the most bullshit in the week.';

        $markers = [
            'markerOne' => 'Monday',
            'markerTwo' => 'mostly the',
        ];        
        
        $result = $this->subject->replaceMarkers($string, $markers);
        self::assertEquals($expected, $result);
    }

    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function getIdentifierUsingNonPersistentQueueMailThrowsException()
    {

        /**
         * Scenario:
         *
         * Given a non-persisted queueMail-object
         * When the method is called
         * Then an exception is thrown
         * Then the code of the exception is 1634648093
         */

        static::expectException(\RKW\RkwMailer\Exception::class);
        static::expectExceptionCode(1634648093);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = GeneralUtility::makeInstance(QueueMail::class);
        $this->subject->getIdentifier($queueMail, 'Abc');

    }

    /**
     * @test
     * @throws \Exception
     */
    public function getIdentifierUsingPersistedQueueMailReturnsIdentifierForPlaintext()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueMail-object
         * Given isPlaintext with the value true
         * Given an additional string
         * When method is called
         * Then a string is returned
         * Then the string begins with prefix "ViewHelperCache"
         * Then the string contains the uid of the queueMail 
         * Then the string contains the keyword "plaintext"
         * Then the string ends with a sha1-key based on the additional string
         */

        $this->importDataSet(static::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findbyUid(10);

        $result = $this->subject->getIdentifier($queueMail, true, 'test');
        self::assertStringStartsWith('ViewHelperCache', $result);
        self::assertContains('_10_', $result);
        self::assertContains('_plaintext_', $result);
        self::assertStringEndsWith(sha1('test'), $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getIdentifierUsingPersistedQueueMailReturnsIdentifierForHtml()
    {

        /**
         * Scenario:
         *
         * Given a persisted queueMail-object
         * Given isPlaintext with the value false
         * Given an additional string
         * When method is called
         * Then a string is returned
         * Then the string begins with prefix "ViewHelperCache"
         * Then the string contains the uid of the queueMail
         * Then the string contains the keyword "html"
         * Then the string ends with a sha1-key based on the additional string
         */

        $this->importDataSet(static::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findbyUid(10);

        $result = $this->subject->getIdentifier($queueMail, false, 'test');
        self::assertStringStartsWith('ViewHelperCache', $result);
        self::assertContains('_10_', $result);
        self::assertContains('_html_', $result);
        self::assertStringEndsWith(sha1('test'), $result);
    }

    //=============================================
    /**
     * @test
     * @throws \Exception
     */
    public function getContentUsingSameIdentifierReturnsSameString()
    {

        /**
         * Scenario:
         *
         * Given a valid identifier
         * Given setContent has been called with the same identifier before
         * When the method is called
         * Then the string that has been used for setContent is returned
         */
        $this->importDataSet(static::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findbyUid(10);

        $identifier = $this->subject->getIdentifier($queueMail, false, 'test');
        $this->subject->setContent($identifier, 'Abc');

        self::assertEquals('Abc', $this->subject->getContent($identifier));

    }

    /**
     * @test
     * @throws \Exception
     */
    public function getContentUsingDifferentIdentifierReturnsEmptyString()
    {

        /**
         * Scenario:
         *
         * Given a valid identifier
         * Given setContent has been called with the another identifier before
         * When the method is called
         * Then empty is returned
         */
        $this->importDataSet(static::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->queueMailRepository->findbyUid(10);

        $identifier = $this->subject->getIdentifier($queueMail, false, 'test');
        $this->subject->setContent($identifier, 'Abc');

        $identifier = $this->subject->getIdentifier($queueMail, true, 'test');
        self::assertEmpty($this->subject->getContent($identifier));

    }

    //=============================================
    /**
     * TearDown
     */
    protected function tearDown()
    {
        $this->subject->clearCache();
        parent::tearDown();
    }








}