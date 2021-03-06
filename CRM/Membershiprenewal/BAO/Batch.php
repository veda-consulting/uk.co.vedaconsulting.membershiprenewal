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
 * Membership renewal Batch BAO class.
 */

class CRM_Membershiprenewal_BAO_Batch extends CRM_Membershiprenewal_DAO_Batch {
  /**
   * Generate renewal batch name.
   *
   * @return string
   *   batch name
   */
  public static function generateRenewalBatchName() {
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;
    $sql = "SELECT max(id) FROM {$tableName}";
    $batchNo = CRM_Core_DAO::singleValueQuery($sql) + 1;

    // Get renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
      $batchStr = 'New Joiners & Renewals';
    } else {
      $batchStr = 'Renewals';
    }

    return ts($batchStr.' %1', array(1 => $batchNo)) . ': ' . date('Y-m-d');
  }

  /**
   * Get batch list
   *
   * @return array $batchList
   */
  public static function getRenewalBatchList($batchId = NULL) {
    $batchList = array();
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;

    $where = '';
    if (!empty($batchId)) {
      $where = " WHERE b.id = {$batchId}";
    }

    $sql = "
SELECT b.*, c.display_name FROM {$tableName} b 
LEFT JOIN civicrm_contact c ON c.id = b.created_id
{$where}
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $batchList[$dao->id]['id'] = $dao->id;
      $batchList[$dao->id]['title'] = $dao->title;
      $batchList[$dao->id]['created_id'] = $dao->created_id;
      $batchList[$dao->id]['created_name'] = $dao->display_name;
      $batchList[$dao->id]['created_date'] = CRM_Utils_Date::customFormat($dao->created_date);
      $batchList[$dao->id]['first_reminder_date'] = CRM_Utils_Date::customFormat($dao->first_reminder_date);
      $batchList[$dao->id]['second_reminder_date'] = CRM_Utils_Date::customFormat($dao->second_reminder_date);
      $batchList[$dao->id]['third_reminder_date'] = CRM_Utils_Date::customFormat($dao->third_reminder_date);
      $batchList[$dao->id]['print_file_id'] = $dao->print_file_id;
      $batchList[$dao->id]['print_entity_file_id'] = $dao->print_entity_file_id;

      // Renewal month year
      $monthYearArray = explode('-', $dao->renewal_month_year);
      $batchList[$dao->id]['renewal_month_year'] = $dao->renewal_month_year;
      $monthLabel = CRM_Membershiprenewal_Constants::$months[$monthYearArray[0]];
      $batchList[$dao->id]['renewal_month_year_label'] = $monthLabel .', '.$monthYearArray[1];
    }

    return $batchList;
  }

  /**
   * Get batch activities
   *
   * @return array $batchList
   */
  public static function getRenewalBatchActivitiesList($batchId, $reminderType, $activityTypeId = NULL) {

    if (empty($batchId) || empty($reminderType)) {
      return;
    }

    // Get activity statuses
    $activityStatuses = CRM_Membershiprenewal_Utils::getActivityStatuses();

    $config = CRM_Core_Config::singleton(); 

    $activitiesList = array();
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ENTITY_BATCH_TABLE_NAME;

    // Custom table, where membership_id is saved
    $customTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;
    $logTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EXCLUDED_MEMBER_DETAILS;
    $customFieldColumnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_CUSTOM_FIELD_COLUMN_NAME;
    $customFieldStatusColumnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_STATUS_CUSTOM_FIELD_COLUMN_NAME;

    $whereClauses = array();
    $whereClauses[] = " WHERE (1)";
    if (!empty($batchId)) {
      $whereClauses[] = "b.batch_id = {$batchId}";
    }
    if (!empty($reminderType)) {
      $whereClauses[] = "b.reminder_type = {$reminderType}";
    }
    if (!empty($activityTypeId)) {
      $whereClauses[] = "a.activity_type_id = {$activityTypeId}";
    }

    $whereClause = implode(' AND ', $whereClauses);

    $sql = "
SELECT b.id, b.batch_id, b.activity_id, a.activity_type_id, a.subject, a.details, a.status_id, 
a.activity_date_time, c.display_name, ac.contact_id, custom.{$customFieldColumnName}, custom.{$customFieldStatusColumnName}, member.membership_type_id, log.communication_type
FROM {$tableName} b 
LEFT JOIN civicrm_activity a ON a.id = b.activity_id
LEFT JOIN {$customTableName} custom ON custom.entity_id = b.activity_id
LEFT JOIN {$logTableName} log ON custom.membership_id = log.membership_id AND b.batch_id = log.batch_id
LEFT JOIN civicrm_membership member ON custom.{$customFieldColumnName} = member.id
LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
LEFT JOIN civicrm_contact c ON c.id = ac.contact_id
{$whereClause}
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $activitiesList[$dao->activity_type_id][$dao->id]['id'] = $dao->id;
      $activitiesList[$dao->activity_type_id][$dao->id]['batch_id'] = $dao->batch_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['activity_id'] = $dao->activity_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['activity_type_id'] = $dao->activity_type_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['subject'] = $dao->subject;
      $activitiesList[$dao->activity_type_id][$dao->id]['details'] = $dao->details;
      $activitiesList[$dao->activity_type_id][$dao->id]['status_id'] = $dao->status_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['activity_status'] = $activityStatuses[$dao->status_id];
      $activitiesList[$dao->activity_type_id][$dao->id]['activity_date_time'] = $dao->activity_date_time;
      $activitiesList[$dao->activity_type_id][$dao->id]['activity_contact'] = $dao->display_name;
      $activitiesList[$dao->activity_type_id][$dao->id]['contact_id'] = $dao->contact_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['membership_id'] = $dao->{$customFieldColumnName};
      $activitiesList[$dao->activity_type_id][$dao->id]['membership_type_id'] = $dao->membership_type_id;
      $activitiesList[$dao->activity_type_id][$dao->id]['renewal_activity_status'] = $dao->{$customFieldStatusColumnName};
      $activitiesList[$dao->activity_type_id][$dao->id]['communication_type'] = $dao->communication_type;
      
      // Highlight the row in red if activity status is scheduled
      $tr_class = '';
      if ($dao->status_id == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID) {
        $tr_class = 'status-overdue';
      }
      $activitiesList[$dao->activity_type_id][$dao->id]['tr_class'] = $tr_class;
    }

    if (!empty($activityTypeId)) {
      return $activitiesList[$activityTypeId];
    }
    return $activitiesList;
  }

  /**
   * Get batch activities
   *
   * @return array $batchList
   */
  public static function getActivitiesDetails($activityId) {

    if (empty($activityId)) {
      return;
    }
    // Custom table, where membership_id is saved
    $customTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;
    $customFieldColumnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_CUSTOM_FIELD_COLUMN_NAME;
    $MemEndDateCustomColumnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_END_DATE_CUSTOM_FIELD_COLUMN_NAME;
    $commTypeColumns = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_COMM_TYPE_COLUMN_NAME;

    $whereClauses = array();
    $whereClauses[] = " WHERE (1)";
    if (!empty($activityId)) {
      $whereClauses[] = "a.id = {$activityId}";
    }

    $whereClause = implode(' AND ', $whereClauses);

    $sql = "
SELECT a.activity_type_id, a.subject, a.status_id, 
a.activity_date_time, c.display_name, ac.contact_id, custom.{$customFieldColumnName},
custom.{$MemEndDateCustomColumnName}, custom.{$commTypeColumns}, m.membership_type_id
FROM civicrm_activity a
LEFT JOIN {$customTableName} custom ON custom.entity_id = a.id
LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
LEFT JOIN civicrm_contact c ON c.id = ac.contact_id
LEFT JOIN civicrm_membership m ON m.id = custom.{$customFieldColumnName}
{$whereClause}
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $activityDetails = $dao->toArray();
    return $activityDetails;
  }

  /**
   * Create renewal batch
   *
   * @return string
   *   batch title
   */
  public static function createRenewalBatch($params) {

    if (empty($params['title'])) {
      return;
    }

    $session =& CRM_Core_Session::singleton( );
    $userContactId = $session->get( 'userID' ); // which is contact id of the user

    $params['name'] = CRM_Utils_String::titleToVar($params['title']);
    $params['created_id'] = $userContactId;

    $batch = new CRM_Membershiprenewal_DAO_Batch();
    $batch->copyValues($params);
    $batch->save();

    // FIXME: Created date is not saved using DAO
    // So updating the created date using SQL
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;
    $batchSql = "UPDATE {$tableName} SET created_date = %1, first_reminder_date = %1 WHERE id = %2";
    $batchParams = array(
      '1' => array($params['created_date'], 'String' ),
      '2' => array($batch->id, 'Integer' ),
    );
    CRM_Core_DAO::executeQuery($batchSql, $batchParams);

    return $batch;
  }

  /**
   * Function to prepare for creating 2nd or 3rd renewal reminders
   * 
   * @params int $reminder_type (2 or 3)
   *
   * @return boolean $status
   */
  public static function createRenewalReminders($reminder_type) {
    if (empty($reminder_type)) {
      return;
    }

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    $batchTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME;
    $batchEntitiesTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ENTITY_BATCH_TABLE_NAME;
    $activitiesCustomTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;

    if ($reminder_type == 2) {
      $columnName = 'second_reminder_date';
      $settingName = 'renewal_second_reminder';
    }
    if ($reminder_type == 3) {
      $columnName = 'third_reminder_date';
      $settingName = 'renewal_third_reminder';
    }

    $noOfdays = $settingsArray[$settingName];

    if (empty($noOfdays)) {
      return;
    }

    $currentDate = date("Y-m-d H:i:s");

    $batchIds = array();

    // Get all activities for reminder
    // Get only memberships which are not renewed
    // Check if the membership end date is same as the end date saved in activity
    // If yes, then the membership is not renewed
    /*$selectSql = "SELECT * from {$tableName} WHERE {$columnName} IS NULL AND DATE_ADD(created_date,INTERVAL {$noOfdays} DAY) <= %1";*/
    $previousReminder = $reminder_type - 1;
    $selectSql = 
"SELECT b.renewal_month_year, b.id as batch_id, eb.activity_id, m.contact_id, email.email, address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail, contact.is_opt_out, m.id as membership_id, m.membership_type_id, m.end_date as end_date FROM {$batchEntitiesTableName} eb
LEFT JOIN {$batchTableName} b ON eb.batch_id = b.id
INNER JOIN {$activitiesCustomTableName} ac ON eb.activity_id = ac.entity_id
INNER JOIN civicrm_membership m ON ac.membership_id = m.id
LEFT JOIN civicrm_contact contact ON m.contact_id = contact.id
LEFT JOIN civicrm_email email ON m.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_address address ON m.contact_id = address.contact_id AND address.is_primary = 1
WHERE ac.membership_end_date = m.end_date
AND b.{$columnName} IS NULL
AND DATE_SUB(m.end_date, INTERVAL {$noOfdays} DAY) <= %1
AND eb.reminder_type = %2
GROUP BY eb.activity_id
";
    $selectParams = array(
      '1' => array($currentDate, 'String'),
      '2' => array($previousReminder, 'Integer'),
    );
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    while($selectDao->fetch()) {
      // get month/year to create activities
      // $monthYearArray = explode('-', $selectDao->renewal_month_year);

      // Create activities for 2nd renewal reminder
      //$activityIds = CRM_Membershiprenewal_BAO_Batch::createActivitiesForRenewals($monthYearArray[0], $monthYearArray[1]);

      $activityId = CRM_Membershiprenewal_BAO_Batch::createActivitiesForRenewalReminders($selectDao, $currentDate, $reminder_type, $settingsArray);

      // Skip if $activityId is empty
      // as this might be 'No action' for the membership type
      if (empty($activityId)) {
        continue;
      }

      $batchId = $selectDao->batch_id;
      $batchIds[] = $batchId; // To update batch reminder date

      // Insert into civicrm_membership_renewal_entity_batch table
      // This is 2nd or 3rd reminder
      if (!empty($activityId) && !empty($batchId)) {

        $entitySql = "
INSERT INTO {$batchEntitiesTableName} (batch_id, activity_id, reminder_type) 
VALUES ({$batchId}, {$activityId}, {$reminder_type})
";
        CRM_Core_DAO::executeQuery($entitySql);
      }
    }  

    // Update batch and set 2nd or 3rd renewal date
    if (!empty($batchIds)) {
      $batchIdsStr = implode(',', $batchIds);
      $batchUpdateSql = "UPDATE {$batchTableName} SET {$columnName} = %1 WHERE id IN ({$batchIdsStr})";
      $batchUpdateParams = array(
        '1' => array($currentDate, 'String' ),
      );
      CRM_Core_DAO::executeQuery($batchUpdateSql, $batchUpdateParams);
    }
  }

  /**
   * Function get create activites for reminders
   * 
   * @params object $activity (first reminder activity details)
   *
   * @return boolean $status
   */
  public static function createActivitiesForRenewalReminders($activityObj, $currentDate, $reminder_type, $settingsArray) {

    // Get activity type name by checking email, address and communication preference      
    $activityType = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($activityObj);

    // Get message templates for reminder and membership type
    $messageTemplateId = self::getMessageTemplateForRenewalReminder($reminder_type, $activityObj->membership_type_id, $activityType);

    // Skip, if no action is needed for the membership type
    // based on settings
    if (empty($messageTemplateId) && $activityType != CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME) {
      return;
    }

    // Get activity type id for activity type name
    $activityTypeID = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($activityType);

    // Get logged in user's Contact ID
    $userContactId = CRM_Membershiprenewal_Utils::getLoggedInUserContactID();

    // Prepare activity params
    $activityParams = array(
      'activity_type_id' => $activityTypeID,
      'subject' => 'Membership Renewal',
      'activity_date_time' => $currentDate,
      'details' => '',
      'status_id' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled status
      'source_contact_id' => $userContactId,
      'target_contact_id' => $activityObj->contact_id,
      'is_test' => 0,
    );

    // Create activity using API
    $activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityParams);

    $activityIds[] = $activityResult['id'];

    if ($activityResult['id']) {
      // Update activity custom data and save membership id
      // as passing 'custom_' is not working in version 4.6
      self::insertCustomDataForActivity($activityResult['id'], $activityObj->membership_id, $activityObj->end_date);

      // Prepare html using message template, to save in activity details section
      // This is for sending scheduled reminders
      self::updateActivityWithMessageTemplate($activityResult['id'], $activityType, $reminder_type, $activityObj->membership_type_id);
    }

    return $activityResult['id'];

  }

  /**
   * Function to insert activities in renewal log table
   * 
   * @params int $batchId 
   */
  public static function recordRenewalLog($batchId) {
    if (empty($batchId)) {
      return;
    }

    $logTable = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EXCLUDED_MEMBER_DETAILS;
    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $logSql = "
INSERT INTO {$logTable} (batch_id, membership_id, membership_type_id, contact_id, join_date, 
start_date, end_date, renewal_date, communication_type, reason, status)
SELECT {$batchId}, membership_id, membership_type_id, contact_id, join_date, 
start_date, end_date, renewal_date, communication_type, reason, status FROM {$tempTableName}
";
    CRM_Core_DAO::executeQuery($logSql);
  }

  /**
   * Function to insert activities in entity batch table
   * 
   * @params int $batchId 
   */
  public static function insertActivitiesForBatch($batchId, $reminder = 1) {
    if (empty($batchId)) {
      return;
    }

    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $entityTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ENTITY_BATCH_TABLE_NAME;
    $entitySql = "INSERT INTO {$entityTableName} (batch_id, activity_id, reminder_type) 
              SELECT {$batchId}, activity_id ,1 FROM {$tempTableName} WHERE status = 1";
    CRM_Core_DAO::executeQuery($entitySql);
  }

  /**
   * Function get create activites for new joiners & renewals for selected month/year
   * 
   * @params int $month 
   * @params int $year
   *
   * @return boolean $status
   */
  public static function createActivities($month, $year) {

    if (empty($month) || empty($year)) {
      return;
    }

    $result = array(
      'activities' => array(),
      'excluded' => array(),
    );

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Check if new joiners need to be included in the list
    if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
      // Get new joiners list
      self::createActivitiesForRenewalsNewJoiners($month, $year, $result);
    }

    self::createActivitiesForRenewals($month, $year, $result);

    return $result;
  }

  /**
   * Function get create activites for new joiners for selected month/year
   * 
   * @params int $month 
   * @params int $year
   *
   * @return boolean $status
   */
  public static function createActivitiesForRenewalsNewJoiners($month, $year, &$result) {

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Renewal dates rable
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate = date("Y-m-t", strtotime($firstDate));
    $currentDate = date("Y-m-d");
    $noOfdays = $settingsArray['renewal_first_reminder'];

    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $commType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_NEW_JOINER;

    // Get all the membership up for renewals
    $selectSql = "
SELECT renewal.id, renewal.membership_id, member.contact_id, member.membership_type_id, email.email, address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail, contact.is_opt_out,
contact.display_name, contact.id as contact_id, member.start_date, member.end_date, member.join_date,
renewal.communication_type
FROM {$tempTableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
LEFT JOIN civicrm_contact contact ON member.contact_id = contact.id
LEFT JOIN civicrm_email email ON member.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_address address ON member.contact_id = address.contact_id AND address.is_primary = 1
WHERE renewal.status = 1 AND renewal.communication_type = %1
";
//AND DATE_SUB(renewal.end_date, INTERVAL {$noOfdays} DAY) <= %3
    $selectParams = array(
      '1' => array($commType, 'String'),
    );
    /*$selectParams = array(
      '1' => array($firstDate, 'String'),
      '2' => array($lastDate, 'String'),
    );*/
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);

    // Get logged in user's Contact ID
    $userContactId = CRM_Membershiprenewal_Utils::getLoggedInUserContactID();

    while($selectDao->fetch()) {

      // Get activity type name by checking email, address and communication preference      
      $activityType = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($selectDao);

      // Get message templates for reminder and membership type
      $messageTemplateId = self::getMessageTemplateForRenewalReminder(1, $selectDao->membership_type_id, $activityType, $isJoiner = TRUE, $isAutoRenew = FALSE);

      // Skip, if no action is needed for the membership type
      // based on settings
      /*if (empty($messageTemplateId) && $activityType != CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME) {
        $result['excluded'][] = $selectDao->membership_id;
        continue;
      }*/

      // Get activity type id for activity type name
      $activityTypeID = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($activityType);

      // Get activity date
      $activityDate = CRM_Membershiprenewal_BAO_Membershiprenewal::getScheduledDateForFirstReminder($selectDao->end_date, $settingsArray);

      // Prepare activity params
      $activityParams = array(
        'activity_type_id' => $activityTypeID,
        'subject' => 'Membership - New Joiners',
        'activity_date_time' => $activityDate,
        'details' => '',
        'status_id' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled status
        'source_contact_id' => $userContactId,
        'target_contact_id' => $selectDao->contact_id,
        'is_test' => 0,
      );
      
      // Create activity using API
      $activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityParams);

      $result['activities'][] = $activityResult['id'];

      if ($activityResult['id']) {
        // Update activity custom data and save membership id
        // as passing 'custom_' is not working in version 4.6
        self::insertCustomDataForActivity($activityResult['id'], $selectDao->membership_id, $selectDao->end_date, $selectDao->communication_type);

        // Prepare html using message template, to save in activity details section
        // This is for sending scheduled reminders or print letters
        // 1 - this is first renewal reminder
        self::updateActivityWithMessageTemplate($activityResult['id'], $activityType, 1, $selectDao->membership_type_id, $isJoiner = TRUE, $isAutoRenew = FALSE);

        // Update activity_id in temp table
        $updateTempSql = "UPDATE {$tempTableName} SET activity_id = %1 WHERE id = %2";
        $updateTempParams = array(
          '1' => array($activityResult['id'], 'Integer'),
          '2' => array($selectDao->id, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($updateTempSql, $updateTempParams);
      }
    }

    //return array($activityIds, $excludedMembershipIds);
  }

  /**
   * Function get create activites for renewals for selected month/year
   * 
   * @params int $month 
   * @params int $year
   *
   * @return boolean $status
   */
  public static function createActivitiesForRenewals($month, $year, &$result) {

    if (empty($month) || empty($year)) {
      return;
    }

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    $activityIds = $excludedMembershipIds = array();

    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate = date("Y-m-t", strtotime($firstDate));
    $currentDate = date("Y-m-d");
    $noOfdays = $settingsArray['renewal_first_reminder'];

    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $commType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_RENEWAL;

    // Get all the membership up for renewals
    /*$selectSql = "
SELECT renewal.membership_id, renewal.renewal_date, renewal.end_date, renewal.contact_id, member.membership_type_id, email.email, address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail, contact.is_opt_out
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
LEFT JOIN civicrm_contact contact ON renewal.contact_id = contact.id
LEFT JOIN civicrm_email email ON renewal.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_address address ON renewal.contact_id = address.contact_id AND address.is_primary = 1
WHERE renewal.renewal_date >= %1 AND renewal.renewal_date <= %2
";*/
    $selectSql = "
SELECT renewal.id, renewal.membership_id, member.contact_id, member.membership_type_id, email.email, address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail, contact.is_opt_out,
contact.display_name, contact.id as contact_id, member.start_date, member.end_date, member.join_date,
renewal.communication_type, member.contribution_recur_id, recur.payment_instrument_id
FROM {$tempTableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
LEFT JOIN civicrm_contribution_recur recur ON member.contribution_recur_id = recur.id
LEFT JOIN civicrm_contact contact ON member.contact_id = contact.id
LEFT JOIN civicrm_email email ON member.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_address address ON member.contact_id = address.contact_id AND address.is_primary = 1
WHERE renewal.status = 1 AND renewal.communication_type = %1
";
    $selectParams = array(
      '1' => array($commType, 'String'),
    );

    /*$selectParams = array(
      '1' => array($firstDate, 'String'),
      '2' => array($lastDate, 'String'),
    );*/
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);

    // Get logged in user's Contact ID
    $userContactId = CRM_Membershiprenewal_Utils::getLoggedInUserContactID();

    while($selectDao->fetch()) {

      // Get activity type name by checking email, address and communication preference
      $activityType = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($selectDao);

      // Check if the membership is set for auto-renew
      // using the payment method in the related recurring record
      $isAutoRenew = FALSE;
      if (!empty($selectDao->payment_instrument_id) && 
        in_array($selectDao->payment_instrument_id, $settingsArray['autorenew_payment_instrument_id'])) {
        $isAutoRenew = TRUE;
      }

      // Get message templates for reminder and membership type
      $messageTemplateId = self::getMessageTemplateForRenewalReminder(1, $selectDao->membership_type_id, $activityType, $isJoiner = FALSE, $isAutoRenew);

      //CRM_Core_Error::debug_var('Debug-String', "CONTACT: {$selectDao->display_name}, ACT TYPE: {$activityType}, MSG TEMPLATE: {$messageTemplateId}, AUTORENEW: {$isAutoRenew}");

      // Skip, if no action is needed for the membership type
      // based on settings
      /*if (empty($messageTemplateId) && $activityType != CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME) {
        $result['excluded'][] = $selectDao->membership_id;
        continue;
      }*/

      // Get activity type id for activity type name
      $activityTypeID = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($activityType);

      // Get activity date
      $activityDate = CRM_Membershiprenewal_BAO_Membershiprenewal::getScheduledDateForFirstReminder($selectDao->end_date, $settingsArray);

      // Prepare activity params
      $activityParams = array(
        'activity_type_id' => $activityTypeID,
        'subject' => 'Membership Renewal',
        'activity_date_time' => $activityDate,
        'details' => '',
        'status_id' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled status
        'source_contact_id' => $userContactId,
        'target_contact_id' => $selectDao->contact_id,
        'is_test' => 0,
      );

      // Create activity using API
      $activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityParams);

      $result['activities'][] = $activityResult['id'];

      if ($activityResult['id']) {
        // Update activity custom data and save membership id
        // as passing 'custom_' is not working in version 4.6
        self::insertCustomDataForActivity($activityResult['id'], $selectDao->membership_id, $selectDao->end_date, $selectDao->communication_type);

        // Prepare html using message template, to save in activity details section
        // This is for sending scheduled reminders or print letters
        // 1 - this is first renewal reminder
        self::updateActivityWithMessageTemplate($activityResult['id'], $activityType, 1, $selectDao->membership_type_id, $isJoiner = FALSE, $isAutoRenew);

        // Update activity_id in temp table
        $updateTempSql = "UPDATE {$tempTableName} SET activity_id = %1 WHERE id = %2";
        $updateTempParams = array(
          '1' => array($activityResult['id'], 'Integer'),
          '2' => array($selectDao->id, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($updateTempSql, $updateTempParams);
      }
    }

    //return array($activityIds, $excludedMembershipIds);
  }

  /**
   * Function to check if the membership is set for auto-renew
   * using the payment method in the related recurring record
   * 
   * @params int $month 
   * @params int $year
   *
   * @return boolean $status
   */
  public static function checkIfMembershipIsAutoRenew($recurId) {

    if (empty($recurId)) {
      return FALSE;
    }

    $recurParams = array(
      'id' => $activityId,
      'sequential' => 1,
    );
    $recurDetails = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('ContributionRecur', 'get', $recurParams);

  }

  public static function prepareTempTable($month, $year) {
    if (empty($month) || empty($year)) {
      return;
    }

    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate  = date("Y-m-t", strtotime($firstDate));

    $renewalTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE {$tempTableName}");

    $renewalDateEmpty = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_RENEWAL_DATE_EMPTY;
    $notInRange = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_NOT_IN_DATE_RANGE_MESSAGE;
    $noActionmessage = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_NO_ACTION_MESSAGE;

    $commTypeNew = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_NEW_JOINER;
    $commTypeRenewal = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_RENEWAL;

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    $allMemTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();
    $allMemTypes = array_keys($allMemTypes);

    // Get all membership types which has renewal settings
    $allMemTypesForRenewal = CRM_Membershiprenewal_Utils::getAllMembershipTypesSetForRenewal();

    // Get all membership types which are not set for renewal
    $memTypesNotForRenewal = array_diff($allMemTypes, $allMemTypesForRenewal);

    $extraExclude = $extraInclude = '';
    if (!empty($memTypesNotForRenewal)) {
      $memTypesIdsNotForRenewal = @implode(',', $memTypesNotForRenewal);
      $extraExclude = " AND membership_type_id NOT IN ({$memTypesIdsNotForRenewal})";
      $extraInclude = " AND membership_type_id IN ({$memTypesIdsNotForRenewal})";
    }

    // Check if new joiners need to be included in the list
    if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {

      // New joiners - To be included in the batch
      $sql = "
INSERT INTO {$tempTableName} (membership_id, membership_type_id, contact_id, join_date,
start_date, end_date, reason, communication_type, status)
SELECT id, membership_type_id, contact_id, join_date, start_date, end_date, NULL, '{$commTypeNew}', 1
FROM civicrm_membership WHERE join_date > %1 AND id NOT IN 
(SELECT membership_id FROM civicrm_value_membership_renewal_information){$extraExclude}";
      $params = array(
        '1' => array($settingsArray['cut_off_date'], 'String' ),
      );
      //echo $settingsArray['cut_off_date'];
      //echo $sql;exit;
      CRM_Core_DAO::executeQuery($sql, $params);

      // New joiners - No Action is set for the membership type in renewal settings
      $sql = "
INSERT INTO {$tempTableName} (membership_id, membership_type_id, contact_id, join_date,
start_date, end_date, reason, communication_type, status)
SELECT id, membership_type_id, contact_id, join_date, start_date, end_date, '{$noActionmessage}', '{$commTypeNew}', 0
FROM civicrm_membership WHERE join_date > %1 {$extraInclude}
";
      CRM_Core_DAO::executeQuery($sql, $params);
    }

    $sql = "
INSERT INTO {$tempTableName} (membership_id, membership_type_id, contact_id, join_date,
start_date, end_date, renewal_date, communication_type, status)
SELECT membership_id, membership_type_id, contact_id, join_date, start_date, 
end_date, renewal_date, '{$commTypeRenewal}', 1
FROM {$renewalTableName} WHERE 
(join_date < %1 OR join_date > %2) AND
renewal_date IS NOT NULL AND renewal_date != ''
    ";
    $params = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );
    CRM_Core_DAO::executeQuery($sql, $params);

    // Renewals - Renewal date empty
    /*$sql = "UPDATE {$tempTableName} SET reason = '{$renewalDateEmpty}' WHERE renewal_date IS NULL";
    $params = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );
    CRM_Core_DAO::executeQuery($sql, $params);*/

    // Renewals - Not in date table
    $sql = "UPDATE {$tempTableName} SET reason = '{$notInRange}', status = 0 WHERE renewal_date < %1 OR renewal_date > %2";
    $params = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );
    CRM_Core_DAO::executeQuery($sql, $params);

    // Renewals - No Action is set for the membership type in renewal settings
    if (!empty($memTypesNotForRenewal)) {
      $sql = "UPDATE {$tempTableName} SET reason = '{$noActionmessage}', status = 0 WHERE reason IS NULL {$extraInclude}";
      CRM_Core_DAO::executeQuery($sql);
    }

    return TRUE;

  }

  /**
   * Function to get membership communications summary, to display before the activities are created
   * 
   * @params int $month 
   * @params int $year
   *
   * @return array $result
   */
  public static function getactivitiesSummary($month, $year) {
    if (empty($month) || empty($year)) {
      return;
    }

    $result = array(
      'total_count' => 0,
      'email_count' => 0,
      'letter_count' => 0,
      'unknown_count' => 0,
      'excluded_count' => 0,
      'sms_count' => 0,
      'email_activities' => array(),
      'letter_activities' => array(),
      'unknown_activities' => array(),
      'excluded' => array(),
      'formatted_contacts' => array(),
      'sms_contacts' => array(),
      'sms_details' => array(),
    );

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    
    // Check if new joiners need to be included in the list
    if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
      // Get new joiners list
      self::getactivitiesSummaryForNewJoiners($month, $year, $result);
    }

    self::getactivitiesSummaryForRenewals($month, $year, $result);

    return $result;
  }

  /**
   * Function get summary for new joiners, to display before the activities are created
   * 
   * @params int $month 
   * @params int $year
   *
   * @return array $result
   */
  public static function getactivitiesSummaryForNewJoiners($month, $year, &$result) {
    
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate = date("Y-m-t", strtotime($firstDate));

    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // Get all the membership up for renewals
    $selectSql = "
SELECT member.id as membership_id, member.contact_id, member.membership_type_id, email.email, 
address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail, 
contact.is_opt_out, contact.sort_name, contact.is_deceased,contact.display_name, 
contact.id as contact_id, member.start_date, member.end_date, member.join_date, 
'New Joiner' as communication_type,
phone.phone, contact.do_not_sms, phone.id as phone_id, phone.phone_type_id
FROM civicrm_membership member
LEFT JOIN civicrm_contact contact ON member.contact_id = contact.id
LEFT JOIN civicrm_email email ON member.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_phone phone ON member.contact_id = phone.contact_id AND phone.phone_type_id = 2
LEFT JOIN civicrm_address address ON member.contact_id = address.contact_id AND address.is_primary = 1
WHERE member.join_date >= %1 AND member.join_date <= %2 AND member.join_date > %3
";
//echo $sql;exit;
    $selectParams = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
      '3' => array($settingsArray['cut_off_date'], 'String' ),
    );
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);

    // All membership types
    $memTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();

    $emailActivitiesCount = $letterAcivitiesCount = $unknownActivitiesCount = $excludedCount = 0;
    $smsCount = 0;
    while($selectDao->fetch()) {

      $dataArray = $selectDao->toArray();
      $dataArray['membership_type'] = $memTypes[$dataArray['membership_type_id']];

      // Get activity type name by checking email, address and communication preference      
      $activityType = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($selectDao);
      
      // Get message templates for reminder and membership type
      $messageTemplateId = self::getMessageTemplateForRenewalReminder(1, $selectDao->membership_type_id, $activityType, $isJoiner = TRUE, $isAutoRenew = FALSE);

      // Skip, if no action is needed for the membership type
      // based on settings
      if (empty($messageTemplateId) && $activityType != CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME) {
        $excludedCount++;
        $result['excluded'][] = $selectDao->membership_id;
        continue;
      }

      ## Decide activity types based in the below conditions
      // Check if valid email is available
      // 1. Email is not empty
      // 2. Email is not on hold (checking this in query)
      // 3. Contact does not have DO NOT EMAIL flag ticked
      // 4. Contact did not OPT OUT of all mailings
      if (!empty($selectDao->email) && $selectDao->do_not_email == 0) {
        $emailActivitiesCount ++;
        $result['email_activities'][] = $dataArray;
      }
      // Check if we have a valid address
      // 1. Street address and Postcode are not empty
      // 2. Contact does not have DO NOT MAIL flag ticked
      else if (!empty($selectDao->street_address) && 
        !empty($selectDao->postal_code) 
        && $selectDao->do_not_mail == 0) {
        $letterAcivitiesCount++;
        $result['letter_activities'][] = $dataArray;
      } 
      // If none of the above 
      // Create the activity type as Unknown
      else {
        $unknownActivitiesCount++;
        $result['unknown_activities'][] = $dataArray;
      }

      // Get Phone details
      if (!empty($selectDao->phone)) {
        $smsCount++;

        // Add phone details to result array to send SMS
        $result['formatted_contacts'][] = array(
          'contact_id' => $selectDao->contact_id,
          'sort_name' => $selectDao->sort_name,
          'display_name' => $selectDao->display_name,
          'do_not_sms' => $selectDao->do_not_sms,
          'is_deceased' => $selectDao->is_deceased,
          'phone_id' => $selectDao->phone_id,
          'phone_type_id' => $selectDao->phone_type_id,
          'phone' => $selectDao->phone,
        );

        $result['sms_contacts'][] = $selectDao->contact_id;
        $result['sms_details'][] = $selectDao->contact_id.'::'.$selectDao->phone;
      }
    }

    $totalCount = $emailActivitiesCount + $letterAcivitiesCount + $unknownActivitiesCount;

    $result['total_count'] += $totalCount;
    $result['email_count'] += $emailActivitiesCount;
    $result['letter_count'] += $letterAcivitiesCount;
    $result['unknown_count'] += $unknownActivitiesCount;
    $result['excluded_count'] += $excludedCount;
    $result['sms_count'] += $smsCount;
  }

  /**
   * Function get renewal summary, to display before the activities are created
   * 
   * @params int $month 
   * @params int $year
   *
   * @return array $result
   */
  public static function getactivitiesSummaryForRenewals($month, $year, &$result) {

    // Renewal dates table
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate = date("Y-m-t", strtotime($firstDate));

    // Get all the membership up for renewals
    $selectSql = "
SELECT renewal.membership_id, renewal.renewal_date, renewal.contact_id, member.membership_type_id, 
email.email, address.street_address, address.postal_code, contact.do_not_email, contact.do_not_mail,
contact.is_opt_out, contact.sort_name, contact.is_deceased, contact.display_name, 
contact.id as contact_id, member.start_date, member.end_date, member.join_date,
'Renewal' as communication_type, member.contribution_recur_id, recur.payment_instrument_id,
phone.phone, contact.do_not_sms, phone.id as phone_id, phone.phone_type_id
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
LEFT JOIN civicrm_contribution_recur recur ON member.contribution_recur_id = recur.id
LEFT JOIN civicrm_contact contact ON renewal.contact_id = contact.id
LEFT JOIN civicrm_email email ON renewal.contact_id = email.contact_id AND email.on_hold = 0 AND email.is_primary = 1
LEFT JOIN civicrm_phone phone ON member.contact_id = phone.contact_id AND phone.phone_type_id = 2
LEFT JOIN civicrm_address address ON renewal.contact_id = address.contact_id AND address.is_primary = 1
WHERE renewal.renewal_date >= %1 AND renewal.renewal_date <= %2
";
    $selectParams = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );
    $selectDao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    // All membership types
    $memTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();

    $emailActivitiesCount = $letterAcivitiesCount = $unknownActivitiesCount = $excludedCount = 0;
    $smsCount = 0;
    //$excludedMembershipIds = $emailActivities = $letterAcivities = $unknownActivities = array();
    while($selectDao->fetch()) {

      $dataArray = $selectDao->toArray();
      $dataArray['membership_type'] = $memTypes[$dataArray['membership_type_id']];

      // Get activity type name by checking email, address and communication preference      
      $activityType = CRM_Membershiprenewal_BAO_Batch::getActivityTypeID($selectDao);

      // Check if the membership is set for auto-renew
      // using the payment method in the related recurring record
      $isAutoRenew = FALSE;
      if (!empty($selectDao->payment_instrument_id) && 
        in_array($selectDao->payment_instrument_id, $settingsArray['autorenew_payment_instrument_id'])) {
        $isAutoRenew = TRUE;
        $dataArray['communication_type'] = 'Auto-Renewal';
      }

      // Get message templates for reminder and membership type
      $messageTemplateId = self::getMessageTemplateForRenewalReminder(1, $selectDao->membership_type_id, $activityType, $isJoiner = FALSE, $isAutoRenew);

      // Skip, if no action is needed for the membership type
      // based on settings
      if (empty($messageTemplateId) && $activityType != CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME) {
        $excludedCount++;
        $excludedMembershipIds[] = $selectDao->membership_id;
        continue;
      }

      ## Decide activity types based in the below conditions
      // Check if valid email is available
      // 1. Email is not empty
      // 2. Email is not on hold (checking this in query)
      // 3. Contact does not have DO NOT EMAIL flag ticked
      // 4. Contact did not OPT OUT of all mailings
      if (!empty($selectDao->email) && $selectDao->do_not_email == 0) {
        $emailActivitiesCount ++;
        $result['email_activities'][] = $dataArray;
      }
      // Check if we have a valid address
      // 1. Street address and Postcode are not empty
      // 2. Contact does not have DO NOT MAIL flag ticked
      else if (!empty($selectDao->street_address) && 
        !empty($selectDao->postal_code) 
        && $selectDao->do_not_mail == 0) {
        $letterAcivitiesCount++;
        $result['letter_activities'][] = $dataArray;
      } 
      // If none of the above 
      // Create the activity type as Unknown
      else {
        $unknownActivitiesCount++;
        $result['unknown_activities'][] = $dataArray;
      }

      // Get Phone details
      if (!empty($selectDao->phone)) {
        $smsCount++;

        // Add phone details to result array to send SMS
        $result['formatted_contacts'][] = array(
          'contact_id' => $selectDao->contact_id,
          'sort_name' => $selectDao->sort_name,
          'display_name' => $selectDao->display_name,
          'do_not_sms' => $selectDao->do_not_sms,
          'is_deceased' => $selectDao->is_deceased,
          'phone_id' => $selectDao->phone_id,
          'phone_type_id' => $selectDao->phone_type_id,
          'phone' => $selectDao->phone,
        );

        $result['sms_contacts'][] = $selectDao->contact_id;
        $result['sms_details'][] = $selectDao->contact_id.'::'.$selectDao->phone;
      }
    }

    $totalCount = $emailActivitiesCount + $letterAcivitiesCount + $unknownActivitiesCount;

    $result['total_count'] += $totalCount;
    $result['email_count'] += $emailActivitiesCount;
    $result['letter_count'] += $letterAcivitiesCount;
    $result['unknown_count'] += $unknownActivitiesCount;
    $result['excluded_count'] += $excludedCount;
    $result['sms_count'] += $smsCount;
  }

  /**
   * Function get activity type based on contact's email, address and communication preference
   * 
   * @params object $object
   *
   * @return int $activityTypeId
   */
  public static function getActivityTypeID($activityObj) {
    ## Decide activity types based in the below conditions
    // Check if valid email is available
    // 1. Email is not empty
    // 2. Email is not on hold (checking this in query)
    // 3. Contact does not have DO NOT EMAIL flag ticked
    // 4. Contact did not OPT OUT of all mailings
    if (!empty($activityObj->email) && $activityObj->do_not_email == 0) {
      $activityType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME;
    } 
    // Check if we have a valid address
    // 1. Street address and Postcode are not empty
    // 2. Contact does not have DO NOT MAIL flag ticked
    else if (!empty($activityObj->street_address) && 
      !empty($activityObj->postal_code) 
      && $activityObj->do_not_mail == 0) {
      $activityType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME;
    } 
    // If none of the above 
    // Create the activity type as Unknown
    else {
      $activityType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME;
    }

    return $activityType;
  }

  /**
   * Function get activity type Id for activity name
   * 
   * @params string $activityTypeName
   *
   * @return int $activityTypeId
   */
  public static function getActivityTypeIDForName($activityTypeName) {

    // get activity type ID using name
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      $activityTypeName,
      'name'
    );

    return $activityTypeID;
  }

  /**
   * Function get activity type based on contact's email, address and communication preference
   * 
   * @params object $object
   *
   * @return int $activityTypeId
   */
  public static function insertCustomDataForActivity($activityId, $membershipId, $membershipEndDate, $commType) {

    if (empty($activityId) || empty($membershipId) || empty($membershipEndDate)) {
      return;
    }

    if (empty($commType)) {
      $commType = 'Renewal';
    }

    $columnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_CUSTOM_FIELD_COLUMN_NAME;
    $memDateColumnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_END_DATE_CUSTOM_FIELD_COLUMN_NAME;
    $commTypeColumns = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_COMM_TYPE_COLUMN_NAME;
    $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;

    $updateSql = "INSERT INTO {$tableName} SET {$columnName} = %1, {$memDateColumnName} = %2, entity_id  = %3, {$commTypeColumns} = %4";
    $emailedStatus = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAILED_STATUS;
    $updateParams = array(
      '1' => array( $membershipId, 'Integer'),
      '2' => array( $membershipEndDate, 'String'),
      '3' => array( $activityId, 'Integer'),
      '4' => array( $commType, 'String'),
    );
    CRM_Core_DAO::executeQuery($updateSql, $updateParams);
  }

  /**
   * Function to update activity details and subject
   * with message template after replacing tokens
   * 
   * @params object $object
   *
   * @return int $activityTypeId
   */
  public static function updateActivityWithMessageTemplate($activityId, $activityType, $renewalReminder = 1, $memTypeId = NULL, $isJoiner = FALSE, $isAutoRenew = FALSE) {

    if (empty($activityId) || empty($activityType)) {
      return;
    }

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
    $msgTemplateId = '';

    $joinerStr = '';
    if ($isJoiner == TRUE) {
      $joinerStr = 'joiner_';
    }

    if ($isAutoRenew == TRUE) {
      $joinerStr = 'autorenew_';
    }

    $emailTemplateKey = $joinerStr.'email_message_template_'.$renewalReminder;
    $letterTemplateKey = $joinerStr.'letter_message_template_'.$renewalReminder;

    // Email activity
    if ($activityType == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME) {

      // Check if there is membership type specific email message template
      if (!empty($memTypeId) && !empty($settingsArray[$emailTemplateKey.'_'.$memTypeId])) {
        $msgTemplateId = $settingsArray[$emailTemplateKey.'_'.$memTypeId];
        // Check if same as default
        if ($msgTemplateId == 'sameasdefault') {
          $msgTemplateId = $settingsArray[$emailTemplateKey];  
        }
      } 
      /*else { // else use default one
        $msgTemplateId = $settingsArray[$emailTemplateKey];
      }*/
    }

    // Letter activity
    if ($activityType == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME) {

      // Check if there is membership type specific letter message template
      if (!empty($memTypeId) && !empty($settingsArray[$letterTemplateKey.'_'.$memTypeId])) {
        $msgTemplateId = $settingsArray[$letterTemplateKey.'_'.$memTypeId];
        // Check if same as default
        if ($msgTemplateId == 'sameasdefault') {
          $msgTemplateId = $settingsArray[$letterTemplateKey];  
        }
      } 
      /*else { // else use default one
        $msgTemplateId = $settingsArray[$letterTemplateKey];
      }*/
    }

    // Get html after all tokens are replaced
    if (!empty($msgTemplateId)) {
      $msgTemplate = CRM_Membershiprenewal_BAO_Membershiprenewal::composeHTMLForActivity($activityId, $msgTemplateId);

      // Save the html content as activity details
      $actUpdateParams['id'] = $activityId;
      $actUpdateParams['details'] = $msgTemplate['msg_html'];
      $actUpdateParams['subject'] = $msgTemplate['msg_subject'];

      // Update activity
      CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $actUpdateParams);
    }
  }

  /**
   * Function to get message template id for reminder and membership types 
   * 
   * @params int renewalReminder
   * @params int memTypeId 
   *
   * @return int $msgTemplateId
   */
  public static function getMessageTemplateForRenewalReminder($renewalReminder = 1, $memTypeId, $activityType, $isJoiner = FALSE, $isAutoRenew = FALSE) {

    if (empty($memTypeId) || empty($activityType)) {
      return;
    }

    // Get membership renewal settings
    $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

    $msgTemplateId = '';

    $joinerStr = '';
    if ($isJoiner == TRUE) {
      $joinerStr = 'joiner_';
    }

    if ($isAutoRenew == TRUE) {
      $joinerStr = 'autorenew_';
    }

    $emailTemplateKey = $joinerStr.'email_message_template_'.$renewalReminder;
    $letterTemplateKey = $joinerStr.'letter_message_template_'.$renewalReminder;

    // Email activity
    if ($activityType == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME) {

      // Check if there is membership type specific email message template
      if (!empty($memTypeId) && !empty($settingsArray[$emailTemplateKey.'_'.$memTypeId])) {
        $msgTemplateId = $settingsArray[$emailTemplateKey.'_'.$memTypeId];
        // Check if same as default
        if ($msgTemplateId == 'sameasdefault') {
          $msgTemplateId = $settingsArray[$emailTemplateKey];  
        }
      } 
      /*else { // else use default one
        $msgTemplateId = $settingsArray[$emailTemplateKey];
      }*/
    }

    // Letter activity
    if ($activityType == CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME) {

      // Check if there is membership type specific letter message template
      if (!empty($memTypeId) && !empty($settingsArray[$letterTemplateKey.'_'.$memTypeId])) {
        $msgTemplateId = $settingsArray[$letterTemplateKey.'_'.$memTypeId];
        // Check if same as default
        if ($msgTemplateId == 'sameasdefault') {
          $msgTemplateId = $settingsArray[$letterTemplateKey];  
        }
      } 
      /*else { // else use default one
        $msgTemplateId = $settingsArray[$letterTemplateKey];
      }*/
    }

    return $msgTemplateId;
  }

  /**
   * Function get create activites for renewals for selected month/year
   * 
   * @params array $activities
   * @param int $reminderType   
   *
   * @return boolean $status
   */
  public static function resendRenewalActivities($activities, $reminderType = 1) {
    if (empty($activities)) {
      return;
    }

    // Get logged in user's Contact ID
    $userContactId = CRM_Membershiprenewal_Utils::getLoggedInUserContactID();

    $activityIds = array();

    foreach ($activities as $activityId) {
      // Get activity details
      $activityMiscDetails = CRM_Membershiprenewal_BAO_Batch::getActivitiesDetails($activityId);

      $activityParams = array(
        'id' => $activityId,
        'sequential' => 1,
      );
      // Get activity details
      $activityResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'get', $activityParams);
      $activityDetails = $activityResult['values'][0];

      // Get activity date
      $activityDate = CRM_Membershiprenewal_BAO_Membershiprenewal::getScheduledDateForReSent();

      // Re-parse the content and replace any tokens
      $msgTemplate['msg_subject'] = $activityDetails['subject'];
      $msgTemplate['msg_html'] = $activityDetails['details'];
      // Replace contact tokens
      CRM_Membershiprenewal_BAO_Membershiprenewal::replaceContactTokens($activityMiscDetails, $msgTemplate);

      // Replace membership renewal tokens
      CRM_Membershiprenewal_BAO_Membershiprenewal::replaceRenewalTokens($activityMiscDetails, $msgTemplate);

      // Prepare activity params
      $activityCreateParams = array(
        'activity_type_id' => $activityDetails['activity_type_id'],
        'subject' => $msgTemplate['msg_subject'],
        'activity_date_time' => $activityDate,
        'details' => $msgTemplate['msg_html'],
        'status_id' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID, // Scheduled status
        'source_contact_id' => $userContactId,
        'target_contact_id' => $activityMiscDetails['contact_id'],
        'is_test' => 0,
      );

      // Create activity using API
      $activityCreateResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $activityCreateParams);

      if ($activityCreateResult['id']) {
        // Update activity custom data and save membership id
        // as passing 'custom_' is not working in version 4.6
        self::insertCustomDataForActivity($activityCreateResult['id'], $activityMiscDetails['membership_id'], $activityMiscDetails['membership_end_date'], $activityMiscDetails['membership_communication_type']);
      }
    }
  }

  /**
   * Function to delete all batches and the related activities
   */
  public static function resetAllMembershipCommunications() {
    $emailActType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME;
    $letterActType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME;
    $unknownActType = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME;

    $emailActTypeId = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($emailActType);
    $letterActTypeId = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($letterActType);
    $unknownActTypeId = CRM_Membershiprenewal_BAO_Batch::getActivityTypeIDForName($unknownActType);

    $allActTypeIdStr = "{$emailActTypeId}, {$letterActTypeId}, {$unknownActTypeId}";

    // Delete activity contacts
    $sql = "
DELETE FROM civicrm_activity_contact WHERE activity_id IN 
(SELECT id FROM civicrm_activity WHERE activity_type_id IN ({$allActTypeIdStr}))
";
    CRM_Core_DAO::executeQuery($sql);

    // Delete activities
    $sql = "DELETE FROM civicrm_activity WHERE activity_type_id IN ({$allActTypeIdStr})";
    CRM_Core_DAO::executeQuery($sql);

    // Truncate membership renewal related tables
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_value_membership_renewal_information");
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_details");
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_entity_batch");
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_batch_files");
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_batch");
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_log");
  }
}
