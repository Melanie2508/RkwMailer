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
 * LinkController
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LinkController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * QueueMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueMailRepository
     * @inject
     */
    protected $queueMailRepository;

    /**
     * QueueRecipientRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\QueueRecipientRepository
     * @inject
     */
    protected $queueRecipientRepository;

    /**
     * LinkRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\LinkRepository
     * @inject
     */
    protected $linkRepository;

    /**
     * statisticOpeningRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\StatisticOpeningRepository
     * @inject
     */
    protected $statisticOpeningRepository;

    /**
     * StatisticsUtility
     *
     * @var \RKW\RkwMailer\Utility\StatisticsUtility
     * @inject
     */
    protected $statisticsUtility;

    /**
     * StatisticSentUtility
     *
     * @var \RKW\RkwMailer\Utility\StatisticSentUtility
     * @inject
     */
    protected $statisticSentUtility;

    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


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

        $request = $this->request->getArguments();
        $hash = preg_replace('/[^a-zA-Z0-9]/', '', $request['hash']);
        $queueMailId = intval($request['mid']);
        $queueMailRecipientId = intval($request['uid']);


        if ($url = $this->statisticsUtility->getRedirectLink($hash, $queueMailId, $queueMailRecipientId)) {

            // if no delay is set, redirect directly
            if (!intval($this->settings['redirectDelay'])) {
                $this->redirectToUri($url);

                return;
                //===
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
            //===

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
     * action confirmation
     * count unique mail openings (pixel)
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function confirmationAction()
    {

        $request = $this->request->getArguments();
        $mailId = intval($request['mid']);
        $userId = intval($request['uid']);

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        if (
            ($mailId)
            && ($userId)
            && ($queueMail = $this->queueMailRepository->findByUid($mailId))
            && ($queueRecipient = $this->queueRecipientRepository->findByUid($userId))
        ) {

            // get statistics if already created
            /** @var \RKW\RkwMailer\Domain\Model\StatisticOpening $statisticOpening */
            $statisticOpening = $this->statisticOpeningRepository->findOneByQueueMailAndQueueRecipientAndPixel($queueMail, $queueRecipient);
            if (!$statisticOpening) {

                $statisticOpening = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Domain\\Model\\StatisticOpening');
                $statisticOpening->setPixel(1);
                $statisticOpening->setClickCount($statisticOpening->getClickCount() + 1);
                $statisticOpening->setQueueMail($queueMail);
                $statisticOpening->setQueueRecipient($queueRecipient);

                $this->statisticOpeningRepository->add($statisticOpening);
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Adding new statisticOpening for opening (queueMail uid=%s, queueRecipient uid=%s).', $queueMail->getUid(), $queueRecipient->getUid()));

                // Hint: "Pixel" meant "opened"
                // StatisticSent: ALTERNATIVE EVALUATION
                $this->statisticSentUtility->elevateStatistic($queueMail, $queueRecipient, 'opened');

            } else {
                $statisticOpening->setClickCount($statisticOpening->getClickCount() + 1);
                $this->statisticOpeningRepository->update($statisticOpening);
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Updating statisticOpening uid=%s for opening (queueMail uid=%s, queueRecipient uid=%s).', $statisticOpening->getUid(), $queueMail->getUid(), $queueRecipient->getUid()));
            }

            // Count click in both cases
            // StatisticSent: ALTERNATIVE EVALUATION
            $this->statisticSentUtility->elevateStatistic($queueMail, $queueRecipient, 'click');

            $this->persistenceManager->persistAll();
        }

        // return gif-data
        $name = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:rkw_mailer/Resources/Public/Images/spacer.gif');
        header("Content-Type: image/gif");
        header("Content-Length: " . filesize($name));
        readfile($name);

        exit();
        //===
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
        }

        return $this->logger;
        //===
    }
}