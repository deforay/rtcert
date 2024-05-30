--ilahir 13-APR-2016
ALTER TABLE  `spi_form_v_3` ADD  `status` VARCHAR( 100 ) NULL DEFAULT  'pending';


--ilahir 23-Apr-2016

CREATE TABLE IF NOT EXISTS `spi_rt_3_facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` varchar(255) DEFAULT NULL,
  `` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

--ilahir 26-Apr-2016


CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `status`) VALUES
(1, 'SPI Form', 'active');


CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `role_code` varchar(255) DEFAULT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `description` mediumtext,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;


ALTER TABLE  `users` ADD  `first_name` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `id` ;
ALTER TABLE  `users` ADD  `last_name` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `first_name` ;
ALTER TABLE  `users` ADD  `status` VARCHAR( 255 ) NULL DEFAULT NULL ;
ALTER TABLE  `users` ADD  `created_on` DATETIME NULL DEFAULT NULL ;
ALTER TABLE  `users` ADD  `email` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `password` ;


CREATE TABLE IF NOT EXISTS `user_role_map` (
  `map_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`map_id`),
  KEY `role_id` (`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

--
-- Constraints for table `user_role_map`
--
ALTER TABLE `user_role_map`
  ADD CONSTRAINT `user_role_map_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_role_map_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--saravanna 03-may-2016
CREATE TABLE IF NOT EXISTS  `global_config` (
 `config_id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
 `display_name` VARCHAR( 255 ) NOT NULL ,
 `global_name` VARCHAR( 255 ) DEFAULT NULL ,
 `global_value` VARCHAR( 255 ) DEFAULT NULL ,
PRIMARY KEY (  `config_id` )
) ENGINE = MYISAM DEFAULT CHARSET = latin1 AUTO_INCREMENT =1;

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Auto Approve Status', 'approve_status', 'yes');



