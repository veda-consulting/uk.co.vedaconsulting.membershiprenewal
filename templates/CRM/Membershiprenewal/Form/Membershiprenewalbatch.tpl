{assign var=batchId value=$batchList.id}
{assign var=fileId value=$batchList.print_file_id}
{assign var=efileId value=$batchList.print_entity_file_id}

<table>
<tr>
<td width="70%">
<h3>{ts}Membership Communication(s) - Batch Details{/ts}</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      <table class="crm-info-panel">
        <tbody>
        <tr>
          <td class="label">{ts}Title{/ts}</td>
          <td class="bold">{$batchList.title}</td>
        </tr>
        <tr>
          <td class="label">{ts}Renewal Month/Year{/ts}</td>
          <td class="bold">{$batchList.renewal_month_year_label}</td>
        </tr>
        <tr>
          <td class="label">{ts}Created by{/ts}</td>
          <td class="bold">{$batchList.created_name}</td>
        </tr>
        <tr>
          <td class="label">{ts}Created date{/ts}</td>
          <td class="bold">{$batchList.created_date}</td>
        </tr>
        <tr>
          <td class="label">{ts}First reminder date{/ts}</td>
          <td class="bold">{$batchList.first_reminder_date}</td>
        </tr>
        <tr>
          <td class="label">{ts}Second reminder date{/ts}</td>
          <td class="bold">{$batchList.second_reminder_date}</td>
        </tr>
        <tr>
          <td class="label">{ts}Third reminder date{/ts}</td>
          <td class="bold">{$batchList.third_reminder_date}</td>
        </tr>
        </tbody>
      </table>

    </div>
</div>
</td>
<td>
<h3>{ts}Reminder{/ts}</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      <br />
      <span class="reminderTypeLabel">{$reminderLabel}</span>
      <br /><br />
    </div>
</div>    

<h3>{ts}Summary{/ts}</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
    	<table class="crm-info-panel">
        <tbody>
        <tr>
          <td class="label">{ts}To Email{/ts}</td>
          <td class="bold">{$emailActivitiesCount}</td>
        </tr>
        <tr>
          <td class="label">{ts}To Print{/ts}</td>
          <td class="bold">{$letterActivitiesCount}</td>
        </tr>
        <tr>
          <td class="label">{ts}Unknown{/ts}</td>
          <td class="bold">{$unknownActivitiesCount}</td>
        </tr>
        </tbody>
      </table>

    </div>
</div>

</td>
</tr>
</table>

{if $emailActivities}

<h3>{ts}Membership communication(s) to Email{/ts} ({$emailActivitiesCount})</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      <!--<div id="help">
        {ts}Some help text here.{/ts}
      </div>-->
	  
    <div class="crm-submit-buttons">
      <div id="resend-button" style="display: none;">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      <span id="resend-counter"></span>
    </div>  

	  <table class="selector row-highlight" id="emailActivitiesTable">
        <thead class="sticky">
        <tr>
         <th width="5%" scope="col">
          &nbsp;&nbsp;<input type='checkbox' id='check_all'>
          <!--&nbsp;&nbsp;<input type='checkbox' id='check_this_page'>-->
         </th>
         <th width="30%" scope="col">{ts}Subject{/ts}</th>
         <th width="20%" scope="col">{ts}With{/ts}</th>
         <th width="20%" scope="col">{ts}Date{/ts}</th>
         <th width="10%" scope="col">{ts}Status{/ts}</th>
         <th width="15%" scope="col"></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$emailActivities key=someId item=activityDetails}
        {assign var=activityId value=$activityDetails.id}
        {assign var=contactId value=$activityDetails.contact_id}
        {assign var=activityId value=$activityDetails.activity_id}
        {assign var=activityTypeId value=$activityDetails.activity_type_id}
        <tr class="{$activityDetails.tr_class}">
          <td><input type='checkbox' name='selected_activities[]' class='emailRows' value={$activityId}></td>
          <td>{$activityDetails.subject}</td>
          <td>
            <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}">
            {$activityDetails.activity_contact}
            </a>
          </td>
          <td>{$activityDetails.activity_date_time}</td>
          <td>{$activityDetails.activity_status}</td>
          <td>
            <a href="{crmURL p="civicrm/activity" q="atype=$activityTypeId&action=view&reset=1&id=$activityId&cid=$contactId&context=activity&searchContext=activity"}">
              View
            </a>
          </td>
        </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
</div>

