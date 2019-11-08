<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$tempCols = array(

    'tx_rkwmailer_soft_bounce_count' => array(
        'label'=>'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_frontenduser.tx_rkwmailer_soft_bounce_count',
        'exclude' => 0,
        'config'=>array(
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim,int'
        )
    ),
    'tx_rkwmailer_hard_bounce_count' => array(
        'label'=>'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_frontenduser.tx_rkwmailer_hard_bounce_count',
        'exclude' => 0,
        'config'=>array(
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim,int'
        )
    ),
    'tx_rkwmailer_last_bounce' => array(
        'label'=>'LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_frontenduser.tx_rkwmailer_last_bounce',
        'exclude' => 0,
        'config'=>array(
            'type'=>'input',
            'size' => 20,
            'max' => '256',
            'eval' => 'trim,int'
        )
    ),

);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempCols);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','--div--;LLL:EXT:rkw_mailer/Resources/Private/Language/locallang_db.xlf:tx_rkwmailer_domain_model_frontenduser, tx_rkwmailer_soft_bounce_count, tx_rkwmailer_hard_bounce_count, tx_rkwmailer_last_bounce', '', '');