--ilahir 04-May-2016
CREATE TABLE IF NOT EXISTS `event_log` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `actor` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `resource_name` varchar(255) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `actor` (`actor`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

ALTER TABLE `event_log`
  ADD CONSTRAINT `event_log_ibfk_1` FOREIGN KEY (`actor`) REFERENCES `users` (`id`);

--ilahir 10-MAY-2016

CREATE TABLE IF NOT EXISTS `resources` (
  `resource_id` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `privileges` (
  `resource_id` varchar(255) NOT NULL DEFAULT '',
  `privilege_name` varchar(255) NOT NULL DEFAULT '',
  `display_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`resource_id`,`privilege_name`),
  UNIQUE KEY `resource_id_2` (`resource_id`,`privilege_name`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


INSERT INTO `resources` (`resource_id`, `display_name`) VALUES
('Application\\Controller\\Config', 'Global Config'),
('Application\\Controller\\Facility', 'Manage Facility'),
('Application\\Controller\\Index', 'Dashboard'),
('Application\\Controller\\Roles', 'Manage Roles'),
('Application\\Controller\\SpiV3', 'Manage SpiV3 Form'),
('Application\\Controller\\Users', 'Manage Users');


INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES
('Application\\Controller\\Config', 'edit-global', 'Edit'),
('Application\\Controller\\Config', 'index', 'Access'),
('Application\\Controller\\Facility', 'add', 'Add'),
('Application\\Controller\\Facility', 'edit', 'Edit'),
('Application\\Controller\\Facility', 'get-facility-name', 'Merge Facilities'),
('Application\\Controller\\Facility', 'index', 'Access'),
('Application\\Controller\\Index', 'index', 'Access'),
('Application\\Controller\\Roles', 'add', 'Add'),
('Application\\Controller\\Roles', 'edit', 'Edit'),
('Application\\Controller\\Roles', 'index', 'Access'),
('Application\\Controller\\SpiV3', 'approve-status', 'Approved Status'),
('Application\\Controller\\SpiV3', 'download-pdf', 'Download pdf'),
('Application\\Controller\\SpiV3', 'edit', 'Edit'),
('Application\\Controller\\SpiV3', 'index', 'Access'),
('Application\\Controller\\SpiV3', 'manage-facility', 'Access to edit SPI Form'),
('Application\\Controller\\Users', 'add', 'Add'),
('Application\\Controller\\Users', 'edit', 'Edit'),
('Application\\Controller\\Users', 'index', 'Access');

--Pal 12-MAY-2016
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Email', 'Manage Email');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Email', 'index', 'Access');


INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'corrective-action-pdf', 'Download corrective action pdf');

--Pal 13-MAY-2016
CREATE TABLE IF NOT EXISTS `temp_mail` (
  `temp_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_full_name` varchar(255) DEFAULT NULL,
  `from_mail` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) NOT NULL,
  `cc` varchar(500) DEFAULT NULL,
  `bcc` varchar(500) DEFAULT NULL,
  `subject` mediumtext,
  `message` mediumtext,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`temp_id`)
)

--Pal 17-MAY-2016
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'delete', 'Delete');


--Amit 17-MAY-2016
ALTER TABLE `spi_form_v_3` CHANGE `avgMonthTesting` `avgMonthTesting` INT NULL DEFAULT '0';


--Pal 17-MAY-2016
ALTER TABLE `spi_rt_3_facilities` ADD `status` VARCHAR(255) NOT NULL DEFAULT 'active' AFTER `longitude`;

--Pal 24th-Aug-2016
CREATE TABLE `user_token_map` (
  `user_id` int(11) NOT NULL,
  `token` varchar(45) NOT NULL
)

CREATE TABLE `audit_mails` (
  `mail_id` int(11) NOT NULL,
  `from_full_name` varchar(255) DEFAULT NULL,
  `from_mail` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) NOT NULL,
  `cc` varchar(500) DEFAULT NULL,
  `bcc` varchar(500) DEFAULT NULL,
  `subject` mediumtext,
  `message` mediumtext,
  `status` varchar(255) NOT NULL DEFAULT 'pending'
)

ALTER TABLE `audit_mails`
  ADD PRIMARY KEY (`mail_id`);

ALTER TABLE `audit_mails`
  MODIFY `mail_id` int(11) NOT NULL AUTO_INCREMENT;

--Pal 25th-Aug-2016
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Header', 'header', NULL), (NULL, 'Logo', 'logo', NULL);


--ilahir 16-NOV-2016
INSERT INTO `odkdash`.`global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Language', 'language', 'English');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Dashboard', 'Manage Dashboard');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Dashboard', 'index', 'Access'), ('Application\\Controller\\Dashboard', 'audi-details', 'Manage Audit details');
UPDATE `resources` SET `display_name` = 'Home' WHERE `resources`.`resource_id` = 'Application\\Controller\\Index';

ALTER TABLE `roles` ADD PRIMARY KEY(`role_id`);
ALTER TABLE `roles` ADD UNIQUE(`role_id`);
ALTER TABLE `roles` CHANGE `role_id` `role_id` INT(11) NOT NULL AUTO_INCREMENT;

--Pal 04-Apr-2017
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Facility', 'map-province', 'Map Province');

UPDATE `privileges` SET `privilege_name` = 'get-province-list' WHERE `privileges`.`resource_id` = 'Application\\Controller\\Facility' AND `privileges`.`privilege_name` = 'map-province';

--Pal 06-Apr-2017
CREATE TABLE `r_spi_form_v_3_download` (
  `r_download_id` int(11) NOT NULL,
  `auditroundno` varchar(255) DEFAULT NULL,
  `assesmentofaudit` varchar(255) DEFAULT NULL,
  `testingpointtype` varchar(255) DEFAULT NULL,
  `testingpointname` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `level_name` varchar(255) DEFAULT NULL,
  `AUDIT_SCORE_PERCANTAGE` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `download_status` int(11) NOT NULL DEFAULT '0'
);

ALTER TABLE `r_spi_form_v_3_download`
  ADD PRIMARY KEY (`r_download_id`);

ALTER TABLE `r_spi_form_v_3_download`
  MODIFY `r_download_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `r_spi_form_v_3_download` ADD `user` INT(11) NOT NULL AFTER `r_download_id`;

--saravanan 05-apr-2017
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'duplicate', 'Duplicate');

CREATE TABLE `spi_form_v_3_duplicate` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `formId` varchar(255) NOT NULL,
  `formVersion` varchar(255) NOT NULL,
  `meta-instance-id` varchar(255) NOT NULL,
  `meta-model-version` varchar(255) NOT NULL,
  `meta-ui-version` varchar(255) NOT NULL,
  `meta-submission-date` varchar(255) NOT NULL,
  `meta-is-complete` varchar(255) NOT NULL,
  `meta-date-marked-as-complete` varchar(255) NOT NULL,
  `start` varchar(255) DEFAULT NULL,
  `end` varchar(255) DEFAULT NULL,
  `today` date DEFAULT NULL,
  `deviceid` varchar(255) DEFAULT NULL,
  `subscriberid` varchar(255) DEFAULT NULL,
  `text_image` varchar(255) DEFAULT NULL,
  `info1` varchar(255) DEFAULT NULL,
  `info2` varchar(255) DEFAULT NULL,
  `assesmentofaudit` date NOT NULL,
  `auditroundno` varchar(255) DEFAULT NULL,
  `facilityname` varchar(255) DEFAULT NULL,
  `facilityid` varchar(255) DEFAULT NULL,
  `testingpointname` varchar(255) DEFAULT NULL,
  `testingpointtype` varchar(255) DEFAULT NULL,
  `testingpointtype_other` varchar(255) DEFAULT NULL,
  `locationaddress` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `level_other` varchar(255) DEFAULT NULL,
  `level_name` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `affiliation_other` varchar(255) DEFAULT NULL,
  `NumberofTester` varchar(255) DEFAULT NULL,
  `avgMonthTesting` int(11) DEFAULT '0',
  `name_auditor_lead` varchar(255) DEFAULT NULL,
  `name_auditor2` varchar(255) DEFAULT NULL,
  `info4` varchar(255) DEFAULT NULL,
  `INSTANCE` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_1` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_1` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_2` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_2` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_3` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_3` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_4` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_4` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_5` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_5` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_6` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_6` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_7` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_7` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_8` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_8` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_9` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_9` varchar(255) DEFAULT NULL,
  `PERSONAL_Q_1_10` varchar(255) DEFAULT NULL,
  `PERSONAL_C_1_10` varchar(255) DEFAULT NULL,
  `PERSONAL_SCORE` varchar(255) DEFAULT NULL,
  `PERSONAL_Display` varchar(255) DEFAULT NULL,
  `PERSONALPHOTO` varchar(255) DEFAULT NULL,
  `PHYSICAL_Q_2_1` varchar(255) DEFAULT NULL,
  `PHYSICAL_C_2_1` varchar(255) DEFAULT NULL,
  `PHYSICAL_Q_2_2` varchar(255) DEFAULT NULL,
  `PHYSICAL_C_2_2` varchar(255) DEFAULT NULL,
  `PHYSICAL_Q_2_3` varchar(255) DEFAULT NULL,
  `PHYSICAL_C_2_3` varchar(255) DEFAULT NULL,
  `PHYSICAL_Q_2_4` varchar(255) DEFAULT NULL,
  `PHYSICAL_C_2_4` varchar(255) DEFAULT NULL,
  `PHYSICAL_Q_2_5` varchar(255) DEFAULT NULL,
  `PHYSICAL_C_2_5` varchar(255) DEFAULT NULL,
  `PHYSICAL_SCORE` varchar(255) DEFAULT NULL,
  `PHYSICAL_Display` varchar(255) DEFAULT NULL,
  `PHYSICALPHOTO` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_1` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_1` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_2` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_2` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_3` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_3` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_4` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_4` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_5` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_5` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_6` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_6` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_7` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_7` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_8` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_8` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_9` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_9` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_10` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_10` varchar(255) DEFAULT NULL,
  `SAFETY_Q_3_11` varchar(255) DEFAULT NULL,
  `SAFETY_C_3_11` varchar(255) DEFAULT NULL,
  `SAFETY_SCORE` varchar(255) DEFAULT NULL,
  `SAFETY_DISPLAY` varchar(255) DEFAULT NULL,
  `SAFETYPHOTO` varchar(255) DEFAULT NULL,
  `PRE_Q_4_1` varchar(255) DEFAULT NULL,
  `PRE_C_4_1` varchar(255) DEFAULT NULL,
  `PRE_Q_4_2` varchar(255) DEFAULT NULL,
  `PRE_C_4_2` varchar(255) DEFAULT NULL,
  `PRE_Q_4_3` varchar(255) DEFAULT NULL,
  `PRE_C_4_3` varchar(255) DEFAULT NULL,
  `PRE_Q_4_4` varchar(255) DEFAULT NULL,
  `PRE_C_4_4` varchar(255) DEFAULT NULL,
  `PRE_Q_4_5` varchar(255) DEFAULT NULL,
  `PRE_C_4_5` varchar(255) DEFAULT NULL,
  `PRE_Q_4_6` varchar(255) DEFAULT NULL,
  `PRE_C_4_6` varchar(255) DEFAULT NULL,
  `PRE_Q_4_7` varchar(255) DEFAULT NULL,
  `PRE_C_4_7` varchar(255) DEFAULT NULL,
  `PRE_Q_4_8` varchar(255) DEFAULT NULL,
  `PRE_C_4_8` varchar(255) DEFAULT NULL,
  `PRE_Q_4_9` varchar(255) DEFAULT NULL,
  `PRE_C_4_9` varchar(255) DEFAULT NULL,
  `PRE_Q_4_10` varchar(255) DEFAULT NULL,
  `PRE_C_4_10` varchar(255) DEFAULT NULL,
  `PRE_Q_4_11` varchar(255) DEFAULT NULL,
  `PRE_C_4_11` varchar(255) DEFAULT NULL,
  `PRE_Q_4_12` varchar(255) DEFAULT NULL,
  `PRE_C_4_12` varchar(255) DEFAULT NULL,
  `PRETEST_SCORE` varchar(255) DEFAULT NULL,
  `PRETEST_Display` varchar(255) DEFAULT NULL,
  `PRETESTPHOTO` varchar(255) DEFAULT NULL,
  `TEST_Q_5_1` varchar(255) DEFAULT NULL,
  `TEST_C_5_1` varchar(255) DEFAULT NULL,
  `TEST_Q_5_2` varchar(255) DEFAULT NULL,
  `TEST_C_5_2` varchar(255) DEFAULT NULL,
  `TEST_Q_5_3` varchar(255) DEFAULT NULL,
  `TEST_C_5_3` varchar(255) DEFAULT NULL,
  `TEST_Q_5_4` varchar(255) DEFAULT NULL,
  `TEST_C_5_4` varchar(255) DEFAULT NULL,
  `TEST_Q_5_5` varchar(255) DEFAULT NULL,
  `TEST_C_5_5` varchar(255) DEFAULT NULL,
  `TEST_Q_5_6` varchar(255) DEFAULT NULL,
  `TEST_C_5_6` varchar(255) DEFAULT NULL,
  `TEST_Q_5_7` varchar(255) DEFAULT NULL,
  `TEST_C_5_7` varchar(255) DEFAULT NULL,
  `TEST_Q_5_8` varchar(255) DEFAULT NULL,
  `TEST_C_5_8` varchar(255) DEFAULT NULL,
  `TEST_Q_5_9` varchar(255) DEFAULT NULL,
  `TEST_C_5_9` varchar(255) DEFAULT NULL,
  `TEST_SCORE` varchar(255) DEFAULT NULL,
  `TEST_DISPLAY` varchar(255) DEFAULT NULL,
  `TESTPHOTO` varchar(255) DEFAULT NULL,
  `POST_Q_6_1` varchar(255) DEFAULT NULL,
  `POST_C_6_1` varchar(255) DEFAULT NULL,
  `POST_Q_6_2` varchar(255) DEFAULT NULL,
  `POST_C_6_2` varchar(255) DEFAULT NULL,
  `POST_Q_6_3` varchar(255) DEFAULT NULL,
  `POST_C_6_3` varchar(255) DEFAULT NULL,
  `POST_Q_6_4` varchar(255) DEFAULT NULL,
  `POST_C_6_4` varchar(255) DEFAULT NULL,
  `POST_Q_6_5` varchar(255) DEFAULT NULL,
  `POST_C_6_5` varchar(255) DEFAULT NULL,
  `POST_Q_6_6` varchar(255) DEFAULT NULL,
  `POST_C_6_6` varchar(255) DEFAULT NULL,
  `POST_Q_6_7` varchar(255) DEFAULT NULL,
  `POST_C_6_7` varchar(255) DEFAULT NULL,
  `POST_Q_6_8` varchar(255) DEFAULT NULL,
  `POST_C_6_8` varchar(255) DEFAULT NULL,
  `POST_Q_6_9` varchar(255) DEFAULT NULL,
  `POST_C_6_9` varchar(255) DEFAULT NULL,
  `POST_SCORE` varchar(255) DEFAULT NULL,
  `POST_DISPLAY` varchar(255) DEFAULT NULL,
  `POSTTESTPHOTO` varchar(255) DEFAULT NULL,
  `EQA_Q_7_1` varchar(255) DEFAULT NULL,
  `EQA_C_7_1` varchar(255) DEFAULT NULL,
  `EQA_Q_7_2` varchar(255) DEFAULT NULL,
  `EQA_C_7_2` varchar(255) DEFAULT NULL,
  `EQA_Q_7_3` varchar(255) DEFAULT NULL,
  `EQA_C_7_3` varchar(255) DEFAULT NULL,
  `EQA_Q_7_4` varchar(255) DEFAULT NULL,
  `EQA_C_7_4` varchar(255) DEFAULT NULL,
  `EQA_Q_7_5` varchar(255) DEFAULT NULL,
  `EQA_C_7_5` varchar(255) DEFAULT NULL,
  `EQA_Q_7_6` varchar(255) DEFAULT NULL,
  `EQA_C_7_6` varchar(255) DEFAULT NULL,
  `EQA_Q_7_7` varchar(255) DEFAULT NULL,
  `EQA_C_7_7` varchar(255) DEFAULT NULL,
  `EQA_Q_7_8` varchar(255) DEFAULT NULL,
  `EQA_C_7_8` varchar(255) DEFAULT NULL,
  `sampleretesting` varchar(255) DEFAULT NULL,
  `EQA_Q_7_9` varchar(255) DEFAULT NULL,
  `EQA_C_7_9` varchar(255) DEFAULT NULL,
  `EQA_Q_7_10` varchar(255) DEFAULT NULL,
  `EQA_C_7_10` varchar(255) DEFAULT NULL,
  `EQA_Q_7_11` varchar(255) DEFAULT NULL,
  `EQA_C_7_11` varchar(255) DEFAULT NULL,
  `EQA_Q_7_12` varchar(255) DEFAULT NULL,
  `EQA_C_7_12` varchar(255) DEFAULT NULL,
  `EQA_Q_7_13` varchar(255) DEFAULT NULL,
  `EQA_C_7_13` varchar(255) DEFAULT NULL,
  `EQA_Q_7_14` varchar(255) DEFAULT NULL,
  `EQA_C_7_14` varchar(255) DEFAULT NULL,
  `EQA_MAX_SCORE` varchar(255) DEFAULT NULL,
  `EQA_REQ` varchar(255) DEFAULT NULL,
  `EQA_OPT` varchar(255) DEFAULT NULL,
  `EQA_SCORE` varchar(255) DEFAULT NULL,
  `EQA_DISPLAY` varchar(255) DEFAULT NULL,
  `EQAPHOTO` varchar(255) DEFAULT NULL,
  `FINAL_AUDIT_SCORE` varchar(255) DEFAULT NULL,
  `MAX_AUDIT_SCORE` varchar(255) DEFAULT NULL,
  `AUDIT_SCORE_PERCANTAGE` varchar(255) DEFAULT NULL,
  `staffaudited` varchar(255) DEFAULT NULL,
  `durationaudit` varchar(255) DEFAULT NULL,
  `personincharge` varchar(255) DEFAULT NULL,
  `endofsurvey` varchar(255) DEFAULT NULL,
  `info5` varchar(255) DEFAULT NULL,
  `info6` varchar(255) DEFAULT NULL,
  `info10` varchar(255) DEFAULT NULL,
  `info11` varchar(255) DEFAULT NULL,
  `summarypage` varchar(255) DEFAULT NULL,
  `SUMMARY_NOT_AVL` varchar(255) DEFAULT NULL,
  `info12` varchar(255) DEFAULT NULL,
  `info17` varchar(255) DEFAULT NULL,
  `info21` varchar(255) DEFAULT NULL,
  `info22` varchar(255) DEFAULT NULL,
  `info23` varchar(255) DEFAULT NULL,
  `info24` varchar(255) DEFAULT NULL,
  `info25` varchar(255) DEFAULT NULL,
  `info26` varchar(255) DEFAULT NULL,
  `info27` varchar(255) DEFAULT NULL,
  `correctiveaction` text,
  `sitephoto` varchar(255) DEFAULT NULL,
  `Latitude` varchar(255) DEFAULT NULL,
  `Longitude` varchar(255) DEFAULT NULL,
  `Altitude` varchar(255) DEFAULT NULL,
  `Accuracy` varchar(255) DEFAULT NULL,
  `auditorSignature` text,
  `instanceID` varchar(255) DEFAULT NULL,
  `instanceName` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'pending'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `spi_form_v_3_duplicate`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `spi_form_v_3_duplicate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


--Pal 06-Apr-2017
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'download-files', 'Download Zipped Files');

ALTER TABLE `r_spi_form_v_3_download` DROP `file_name`;

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'view-data', 'Dashboard - View Details');

