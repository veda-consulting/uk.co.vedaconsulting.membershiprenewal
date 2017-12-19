<?php

class CRM_Membershiprenewal_Page_MembershipRenewalSingle extends CRM_Core_Page {

	public function run() {

		//get required values and prepate params.
		$contactID   = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
		$redirectUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contactID}");
		$selectDao   = self::getDetails($contactID);

		if (!$selectDao) {
			$message = ts("unable to send Membership Renewal. Please check the contact has valid membership to send renewal");
			$alert = 'error';
		}
		else {

			$settings       = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
			$commTypeRenewal= CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_RENEWAL;
			$isJoiner       = FALSE;

			// Get logged in user's Contact ID
			$userContactId = CRM_Membershiprenewal_Utils::getLoggedInUserContactID();

			$isAutoRenew   = CRM_Membershiprenewal_BAO_Batch::checkIfRecurringIsAutoRenew($selectDao, $settings);

			// Get activity type name by checking email, address and communication preference      
			$activityType  = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($selectDao);

			// Get message templates for reminder and membership type
			$messageTemplateId = CRM_Membershiprenewal_BAO_Batch::getMessageTemplateForRenewalReminder(1, $selectDao->membership_type_id, $activityType, $isJoiner, $isAutoRenew, $settings);

			// Get activity type id for activity type name
			$activityTypeID    = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($activityType);

			// Get activity date
			$activityDate      = CRM_Membershiprenewal_BAO_Membershiprenewal::getScheduledDateForFirstReminder($selectDao->end_date, $settings, TRUE);

			//Create Activity
			$activityParams = array(
				'activity_type_id'=> $activityTypeID,
				'subject'=> 'Membership - '.$selectDao->communication_type,
				'activity_date_time'=> $activityDate,
				'details'=> '',
				'status_id'=> CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled status
				'source_contact_id'=> $userContactId,
				'target_contact_id'=> $contactID,
				'is_test'=> 0,
			);
			$activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityParams);

			//Update custom data and message template with activity
			if ($activityResult['id']) {
				// Update activity custom data and save membership id
				// as passing 'custom_' is not working in version 4.6
				CRM_Membershiprenewal_BAO_Batch::insertCustomDataForActivity($activityResult['id'], $selectDao->membership_id, $selectDao->end_date, $selectDao->communication_type);

				CRM_Core_Error::debug_log_message("Activity custom data saved for Membership ID {$selectDao->membership_id}");

				// Prepare html using message template, to save in activity details section
				// This is for sending scheduled reminders or print letters
				// 1 - this is first renewal reminder
				CRM_Membershiprenewal_BAO_Batch::updateActivityWithMessageTemplate($activityResult['id'], $activityType, 1, $selectDao->membership_type_id, $isJoiner, $isAutoRenew, $settings);

				CRM_Core_Error::debug_log_message("Activity details & attachment updated for Membership ID {$selectDao->membership_id}");
			}

			//Set status and redirect
			$message = ts('Membership renewal letter/email has been Prepared successfully.');
			$alert = 'success';
		}

		//Set status and redirect
		$this->assign('message', $message);
		CRM_Core_Session::setStatus($message, 'Membership Renewal', $alert);
		CRM_Utils_System::redirect($redirectUrl);
		CRM_Utils_System::civiExit();
		parent::run();
	}


	public static function getDetails($contactID) {

		$sql = "
		SELECT member.id as membership_id, 'Renewal' as communication_type, 
		member.contact_id, member.membership_type_id, member.start_date, 
		member.end_date, member.join_date, member.contribution_recur_id,
		email.email, address.street_address, address.supplemental_address_1, 
		address.supplemental_address_2, address.supplemental_address_3, 
		address.city, address.postal_code, contact.do_not_email, 
		contact.do_not_mail, contact.is_opt_out, contact.display_name, 
		contact.id as contact_id, recur.payment_instrument_id
		FROM civicrm_membership member
		INNER JOIN civicrm_contact contact ON member.contact_id = contact.id
		LEFT JOIN civicrm_contribution_recur recur ON member.contribution_recur_id = recur.id
		LEFT JOIN civicrm_email email ON member.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
		LEFT JOIN civicrm_address address ON member.contact_id = address.contact_id AND address.is_primary = 1
		WHERE member.contact_id = %1
		";
		$sqlParams = array( 1 => array($contactID, 'Integer') );
		$dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);

		return $dao->fetch() ? $dao : FALSE;
	}
}
