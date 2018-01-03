-- create table to hold membership renewal batch details
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Name',
  `title` varchar(255) NOT NULL COMMENT 'Title',
  `created_id` int(11) unsigned NOT NULL COMMENT 'User ID who ran the renewal process',
  `created_date` datetime DEFAULT NULL COMMENT 'Date when the renewal process was run',
  `renewal_month_year` varchar(255) NOT NULL COMMENT 'Renewal month and year',
  `print_file_id` int(11) unsigned NULL COMMENT 'Print file id',
  `print_entity_file_id` int(11) unsigned NULL COMMENT 'Print entity file id',
  `first_reminder_date` datetime DEFAULT NULL COMMENT 'First reminder date same as created date',
  `second_reminder_date` datetime DEFAULT NULL COMMENT 'Second reminder date',
  `third_reminder_date` datetime DEFAULT NULL COMMENT 'Third reminder date',
	`is_test` tinyint(4) DEFAULT '0' COMMENT 'Test mode indication',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table to hold membership renewal batch details
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_entity_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(255) unsigned NOT NULL COMMENT 'Renewal Batch ID',
  `activity_id` int(255) unsigned NOT NULL COMMENT 'Activity ID',
  `reminder_type` int(255) unsigned NOT NULL COMMENT 'First or Second or Third Reminder',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table to hold membership renewal file details
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_batch_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(255) unsigned NOT NULL COMMENT 'Renewal Batch ID',
  `entity_file_id` int(255) unsigned NOT NULL COMMENT 'Entity File ID',
  `reminder_type` int(255) unsigned NOT NULL COMMENT 'First or Second or Third Reminder',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table to hold membership renewal dates for each membership
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_dates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `membership_id` int(10) unsigned NOT NULL COMMENT 'Membership Id',
  `membership_type_id` int(10) unsigned NOT NULL COMMENT 'Membership Type Id',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'Contact Id',
  `join_date` date DEFAULT NULL COMMENT 'Beginning of initial membership period (member since...).',
  `start_date` date DEFAULT NULL COMMENT 'Beginning of current uninterrupted membership period.',
  `end_date` date DEFAULT NULL COMMENT 'Current membership period expire date.',
  `renewal_date` date DEFAULT NULL COMMENT 'Current membership period renewal date.',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- temp table to process renewal batch
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `membership_id` int(255) unsigned NOT NULL COMMENT 'Membership ID',
  `membership_type_id` int(10) unsigned NOT NULL COMMENT 'Membership Type Id',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'Contact Id',
  `join_date` date DEFAULT NULL COMMENT 'Beginning of initial membership period (member since...).',
  `start_date` date DEFAULT NULL COMMENT 'Beginning of current uninterrupted membership period.',
  `end_date` date DEFAULT NULL COMMENT 'Current membership period expire date.',
  `renewal_date` date DEFAULT NULL COMMENT 'Current membership period renewal date.',
  `communication_type` varchar(255) DEFAULT NULL COMMENT 'Communication Yype',
  `reason` varchar(255) DEFAULT NULL COMMENT 'Do not process reason',
  `activity_id` int(255) unsigned NULL COMMENT 'Acitivity ID',
  `status` int(255) unsigned NULL COMMENT 'Active or not',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table to hold membership renewal logs
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(255) unsigned NOT NULL COMMENT 'Renewal Batch ID',
  `membership_id` int(255) unsigned NOT NULL COMMENT 'Membership ID',
  `membership_type_id` int(10) unsigned NOT NULL COMMENT 'Membership Type Id',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'Contact Id',
  `join_date` date DEFAULT NULL COMMENT 'Beginning of initial membership period (member since...).',
  `start_date` date DEFAULT NULL COMMENT 'Beginning of current uninterrupted membership period.',
  `end_date` date DEFAULT NULL COMMENT 'Current membership period expire date.',
  `renewal_date` date DEFAULT NULL COMMENT 'Current membership period renewal date.',
  `communication_type` varchar(255) DEFAULT NULL COMMENT 'Communication Yype',
  `reason` varchar(255) DEFAULT NULL COMMENT 'Do not process reason',
  `status` int(255) unsigned NULL COMMENT 'Processed or not',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;