-- drop the tables created during install
DROP TABLE IF EXISTS `civicrm_membership_renewal_batch`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_batch`;
DROP TABLE IF EXISTS `civicrm_membership_renewal_entity_batch`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_entity_batch`;
DROP TABLE IF EXISTS `civicrm_membership_renewal_entity_batch`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_entity_batch`;
DROP TABLE IF EXISTS `civicrm_membership_renewal_batch_files`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_batch_files`;

-- this is a new table where we store excluded membership renewal details, drop this table while uninstall 
DROP TABLE IF EXISTS `civicrm_membership_renewal_log`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_log`;

DROP TABLE IF EXISTS `civicrm_membership_renewal_details`;
DROP TABLE IF EXISTS `log_civicrm_membership_renewal_details`;

-- drop custom set and their fields
DROP TABLE IF EXISTS `civicrm_value_membership_renewal_information`;
DROP TABLE IF EXISTS `log_civicrm_value_membership_renewal_information`;
SELECT @custom_group_id := id from civicrm_custom_group where table_name = 'civicrm_value_membership_renewal_information';
DELETE FROM `civicrm_custom_field` WHERE custom_group_id = @custom_group_id;
DELETE FROM `civicrm_custom_group` WHERE table_name = 'civicrm_value_membership_renewal_information';

-- delete membership renewal settings
DELETE FROM `civicrm_setting` WHERE name = 'membership_renewal_settings';