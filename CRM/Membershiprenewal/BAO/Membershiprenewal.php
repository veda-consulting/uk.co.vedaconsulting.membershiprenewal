<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Membership renewal BAO class.
 */

class CRM_Membershiprenewal_BAO_Membershiprenewal {
  CONST TOKEN_VAR_NAME = "CiviCRM";
  /**
   * Function to prepare letters
   *
   * @param array $batchDetails
   * @param int $activityTypeId
   * @param int $reminderType
   *
   * @return int $fileId
   */
  public static function prepareLetters($batchDetails, $activityTypeId, $reminderType) {

  	if (empty($batchDetails) || empty($activityTypeId) || empty($reminderType)) {
  		return;
  	}

    $batchId = $batchDetails['id'];
    $batchCreatedDate = $batchDetails['created_date'];
    $batchUrl = CRM_Utils_System::url('civicrm/membershiprenewal/batch', "id={$batchId}&reminderType={$reminderType}&reset=1");

  	// Get message template for letters
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Get activities to prepare letters
  	$activities = CRM_Membershiprenewal_BAO_Batch::getRenewalBatchActivitiesList($batchId, $reminderType, $activityTypeId);

    $actTypes = CRM_Membershiprenewal_Utils::getAllActivityTypes();

    // Check if word mailmerge is enabled in settings
    if ($settingsArray['enable_word_mailmerge'] == 1) {
      $token = CRM_Core_SelectValues::contactTokens();
      $token = $token + CRM_Core_SelectValues::membershipTokens();

      $tokenMerge = array();
      foreach ($token as $key => $label) {
        $tokenMerge [] = array(
          'id' => $key,
          'text' => $label,
        );
      }

      foreach ($tokenMerge as $tmKey => $tmValue) {
        $tokenFullName =  str_replace(array('{','}'),"",$tmValue['id']);
        $explodedTokenName =  explode('.', $tokenFullName);
        $tokenMerge[$tmKey]['token_name'] =  ($explodedTokenName[0] != 'contact') ? $tokenFullName : $explodedTokenName[1];
        if ($explodedTokenName[0] != 'civiqrcode'){
          if ($explodedTokenName[0] != 'contact') {
            $tokenMerge[$tmKey]['var_name'] =  '['.self::TOKEN_VAR_NAME.'.'.$tokenFullName.';block=w:tr]';
            $tokenMerge[$tmKey]['var_name_table'] =  '['.self::TOKEN_VAR_NAME.'.'.$tokenFullName.';block=w:tbl]';
          }
          else {
            //need to do proper fix seems token named as contact.address_block
            // 'address_block' token assigned into 'contact' token array
            //$explodedTokenName[1] = ($explodedTokenName[1] == 'address_block') ? 'contact.'.$explodedTokenName[1] : $explodedTokenName[1];
            $tokenMerge[$tmKey]['var_name'] =  '['.self::TOKEN_VAR_NAME.'.'.$explodedTokenName[1].';block=w:tr]';
            $tokenMerge[$tmKey]['var_name_table'] =  '['.self::TOKEN_VAR_NAME.'.'.$explodedTokenName[1].';block=w:tbl]';
          }
        }
        else {
          $tokenMerge[$tmKey]['var_name'] =  '['.self::TOKEN_VAR_NAME.'.'.$tokenFullName.';block=w:image;ope=changepic]';
          $tokenMerge[$tmKey]['var_name_table'] =  '['.self::TOKEN_VAR_NAME.'.'.$tokenFullName.';block=w:image;ope=changepic]';
        }

        $allTokens[$explodedTokenName[0]][] = $explodedTokenName[1];
        $returnProperties[$explodedTokenName[1]] = 1;
      }

      //$this->_tokenMerge = $tokenMerge;

      $noofContact = count($activities);

      // contactrows to check for duplicate address
      $contactrows = array();
      foreach ($activities as $key => $value){
        $SelectedcontactID = $value['contact_id'];

        // get the details for all selected contacts
        list($contactDetails) = CRM_Utils_Token::getTokenDetails(array($SelectedcontactID),
          $returnProperties,
          NULL, NULL, FALSE,
          $allTokens
        );

        // populate contactrows array to check dupliacte address
        $contactrows[$SelectedcontactID] = $contactDetails[$SelectedcontactID];
      }

      $config = CRM_Core_Config::singleton();

      if (file_exists($config->extensionsDir.'/uk.co.vedaconsulting.module.wordmailmerge/tinybutstrong/tbs_class.php')) {
        // Intialize clsTinyButStrong
        require_once $config->extensionsDir.'/uk.co.vedaconsulting.module.wordmailmerge/tinybutstrong/tbs_class.php';
        require_once $config->extensionsDir.'/uk.co.vedaconsulting.module.wordmailmerge/tinybutstrong-opentbs/tbs_plugin_opentbs.php';
        $TBS = new clsTinyButStrong; // new instance of TBS
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN); // load the OpenTBS plugin
      } else {
        CRM_Core_Error::fatal(ts('Not able to load clsTinyButStrong class. Please make sure you have installed wordmailmerge extension.'));
      }
    }

