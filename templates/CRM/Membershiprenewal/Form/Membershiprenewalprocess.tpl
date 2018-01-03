{* HEADER *}

<div class="crm-block crm-form-block crm-dotmailer-mapping-form-block">

{if $memRenewalSettings.is_test}
  <div class="alert alert-warning">
    <strong>Warning!</strong>  {ts}Membership Renewal has been to set to <strong>Test Mode</strong>{/ts}
  </div>
{/if}

<div class="crm-accordion-wrapper">
	<div class="crm-accordion-header">
		<span>{ts}Batch Details{/ts}</span>
	</div>
	<div class="crm-accordion-body">

		<div id="help">
			{if $memRenewalSettings.include_joiner eq 1}
				{ts}The summary section displays the communications for memberships who joined in the selected month/year and memberships that can be renewed for the selected month/year.{/ts}
			{else}
				{ts}The summary section displays the communications for memberships that can be renewed for the selected month/year.{/ts}
			{/if}
			<br /><br />
			{ts}<strong>You can only run the process once for the selected month/year</strong>. This process will create the activities which will be automatically emailed to the contacts via scheduled reminders (for the contacts who have a valid email address) and the activites for which the renewal letters can be printed (for the contacts who do not have a valid email address, but have a valid postal address).{/ts}
			<br /><br />
			{ts}<strong>Are you sure you want to process membership communications?</strong>{/ts}
		</div>
	
		<div class="crm-section">
			<div class="label">{$form.title.label}</div>
			<div class="content">{$form.title.html}</div>
			<div class="clear"></div>
		</div>

		<div class="crm-section">
			<div class="label">{$form.month_year.label}</div>
			<div class="content">
				{$form.month_year.html}
			</div>
			<div class="clear"></div>
		</div>

		{if $SMSCount > 0}

			<div class="crm-section">
				<div class="label">{$form.send_sms.label}</div>
				<div class="content">
					{$form.send_sms.html}
					<br />Tick to send SMS to contacts having a valid mobile number.
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section">
				<div class="label">&nbsp;</div>
				<div class="content">
					<span id="smsCount">SMS will be sent to {$SMSCount} contact(s).</span>
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section">
				<div class="label">{$form.activity_subject.label}</div>
				<div class="content">
					{$form.activity_subject.html}
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section">
				<div class="label">{$form.sms_text_message.label}</div>
				<div class="content">
					<div id='char-count-message'></div>
					{$form.sms_text_message.html}
				</div>
				<div class="clear"></div>
			</div>

		{/if}

		<div class="crm-submit-buttons">
		{include file="CRM/common/formButtons.tpl" location="bottom"}
		</div>
	</div>
</div>

</div>

<h3>{ts}Membership Communications Summary{/ts}</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">

<p>{ts}Number of selected membership(s){/ts}: {$summary.total_count}</p>

<table class="selector">
<tr>
    <td>{ts}Membership communication(s) can be emailed{/ts}</td>
    <td>{$summary.email_count}</td>
</tr>
<tr>
    <td>{ts}Membership communication(s) letter can be printed{/ts}</td>
    <td>{$summary.letter_count}</td>
</tr>
<tr>
    <td>{ts}Membership communication(s) to send via alternative communication method{/ts}</td>
    <td>{$summary.unknown_count}</td>
</tr>
<!--<tr>
    <td>{ts}Membership renewal(s) exlcuded from this batch{/ts}</td>
    <td>{$summary.excluded_count}</td>
</tr>-->
</table>
</div>

{if $summary.email_count > 0}
<div class="crm-accordion-wrapper collapsed">
<div class="crm-accordion-header">
	<span>{ts}Membership communication(s) can be emailed ({$summary.email_count}){/ts}</span>
</div>
<div class="crm-accordion-body">
	<table class="selector row-highlight" id="emailActivities">
	    <thead class="sticky">
	    <tr>
	     <th>{ts}Contact{/ts}</th>
	     <th>{ts}Communication Type{/ts}</th>
	     <th>{ts}Membership Type{/ts}</th>
	     <th>{ts}Join Date{/ts}</th>
	     <th>{ts}Start Date{/ts}</th>
	     <th>{ts}End Date{/ts}</th>
	     <th>{ts}Renewal Date{/ts}</th>
	     <th></th>
	    </tr>
	    </thead>
	    <tbody>
	    {foreach from=$summary.email_activities key=someId item=membershipsDetails}
	    {assign var=contactId value=$membershipsDetails.contact_id}
	    {assign var=membershipId value=$membershipsDetails.membership_id}
	    <tr>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}" target='_blank'>
	        {$membershipsDetails.display_name}
	        </a>
	      </td>
	      <td>{$membershipsDetails.communication_type}</td>
	      <td>{$membershipsDetails.membership_type}</td>
	      <td>{$membershipsDetails.join_date|crmDate}</td>
	      <td>{$membershipsDetails.start_date|crmDate}</td>
	      <td>{$membershipsDetails.end_date|crmDate}</td>
	      <td>{$membershipsDetails.renewal_date|crmDate}</td>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=$contactId&id=$membershipId&context=membership&selectedChild=member"}" target='_blank'>
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
<script>
cj('#emailActivities').DataTable({
	"pageLength": 100,
	"ordering": false,
	"searching": true
});
</script>
{/literal}
{/if}

