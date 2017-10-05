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

  // Create 2nd renewal reminder activities
  CRM_Membershiprenewal_BAO_Batch::createRenewalReminders(2);

  // Create 3rd renewal reminder activities
  CRM_Membershiprenewal_BAO_Batch::createRenewalReminders(3);

  $returnValues = array();
  // Return success
  return civicrm_api3_create_success($returnValues, $params, 'Membershiprenewal', 'Preparerenewaldates');
}