<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_rkwmailer_domain_model_queuemail', 'EXT:rkw_mailer/Resources/Private/Language/locallang_csh_tx_rkwmailer_domain_model_queuemail.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_rkwmailer_domain_model_queuemail');
$GLOBALS['TCA']['tx_rkwmailer_domain_model_queuemail'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail',
		'label' => 'subject',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'dividers2tabs' => true,
		'hideTable' => true,

		'sortby' => 'sorting',
		'searchFields' => 'status, type, pipeline, queue_recipients, from_name, from_address, reply_address, return_path, subject, body_text, settings_pid, campaign_parameter priority, plaintext_template, html_template, calendar_template, tstamp_fav_sending, tstamp_real_sending, tstamp_send_finish',
		'iconfile' => 'EXT:rkw_mailer/Resources/Public/Icons/tx_rkwmailer_domain_model_queuemail.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'status, type, pipeline, queue_recipients, from_name, from_address, reply_address, return_path, subject, body_text, settings_pid, campaign_parameter priority, plaintext_template, html_template, calendar_template, tstamp_fav_sending, tstamp_real_sending, tstamp_send_finish',
	),
	'types' => array(
		'1' => array('showitem' => 'subject, body_text, queue_recipients, --div--;LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tabs.from, from_name, from_address, reply_address, return_path, --div--;LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tabs.settings, settings_pid, plaintext_template, html_template, calendar_template, campaign_parameter, priority, --div--;LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tabs.sending, tstamp_fav_sending, tstamp_real_sending, tstamp_send_finish, status, type, pipeline'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(

        'status' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'eval' => 'int',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 2,
                'readOnly' => 1,
                'items' => array(
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status.I.draft', 1),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status.I.waiting', 2),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status.I.sending', 3),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status.I.sent', 4),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.status.I.error', 99),
                ),
            )
        ),

        'type' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.type',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'eval' => 'int',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'readOnly' => 1,
                'items' => array(
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.type.I.0', 0),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.type.I.1', 1),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.type.I.2', 2),
                ),
            )

        ),
        'pipeline' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.pipeline',
            'config' => array(
                'type' => 'check',
            ),
        ),

		'from_name' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.from_name',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'from_address' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.from_address',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,email'
			),
		),
		'reply_address' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.reply_address',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,email'
			),
		),
		'return_path' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.return_path',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,email'
			),
		),
		'subject' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.subject',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
        'body_text' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.body_text',
			'config' => array(
				'type' => 'text',
                'rows' => 40,
			),
		),
        'attachment' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.attachment',
			'config' => array(
                'type' => 'passthrough',
			)
		),
        'attachment_type' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.attachment_type',
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'attachment_name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.attachment_name',
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'plaintext_template' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.plaintext_template',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ),
        ),
        'html_template' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.html_template',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ),
        ),
        'calendar_template' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.calendar_template',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ),
        ),
		'layout_paths' => array(
			'exclude' => 0,
			'config' => array(
				'type' => 'passthrough',
			),
		),
		'partial_paths' => array(
			'exclude' => 0,
			'config' => array(
				'type' => 'passthrough',
			),
		),
        'template_paths' => array(
            'exclude' => 0,
            'config' => array(
                'type' => 'passthrough',
            ),
        ),
        'category' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.category',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ),
        ),
        'campaign_parameter' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.campaign_parameter',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ),
        ),
		'priority' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority',
			'config' => array(
                'type' => 'select',
				'renderType' => 'selectSingle',
                'size' => 1,
                'eval' => 'int',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 2,
                'readOnly' => 1,
                'items' => array(
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority.I.highest', 1),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority.I.high', 2),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority.I.default', 3),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority.I.low', 4),
                    array('LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.priority.I.lowest', 5),
                ),
			)
		),
        'settings_pid' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.settings_pid',
            'config' => array(
                'foreign_table' => 'pages',
                'type' => 'input',
                'size' => '30',
                'max' => '256',
                'eval' => 'int,required',
                'wizards' => array(
                    'link' => array(
                        'type' => 'popup',
                        'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                        'module' => array(
                            'name' => 'wizard_link',
                            'urlParameters' => array(
                                'mode' => 'wizard',
                            )
                        ),
                        'JSopenParams' => 'height=400,width=550,status=0,menubar=0,scrollbars=1',
                        'params' => Array(
                            // List of tabs to hide in link window. Allowed values are:
                            // file, mail, page, spec, folder, url
                            'blindLinkOptions' => 'mail,file,url,spec,folder',

                            // allowed extensions for file
                            //'allowedExtensions' => 'mp3,ogg',
                        )
                    )
                ),
                'softref' => 'typolink'
            )
        ),
		'tstamp_fav_sending' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tstamp_fav_sending',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'tstamp_real_sending' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tstamp_real_sending',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
                'readOnly' =>1,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'tstamp_send_finish' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.tstamp_send_finish',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
                'readOnly' =>1,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
        'total' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.total',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'sent' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.sent',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'successful' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.successful',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'failed' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.failed',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'deferred' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.deferred',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'bounced' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.bounced',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'opened' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.opened',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'clicked' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_queuemail.clicked',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
    ),
);
