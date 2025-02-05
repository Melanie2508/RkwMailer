<?php
namespace RKW\RkwMailer\Tests\Integration\ViewHelpers\Frontend\Uri;

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
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * TypolinkViewHelperTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TypolinkViewHelperTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/TypolinkViewHelperTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/rkw_basics',
        'typo3conf/ext/rkw_mailer',
        'typo3conf/ext/realurl'
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [ ];

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    private $standAloneViewHelper;

  
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private $objectManager;


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp()
    {

        // define realUrl-config
        define('TX_REALURL_AUTOCONF_FILE', 'typo3conf/ext/rkw_mailer/Tests/Integration/ViewHelpers/Frontend/TypolinkViewHelperTest/Fixtures/RealUrlConfiguration.php');

        parent::setUp();

        $this->importDataSet(__DIR__ . '/TypolinkViewHelperTest/Fixtures/Database/Global.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:realurl/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_basics/Configuration/TypoScript/setup.typoscript',
                'EXT:rkw_mailer/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->standAloneViewHelper = $this->objectManager->get(StandaloneView::class);
        $this->standAloneViewHelper->setTemplateRootPaths(
            [
                0 => self::FIXTURE_PATH . '/Frontend/Templates'
            ]
        );


    }


    /**
     * @test
     * @throws \Exception
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function itRendersLinks ()
    {

        /**
         * Scenario:
         *
         * Given the ViewHelper is used in a template
         * Given a typolink to an external page with target, class and title
         * Given a typolink to an external page with anchor, target, class and title
         * Given a typolink to an email with target, class and title, using the old typolink-style
         * Given a typolink to an email with target, class and title, using the new typolink-style
         * Given a typolink to an internal page with target, class and title, using the old typolink-style
         * Given a typolink to an internal page with target, class and title, using the new typolink-style
         * Given a typolink to an internal page with anchor, target, class and title, using the old typolink-style
         * Given a typolink to an internal page with anchor, target, class and title, using the new typolink-style
         * Given a typolink to an existing file with anchor, target, class and title            
         * When the ViewHelper is rendered
         * Then the typolink to an external page with target, class and title is returned as absolute link with target, class and title
         * Then the typolink to an external page with anchor, target, class and title is returned as absolute link with anchor, target, class and title
         * Then the typolink to an email with target, class and title, using the old typolink-style is returned as email-link with target, class and title
         * Then the typolink to an email with target, class and title, using the new typolink-style is returned as email-link with target, class and title
         * Then the typolink to an internal page with target, class and title, using the old typolink-style is returned as absolute link with target, class and title
         * Then the typolink to an internal page with target, class and title, using the new typolink-style is returned as absolute link with target, class and title
         * Then the typolink to an internal page with anchor, target, class and title, using the old typolink-style is returned as absolute link with anchor, target, class and title
         * Then the typolink to an internal page with anchor, target, class and title, using the new typolink-style is returned as absolute link with anchor, target, class and title
         * Then the typolink to an existing file with target, class and title is returned as absolute link with target, class and title
         */
        $this->standAloneViewHelper->setTemplate('Check10.html');

        $expected = file_get_contents(__DIR__ . '/TypolinkViewHelperTest/Fixtures/Expected/Check10.txt');
        $result = $this->standAloneViewHelper->render();

        self::assertEquals($expected, $result);
    }


    //=============================================

    /**
     * TearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }








}