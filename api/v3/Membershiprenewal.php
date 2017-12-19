<?php

/**
 * Membership reenewal prepare renewal dates API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */ 
function civicrm_api3_membershiprenewal_preparerenewaldates($params) {

  // Prepare renewal dates
  CRM_Membershiprenewal_Utils::prepareRenewalDatesForMemberships();

  // Get membership renewal settings
  $settings = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

  // Create 2nd renewal reminder activities
  if (isset($settings['enable_second_reminder']) && $settings['enable_second_reminder'] == 1) {
		CRM_Membershiprenewal_BAO_Batch::createRenewalReminders(2);
	}

  // Create 3rd renewal reminder activities
  if (isset($settings['enable_third_reminder']) && $settings['enable_third_reminder'] == 1) {
		CRM_Membershiprenewal_BAO_Batch::createRenewalReminders(3);
	}

  $returnValues = array();
  // Return success
  return civicrm_api3_create_success($returnValues, $params, 'Membershiprenewal', 'Preparerenewaldates');
}