{literal}
<script type="text/javascript">
cj(document).ready( function() { 
	var emailActivitiesTable = cj('#emailActivitiesTable').DataTable({
    "ordering": false,
    "searching": true,
  });

  // Check all
  cj(document).on("change", "input[id='check_all']", function () {
    var cells = emailActivitiesTable.cells().nodes();
    cj( cells ).find(':checkbox').prop('checked', cj(this).prop("checked"));

    setSelectedActivitiesCounter(emailActivitiesTable);
  });

  //cj(document).on("change", "input[id='check_this_page']", function () {
  //  cj(".emailRows").prop('checked', cj(this).prop("checked"));
  //});

  // Individual activities ticked
  cj(document).on("change", "input[name='selected_activities[]']", function () {
    setSelectedActivitiesCounter(emailActivitiesTable);
  });

  // Issue: jQuery DataTables removes non-visible rows from DOM for performance reasons, 
  // that is why only current page elements are posted
  // Solution: You may need to turn those <input type="checkbox"> that are checked 
  // and don't exist in //DOM into <input type="hidden"> upon form submission.
  cj("#Membershiprenewalbatch").on('submit', function(e){
     var $form = cj(this);

     // Iterate over all checkboxes in the table
     emailActivitiesTable.$('input[name="selected_activities[]"]').each(function(){

        // If checkbox doesn't exist in DOM
        if(!cj.contains(document, this)){
           // If checkbox is checked
           if(this.checked){
              // Create a hidden element 
              $form.append(
                 cj('<input>')
                    .attr('type', 'hidden')
                    .attr('name', this.name)
                    .val(this.value)
              );
           }
        } 
     });
  });

  function setSelectedActivitiesCounter(emailActivitiesTable) {
    var count = 0;

    emailActivitiesTable.$('input[name="selected_activities[]"]').each(function(){
      if(this.checked){
        count++;
      }
    });

    cj('#resend-counter').html('');
    if (count > 0) {
      var rowStr = 'rows';
      if (count == 1) {
        rowStr = 'row';
      }
      cj('#resend-counter').html('( ' + count +  ' ' + rowStr + ' selected )');
      cj('#resend-button').show();
    } else {
      cj('#resend-button').hide();
    }
  }

});
</script>

<style>
#resend-counter {
  color: #2cd337;
  font-size: 16px;
  font-weight: bold;
}
</style>
{/literal}

{/if}

<br /><br />

{if $letterActivities}

<h3>{ts}Membership communication(s) to Print{/ts} ({$letterActivitiesCount})</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      <!--<div id="help">
        {ts}Some help text here.{/ts}
      </div>-->

      <div>
        <!--{if $fileId}
          <a class="button" href="{crmURL p="civicrm/file" q="id=$fileId&eid=$efileId&reset=1"}">Download Letters</a>
          <br /><br />
        {else}
          <a class="button" href="{crmURL p="civicrm/membershiprenewal/batch" q="action=printletters&id=$batchId&reset=1"}">Prepare Letters</a>
          <br /><br />
        {/if}  -->
        <table>
        <tr>
          <td width="50%">
            {if $downloadFiles}
            <strong>{ts}Download Letters{/ts}:</strong><br />
              {foreach from=$downloadFiles key=fileId item=fileDetails}
                <a href="{crmURL p="civicrm/file" q="id=$fileId&eid=$batchId&reset=1"}">{$fileDetails.uri}</a><br />
              {/foreach}
            {/if}
          </td>
          <td  width="50%" valign="middle" align="center">
            <a class="button" href="{crmURL p="civicrm/membershiprenewal/batch" q="action=printletters&reminderType=$reminderType&id=$batchId&reset=1"}">{ts}Prepare Letters{/ts}</a>
          </td>
        </tr>
        </table>
      </div>
	  
	  <table class="selector row-highlight" id="letterActivitiesTable">
        <thead class="sticky">
        <tr>
         <th width="35%" scope="col">{ts}Subject{/ts}</th>
         <th width="20%" scope="col">{ts}With{/ts}</th>
         <th width="20%" scope="col">{ts}Date{/ts}</th>
         <th width="10%" scope="col">{ts}Status{/ts}</th>
         <th width="15%" scope="col"></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$letterActivities key=someId item=activityDetails}
        {assign var=contactId value=$activityDetails.contact_id}
        {assign var=activityId value=$activityDetails.activity_id}
        {assign var=activityTypeId value=$activityDetails.activity_type_id}
        <tr class="{$activityDetails.tr_class}">
          <td>{$activityDetails.subject}</td>
          <td>
            <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}">
            {$activityDetails.activity_contact}
            </a>
          </td>
          <td>{$activityDetails.activity_date_time}</td>
          <td>{$activityDetails.activity_status}</td>
          <td>
            <a href="{crmURL p="civicrm/activity" q="atype=$activityTypeId&action=view&reset=1&id=$activityId&cid=$contactId&context=activity&searchContext=activity"}">
              View
            </a>
          </td>
        </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
