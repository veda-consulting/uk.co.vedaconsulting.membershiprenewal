<?php

require_once 'membershiprenewal.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function membershiprenewal_civicrm_config(&$config) {
  _membershiprenewal_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function membershiprenewal_civicrm_xmlMenu(&$files) {
  _membershiprenewal_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function membershiprenewal_civicrm_install() {
  _membershiprenewal_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function membershiprenewal_civicrm_uninstall() {
  _membershiprenewal_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function membershiprenewal_civicrm_enable() {
  _membershiprenewal_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function membershiprenewal_civicrm_disable() {
  _membershiprenewal_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function membershiprenewal_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _membershiprenewal_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function membershiprenewal_civicrm_managed(&$entities) {
  _membershiprenewal_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function membershiprenewal_civicrm_caseTypes(&$caseTypes) {
  _membershiprenewal_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function membershiprenewal_civicrm_angularModules(&$angularModules) {
_membershiprenewal_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function membershiprenewal_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _membershiprenewal_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_navigationMenu
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function membershiprenewal_civicrm_navigationMenu(&$params){

  // get the id of Membership Menu
  $membershipMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'Memberships', 'id', 'name');

  // skip adding menu if there is no administer menu
  if ($membershipMenuId) {
    // get the maximum key under adminster menu
    $maxKey = max( array_keys($params[$membershipMenuId]['child']));
    $params[$membershipMenuId]['child'][$maxKey+1] =  array (
       'attributes' => array (
        'label'      => ts('Membership Communications Dashboard'),
        'name'       => 'Membership_Renewal_Dashboard',
        'url'        => CRM_Utils_System::url('civicrm/membershiprenewal', 'reset=1', TRUE),
        'permission' => 'access CiviMember',
        'operator'   => NULL,
        'separator'  => FALSE,
        'parentID'   => $membershipMenuId,
        'navID'      => $maxKey+1,
        'active'     => 1
      )
    );
  }
}

function membershiprenewal_civicrm_alterMailParams(&$params, $context = NULL) {
  if ($params['groupName'] == 'Scheduled Reminder Sender' && $params['entity'] == 'action_schedule') {
    $checkParams = array(
      'name' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_NAME,
    );
    $schuleReminderResult = CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('ActionSchedule', 'get', $checkParams);
    // This is 'Send Membership Renewal Email' scheduled reminder
    // and Check if we have activity id
    if ($schuleReminderResult['id'] == $params['entity_id']
      && isset($params['activity_id']) && !empty($params['activity_id'])) {

      // Get membership renewal settings
      $settings = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();

      // Check if 'Enable Attachment?' is set
      if ($settings['enable_attachment'] == 1) {

        // Get activity attachments
        $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $params['activity_id']);
        if (!empty($attachments)) {
          $params['attachments'] = $attachments;
        }
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_post
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postpermission
 */
function membershiprenewal_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  // To complete activities after schedule reminders are sent
  // Capture when action log update is happening
  // $op - edit
  // $objectName - ActionLog
  // action_date_time is updated in the table
  if ($op == 'edit' && $objectName == 'ActionLog' && !empty($objectRef->action_date_time)) {
    // $objectId is the id of civicrm_action_log table
    // get activity id from the table
    $sql = "SELECT * FROM civicrm_action_log WHERE id = %1";
    $params = array('1' => array($objectId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();

    if (!empty($dao->entity_id) && $dao->entity_table == 'civicrm_activity') {
      // Update activity to completed status
      $actParams = array(
        'id' => $dao->entity_id,
        'status_id' => CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_COMPLETED_ACTIVITY_STATUS_ID
      );
      CRM_Membershiprenewal_Utils::CiviCRMAPIWrapper('Activity', 'create', $actParams);

      // Update custom field (Renewal Activity Status) to 'Emailed'
      $columnName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_STATUS_CUSTOM_FIELD_COLUMN_NAME;
      $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME;

      $updateSql = "UPDATE {$tableName} SET {$columnName} = %1 WHERE entity_id  = %2";
      $emailedStatus = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EMAILED_STATUS;
      $updateParams = array(
        '1' => array( $emailedStatus, 'String'),
        '2' => array( $dao->entity_id, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($updateSql, $updateParams);
    }
  }
}

/**
 * Implements hook_civicrm_summaryActions().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_summaryActions
 */
function membershiprenewal_civicrm_summaryActions( &$actions, $contactID ) {
  $renewalReminderTitle = ts('Send Renewal Reminder');
  $actions['sendRenewalReminder'] = array(
    'title'   => $renewalReminderTitle,
    'weight'  => 1000,
    'class'   => 'no-popup',
    'ref'     => 'sendrenewalreminder',
    'key'     => 'sendrenewalreminder',
    'href'    => CRM_Utils_System::url('civicrm/renewal/single', 'reset=1&id='.$contactID),
  );
}