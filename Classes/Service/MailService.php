<?php

namespace RKW\RkwMailer\Service;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use RKW\RkwMailer\Domain\Repository\QueueMailRepository;
use RKW\RkwMailer\Domain\Repository\QueueRecipientRepository;
use RKW\RkwMailer\Domain\Repository\BounceMailRepository;
use RKW\RkwMailer\Validation\QueueMailValidator;
use RKW\RkwMailer\Validation\QueueRecipientValidator;

/**
 * MailService
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MailService
{

    /**
     * Signal name
     *
     * @const string
     */
    const SIGNAL_TO_BEFORE_ATTACH = 'toBeforeAttach';

    /**
     * Signal name
     *
     * @const string
     */
    const SIGNAL_RENDER_TEMPLATE_AFTER_MARKERS = 'renderTemplateAfterMarkers';

    /**
     * Signal name
     *
     * @const string
     */
    const SIGNAL_RENDER_TEMPLATE_AFTER_RENDER = 'renderTemplateAfterRender';

    /**
     * Signal name
     *
     * @const string
     */
    const SIGNAL_SEND_TO_RECIPIENT_BEFORE_SEND = 'sendToRecipientBeforeSend';

    /**
     * Namespace Keyword
     *
     * @const string
     */
    const NAMESPACE_KEYWORD = 'RKW_MAILER_NAMESPACES';

    /**
     * Namespace Keyword
     *
     * @const string
     */
    const NAMESPACE_ARRAY_KEYWORD = 'RKW_MAILER_NAMESPACES_ARRAY';

    /**
     * Debug switch
     *
     * @const string
     */
    const DEBUG_TIME = false;


    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;


    /**
     * configurationManager
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;


    /**
     * persistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;

    /**
     * queueMail
     *
     * @var \RKW\RkwMailer\Domain\Model\QueueMail
     */
    protected $queueMail;

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
     * BounceMailRepository
     *
     * @var \RKW\RkwMailer\Domain\Repository\BounceMailRepository
     * @inject
     */
    protected $bounceMailRepository;


    /**
     * StatisticSentUtility
     *
     * @var \RKW\RkwMailer\Utility\StatisticSentUtility
     * @inject
     */
    protected $statisticSentUtility;


    /**
     * QueueMailValidator
     *
     * @var \RKW\RkwMailer\Validation\QueueMailValidator
     * @inject
     */
    protected $queueMailValidator = null;


    /**
     * QueueMailValidator
     *
     * @var \RKW\RkwMailer\Validation\QueueRecipientValidator
     * @inject
     */
    protected $queueRecipientValidator = null;

    /**
     * Signal-Slot Dispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @inject
     */
    protected $signalSlotDispatcher;


    /**
     * Logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;


    /**
     * The normal settings
     *
     * @var array
     */
    protected $settings = array();


    /**
     * Constructor
     * @param bool $unitTest
     */
    public function __construct($unitTest = false)
    {
        self::debugTime(__LINE__, __METHOD__);
        if (! $unitTest) {
            $this->initializeService();
        }

        self::debugTime(__LINE__, __METHOD__);
    }

    /**
     * function initializeService
     *
     * @return void
     */
    public function initializeService()
    {
        // set objects if they haven't been injected yet
        if (!$this->objectManager) {
            $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        }
        if (!$this->configurationManager) {
            $this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        }
        if (!$this->persistenceManager) {
            $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        }
        if (!$this->queueMailRepository) {
            $this->queueMailRepository = $this->objectManager->get(QueueMailRepository::class);
        }
        if (!$this->queueRecipientRepository) {
            $this->queueRecipientRepository = $this->objectManager->get(QueueRecipientRepository::class);
        }
        if (!$this->bounceMailRepository) {
            $this->bounceMailRepository = $this->objectManager->get(BounceMailRepository::class);
        }
        if (!$this->queueMailValidator) {
            $this->queueMailValidator = $this->objectManager->get(QueueMailValidator::class);
        }
        if (!$this->queueRecipientValidator) {
            $this->queueRecipientValidator = $this->objectManager->get(QueueRecipientValidator::class);
        }

        \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(__CLASS__ . ': Please use the ObjectManager to load this class.');
    }

    /**
     * Init and return the queueMail
     *
     * @return \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function getQueueMail()
    {
        if (!$this->queueMail instanceof \RKW\RkwMailer\Domain\Model\QueueMail) {

            /** @var \RKW\RkwMailer\Domain\Model\QueueMail queueMail */
            $this->queueMail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Domain\\Model\\QueueMail');

            // set storage pidt
            $this->queueMail->setPid(intval($this->getSettings('storagePid', 'persistence')));

            // set default tstampFavSending and crDate on now
            $this->queueMail->setTstampFavSending(time());
            $this->queueMail->setCrdate(time());

            // set status to draft
            $this->queueMail->setStatus(1);

            // add and persist
            $this->queueMailRepository->add($this->queueMail);
            $this->persistenceManager->persistAll();
        }

        return $this->queueMail;
        //===
    }

    /**
     * Sets the queueMail
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueMail $queueMail
     * @return void
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function setQueueMail(\RKW\RkwMailer\Domain\Model\QueueMail $queueMail)
    {
        // check QueueMail-object
        if (!$this->queueMailValidator->validate($queueMail)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException('Invalid or missing data in queueMail-object.', 1540186518);
            //===
        }

        if ($queueMail->_isNew()) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException('The queueMail-object has to be persisted before it can be used.', 1540193242);
            //===
        }

        $this->queueMail = $queueMail;

    }



    /**
     * Sets the recipients
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser|\TYPO3\CMS\Extbase\Domain\Model\BackendUser|array $basicData
     * @param array $additionalData
     * @param bool  $renderTemplates
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @return boolean
     */
    public function setTo($basicData, $additionalData = array(), $renderTemplates = false)
    {

        self::debugTime(__LINE__, __METHOD__);

        /** @var \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient */
        $queueRecipient = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('RKW\\RkwMailer\\Domain\\Model\\QueueRecipient');

        // if a FrontendUser is given, take it's basic values
        if ($basicData instanceof \TYPO3\CMS\Extbase\Domain\Model\FrontendUser) {

           $this->setQueueRecipientPropertiesByFrontendUser($queueRecipient, $basicData, $additionalData);

         // if a BackendUser is given, take it's basic values
        } else if ($basicData instanceof \TYPO3\CMS\Extbase\Domain\Model\BackendUser) {

            $this->setQueueRecipientPropertiesByBackendUser($queueRecipient, $basicData, $additionalData);

        // fallback with array
        } else if (is_array($basicData)) {

            $this->setQueueRecipientPropertiesByArray($queueRecipient, array_merge($basicData, $additionalData));
        }

        // set marker
        if (isset($additionalData['marker'])) {
            $queueRecipient->setMarker($this->implodeMarker($additionalData['marker']));
        }

        // Signal slot
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, self::SIGNAL_TO_BEFORE_ATTACH . ($this->getQueueMail()->getCategory() ? '_' . ucFirst($this->getQueueMail()->getCategory()) : ''), array($this->getQueueMail(), &$queueRecipient));

        if ($this->addQueueRecipient($queueRecipient)) {

            // render templates right away?
            if ($renderTemplates) {
                $this->renderTemplates($queueRecipient);
            }

            self::debugTime(__LINE__, __METHOD__);
            return true;
            //===
        }

        self::debugTime(__LINE__, __METHOD__);
        return false;
        //===
    }


    /**
     * Returns the recipients
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @depricated since 2018/10/28 use $this->queueRecipientRepository->findByQueueMail($queueMail) instead
     */
    public function getTo()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(__CLASS__ . ': GetTo() method will be removed soon. Use $this->queueRecipientRepository->findByQueueMail($queueMail) instead.');
        return $this->queueRecipientRepository->findByQueueMail($this->getQueueMail());
        //===
    }



    /**
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser
     * @param array $additionalData
     * @return void
     */
    public function setQueueRecipientPropertiesByFrontendUser (\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient, \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser, $additionalData = array())
    {

         // expand mapping for \RKW\RkwRegistration\Domain\Model\FrontendUser
        $additionalPropertyMapper = [];
        if ($frontendUser instanceof \RKW\RkwRegistration\Domain\Model\FrontendUser) {
            $additionalPropertyMapper['txRkwregistrationGender'] = 'salutation';
            $additionalPropertyMapper['txRkwregistrationLanguageKey'] = 'languageCode';
            $additionalPropertyMapper['titleText'] = 'title';
        }

        // set all relevant values according to given data
        $this->setQueueRecipientPropertiesSub($queueRecipient, $frontendUser, $additionalData, $additionalPropertyMapper);

        /* @toDo: Leeds to problems since this does an implicit update on the object
         * which may lead to persisting data before having received a confirmation via opt-in-mail!!!
         */
        if (!$frontendUser->_isNew()) {
            $queueRecipient->setFrontendUser($frontendUser);
        }
    }


    /**
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser
     * @param array $additionalData
     * @return void
     */
    public function setQueueRecipientPropertiesByBackendUser (\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient, \TYPO3\CMS\Extbase\Domain\Model\BackendUser $backendUser, $additionalData = array())
    {

        // expand mapping for \RKW\RkwRegistration\Domain\Model\BackendUser
        $additionalPropertyMapper = [];
        if ($backendUser instanceof \RKW\RkwRegistration\Domain\Model\BackendUser) {
            $additionalPropertyMapper ['lang'] = 'languageCode';
        }

        // split realName
        $nameArray = [];
        if ($backendUser->getRealName()) {
            $nameArray = explode(' ', $backendUser->getRealName());

        } else if (
            (isset($additionalData['realName']))
            && ($additionalData['realName'])
        ){
            $nameArray = explode(' ', $additionalData['realName']);
        }

        if (count($nameArray) == 2) {
            if (isset($nameArray[0])) {
                $queueRecipient->setFirstName($nameArray[0]);
            }
            if (isset($nameArray[1])) {
                $queueRecipient->setLastName($nameArray[1]);
            }

        } else if (count($nameArray) == 3) {
            if (isset($nameArray[0])) {
                $queueRecipient->setTitle($nameArray[0]);
            }
            if (isset($nameArray[1])) {
                $queueRecipient->setFirstName($nameArray[1]);
            }
            if (isset($nameArray[2])) {
                $queueRecipient->setLastName($nameArray[2]);
            }
        } else if (count($nameArray) > 0) {
            $queueRecipient->setLastName($backendUser->getRealName());
        }


        // set all relevant values according to given data
        $this->setQueueRecipientPropertiesSub($queueRecipient, $backendUser, $additionalData, $additionalPropertyMapper);

    }


    /**
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $frontendUser
     * @param array $data
     * @return void
     * @deprecated Do not use this method any more
     */
    public function setQueueRecipientPropertiesByArray (\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient, $data = array())
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(__CLASS__ . ': Support for setting values for queueRecipient via array will be removed soon. Do not use it any more.');
        $this->setQueueRecipientPropertiesSub($queueRecipient, null, $data);
    }


    /**
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $user
     * @param array $additionalPropertyMapper
     * @param array $additionalData
     */
    private function setQueueRecipientPropertiesSub (\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient, \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $user = null, $additionalData = array(), $additionalPropertyMapper = array())
    {

        // define property mapping - order is important!
        $defaultPropertyMapper = [
            'username' => 'email',
            'email' => 'email',
            'title' => 'title',
            'salutation' => 'salutation',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'subject' => 'subject',
            'languageCode' => 'languageCode'
        ];

        // add additional mappings
        $propertyMapper = array_merge($defaultPropertyMapper, $additionalPropertyMapper);

        // set all relevant values according to given data
        foreach ($propertyMapper as $propertySource => $propertyTarget) {
            $getter = 'get' . ucFirst($propertySource);
            $setter = 'set' . ucFirst($propertyTarget);

            if (method_exists($queueRecipient, $setter)) {

                // check for getter value
                if (
                    ($user instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity)
                    && (method_exists($user, $getter))
                    && (null !== $value = $user->$getter())
                    && ($value !== '') // We cannot check with empty() here, because 0 is a valid value
                    && ($value !== 99)
                ) {
                    $queueRecipient->$setter($value);

                // fallback: check for value in additional data
                } else if (
                    (isset($additionalData[$propertySource]))
                    && (null !== $value = $additionalData[$propertySource])
                    && ($value !== '') // We cannot check with empty() here, because 0 is a valid value
                    && ($value !== 99)
                ){
                    $queueRecipient->$setter($value);
                }
            }
        }
    }



    /**
     * function storing data
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @throws \Exception
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @return bool
     */
    public function addQueueRecipient(\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient)
    {

        self::debugTime(__LINE__, __METHOD__);
        if ($this->queueRecipientValidator->validate($queueRecipient)) {

            // get queueMail-object
            $queueMail = $this->getQueueMail();

            // add recipient with status "waiting" to queueMail and remove it from object storage
            $queueRecipient->setStatus(2);

            // set storage pid
            $queueRecipient->setPid(intval($this->getSettings('storagePid', 'persistence')));

            // set queueMail
            $queueRecipient->setQueueMail($queueMail);

            // update, add and persist
            $this->queueRecipientRepository->add($queueRecipient);
            $this->persistenceManager->persistAll();

            self::debugTime(__LINE__, __METHOD__);
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Added recipient with email "%s" (uid=%s) to queueMail with uid=%s.', $queueRecipient->getEmail(), $queueRecipient->getUid(), $queueMail->getUid()));

            return true;
            //===
        }

        return false;
        //===
    }


    /**
     * get url for images
     *
     * @return string
     * @throws \Exception
     */
    public function getImageUrl()
    {
        // build paths to images and logos
        $queueMail = $this->getQueueMail();
        $url = $queueMail->getSettings('baseUrlImages');
        if (
            ($queueMail->getSettings('baseUrl'))
            && ($queueMail->getSettings('basePathImages'))
        ) {
            $url = $queueMail->getSettings('baseUrl') . '/' . $this->getRelativePath($queueMail->getSettings('basePathImages'));
        }

        return $url;
        //===
    }


    /**
     * get url for images
     *
     * @return string
     * @throws \Exception
     */
    public function getLogoUrl()
    {
        // build paths to images and logos
        $queueMail = $this->getQueueMail();
        $url = $queueMail->getSettings('baseUrlLogo');
        if (
            ($queueMail->getSettings('baseUrl'))
            && ($queueMail->getSettings('basePathLogo'))
        ) {
            $url = $queueMail->getSettings('baseUrl') . '/' . $this->getRelativePath($queueMail->getSettings('basePathLogo'));
        }

        return $url;
        //===
    }


    /**
     * rendering of templates
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @throws \Exception
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function renderTemplates(\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient)
    {

        self::debugTime(__LINE__, __METHOD__);

        // get queueMail-object and check for at least one template!
        $queueMail = $this->getQueueMail();
        if ($queueRecipient->_isNew()) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException('The queueRecipient-object has to be persisted before it can be used.', 1540294116);
            //===
        }

        // build HTML- or Plaintext- Template if set!
        $markerArray = array();
        foreach (
            array(
                'html'      => 'html',
                'plaintext' => 'plaintext',
                'calendar'  => 'calendar',
            ) as $property => $template
        ) {

            $templateGetter = 'get' . ucFirst($template) . 'Template';
            $propertySetter = 'set' . ucFirst($property) . 'Body';
            $propertyGetter = 'get' . ucFirst($property) . 'Body';
            if ($queueMail->$templateGetter()) {
                if (! $queueRecipient->$propertyGetter()) {

                    // build marker array - but only once!
                    if (count($markerArray) < 1) {

                        // rebuild lightweight marker. Replace simple references to real extbase objects
                        $queueRecipientMarker = $this->explodeMarker($queueRecipient->getMarker());
                        $markerArray = array_merge(
                            (is_array($queueRecipientMarker) ? $queueRecipientMarker : []),
                            [
                                'queueRecipient' => $queueRecipient,
                            ]
                        );
                        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, self::SIGNAL_RENDER_TEMPLATE_AFTER_MARKERS . ($queueMail->getCategory() ? '_' . ucFirst($queueMail->getCategory()) : ''), array($queueMail, &$queueRecipient, &$markerArray));
                    }

                    // render template
                    $renderedTemplate = $this->renderSingleTemplate($template, $markerArray);

                    // add to recipient
                    $this->getSignalSlotDispatcher()->dispatch(__CLASS__, self::SIGNAL_RENDER_TEMPLATE_AFTER_RENDER . ($queueMail->getCategory() ? '_' . ucFirst($queueMail->getCategory()) : ''), array($queueMail, &$queueRecipient, &$renderedTemplate));
                    $queueRecipient->$propertySetter($renderedTemplate);

                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Added %s-template-property for recipient with email "%s" (queueMail uid=%s).', ucFirst($template), $queueRecipient->getEmail(), $queueMail->getUid()));
                } else {
                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('%s-template-property is already set for recipient with email "%s" (queueMail uid=%s).', ucFirst($template), $queueRecipient->getEmail(), $queueMail->getUid()));
                }
            } else {
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('%s-template is not set for recipient with email "%s" (queueMail uid=%s).', ucFirst($template), $queueRecipient->getEmail(), $queueMail->getUid()));
            }
        }

        self::debugTime(__LINE__, __METHOD__);
    }


    /**
     * Renders single template
     *
     * @param string $templateType
     * @param array $markerArray
     * @return string
     * @throws \Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderSingleTemplate($templateType, $markerArray = array())
    {

        // check for type
        if (!in_array($templateType, [ 'html', 'plaintext', 'calendar'])) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceException('Invalid templateType given.', 1551872053);
            //===
        }

        $queueMail = $this->getQueueMail();
        $templateGetter = 'get' . ucFirst($templateType) . 'Template';

        // check for template
        if (! $queueMail->$templateGetter()) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceException(sprintf('No valid template for %s set.', $templateType), 1551872053);
            //===
        }

        // get paths
        $layoutRootPaths = $this->getSettings('layoutRootPaths', 'view');
        $partialRootPaths = $this->getSettings('partialRootPaths', 'view');
        $templateRootPaths = $this->getSettings('templateRootPaths', 'view');

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $emailView */
        $emailView = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $emailView->setLayoutRootPaths($layoutRootPaths);
        $emailView->setPartialRootPaths($partialRootPaths);
        $emailView->setTemplateRootPaths($templateRootPaths);

        // set additional layout und partial path
        if ($queueMail->getLayoutPaths()) {
            $emailView->setLayoutRootPaths(array_merge($layoutRootPaths, $queueMail->getLayoutPaths()));
        }
        if ($queueMail->getPartialPaths()) {
            $emailView->setPartialRootPaths(array_merge($partialRootPaths, $queueMail->getPartialPaths()));
        }
        if ($queueMail->getTemplatePaths()) {
            $emailView->setTemplateRootPaths(array_merge($templateRootPaths, $queueMail->getTemplatePaths()));
        }

        // check for absolute paths!
        if (strpos($queueMail->$templateGetter(), 'EXT:') === 0) {
            $templatePathFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($queueMail->$templateGetter() . '.html', true, true);
            $emailView->setTemplatePathAndFilename($templatePathFile);
        } else {
            $emailView->setTemplate($queueMail->$templateGetter());
        }

        // assign markers
        $finalMarkerArray = array_merge(
            $markerArray,
            [
                'queueMail'            => $queueMail,
                'queueMailSettingsPid' => $queueMail->getSettingsPid(),
                'bodyText'             => $queueMail->getBodyText(),
                'settings'             => $queueMail->getSettings(),
                'mailType'             => ucFirst($templateType),
            ]
        );

        $emailView->assignMultiple($finalMarkerArray);

        // replace baseURLs in final email  - replacement with asign only works in template-files, not on layout-files
        $renderedTemplate = preg_replace('/###baseUrl###/', $queueMail->getSettings('baseUrl'), $emailView->render());
        $renderedTemplate = preg_replace('/###baseUrlImages###/', $this->getImageUrl(), $renderedTemplate);
        $renderedTemplate = preg_replace('/###baseUrlLogo###/', $this->getLogoUrl(), $renderedTemplate);


        // replace relative paths and absolute paths to server-root!
        /* @toDo: Check if Environment-variables are still valid in TYPO3 8.7 and upwards! */
        $replacePaths = [
            GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'),
            $_SERVER['TYPO3_PATH_ROOT'] .'/'
        ];

        foreach ($replacePaths as $replacePath) {
            $renderedTemplate = preg_replace('/(src|href)="' . str_replace('/', '\/', $replacePath) . '([^"]+)"/', '$1="' . '/$2"', $renderedTemplate);
        }
        $renderedTemplate = preg_replace('/(src|href)="\/([^"]+)"/', '$1="' . $queueMail->getSettings('baseUrl') . '/$2"', $renderedTemplate);

        return $renderedTemplate;
        //===
    }


    /**
     * function send
     *
     * @return boolean
     * @throws \Exception
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function send()
    {
        self::debugTime(__LINE__, __METHOD__);

        $queueMail = $this->getQueueMail();
        if (!$this->queueMailValidator->validate($queueMail)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException('Invalid or missing data in queueMail-object.', 1540186577);
            //===
        }

        // only start sending if we are in draft status
        if ($queueMail->getStatus() == 1) {

            // find all final recipients of waiting mails!
            $recipientCount = $this->queueRecipientRepository->findAllByQueueMailWithStatusWaiting($queueMail, 0)->count();
            if ($recipientCount > 0) {

                // set status to waiting so the email will be processed
                $queueMail->setStatus(2);

                // update and persist changes
                $this->queueMailRepository->update($queueMail);

                // persist all until here
                $this->persistenceManager->persistAll();

                // reset object
                $this->unsetVariables();

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Marked queueMail with uid=%s for cronjob (%s recipients).', $queueMail->getUid(), $recipientCount));

                return true;
                //====

            } else {
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('QueueMail with uid=%s has no recipients.', $queueMail->getUid()));
            }

        } else {
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('QueueMail with uid=%s is not a draft (status = %s).', $queueMail->getUid(), $queueMail->getStatus()));
        }

        self::debugTime(__LINE__, __METHOD__);

        return false;
        //===
    }


    /**
     * function sendToRecipient
     * this method is extensively protected via try-catch because it may be used in cronjob-context
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @return boolean
     * @throws \Exception
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function sendToRecipient(\RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient)
    {
        self::debugTime(__LINE__, __METHOD__);
        $status = false;

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->getQueueMail();

        // validate queueMail
        if (!$this->queueMailValidator->validate($queueMail)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException('Invalid or missing data in queueMail-object.', 1438249330);
            //===
        }

        // validate queueRecipient
        if (!$this->queueRecipientValidator->validate($queueRecipient)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException('Invalid or missing data in queueRecipient-object.', 1438249113);
            //===
        }
        if ($queueRecipient->_isNew()) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException('The queueMailRecipient-object has to be persisted before it can be used.', 1540187277);
            //===
        }


        // check if email of recipient has bounced recently - but only for pipeline mailings
        if (
            ($queueRecipient->getFrontendUser()->getTxRkwmailerHardBounceCount() < 3)
            || (! $this->queueMail->getPipeline())
        ){

            // render templates
            $this->renderTemplates($queueRecipient);

            // try to send message
            try {

                /** @var  \TYPO3\CMS\Core\Mail\MailMessage $message */
                $message = $this->prepareEmailForRecipient($queueRecipient);
                $this->getSignalSlotDispatcher()->dispatch(__CLASS__, self::SIGNAL_SEND_TO_RECIPIENT_BEFORE_SEND . ($queueMail->getCategory() ? '_' . ucFirst($queueMail->getCategory()) : ''), array(&$queueMail, &$queueRecipient));

                $message->send();
                $status = true;

                // set recipient status 4 for "sent" and remove marker
                $queueRecipient->setStatus(4);

                // StatisticSent: ALTERNATIVE EVALUATION
                $this->statisticSentUtility->elevateStatistic($queueMail, $queueRecipient, 'sent');

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, sprintf('Successfully sent e-mail to "%s" (recipient-uid=%s) for queueMail id=%s.', $queueRecipient->getEmail(), $queueRecipient->getUid(), $queueMail->getUid()));


            } catch (\Exception $e) {

                $status = false;
                $errorMessage = str_replace(array("\n", "\r"), '', $e->getMessage());

                // set recipient status to error
                $queueRecipient->setStatus(99);

                // StatisticSent: ALTERNATIVE EVALUATION
                $this->statisticSentUtility->elevateStatistic($queueMail, $queueRecipient, 'failed');

                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('An error occurred while trying to send an e-mail to "%s" (recipient-uid=%s). Message: %s', $queueRecipient->getEmail(), $queueRecipient->getUid(), $errorMessage));
            }

        } else {

            // set status to deferred - we don't sent an email to this address again
            $queueRecipient->setStatus(97);
            $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('E-mail "%s" (recipient-uid=%s) blocked for further mailings because of bounces detected during processing of queueMail width uid=%s.', $queueRecipient->getEmail(), $queueRecipient->getUid(), $queueMail->getUid()));

            // StatisticSent: ALTERNATIVE EVALUATION
            $this->statisticSentUtility->elevateStatistic($queueMail, $queueRecipient, 'deferred');

        }

        // User has to be updated no matter what!
        $this->queueRecipientRepository->update($queueRecipient);

        // persist
        $this->persistenceManager->persistAll();

        self::debugTime(__LINE__, __METHOD__);

        return $status;
        //===
    }

    /**
     * prepareEmailForRecipient
     *
     * prepares email object for given recipient user
     *
     * @param \RKW\RkwMailer\Domain\Model\QueueRecipient $queueRecipient
     * @return null |\TYPO3\CMS\Core\Mail\MailMessage
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException
     * @throws \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function prepareEmailForRecipient ($queueRecipient) {

        /** @var \RKW\RkwMailer\Domain\Model\QueueMail $queueMail */
        $queueMail = $this->getQueueMail();

        // validate queueMail
        if (!$this->queueMailValidator->validate($queueMail)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueMailException('Invalid or missing data in queueMail-object.', 1438249330);
            //===
        }
        // validate queueRecipient
        if (!$this->queueRecipientValidator->validate($queueRecipient)) {
            throw new \RKW\RkwMailer\Service\Exception\MailServiceQueueRecipientException('Invalid or missing data in queueRecipient-object.', 1552485792);
            //===
        }

        // render templates
        $this->renderTemplates($queueRecipient);

        if ($queueRecipient->getStatus() < 4) {

            /** @var \TYPO3\CMS\Core\Mail\MailMessage $message */
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');

            // Set message parts based on queueRecipient
            if (
                $queueRecipient->getPlaintextBody()
                || $queueRecipient->getHtmlBody()
            ) {

                // build e-mail
                foreach (
                    array(
                        'html'     => 'html',
                        'plain'    => 'plaintext',
                    ) as $shortName => $longName
                ) {

                    $getter = 'get' . ucFirst($longName) . 'Body';
                    if ($queueRecipient->$getter()) {

                        $message->addPart($queueRecipient->$getter(), 'text/' . $shortName);
                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Setting %s-body for recipient with uid=%s in queueMail with uid=%s.', $longName, $queueRecipient->getUid(), $queueMail->getUid()));
                    }
                }

            // set raw body-text from queueMail
            } else {

                $emailBody = $queueMail->getBodyText();
                $message->setBody($emailBody, 'text/plain');
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Setting default body for recipient with uid=%s in queueMail with uid=%s.', $queueRecipient->getUid(), $queueMail->getUid()));
            }

            // set calendar attachment
            if ($queueRecipient->getCalendarBody()) {

                // replace line breaks according to RFC 5545 3.1.
                $emailString = preg_replace('/\n/', "\r\n", $queueRecipient->getCalendarBody());
                $attachment = \Swift_Attachment::newInstance($emailString, 'meeting.ics', 'text/calendar');
                $message->attach($attachment);
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Setting calendar-body for recipient with uid=%s in queueMail with uid=%s.', $queueRecipient->getUid(), $queueMail->getUid()));
            }

            // add attachment if set
            /*
            if (
                ($queueMail->getAttachment())
                && ($queueMail->getAttachmentName())
                && ($queueMail->getAttachmentType())
            ) {

                $attachment = \Swift_Attachment::newInstance($queueMail->getAttachment(), $queueMail->getAttachmentName(), $queueMail->getAttachmentType());
                $message->attach($attachment);
            }
            */

            // ====================================================
            // Send mail
            // set status 3 for "sending" (pro forma since no persistence here)
            $queueRecipient->setStatus(3);

            // build message based on given data
            $recipientAddress = $queueRecipient->getEmail();
            $recipientName = trim(ucfirst($queueRecipient->getFirstName()) . ' ' . ucfirst($queueRecipient->getLastName()));
            if (trim($recipientName)) {
                $recipientAddress = [$queueRecipient->getEmail() => $recipientName];
            }

            $message->setFrom(array($queueMail->getFromAddress() => $queueMail->getFromName()))
                ->setReplyTo($queueMail->getReplyAddress())
                ->setTo($recipientAddress)
                ->setSubject($queueRecipient->getSubject() ? $queueRecipient->getSubject() : $queueMail->getSubject())
                ->setPriority(intval($queueMail->getPriority()))
                ->setReturnPath($queueMail->getReturnPath());


            return $message;
            //===
        }

        return null;
        //===

    }



    /**
     * implodeMarker
     * transform objects into simple references
     *
     * @param array $marker
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function implodeMarker($marker)
    {
        self::debugTime(__LINE__, __METHOD__);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper */
        $dataMapper = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        foreach ($marker as $key => $value) {

            // replace current entry with "table => uid" reference
            // keep current variable name, don't use "unset"
            if (is_object($value)) {

                // Normal DomainObject
                if ($value instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {

                    $namespace = filter_var($dataMapper->getDataMap(get_class($value))->getClassName(), FILTER_SANITIZE_STRING);
                    if ($value->_isNew()) {
                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Object with namespace %s in marker-array is not persisted and will be stored as serialized object in the database. This may cause performance issues!', $namespace));
                    } else {
                        $marker[$key] = self::NAMESPACE_KEYWORD . ' ' . $namespace . ":" . $value->getUid();
                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Replacing object with namespace %s and uid %s in marker-array.', $namespace, $value->getUid()));
                    }

                // ObjectStorage or QueryResult
                } else {
                    if (
                        ($value instanceof \Iterator)
                        && (
                            (
                                ($value instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface)
                                && ($firstObject = $value->getFirst())
                            )
                            || (
                                ($value instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage)
                                && ($firstObject = $value->current())
                            )
                        )
                        && ($firstObject instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity)
                    ) {

                        $newValues = array();
                        $namespace = filter_var($dataMapper->getDataMap(get_class($firstObject))->getClassName(), FILTER_SANITIZE_STRING);
                        $replaceObjectStorage = true;
                        foreach ($value as $object) {
                            if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
                                if ($object->_isNew()) {
                                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Object with namespace %s in marker-array is not persisted. The object storage it belongs to will be stored as serialized object in the database. This may cause performance issues!', $namespace));
                                    $replaceObjectStorage = false;
                                    break;
                                    //===
                                } else {
                                    $newValues[] = $namespace . ":" . $object->getUid();
                                    $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Replacing object with namespace %s and uid %s in marker-array.', $namespace, $object->getUid()));
                                }
                            }
                        }
                        if ($replaceObjectStorage) {
                            $marker[$key] = self::NAMESPACE_ARRAY_KEYWORD . ' ' . implode(',', $newValues);
                        }

                    } else {
                        $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, sprintf('Object of class %s in marker-array will be stored as serialized object in the database. This may cause performance issues!', get_class($value)));
                    }
                }
            }
        }

        self::debugTime(__LINE__, __METHOD__);

        return $marker;
        //===
    }


    /**
     * explodeMarker
     * transform simple references to objects
     *
     * @param array $marker
     * @return array
     */
    public function explodeMarker($marker)
    {
        self::debugTime(__LINE__, __METHOD__);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        foreach ($marker as $key => $value) {

            // check for keyword
            if (
                (is_string($value))
                && (
                    (strpos(trim($value), self::NAMESPACE_KEYWORD) === 0)
                    || (strpos(trim($value), self::NAMESPACE_ARRAY_KEYWORD) === 0)
                )
            ) {

                // check if we have an array here
                $isArray = (bool)(strpos(trim($value), self::NAMESPACE_ARRAY_KEYWORD) === 0);
                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Detection of objectStorage: %s.', intval($isArray)));

                /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $objectStorage */
                $objectStorage = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');

                // clean value from keyword
                $cleanedValue = trim(
                    str_replace(
                        array(
                            self::NAMESPACE_ARRAY_KEYWORD,
                            self::NAMESPACE_KEYWORD,
                        ),
                        '',
                        $value
                    )
                );

                // Go through list of objects. May be comma-separated in case of QueryResultInterface or ObjectStorage
                $listOfObjectDefinitions = GeneralUtility::trimExplode(',', $cleanedValue);
                foreach ($listOfObjectDefinitions as $objectDefinition) {

                    // explode namespace and uid
                    $explodedValue = GeneralUtility::trimExplode(':', $objectDefinition);
                    $namespace = trim($explodedValue[0]);
                    $uid = intval($explodedValue[1]);

                    if (class_exists($namespace)) {

                        // @toDo: Find a way to get the repository namespace instead of this replace
                        $repositoryName = str_replace('Model', 'Repository', $namespace) . 'Repository';
                        if (class_exists($repositoryName)) {

                            /** @var \TYPO3\CMS\Extbase\Persistence\Repository $repository */
                            $repository = $objectManager->get($repositoryName);

                            // build query - we fetch everything here!
                            $query = $repository->createQuery();
                            $query->getQuerySettings()->setRespectStoragePage(false);
                            $query->getQuerySettings()->setIgnoreEnableFields(true);
                            $query->getQuerySettings()->setIncludeDeleted(true);
                            $query->matching(
                                $query->equals('uid', $uid)
                            )->setLimit(1);

                            if ($result = $query->execute()->getFirst()) {
                                $objectStorage->attach($result);
                                $this->getLogger()->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, sprintf('Replacing object with namespace %s and uid %s in marker-array.', $namespace, $result->getUid()));
                            }
                        }
                    }
                }

                if ($objectStorage->count() > 0) {
                    if ($isArray) {
                        $marker[$key] = $objectStorage;
                    } else {
                        $objectStorage->rewind();
                        $marker[$key] = $objectStorage->current();
                    }
                }
            }
        }

        self::debugTime(__LINE__, __METHOD__);
        return $marker;
        //===
    }


    /**
     * unset several variables
     *
     * @return void
     */
    protected function unsetVariables()
    {
        unset($this->queueMail);
    }


    /**
     * Returns SignalSlotDispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {

        if (!$this->signalSlotDispatcher) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            $this->signalSlotDispatcher = $objectManager->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        }

        return $this->signalSlotDispatcher;
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
    
    /**
     * Returns the relative image path
     *
     * @param string $path
     * @return string
     */
    protected function getRelativePath($path)
    {
        if (strpos($path, 'EXT:') === 0) {

            list($extKey, $local) = explode('/', substr($path, 4), 2);
            if (
                ((string)$extKey !== '')
                && (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey))
                && ((string)$local !== '')
            ) {
                $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extKey) . $local;
                if (strpos($path, '../') === 0) {
                    $path = substr($path, -(strlen($path)-3));
                }
            }
        }

        return $path;
        //===
    }


    /**
     * Gets TypoScript framework settings
     *
     * @param string $param
     * @param string $type
     * @return mixed
     */
    private function getSettings($param = '', $type = 'settings')
    {

        if (!$this->settings) {

            $this->settings = $this->configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'RkwMailer',
                'user'
            );
        }

        if ($param) {

            if ($this->settings[$type][$param . '.']) {
                return $this->settings[$type][$param . '.'];
                //===
            }

            return $this->settings[$type][$param];
            //===

        }

        return $this->settings[$type];
        //===
    }


    /**
     * Does debugging of runtime
     *
     * @param integer $line
     * @param string  $function
     */
    private static function debugTime($line, $function)
    {

        if (self::DEBUG_TIME) {

            $path = PATH_site . '/typo3temp/tx_rkwmailer_runtime.txt';
            file_put_contents($path, microtime() . ' ' . $line . ' ' . $function . "\n", FILE_APPEND);
        }
    }


}