--saravnana 24-may-2017
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Facility', 'export-facility', 'Export Facilities');
--Pal 06-Jun-2017
ALTER TABLE `spi_form_v_3` ADD `facility` INT(11) NULL DEFAULT NULL AFTER `auditroundno`;
--saravanna 21-jun-2017
INSERT INTO `odkdash`.`privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\SpiV3', 'spiv3-import-csv-file', 'SPIV3 Import CSV');

--Pal 27-Jun-2017
ALTER TABLE `spi_form_v_3_duplicate` DROP `facilityname`;

ALTER TABLE `spi_form_v_3` DROP `facilityname`;

ALTER TABLE `spi_form_v_3` DROP `facilityid`;

ALTER TABLE `spi_form_v_3_duplicate` CHANGE `facilityid` `facility` INT(11) NULL DEFAULT NULL;

ALTER TABLE `spi_form_v_3` ADD `facilityid` INT(11) NULL DEFAULT NULL AFTER `facility`, ADD `facilityname` VARCHAR(500) NULL DEFAULT NULL AFTER `facilityid`;

ALTER TABLE `spi_form_v_3_duplicate` ADD `facilityid` INT(11) NULL DEFAULT NULL AFTER `facility`, ADD `facilityname` VARCHAR(500) NULL DEFAULT NULL AFTER `facilityid`;


-- Amit 03 Jan 2018
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\CertificationMail', 'index', 'Access');

-- Pal 09 Jan 2018
ALTER TABLE `roles` ADD `access_level` INT(11) NULL DEFAULT NULL AFTER `role_name`;

CREATE TABLE `country` (
  `country_id` int(11) NOT NULL,
  `country_name` varchar(45) DEFAULT NULL,
  `country_code` varchar(45) DEFAULT NULL,
  `country_status` varchar(45) NOT NULL DEFAULT 'active'
)

ALTER TABLE `country`
  ADD PRIMARY KEY (`country_id`);

ALTER TABLE `country`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT

CREATE TABLE `location_details` (
  `location_id` int(11) NOT NULL,
  `parent_location` int(11) DEFAULT '0',
  `location_name` varchar(255) DEFAULT NULL,
  `location_code` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `country` int(11) NOT NULL
)

ALTER TABLE `location_details`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `country` (`country`);

ALTER TABLE `location_details`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT

alter table location_details add FOREIGN key(country) REFERENCES country(country_id)

CREATE TABLE `user_country_map` (
  `map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL
)

