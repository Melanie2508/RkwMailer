<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_rkwmailer_domain_model_statisticsent', 'EXT:rkw_mailer/Resources/Private/Language/locallang_csh_tx_rkwmailer_domain_model_statisticsent.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_rkwmailer_domain_model_statisticsent');
$GLOBALS['TCA']['tx_rkwmailer_domain_model_statisticsent'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent',
		'label' => 'mail_id',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'dividers2tabs' => true,
		'hideTable' => true,

		'searchFields' => 'queue_mail, foreign_uid, foreign_table, foreign_field, total, sent, successful, failed, deferred, bounced, opened, clicked',
		'iconfile' => 'EXT:rkw_mailer/Resources/Public/Icons/tx_rkwmailer_domain_model_statisticsent.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'queue_mail, foreign_uid, foreign_table, foreign_field, total, sent, successful, failed, deferred, bounced, opened, clicked',
	),
	'types' => array(
		'1' => array('showitem' => 'queue_mail, foreign_uid, foreign_table, foreign_field, total, sent, successful, failed, deferred, bounced, opened, clicked'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(

        'queue_mail' => array(
            'config' => array(
                'type' => 'passthrough',
                'foreign_table' => 'tx_rkwmailer_domain_model_queuemail',
            ),
        ),
		'foreign_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.foreign_uid',
			'config' => array(
				'type' => 'input',
				'size' => 4,
				'eval' => 'int'
			)
		),
        'foreign_table' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.foreign_table',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'foreign_field' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.foreign_field',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'total' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.total',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'sent' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.sent',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'successful' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.successful',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'failed' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.failed',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'deferred' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.deferred',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'bounced' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.bounced',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'opened' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.opened',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
        'clicked' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_statisticsent.clicked',
            'config' => array(
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            )
        ),
	),
);
