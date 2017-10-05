-- create table to hold excluded from membership renewal details
CREATE TABLE IF NOT EXISTS `civicrm_membership_renewal_excluded_member_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int(255) unsigned NOT NULL COMMENT 'Renewal Batch ID',
  `membership_id` int(255) unsigned NOT NULL COMMENT 'Membership ID',
  `reason` varchar(255) DEFAULT NULL COMMENT 'Do not process reason',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;