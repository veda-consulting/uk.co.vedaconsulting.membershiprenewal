<?php

/** 
 * Class to handle activities move between different queues
 */

require_once 'CRM/Core/Page.php';

class CRM_Membershiprenewal_Page_MoveActivity extends CRM_Core_Page {
  function run() {
    
  	if (empty($_GET['id']) || empty($_GET['moveto'])
    	|| !isset($_GET['id']) || !isset($_GET['moveto'])
    	) {
    	// Redirect to previous page
    	CRM_Membershiprenewal_Utils::setMessageAndRedirect();
    }

    $id = $_GET['id'];
    $moveto = $_GET['moveto'];
    $batchId = $_GET['batchId'];
    $reminderType = $_GET['reminderType'];
    $batchUrl = CRM_Utils_System::url('civicrm/membershiprenewal/batch', "id={$batchId}&reminderType={$reminderType}&reset=1");

    $activityType = '';

    $whereClauses = $selectParams = array();
    $whereClauses[] = " WHERE (1)";
    if (!empty($id)) {
    	$selectParams[1] = array($id, 'Integer');
      $whereClauses[] = "a.id = %1";
    }

    $whereClause = implode(' AND ', $whereClauses);

    switch ($moveto) {
    	case 'email':
    		$selectSql = "
SELECT custom.membership_id, email.email, contact.do_not_email, contact.do_not_mail, contact.is_opt_out
FROM civicrm_activity a
INNER JOIN civicrm_value_membership_renewal_information custom ON custom.entity_id = a.id
LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
LEFT JOIN civicrm_contact contact ON contact.id = ac.contact_id
LEFT JOIN civicrm_email email ON contact.id = email.contact_id AND email.on_hold = 0
{$whereClause}
";
	    	$selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
	    	$selectDao->fetch();
        // Check if valid email is available
	      // 1. Email is not empty
	      // 2. Email is not on hold (checking this in query)
	      // 3. Contact does not have DO NOT EMAIL flag ticked
	      // 4. Contact did not OPT OUT of all mailings
	  		if (!empty($selectDao->email) && $selectDao->do_not_email == 0 && $selectDao->is_opt_out == 0) {
	        $activityType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME;
	        $queue = 'Email';
          $memId = $selectDao->membership_id;
	      } else {
	      	$message = ts("Not able to move activity. Please check the contact's email or communication preference");
	      	$status = 'warning';
	      }
    	break;

    	case 'print':
    		$selectSql = "
SELECT custom.membership_id, address.street_address, address.postal_code, contact.do_not_mail, contact.is_opt_out
FROM civicrm_activity a
INNER JOIN civicrm_value_membership_renewal_information custom ON custom.entity_id = a.id
LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
LEFT JOIN civicrm_contact contact ON contact.id = ac.contact_id
LEFT JOIN civicrm_address address ON contact.id = address.contact_id
{$whereClause}
";
	    	$selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
	    	$selectDao->fetch();
	    	// Check if we have a valid address
      	// 1. Street address and Postcode are not empty
      	// 2. Contact does not have DO NOT MAIL flag ticked
	  		if (!empty($selectDao->street_address) && 
        	!empty($selectDao->postal_code) 
        	&& $selectDao->do_not_mail == 0) {
	        $activityType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME;
	      	$queue = 'Print';
          $memId = $selectDao->membership_id;
	      } else {
	      	$message = ts("Not able to move activity. Please check the contact's address or communication preference");
	      	$status = 'warning';
	      }
    	break;	
    }

    if (empty($message)) {
    	$message = ts('No action performed');
    	$status = 'warning';
    }

    $messageTitle = 'Move Membership Renewal Activity';

    // No valid activity Type available
    // Redirect to previous page
    if (empty($activityType)) {
			// Redirect to previous page
			CRM_Membershiprenewal_Utils::setMessageAndRedirect($message, $messageTitle, $status, $batchUrl);
    }

    // We have a valid activity Type now
    // Now move the activity to the appropriate queue
    // get activity type ID using name
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      $activityType,
      'name'
    );

    // Prepare activity params
    $activityParams = array(
      'activity_type_id' => $activityTypeID,
      'id' => $id,
    );
    // Update activity using API
    $activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityParams);

    if ($activityResult['id']) {

      $memTypeId = NULL;
      // Get membership type id for the membership
      // to get membership type specific message template
      if (!empty($memId)) {
        $memResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Membership', 'get',array(
          'sequential' => 1,
          'id' => $memId,
        ));
        $memTypeId = $memResult['values'][0]['membership_type_id'];
      }

      // Prepare html using message template, to save in activity details section
      // This is for sending scheduled reminders or print letters
      CRM_Membershiprenewal_BAO_Batch::updateActivityWithMessageTemplate($activityResult['id'], $activityType, $memTypeId);
    }

    $message = ts("Activity moved to {$queue} queue.");
    $status = 'success';

    // Redirect to previous page
		CRM_Membershiprenewal_Utils::setMessageAndRedirect($message, $messageTitle, $status, $batchUrl);
  }
}