{if $summary.letter_count > 0}
<div class="crm-accordion-wrapper collapsed">
<div class="crm-accordion-header">
	<span>{ts}Membership communication(s) letter can be printed ({$summary.letter_count}){/ts}</span>
</div>
<div class="crm-accordion-body">
	<table class="selector row-highlight" id="letterActivities">
	    <thead class="sticky">
	    <tr>
	     <th>{ts}Contact{/ts}</th>
	     <th>{ts}Communication Type{/ts}</th>
	     <th>{ts}Membership Type{/ts}</th>
	     <th>{ts}Join Date{/ts}</th>
	     <th>{ts}Start Date{/ts}</th>
	     <th>{ts}End Date{/ts}</th>
	     <th>{ts}Renewal Date{/ts}</th>
	     <th></th>
	    </tr>
	    </thead>
	    <tbody>
	    {foreach from=$summary.letter_activities key=someId item=membershipsDetails}
	    {assign var=contactId value=$membershipsDetails.contact_id}
	    {assign var=membershipId value=$membershipsDetails.membership_id}
	    <tr>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}" target='_blank'>
	        {$membershipsDetails.display_name}
	        </a>
	      </td>
	      <td>{$membershipsDetails.communication_type}</td>
	      <td>{$membershipsDetails.membership_type}</td>
	      <td>{$membershipsDetails.join_date|crmDate}</td>
	      <td>{$membershipsDetails.start_date|crmDate}</td>
	      <td>{$membershipsDetails.end_date|crmDate}</td>
	      <td>{$membershipsDetails.renewal_date|crmDate}</td>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=$contactId&id=$membershipId&context=membership&selectedChild=member"}" target='_blank'>
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
<script>
cj('#letterActivities').DataTable({
	"pageLength": 100,
	"ordering": false,
	"searching": true
});
</script>
{/literal}
{/if}

{if $summary.unknown_count > 0}
<div class="crm-accordion-wrapper collapsed">
<div class="crm-accordion-header">
	<span>{ts}Membership communication(s) to send via alternative communication method ({$summary.unknown_count}){/ts}</span>
</div>
<div class="crm-accordion-body">
	<table class="selector row-highlight" id="unknownActivities">
	    <thead class="sticky">
	    <tr>
	     <th>{ts}Contact{/ts}</th>
	     <th>{ts}Communication Type{/ts}</th>
	     <th>{ts}Membership Type{/ts}</th>
	     <th>{ts}Join Date{/ts}</th>
	     <th>{ts}Start Date{/ts}</th>
	     <th>{ts}End Date{/ts}</th>
	     <th>{ts}Renewal Date{/ts}</th>
	     <th></th>
	    </tr>
	    </thead>
	    <tbody>
	    {foreach from=$summary.unknown_activities key=someId item=membershipsDetails}
	    {assign var=contactId value=$membershipsDetails.contact_id}
	    {assign var=membershipId value=$membershipsDetails.membership_id}
	    <tr>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}" target='_blank'>
	        {$membershipsDetails.display_name}
	        </a>
	      </td>
	      <td>{$membershipsDetails.communication_type}</td>
	      <td>{$membershipsDetails.membership_type}</td>
	      <td>{$membershipsDetails.join_date|crmDate}</td>
	      <td>{$membershipsDetails.start_date|crmDate}</td>
	      <td>{$membershipsDetails.end_date|crmDate}</td>
	      <td>{$membershipsDetails.renewal_date|crmDate}</td>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=$contactId&id=$membershipId&context=membership&selectedChild=member"}" target='_blank'>
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
<script>
cj('#unknownActivities').DataTable({
	"pageLength": 100,
	"ordering": false,
	"searching": true
});
</script>
{/literal}
{/if}

{* if $summary.excluded_count > 0 *}
<div class="crm-accordion-wrapper collapsed">
<div class="crm-accordion-header">
	<span>{ts}Search membership(s) excluded from this batch{/ts}</span>
