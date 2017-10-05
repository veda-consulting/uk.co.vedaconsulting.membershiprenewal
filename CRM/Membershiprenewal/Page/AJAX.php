<?php

require_once 'CRM/Utils/Array.php';

class CRM_Membershiprenewal_Page_AJAX
{
  static function getExcludedMembershipsResult() {
    $searchTerm = CRM_Utils_Array::value('searchTerm', $_REQUEST);
    $batchId = CRM_Utils_Array::value('batchId', $_REQUEST);
    //$year = CRM_Utils_Array::value('year', $_REQUEST);
    if (empty($searchTerm)){
      return;
    }

    $extraWhere = '';
    if (!empty($batchId)) {
      $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_EXCLUDED_MEMBER_DETAILS;      
      $extraWhere = " AND renewal.batch_id = {$batchId}";
    } else {
      $tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    }
    // Search in exlucded membership list
    $excludedMemberships = array();

    $tempTableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_BATCH_TEMP_TABLE;
    $selectSql = "
SELECT renewal.membership_id
, renewal.renewal_date
, renewal.end_date
, renewal.contact_id
, contact.display_name
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
, renewal.reason
, renewal.communication_type
FROM {$tempTableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON renewal.contact_id = contact.id
WHERE renewal.status = 0 AND contact.display_name LIKE '%{$searchTerm}%'
{$extraWhere}
";
    $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    while ($dao->fetch()) {
      $excludedMemberships[$dao->membership_id] = $dao->toArray();
    }

    ## Membership renewals not in date range
    /*$tableName = CRM_Membershiprenewal_Constants::MEMBERSHIP_RENEWAL_TABLE_NAME;
    $firstDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
    $lastDate  = date("Y-m-t", strtotime($firstDate));
    $selectSql = "
SELECT renewal.membership_id
, renewal.renewal_date
, renewal.end_date
, renewal.contact_id
, contact.display_name
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
, 'Renewal date is not in date range' as reason
, 'Renewal' as communication_type
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON renewal.contact_id = contact.id
WHERE (renewal.renewal_date < %1 OR renewal.renewal_date > %2)
AND contact.display_name LIKE '%{$searchTerm}%'
";
    $selectParams = array(
      '1' => array($firstDate, 'String' ),
      '2' => array($lastDate, 'String' ),
    );

    $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    while ($dao->fetch()) {
      $excludedMemberships[$dao->membership_id] = $dao->toArray();
    }

    ## No Action is set for the membership type in renewal settings
    // Get all Memberships
    $allMemTypes = CRM_Membershiprenewal_Utils::getAllMembershipTypes();
    $allMemTypes = array_keys($allMemTypes);
    // Get all membership types which has renewal settings
    $allMemTypesForRenewal = CRM_Membershiprenewal_Utils::getAllMembershipTypesSetForRenewal();

    // Get all membership types which are not set for renewal
    $memTypesNotForRenewal = array_diff($allMemTypes, $allMemTypesForRenewal);
    if (!empty($memTypesNotForRenewal)) {
      $memTypesIdsNotForRenewal = @implode(',', $memTypesNotForRenewal);

      $selectSql = "
SELECT renewal.membership_id
, renewal.renewal_date
, renewal.end_date
, renewal.contact_id
, contact.display_name
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
, 'No Action is set for the membership type in renewal settings' as reason
, 'Renewal' as communication_type
FROM {$tableName} renewal
INNER JOIN civicrm_membership member ON renewal.membership_id = member.id 
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON renewal.contact_id = contact.id
WHERE (renewal.renewal_date >= %1 OR renewal.renewal_date <= %2)
AND contact.display_name LIKE '%{$searchTerm}%'
AND member.membership_type_id IN ({$memTypesIdsNotForRenewal})
";
      $selectParams = array(
        '1' => array($firstDate, 'String' ),
        '2' => array($lastDate, 'String' ),
      );

      $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
      while ($dao->fetch()) {
        if (!isset($excludedMemberships[$dao->membership_id])) {
          $excludedMemberships[$dao->membership_id] = $dao->toArray();
        }
      }

      // Get membership renewal settings
      $settingsArray = CRM_Membershiprenewal_Utils::getMembershipRenewalSettings();
      
      // Check if new joiners need to be included in the list
      if (isset($settingsArray['include_joiner']) && $settingsArray['include_joiner'] == 1) {
        // Get new joiners - exlusion list
        $selectSql = "
SELECT member.id as membership_id
, member.end_date
, member.contact_id
, contact.display_name
, member.join_date as join_date
, member.start_date as start_date
, member_type.name as membership_type
, contact.display_name as display_name
, 'No Action is set for the membership type in settings' as reason
, 'New Joiner' as communication_type
FROM civicrm_membership member
INNER JOIN civicrm_membership_type member_type ON member.membership_type_id = member_type.id 
INNER JOIN civicrm_contact contact ON member.contact_id = contact.id
WHERE (member.join_date >= %1 AND member.join_date <= %2)
AND contact.display_name LIKE '%{$searchTerm}%'
AND member.membership_type_id IN ({$memTypesIdsNotForRenewal})
";
        $selectParams = array(
          '1' => array($firstDate, 'String' ),
          '2' => array($lastDate, 'String' ),
        );

        $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
        while ($dao->fetch()) {
          if (!isset($excludedMemberships[$dao->membership_id])) {
            $excludedMemberships[$dao->membership_id] = $dao->toArray();
          }
        }
        
      }
    }*/

    // Get results via API
    /*$matchingContacts = array();
    $params = array(
      'display_name' => $searchTerm,
      'is_deleted' => 0,
    );
    $result = CRM_Voicecommands_Utils::CiviCRMAPIWrapper('Contact', 'get', $params);
    $values = $result['values'];
    foreach($values as $key => $value) {
      $matchingContacts[$value['id']] = $value['id'];
    }

    //print_r ($matchingContacts);exit;

    $exludedFinalResult = array();

    // Get all membership renewals
    $summary = CRM_Membershiprenewal_BAO_Batch::getactivitiesSummaryForRenewals($month, $year);

    //Get excluded membership renewal before create activities
    $excludedMembershipIds = $summary['excluded'];
    $notInDateRangeMembership = CRM_Membershiprenewal_Utils::getExcludedFromMembershipRenewal($month, $year, FALSE, $matchingContacts);
    $excludedMemberships = array();
    if (!empty($notInDateRangeMembership)) {
      $summary['excluded_count'] = $summary['excluded_count'] + count($notInDateRangeMembership);
      foreach ($notInDateRangeMembership as $key => $value) {
        if (array_key_exists($value['contact_id'], $matchingContacts)) {
          $value['reason'] = ts('Renewal date is not in date range');
          $exludedFinalResult[] = $value;
        }
        //$value['reason'] = ts('Renewal date is not in date range');
      }
      $excludedMemberships = $notInDateRangeMembership;
    }

    if (!empty($excludedMembershipIds)) {
      $msgTplNotExistsMembershipIds = CRM_Membershiprenewal_Utils::getMembershipRenewalDetailsByMembershipIds($excludedMembershipIds, $matchingContacts);
      if (!empty($msgTplNotExistsMembershipIds)) {
        foreach ($msgTplNotExistsMembershipIds as $key => $value) {
          //$value['reason'] = ts('Message template not found for this Membership type');
          //$excludedMemberships[] = $value;
          if (array_key_exists($value['contact_id'], $matchingContacts)) {
            $value['reason'] = ts("No Action is set for the membership type in renewal settings");
            $exludedFinalResult[] = $value;
          }
        }
      }
    }*/

    $resultHtml = '<thead class="sticky">
      <tr>
       <th>Contact</th>
       <th>Communication Type</th>
       <th>Membership Type</th>
       <th>Join Date</th>
       <th>Start Date</th>
       <th>End Date</th>
       <th>Renewal Date</th>
       <th>Reason</th>
       <th></th>
      </tr>
      </thead>';
    $resultHtml .= '<tbody>';

    if (empty($excludedMemberships)) {
      $resultHtml .= '<tr>';
      $resultHtml .= '<td colspan="8">No result found.</td>';
      $resultHtml .= '</tr>';
    }
    else {
      foreach($excludedMemberships as $key => $value) {

        $resultHtml .= '<tr>';

        $contacturl = CRM_Utils_System::url('civicrm/contact/view', "cid={$value['contact_id']}&reset=1");
        $resultHtml .= "<td><a href='{$contacturl}' target='_blank' title='View Contact'>{$value['display_name']}</a></td>";

        $resultHtml .= '<td>'.$value['communication_type'].'</td>';
        //$resultHtml .= '<td>'.$value['display_name'].'</td>';
        $resultHtml .= '<td>'.$value['membership_type'].'</td>';
        $resultHtml .= '<td>'.$value['join_date'].'</td>';
        $resultHtml .= '<td>'.$value['start_date'].'</td>';
        $resultHtml .= '<td>'.$value['end_date'].'</td>';
        $resultHtml .= '<td>'.$value['renewal_date'].'</td>';
        $resultHtml .= '<td>'.$value['reason'].'</td>';

        $memurl = CRM_Utils_System::url('civicrm/contact/view/membership', "cid={$value['contact_id']}&action=view&id={$value['membership_id']}&context=membership&selectedChild=member");
        $resultHtml .= "<td><a href='{$memurl}' target='_blank' title='View Membership'>View</a></td>";

        $resultHtml .= '</tr>';
      }
    }

    $resultHtml .= '</tbody>';

    echo $resultHtml;
    exit;
  }
}

