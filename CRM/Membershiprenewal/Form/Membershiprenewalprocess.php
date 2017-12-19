<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Membershiprenewal_Form_Membershiprenewalprocess extends CRM_Core_Form {

  public function preProcess() {
    $state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');
    // Import is done, so display the results
    if ($state == 'done') {
      $renewalDetailsTable = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
      $getActivitiesCountSql = "SELECT count(*) as count FROM {$renewalDetailsTable} WHERE activity_id IS NOT NULL";
      $activitiesCount = CRM_Core_DAO::singleValueQuery($getActivitiesCountSql);

      // If activities are created, then create batch
      if ($activitiesCount > 0) {

        $month_year = CRM_Utils_Request::retrieve('month_year', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');

        $title = CRM_Utils_Request::retrieve('title', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');

        $selectedPeriodArray = explode('-', $month_year);

        // Date when the batch is created (also used as renewal date)
        $createdDate = date("Y-m-d H:i:s");

        // Create renewal batch
        $batchParams['title'] = $title;
        $batchParams['created_date'] = $createdDate;
        // Set first reminder as the batch created date
        $batchParams['first_reminder_date'] = $createdDate;
        $batchParams['renewal_month_year'] = $month_year;
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
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['title'] = CRM_Membershiprenewal_BAO_Batch::generateRenewalBatchName();
    $defaults['month_year'] = CRM_Utils_Array::value('month_year', $_GET);
    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    if ($settingsArray['enable_sms'] == 1) {
      $defaults['sms_text_message'] = $settingsArray['sms_text_message'];
      $defaults['activity_subject'] = $settingsArray['sms_activity_subject'];
    }
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
        'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
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

    parent::buildQuickForm();
  }

  static function formRule( $values ){
    $errors = array();

    // Check if the month/year is already processed
    $batch = new CRM_Membershiprenewal_DAO_Batch();
    $batch->renewal_month_year = $values['month_year'];
    if ($batch->find(TRUE)) {
      // $errors['month_year'] = ts('Membership renewals already processed for this month/year. Please choose a different month/year.');
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

    $runner = self::getRunner($values);
    if ($runner) {
      // Run Everything in the Queue via the Web.
      $runner->runAllViaWeb();
    }
  }

  static function getRunner($values) {

    // Create queue for creating activities
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_QUEUE_NAME,
      'type' => 'Sql',
      'reset' => TRUE,
    ));

    $result = array(
      'activities' => array(),
      'excluded' => array(),
    );

    // Renewal details table
    $renewalDetailsTable = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $communicationsSql = "SELECT * FROM {$renewalDetailsTable} WHERE status = 1";
    $communicationsRes = CRM_Core_DAO::executeQuery($communicationsSql);
    $communications = array();
    while($communicationsRes->fetch()) {
      $communications[] = $communicationsRes->id;
    }
    $count = count($communications);

    // Set the Number of Rounds
    $rounds = ceil($count/CRM_Membershiprenewal_Constants::BATCH_COUNT);

    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {
      $start = $i * CRM_Membershiprenewal_Constants::BATCH_COUNT;
      $communicationsArray = array_slice($communications, $start, CRM_Membershiprenewal_Constants::BATCH_COUNT, TRUE);

      CRM_Core_Error::debug_var('communicationsArray', $communicationsArray);

      $counter = ($rounds > 1) ? ($start + CRM_Membershiprenewal_Constants::BATCH_COUNT) : $count;
      $task = new CRM_Queue_Task(
        array('CRM_Membershiprenewal_BAO_Batch', 'createActivities'),
        array($communicationsArray),
        "Processing communcations {$counter} of {$count}"
      );

      // Add the Task to the Queue
      $queue->createItem($task);
      $i++;
    }

    if (!empty($communications)) {

      $endParams = CRM_Membershiprenewal_Constants::END_PARAMS.'&month_year='.$values['month_year'].'&title='.$values['title'];

      // Get membership renewal settings
      $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
      if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
        $title = ts('Process Membership Communications - New Joiners & Renewals');
      } else {
        $title = ts('Process Membership Communications - Renewals');
      }


      // Setup the Runner
      $runner = new CRM_Queue_Runner(array(
        'title' => $title,
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
        'onEndUrl' => CRM_Utils_System::url(CRM_Membershiprenewal_Constants::END_URL, $endParams, TRUE, NULL, FALSE),
      ));

      return $runner;
    }

    return FALSE;
  }
}
