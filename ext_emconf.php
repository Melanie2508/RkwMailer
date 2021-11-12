<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "rkw_mailer"
 *
 * Auto generated by Extension Builder 2014-11-07
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'RKW Mailer',
	'description' => 'Extension sending e-mails and bulk-mailings',
	'category' => 'plugin',
	'author' => 'Maximilian Fäßler, Steffen Kroggel',
	'author_email' => 'faesslerweb@web.de, developer@steffenkroggel.de',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '9.5.1',
	'constraints' => [
		'depends' => [
            'typo3' => '8.7.0-9.5.99',
            'rkw_basics' => '8.7.76-9.5.99'
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
];
