{if $memRenewalSettings.include_joiner eq 1}
<h3>Membership Communications - New Joiners & Renewals</h3>
{else}
<h3>Membership Communications - Renewals</h3>
{/if}
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      {if $memRenewalSettingsNotSet}
        {* Membership renewal settings are not done yet *}
        <div id="help">
          {ts}Membership communications parameters not set. Click <a href="{crmURL p="civicrm/membershiprenewal/settings" q="reset=1"}">here</a> to add the communications parameters.{/ts}
        </div>
      {else}
        <div>
          Click the below button to process the communications for {if $memRenewalSettings.include_joiner eq 1} new joiners and renewals {else} renewals {/if}
          <br /><br />
          {foreach from=$renewalList key=key item=value}

          <a class="button" href="{crmURL p="civicrm/membershiprenewal/process" q="month_year=$key&reset=1"}">Process for {$value}</a>

          <br /><br />
          {/foreach}
        </div>
      {/if}

      {if $batchList}
      <table class="selector row-highlight" id="BatchListTable">
        <thead class="sticky">
        <tr>
        <th scope="col">{ts}Batch #{/ts}</th>
         <th scope="col">{ts}Title{/ts}</th>
         <th scope="col">{ts}Month/Year{/ts}</th>
         <th scope="col">{ts}Action{/ts}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$batchList key=batchId item=batchDetails}
        {assign var=id value=$batchDetails.id}
        <tr>
          <td>{$batchDetails.id}</td>
          <td>{$batchDetails.title}</td>
          <td>{$batchDetails.renewal_month_year_label}</td>
          <td>
            <span class="btn-slide crm-hover-button">
            View...
            <ul class="panel" style="display: none;">
            {if $batchDetails.first_reminder_date neq NULL}
            <li>
              <a href='{crmURL p="civicrm/membershiprenewal/batch" q="id=$id&reset=1&reminderType=1"}'>{ts}First Reminder{/ts}</a>
            </li>
            {/if}
            {if $batchDetails.second_reminder_date neq NULL}
            <li>
              <a href='{crmURL p="civicrm/membershiprenewal/batch" q="id=$id&reset=1&reminderType=2"}'>{ts}Second Reminder{/ts}</a>
            </li>
            {/if}
            {if $batchDetails.third_reminder_date neq NULL}
            <li>
              <a href='{crmURL p="civicrm/membershiprenewal/batch" q="id=$id&reset=1&reminderType=3"}'>{ts}Third Reminder{/ts}</a>
            </li>
            {/if}
            </ul>
            </span>
          </td>
        </tr>
        {/foreach}
        </tbody>
      </table>
      {/if}
    </div>
</div>

{if !$memRenewalSettingsNotSet}
<h3>Membership communications parameters and tokens</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
        <div id="help">
          Click <a href="{crmURL p="civicrm/membershiprenewal/viewsettings" q="reset=1"}">here</a> to view membership communications parameters and renewal tokens.
        </div>
    </div>
</div>
{/if}

{if $batchList}
<!--<h3>Reset Membership Communications</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
        <div>
          <a onclick="return confirm('This action will delete all batches and the related activities. Do you want to continue?')" href="{crmURL p="civicrm/membershiprenewal" q="reset=1&action=deleteallbatches"}">
            <b><span class="crm-contact-deceased">DELETE ALL BATCHES</span></b>
          </a>
        </div>
    </div>
</div>-->
{/if}

{if $batchList}
  {literal}
  <script>
  cj(document).ready( function() { 
    cj('#BatchListTable').DataTable({
      "order": [[ 0, "desc" ]]
    });
  });
  </script>
  {/literal}
{/if}