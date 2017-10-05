<?php

require_once 'CRM/Core/Page.php';

class CRM_Membershiprenewal_Page_Membershiprenewaldashboard extends CRM_Core_Page {
  function run() {

    // Delete all batches and related activities
    if (isset($_GET['action']) && $_GET['action'] == 'deleteallbatches') {

      CRM_Membershiprenewal_BAO_Batch::resetAllMembershipCommunications();

      $url = CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1");

      $message = "All batches and related activies deleted.";
      CRM_Core_Session::setStatus($message, 'Membership Communications Dashboard', 'success');
      CRM_Utils_System::redirect($url);
      CRM_Utils_System::civiExit();
    }

    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Membership Communications Dashboard'));

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Get all message templates 
    $msgTemplates = CRM_Membershiprenewal_Utils::getMessageTemplates();
    if (!empty($settingsArray['email_message_template'])) {
    	$settingsArray['email_message_template_title'] = $msgTemplates[$settingsArray['email_message_template']];
    }
    if (!empty($settingsArray['letter_message_template'])) {
    	$settingsArray['letter_message_template_title'] = $msgTemplates[$settingsArray['letter_message_template']];
    }

    if (empty($settingsArray)) {
        $this->assign('memRenewalSettingsNotSet', 1);
    }

    // Get unprocessed renewal list based on settings
    $renewalList = CRM_Membershiprenewal_Utils::getValidListForRenewal($settingsArray, TRUE);
    $this->assign('renewalList', $renewalList);    

    $this->assign('memRenewalSettings', $settingsArray);

    // Get batch list
    $batchList = CRM_Membershiprenewal_BAO_Batch::getRenewalBatchList();
    $this->assign('batchList', $batchList);

    // Get renewal tokens for display
    $renewalTokens = CRM_Membershiprenewal_Constants::$renewalTokens;
    $this->assign('renewalTokens', $renewalTokens);

    parent::run();
  }
}
