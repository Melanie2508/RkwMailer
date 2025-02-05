<?php

namespace RKW\RkwMailer\Utility;

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

use RKW\RkwBasics\Utility\FrontendSimulatorUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * FrontendTypolinkUtility
 * We can not extend the basic class here, since the methods are used as static methods and this confuses translation-handling
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendTypolinkUtility 
{

    /**
     * Returns Typolink as a-tag in frontend-context
     *
     * @param string $linkText
     * @param string $parameter
     * @param string $additionalParams
     * @param string $styles
     * @return string
     */
    public static function getTypolink (
        string $linkText, 
        string $parameter, 
        string $additionalParams = '',
        string $styles = ''
    ): string {

        // get pageUid for frontend simulation from parameter
        $pageUid = self::getPageUidFromParameter($parameter);
        
        // init frontend
        FrontendSimulatorUtility::simulateFrontendEnvironment($pageUid);

        /** @var ContentObjectRenderer $contentObject */
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $content = $contentObject->typoLink(
            $linkText,
            [
                'parameter'         => self::createTypolinkParameterFromArguments($parameter, $additionalParams),
                'forceAbsoluteUrl'  => 1, // force absolute URL
                'target'            => '_blank',
                'extTarget'         => '_blank',
                'fileTarget'        => '_blank',
                'ATagParams'        => ($styles ? 'style="' . $styles . '"' : '')
            ]
        );

        // reset frontend
        FrontendSimulatorUtility::resetFrontendEnvironment();

        return $content;
    }


    /**
     * Returns Typolink as URL in frontend-context
     *
     * @param string $parameter
     * @param string $additionalParams
     * @return string
     */
    public static function getTypolinkUrl (string $parameter, string $additionalParams = ''): string
    {

        // get pageUid for frontend simulation from parameter
        $pageUid = self::getPageUidFromParameter($parameter);
        
        // init frontend
        FrontendSimulatorUtility::simulateFrontendEnvironment($pageUid);

        /** @var ContentObjectRenderer $contentObject */
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $content = $contentObject->typoLink_URL(
            [
                'parameter'         => self::createTypolinkParameterFromArguments($parameter, $additionalParams),
                'forceAbsoluteUrl'  => 1, // force absolute URL
                'target'            => '_blank',
                'extTarget'         => '_blank',
                'fileTarget'        => '_blank',
            ]
        );

        // reset frontend
        FrontendSimulatorUtility::resetFrontendEnvironment();

        return $content;
    }



    /**
     * Sets the style-attribute for a-tags
     *
     * @param string $string
     * @param string $style
     * @return string
     */
    public static function addStyleAttribute(string $string, string $style = ''): string
    {
        if ($style) {
            if (strpos($string, 'style="') !== false) {
                $string = preg_replace_callback(
                    '/style="([^"]+)"/', 
                    function ($matches) use ($style) {
                        return 'style="' . trim($matches[1], ' ;') . '; ' . $style . '"';
                    },
                    $string
                );
            } else {
                $string = trim($string) . ' style="' . $style . '"';
            }
        }
        
        return trim($string);
    }



    /**
     * Gets pageUid from typolink-parameter for frontend-simulation
     *
     * @param string $parameter
     * @return int
     */
    public static function getPageUidFromParameter (string $parameter): int
    {
        // check for pid in parameters for getting correct domain
        if (preg_match('/^((t3:\/\/page\?uid=)?([0-9]+))/', $parameter, $matches)) {
            if ($matches[3] > 0) {
                return $matches[3];
            }
        }

        return 0;
    }


    /**
     * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
     *
     * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
     * @param string $additionalParameters
     *
     * @return string The final TypoLink string
     * @see \TYPO3\CMS\Fluid\ViewHelpers\Uri\TypolinkViewHelper
     */
    protected static function createTypolinkParameterFromArguments($parameter, $additionalParameters = '')
    {
        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);

        // Combine additionalParams
        if ($additionalParameters) {
            $typolinkConfiguration['additionalParams'] .= $additionalParameters;
        }

        return $typoLinkCodec->encode($typolinkConfiguration);
    }

}
