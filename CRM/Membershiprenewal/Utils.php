<?php

require_once 'CRM/Core/Page.php';

class CRM_Membershiprenewal_Utils {
  /**
   * CiviCRM API wrapper
   *
   * @param string $entity
   * @param string $action
   * @param string $params
   *
   * @return array of API results
   */
  public static function CiviCRMAPIWrapper($entity, $action, $params) {

    if (empty($entity) || empty($action) || empty($params)) {
      return;
    }

    try {
      $result = civicrm_api3($entity, $action, $params);
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('CiviCRM API Call Failed');
      CRM_Core_Error::debug_var('CiviCRM API Call Error', $e);
      return;
    }

    return $result;
  }

  /**
   * Get Message templates
   *
   * @return array of message templates ids, title
   */
  public static function getMessageTemplates() {

    $msgTemplates = array('' => '- select -');

    // Get all message templates from CiviCRM
    $result = self::CiviCRMAPIWrapper('MessageTemplate', 'get', array(
      'sequential' => 1,
      'return' => array("id", "msg_title"),
      'options' => array('limit' => 0),
    ));

    if (!empty($result['values'])) {
      foreach($result['values'] as $key => $value) {
        $msgTemplates[$value['id']] = $value['msg_title'];
      }
    }

    return $msgTemplates;
  }

  /**
   * Get all membership types
   *
   * @return array of membership types ids, title
   */
  public static function getAllMembershipTypes() {

    //$memTypes = array('' => '- select -');
    $memTypes = array();

    // Get all membership types from CiviCRM
    $result = self::CiviCRMAPIWrapper('MembershipType', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      'return' => array("id", "name"),
      'options' => array('limit' => 0),
    ));

    if (!empty($result['values'])) {
      foreach($result['values'] as $key => $value) {
        $memTypes[$value['id']] = $value['name'];
      }
    }

