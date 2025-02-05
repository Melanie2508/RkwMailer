<?php

namespace RKW\RkwMailer\ViewHelpers\Frontend\Uri;

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

use RKW\RkwMailer\UriBuilder\FrontendUriBuilder;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class ActionViewHelper
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ActionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Uri\ActionViewHelper
{

    /**
     * The output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;
    
    
    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
       
        $this->registerArgument('queueMail', '\RKW\RkwMailer\Domain\Model\QueueMail', 'QueueMail-object for redirecting links');
        $this->registerArgument('queueRecipient', '\RKW\RkwMailer\Domain\Model\QueueRecipient', 'QueueRecipient-object of email');

    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $pageUid = $arguments['pageUid'];
        $pageType = $arguments['pageType'];
        $noCache = $arguments['noCache'];
        $noCacheHash = $arguments['noCacheHash'];
        $section = $arguments['section'];
        $format = $arguments['format'];
        $linkAccessRestrictedPages = $arguments['linkAccessRestrictedPages'];
        $additionalParams = $arguments['additionalParams'];
        // $absolute = $arguments['absolute'];
        $addQueryString = $arguments['addQueryString'];
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'];
        $addQueryStringMethod = $arguments['addQueryStringMethod'];
        $queueMail = $arguments['queueMail'];
        $queueRecipient = $arguments['queueRecipient'];
        $action = $arguments['action'];
        $controller = $arguments['controller'];
        $extensionName = $arguments['extensionName'];
        $pluginName = $arguments['pluginName'];
        $arguments = $arguments['arguments'];

        try {
            
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            
            /** @var \RKW\RkwMailer\UriBuilder\FrontendUriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get(FrontendUriBuilder::class);
            
            $uriBuilder
                ->reset()
                ->setTargetPageUid($pageUid)
                ->setTargetPageType($pageType)
                ->setNoCache($noCache)
                ->setUseCacheHash(!$noCacheHash)
                ->setSection($section)
                ->setFormat($format)
                ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
                ->setArguments($additionalParams)
                ->setCreateAbsoluteUri(true)// force absolute link
                ->setAddQueryString($addQueryString)
                ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
                ->setAddQueryStringMethod($addQueryStringMethod);
    
            if ($queueMail) {
                $uriBuilder->setUseRedirectLink(true)
                    ->setQueueMail($queueMail);
                
                if ($queueRecipient) {
                    $uriBuilder->setQueueRecipient($queueRecipient);
                }                    
            }
    
            return $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName);

        } catch (\Exception $e) {

            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->log(
                LogLevel::ERROR, 
                sprintf(
                    'Error while trying to set link: %s', 
                    $e->getMessage()
                )
            );
        }
        
        return '';
    }
    
}