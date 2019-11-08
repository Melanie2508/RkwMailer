#
# Table structure for table 'tx_rkwmailer_domain_model_queuemail'
#
CREATE TABLE tx_rkwmailer_domain_model_queuemail (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	sorting int(11) unsigned DEFAULT '0' NOT NULL,
    status tinyint(2) unsigned DEFAULT '1',
	type tinyint(2) unsigned DEFAULT '0',
    pipeline tinyint(1) unsigned DEFAULT '0',

	from_name varchar(255) DEFAULT '' NOT NULL,
	from_address varchar(255) DEFAULT '' NOT NULL,
	reply_address varchar(255) DEFAULT '' NOT NULL,
	return_path varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	body_text text NOT NULL,
	attachment blob,
	attachment_type varchar(255) DEFAULT '' NOT NULL,
	attachment_name varchar(255) DEFAULT '' NOT NULL,

	plaintext_template longtext NOT NULL,
	html_template longtext NOT NULL,
	calendar_template longtext NOT NULL,

	layout_paths text NOT NULL,
	partial_paths text NOT NULL,
	template_paths text NOT NULL,

	category varchar(255) DEFAULT '' NOT NULL,
	campaign_parameter varchar(255) DEFAULT '' NOT NULL,
	priority int(11) DEFAULT '0' NOT NULL,
	settings_pid int(11) unsigned DEFAULT '0',

	tstamp_fav_sending int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp_real_sending int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp_send_finish int(11) unsigned DEFAULT '0' NOT NULL,


	PRIMARY KEY (uid),
	KEY parent (pid),
    KEY status (status),
    KEY type (type)

);

#
# Table structure for table 'tx_rkwmailer_domain_model_queuerecipient'
#
CREATE TABLE tx_rkwmailer_domain_model_queuerecipient (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	queue_mail int(11) unsigned DEFAULT '0',
	frontend_user int(11) unsigned DEFAULT '0',

	email varchar(255) DEFAULT '' NOT NULL,
	salutation tinyint(2) unsigned DEFAULT '0',
	title varchar(255) DEFAULT '' NOT NULL,
	first_name varchar(255) DEFAULT '' NOT NULL,
	last_name varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	marker longtext NOT NULL,
	status tinyint(2) unsigned DEFAULT '1',
	language_code varchar(2) DEFAULT '' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
    KEY email (email),
    KEY status (status),
    KEY queue_mail (queue_mail),
    KEY queue_mail_status (queue_mail,status),

);


#
# Table structure for table 'tx_rkwmailer_domain_model_statisticopening'
#
CREATE TABLE tx_rkwmailer_domain_model_statisticopening (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	queue_mail int(11) DEFAULT '0' NOT NULL,
	queue_recipient int(11) DEFAULT '0' NOT NULL,
	link int(11) DEFAULT '0' NOT NULL,
	pixel int(11) DEFAULT '0' NOT NULL,
	click_count int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY queue_mail (queue_mail),
	KEY link (link),
	KEY pixel (pixel),

);

#
# Table structure for table 'tx_rkwmailer_domain_model_link'
#
CREATE TABLE tx_rkwmailer_domain_model_link (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	queue_mail int(11) DEFAULT '0' NOT NULL,

	hash varchar(255) DEFAULT '' NOT NULL,
	url text NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
    UNIQUE KEY `hash` (`hash`),
	KEY parent (pid),
	KEY queue_mail (queue_mail),
);


#
# Table structure for table 'tx_rkwmailer_domain_model_bouncemail'
#
CREATE TABLE tx_rkwmailer_domain_model_bouncemail (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	status tinyint(3) unsigned DEFAULT '0' NOT NULL,
	type varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,

	rule_number int(11) unsigned DEFAULT '0' NOT NULL,
	rule_category varchar(255) DEFAULT '' NOT NULL,

	header text NOT NULL,
    body text NOT NULL,

    header_full longtext NOT NULL,
    body_full longtext NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
    KEY email (email),
    KEY status (status),
    KEY email_status (email, status),

);



#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (

    tx_rkwmailer_soft_bounce_count int(11) unsigned DEFAULT '0' NOT NULL,
    tx_rkwmailer_hard_bounce_count int(11) unsigned DEFAULT '0' NOT NULL,
    tx_rkwmailer_last_bounce int(11) DEFAULT '0' NOT NULL,
);