ALTER TABLE `user_country_map`
  ADD PRIMARY KEY (`map_id`);

ALTER TABLE `user_country_map`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT

CREATE TABLE `user_province_map` (
  `map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
)

ALTER TABLE `user_province_map`
  ADD PRIMARY KEY (`map_id`);

ALTER TABLE `user_province_map`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT

CREATE TABLE `user_district_map` (
  `map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
)

ALTER TABLE `user_district_map`
  ADD PRIMARY KEY (`map_id`);

ALTER TABLE `user_district_map`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT

 -- Pal 11 Jan 2018
alter table provider drop FOREIGN key fk_provider_district

alter table provider drop FOREIGN key fk_provider_region

-- Pal 12 Jan 2018
alter table certification_facilities drop FOREIGN key fk_facility_district


-- Pal 16 Jan 2018

INSERT INTO `roles` (`role_id`, `category_id`, `role_code`, `role_name`, `access_level`, `description`, `status`) VALUES
(1, 1, 'AD', 'Super Admin', 1, NULL, 'active'),
(2, 1, 'CM', 'Country Manager', 2, NULL, 'active'),
(3, 1, 'DM', 'Data Manager', 3, NULL, 'active'),
(4, 1, 'DEO', 'Data Entry Operator', 3, NULL, 'active');

-- Pal 17 Jan 2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Months certification is valid', 'month-validity', NULL);

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Flex limit of months allowed for recertification after validity period', 'month-flex-limit', NULL), (NULL, 'Number of months allowed to administer exam prior to recertification', 'month-prior', NULL);

UPDATE `global_config` SET `global_name` = 'month-prior-to-certification' WHERE `global_config`.`config_id` = 9;

