<?php

require_once 'CRM/Core/Page.php';

class CRM_Membershiprenewal_Page_Membershiprenewalviewsettings extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Membership Communications Settings'));

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

    $this->assign('memRenewalSettings', $settingsArray);

    // Get renewal tokens for display
    $renewalTokens = CRM_Membershiprenewal_Constants::$renewalTokens;
    $this->assign('renewalTokens', $renewalTokens);

    parent::run();
  }
}