    // Loop through all activities and prepare letter html
    $allHtmlStr = '';
    $allHtml = $wordTemplates = array();
    $processedActivities = array(); // To update activities to completed

  	foreach ($activities as $key => $activity) {

      $activity['activity_type_name'] = $actTypes[$activity['activity_type_id']];

      $isJoiner = FALSE;
      if ($activity['communication_type'] == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_NEW_JOINER) {
        $isJoiner = TRUE;
      }
 
      // Prepare letters for only activities which are not yet printed
      if (empty($activity['renewal_activity_status'])) {

        // Check if word mail merge is enabled
        if ($settingsArray['enable_word_mailmerge'] == 1) {
          // Check if a valid file is attached to the message template
          $attachment = self::getFileAttachment($reminderType, $activity, $isJoiner);
        }

        // This is word mail merge and we have a file attachment
        if ($settingsArray['enable_word_mailmerge'] == 1 && !empty($attachment)) {

          $template = $attachment;

          $selectedCID = $activity['contact_id'];

          $contactFormatted = array();
          $contactFormatted[$selectedCID] = $contactrows[$selectedCID];

          $membershipFormatted = array();
          $membershipFormatted = CRM_Utils_Token::getMembershipTokenDetails($activity['membership_id']);

          foreach ($tokenMerge as $atKey => $atValue) {
            // Replace hook tokens
            $explodedTokenName = explode('.', $atValue['token_name']);
            // this is fixed by assigning 'address_block' token into 'contact' token array // gopi@vedaconsulting.co.uk
            //need to do proper fix seems token named as contact.address_block
            // $atValue['token_name'] = ($atValue['token_name'] == 'address_block') ? 'contact.'.$atValue['token_name'] : $atValue['token_name'];
            if (array_key_exists($atValue['token_name'], $contactFormatted[$selectedCID]) ) {
              if (!empty($explodedTokenName[1]) && $explodedTokenName[0] != 'contact') {
                $vars[$key][$explodedTokenName[0]][$explodedTokenName[1]] = $contactFormatted[$selectedCID][$atValue['token_name']];
              }
              else{
                $vars[$key][$atValue['token_name']] = $contactFormatted[$selectedCID][$atValue['token_name']];
              }
            }
            else {
              if ($explodedTokenName[0] == 'membership') {
                $explodedTokenName[1] = ($explodedTokenName[1] == 'membership_id') ? 'id' : $explodedTokenName[1];
                $vars[$key][$explodedTokenName[0]][$explodedTokenName[1]] = CRM_Utils_Token::getMembershipTokenReplacement($explodedTokenName[0], $explodedTokenName[1], $membershipFormatted[$contactFormatted[$selectedCID]['membership_id']]);
              }
              else {
                $vars[$key][$atValue['token_name']] = CRM_Utils_Token::getContactTokenReplacement($atValue['token_name'], $contactFormatted[$selectedCID], FALSE, FALSE);
              }
            }

            //need to do proper fix, token_name.date seems not returning null value if not found
            if ($explodedTokenName[0] == 'token_name' && !is_array($vars[$key]['token_name'])) {
              $vars[$key][$atValue['token_name']] = '';
            }
          }

          foreach (CRM_Core_SelectValues::membershipTokens() as $token => $label) {
            $token = str_replace(array('{','}'),"",$token);
            $tokenNames = explode('.', $token);
            $vars[$key]['membership'][$tokenNames[1]] = $label;
          }

          foreach ($vars[$key] as $varKey => $varValue) {
            $explodeValues = explode('.', $varKey);
            if (isset($explodeValues[1]) && !empty($explodeValues[1])) {
              $vars[$key][$explodeValues[0]][$explodeValues[1]] = $vars[$key][$varKey];
              unset($vars[$key][$varKey]);
            }
          }

          $wordTemplates[] = $value;

          $TBS->LoadTemplate($template, OPENTBS_ALREADY_UTF8);
          $TBS->MergeBlock(self::TOKEN_VAR_NAME,$vars);

          // Word template - end

        } else {

          // Html template - start  
          $html = "";
          $html = $activity['details'];

          // Acvitity details is empty
          // may be moved from Unknown to print
          // So prepare the letter content using message template
          if (empty($html)) {
            $msgTemplate = $msgTemplateDetails;
            // Replace contact tokens
            CRM_Membershiprenewal_BAO_Membershiprenewal::replaceContactTokens($activity, $msgTemplate);

            // Replace membership renewal tokens
            CRM_Membershiprenewal_BAO_Membershiprenewal::replaceRenewalTokens($activity, $msgTemplate, $batchCreatedDate);

            $html = $msgTemplate['msg_html'];
            $html = str_replace('<html>' , '' , $html);
            $html = str_replace('</html>' , '' , $html);
          }
          $allHtml[] = $html;
          //$allHtml .= "<div STYLE='page-break-after: always'></div>";

          // Html template - end
        }

        $processedActivities[] = $activity['activity_id'];
      }
  	}

