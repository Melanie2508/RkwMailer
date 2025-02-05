<?php
namespace RKW\RkwMailer\Tests\Integration\Utility;


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
use RKW\RkwBasics\Domain\Repository\PagesRepository;
use RKW\RkwMailer\Utility\QueueRecipientUtility;
use RKW\RkwMailer\Domain\Model\QueueRecipient;
use RKW\RkwRegistration\Domain\Model\Title;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use RKW\RkwRegistration\Domain\Model\FrontendUser as FrontendUserRkw;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use RKW\RkwRegistration\Domain\Model\BackendUser as BackendUserRkw;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * QueueRecipientUtilityTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueueRecipientUtilityTest extends FunctionalTestCase
{


    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/QueueRecipientUtilityTest/Fixtures';

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_mailer',
        'typo3conf/ext/rkw_registration',
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [ ];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager;

    
    /**
     * @var \RKW\RkwMailer\Utility\QueueRecipientUtility
     */
    private $subject;

    /**
     * @var \RKW\RkwBasics\Domain\Repository\PagesRepository
     */
    private $pagesRepository;

    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {
       
        parent::setUp();

        $this->importDataSet(self::FIXTURE_PATH .  '/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:rkw_basics/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_registration/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        
        $this->subject = GeneralUtility::makeInstance(QueueRecipientUtility::class);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->pagesRepository = $this->objectManager->get(PagesRepository::class);

    }


    //=============================================

    /**
     * @test
     * @throws \Exception
     */
    public function initQueueRecipientReturnsQueueRecipientObject()
    {
        /**
         * Scenario:
         *
         * When the method is called
         * Then a QueueRecipient-object is returned
         */

        $result = $this->subject->initQueueRecipient();
        self::assertInstanceOf(QueueRecipient::class, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function initQueueRecipientGivenCoreFrontendUserSetsEmailViaUsername()
    {
        /**
         * Scenario:
         *
         * Given an Extbase-FrontendUser-object
         * Given that FrontendUser-Object has only a username-property set
         * Given that username is a valid e-mail-address
         * When the method is called
         * Then the QueueRecipient-object is returned
         * Then the email-property of this object is set to the value of the username-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUser();

        $additionalData = [];
        $frontendUser->setUsername('lauterbach@spd.de');
        $expected->setEmail('lauterbach@spd.de');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);        
    }

    /**
     * @test
     * @throws \Exception
     */
    public function initQueueRecipientGivenCoreFrontendUserDoesNotSetEmailViaUsername()
    {
        /**
         * Scenario:
         *
         * Given an Extbase-FrontendUser-Object
         * Given that FrontendUser-Object has only a username-property set
         * Given that username is not a valid e-mail-address
         * When the method is called
         * Then the QueueRecipient-object is returned
         * Then the email-property of this object is not set to the value of the username-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUser();

        $additionalData = [];
        $frontendUser->setUsername('lauterbach');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreFrontendUserDoesNotOverrideEmailViaUsername()
    {
        /**
         * Scenario:
         *
         * Given an Extbase-FrontendUser-Object
         * Given that FrontendUser-Object has a username-property set
         * Given that username is a valid email-address
         * Given that FrontendUser-Object has an email-address set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUser();

        $additionalData = [];
        $frontendUser->setUsername('testen@test.de');
        $frontendUser->setEmail('lauterbach@spd.de');
        $expected->setEmail('lauterbach@spd.de');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreFrontendUserDoesFullMapping()
    {
        /**
         * Scenario:
         *
         * Given an Extbase-FrontendUser-Object
         * Given that FrontendUser-Object has a username-property set
         * Given that username is a valid email-address
         * Given that FrontendUser-Object has the email-property set
         * Given that FrontendUser-Object has the title-property set
         * Given that FrontendUser-Object has the firstName-property set
         * Given that FrontendUser-Object has the lastName-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the FrontendUser-Object
         * Then the title-property of this object is set to the value of the title-property of the FrontendUser-Object
         * Then the firstName-property of this object is set to the value of the firstName-property of the FrontendUser-Object
         * Then the lastName-property of this object is set to the value of the lastName-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUser();

        $additionalData = [];
        $frontendUser->setUsername('testen@test.de');
        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setTitle('Prof.');
        $frontendUser->setFirstName('Karl');
        $frontendUser->setLastName('Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setTitle('Prof.');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenRkwFrontendUserDoesFullMapping()
    {
        /**
         * Scenario:
         *
         * Given a RkwRegistration-FrontendUser-Object
         * Given that FrontendUser-Object has a username-property set
         * Given that username is a valid email-address
         * Given that FrontendUser-Object has the email-property set
         * Given that FrontendUser-Object has the title-property set
         * Given that FrontendUser-Object has the firstName-property set
         * Given that FrontendUser-Object has the lastName-property set
         * Given that FrontendUser-Object has the txRkwregistrationTitle-property set
         * Given that FrontendUser-Object has the txRkwregistrationGender-property set
         * Given that FrontendUser-Object has the txRkwregistrationLanguageKey-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the FrontendUser-Object
         * Then the title-property of this object is set to the value of the  txRkwregistrationTitle-property of the FrontendUser-Object
         * Then the firstName-property of this object is set to the value of the firstName-property of the FrontendUser-Object
         * Then the lastName-property of this object is set to the value of the lastName-property of the FrontendUser-Object
         * Then the salutation-property of this object is set to the value of the txRkwregistrationGender-property of the FrontendUser-Object
         * Then the languageCode-property of this object is set to the value of the txRkwregistrationLanguageKey-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUserRkw();
        $title = new Title();

        $title->setName('Dr.');

        $additionalData = [];
        $frontendUser->setUsername('testen@test.de');
        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setTitle('Prof.');
        $frontendUser->setFirstName('Karl');
        $frontendUser->setLastName('Lauterbach');
        $frontendUser->setTxRkwregistrationTitle($title);
        $frontendUser->setTxRkwregistrationGender(0);
        $frontendUser->setTxRkwregistrationLanguageKey('fr');
        
        $expected->setEmail('lauterbach@spd.de');
        $expected->setTitle('Dr.');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');
        $expected->setSalutation(0);
        $expected->setLanguageCode('fr');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }


    /**
     * @test
     */
    public function initQueueRecipientGivenCoreFrontendUserUsesAdditionalDataAsFallback()
    {
        /**
         * Scenario:
         * 
         * Given an additionalData-Array
         * Given that additionalData-Array has an username-key set
         * Given that username is a valid email-address 
         * Given that additionalData-Array has an email-key set
         * Given that additionalData-Array has a title-key set
         * Given that additionalData-Array has a firstName-key set
         * Given that additionalData-Array has a lastName-key set
         * Given an Extbase-FrontendUser-Object
         * Given that FrontendUser-Object has the email-property set
         * Given that FrontendUser-Object has the lastName-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the FrontendUser-Object
         * Then the title-property of this object is set to the value of the title-key of the additionalData-Array
         * Then the firstName-property of this object is set to the value of the firstName-key of the additionalData-Array
         * Then the lastName-property of this object is set to the value of the lastName-property of the FrontendUser-Object
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUserRkw();
        $additionalData = [
            'username' => 'testen2@test.de',
            'email' => 'merkel@cdu.de',
            'title' => 'Dr.',
            'firstName' => 'Angela',
            'lastName' => 'Merkel',
        ];

        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setLastName('Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setTitle('Dr.');
        $expected->setFirstName('Angela');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenRkwFrontendUserUsesAdditionalDataAsFallback()
    {
        /**
         * Scenario:
         *
         * Given an additionalData-Array
         * Given that additionalData-Array has an username-key set
         * Given that username is a valid email-address
         * Given that additionalData-Array has an email-key set
         * Given that additionalData-Array has the title-key set
         * Given that additionalData-Array has a firstName-key set
         * Given that additionalData-Array has a lastName-key set
         * Given that additionalData-Array has the txRkwregistrationGender-key set
         * Given that additionalData-Array has the txRkwregistrationLanguageKey-key set 
         * Given an RkwRegistration-FrontendUser-Object
         * Given that FrontendUser-Object has the email-property set
         * Given that FrontendUser-Object has the lastName-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the FrontendUser-Object
         * Then the title-property of this object is set to the value of the  txRkwregistrationTitle-key of the additionalData-Array
         * Then the firstName-property of this object is set to the value of the firstName-key of the additionalData-Array
         * Then the lastName-property of this object is set to the value of the lastName-property of the FrontendUser-Object
         * Then the salutation-property of this object is set to the value of the txRkwregistrationGender-key of the additionalData-Array
         * Then the languageCode-property of this object is set to the value of the txRkwregistrationLanguageKey-key of the additionalData-Array 
         */
        $expected = new QueueRecipient();
        $frontendUser = new FrontendUserRkw();
        $title = new Title();
        $title->setName('Prof.');

        $additionalData = [
            'username' => 'testen2@test.de',
            'email' => 'merkel@cdu.de',
            'title' => 'Dr.',
            'firstName' => 'Angela',
            'lastName' => 'Merkel',
            'txRkwregistrationGender' => 1,
            'txRkwregistrationLanguageKey' => 'fr'
        ];

        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setLastName('Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setTitle('Dr.');
        $expected->setFirstName('Angela');
        $expected->setLastName('Lauterbach');
        $expected->setSalutation(1);
        $expected->setLanguageCode('fr');
        
        $result = $this->subject::initQueueRecipient($frontendUser, $additionalData);
        self::assertEquals($expected, $result);
    }
    
    //=============================================

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreBackendUserSetsEmail()
    {
        /**
         * Scenario:
         *
         * Given a BackendUser-Object
         * Given that BackendUser-Object has the email-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the BackendUser-Object
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUser();

        $additionalData = [];
        $backendUser->setEmail('lauterbach@spd.de');
        
        $expected->setEmail('lauterbach@spd.de');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreBackendUserSetsNameFromOneWordRealName()
    {
        /**
         * Scenario:
         *
         * Given a BackendUser-Object
         * Given that BackendUser-Object has the email-property set
         * Given that BackendUser-Object has the realName-property set
         * Given that realName-value consists of one word
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the BackendUser-Object
         * Then the lastName-property of this object is set to the value of the realName-property of the BackendUser-Object
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUser();

        $additionalData = [];
        $backendUser->setEmail('lauterbach@spd.de');
        $backendUser->setRealName('Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }


    /**
     * @test
     */
    public function initQueueRecipientGivenCoreBackendUserSetsNameFromTwoWordRealName()
    {
        /**
         * Scenario:
         *
         * Given a BackendUser-Object
         * Given that BackendUser-Object has the email-property set
         * Given that BackendUser-Object has the realName-property set
         * Given that realName-value consists of two words
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the BackendUser-Object
         * Then the firstName-property of this object is set to the first part of the value of the realName-property of the BackendUser-Object
         * Then the lastName-property of this object is set to the second part of the value of the realName-property of the BackendUser-Object
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUser();

        $additionalData = [];
        $backendUser->setEmail('lauterbach@spd.de');
        $backendUser->setRealName('Karl Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreBackendUserSetsNameFromThreeWordRealName()
    {
        /**
         * Scenario:
         *
         * Given a BackendUser-Object
         * Given that BackendUser-Object has the email-property set
         * Given that BackendUser-Object has the realName-property set
         * Given that realName-value consists of three words
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-property of the BackendUser-Object
         * Then the title-property of this object is set to the first part of the value of the realName-property of the BackendUser-Object
         * Then the firstName-property of this object is set to the second part of the value of the realName-property of the BackendUser-Object
         * Then the lastName-property of this object is set to the third part of the value of the realName-property of the BackendUser-Object
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUser();

        $additionalData = [];
        $backendUser->setEmail('lauterbach@spd.de');
        $backendUser->setRealName('Prof. Karl Lauterbach');

        $expected->setEmail('lauterbach@spd.de');
        $expected->setTitle('Prof.');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenCoreBackendUserUsesAdditionalDataAsFallback()
    {
        /**
         * Scenario:
         *
         * Given an additionalData-Array
         * Given that additionalData-Array has an email-key set
         * Given that additionalData-Array has a realName-key set
         * Given that additionalData-Array has a lang-key set
         * Given a Core BackendUser-Object
         * Given that BackendUser-Object has the realName-property set
         * Given that realName-value consists of three words
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-key of the additionalData-Array
         * Then the title-property of this object is set to the first part of the value of the realName-property of the BackendUser-Object
         * Then the firstName-property of this object is set to the second part of the value of the realName-property of the BackendUser-Object
         * Then the lastName-property of this object is set to the third part of the value of the realName-property of the BackendUser-Object
         * Then the languageCode-property of this object is set to the value of the languageCode-key of the additionalData-Array
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUser();

        $additionalData = [
            'realName'      => 'Dr. Angela Merkel',
            'email'         => 'merkel@cdu.de',
        ];
        $backendUser->setRealName('Prof. Karl Lauterbach');

        $expected->setEmail('merkel@cdu.de');
        $expected->setTitle('Prof.');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function initQueueRecipientGivenRkwBackendUserUsesAdditionalDataAsFallback()
    {
        /**
         * Scenario:
         *
         * Given an additionalData-Array
         * Given that additionalData-Array has an email-key set
         * Given that additionalData-Array has a realName-key set
         * Given that additionalData-Array has a lang-key set
         * Given a RkwRegistration-BackendUser-Object
         * Given that BackendUser-Object has the realName-property set
         * Given that realName-value consists of three words
         * Given that BackendUser-Object has the lang-property set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-key of the additionalData-Array
         * Then the title-property of this object is set to the first part of the value of the realName-property of the BackendUser-Object
         * Then the firstName-property of this object is set to the second part of the value of the realName-property of the BackendUser-Object
         * Then the lastName-property of this object is set to the third part of the value of the realName-property of the BackendUser-Object
         * Then the languageCode-property of this object is set to the value of the lang-property of the BackendUser-Object
         */
        $expected = new QueueRecipient();
        $backendUser = new BackendUserRkw();

        $additionalData = [
            'realName'      => 'Dr. Angela Merkel',
            'email'         => 'merkel@cdu.de',
            'lang'          => 'it'
        ];
        $backendUser->setRealName('Prof. Karl Lauterbach');
        $backendUser->setLang('ru');
        
        $expected->setEmail('merkel@cdu.de');
        $expected->setTitle('Prof.');
        $expected->setFirstName('Karl');
        $expected->setLastName('Lauterbach');
        $expected->setLanguageCode('ru');

        $result = $this->subject::initQueueRecipient($backendUser, $additionalData);
        self::assertEquals($expected, $result);
    }


    /**
     * @test
     */
    public function initQueueRecipientGivenArraySetsDefaultData()
    {
        /**
         * Scenario:
         *
         * Given a basicData-Array
         * Given that basicData-Array has an email-key set
         * Given that basicData-Array has a title-key set
         * Given that basicData-Array has a salutation-key set
         * Given that basicData-Array has a firstName-key set
         * Given an additionalData-Array
         * Given that additionalData-Array has a firstName-key set
         * Given that additionalData-Array has a lastName-key set
         * Given that additionalData-Array has a languageCode-key set
         * When the method is called
         * Then the queueRecipient-object is returned
         * Then the email-property of this object is set to the value of the email-key of the basicData-Array
         * Then the title-property of this object is set to the value of the title-key of the basicData-Array
         * Then the salutation-property of this object is set to the value of the salutation-key of the basicData-Array
         * Then the firstName-property of this object is set to the value of the firstName-key of the basicData-Array
         * Then the lastName-property of this object is set to the value of the lastName-key of the additionalData-Array
         * Then the languageCode-property of this object is set to the value of the languageCode-key of the additionalData-Array
         */
        $expected = new QueueRecipient();

        $basicData = [
            'email' => 'merkel@cdu.de',
            'title' => 'Dr.',
            'salutation' => 1,
            'firstName' => 'Angela',
        ];
        
        $additionalData = [
            'firstName' => 'Dorothea',
            'lastName' => 'Bär',
            'languageCode' => 'ru'
        ];
        
        $expected->setEmail('merkel@cdu.de');
        $expected->setTitle('Dr.');
        $expected->setFirstName('Angela');
        $expected->setLastName('Bär');
        $expected->setSalutation(1);
        $expected->setLanguageCode('ru');

        $result = $this->subject::initQueueRecipient($basicData, $additionalData);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function initQueueRecipientIgnoresMarkerIfNotAnArray()
    {
        /**
         * Scenario:
         *
         * Given an additionalData-array with a marker-key as string
         * When the method is called
         * Then a QueueRecipient-object is returned
         * Then the marker-property is not set
         */

        $result = $this->subject->initQueueRecipient([], ['marker' => 'test']);
        self::assertInstanceOf(QueueRecipient::class, $result);
        self::assertEmpty($result->getMarker());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function initQueueRecipientAddsAndImplodesMarker()
    {
        /**
         * Scenario:
         *
         * Given an additionalData-array with a marker-key as array
         * Given this marker-key contains an array with two keys
         * Given the value of first key of the marker-array is a persisted object in the database
         * Given the value of second key of the marker-array is a string
         * When the method is called
         * Then a QueueRecipient-object is returned
         * Then the marker-property contains two keys
         * Then the value of the first key reduced to object-placeholders consisting of namespace and the uid
         * Then the value of the second key is a string
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \RKW\RkwBasics\Domain\Model\Pages $entityOne */
        $entityOne = $this->pagesRepository->findByIdentifier(1);

        $initMarker = [
            'test1' => $entityOne,
            'test2' => 'A string is string'
        ];

        $expected = [
            'test1' => 'RKW_MAILER_NAMESPACES RKW\RkwBasics\Domain\Model\Pages:1',
            'test2' => 'A string is string',
        ];
        
        $result = $this->subject->initQueueRecipient([], ['marker' => $initMarker]);
        self::assertInstanceOf(QueueRecipient::class, $result);

        $marker = $result->getMarker();
        self::assertCount(2, $marker);
        self::assertEquals($expected['test1'], $marker['test1']);
        self::assertEquals($expected['test2'], $marker['test2']);

    }
    
    /**
     * TearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

}