<?php

use CRM_Membershiprenewal_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

require_once 'tests/phpunit/CRM/Membershiprenewal/UnitTestCase.php';

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group e2e
 */
class CRM_Membershiprenewal_RenewalTest extends CRM_Membershiprenewal_UnitTestCase implements EndToEndInterface {

  protected $_contactIds;
  protected $_activities;

  protected $_msgTemplateId;
  protected $_batchId;

  protected $_renewalMonth = '11';
  protected $_renewalYear = '2017';

  protected $_contactDetails = array(
    array(
      'first_name' => 'Renewal_Test_1',
      'last_name' => 'Renewal_Test_1',
      'contact_type' => 'Individual',
      'api.email.create' => array(
        'email' => 'renewal_test_1@example.com',
        'is_primary' => 1,
        'on_hold' => 0,
      ),
      // Membership up for renewal
      'api.membership.create' => array(
        'membership_type_id' => 'AnnualFixed',
        'join_date' => '2017-01-01',
        'start_date' => '2017-01-01',
        'end_date' => '2017-12-31',
      ),
    ),
    array(
      'first_name' => 'Renewal_Test_2',
      'last_name' => 'Renewal_Test_2',
      'contact_type' => 'Individual',
      'api.address.create' => array(
        'street_address' => 'Renewal_Test_Street_Address_2',
        'city' => 'Renewal_Test_City_2',
        'postal_code' => 'Renewal_Test_Postcode_2',
        'is_primary' => 1,
        'location_type_id' => 1,
      ),
      // Membership up for renewal
      'api.membership.create' => array(
        'membership_type_id' => 'AnnualFixed',
        'join_date' => '2017-01-01',
        'start_date' => '2017-01-01',
        'end_date' => '2017-12-31',
      ),
    ),
    array(
      'first_name' => 'Renewal_Test_3',
      'last_name' => 'Renewal_Test_3',
      'contact_type' => 'Individual',
      'api.email.create' => array(
        'email' => 'renewal_test_3@example.com',
        'is_primary' => 1,
        'on_hold' => 1,
      ),
      // Membership up for renewal
      'api.membership.create' => array(
        'membership_type_id' => 'AnnualFixed',
        'join_date' => '2017-01-01',
        'start_date' => '2017-01-01',
        'end_date' => '2017-12-31',
      ),
    ),
    array(
      'first_name' => 'Renewal_Test_4',
      'last_name' => 'Renewal_Test_4',
      'contact_type' => 'Individual',
      'api.email.create' => array(
        'email' => 'renewal_test_4@example.com',
        'is_primary' => 1,
        'on_hold' => 0,
      ),
      // New joiner
      'api.membership.create' => array(
        'membership_type_id' => 'AnnualFixed',
        'join_date' => '2017-08-01',
        'start_date' => '2017-08-01',
        'end_date' => '2017-07-31',
      ),
    ),
  );