UPDATE `global_config` SET `global_name` = 'month-valid' WHERE `global_config`.`config_id` = 7;

-- Pal 24 Jan 2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Certificate Prefix', 'certificate-prefix', 'RTCERT');

ALTER TABLE `provider` ADD `certification_key` INT(11) NULL DEFAULT '0' AFTER `test_site_in_charge`;

ALTER TABLE `provider` CHANGE `certification_key` `certification_key` VARCHAR(45) NULL DEFAULT NULL;

ALTER TABLE `provider` CHANGE `certification_key` `certification_key` INT(11) NULL DEFAULT NULL;

-- Pal 21 Feb 2018
ALTER TABLE `certification` ADD `approval_status` VARCHAR(45) NULL DEFAULT NULL AFTER `examination`;

-- saravanan 21-Feb-2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Latitude', 'latitude', NULL), (NULL, 'Longitude', 'longitude', NULL);

--ilahir 22-Feb-2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Region', 'region', 'Region');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Districts', 'districts', 'Districts'), (NULL, 'Facilities', 'facilities', 'Facilities');

-- Pal 22 Feb 2018
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Certification', 'approval', 'Approval');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Certification', 'recommend', 'Recommend');
-- saravanan 23-feb-2018
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Examination', 'pending', 'Pending tests');

-- Pal 04 APR 2018
ALTER TABLE `certification_facilities` ADD `latitude` VARCHAR(255) NULL DEFAULT NULL AFTER `district`, ADD `longitude` VARCHAR(255) NULL DEFAULT NULL AFTER `latitude`;

ALTER TABLE `certification_facilities` ADD `contact_person_name` VARCHAR(255) NULL DEFAULT NULL AFTER `facility_name`, ADD `phone_no` VARCHAR(15) NULL DEFAULT NULL AFTER `contact_person_name`, ADD `email_id` VARCHAR(45) NULL DEFAULT NULL AFTER `phone_no`;

UPDATE `global_config` SET `display_name` = 'Certification No. Prefix' WHERE `global_config`.`config_id` = 10;

-- Pal 05 APR 2018
ALTER TABLE `written_exam` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `display`, ADD `added_by` INT(11) NULL DEFAULT NULL AFTER `added_on`, ADD `updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `updated_by` INT(11) NULL DEFAULT NULL AFTER `updated_on`;

ALTER TABLE `practical_exam` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `display`, ADD `added_by` INT(11) NULL DEFAULT NULL AFTER `added_on`, ADD `updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `updated_by` INT(11) NULL DEFAULT NULL AFTER `updated_on`;

ALTER TABLE `certification` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `date_end_validity`, ADD `added_by` INT(11) NULL DEFAULT NULL AFTER `added_on`, ADD `last_updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `last_updated_by` INT(11) NULL DEFAULT NULL AFTER `last_updated_on`;

DELETE FROM `global_config` WHERE `global_config`.`config_id` = 2

-- Pal 10 APR 2018
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Dashboard', 'testers', 'Manage Certificate Mail');

UPDATE `privileges` SET `display_name` = 'Manage Tester Mail' WHERE `privileges`.`resource_id` = 'Application\\Controller\\Dashboard' AND `privileges`.`privilege_name` = 'testers';

UPDATE `privileges` SET `display_name` = 'Tester List' WHERE `privileges`.`resource_id` = 'Application\\Controller\\Dashboard' AND `privileges`.`privilege_name` = 'testers';


-- Pal 17 APR 2018
INSERT INTO `roles` (`role_id`, `category_id`, `role_code`, `role_name`, `access_level`, `description`, `status`) VALUES
(1, 1, 'AD', 'Admin', 1, NULL, 'active'),
(2, 1, 'NCC', 'National Certification Coordinator', 2, '', 'active'),
(3, 1, 'NCDM', 'National Certification Data Manager', 2, '', 'active'),
(4, 1, 'DDM', 'District Data Manager', 4, '', 'active'),
(5, 1, 'DC', 'District Coordinator', 4, '', 'active');


ALTER TABLE `provider` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `certification_key`, ADD `added_by` INT(11) NULL DEFAULT NULL AFTER `added_on`, ADD `last_updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `last_updated_by` INT(11) NULL DEFAULT NULL AFTER `last_updated_on`;

--Selvam 19-Jun-2019
ALTER TABLE `pdf_header_texte`  ADD `header_font_size` VARCHAR(255) NULL  AFTER `header_texte`;

--Selvam 20-Jun-2019

ALTER TABLE `global_config` ADD UNIQUE(`global_name`);

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`)
VALUES (NULL, 'Registrar Name', 'registrar-name', NULL);

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`)
VALUES (NULL, 'Registrar Title', 'registrar-title', NULL);

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`)
VALUES (NULL, 'Registrar Digital Signature', 'registrar-digital-signature', NULL);

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`)
VALUES (NULL, 'Translate Register Title', 'translate-register-title', NULL);

