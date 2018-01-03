<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Membershiprenewal_Form_Membershiprenewalsettings extends CRM_Core_Form {

  function buildQuickForm() {

    CRM_Utils_System::setTitle(ts('Membership Communications - Settings'));

    // Get membership renewal settings, for setting defaults
    $defaultsArray = $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    
    // Get all message templates 
    $msgTemplates = CRM_Membershiprenewal_Utils::getMessageTemplates();

    // Get all membership types
    $memTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();

    //$startMonthYear = CRM_Membershiprenewal_Utils::getValidListForRenewalStart();

    // Renewal start month/year
    /*$this->add(
      'select', 
      'renewal_start_month_year', 
      ts('Renewal Start'),
      $startMonthYear, 
      TRUE
    );*/

    // Enable Word mailmerge?
    $this->addElement(
      'checkbox', 
      'enable_word_mailmerge', 
      ts('Enable Word Mailmerge?')
    );

    // Enable SMS?
    $this->addElement(
      'checkbox', 
      'enable_sms', 
      ts('Enable SMS?')
    );

    // Enable Attachment?
    $this->addElement(
      'checkbox', 
      'enable_attachment', 
      ts('Enable Attachment?')
    );

    // SMS message
    $this->assign('max_sms_length', CRM_SMS_Provider::MAX_SMS_CHAR);
    $this->add('textarea', 'sms_text_message', 'SMS Message',
      array(
        'cols' => '80',
        'rows' => '8',
        'onkeyup' => "return verify(this, 'SMS')",
      )
    );

    // SMS activity subject
    $this->add(
      'text',
      'sms_activity_subject',
      ts('SMS Activity Subject'),
      array('class' => 'huge'),
      FALSE
    );

    // Renewal years
    /*$this->add(
      'text',
      'renewal_years',
      ts('Renewal Years'),
      array('size' => 2),
      TRUE
    );*/

    // Renewal Period
    $this->add(
      'text',
      'renewal_period',
      ts('Renewal Period'),
      array('size' => 2),
      TRUE
    );

    // Renewal start offset
    /*$this->add(
      'text',
      'renewal_start_offset',
      ts('Renewal Start Offset'),
      array('size' => 2),
      TRUE
    );

    // Renewal end offset
    $this->add(
      'text',
      'renewal_end_offset',
      ts('Renewal End Offset'),
      array('size' => 2),
      FALSE
    );*/

    // GK 31052017
    $firstRenewalPeriods = array(
      '60' => ts('60 days'),
      '30' => ts('30 days'),
      '0' => ts('Other'),
    );

    $this->addRadio(
      'renewal_first_reminder',
      ts('1st reminder'),
      $firstRenewalPeriods,
      NULL,
      '<br>',
      TRUE
    );

    // other option field
    $this->add(
      'text',
      'renewal_first_reminder_other',
      ts('Renewal End Offset Other'),
      array('size' => 2),
      FALSE
    );

    $this->addRule('renewal_first_reminder_other', ts('Please enter a number for first renewal days (integers only).'), 'positiveInteger'
    );

    $secondRenewalPeriods = array(
      '30' => ts('30 days'),
      '14' => ts('14 days'),
      '0' => ts('Other'),
    );

    // Enable 2nd reminder?
    $this->addElement(
      'checkbox',
      'enable_second_reminder',
      ts('Enable second reminder?')
    );

    $this->addRadio(
      'renewal_second_reminder',
      ts('2nd reminder'),
      $secondRenewalPeriods,
      NULL,
      '<br>',
      FALSE
    );

    // other option field
    $this->add(
      'text',
      'renewal_second_reminder_other',
      ts('Renewal End Offset Other'),
      array('size' => 2),
      FALSE
    );

    $this->addRule('renewal_second_reminder_other',
      ts('Please enter a number for second renewal days (integers only).'), 'positiveInteger'
    );

    $thirdRenewalPeriods = array(
      '7' => ts('7 days'),
      '3' => ts('3 days'),
      '1' => ts('1 day'),
    );

    // Enable 3rd reminder?
    $this->addElement(
      'checkbox',
      'enable_third_reminder',
      ts('Enable third reminder?')
    );

    $this->addRadio(
      'renewal_third_reminder',
      ts('3rd reminder'),
      $thirdRenewalPeriods,
      NULL,
      '<br>',
      FALSE
    );
    // End of GK

    // No of days after we create 1nd Renewal activities 
    /*$this->add(
      'text',
      'renewal_first_reminder',
      ts('1st reminder'),
      array('size' => 2),
      TRUE
    );

    // No of days after we create 2nd Renewal activities 
    $this->add(
      'text',
      'renewal_second_reminder',
      ts('2nd reminder'),
      array('size' => 2),
      TRUE
    );

    $this->add(
      'text',
      'renewal_third_reminder',
      ts('3rd reminder'),
      array('size' => 2),
      TRUE
    );*/

    // Get membership type period (fixed/rolling)
    $memTypePlan = CRM_Membershiprenewal_Utils::getMembershipTypePeriod();
    $this->assign('memTypePlan', $memTypePlan);

    $this->add('date', 'fixed_period_end_day', ts('Membership End Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );

    // Auto-renew payment instruments
    $this->add(
      'select',
      'autorenew_payment_instrument_id',
      'Auto-renew Payment Methods',
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(), // list of options
      false,
      array('class' => 'crm-select2 huge', 'multiple' => 'multiple',)
    );

    // Renewal page link
    $this->add(
      'text',
      'renewal_page_link',
      ts('Membership Renewal Page'),
      array('class' => 'huge'),
      FALSE
    );

    $memTypesBaseMsgTemplates = CRM_Membershiprenewal_Constants::$memTypesBaseMsgTemplates;

    // Include New Joiners?
    $this->addElement(
      'checkbox', 
      'include_joiner', 
      ts('Include New Joiners?')
    );

    $this->addDate('cut_off_date', ts('Cut Off Date'), FALSE, array('formatType' => 'activityDate'));

    // Membership status
    $membershipStatus = CRM_Member_PseudoConstant::membershipStatus();
    $this->add('select', 'membership_status', ts('Membership Status(s)'), $membershipStatus, FALSE,
      array('id' => 'membership_status', 'multiple' => 'multiple', 'class' => 'crm-select2', 'style' => 'width: 50%;')
    );
    
    $noOfJoinerArray = array();

    for ($i=1; $i <=1; $i++) {

      $noOfJoinerArray[$i] = CRM_Membershiprenewal_Utils::ordinal($i);

      // New joiners email template
      $this->add(
        'select', 
        'joiner_email_message_template_'.$i,
        ts('Email message template'), 
        array('' => '- select -') + $msgTemplates, 
        FALSE 
      );

      // New joiners letter template
      $this->add(
        'select', 
        'joiner_letter_message_template_'.$i,
        ts('Letter message template'), 
        array('' => '- select -') + $msgTemplates, 
        FALSE
      );

      // Loop through all membership types
      foreach ($memTypes as $memId => $memName) {

        $emailTemplateFieldName = 'joiner_email_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$emailTemplateFieldName])) {
          //$defaultsArray[$emailTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Renewal email template
        $this->add(
          'select', 
          $emailTemplateFieldName, 
          ts($memName.' - Email message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE 
        );

        $letterTemplateFieldName = 'joiner_letter_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$letterTemplateFieldName])) {
          //$defaultsArray[$letterTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Renewal letter template
        $this->add(
          'select', 
          $letterTemplateFieldName, 
          ts($memName.' - Letter message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE
        );
      }
    }

    // Get number of renewals
    $noOfRenewals = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_NO_OF_RENEWAL_ACTIVITIES;

    $noOfRenewalArray = array();

    for ($i=1; $i <=$noOfRenewals; $i++) {

      $noOfRenewalArray[$i] = CRM_Membershiprenewal_Utils::ordinal($i);

      // Renewal email template
      $this->add(
        'select', 
        'email_message_template_'.$i,
        ts('Email message template'), 
        array('' => '- select -') + $msgTemplates, 
        TRUE 
      );

      // Renewal letter template
      $this->add(
        'select', 
        'letter_message_template_'.$i,
        ts('Letter message template'), 
        array('' => '- select -') + $msgTemplates, 
        TRUE
      );

      // Auto-renew email template
      $this->add(
        'select', 
        'autorenew_email_message_template_'.$i,
        ts('Auto-renew Email message template'), 
        array('' => '- select -') + $msgTemplates, 
        TRUE
      );

      // Auto-renew letter template
      $this->add(
        'select', 
        'autorenew_letter_message_template_'.$i,
        ts('Letter message template'), 
        array('' => '- select -') + $msgTemplates, 
        TRUE
      );

      // Loop through all membership types
      foreach ($memTypes as $memId => $memName) {

        $emailTemplateFieldName = 'email_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$emailTemplateFieldName])) {
          //$defaultsArray[$emailTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Renewal email template
        $this->add(
          'select', 
          $emailTemplateFieldName, 
          ts($memName.' - Email message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE 
        );

        $letterTemplateFieldName = 'letter_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$letterTemplateFieldName])) {
          //$defaultsArray[$letterTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Renewal letter template
        $this->add(
          'select', 
          $letterTemplateFieldName, 
          ts($memName.' - Letter message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE
        );

        $autorenewEmailTemplateFieldName = 'autorenew_email_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$autorenewEmailTemplateFieldName])) {
          //$defaultsArray[$letterTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Auto-renew email template
        $this->add(
          'select', 
          $autorenewEmailTemplateFieldName, 
          ts($memName.' - Auto-renew email message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE
        );

        $autorenewLetterTemplateFieldName = 'autorenew_letter_message_template_'.$i.'_'.$memId;
        // Set default as no action for initial form load
        if (!isset($defaultsArray[$autorenewLetterTemplateFieldName])) {
          //$defaultsArray[$letterTemplateFieldName] = 'noaction';
        }

        // Membership type specific - Auto-renew letter template
        $this->add(
          'select', 
          $autorenewLetterTemplateFieldName, 
          ts($memName.' - Auto-renew letter message template'), 
          $memTypesBaseMsgTemplates + $msgTemplates, 
          FALSE
        );
      }
    }

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // Set defaults
    if (!empty($defaultsArray['cut_off_date'])) {
      $cutOffDate = CRM_Utils_Date::setDateDefaults($defaultsArray['cut_off_date'], 'activityDateTime');
      $defaultsArray['cut_off_date'] = $cutOffDate[0];
    }
    if (empty($defaultsArray['sms_text_message'])) {
      $defaultsArray['sms_text_message'] = 'Your membership is up for renewal. Click the link to renew {membershiprenewal.renewal_page_link}';
    }
    if (empty($defaultsArray['sms_activity_subject'])) {
      $defaultsArray['sms_activity_subject'] = 'Membership Renewal SMS';
    }

    // Unset first reminder value, if we have other value
    // so the defaults are set correctly
    if (isset($defaultsArray['renewal_first_reminder_other']) && !empty($defaultsArray['renewal_first_reminder_other'])) {
      $defaultsArray['renewal_first_reminder'] = 0;
    }

    // Unset second reminder value, if we have other value
    // so the defaults are set correctly
    if (isset($defaultsArray['renewal_second_reminder_other']) && !empty($defaultsArray['renewal_second_reminder_other'])) {
      $defaultsArray['renewal_second_reminder'] = 0;
    }

    $this->setDefaults($defaultsArray);

    $this->assign('memTypes', $memTypes);
    $this->assign('noOfRenewalArray', $noOfRenewalArray);
    $this->assign('noOfJoinerArray', $noOfJoinerArray);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();

    $this->addFormRule(array('CRM_Membershiprenewal_Form_Membershiprenewalsettings', 'formRule'), $this);
  }

   /**
   * Validation.
   */
  public static function formRule($params, $files, $self) {
    $errors = array();
    if (isset($params['include_joiner']) && $params['include_joiner'] == 1) {
      if (empty($params['cut_off_date'])) {
        $errors['cut_off_date'] = ts('Select a cut off date for processing new joiners.');
      }

      if (empty($params['joiner_email_message_template_1'])) {
        $errors['joiner_email_message_template_1'] = ts('New Joiner Communication - Email message template is a required field.');
      }

      if (empty($params['joiner_letter_message_template_1'])) {
        $errors['joiner_email_message_template_1'] = ts('New Joiner Communication - Letter message template is a required field.');
      }
    }

    if (isset($params['enable_second_reminder']) && $params['enable_second_reminder'] == 1) {
      if (empty($params['renewal_second_reminder']) && empty($params['renewal_second_reminder_other'])) {
        $errors['renewal_second_reminder'] = ts('Please select when you want to send second reminder.');
      }
    }

    if (isset($params['enable_third_reminder']) && $params['enable_third_reminder'] == 1) {
      if (empty($params['renewal_third_reminder'])) {
        $errors['renewal_third_reminder'] = ts('Please select when you want to send third reminder.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  function postProcess() {
    $values = $this->exportValues();

    $settingsArray = array();
    //$settingsArray['renewal_years'] = $values['renewal_years'];
    //$settingsArray['renewal_period'] = $values['renewal_period'];
    //$settingsArray['renewal_start_offset'] = $values['renewal_start_offset'];

    $settingsArray['renewal_years'] = 1; // setting 1 as default value
    //$settingsArray['renewal_period'] = 1; // setting 1 as default value
    $settingsArray['renewal_period'] = $values['renewal_period'];
    $settingsArray['renewal_start_offset'] = 0; // setting 1 as default value
    $settingsArray['renewal_end_offset'] = 2;

    //$settingsArray['email_message_template'] = $values['email_message_template'];
    //$settingsArray['letter_message_template'] = $values['letter_message_template'];

    $settingsArray['autorenew_payment_instrument_id'] = $values['autorenew_payment_instrument_id'];
    if (isset($values['fixed_period_end_day']) && !empty($values['fixed_period_end_day'])) {
      $settingsArray['fixed_period_end_day'] = $values['fixed_period_end_day'];
    }
    $settingsArray['renewal_page_link'] = $values['renewal_page_link'];
    $settingsArray['renewal_page_tiny_url'] = '';
    if (!empty($values['renewal_page_link'])) {
      $settingsArray['renewal_page_tiny_url'] = CRM_Membershiprenewal_Utils::getTinyUrl($values['renewal_page_link']);
    }
    
    // First reminder
    if (!empty($values['renewal_first_reminder_other']) && !isset($values['renewal_first_reminder'])) {
      $settingsArray['renewal_first_reminder'] = $values['renewal_first_reminder_other'];
      $settingsArray['renewal_first_reminder_other'] = $values['renewal_first_reminder_other'];
    } else {
      $settingsArray['renewal_first_reminder'] = $values['renewal_first_reminder'];
    }
    // Second reminder
    $settingsArray['enable_second_reminder'] = 0;
    if (isset($values['enable_second_reminder']) && $values['enable_second_reminder'] == 1) {
      $settingsArray['enable_second_reminder'] = 1;
      if (!empty($values['renewal_second_reminder_other']) && !isset($values['renewal_second_reminder'])) {
        $settingsArray['renewal_second_reminder'] = $values['renewal_second_reminder_other'];
        $settingsArray['renewal_second_reminder_other'] = $values['renewal_second_reminder_other'];
      } else {
        $settingsArray['renewal_second_reminder'] = $values['renewal_second_reminder'];
      }
    }

    // 3rd remminder
    $settingsArray['enable_third_reminder'] = 0;
    if (isset($values['enable_third_reminder']) && $values['enable_third_reminder'] == 1) {
      $settingsArray['enable_third_reminder'] = 1;
      $settingsArray['renewal_third_reminder'] = $values['renewal_third_reminder'];
    }

    //// Membership type specific message template settings
    // Get all membership types
    $memTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();

    // Get number of renewals
    $noOfRenewals = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_NO_OF_RENEWAL_ACTIVITIES;

    // Settings key example:
    // email_message_template_1_12 
    // 1 is renewal activity (first, second or third)
    // 12 is membership type ID
    for ($i=1; $i <=$noOfRenewals; $i++) {
      $emailElement = 'email_message_template_'.$i;
      $letterElement = 'letter_message_template_'.$i;
      $autorenewEmailElement = 'autorenew_email_message_template_'.$i;
      $autorenewLetterElement = 'autorenew_letter_message_template_'.$i;
      $settingsArray[$emailElement] = $values[$emailElement];
      $settingsArray[$letterElement] = $values[$letterElement];
      $settingsArray[$autorenewEmailElement] = $values[$autorenewEmailElement];
      $settingsArray[$autorenewLetterElement] = $values[$autorenewLetterElement];
      foreach ($memTypes as $memId => $memName) {

        $emailMemTypeElement = 'email_message_template_'.$i.'_'.$memId;
        $letterMemTypeElement = 'letter_message_template_'.$i.'_'.$memId;
        $autorenewEmailMemTypeElement = 'autorenew_email_message_template_'.$i.'_'.$memId;
        $autorenewLetterMemTypeElement = 'autorenew_letter_message_template_'.$i.'_'.$memId;

        // Renewal Email template
        if (isset($values[$emailMemTypeElement]) && !empty($values[$emailMemTypeElement])) {
          $settingsArray[$emailMemTypeElement] = $values[$emailMemTypeElement];
        }

        // Renewal Letter template
        if (isset($values[$letterMemTypeElement]) && !empty($values[$letterMemTypeElement])) {
          $settingsArray[$letterMemTypeElement] = $values[$letterMemTypeElement];
        }

        // Auto-renew Email template
        if (isset($values[$autorenewEmailMemTypeElement]) && !empty($values[$autorenewEmailMemTypeElement])) {
          $settingsArray[$autorenewEmailMemTypeElement] = $values[$autorenewEmailMemTypeElement];
        }

        // Auto-renew Letter template
        if (isset($values[$autorenewLetterMemTypeElement]) && !empty($values[$autorenewLetterMemTypeElement])) {
          $settingsArray[$autorenewLetterMemTypeElement] = $values[$autorenewLetterMemTypeElement];
        }
      }
    }

    $saveJoinerValues = FALSE;
    $settingsArray['include_joiner'] = 0;
    if (isset($values['include_joiner']) && $values['include_joiner'] == 1) {
      $settingsArray['include_joiner'] = 1;
      $settingsArray['cut_off_date'] = CRM_Utils_Date::processDate($values['cut_off_date']);
      $saveJoinerValues = TRUE;
    }
    // save membership type
    if (!empty($values['membership_status'])) {
      $settingsArray['membership_status'] = $values['membership_status'];
    }     
    for ($i=1; $i <=1; $i++) {
      $emailElement = 'joiner_email_message_template_'.$i;
      $letterElement = 'joiner_letter_message_template_'.$i;
      $settingsArray[$emailElement] = $values[$emailElement];
      $settingsArray[$letterElement] = $values[$letterElement];
      foreach ($memTypes as $memId => $memName) {

        $emailMemTypeElement = 'joiner_email_message_template_'.$i.'_'.$memId;
        $letterMemTypeElement = 'joiner_letter_message_template_'.$i.'_'.$memId;

        if (isset($values[$emailMemTypeElement]) && !empty($values[$emailMemTypeElement]) 
          && $saveJoinerValues == TRUE) {
          $settingsArray[$emailMemTypeElement] = $values[$emailMemTypeElement];
        } else {
          unset($settingsArray[$emailMemTypeElement]);
        }

        if (isset($values[$letterMemTypeElement]) && !empty($values[$letterMemTypeElement]) 
          && $saveJoinerValues == TRUE) {
          $settingsArray[$letterMemTypeElement] = $values[$letterMemTypeElement];
        } else {
          unset($settingsArray[$letterMemTypeElement]);
        }
      }
    }

    // Enable SMS
    $settingsArray['enable_sms'] = 0;
    if (isset($values['enable_sms']) && $values['enable_sms'] == 1) {
      $settingsArray['enable_sms'] = 1;
      $settingsArray['sms_activity_subject'] = $values['sms_activity_subject'];
      $settingsArray['sms_text_message'] = $values['sms_text_message'];
    }

    // Enable Word Mailmerge
    $settingsArray['enable_word_mailmerge'] = 0;
    if (isset($values['enable_word_mailmerge']) && $values['enable_word_mailmerge'] == 1) {
      $settingsArray['enable_word_mailmerge'] = 1;
    }

    // Enable Attachment
    $settingsArray['enable_attachment'] = 0;
    if (isset($values['enable_attachment']) && $values['enable_attachment'] == 1) {
      $settingsArray['enable_attachment'] = 1;
    }

    $settingsStr = serialize($settingsArray);

    // Save the settings
    CRM_Core_BAO_Setting::setItem($settingsStr,CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SETTING_GROUP, CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SETTING_NAME);

    // Create schedule job which will prepare membership renewal dates
    CRM_Membershiprenewal_Utils::createScheduledJob($is_active = 1);

    // Create scheduled reminder for sending membership renewal emails
    CRM_Membershiprenewal_Utils::createScheduledReminder();

    // Prepare membership renewal dates instantly
    // so that the user can process memberships once the settings are saved
    CRM_Membershiprenewal_Utils::prepareRenewalDatesForMemberships();

    $message = "Membership renewal settings saved.";
    $url = CRM_Utils_System::url('civicrm/membershiprenewal/viewsettings', 'reset=1');

    // Set message and redirect
    CRM_Membershiprenewal_Utils::setMessageAndRedirect($message, 'Membership Renewal Settings', 'success', $url);
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
