<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Membershiprenewal_Form_Membershiprenewalprocess extends CRM_Core_Form {

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['title'] = CRM_Membershiprenewal_BAO_Batch::generateRenewalBatchName();
    $defaults['month_year'] = CRM_Utils_Array::value('month_year', $_GET);
    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    $defaults['sms_text_message'] = $settingsArray['sms_text_message'];
    $defaults['activity_subject'] = $settingsArray['sms_activity_subject'];
    return $defaults;
  }

  function buildQuickForm() {

    // Redirect to membership renewal dashboard, if renewal month/year is empty
    $monthYear = CRM_Utils_Array::value('month_year', $_REQUEST);
    if (empty($monthYear)) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1"));
      CRM_Utils_System::civiExit();
    }

    // Get renewal summary, to display before the activities are created
    $selectedPeriodArray = explode('-', $monthYear);

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
      CRM_Utils_System::setTitle(ts('Process Membership Communications - New Joiners & Renewals'));
    } else {
      CRM_Utils_System::setTitle(ts('Process Membership Communications - Renewals'));      
    }

    $month = $selectedPeriodArray[0];
    $year = $selectedPeriodArray[1];

    // Prepare temp table to save processing results
    $status = CRM_Membershiprenewal_BAO_Batch::prepareTempTable($month, $year);

    $summary = CRM_Membershiprenewal_BAO_Batch::getactivitiesSummary($month, $year);

    // Redirect if no membership is available for renewal
    if ($summary['total_count'] == 0) {
      $url = CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1");

      $message = ts("No membership is up for renewal for the selected month/year.");
      CRM_Core_Session::setStatus($message, 'Membership Renewal', 'warning');
      CRM_Utils_System::redirect($url);
      CRM_Utils_System::civiExit();
    }

    // Get valid years for renewals
    $years = CRM_Membershiprenewal_Utils::getValidYearsForRenewal($settingsArray['renewal_years']);

    // Prepare renewal list
    $renewalList = CRM_Membershiprenewal_Utils::getValidListForRenewal($settingsArray);

    // Renewal years
    $this->add(
      'text',
      'title',
      ts('Batch Title'),
      array('size' => 40),
      TRUE
    );

    // add form elements
    $element = $this->add(
      'select', // field type
      'month_year', // field name
      'Select month/year', // field label
      $renewalList, // list of options
      true // is required
    );
    // Freeze month/year selection, as we pass the predefined month/year
    $element->freeze();

    // Search field
    $this->add(
      'text',
      'search_term',
      ts('Name'),
      array('size' => 30),
      FALSE
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Proceed >>'),
        'isDefault' => TRUE,
      ),
    ));

    // Show SMS fields if enabled
    if ($settingsArray['enable_sms'] && $summary['sms_count'] > 0) {

      // Get default SMS provider
      $smsProviderId = CRM_Membershiprenewal_Utils::getDefaultSMSProvider();

      if (!empty($smsProviderId)) { 
        $this->assign('SMSCount', $summary['sms_count']);

        $this->formattedContacts = $summary['formatted_contacts'];
        $this->smsContacts = $summary['sms_contacts'];
        $this->smsDetails = $summary['sms_details'];

        // Send SMS?
        $this->addElement(
          'checkbox', 
          'send_sms', 
          ts('Send SMS?')
        );

        // Renewal years
        $this->add(
          'text',
          'activity_subject',
          ts('Name The SMS'),
          array('class' => 'huge'),
          FALSE
        );

        $this->assign('max_sms_length', CRM_SMS_Provider::MAX_SMS_CHAR);
        $this->add('textarea', 'sms_text_message', 'SMS Message',
          array(
            'cols' => '80',
            'rows' => '8',
            'onkeyup' => "return verify(this, 'SMS')",
          )
        );
      }
    }

    $this->addFormRule( array( 'CRM_Membershiprenewal_Form_Membershiprenewalprocess', 'formRule' ) );

    $this->assign('summary', $summary);
    $this->assign('month', $selectedPeriodArray[0]);
    $this->assign('year', $selectedPeriodArray[1]);

    $loadingImage = CRM_Membershiprenewal_Utils::getLoadingImage();
    $this->assign('loadingImage', $loadingImage);

    $this->assign('memRenewalSettings', $settingsArray);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  static function formRule( $values ){
    $errors = array();

    // Check if the month/year is already processed
    $batch = new CRM_Membershiprenewal_DAO_Batch();
    $batch->renewal_month_year = $values['month_year'];
    if ($batch->find(TRUE)) {
      $errors['month_year'] = ts('Membership renewals already processed for this month/year. Please choose a different month/year.');
    }

    // Check if batch name is already used
    $batch = new CRM_Membershiprenewal_DAO_Batch();
    $batch->name = CRM_Utils_String::titleToVar($values['title']);
    if ($batch->find(TRUE)) {
      $errors['title'] = ts('Renewal title already used. Please choose a different title.');
    }

    return $errors;
  }

  function postProcess() {
    $values = $this->exportValues();

    // Send SMS
    if (isset($values['send_sms']) AND $values['send_sms'] == 1) {

      // Get default SMS provider
      $smsProviderId = CRM_Membershiprenewal_Utils::getDefaultSMSProvider();
      if (!empty($smsProviderId)) {
        $thisValues = $smsParams = array(
          'to' => @implode(',', $this->smsDetails),
          'activity_subject' => $values['activity_subject'],
          'sms_provider_id' => $smsProviderId,
          'sms_text_message' => $values['sms_text_message'],
          'provider_id' => $smsProviderId,
        );

        list($sent, $activityId, $countSuccess) = CRM_Activity_BAO_Activity::sendSMS($this->formattedContacts,
          $thisValues,
          $smsParams,
          $this->smsContacts
        );
      }
    }

    // Date when the batch is created (also used as renewal date)
    $createdDate = date("Y-m-d H:i:s");
    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;

    // get month & year from submitted values
    $selectedPeriodArray = explode('-', $values['month_year']);

    // First create the activities
    $result = CRM_Membershiprenewal_BAO_Batch::createActivities($selectedPeriodArray[0], $selectedPeriodArray[1]);

    $activityIds = $result['activities'];

    // If activities are created, then create batch
    if (!empty($activityIds)) {
      // Create renewal batch
      $batchParams['title'] = $values['title'];
      $batchParams['created_date'] = $createdDate;
      // Set first reminder as the batch created date
      $batchParams['first_reminder_date'] = $createdDate; 
      $batchParams['renewal_month_year'] = $values['month_year'];
      $batch = CRM_Membershiprenewal_BAO_Batch::createRenewalBatch($batchParams);

      $batchId = $batch->id;

      // Insert into civicrm_membership_renewal_entity_batch table
      // This is first reminder
      if (!empty($batchId)) {
        CRM_Membershiprenewal_BAO_Batch::insertActivitiesForBatch($batchId, $reminder = 1);
      }
      $message = "Activities created for membership communication(s).";
      $status = 'success';

      // Redirect to batch screen, where the user can send emails, print letters, etc
      $url = CRM_Utils_System::url('civicrm/membershiprenewal/batch', "id={$batchId}&reminderType=1&reset=1");
    } else {
      $message = "No activities created for membership communication(s), as no memberships are up for renewal for the selected month/year.<br/>No batch created.";
      $status = 'warning';

      // redirect to membership renewal dashboard
      $url = CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1");
    }

    // Save all details in renewal log table
    if (!empty($batchId)) {
      CRM_Membershiprenewal_BAO_Batch::recordRenewalLog($batchId);
    }

    // Set message and redirect
    CRM_Membershiprenewal_Utils::setMessageAndRedirect($message, 'Membership Communication(s)', $status, $url);
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
