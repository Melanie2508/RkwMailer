<?php

namespace RKW\RkwMailer\Domain\Model;

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
 * QueueMail
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwMailer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueueMail extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * crdate
     *
     * @var integer
     */
    protected $crdate;

    /**
     * sorting
     *
     * @var integer
     */
    protected $sorting = 0;

    /**
     * status
     *
     * @var integer
     */
    protected $status = 1;

    /**
     * type
     *
     * @var integer
     */
    protected $type = 0;


    /**
     * pipeline
     *
     * @var bool
     */
    protected $pipeline;


    /**
     * fromName
     *
     * @var string
     */
    protected $fromName = '';

    /**
     * fromAddress
     *
     * @var string
     */
    protected $fromAddress = '';

    /**
     * replyAddress
     *
     * @var string
     */
    protected $replyAddress = '';

    /**
     * returnPath
     *
     * @var string
     */
    protected $returnPath = '';

    /**
     * subject
     *
     * @var string
     */
    protected $subject = '';


    /**
     * bodyText
     *
     * @var string
     */
    protected $bodyText = '';

    /**
     * attachment
     *
     * @var string
     */
    protected $attachment = '';


    /**
     * attachmentType
     *
     * @var string
     */
    protected $attachmentType = '';

    /**
     * attachmentName
     *
     * @var string
     */
    protected $attachmentName = '';

    /**
     * plaintextTemplate
     *
     * @var string
     */
    protected $plaintextTemplate = '';

    /**
     * htmlTemplate
     *
     * @var string
     */
    protected $htmlTemplate = '';

    /**
     * calendarTemplate
     *
     * @var string
     */
    protected $calendarTemplate = '';


    /**
     * templatePaths
     *
     * @var string
     */
    protected $templatePaths = '';


    /**
     * layoutPaths
     *
     * @var string
     */
    protected $layoutPaths = '';

    /**
     * partialPaths
     *
     * @var string
     */
    protected $partialPaths = '';


    /**
     * category
     *
     * @var string
     */
    protected $category = '';


    /**
     * campaignParameter
     *
     * @var string
     */
    protected $campaignParameter = '';


    /**
     * priority
     *
     * @var integer
     */
    protected $priority = 3;


    /**
     * settingsPid
     *
     * @var integer
     */
    protected $settingsPid;

    /**
     * tstampFavSending
     *
     * @var integer
     */
    protected $tstampFavSending;

    /**
     * tstampRealSending
     *
     * @var integer
     */
    protected $tstampRealSending;

    /**
     * tstampSendFinish
     *
     * @var integer
     */
    protected $tstampSendFinish;


    /**
     * total
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $total;


    /**
     * sent
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $sent;


    /**
     * successful
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $successful;


    /**
     * failed
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $failed;

    /**
     * deferred
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $deferred;

    /**
     * bounced
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $bounced;


    /**
     * opened
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $opened;


    /**
     * clicked
     *
     * @deprecated use StatisticSent instead
     * @var integer
     */
    protected $clicked;


    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }


    /**
     * settings
     *
     * @var array
     */
    protected $settings = array();


    /**
     * Constructor
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * Initialize object with default values from configuration
     *
     * @throws \Exception
     */
    public function initializeObject()
    {

        // set defaults
        if (!$this->getFromAddress()) {
            $this->setFromAddress($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']);
        }

        if (!$this->getReplyAddress()) {
            $this->setReplyAddress($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyAddress'] ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyAddress'] : $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']);
        }

        if (!$this->getReturnPath()) {
            $this->setReturnPath($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReturnAddress'] ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReturnAddress'] : $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']);
        }

        if (!$this->getFromName()) {
            $this->setFromName($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']);
        }

        // set configuration root-page
        if (
            (!$this->getSettingsPid())
            && ($GLOBALS['TSFE']->id)
        ) {
            $this->setSettingsPid(intval($GLOBALS['TSFE']->id));

        }

    }

    /**
     * Returns the crdate
     *
     * @return integer $crdate
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Sets the crdate
     *
     * @param integer $crdate
     * @return void
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Returns the sorting
     *
     * @return integer $sorting
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Sets the sorting
     *
     * @param integer $sorting
     * @return void
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }


    /**
     * Returns the status
     *
     * @return integer $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param integer $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the type
     *
     * @return integer $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @param integer $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the pipeline
     *
     * @return bool $pipeline
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * Sets the pipeline
     *
     * @param bool $pipeline
     * @return void
     */
    public function setPipeline($pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * Returns the fromName
     *
     * @return string $fromName
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Sets the fromName
     *
     * @param string $fromName
     * @return void
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    /**
     * Returns the fromAddress
     *
     * @return string $fromAddress
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * Sets the fromAddress
     *
     * @param string $fromAddress
     * @return void
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;
    }

    /**
     * Returns the replyAddress
     *
     * @return string $replyAddress
     */
    public function getReplyAddress()
    {
        return $this->replyAddress;
    }

    /**
     * Sets the replyAddress
     *
     * @param string $replyAddress
     * @return void
     */
    public function setReplyAddress($replyAddress)
    {
        $this->replyAddress = $replyAddress;
    }

    /**
     * Returns the returnPath
     *
     * @return string $returnPath
     */
    public function getReturnPath()
    {
        return $this->returnPath;
    }

    /**
     * Sets the returnPath
     *
     * @param string $returnPath
     * @return void
     */
    public function setReturnPath($returnPath)
    {
        $this->returnPath = $returnPath;
    }

    /**
     * Returns the subject
     *
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets the subject
     *
     * @param string $subject
     * @return void
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Returns the bodyText
     *
     * @return string $bodyText
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Sets the bodyText
     *
     * @param string $bodyText
     * @return void
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;
    }

    /**
     * Returns the attachment
     *
     * @return string $attachment
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Sets the attachment
     *
     * @param string $attachment
     * @return void
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Returns the attachment
     *
     * @return integer $attachment
     */
    public function getAttachmentType()
    {
        return $this->attachmentType;
    }

    /**
     * Sets the attachment
     *
     * @param string $attachmentType
     * @return void
     */
    public function setAttachmentType($attachmentType)
    {
        $this->attachmentType = $attachmentType;
    }

    /**
     * Returns the attachment
     *
     * @return integer $attachment
     */
    public function getAttachmentName()
    {
        return $this->attachmentName;
    }

    /**
     * Sets the attachment
     *
     * @param string $attachmentName
     * @return void
     */
    public function setAttachmentName($attachmentName)
    {
        $this->attachmentName = $attachmentName;
    }

    /**
     * Returns the plaintextTemplate
     *
     * @return string $plaintextTemplate
     */
    public function getPlaintextTemplate()
    {
        return $this->plaintextTemplate;
    }

    /**
     * Sets the plaintextTemplate
     *
     * @param string $plaintextTemplate
     * @return void
     */
    public function setPlaintextTemplate($plaintextTemplate)
    {
        $this->plaintextTemplate = $plaintextTemplate;
    }

    /**
     * Returns the htmlTemplate
     *
     * @return string $htmlTemplate
     */
    public function getHtmlTemplate()
    {
        return $this->htmlTemplate;
    }

    /**
     * Sets the htmlTemplate
     *
     * @param string $htmlTemplate
     * @return void
     */
    public function setHtmlTemplate($htmlTemplate)
    {
        $this->htmlTemplate = $htmlTemplate;
    }

    /**
     * Returns the calendarTemplate
     *
     * @return string $calendarTemplate
     */
    public function getCalendarTemplate()
    {
        return $this->calendarTemplate;
    }

    /**
     * Sets the calendarTemplate
     *
     * @param string $calendarTemplate
     * @return void
     */
    public function setCalendarTemplate($calendarTemplate)
    {
        $this->calendarTemplate = $calendarTemplate;
    }


    /**
     * Returns the layoutPath
     *
     * @return array
     * @throws \Exception
     */
    public function getLayoutPaths()
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->layoutPaths, true);

        return array_merge($paths, $this->getSettings('layoutRootPaths', 'view'));
        //===
    }


    /**
     * Sets the layoutPaths
     *
     * @param array $layoutPaths
     * @return void
     */
    public function setLayoutPaths($layoutPaths)
    {
        $this->layoutPaths = implode(',', $layoutPaths);
    }


    /**
     * Sets the layoutPath
     *
     * @param string $layoutPath
     * @return void
     * @deprecated use addLayoutPath or setLayoutPaths instead
     */
    public function setLayoutPath($layoutPath)
    {
        $this->addLayoutPath($layoutPath);
    }


    /**
     * Adds an layoutPath
     *
     * @param string $layoutPath
     * @return void
     */
    public function addLayoutPath($layoutPath)
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->layoutPaths);
        $paths[] = $layoutPath;
        $this->layoutPaths = implode(',', $paths);
    }


    /**
     * Adds layoutPaths
     *
     * @param array $layoutPaths
     * @return void
     */
    public function addLayoutPaths($layoutPaths)
    {
        if (is_array($layoutPaths)) {
            $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->layoutPaths, true);
            $this->layoutPaths = implode(',', array_merge($paths, $layoutPaths));
        }
    }


    /**
     * Returns the partialPath
     *
     * @return array
     * @throws \Exception
     */
    public function getPartialPaths()
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->partialPaths, true);

        return array_merge($paths, $this->getSettings('partialRootPaths', 'view'));
        //===
    }


    /**
     * Sets the partialPaths
     *
     * @param array $partialPaths
     * @return void
     */
    public function setPartialPaths($partialPaths)
    {
        $this->partialPaths = implode(',', $partialPaths);
    }


    /**
     * Sets the partialPath
     *
     * @param string $partialPath
     * @return void
     * @deprecated use addPartialPath or setPartialPaths instead
     */
    public function setPartialPath($partialPath)
    {
        $this->addPartialPath($partialPath);
    }


    /**
     * Adds an partialPath
     *
     * @param string $partialPath
     * @return void
     */
    public function addPartialPath($partialPath)
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->partialPaths, true);
        $paths[] = $partialPath;
        $this->partialPaths = implode(',', $paths);
    }


    /**
     * Adds partialPaths
     *
     * @param array $partialPaths
     * @return void
     */
    public function addPartialPaths($partialPaths)
    {
        if (is_array($partialPaths)) {
            $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->partialPaths, true);
            $this->partialPaths = implode(',', array_merge($paths, $partialPaths));
        }
    }


    /**
     * Returns the templatePath
     *
     * @return array
     * @throws \Exception
     */
    public function getTemplatePaths()
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->templatePaths, true);

        return array_merge($paths, $this->getSettings('templateRootPaths', 'view'));
        //===
    }


    /**
     * Sets the templatePaths
     *
     * @param array $templatePaths
     * @return void
     */
    public function setTemplatePaths($templatePaths)
    {
        $this->templatePaths = implode(',', $templatePaths);
    }


    /**
     * Sets the templatePath
     *
     * @param string $templatePath
     * @return void
     * @deprecated use addTemplatePath or setTemplatePaths instead
     */
    public function setTemplatePath($templatePath)
    {
        $this->addTemplatePath($templatePath);
    }


    /**
     * Adds an templatePath
     *
     * @param string $templatePath
     * @return void
     */
    public function addTemplatePath($templatePath)
    {
        $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->templatePaths, true);
        $paths[] = $templatePath;
        $this->templatePaths = implode(',', $paths);
    }


    /**
     * Adds templatePaths
     *
     * @param array $templatePaths
     * @return void
     */
    public function addTemplatePaths($templatePaths)
    {
        if (is_array($templatePaths)) {
            $paths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->templatePaths, true);
            $this->templatePaths = implode(',', array_merge($paths, $templatePaths));
        }
    }


    /**
     * Returns the category
     *
     * @return string $category
     */
    public function getCategory()
    {
        return $this->category;
    }


    /**
     * Sets the category
     *
     * @param string $category
     * @return void
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }


    /**
     * Returns the campaignParameter
     *
     * @return string $campaignParameter
     */
    public function getCampaignParameter()
    {
        return $this->campaignParameter;
    }

    /**
     * Returns the exploded campaignParameter
     *
     * @return array
     */
    public function getCampaignParameterExploded()
    {

        // explode by ampersand
        $implodedFirst = explode('&', str_replace('?', '', $this->campaignParameter));

        // now explode by equal-sign
        $result = array();
        foreach ($implodedFirst as $entry) {

            $tempExplode = explode('=', $entry);
            if (
                (count($tempExplode) == 2)
                && (strlen(trim($tempExplode[0])) > 0)
            ) {
                $result [trim($tempExplode[0])] = trim($tempExplode[1]);
            }

        }

        return $result;
        //===
    }

    /**
     * Sets the campaignParameter
     *
     * @param string $campaignParameter
     * @return void
     */
    public function setCampaignParameter($campaignParameter)
    {
        $this->campaignParameter = $campaignParameter;
    }


    /**
     * Returns the priority
     *
     * @return integer $priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Sets the priority
     *
     * @param integer $priority
     * @return void
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }


    /**
     * Returns the settingsPid
     *
     * @return integer $settingsPid
     */
    public function getSettingsPid()
    {
        return $this->settingsPid;
    }

    /**
     * Sets the settingsPid
     *
     * @param integer $settingsPid
     * @return void
     */
    public function setSettingsPid($settingsPid)
    {
        $this->settingsPid = $settingsPid;
    }

    /**
     * Returns the tstampFavSending
     *
     * @return integer $tstampFavSending
     */
    public function getTstampFavSending()
    {
        return $this->tstampFavSending;
    }

    /**
     * Sets the tstampFavSending
     *
     * @param integer $tstampFavSending
     * @return void
     */
    public function setTstampFavSending($tstampFavSending)
    {
        $this->tstampFavSending = $tstampFavSending;
    }

    /**
     * Returns the tstampRealSending
     *
     * @return integer $tstampRealSending
     */
    public function getTstampRealSending()
    {
        return $this->tstampRealSending;
    }

    /**
     * Sets the tstampRealSending
     *
     * @param integer $tstampRealSending
     * @return void
     */
    public function setTstampRealSending($tstampRealSending)
    {
        $this->tstampRealSending = $tstampRealSending;
    }

    /**
     * Returns the tstampSendFinish
     *
     * @return integer $tstampSendFinish
     */
    public function getTstampSendFinish()
    {
        return $this->tstampSendFinish;
    }

    /**
     * Sets the tstampSendFinish
     *
     * @param integer $tstampSendFinish
     * @return void
     */
    public function setTstampSendFinish($tstampSendFinish)
    {
        $this->tstampSendFinish = $tstampSendFinish;
    }

    /**
     * Returns the total
     *
     * @return integer $total
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * Returns the sent
     *
     * @return integer $sent
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @param int $sent
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
    }

    /**
     * Returns the successful
     *
     * @return integer $successful
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * @param int $successful
     */
    public function setSuccessful($successful)
    {
        $this->successful = $successful;
    }

    /**
     * Returns the failed
     *
     * @return integer $failed
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * @param int $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }

    /**
     * Returns the deferred
     *
     * @return integer $deferred
     */
    public function getDeferred()
    {
        return $this->deferred;
    }

    /**
     * @param int $deferred
     */
    public function setDeferred($deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * Returns the bounced
     *
     * @return integer $bounced
     */
    public function getBounced()
    {
        return $this->bounced;
    }

    /**
     * @param int $bounced
     */
    public function setBounced($bounced)
    {
        $this->bounced = $bounced;
    }
    
    /**
     * Returns the opened
     *
     * @return integer $opened
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * @param int $opened
     */
    public function setOpened($opened)
    {
        $this->opened = $opened;
    }

    /**
     * Returns the clicked
     *
     * @return integer $clicked
     */
    public function getClicked()
    {
        return $this->clicked;
    }
    /**
     * @param int $clicked
     */
    public function setClicked($clicked)
    {
        $this->clicked = $clicked;
    }

    /**
     * Gets TypoScript framework settings
     *
     * @param string $param
     * @param string $type
     * @return mixed
     * @throws \Exception
     */
    public function getSettings($param = '', $type = 'settings')
    {

        if (!$this->settings) {

            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
            $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');

            if ($this->getSettingsPid()) {
                $settingsTemp = $this->getTsForPage($this->getSettingsPid());

                // workaround because of dots
                $this->settings = array(
                    'persistence' => $settingsTemp['persistence.'],
                    'view'        => $settingsTemp['view.'],
                    'settings'    => $settingsTemp['settings.'],
                );

            } else {

                $this->settings = $configurationManager->getConfiguration(
                    \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                    'RkwMailer',
                    'user'
                );
            }
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
     * Return TS-Settings for given pid
     *
     * @param $pageId
     * @return array
     * @throws \Exception
     */
    private function getTsForPage($pageId)
    {
        /** @var \TYPO3\CMS\Core\TypoScript\TemplateService $template */
        $template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
        $template->tt_track = 0;
        $template->init();

        /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPage */
        $sysPage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $rootLine = $sysPage->getRootLine(intval($pageId));
        $template->runThroughTemplates($rootLine, 0);
        $template->generateConfig();

        return $template->setup['plugin.']['tx_rkwmailer.'];
        //===
    }
}