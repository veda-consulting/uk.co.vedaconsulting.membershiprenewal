<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Membershiprenewal_Form_Membershiprenewalbatch extends CRM_Core_Form {
  function buildQuickForm() {

    // Redirect to membership renewal dashboard, if $batchId is empty
    if (!isset($_REQUEST['id']) || empty($_REQUEST['id']) ||
      !isset($_REQUEST['reminderType']) || empty($_REQUEST['reminderType'])
      ) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1"));
      CRM_Utils_System::civiExit();
    }

    $batchId = $_REQUEST['id'];
    $reminderType = $_REQUEST['reminderType'];

    // Get label for reminder_type
    $reminderLabel = ucwords(CRM_Membershiprenewal_Constants::$reminder_type[$reminderType]);

    CRM_Utils_System::setTitle(ts('Membership Renewal Batch'));

    // Get batch details
    $batchDetails = CRM_Membershiprenewal_BAO_Batch::getRenewalBatchList($batchId);

    // Redirect to membership renewal dashboard, if $batch details are empty
    // (might have been deleted)
    if (empty($batchDetails[$batchId])) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1"));
      CRM_Utils_System::civiExit();
    }

    $this->assign('batchList', $batchDetails[$batchId]);
    $this->assign('reminderLabel', $reminderLabel);
    
    // Get activity type ids using name
    $emailActivityTypeId = CRM_Core_OptionGroup::getValue('activity_type',
        CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME,
        'name'
      );
    $letterActivityTypeId = CRM_Core_OptionGroup::getValue('activity_type',
        CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME,
        'name'
      );
    $unknownActivityTypeId = CRM_Core_OptionGroup::getValue('activity_type',
        CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME,
        'name'
      );

    //Prepare renewal letter
    if (isset($_GET['action']) && $_GET['action'] == 'printletters') {
      $fileDetails = CRM_Membershiprenewal_BAO_Membershiprenewal::prepareLetters($batchDetails[$batchId], $letterActivityTypeId, $reminderType);

      // Update batch with fileId
      /*$batch = new CRM_Membershiprenewal_DAO_Batch();
      $batch->id = $batchId;
      $batch->print_file_id = $fileDetails['id'];
      $batch->print_entity_file_id = $fileDetails['eid'];
      $batch->save();*/

      $url = CRM_Utils_System::url('civicrm/membershiprenewal/batch', "id={$batchId}&reminderType={$reminderType}&reset=1");

      $message = "Membership renewal letters are ready to be downloaded.";
      CRM_Core_Session::setStatus($message, 'Membership Renewal Letters Prepared', 'success');
      CRM_Utils_System::redirect($url);
      CRM_Utils_System::civiExit();
    }  

    // Get printed files to download
    $files = CRM_Membershiprenewal_BAO_Membershiprenewal::getPreparedLetters($batchId, $reminderType);
    $this->assign('downloadFiles', $files);

    // Get all activities for this batch
    $batchActivitiesList = CRM_Membershiprenewal_BAO_Batch::getRenewalBatchActivitiesList($batchId, $reminderType);

    $emailActivities = $letterActivities = $unknownActivities = array();
    if (isset($batchActivitiesList[$emailActivityTypeId])) {
      $emailActivities = $batchActivitiesList[$emailActivityTypeId];
    }
    if (isset($batchActivitiesList[$letterActivityTypeId])) {
      $letterActivities = $batchActivitiesList[$letterActivityTypeId];
    }
    if (isset($batchActivitiesList[$unknownActivityTypeId])) {
      $unknownActivities = $batchActivitiesList[$unknownActivityTypeId];
    }

    //Get all membership which are excluded from this batch
    //require_once 'CRM/Membershiprenewal/Utils.php';
    //$excludedMemberships = CRM_Membershiprenewal_Utils::getAllMembershipExcludedByBatchId($batchId);
    //$this->assign('excludedMemberships', $excludedMemberships);
    //$this->assign('excludedMembershipsCount', count($excludedMemberships));

    $this->assign('reminderType', $reminderType);

    $this->assign('emailActivities', $emailActivities);
    $this->assign('emailActivitiesCount', count($emailActivities));
    $this->assign('letterActivities', $letterActivities);
    $this->assign('letterActivitiesCount', count($letterActivities));
    $this->assign('unknownActivities', $unknownActivities);
    $this->assign('unknownActivitiesCount', count($unknownActivities));
    $this->addElement('hidden', 'id', $batchId);
    $this->addElement('hidden', 'reminderType', $reminderType);

    // Search field
    $this->add(
      'text',
      'search_term',
      ts('Name'),
      array('size' => 30),
      FALSE
    );
    $loadingImage = CRM_Membershiprenewal_Utils::getLoadingImage();
    $this->assign('loadingImage', $loadingImage);
    
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Re-Send'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    // Re-send email activities
    if (!empty($_POST['selected_activities'])) {
      CRM_Membershiprenewal_BAO_Batch::resendRenewalActivities($_POST['selected_activities'], $values['reminderType']);
    }
   
    $url = CRM_Utils_System::url('civicrm/membershiprenewal/batch', "id={$values['id']}&reminderType={$values['reminderType']}&reset=1");

    $message = "Re-send scheduled for selected membership renewal email(s).";
    CRM_Core_Session::setStatus($message, 'Membership Renewal Emails', 'success');
    CRM_Utils_System::redirect($url);
    CRM_Utils_System::civiExit();

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
