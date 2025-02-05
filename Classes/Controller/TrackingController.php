<?php

namespace RKW\RkwMailer\Controller;

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
 * TrackingController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TrackingController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    
    /**
     * ClickTracker
     *
     * @var \RKW\RkwMailer\Tracking\ClickTracker
     * @inject
     */
    protected $clickTracker;


    /**
     * OpeningTracker
     *
     * @var \RKW\RkwMailer\Tracking\OpeningTracker
     * @inject
     */
    protected $openingTracker;


    /**
     * action redirect
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function redirectAction()
    {
        $parameters = $this->request->getArguments();
        $hash = preg_replace('/[^a-zA-Z0-9]/', '', $parameters['hash']);
        $trackingUrl = filter_var($parameters['url'], FILTER_SANITIZE_URL);
        $queueMailId = intval($parameters['mid']);
        $queueMailRecipientId = intval($parameters['uid']);

        // try to get the tracking-url via old version with hash
        if ($hash) {
            $trackingUrl = $this->clickTracker->getPlainUrlByHash($hash);
        } 
        
        // track the given url
        $this->clickTracker->track($queueMailId, $trackingUrl);

        // get the redirect-url with all relevant parameters
        if ($url = $this->clickTracker->getRedirectUrl($trackingUrl, $queueMailId, $queueMailRecipientId)) {

            // if no delay is set, redirect directly
            if (!intval($this->settings['redirectDelay'])) {
                /** @toDo currently not working with subscription-edit redirect to mein.rkw.de - don't know why! */
                // $this->redirectToUri($url); 
                // exit();
            }

            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'linkController.message.redirect_wait', 'rkw_mailer'
                )
            );

            $this->view->assignMultiple(
                array(
                    'redirectUrl'     => $url,
                    'redirectTimeout' => intval($this->settings['redirectDelay']) * 1000,
                )
            );

            return;
        }

        $this->addFlashMessage(
            \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'linkController.error.redirect_not_possible', 'rkw_mailer'
            ),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
        );

    }


    /**
     * action opening
     * count unique mail openings via tracking pixel
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function openingAction()
    {
        
        $parameters = $this->request->getArguments();
        $queueMailId = intval($parameters['mid']);
        $queueRecipientId = intval($parameters['uid']);

        // track
        $this->openingTracker->track($queueMailId, $queueRecipientId);

        // return gif-data
        $name = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:rkw_mailer/Resources/Public/Images/spacer.gif');
        header("Content-Type: image/gif");
        header("Content-Length: " . filesize($name));
        readfile($name);

        exit();
    }
    
}