    return $memTypes;
  }

  /**
   * Get all activity types
   *
   * @return array of membership types ids, title
   */
  public static function getAllActivityTypes() {

    //$memTypes = array('' => '- select -');
    $actTypes = array();

    // Get all membership types from CiviCRM
    $result = self::CiviCRMAPIWrapper('OptionValue', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      'option_group_id' => "activity_type",
      'options' => array('limit' => 0),
    ));

    if (!empty($result['values'])) {
      foreach($result['values'] as $key => $value) {
        $actTypes[$value['value']] = $value['name'];
      }
    }

    return $actTypes;
  }

  /**
   * Get all membership types which has renewal settings
   *
   * @return array of membership types ids
   */
  public static function getAllMembershipTypesSetForRenewal() {

    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    foreach($settingsArray as $key => $value) {
      if (strpos($key, '_message_template_1_') !== false) {
        $tempArray = @explode('_message_template_1_', $key);
        $memTypes[$tempArray[1]] = $tempArray[1];
      }
    }

    return $memTypes;
  }

  /**
   * Get activity statuses
   *
   * @return array of activity statuses
   */
  public static function getActivityStatuses() {

    $activityStatuses = array();

    $result = self::CiviCRMAPIWrapper('OptionValue', 'get', array(
      'sequential' => 1,
      'option_group_id' => "activity_status",
      'options' => array('limit' => 0),
    ));

    if (!empty($result['values'])) {
      foreach($result['values'] as $key => $value) {
        $activityStatuses[$value['value']] = $value['label'];
      }
    }

    return $activityStatuses;
  }

  /**
   * Get domain details
   *
   * @return array of domain details
   */
  public static function getDomainDetails() {

    $result = self::CiviCRMAPIWrapper('Domain', 'get', array(
      'sequential' => 1,
    ));

    return $result['values'][0];
  }

  /**
   * Get Message templates details
   *
   * @return array of message template details
   */
  public static function getMessageTemplateDetails($msgTemplateId) {

    if (empty($msgTemplateId)) {
      return;
    }

    // Get message template details from CiviCRM
    $result = self::CiviCRMAPIWrapper('MessageTemplate', 'get', array(
      'id' => $msgTemplateId,
    ));

    return $result['values'][$msgTemplateId];
  }

  /**
   * Function to create scheduled reminder for sending membership renewal emails
   */
  public static function createScheduledReminder() {

    // Get Membership reminder email - Activity Type id 
    $emailActivityTypeId = CRM_Core_OptionGroup::getValue('activity_type',
      CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME,
      'name'
    );

    // Get domain details (For from email and name)
    $domain = CRM_Membershiprenewal_Utils::getDomainDetails();

    $params = array(
      'name' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_NAME,
      'title' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_TITLE,
      'recipient' => 3,
      'limit_to' => 1,
      'entity_value' => $emailActivityTypeId, // Membership reminder email - Activity Type id
      'entity_status' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled activity status
      'start_action_offset' => 0,
      'start_action_unit' => 'hour',
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'is_repeat' => 0,
      'is_active' => 1,
      'body_html' => '{activity.details}',
      'subject' => '{activity.subject}',
      'record_activity' => 1,
      'mapping_id' => 1,
      'mode' => 'Email',
      'from_name' => $domain['from_name'],
      'from_email' => $domain['from_email'],
    );

    // Check if the schedule reminder already exists
    $checkParams = array(
      'name' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_NAME,
    );
    $schuleReminderResult = self::CiviCRMAPIWrapper('ActionSchedule', 'get', $checkParams);
    // Create if not exists
    if ($schuleReminderResult['count'] == 0) {
      self::CiviCRMAPIWrapper('ActionSchedule', 'create', $params);  
    }
  }

  /**
   * Function to create scheduled job for preparing membership renewal dates
   *
   * @params boolean $is_active (is the schedule job active or not)
   */
  public static function createScheduledJob($is_active = 1) {

    // Chekc if the schedule job exists
    $selectSql = "SELECT * FROM civicrm_job WHERE api_entity = %1 AND api_action = %2";
    $selectParams = array(
      '1' => array( 'membershiprenewal', 'String' ),
      '2' => array( 'preparerenewaldates', 'String' ),
    );
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    if (!$selectDao->fetch()) {
      // Create schedule job, if not exists
      $domainId = CRM_Core_Config::domainID();
      $query = "INSERT INTO civicrm_job SET domain_id = %1, run_frequency = %2, last_run = NULL, name = %3, description = %4,
      api_entity = %5, api_action = %6, parameters = NULL, is_active = %7";
      $params = array(
        '1' => array($domainId, 'Integer' ),
        '2' => array('Daily', 'String' ),
        '3' => array('Membership Renewal - Prepare Dates', 'String' ),
        '4' => array('To prepare dates for membership renewal', 'String' ),
        '5' => array('membershiprenewal', 'String' ),
        '6' => array('preparerenewaldates', 'String' ),
        '7' => array(1, 'Integer' ),
      );
      CRM_Core_DAO::executeQuery($query, $params);
    } else {
      // Enabled/Disable based on settings
      $updateSql = "UPDATE civicrm_job SET is_active = %3 WHERE api_entity = %1 AND api_action = %2";
      $updateParams = array(
        '1' => array('membershiprenewal', 'String' ),
        '2' => array('preparerenewaldates', 'String' ),
        '3' => array($is_active , 'Integer' ),
      );
      $updateDao = CRM_Core_DAO::executeQuery($updateSql, $updateParams);
    }
  }

  /**
   * Function to create scheduled job for preparing membership renewal dates
   */
  public static function prepareRenewalDatesForMemberships() {
    // Get membership renewal settings
    $settingsArray = self::getMembershipRenewalSettings();

    // Return if the setting params are not available
    if (empty($settingsArray['renewal_years']) || empty($settingsArray['renewal_period'])) {
        return;
    }

    // Get all current membership statuses
    $memStatusResult = self::CiviCRMAPIWrapper('MembershipStatus', 'get', array(
      'sequential' => 1,
      'return' => array("id"),
      'is_current_member' => 1,
    ));

    // Return if there are no current membership statuses
    if (empty($memStatusResult['values'])) {
      return;
    }

    $curMemStatuses = array();
    foreach ($memStatusResult['values'] as $key => $value) {
      $curMemStatuses[] = $value['id'];
    }

    // Get membership types which are not 'Auto Renewal'
    $allMemTypes = self::CiviCRMAPIWrapper('MembershipType', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      'return' => array("id", "auto_renew"),
      'options' => array('limit' => 0),
    ));
    $allNonAutoRenewMemTypes = array();
    foreach($allMemTypes['values'] as $key => $value) {
      // Do not include membership types having Auto-rewew = 2 (Auto-renew required)
      if ($value['auto_renew'] != '2') { 
        $allNonAutoRenewMemTypes[] = $value['id'];
      }
    }

    if (!empty($allNonAutoRenewMemTypes)) {
      $allNonAutoRenewMemTypesStr = implode(',', $allNonAutoRenewMemTypes);
      $allNonAutoRenewMemTypesSQL = " AND membership_type_id IN ({$allNonAutoRenewMemTypesStr})";
    } else {
      $allNonAutoRenewMemTypesSQL = '';
    }

    $curMemStatusesStr = implode(',', $curMemStatuses);

    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    //$years = self::getValidYearsForRenewal($settingsArray['renewal_years']);

    $renewalPeriod = $settingsArray['renewal_period'];
    $renewalYears = $settingsArray['renewal_years'];

    // Truncate membership renewal dates table
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE {$tableName}");

    for ($j = 1; $j <= $renewalYears; $j++) {
      // Select all memberships with current membership statuses
      $sql = "
INSERT INTO $tableName (membership_id, membership_type_id, contact_id, join_date, start_date, end_date, renewal_date)
SELECT m.id, m.membership_type_id, m.contact_id, m.join_date, m.start_date, m.end_date, 
DATE_SUB(
  DATE_SUB(
    DATE_ADD(m.join_date,  INTERVAL {$j} YEAR), 
  INTERVAL {$renewalPeriod} MONTH), 
INTERVAL 1 DAY) as renewal_date
FROM civicrm_membership m
INNER JOIN civicrm_contact c ON m.contact_id = c.id
WHERE m.join_date IS NOT NULL AND c.is_deleted = 0 AND m.status_id IN ({$curMemStatusesStr}) AND m.is_test = 0 {$allNonAutoRenewMemTypesSQL}
";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Function get membership renewal settings
   *
   * @return array $settingsArray (membership settings)
   */
  public static function getMembershipRenewalSettings() {
    // Get renewal settings from civicrm_settings table
    $settingsStr = CRM_Core_BAO_Setting::getItem(
      CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SETTING_GROUP,
      CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SETTING_NAME
    );

    return unserialize($settingsStr);
  }

  /**
   * Function get valid years for membership renewal
   * 
   * @params int $renewal_years (from membership renewal settings)
   *
   * @return array $years (valid years for renewal)
   */
  public static function getValidYearsForRenewal($renewal_years) {

    if (empty($renewal_years)) {
      return;
    }

    $years = array();
    $currentYear = date('Y');
    // Add number of renewal_years to current year
    $endYear = $currentYear + $renewal_years;
    
    for ($j = $currentYear; $j < $endYear ; $j++) { 
      $years[$j] = $j;
    }

    return $years;
  }

  /**
   * Function get valid month/year list for membership renewal
   * 
   * @params array $settingsArray (membership renewal settings)
   *
   * @return array $renewalperiod (valid months/years for renewal)
   */
  public static function getValidListForRenewal($settingsArray, $getUnprocessed = FALSE) {

    $currentDate = date('Y-m-01');

    $startOffset = $settingsArray['renewal_start_offset'];
    $endOffset = $settingsArray['renewal_end_offset'];

    // Start date for which renewals can be processed
    $startDate = date('Y-m-d', strtotime("-{$startOffset} months", strtotime($currentDate)));
    $endDate = date('Y-m-d', strtotime("+{$endOffset} months", strtotime($currentDate)));

    $currentDate = new DateTime();
    $currentRenewalDate = $currentDate->format("Y-m-01");
		$currentRenewalTime = strtotime($currentRenewalDate);
    $renewalOffsetDate = date("Y-m-d", strtotime("+{$settingsArray['renewal_period']} month", $currentRenewalTime));
    
    $start = new DateTime($startDate);
    $start->modify('first day of this month');
    $end = new DateTime($endDate);
    $end->modify('first day of next month');
    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($start, $interval, $end);

    //if (!$getUnprocessed) {
      //$renewalList = array('' => ' -select - ');
    //}
    $firstKey = '';
    foreach ($period as $dt) {
        $key = $dt->format("m-Y");
        $value = $dt->format("M, Y");

        // For unprocessed renewal list, we need to check only until current month
        if ($getUnprocessed) {
          $renewalDate = $dt->format("Y-m-01");
          if (strtotime($renewalDate) <= strtotime($renewalOffsetDate)) {
            $renewalList[$key] = $value;
          }
        } else {
          $renewalList[$key] = $value;
        }
    }

    // Get only unprocessed renewal list, by checking batch table
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;
    foreach ($renewalList as $key => $value) {
      $sql = "SELECT * FROM {$tableName} WHERE renewal_month_year = %1";
      $params = array('1' => array($key, 'String'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        unset($renewalList[$key]);
      }
    }

    // If getting unprocessed list, return only first element
    if ($getUnprocessed) {
      foreach ($renewalList as $key => $value) {
        return array($key => $value);
      }
    }
    return $renewalList;
  }

  /**
   * Function get valid month/year list for membership renewal start month
   */
  public static function getValidListForRenewalStart() {

    $currentDate = date('Y-m-01');

    $startOffset = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_START_YEAR_MONTH_OFFSET;

    // Start date for which renewals can be processed
    $startDate = date('Y-m-d', strtotime("-{$startOffset} months", strtotime($currentDate)));
    $endDate = date('Y-m-d', strtotime($currentDate));

    $start    = new DateTime($startDate);
    $start->modify('first day of this month');
    $end      = new DateTime($endDate);
    $end->modify('first day of next month');
    $interval = DateInterval::createFromDateString('1 month');
    $period   = new DatePeriod($start, $interval, $end);

    $renewalList = array('' => ' -select - ');
    foreach ($period as $dt) {
        $key = $dt->format("m-Y");
        $renewalList[$key] = $dt->format("M, Y");
    }

    return $renewalList;
  }

  /**
   * Get CiviCRM version using SQL
   * using BAO function to get version is not compatible with all versions
   */
  public static function getCiviVersion() {
    $sql = "SELECT version FROM civicrm_domain";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    return $dao->version;
  }

  /**
   * Function to set message and redirect to a page/form
   */
  public static function setMessageAndRedirect($message = 'Membership Renewal', $title = 'Membership Renewal', $status = 'success', $url) {

    if (empty($url)) {
      $url = CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1");
    }

    CRM_Core_Session::setStatus($message, $title, $status);
    CRM_Utils_System::redirect($url);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to redirect to previous pafe
   */
  public static function redirectToPreviousPage($message = 'Membership Renewal', $title = 'Membership Renewal', $status = 'success') {
    // Get HTTP referrer if available
    $url = $_SERVER['HTTP_REFERER'];
    // If empty, redirect to membership renewal dashboard
    if (empty($url)) {
      $url = CRM_Utils_System::url('civicrm/membershiprenewal', "reset=1");
    }

    CRM_Core_Session::setStatus($message, $title, $status);
    CRM_Utils_System::redirect($url);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to get logged in user's Contact ID
   */
  public static function getLoggedInUserContactID($message = 'Membership Renewal', $title = 'Membership Renewal', $status = 'success') {

    // Get logged in user ID
    $session =& CRM_Core_Session::singleton( );
    return $session->get( 'userID' );

  }

  /**
   * Function to get numbers with ordinal suffix
   */
  public static function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
  }

  /**
   * Function to get membership ids which are not included for membership renewal in date range
   * @param month, year
   * @return array membershipIDs
   */
  public static function getExcludedFromMembershipRenewal($month, $year, $returnOnlyMembershipIDs = TRUE, $contactIds = array()) {
    if (empty($month) || empty($year)) {
      return;
    }

    $excludedMembershipIds = array();
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate  = date("Y-m-t", strtotime($firstDate));

    // Get all the membership up which are not included for renewals
    $selectSql = "
SELECT renewal.membership_id
, renewal.renewal_date
, renewal.end_date
, renewal.contact_id
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON renewal.contact_id = contact.id
WHERE (renewal.renewal_date < %1 OR renewal.renewal_date > %2)
";
    $selectParams = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );

    // Additional criteria - Search only Contact Ids
    if (!empty($contactIds)) {
      $contactidsStr = implode(',', $contactIds);
      $selectSql .= " AND renewal.contact_id IN ({$contactidsStr})";
    }

    $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    while ($dao->fetch()) {
      if ($returnOnlyMembershipIDs) {
        $excludedMembershipIds[] = $dao->membership_id;
      }
      else{
        $excludedMembershipIds[] = $dao->toArray();
      }
    }

    return $excludedMembershipIds;
  }

  public static function getMembershipRenewalDetailsByMembershipIds($membershipIDs = array(), $contactIds = array()) {
    if (empty($membershipIDs)) {
      return;
    }

    $membershipIDsIn = implode(', ', $membershipIDs);
    $returnResult = array();
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    // Get all the membership up which are not included for renewals
    $selectSql = "
SELECT renewal.membership_id
, renewal.renewal_date
, renewal.end_date
, renewal.contact_id
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON renewal.contact_id = contact.id
WHERE renewal.membership_id IN ({$membershipIDsIn})
";

    // Additional criteria - Search only Contact Ids
    if (!empty($contactIds)) {
      $contactidsStr = implode(',', $contactIds);
      $selectSql .= " AND renewal.contact_id IN ({$contactidsStr})";
    }

    $dao = CRM_Core_DAO::executeQuery($selectSql);
    while ($dao->fetch()) {
      $returnResult[] = $dao->toArray();
    }

    return $returnResult;
  }

  /**
   * Function to get all membership which are not included in batch
   * @param month, year
   * @return array
   */
  public static function getAllMembershipExcludedByBatchId($batchId) {
    if (empty($batchId)) {
      return array();
    }

    $excludedMembershipDetails = array();
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EXCLUDED_MEMBER_DETAILS;

    // Get all the membership up which are not included for renewals
    $selectSql = "
SELECT a.membership_id
, a.reason
, member.join_date as join_date
, member.start_date as start_date
, member.end_date as end_date
, member_type.name as membership_type
, contact.display_name as display_name
, contact.id as contact_id
FROM {$tableName} a
INNER JOIN civicrm_membership member ON a.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON member.contact_id = contact.id
WHERE a.batch_id = %1 
    ";
    $selectParams = array(
      '1' => array($batchId, 'Integer' ),
    );
    $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    while ($dao->fetch()) {
      $excludedMembershipDetails[] = $dao->toArray();
    }

    return $excludedMembershipDetails;
  }

  public static function getResourceUrl($entityUrl = 'extensionsURL') {
    $civiVersion = self::getCiviVersion();

    if ($civiVersion < 4.7) {
      $config = CRM_Core_Config::singleton();
      $extSettingUrl = $config->$entityUrl;
    } else {
      $extSetting = self::CiviCRMAPIWrapper('Setting', 'get', array(
        'sequential' => 1,
        'return' => array($entityUrl),
      ));
      $extSettingUrl = $extSetting['values'][0][$entityUrl];
      $extSettingUrl = Civi::paths()->getUrl($extSettingUrl, 'absolute');
    }

    return $extSettingUrl;
  }

  public static function getLoadingImage() {
    $civiResourceUrl = CRM_Membershiprenewal_Utils::getResourceUrl('userFrameworkResourceURL');
    $loadingImageUrl = $civiResourceUrl.'/i/loading-E6E6DC.gif';
    $loadingImage = "<tr><td width='100%' height='50px' style='text-align:center; vertical-align: middle;'><img src='{$loadingImageUrl}'></td></tr>";

    return $loadingImage;
  }

  /**
   * Function to get default SMS provider
   */
  public static function getDefaultSMSProvider() {

    $SMSproviderId = '';

    $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');
    foreach($providers as $provider) {
      $SMSproviderId = $provider['id'];
    }

    return $SMSproviderId;
  }
}
