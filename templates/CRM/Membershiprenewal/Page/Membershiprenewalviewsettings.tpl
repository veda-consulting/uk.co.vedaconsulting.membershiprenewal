<h3>Go to</h3>
<div>
<img src="{$config->resourceBase}/i/admin/small/membership_type.png" alt="Membership Types"> <a href="{crmURL p="civicrm/membershiprenewal" q="reset=1"}">Membership Communications Dashboard</a> <br /><br />
</div>

<h3>Settings</h3>
<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
    <div>
      {if $memRenewalSettings}
        <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
            <div class="crm-accordion-header">
                Communications parameters
            </div>
            <!-- /.crm-accordion-header -->
            <div class="crm-accordion-body">

              <div id="help">
                {ts}To add/update the communications parameters, click <a href="{crmURL p="civicrm/membershiprenewal/settings" q="reset=1"}">here</a>.{/ts}
              </div>

              <table class="selector row-highlight">
                <thead class="sticky">
                <tr>
                 <th scope="col">{ts}Parameters{/ts}</th>
                 <th scope="col">{ts}Value{/ts}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>Include New Joiners?</td>
                  <td>{if $memRenewalSettings.include_joiner}Yes{else}No{/if}</td>
                </tr>
                <tr>
                  <td>Renewal Years</td>
                  <td>{$memRenewalSettings.renewal_years} years</td>
                </tr>
                <tr>
                  <td>Renewal Period</td>
                  <td>{$memRenewalSettings.renewal_period} months</td>
                </tr>
                <!--<tr>
                  <td>Start Offset</td>
                  <td>{$memRenewalSettings.renewal_start_offset} months</td>
                </tr>-->
                <!--<tr>
                  <td>End Offset</td>
                  <td>{$memRenewalSettings.renewal_end_offset} months</td>
                </tr>-->
                {if $memRenewalSettings.renewal_first_reminder}
                <tr>
                  <td>1st Reminder</td>
                  <td>{$memRenewalSettings.renewal_first_reminder} days</td>
                </tr>
                {/if}
                <tr>
                  <td>2nd Reminder</td>
                  <td>
                    {if $memRenewalSettings.enable_second_reminder}
                      {$memRenewalSettings.renewal_second_reminder} days
                    {else}
                      {ts}Disabled{/ts}
                    {/if}
                  </td>
                </tr>
                <tr>
                  <td>3rd Reminder</td>
                  <td>
                    {if $memRenewalSettings.enable_third_reminder}
                      {$memRenewalSettings.renewal_third_reminder} days
                    {else}
                      {ts}Disabled{/ts}
                    {/if}
                  </td>
                </tr>
                <!--<tr>
                  <td>Email Message template</td>
                  <td>{$memRenewalSettings.email_message_template_title}</td>
                </tr>
                <tr>
                  <td>Letter Message template</td>
                  <td>{$memRenewalSettings.letter_message_template_title}</td>
                </tr>-->
                </tbody>
              </table>
            </div>
            <!-- /.crm-accordion-body -->
        </div>
        <!-- /.crm-accordion-wrapper -->
      {/if}

      {* Renewal tokens *}
      <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
            <div class="crm-accordion-header">
                Renewal tokens
            </div>
            <!-- /.crm-accordion-header -->
            <div class="crm-accordion-body">

            <table class="selector row-highlight">
                <thead class="sticky">
                <tr>
                 <th scope="col">{ts}Label{/ts}</th>
                 <th scope="col">{ts}Token{/ts}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$renewalTokens key=label item=token}
                <tr>
                  <td>{$label}</td>
                  <td>{$token}</td>
                </tr>
                {/foreach}
                </tbody>
              </table>
            </div>
            <!-- /.crm-accordion-body -->
        </div>
        <!-- /.crm-accordion-wrapper -->

    </div>
</div>

{if $batchList}
  {literal}
  <script>
  cj(document).ready( function() { 
    cj('#BatchListTable').DataTable();
  });
  </script>
  {/literal}
{/if}