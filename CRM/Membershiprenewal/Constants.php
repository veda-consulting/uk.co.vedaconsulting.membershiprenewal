<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Constants used in membership renewal extension
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

class CRM_Membershiprenewal_Constants {

  // Membership renewal dates table name
  const MEMBERSHIP_RENEWAL_NO_OF_RENEWAL_ACTIVITIES = 3;

  // Membership renewal dates table name
  const MEMBERSHIP_RENEWAL_TABLE_NAME = 'civicrm_membership_renewal_dates';

  // Membership renewal batch table name
  const MEMBERSHIP_RENEWAL_BATCH_TABLE_NAME = 'civicrm_membership_renewal_batch';

  // Membership renewal files table name
  const MEMBERSHIP_RENEWAL_BATCH_FILES_TABLE_NAME = 'civicrm_membership_renewal_batch_files';

  // Membership renewal entity batch table name
  const MEMBERSHIP_RENEWAL_ENTITY_BATCH_TABLE_NAME = 'civicrm_membership_renewal_entity_batch';
  
  //Excluded from Membership renewal process table name
  const MEMBERSHIP_RENEWAL_EXCLUDED_MEMBER_DETAILS = 'civicrm_membership_renewal_log';

  //Excluded from Membership renewal process table name
  const MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE = 'civicrm_membership_renewal_details';

  // Activities custom data table name
  const MEMBERSHIP_RENEWAL_ACTIVITIES_CUSTOM_DATA_TABLE_NAME = 'civicrm_value_membership_renewal_information';

  // Activities custom data - Membership ID custom field column name
  const MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_CUSTOM_FIELD_COLUMN_NAME = 'membership_id';
  const MEMBERSHIP_RENEWAL_ACTIVITIES_MEMBERSHIP_END_DATE_CUSTOM_FIELD_COLUMN_NAME = 'membership_end_date';
  const MEMBERSHIP_RENEWAL_ACTIVITIES_STATUS_CUSTOM_FIELD_COLUMN_NAME = 'renewal_activity_status';
  const MEMBERSHIP_RENEWAL_ACTIVITIES_COMM_TYPE_COLUMN_NAME = 'membership_communication_type';

  // CiviCRM setting group name
  const MEMBERSHIP_RENEWAL_SETTING_GROUP = 'Membership Renewal Settings';

  // CiviCRM setting name
  const MEMBERSHIP_RENEWAL_SETTING_NAME = 'membership_renewal_settings';

  // Activity type names
  const MEMBERSHIP_RENEWAL_EMAIL_ACTIVITY_TYPE_NAME = 'Membership_Communication_Email';
  const MEMBERSHIP_RENEWAL_LETTER_ACTIVITY_TYPE_NAME = 'Membership_Communication_Letter';
  const MEMBERSHIP_RENEWAL_UNKNOWN_ACTIVITY_TYPE_NAME = 'Membership_Communication_Unknown';

  // Membership custom group and field name (against activity)
  const MEMBERSHIP_RENEWAL_MEMBERSHIP_ID_CUSTOM_GROUP_NAME = 'Membership_Renewal_Information';
  const MEMBERSHIP_RENEWAL_MEMBERSHIP_ID_CUSTOM_FIELD_NAME = 'Membership_ID';

  // Ac
  const MEMBERSHIP_RENEWAL_SCHEDULED_ACTIVITY_STATUS_ID = 1;
  const MEMBERSHIP_RENEWAL_COMPLETED_ACTIVITY_STATUS_ID = 2;

  // Schedule reminder details
  const MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_NAME = 'Send_Membership_Renewal_Email';
  const MEMBERSHIP_RENEWAL_SCHEDULE_REMINDER_TITLE = 'Send Membership Renewal Email';

  const MEMBERSHIP_RENEWAL_PRINTED_STATUS = 'Printed';
  const MEMBERSHIP_RENEWAL_EMAILED_STATUS = 'Emailed';

  const MEMBERSHIP_RENEWAL_RENEWAL_DATE_EMPTY = 'Renewal date is empty';
  const MEMBERSHIP_RENEWAL_NOT_IN_DATE_RANGE_MESSAGE = 'Renewal date is not in date range';
  const MEMBERSHIP_RENEWAL_NO_ACTION_MESSAGE = 'No Action is set for the membership type in renewal settings';
  
  const MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_NEW_JOINER = 'New Joiner';
  const MEMBERSHIP_RENEWAL_COMMUNICATION_TYPE_RENEWAL = 'Renewal';


  const MEMBERSHIP_RENEWAL_START_YEAR_MONTH_OFFSET = 12;

  // Months array
  public static $months = array(
    '01' => 'Jan',
    '02' => 'Feb',
    '03' => 'Mar',
    '04' => 'Apr',
    '05' => 'May',
    '06' => 'Jun',
    '07' => 'Jul',
    '08' => 'Aug',
    '09' => 'Sep',
    '10' => 'Oct',
    '11' => 'Nov',
    '12' => 'Dec',
  );

  // Membership renewal tokens
  public static $renewalTokens = array(
    'Membership Type' => '{membershiprenewal.membership_name}',
    'Membership Fee' => '{membershiprenewal.membership_fee}',
    'Join Date' => '{membershiprenewal.join_date}',
    'Start Date' => '{membershiprenewal.start_date}',
    'End Date' => '{membershiprenewal.end_date}',
    'Renewal Date' => '{membershiprenewal.renewal_date}',
  );

  // Reminder type
  public static $reminder_type = array(
    '1' => 'First renewal reminder',
    '2' => 'Second renewal reminder',
    '3' => 'Third renewal reminder',
  );

  // Base array for Membership type specific message templates
  public static $memTypesBaseMsgTemplates = array(
    '' => '- No Action -',
    'sameasdefault' => '- same as default -',
  );

}
// end CRM_Membershiprenewal_Constants