</div>
<div class="crm-accordion-body" id="excludedResultTable">

	<!--<div class="crm-block crm-form-block crm-membership-renewal-search-block">
		<div class="crm-section">
			<div class="label">{$form.search_term.label}</div>
			<div class="content">{$form.search_term.html}</div>
			<div class="clear"></div>
		</div>
	</div>-->

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

	<!--<table class="selector row-highlight" id="excludedMembershipTableTemp">
	    <thead class="sticky">
	    <tr>
	     <th>{ts}Contact{/ts}</th>
	     <th>{ts}Membership Type{/ts}</th>
	     <th>{ts}Join Date{/ts}</th>
	     <th>{ts}reason{/ts}</th>
	     <th></th>
	    </tr>
	    </thead>
	    <tbody>
	    {foreach from=$excludedMemberships key=someId item=membershipsDetails}
	    {assign var=contactId value=$membershipsDetails.contact_id}
	    {assign var=membershipId value=$activityDetails.membership_id}
	    <tr>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=$contactId"}">
	        {$membershipsDetails.display_name}
	        </a>
	      </td>
	      <td>{$membershipsDetails.membership_type}</td>
	      <td>{$membershipsDetails.join_date|crmDate}</td>
	      <td>{$membershipsDetails.reason}</td>
	      <td>
	        <a href="{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=$contactId&id=$membershipId&context=membership&selectedChild=member"}">
	          View 
	        </a>
	      </td>
	    </tr>
	    {/foreach}
	    </tbody>
	  </table>-->
</div>
</div>

{literal}
<style type="text/css">
 .alert {
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid transparent;
  border-radius: 4px;
 }

 .alert-warning {
  color: #a94442;
  background-color: #f2dede;
  border-color: #ebccd1;
 }

</style>

<script>

cj( document ).ready(function() {
	var month = {/literal}{$month}{literal};
	var year = {/literal}{$year}{literal};
	cj("#searchExcludedListButton").click(function() {
	 	if( !cj("#search_term").val() ) {
	 		alert('Please enter Name or Email to search in the excluded list');
	 	} else {
	 		var searchTerm = cj("#search_term").val();
	 		initiateExcludedMembershipSearch(searchTerm, month, year)
	 	}
	});
});

function initiateExcludedMembershipSearch(searchTerm, month, year) {
    
    cj("#excludedMembershipTable").html("{/literal}{$loadingImage}{literal}");

    var getResultUrl = CRM.url('civicrm/ajax/rest', {"className": "CRM_Membershiprenewal_Page_AJAX", "fnName": "getExcludedMembershipsResult", "json": 1});

    cj.ajax({
      url : getResultUrl,
      type: "GET",
      data: {searchTerm: searchTerm, month: month, year: year},
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
    //	"pageLength": 100,
    //	"ordering": false,
    //	"searching": true
    //});
  // });
</script>
{/literal}
{* /if *}

{if $SMSCount > 0}
{literal}
<script type="text/javascript">

cj( document ).ready(function() {
	hideAllFields();

	showFields('#send_sms', '#activity_subject');
	showFields('#send_sms', '#sms_text_message');
	showFields('#send_sms', '#smsCount');
	cj('#send_sms').change(function() {
    	showFields('#send_sms', '#activity_subject');
    	showFields('#send_sms', '#sms_text_message');
    	showFields('#send_sms', '#smsCount');
  	});
});

function showFields(triggerField, targetField) {
 if (cj(triggerField).prop('checked')) {
    cj(targetField).parent().parent().show();
  } else {
    cj(targetField).parent().parent().hide();
  }
}

function hideAllFields() {
  cj('#activity_subject').parent().parent().hide();
  cj('#sms_text_message').parent().parent().hide();
  cj('#smsCount').parent().parent().hide();
}

{/literal}{if $max_sms_length}{literal}
maxCharInfoDisplay();

cj('#sms_text_message').bind({
  change: function() {
   maxLengthMessage();
  },
  keyup:  function() {
   maxCharInfoDisplay();
  }
});

function maxLengthMessage()
{
   var len = cj('#sms_text_message').val().length;
   var maxLength = {/literal}{$max_sms_length}{literal};
   if (len > maxLength) {
      cj('#sms_text_message').crmError({/literal}'{ts escape="js"}SMS body exceeding limit of 160 characters{/ts}'{literal});
      return false;
   }
return true;
}

function maxCharInfoDisplay(){
   var maxLength = {/literal}{$max_sms_length}{literal};
   var enteredCharLength = cj('#sms_text_message').val().length;
   var count = enteredCharLength;

   if( count < 0 ) {
      cj('#sms_text_message').val(cj('#sms_text_message').val().substring(0, maxLength));
      count = 0;
   }
   cj('#char-count-message').text( "You can insert up to " + maxLength + " characters. You have entered " + count + " characters." );
}
{/literal}{/if}{literal}

</script>
{/literal}

{/if}

{* FOOTER *}

<div id="dialog" title="Processing" style="display: none;">
  <p>Processing...<img src="{$config->resourceBase}i/loading.gif" style="width:15px;height:15px;" /></p>
</div>

{literal}
<script>
/*cj( document ).ready(function() {
	cj("#_qf_Membershiprenewalprocess_submit-bottom").click(function() {
		cj("#dialog").dialog({
	  	width: 350,
      height: 100,
			modal: true,
			closeOnEscape: false,
	   		open: function(event, ui) { cj(".ui-dialog-titlebar-close").hide(); }
		});
	});
});*/
</script>
{/literal}