</div>

{literal}
<script type="text/javascript">
	cj(document).ready( function() { 
		cj('#letterActivitiesTable').DataTable({
      "ordering": false,
      "searching": true,
    });
	});
</script>
{/literal}

{/if}

<br /><br />

{if $unknownActivities}

<h3>{ts}Membership communication(s) to send via alternative communication method{/ts} ({$unknownActivitiesCount})</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      <!--<div id="help">
        {ts}Some help text here.{/ts}
      </div>-->
	  
	  <table class="selector row-highlight" id="unknownActivitiesTable">
        <thead class="sticky">
        <tr>
         <th width="35%" scope="col">{ts}Subject{/ts}</th>
         <th width="20%" scope="col">{ts}With{/ts}</th>
         <th width="20%" scope="col">{ts}Date{/ts}</th>
         <th width="10%" scope="col">{ts}Status{/ts}</th>
         <th width="15%" scope="col"></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$unknownActivities key=someId item=activityDetails}
        {assign var=contactId value=$activityDetails.contact_id}
        {assign var=activityId value=$activityDetails.activity_id}
        {assign var=activityTypeId value=$activityDetails.activity_type_id}
        <tr class="{$activityDetails.tr_class}">
          <td>{$activityDetails.subject}</td>
          <td>
            <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}">
            {$activityDetails.activity_contact}
            </a>
          </td>
          <td>{$activityDetails.activity_date_time}</td>
          <td>{$activityDetails.activity_status}</td>
          <td>
            <a href="{crmURL p="civicrm/activity" q="atype=$activityTypeId&action=view&reset=1&id=$activityId&cid=$contactId&context=activity&searchContext=activity"}">
              View
            </a>
            <br />
            <a href="{crmURL p="civicrm/membershiprenewal/moveactivity" q="id=$activityId&cid=$contactId&batchId=$batchId&reminderType=$reminderType&moveto=email"}">
              Move to Email
            </a>
            <br />
            <a href="{crmURL p="civicrm/membershiprenewal/moveactivity" q="id=$activityId&cid=$contactId&batchId=$batchId&reminderType=$reminderType&moveto=print"}">
              Move to Print
            </a>
          </td>
        </tr>
        {/foreach}
        </tbody>
      </table>
    </div>
</div>

{literal}
<script type="text/javascript">
	cj(document).ready( function() { 
		cj('#unknownActivitiesTable').DataTable({
      "ordering": false,
      "searching": true,
    });
	});
</script>
{/literal}


{/if}

{literal}
<style>
.reminderTypeLabel {
  font-size: 20px;
  font-weight: bold;
  color: #00994d;
  padding: 10px;
  text-align: center;
}
</style>
{/literal}

<h3>{ts}Search membership(s) excluded from this batch{/ts}</h3>

<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
  <table>
    <tr>
      <td>
        {$form.search_term.label} &nbsp; 
        {$form.search_term.html} &nbsp; 
        <button type="button" id="searchExcludedListButton">Search</button> 
      </td>
    </tr>
  </table>

  <table class="selector row-highlight" id="excludedMembershipTable">

  </table>  

</div>

{literal}
<script>

cj( document ).ready(function() {
  var batchId = cj('#id').val();
  cj("#searchExcludedListButton").click(function() {
    if( !cj("#search_term").val() ) {
      alert('Please enter Name or Email to search in the excluded list');
    } else {
      var searchTerm = cj("#search_term").val();
      initiateExcludedMembershipSearch(searchTerm, batchId)
    }
  });
});

function initiateExcludedMembershipSearch(searchTerm, batchId) {
    
    cj("#excludedMembershipTable").html("{/literal}{$loadingImage}{literal}");

    var getResultUrl = CRM.url('civicrm/ajax/rest', {"className": "CRM_Membershiprenewal_Page_AJAX", "fnName": "getExcludedMembershipsResult", "json": 1});

    cj.ajax({
      url : getResultUrl,
      type: "GET",
      data: {searchTerm: searchTerm, batchId: batchId},
      async: false,
      datatype:"json",
      success: function(data, status){
        cj("#excludedMembershipTable").html(data);
        return false;
      }
    })
    .always(function() {
      // after the ajax is complete, this function is 
      // called and you can bind the datatable function on your table
      cj("#excludedMembershipTable").DataTable({
        "destroy": true,
        "pageLength": 10,
        "searching": true,
      });
    });
    return false;
 }

  // cj(document).ready( function() { 
    //cj('#excludedMembershipTable').DataTable({
    //  "pageLength": 100,
    //  "ordering": false,
    //  "searching": true
    //});
  // });
</script>
{/literal}