  /**
   * Membership type ID for annual fixed membership.
   *
   * @var int
   */
  protected $membershipTypeAnnualFixedID;

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Generic function to delete records created during renewal test case
   */
  public function resetRenewal() {

    $batchId = CRM_Core_DAO::singleValueQuery("SELECT id FROM `civicrm_membership_renewal_batch` WHERE title = 'Renewal_Batch_Test_NOV2017'");

    // Delete activity custom data
    $sql = "
DELETE FROM civicrm_value_membership_renewal_information WHERE entity_id IN 
(SELECT activity_id FROM civicrm_membership_renewal_entity_batch WHERE batch_id = {$batchId})
";

    // Delete activity contacts
    $sql = "
DELETE FROM civicrm_activity_contact WHERE activity_id IN 
(SELECT activity_id FROM civicrm_membership_renewal_entity_batch WHERE batch_id = {$batchId})
";
    // Delete activities
    $sql = "
DELETE FROM civicrm_activity WHERE id IN 
(SELECT activity_id FROM civicrm_membership_renewal_entity_batch WHERE batch_id = {$batchId})
";

    // Delete records membership renewal related tables
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE civicrm_membership_renewal_details");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_renewal_entity_batch WHERE batch_id = {$batchId}");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_renewal_batch_files WHERE batch_id = {$batchId}");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_renewal_batch WHERE id = {$batchId}");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_renewal_log WHERE batch_id = {$batchId}");
  }

  /**
   * Generic function to create contacts, to be used in test cases
   *
   * @return array
   *   ids of contacts created
   */
  public function createRenewalContact() {
    $contactDetails = $this->_contactDetails;
    $contactIds = array();
    foreach($contactDetails as $contact) {
      $contact['api.membership.create']['memebrship_type_id'] = $this->membershipTypeAnnualFixedID;
      $result = $this->callAPISuccess('contact', 'create', $contact);
      $contactIds[] = $result['id'];
    }

    return $contactIds;
  }

  /**
   * Generic function to create membership renewal settings, to be used in test cases
   */
  public function insertMembershipRenewalSettings() {
    $settings = array();
    $settings['include_joiner'] = 1;
    $settings['cut_off_date'] = '20170630000000';
    $settings['renewal_years'] = 1;
    $settings['renewal_period'] = 1;
    $settings['renewal_start_offset'] = 0;
    $settings['renewal_end_offset'] = 2;
    $settings['renewal_first_reminder'] = 45;
    $settings['renewal_second_reminder'] = 30;
    $settings['renewal_third_reminder'] = 15;

    $memTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();

    for ($i=1; $i <= 3; $i++) {
      $settings['email_message_template_'.$i] = $this->_msgTemplateId;
      $settings['letter_message_template_'.$i] = $this->_msgTemplateId;
      $settings['autorenew_email_message_template_'.$i] = $this->_msgTemplateId;
      $settings['autorenew_letter_message_template_'.$i] = $this->_msgTemplateId;

      $settings['joiner_email_message_template_'.$i] = $this->_msgTemplateId;
      $settings['joiner_letter_message_template_'.$i] = $this->_msgTemplateId;

      foreach ($memTypes as $memId => $memName) {
        $settings['email_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId;
        $settings['letter_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId;
        $settings['autorenew_letter_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId;
        $settings['autorenew_letter_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId;
        $settings['joiner_email_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId;
        $settings['joiner_letter_message_template_'.$i.'_'.$memId] = $this->_msgTemplateId; 
      }
    }

    $settingsStr = serialize($settings);

    // Save the settings
    CRM_Core_BAO_Setting::setItem($settingsStr, 'Membership Renewal Settings', 'membership_renewal_settings');
  }

  /**
   * Generic function to create contacts, membership types, etrc
   */
  public function testPrepareRenewal() {
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', array(
      'domain_id' => 1,
      'name' => "AnnualFixed",
      'member_of_contact_id' => 1,
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "rolling",
      'financial_type_id' => 2,
    ));
    $this->membershipTypeAnnualFixedID = $membershipTypeAnnualFixed['id'];
    //$this->membershipTypeAnnualFixedID = 103;

    $this->_contactIds = $this->createRenewalContact();
    CRM_Core_Error::debug_var('ContactIds', $this->_contactIds);
    //$this->_contactIds = array(262304, 262305, 262306, 262307);

    $this->_msgTemplateId = CRM_Core_DAO::singleValueQuery("SELECT * FROM `civicrm_msg_template` WHERE msg_title = 'Memberships - Receipt (on-line)'");

    // Check if renewal settings is set already
    $count = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM `civicrm_setting` WHERE name = 'membership_renewal_settings'");
    if ($count > 1) {
      // Copy renewal settings if set already
      CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `keep_membership_renewal_settings`");
      CRM_Core_DAO::executeQuery("CREATE TABLE `keep_membership_renewal_settings` AS SELECT * FROM `civicrm_setting` WHERE name = 'membership_renewal_settings'");
      CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_setting` WHERE name = 'membership_renewal_settings'");
    }

    $this->insertMembershipRenewalSettings();

    // Logged in user
    $session = CRM_Core_Session::singleton();
    $session->set('userID', 2);

    return array('contactIds' => $this->_contactIds, 'membership_type_id' => $this->membershipTypeAnnualFixedID);
  }

  /**
   * @depends testPrepareRenewal
   */
  public function testPrepareRenewalDates($testDetails) {
    $this->callAPISuccess('Membershiprenewal', 'Preparerenewaldates', array());
  }

  /**
   * @depends testPrepareRenewal
   */
  public function testPrepareRenewalTempTable($testDetails) {
    
    $status = CRM_Membershiprenewal_BAO_Batch::prepareTempTable($this->_renewalMonth, $this->_renewalYear);

    // Process renewal only for contacts created for test case
    $contactIdStr = implode(',', $testDetails['contactIds']);
    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_membership_renewal_details` WHERE contact_id NOT IN ({$contactIdStr})");

    $this->assertNotEmpty($status);
  }

  /**
   * @depends testPrepareRenewal
   */
  public function testProcessRenewal($testDetails) {
    
    // Create renewal activities
    $result = CRM_Membershiprenewal_BAO_Batch::createActivities($this->_renewalMonth, $this->_renewalYear);

    $this->assertNotEmpty($result['activities']);

    if (!empty($result['activities'])) {
      $this->_activities = $result['activities'];

      // Create batch
      $currentDate = date('Y-m-d H:i:s');
      $params = array(
        'title' => 'Renewal_Batch_Test_NOV2017',
        'created_date' => $currentDate,
        'first_reminder_date' => $currentDate,
        'renewal_month_year' => $this->_renewalMonth.'-'.$this->_renewalYear,
      );
      $batch = CRM_Membershiprenewal_BAO_Batch::createRenewalBatch($params);

      $this->_batchId = $batch->id;

      $this->assertNotEmpty($batch->id);

      if (!empty($batch->id)) {
        // Insert activities in entity batch table
        CRM_Membershiprenewal_BAO_Batch::insertActivitiesForBatch($batch->id, $reminder = 1);

        // Record renewal log
        CRM_Membershiprenewal_BAO_Batch::recordRenewalLog($batch->id);
      }
    }
  }

  /**
   * @depends testPrepareRenewal
   */
  public function testResetRenewal($testDetails) {
    // Delete records created during renewal test case
    $this->resetRenewal();

    CRM_Core_Error::debug_var('memTypeId', $this->membershipTypeAnnualFixedID);

    // Delete created membership type
    $this->callAPISuccess('membership_type', 'delete', array('id' => $testDetails['membership_type_id']));

    // Delete created contacts
    //$contactIds = $this->_contactIds;
    foreach($testDetails['contactIds'] as $contactId) {
      $this->callAPISuccess('contact', 'delete', array('id' => $contactId, 'skip_undelete' => TRUE));
    }

    /*$contactDetails = $this->_contactDetails;
    foreach($contactDetails as $contact) {
      if (isset($contact['api.email.create'])) {
        unset($contact['api.email.create']);
      }

      if (isset($contact['api.membership.create'])) {
        unset($contact['api.membership.create']);
      }

      if (isset($contact['api.address.create'])) {
        unset($contact['api.address.create']);
      }
      $contact['skip_undelete'] = TRUE;

      $this->callAPISuccess('contact', 'delete', $contact);
    }*/

    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_setting` WHERE name = 'membership_renewal_settings'");
    if (CRM_Core_DAO::checkTableExists('keep_membership_renewal_settings')) {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_setting SELECT * FROM keep_membership_renewal_settings");
      CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `keep_membership_renewal_settings`");
    }
  }
}