-- Thanaseelan 03 Feb, 2020
INSERT INTO `resources` (`resource_id`, `display_name`)
VALUES ('Application\\Controller\\TestConfig', 'Online Test Config'),
('Application\\Controller\\TestSection', 'Online Question Categories'),
('Application\\Controller\\TestQuestion', 'Online Tests');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`)
VALUES ('Application\\Controller\\TestConfig', 'index', 'Access'), ('Application\\Controller\\TestConfig', 'edit', 'Edit'),
('Application\\Controller\\TestSection', 'index', 'Access'), ('Application\\Controller\\TestSection', 'add', 'Add'),('Application\\Controller\\TestSection', 'edit', 'Edit'),
('Application\\Controller\\TestQuestion', 'index', 'Access'),('Application\\Controller\\TestQuestion', 'add', 'Add'), ('Application\\Controller\\TestQuestion', 'edit', 'Edit');

CREATE TABLE `test_config` (
 `config_id` int(11) NOT NULL AUTO_INCREMENT,
 `display_name` varchar(255) NOT NULL,
 `test_config_name` varchar(255) NOT NULL,
 `test_config_value` varchar(255) NOT NULL,
 PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO `test_config` (`config_id`, `display_name`, `test_config_name`, `test_config_value`) VALUES
(NULL, 'Passing Percentage', 'passing-percentage', '80'),
(NULL, 'Maximum question per test', 'maximum-question-per-test', '80'),
(NULL, 'Allow Retest', 'allow-retest', '80'),
(NULL, 'Months certification is valid', 'month-valid', '48');

CREATE TABLE `test_sections` (
 `section_id` int(11) NOT NULL AUTO_INCREMENT,
 `section_name` varchar(255) DEFAULT NULL,
 `status` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

CREATE TABLE `test_questions` (
 `question_id` int(11) NOT NULL AUTO_INCREMENT,
 `question` mediumtext,
 `section` int(11) DEFAULT NULL,
 `status` varchar(255) DEFAULT NULL,
 `correct_option` varchar(255) DEFAULT NULL,
 `correct_option_text` text,
 PRIMARY KEY (`question_id`),
 KEY `section` (`section`),
 CONSTRAINT `test_questions_ibfk_1` FOREIGN KEY (`section`) REFERENCES `test_sections` (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1;

CREATE TABLE `test_options` (
 `option_id` int(11) NOT NULL AUTO_INCREMENT,
 `question` int(11) DEFAULT NULL,
 `option` mediumtext,
 `status` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`option_id`),
 KEY `question` (`question`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=latin1;

CREATE TABLE `tests` (
 `test_id` int(11) NOT NULL AUTO_INCREMENT,
 `pretest_start_datetime` datetime NOT NULL,
 `pretest_end_datetime` datetime DEFAULT NULL,
 `pre_test_score` varchar(255) DEFAULT NULL,
 `pre_test_status` varchar(255) DEFAULT 'not started',
 `posttest_start_datetime` datetime DEFAULT NULL,
 `posttest_end_datetime` datetime DEFAULT NULL,
 `post_test_score` varchar(22) DEFAULT NULL,
 `post_test_status` varchar(255) NOT NULL DEFAULT 'not started',
 `user_id` int(11) NOT NULL,
 `certificate_no` varchar(255) DEFAULT NULL,
 `user_test_status` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`test_id`),
 KEY `user_foreign_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;