    // Return if no letters are to be printed
    if (empty($allHtml) && empty($wordTemplates)) {
      $message = ts('No letters to print');
      $status = 'warning';
      $messageTitle = 'Membership Renewal - Print Letters';
      // Redirect to batch page
      CRM_Membershiprenewal_Utils::setMessageAndRedirect($message, $messageTitle, $status, $batchUrl);
    }

    if (!empty($allHtml)) {
      $allHtmlStr = implode("<div STYLE='page-break-after: always'></div>", $allHtml);
    }

    if (!empty($allHtmlStr)) {
      $fileDetails = self::saveFile($allHtmlStr, $batchId, $batchId, $reminderType, FALSE);
    }

    if (!empty($wordTemplates)) {
      $fileDetails = self::saveFile($TBS, $batchId, $batchId, $reminderType, TRUE);
    }

    // Update activities to completed
    if (!empty($processedActivities)) {
      $processedActivitiesStr = implode(',', $processedActivities);
      $completedActivityStatus = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMPLETED_ACTIVITY_STATUS_ID;
      $updateSql = "UPDATE civicrm_activity SET status_id = %1 WHERE id IN ({$processedActivitiesStr})";
      $updateParams = array(
        '1' => array( $completedActivityStatus, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($updateSql, $updateParams);

      // Update custom field (Renewal Activity Status) to 'Printed'
      $columnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_STATUS_CUSTOM_FIELD_COLUMN_NAME;
      $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;

      $updateSql = "UPDATE {$tableName} SET {$columnName} = %1 WHERE entity_id IN ({$processedActivitiesStr})";
      $printedStatus = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_PRINTED_STATUS;
      $updateParams = array(
        '1' => array( $printedStatus, 'String'),
      );
      CRM_Core_DAO::executeQuery($updateSql, $updateParams);
    }

    return $fileDetails;
  }

  public static function composeHTMLForActivity($activityId, $msgTemplateId) {
    if (empty($activityId) || empty($msgTemplateId)) {
      return;
    }

    // Get activity details
    $activityDetails = CRM_Membershiprenewal_BAO_Batch::getActivitiesDetails($activityId);

    // get message template details
    $msgTemplateDetails = CRM_Membershiprenewal_Utils::getMessageTemplateDetails($msgTemplateId);

    $msgTemplate = $msgTemplateDetails;

    // Replace contact tokens
    CRM_Membershiprenewal_BAO_Membershiprenewal::replaceContactTokens($activityDetails, $msgTemplate);

    // Replace membership renewal tokens
    CRM_Membershiprenewal_BAO_Membershiprenewal::replaceRenewalTokens($activityDetails, $msgTemplate);

    return $msgTemplate;
  }

  public static function saveFile($htmlOrWordObj, $batchId, $entityId, $reminderType, $isWord = TRUE) {
    if (empty($batchId) || empty($entityId) || empty($reminderType)) {
      return;
    }

    $config = CRM_Core_Config::singleton( );
    $csv_path = $config->customFileUploadDir;

    $upload_date = date('Y-m-d H:i:s');

    // Get label for reminder_type
    $reminderLabel = CRM_Membershiprenewal_Constants::$reminder_type[$reminderType];
    $reminderLabel = str_replace(' ', '-', $reminderLabel);

    // Word template
    if ($isWord == TRUE) {
      $fileName = "Renewal-Letters-{$reminderLabel}-{$batchId}-{$upload_date}-Word.docx";
      $filePathName = "{$csv_path}{$fileName}";
      $htmlOrWordObj->Show(OPENTBS_FILE, $filePathName);
      $mimeType = 'application/msword';
    }
    // Html template
    else {
      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
      $smarty = CRM_Core_Smarty::singleton();
      $html = $smarty->fetch("string:{$htmlOrWordObj}");

      $fileName = "Renewal-Letters-{$reminderLabel}-{$batchId}-{$upload_date}-PDF.pdf";
      $filePathName = "{$csv_path}{$fileName}";
      require_once 'CRM/Utils/PDF/Utils.php';
      $pdfContent = CRM_Utils_PDF_Utils::html2pdf($html , $fileName , true , CRM_Core_DAO::$_nullArray );

      $handle = fopen($filePathName, 'w');
      file_put_contents($filePathName, $pdfContent);
      fclose($handle);

      $mimeType = 'application/pdf';
    }

    require_once 'CRM/Core/DAO/File.php';
    $fileDao = new CRM_Core_DAO_File();
    $fileDao->mime_type = $mimeType;
    $fileDao->uri = $fileName;
    $fileDao->upload_date = $upload_date;
    $fileDao->save();
    $fileId = $fileDao->id;

    require_once 'CRM/Core/DAO/EntityFile.php';
    $efileDao = new CRM_Core_DAO_EntityFile();
    $efileDao->entity_id = $entityId;
    $efileDao->entity_table = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;
    $efileDao->file_id = $fileId;
    $efileDao->save();

    $entityFileId = $efileDao->id;

    // Save entity file ID in custom table 
    self::saveEntityFileForBatch($batchId, $reminderType, $entityFileId);

    return array('id' => $fileId, 'eid' => $efileDao->id);
  }
  /**
   * Save entity file ID in custom table 
   * So that we can save files for different renewal types (1st reminder, 2nd reminder, etc)
   *
   * @param int $contactid
   * @param array $msgTemplateDetails
   */
  public static function saveEntityFileForBatch($batchId, $reminderType, $entityFileId) {

    if (empty($batchId) || empty($reminderType) || empty($entityFileId)) {
      return;
    }

    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_FILES_TABLE_NAME;

    $sql = "INSERT INTO {$tableName} SET batch_id = %1, entity_file_id = %2, reminder_type = %3";
    $params = array(
      '1' => array( $batchId, 'Integer'),
      '2' => array( $entityFileId, 'Integer'),
      '3' => array( $reminderType, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Function to replace contact tokens
   *
   * @param int $contactid
   * @param array $msgTemplateDetails
   */
  public static function replaceContactTokens($activityDetails, &$msgTemplateDetails) {

    if (empty($activityDetails['contact_id'])) {
      return;
    }

    // Get contact details
    $contactId = $activityDetails['contact_id'];
    $contactParams = array ('id' => $contactId);
    $contactDetails = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Contact','get', $contactParams);
    $contact = $contactDetails['values'][$contactId];

    $subject = $msgTemplateDetails['msg_subject'];
    //$text = $msgTemplateDetails['msg_text'];
    $html = $msgTemplateDetails['msg_html'];

    // Get all core tokens
    list($allTokens, $returnProperties) = self::getAllTokens($html, $subject);
    list($contacts) = CRM_Utils_Token::getTokenDetails(
      $contactParams,
      $returnProperties,
      FALSE,
      FALSE,
      array(array('membership_id', '=', $activityDetails['membership_id'], 0, 0)),
      $allTokens
    );

    $hookTokens = self::getAllHookTokens();
    $categories = array_keys($hookTokens);

    require_once("CRM/Mailing/BAO/Mailing.php");
    $mailing = new CRM_Mailing_BAO_Mailing;
    //$mailing->body_text = $text;
    $mailing->subject = $subject;
    $mailing->body_html = $html;
    $tokens = $mailing->getTokens();

    require_once("CRM/Utils/Token.php");
    //$subject = CRM_Utils_Token::replaceDomainTokens($subject, $domain, true, $tokens['subject']);
    //$html    = CRM_Utils_Token::replaceDomainTokens($html, $domain, true, $tokens['html']);
    if ($contactId) {
      $subject = CRM_Utils_Token::replaceContactTokens($subject, $contacts[$contactId], false, $tokens['subject']);
      $html    = CRM_Utils_Token::replaceContactTokens($html, $contacts[$contactId], false, $tokens['html']);
      
      $category = array('contact');
      $subject = CRM_Utils_Token::replaceHookTokens($subject, $contacts[$contactId] , $categories, false, false);
      $html    = CRM_Utils_Token::replaceHookTokens($html, $contacts[$contactId] , $categories , true, false);
    }

    $msgTemplateDetails['msg_subject'] = $subject;
    $msgTemplateDetails['msg_html'] = $html;
  }

  /**
   * Function to replace membership renewal tokens
   *
   * @param int $membershipId
   * @param array $msgTemplateDetails
   */
  public static function replaceRenewalTokens($activityDetails, &$msgTemplateDetails) {

    if (empty($activityDetails['membership_id'])) {
      return;
    }

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Get membership details
    $membershipId = $activityDetails['membership_id'];
    $memParams = array ('id' => $membershipId);
    $memDetails = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Membership','get', $memParams);
    $membership = $memDetails['values'][$membershipId];

    // Get membership type details
    $memTypeParams = array ('id' => $membership['membership_type_id']);
    $memTypeDetails = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('MembershipType','get', $memTypeParams);

    $membershipFees = $memTypeDetails['values'][$membership['membership_type_id']]['minimum_fee'];

    $membership['membership_fee'] = CRM_Utils_Money::format($membershipFees);
    $membership['reminder_date'] = $activityDetails['activity_date_time'];
    $membership['renewal_page_link'] = $settingsArray['renewal_page_tiny_url'];

    $subject = $msgTemplateDetails['msg_subject'];
    //$text = $msgTemplateDetails['msg_text'];
    $html = $msgTemplateDetails['msg_html'];

    // Get renewal tokens
    $renewalTokens = CRM_Membershiprenewal_Constants::$renewalTokens;

    // Replace membership tokens
    foreach ($renewalTokens as $label => $token) {

      // get token part
      $tokenStr = str_replace('{membershiprenewal.', '', $token);
      $tokenStr = str_replace('}', '', $tokenStr);

      if (isset($membership[$tokenStr]) && !empty($membership[$tokenStr])) {
        $value = $membership[$tokenStr];
      } else {
        $value = '';
      }

      if (strpos($tokenStr, '_date') !== false) {
        $value = CRM_Utils_Date::customFormat($value);
      }

      $subject = str_replace($token, $value, $subject);
      //$text = str_replace($token, $value, $text);
      $html = str_replace($token, $value, $html);
    }

    $msgTemplateDetails['msg_subject'] = $subject;
    //$msgTemplateDetails['msg_text'] = $text;
    $msgTemplateDetails['msg_html'] = $html;

  }

  /**
   * Function to get prepared letters to download
   *
   * @param int $batchId
   *
   * @return array $fileId
   */
  public static function getPreparedLetters($batchId, $reminderType) {

    if (empty($batchId) || empty($reminderType)) {
      return;
    }

    $files = array();

    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_FILES_TABLE_NAME;

    $sql = "
SELECT f.* FROM civicrm_entity_file e
INNER JOIN {$tableName} batchfiles ON batchfiles.entity_file_id = e.id
LEFT JOIN civicrm_file f ON f.id = e.file_id
WHERE e.entity_id = %1 AND e.entity_table = %2 AND batchfiles.reminder_type = %3";
    $params = array(
      '1' => array( $batchId, 'Integer'),
      '2' => array( CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME, 'String'),
      '3' => array( $reminderType, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while($dao->fetch()) {
      $files[$dao->id]['id'] = $dao->id;
      $files[$dao->id]['uri'] = $dao->uri;
    }

    return $files;
  }

  /**
   * Function to calculate scheduled date for resend
   * Schedule activity date to 9 AM same day if current time < 9 AM
   * else 9 AM next day
   */
  public static function getScheduledDateForReSent() {
    $timestamp = strtotime(date("Y-m-d H:i:s"));
    // Current time is less than 9 AM
    // Schedule activity date for same day
    if (date('G',$timestamp) < 9) {
      $scheduleTime = strtotime("9am", $timestamp); 
    }
    else {
      $scheduleTime = strtotime("+1 day 9am", $timestamp);
    }
    $scheduleDate = date("Y-m-d H:i:s", $scheduleTime);

    return $scheduleDate;
  }

  /**
   * Function to calculate scheduled date for 1st reminder
   * Calculate activity date to membership end date minus 1st reminder offset from settings
   * If the calculated 1st reminder date less than current date, then current date will be used. 
   *
   * @param date $endDate
   * @param array $settingsArray
   *
   * @return date $activityDate
   */
  public static function getScheduledDateForFirstReminder($endDate, $settingsArray, $isNewJoiner = FALSE) {
    if (empty($endDate) || empty($settingsArray['renewal_first_reminder'])) {
      return date("Y-m-d H:i:s");
    }

    $currentTimeStamp = strtotime(date("Y-m-d"));
    if ($isNewJoiner) {
      // Send welcome email in the next hour for new joiners
      $activityTimeStamp = $currentTimeStamp;
    } else {
      // Calculate activity date to membership end date minus 1st reminder offset from settings
      $endDateTimeStamp = strtotime($endDate);
      $activityTimeStamp = strtotime("-{$settingsArray['renewal_first_reminder']} day", $endDateTimeStamp);
    }
    
    if ($activityTimeStamp < $currentTimeStamp) {
      $activityTimeStamp = $currentTimeStamp;
    }
    $activityDate = date("Y-m-d", $activityTimeStamp).' '.date("G").':00:00';
    // Set next hour for activity time
    $activityDate = date('Y-m-d H:i:s', strtotime('+0 minutes', strtotime($activityDate)));

    return $activityDate;
  }

  /**
   * Function to get all core tokens
   */
  public static function getAllTokens($html, $subject) {
    $domain = CRM_Core_BAO_Domain::getDomain();

    $tplTokens = array_merge(
      CRM_Utils_Token::getTokens($html),
      CRM_Utils_Token::getTokens($subject));

    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = $tokens + CRM_Core_SelectValues::membershipTokens();

    $tokenMerge = array();
    foreach ($tokens as $key => $label) {
      $tokenMerge[] = array(
        'id' => $key,
        'text' => $label,
      );
    }

    foreach ($tokenMerge as $tmKey => $tmValue) {
      $tokenFullName =  str_replace(array('{','}'),"",$tmValue['id']);
      $explodedTokenName =  explode('.', $tokenFullName);
      $allTokens[$explodedTokenName[0]][] = $explodedTokenName[1];
      if ($explodedTokenName[0] == 'contact') {
        $returnProperties[$explodedTokenName[1]] = 1;
      }
    }
    $allTokens = $allTokens + $tplTokens;

    return array($allTokens, $returnProperties);
  }

  /**
   * Function to get all hook tokens
   */
  public static function getAllHookTokens() {
    // call token hook
    $hookTokens = array();
    CRM_Utils_Hook::tokens($hookTokens);
    return $hookTokens;  
  }

  /**
   * Function to check if file is attached to message template
   */
  public static function getFileAttachment($reminderType, $activity, $isJoiner = FALSE) {

    $template = '';

    $messageTemplateId = $fileId = '';
    $messageTemplateId = CRM_Membershiprenewal_BAO_Batch::getMessageTemplateForRenewalReminder($reminderType, $activity['membership_type_id'], $activity['activity_type_name'], $isJoiner);

    if (!empty($messageTemplateId) && CRM_Core_DAO::checkTableExists('veda_civicrm_wordmailmerge')) {
      $mysql =  "SELECT * FROM veda_civicrm_wordmailmerge WHERE msg_template_id = %1";
      $params = array(1 => array($messageTemplateId, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($mysql, $params);
      if ($dao->fetch()) {
        $fileId = $dao->file_id;
      }
      if (!empty($fileId)) {
        $file_sql = "SELECT * FROM civicrm_file WHERE id = %1";
        $file_params = array(1 => array($fileId, 'Integer'));
        $file_dao = CRM_Core_DAO::executeQuery($file_sql, $file_params);
        //$dao = CRM_Core_DAO::executeQuery($sql);
        if ($file_dao->fetch()) {
          $default['fileID']        = $file_dao->id;
          $default['mime_type']     = $file_dao->mime_type;
          $default['fileName']      = $file_dao->uri;
          $default['cleanName']     = CRM_Utils_File::cleanFileName($file_dao->uri);
          $default['fullPath']      = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $file_dao->uri;
          $default['deleteURLArgs'] = CRM_Core_BAO_File::deleteURLArgs('civicrm_file', $messageTemplateId, $file_dao->id);
        }
        $template = $default['fullPath'];
      }
    }

    return $template;
  }
}