CREATE TABLE `pretest_questions` (
 `pre_test_id` int(11) NOT NULL AUTO_INCREMENT,
 `test_id` int(11) NOT NULL,
 `question_id` int(11) NOT NULL,
 `question_text` varchar(255) DEFAULT NULL,
 `response_id` varchar(255) DEFAULT NULL,
 `response_text` text,
 `score` varchar(255) DEFAULT NULL,
 `pre_test_status` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`pre_test_id`),
 KEY `pre_question_foreign_id` (`question_id`),
 KEY `pre_response_foreign_id` (`response_id`),
 KEY `pre_test_id` (`test_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1;

CREATE TABLE `posttest_questions` (
 `post_test_id` int(11) NOT NULL AUTO_INCREMENT,
 `test_id` int(11) NOT NULL,
 `question_id` int(11) NOT NULL,
 `question_text` varchar(255) DEFAULT NULL,
 `response_id` varchar(255) DEFAULT NULL,
 `response_text` text,
 `score` varchar(255) DEFAULT NULL,
 `post_test_status` int(11) DEFAULT NULL,
 PRIMARY KEY (`post_test_id`),
 KEY `post_test_id` (`test_id`),
 KEY `post_question_foreign_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=latin1;

-- 04 Mar, 2020
ALTER TABLE `provider` ADD `username` VARCHAR(255) NOT NULL AFTER `time_worked`, ADD `password` VARCHAR(255) NOT NULL AFTER `username`;
-- 05 Mar, 2020
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Test', 'Online Tests'), ('', NULL);
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Test', 'index', 'Access'), ('Application\\Controller\\Test', 'result', 'Test Result'), ('Application\\Controller\\Test', 'intro', 'Test Intro Page');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Provider', 'login', 'Login'), ('Certification\\Controller\\Provider', 'logout', 'Logout');
-- 06 Mar, 2020
INSERT INTO `test_config` (`config_id`, `display_name`, `test_config_name`, `test_config_value`) VALUES (NULL, 'Link Expire Hour', 'link-expire', '48');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Provider', 'send-test-link', 'Send Test Link');
ALTER TABLE `provider` ADD `link_send_count` INT(11) NOT NULL AFTER `last_updated_by`, ADD `link_send_on` DATETIME NOT NULL AFTER `link_send_count`, ADD `link_send_by` INT(11) NOT NULL AFTER `link_send_on`, ADD `link_token` INT(11) NOT NULL AFTER `link_send_by`;
-- Thana 12 Mar 2020
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Provider', 'frequency-question', 'Frequency Question'), ('Certification\\Controller\\Provider', 'test-frequency', 'Test Frequency'), ('Certification\\Controller\\Provider', 'certificate-pdf', 'Certificate Pdf');
-- Thana 31 Mar, 2020
ALTER TABLE `test_sections` ADD `section_slug` VARCHAR(255) NULL DEFAULT NULL AFTER `section_name`;
ALTER TABLE `written_exam` ADD `test_id` INT(11) NULL DEFAULT NULL AFTER `id_written_exam`;
-- Thana 01 Apr, 2020
ALTER TABLE `pretest_questions` CHANGE `question_text` `question_text` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `test_questions` CHANGE `question` `question` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `test_options` CHANGE `option` `option` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
-- Thana 08 Apr, 2020
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\PrintTestPdf', 'Print Test PDF');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\PrintTestPdf', 'view-pdf-question', 'View PDF Questions'), ('Application\\Controller\\PrintTestPdf', 'index', 'Access'), ('Application\\Controller\\PrintTestPdf', 'add', 'Add New Test'), ('Application\\Controller\\PrintTestPdf', 'print-pdf-question', 'Print PDF Questions');
-- Thana 09 Apr, 2020
CREATE TABLE `print_test_pdf` (
 `ptp_id` int NOT NULL AUTO_INCREMENT,
 `ptp_title` text,
 `ptp_no_participants` int DEFAULT NULL,
 `ptp_variation` int DEFAULT NULL,
 `ptp_create_on` datetime DEFAULT NULL,
 `ptp_create_by` int DEFAULT NULL,
 PRIMARY KEY (`ptp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `print_test_pdf_details` (
 `ptpd_id` int NOT NULL AUTO_INCREMENT,
 `ptp_id` int DEFAULT NULL,
 `variant_no` int DEFAULT NULL,
 `question_id` int DEFAULT NULL,
 `response_id` int DEFAULT NULL,
 PRIMARY KEY (`ptpd_id`),
 KEY `ptp_id` (`ptp_id`),
 KEY `question_id` (`question_id`),
 KEY `response_id` (`response_id`),
 CONSTRAINT `print_test_pdf_details_ibfk_1` FOREIGN KEY (`ptp_id`) REFERENCES `print_test_pdf` (`ptp_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `print_test_pdf_details_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `test_questions` (`question_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT `print_test_pdf_details_ibfk_3` FOREIGN KEY (`response_id`) REFERENCES `test_options` (`option_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Thana 14 Apr, 2020
ALTER TABLE `test_questions` ADD `question_code` VARCHAR(50) NULL DEFAULT NULL AFTER `question_id`;
ALTER TABLE `training_organization` ADD `abbreviation` TEXT NULL DEFAULT NULL AFTER `training_organization_name`;
ALTER TABLE `training_organization` ADD `address` TEXT NULL DEFAULT NULL AFTER `type_organization`, ADD `phone` VARCHAR(255) NULL DEFAULT NULL AFTER `address`;
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\PrintTestPdf', 'edit', 'Edit Title'), ('Application\\Controller\\PrintTestPdf', 'change-status', 'Change Status');
-- Thana 15 Apr, 2020
ALTER TABLE `print_test_pdf` ADD `ptp_status` VARCHAR(25) NOT NULL DEFAULT 'active' COMMENT 'active and inactive status' AFTER `ptp_create_by`;
ALTER TABLE `print_test_pdf_details` ADD `unique_id` VARCHAR(50) NULL DEFAULT NULL AFTER `response_id`;
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\PrintTestPdf', 'answer-key-one', 'Answer Key One'), ('Application\\Controller\\PrintTestPdf', 'answer-key-two', 'Answer Key Two'), ('Application\\Controller\\PrintTestPdf', 'examination', 'Examination Sheet');
-- Thana 22 Apr, 2020
ALTER TABLE `test_sections` ADD `section_description` TEXT NULL DEFAULT NULL AFTER `section_slug`;
CREATE TABLE `test_config_details` (
 `id` int NOT NULL AUTO_INCREMENT,
 `section_id` int DEFAULT NULL,
 `no_of_questions` varchar(50) DEFAULT NULL,
 `percentage` varchar(50) DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `section_id` (`section_id`),
 CONSTRAINT `test_config_details_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `test_sections` (`section_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10;
-- Thana 22 May, 2020
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Certificate expiry days for alert', 'certificate-alert-days', '25');
ALTER TABLE `provider` ADD `test_link_send` VARCHAR(10) NOT NULL DEFAULT 'no' AFTER `link_token`;

-- Thana 04 Jun, 2020
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\MailTemplate', 'Mail Template');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\MailTemplate', 'index', 'Access Template'), ('Application\\Controller\\MailTemplate', 'add', 'Add New Template'), ('Application\\Controller\\MailTemplate', 'edit', 'Edit Template');
CREATE TABLE `mail_template` (
 `mail_temp_id` int NOT NULL AUTO_INCREMENT,
 `mail_purpose` varchar(255) NOT NULL,
 `from_name` varchar(255) DEFAULT NULL,
 `mail_from` varchar(255) DEFAULT NULL,
 `mail_subject` varchar(255) DEFAULT NULL,
 `mail_content` text,
 `mail_footer` text,
 PRIMARY KEY (`mail_temp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
-- Thana 08 Jun, 2020
ALTER TABLE `mail_template` ADD `mail_status` VARCHAR(50) NULL DEFAULT 'inactive' AFTER `mail_footer`;

ALTER TABLE `mail_template` ADD `mail_title` VARCHAR(255) NULL DEFAULT NULL AFTER `mail_temp_id`;
-- Thana 09 Jun, 2020
ALTER TABLE `provider` ADD `test_mail_send` VARCHAR(20) NOT NULL DEFAULT 'no' AFTER `test_link_send`;

INSERT INTO `mail_template` (`mail_temp_id`, `mail_title`, `mail_purpose`, `from_name`, `mail_from`, `mail_subject`, `mail_content`, `mail_footer`, `mail_status`) VALUES
(7, 'Send Online Test Link', 'send-online-test-link', 'RTCQI Certification Team', 'thanaseelan@deforay.com', 'RTCQI Online examination Invitation', 'Hi, <b>##USER##<br></b><div><span><br>You are invited to attend online Safety Familiarization for Clinicians Test.<br><br></span><div><span>To attend the test  <b>##URL## </b></span>or copy and paste the URL <b>##URLWITHOUTLINK##</b> in the browser.<br><br><br><div><span>Regards,<br></span><div><b>##COUNTRY##</b> RT Certification Team</div></div></div></div>', '<div><span><i><br>This is an auto-generated email, please don\'t reply to this email address.</i></span></div>', 'active'),
(2, 'Online Test Mail Fail', 'online-test-mail-fail', 'RTCQI', 'thanaseelan@deforay.com', 'Online Test Results', 'Dear<b> ##USER## ,<br><br></b>Thank you for completing the <b>##TESTNAME##</b><span><br><br></span>You did not meet the minimum score criteria to clear this test. If you would like to take this test again, please contact the test provider<br><br><br>Regards,<br><b>RTCQI</b> Certification Team<b><br></b>', '<br><br>This is an auto-generated email, please don\'t reply to this email address.<b><br></b><br>', 'active'),
(4, 'Online Test Mail Pass', 'online-test-mail-pass', 'RTCQI Certification Team', 'thanaseelan@deforay.com', 'RTCQI Online Test Results', 'Dear<b> ##USER## ,<br><br></b>Thank you for completing the <b>##TESTNAME##</b><br><br>You have scored <b>##SCORE##</b><br><br><br>Regards,<br><b>RTCQI</b> Certification Team<b><br></b>', '<br><br>This is an auto-generated email, please don\'t reply to this email address.<b><br></b><br>', 'active'),
(6, 'Certificate Reminder', 'certificate-reminder', 'RTCQI Certification Team', 'thanaseelan@deforay.com', 'RTCQI Certificate Reminder', '<div><span>Dear <b>##USER##,<br><br></b></span><div>Your RTCQI Certificate <b>##CERTIFICATE_NUMBER##</b> <span>is expiring on <b>##CERTIFICATE_EXPIRY_DATE##<br></b></span><div><span><br>To renew your certificate, you will have to appear for the written and practical tests again.</span></div></div><div>We are sending you the link to the written test. <i>(</i><i>Please click the following link or copy and paste in your browser address bar)<br></i><b><br>##URLWITHOUTLINK##<br></b><br><div>Please note that this link can be used only once and will expire in <b>##EXPIRY_HOURS##</b> hours<br><div><span>If you have any questions, please feel free to reach out to us.<br><br></span><div><span>Thanks and Regards,<br></span><div><span><b>##COUNTRY##</b> RTCQI Tester Certification Team</span></div></div></div></div></div></div>', '<div><span><i>This is an auto-generated email, please don\'t reply to this email address.</i></span></div>', 'active');
-- Thana 10 Jun, 2020
INSERT INTO `test_config` (`config_id`, `display_name`, `test_config_name`, `test_config_value`) VALUES (NULL, 'Test Name Title', 'test-name', 'Online RTCQI Test');
-- Thana 11 Jun, 2020
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Examination', 'xls', 'Export Data');
INSERT INTO `test_config` (`config_id`, `display_name`, `test_config_name`, `test_config_value`) VALUES (NULL, 'Test Name Title', 'test-name', 'Online RTCQI Test');
-- Sivakumar  12 Jun, 2020
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Certification', 'certification-expiry', 'Certification Expiry');
-- Thana 15 Jun, 2020
ALTER TABLE `provider` ADD `profile_picture` VARCHAR(50) NULL DEFAULT NULL AFTER `test_mail_send`;
ALTER TABLE `provider` CHANGE `link_send_count` `link_send_count` INT NULL DEFAULT NULL, CHANGE `link_send_on` `link_send_on` DATETIME NULL DEFAULT NULL, CHANGE `link_send_by` `link_send_by` INT NULL DEFAULT NULL, CHANGE `link_token` `link_token` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
-- Thana 16 Jun, 2020
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Certification\\Controller\\Provider', 'import-excel', 'Bulk Import Testers');
-- Sivakumar 25 Jun, 2020
ALTER TABLE `written_exam` ADD `training_id` INT(11) NULL DEFAULT NULL AFTER `display`;
ALTER TABLE `practical_exam` ADD `training_id` INT(11) NULL DEFAULT NULL AFTER `display`;

ALTER TABLE `written_exam` CHANGE `training_id` `training_id` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `practical_exam` CHANGE `training_id` `training_id` VARCHAR(255) NULL DEFAULT NULL;

-- Sivakumar 29 Jun, 2020
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Feedback send mailid', 'feedback-send-mailid', 'thanaseelan@deforay.com')
CREATE TABLE `feedback_mail` (
 `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
 `feedback_name` varchar(255) NOT NULL,
 `feedback_email` varchar(255) NOT NULL,
 `feedback_subject` varchar(255) NOT NULL,
 `feedback_message` longtext NOT NULL,
 `added_on` datetime NOT NULL,
 PRIMARY KEY (`feedback_id`)
);
-- Thana 3-Aug-2020
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Show Tester Photo In Certificate', 'show-tester-photo-in-certificate', 'no');

-- Brindha 09-June-2023
INSERT INTO `mail_template` (`mail_temp_id`, `mail_title`, `mail_purpose`, `from_name`, `mail_from`, `mail_subject`, `mail_content`, `mail_footer`, `mail_status`) VALUES (9, 'Send Certificate', 'send-certificate', 'RTCQI PERSONNEL CERTIFICATION PROGRAM', NULL, 'HIV Tester Certificate of Competency', '<div>Congratulations ##USER##! You have successfully fulfilled the
requirements of the national HIV tester certification program and are
deemed competent to perform HIV  Rapid Testing.  This certificate of
competency is delivered to you for a two year period from the date of
issuance.

Important Note!!!

This certificate is only issued for HIV Rapid Testing and does not allow
 to perform any other test.

Note for printing the certificate!!!
To print this certificate ensure that the paper size selected by the
printer is A4 and that the orientation is landscape</div>', NULL, 'active');

INSERT INTO `mail_template` (`mail_temp_id`, `mail_title`, `mail_purpose`, `from_name`, `mail_from`, `mail_subject`, `mail_content`, `mail_footer`, `mail_status`) VALUES (10, 'Send Reminder', 'send-reminder', 'RTCQI PERSONNEL CERTIFICATION PROGRAM', NULL, 'HIV Tester Certificate Reminder', '<div>This is a reminder that your HIV tester certificate will expire on ##CERTIFICATE_EXPIRY_DATE## . Please contact your national certification organization to schedule both the written and practical examinations. Any delay in completing these assessments will automatically result in the withdrawal of your certificate.</div>', NULL, 'active');

-- Brindha 09-Apr-2024
UPDATE resources SET resource_id = CONCAT(resource_id , 'Controller') where resource_id NOT LIKE '%Controller';
UPDATE privileges SET resource_id = CONCAT(resource_id , 'Controller') where resource_id NOT LIKE '%Controller';

-- Brindha 15-Apr-2024
UPDATE `mail_template` SET `mail_subject` = 'HIV Tester Certificate of Competency for ##USER##' WHERE `mail_template`.`mail_purpose` = 'send-certificate';
UPDATE `mail_template` SET `mail_subject` = 'HIV Tester Certificate Reminder for ##USER##' WHERE `mail_template`.`mail_purpose` = 'send-reminder';


-- Amit 17-Apr-2024

ALTER TABLE `global_config` CHANGE `config_id` `config_id` INT NOT NULL AUTO_INCREMENT, CHANGE `display_name` `display_name` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, CHANGE `global_name` `global_name` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL, CHANGE `global_value` `global_value` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
ALTER TABLE `global_config` ADD UNIQUE(`global_name`);

-- Brindha 17-Apr-2024
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Dashboard Content', 'dashboard-content', '<span>Dear <b>USER, <br><br></b></span><div> RTCQI <b>CERT_NUMBER </b> <br><span>is expiring on <b>EXPIRY_DATE <br></b></span></div>Thanks.');

-- Brindha 23-Apr-2024
INSERT INTO `roles` (`role_id`, `category_id`, `role_code`, `role_name`, `access_level`, `description`, `status`) VALUES (NULL, '1', 'PARTNER', 'Partner Organization', '1', '', 'active');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Written Exam Passing Score', 'written_passing_score', '80');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Practical Exam - Direct Observation Passing Score', 'practical_direct_observation_passing_score', '90');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Practical Exam - Sample Testing Passing Score', 'practical_sample_testing_passing_score', '100');

-- Brindha 10-May-2024
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Certificate Text', 'certificate-text', 'Certificate of Competency');

-- Brindha 14-May-2024
INSERT INTO `country` (`country_id`, `country_name`, `country_code`, `country_status`) VALUES (NULL, 'Healthland', 'HL', 'active');

-- Brindha 16-May-2024
ALTER TABLE roles
ADD CONSTRAINT role_name UNIQUE (role_name),
ADD CONSTRAINT role_code UNIQUE (role_code);

-- ilahir 30-May-2024
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Dashboard Map Zoom Level', 'dashboard_map_zoomlevel', '8');