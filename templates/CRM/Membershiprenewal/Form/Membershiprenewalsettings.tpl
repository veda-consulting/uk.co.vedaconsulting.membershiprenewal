{* HEADER *}

<div class="crm-block crm-form-block crm-dotmailer-mapping-form-block">

	<div id="help">
		{ts}Set the membership communications parameters/settings here.{/ts}
	</div>

	<!-- GK 31052017 -->
	<div class="crm-wizard center" id="gk-msform">
		<!-- Progress Bar -->
		<ul id="progressbar">
			<li class="active">Intro</li>
			<li>New Joiners</li>
			<li>Is Yearly?</li>
			<li>Exclude membership status</li>
			<li>1st Reminder</li>
			<li>2nd Reminder</li>
			<li>3rd Reminder</li>
			<li>Emails</li>
			<li>Templates</li>
			<li>Misc</li>
			<li></li>
		</ul>
		<!-- First fieldset - welcome-->
		<fieldset id="first">
			<h2 class="title center">Membership Communications - Setup Wizard</h2>
			<br />
			<h3 class="left">Welcome to the membership communications dashboard setup!</h3>
			<div class="left" style="padding: 4px 6px;">
				<p>The membership communications dashboard allows you to set up scheduled reminder emails and print PDF letters for your membership renewals. It does this by creating activities against each member who is due to expire. If the member renews before the renewal communication activity is created, the reminder will not get sent out.</p>
				<p>There are some things you will need to prepare before you start this wizard:</p>
				<p>1 . You will need to have setup your membership communications message templates for both email and PDF letters. <br>You will need to decide whether you send different emails out to different membership types to just use one standard renewal template for all membership types.
	 			</p>
	 			<p>In total for each membership type (or just one default template) you should prepare 3 templates for both email and PDF letters, : The first renewal reminder template The second renewal reminder template The third and final renewal reminder template</p>
	 			<p>2 . Please have the following information about your renewal process: <br>How long are your memberships? When do you send the first renewal out? When do you send the second renewal out? When do you send the Third renewal out? Do you send different emails out to different membership types?
	 			</p>
	 		</div>
			<input type="button" name="Start" class="next_btn center" value="Start"/>
		</fieldset>
		<!-- End of first fieldset - welcome-->

		<!-- New Joiners -->
		<fieldset>
			<h2 class="title center">New Joiners</h2>
			<p class="subtitle">Step 1</p>
			<h3 class="left">Do you want to include new joiner in the communications batch?</h3>

			<div class="crm-section include_new_joiners left">
				<div class="label">{$form.include_joiner.label}</div>
				<div class="content">
					{$form.include_joiner.html}
					<br />
					<span class="description">Tick if new joined should be included in renewal batch?</span>
				</div>
				<div class="clear"></div>
			</div>

			{foreach from=$noOfJoinerArray key=joinerKey item=joinerVal}
			{assign var=emailElementName value='joiner_email_message_template_'|cat:$joinerKey}
			{assign var=letterElementName value='joiner_letter_message_template_'|cat:$joinerKey}
			<div class="crm-section cut_off_date left" id="NewJoinerCommunication">

				<div class="crm-section include_new_joiners">

					<div class="label">{$form.cut_off_date.label}</div>
					<div class="content">
						{include file="CRM/common/jcalendar.tpl" elementName=cut_off_date}
						<br />
						<span class="description">Cut off date to process new joiners. Any members joined after this date will be processed in Membership communications extension.</span>
					</div>
					<div class="clear"></div>
				</div>

			    <div>
			        <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed collapsed">
			            <div class="crm-accordion-header">
			                New Joiner Communication
			            </div>
			            <!-- /.crm-accordion-header -->
			            <div class="crm-accordion-body">

			            	<!-- renewal activity message templaes -->
							<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">

								<table class="selector row-highlight">
							        <thead class="sticky">
							        <tr>
							         <th scope="col"></th>
							         <th scope="col">
							         	{ts}Email Message Template{/ts}
							         	<br />
										<span class="description">Message template to be used when welcome emails are sent.</span>
							         </th>
							         <th scope="col">
							         	{ts}Letter Message Template{/ts}
							         	<br />
										<span class="description">Message template to be used when welcome letters are printed.</span>
							         </th>
							        </tr>
							        </thead>
							        <tbody>
							        <tr>
							          <td>{ts}Default{/ts}&nbsp;<span class="crm-marker" title="This field is required.">*</span></td>	
							          <td>{$form.$emailElementName.html}</td>
							          <td>{$form.$letterElementName.html}</td>
							        </tr>
							        {foreach from=$memTypes key=memId item=memName}
							        	{assign var=emailMemTypeElementName value=$emailElementName|cat:'_'}
							        	{assign var=emailMemTypeElementName value=$emailMemTypeElementName|cat:$memId}
							        	{assign var=letterMemTypeElementName value=$letterElementName|cat:'_'}
							        	{assign var=letterMemTypeElementName value=$letterMemTypeElementName|cat:$memId}
							        	<tr>
								          <td>{$memName}</td>
								          <td>{$form.$emailMemTypeElementName.html}</td>
								          <td>{$form.$letterMemTypeElementName.html}</td>
								        </tr>	
							        {/foreach}
							        </tbody>
								</table>
							</div>
							<!-- renewal activity message templaes -->

			            </div>
			        </div>
			    </div>
			</div>
			{/foreach}

			<div class="clear"></div>
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="next" type="button" value="Next">
		</fieldset>
		<!-- End of new joiners -->

		<!-- Yearly fieldset -->
		<fieldset>
			<h2 class="title">Renewals</h2>
			<p class="subtitle">Step 2</p>
			<h3 class="left">Does membership renew every year?</h3>

			<div class="crm-section renewal_period left">
				<div class="label">{$form.renewal_period.label}</div>
				<div class="content">
					{$form.renewal_period.html}
					<br />
					<span class="description">Number of months before which renewals are processed for a membership.
					<br />
					Example: If the renewal date is 31/12/2017, then the renewal can be processed in Nov 2017 if the renewal period is set to 1 month.
					</span>
				</div>
				<div class="clear"></div>
			</div>

			{if $memTypePlan eq 'fixed'}
			<div class="crm-section fixed_period_end_day left">
				<div class="label">{$form.fixed_period_end_day.label}</div>
				<div class="content">
					{$form.fixed_period_end_day.html}
					<br />
					<span class="description">As you membership type plan are set as 'Fixed', specify the membership end date.</span>
				</div>
				<div class="clear"></div>
			</div>
			{/if}

			<div class="crm-section autorenew_payment_instrument_id left">
				<div class="label">{$form.autorenew_payment_instrument_id.label}</div>
				<div class="content">
					{$form.autorenew_payment_instrument_id.html}
					<br />
					<span class="description">Select the auto-renew payment methods.
					<br />
					If the membership is linked with a recurring record having the selected payment methods, Auto-renew message templates will be used to send the email or print letters and you will also get the option to auto-renew the membership after the 3rd reminder is sent.</span>
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section renewal_page_link left">
				<div class="label">{$form.renewal_page_link.label}</div>
				<div class="content">
					{$form.renewal_page_link.html}
					<br />
					<span class="description">Enter the membership renewal page full link. This link is used to replace the renewal link token in the emails. 
					<br />
					Example: http://example.com/civicrm/contribute/transact?id=1&reset=1
					</span>
				</div>
				<div class="clear"></div>
			</div>

			<input class="pre_btn" name="previous" type="button" value="Previous">
			{if $memTypePlan eq 'fixed'}
				<input class="next_btn" name="next" type="button" value="Next">
			{else}
		    	<input type="button" name="No" class="" value="No" id="non-yearly" />
				<input type="button" name="Yes" class="next_btn" value="Yes" id="yearly" />
			{/if}
		</fieldset>
		<!-- End of Yearly fieldset -->
		<!-- Membership status fieldset -->
		<fieldset>
		 <h3>Which membership status(s) would you like to exclude?</h3>
		 <p class="subtitle">Step 3</p>
		 <div class="crm-section membership_status">
		   <table class="selector row-highlight">
		     <thead class="sticky">
		       <tr>
		         <th scope="col">
		         {ts}{$form.membership_status.label}{/ts}
		         <br />
		         <span class="description">Membership status(s) selected here would be excluded from renewal process.</span>
		       </th>
		       </tr>
		     </thead>
		     <tbody>
		       <tr class="default-templates">
		         <td>{$form.membership_status.html}</td>
		       </tr>
		     </tbody>
		   </table>
		 </div>
		 <div class="clear"></div>
		 <input class="pre_btn" name="previous" type="button" value="Previous">
		 <input class="next_btn" name="next" type="button" value="Next">
		</fieldset>
		<!-- End of Membership statu fieldset -->
		<!-- First reminder -->
		<fieldset>
			<h2 class="title">First Renewal Reminder</h2>
			<p class="subtitle">Step 4</p>
			<h3 class="left">When do you send the first renewal reminder out before the membership expires?</h3>
			<div class="left crm-section renewal_first_reminder">
				{$form.renewal_first_reminder.html}
				<div class="other-value" style="display: none;">
					{$form.renewal_first_reminder_other.html} days
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="next" type="button" value="Next">
		</fieldset>
		<!-- End of first reminder -->
		<!-- Second reminder -->
		<fieldset>
			<h2 class="title">Second renewal reminder</h2>
			<p class="subtitle">Step 5</p>

			<div class="crm-section enable_second_reminder left">
				<div class="label">{$form.enable_second_reminder.label}</div>
				<div class="content">
					{$form.enable_second_reminder.html}
					<br />
					<span class="description">Tick if you want to send the second reminder</span>
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section enable_second_reminder left" id="SecondReminder">
				<h3 class="left">When do you send the second renewal reminder out before the membership expires?</h3>
				<div class="left crm-section renewal_second_reminder">
					{$form.renewal_second_reminder.html}
					<div class="other-value" style="display: none;">
						{$form.renewal_second_reminder_other.html} days
					</div>
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="next" type="button" value="Next">
		</fieldset>
		<!-- End of second reminder -->
		<!-- Third reminder -->
		<fieldset>
			<h2 class="title">Third renewal reminder</h2>
			<p class="subtitle">Step 6</p>

			<div class="crm-section enable_third_reminder left">
				<div class="label">{$form.enable_third_reminder.label}</div>
				<div class="content">
					{$form.enable_third_reminder.html}
					<br />
					<span class="description">Tick if you want to send the third reminder</span>
				</div>
				<div class="clear"></div>
			</div>

			<div class="crm-section enable_third_reminder left" id="ThirdReminder">
				<h3 class="left">When do you send the third renewal reminder out before the membership expires?</h3>
				<div class="left crm-section renewal_third_reminder">
					{$form.renewal_third_reminder.html}
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="next" type="button" value="Next">
		</fieldset>
		<!-- End of Third reminder -->
		<!-- Message templates Type -->
		<fieldset>
			<h2 class="title">Message Templates</h2>
			<p class="subtitle">Step 7</p>
			<h3 class="left">Do you send different emails to different membership types?</h3>
			{$form.activity_test.html}
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="no" type="button" value="No" id="same-templates">
			<input class="next_btn" name="yes" type="button" value="Yes" id="different-templates">
		</fieldset>
		<!-- End of Message templates Type -->
		<!-- Last fieldset -->
		<fieldset id="last">
			<h2 class="title">Message Templates</h2>
			<p class="subtitle">Step 8</p>
	    	{foreach from=$noOfRenewalArray key=renewalKey item=renewalVal}
		    {assign var=emailElementName value='email_message_template_'|cat:$renewalKey}
		    {assign var=letterElementName value='letter_message_template_'|cat:$renewalKey}
		    {assign var=autorenewEmailElementName value='autorenew_email_message_template_'|cat:$renewalKey}
			{assign var=autorenewLetterElementName value='autorenew_letter_message_template_'|cat:$renewalKey}
		    <div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">
	        <div>
            <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed collapsed">
              <div class="crm-accordion-header">
                {$renewalVal} Renewal Reminder
              </div>
              <!-- /.crm-accordion-header -->
              <div class="crm-accordion-body">
            		<!-- renewal activity message templaes -->
				<div class="crm-block crm-form-block crm-dotmailer-mapping-list-form-block">

				<table class="selector row-highlight">
		        <thead class="sticky">
			        <tr>
			         	<th scope="col"></th>
			         	<th scope="col">
				         	{ts}Email Message Template{/ts}
				         	<br />
							<span class="description">Message template to be used when renewal emails are sent to contacts.</span>
			         	</th>
			         	<th scope="col">
			         		{ts}Letter Message Template{/ts}
			         		<br />
							<span class="description">Message template to be used when renewal letters are printed.</span>
			         	</th>
			         	<th scope="col">
				         	{ts}Auto-renew Email Message Template{/ts}
				         	<br />
							<span class="description">Message template to be used when auto-renew emails are sent.</span>
				         </th>
				         <th scope="col">
				         	{ts}Auto-renew Letter Message Template{/ts}
				         	<br />
							<span class="description">Message template to be used when auto-renew letters are printed.</span>
				         </th>
			        </tr>
		        </thead>
		        <tbody>
			        <tr class="default-templates">
			          <td>{ts}Default{/ts}&nbsp;<span class="crm-marker" title="This field is required.">*</span></td>
			          <td>{$form.$emailElementName.html}</td>
			          <td>{$form.$letterElementName.html}</td>
			          <td>{$form.$autorenewEmailElementName.html}</td>
				      <td>{$form.$autorenewLetterElementName.html}</td>
			        </tr>
			        {foreach from=$memTypes key=memId item=memName}
			        	{assign var=emailMemTypeElementName value=$emailElementName|cat:'_'}
			        	{assign var=emailMemTypeElementName value=$emailMemTypeElementName|cat:$memId}
			        	{assign var=letterMemTypeElementName value=$letterElementName|cat:'_'}
			        	{assign var=letterMemTypeElementName value=$letterMemTypeElementName|cat:$memId}

		        		{assign var=autorenewEmailMemTypeElementName value=$autorenewEmailElementName|cat:'_'}
			        	{assign var=autorenewEmailMemTypeElementName value=$autorenewEmailMemTypeElementName|cat:$memId}
			        	{assign var=autorenewLetterMemTypeElementName value=$autorenewLetterElementName|cat:'_'}
			        	{assign var=autorenewLetterMemTypeElementName value=$autorenewLetterMemTypeElementName|cat:$memId}
			        	<tr class="membership-templates">
				          <td>{$memName}</td>
				          <td>{$form.$emailMemTypeElementName.html}</td>
				          <td>{$form.$letterMemTypeElementName.html}</td>
				          <td>{$form.$autorenewEmailMemTypeElementName.html}</td>
					      <td>{$form.$autorenewLetterMemTypeElementName.html}</td>
				        </tr>
			        {/foreach}
		        </tbody>
					</table>
				</div>
				<!-- renewal activity message templates -->
              </div>
            </div>
	        </div>
		    </div>
	    	{/foreach}
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="next_btn" name="next" type="button" value="Next">
		</fieldset>

		<!-- Enable SMS, Word Mailmerge, Attachment -->
		<fieldset>
			<h2 class="title">Miscellaneous</h2>
			<p class="subtitle">Step 9</p>

			<h3 class="left">Do you want to attach the letter renewal reminder as attachment in the email?</h3>
			<div class="crm-section enable_word_mailmerge left">
				<div class="label">{$form.enable_attachment.label}</div>
				<div class="content">
					{$form.enable_attachment.html}
					<br />
					<span class="description">The letter content of the renewal reminder will also be included as attachment.</span>
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>

			<h3 class="left">Do you want to send SMS to members about the renewal?</h3>
			<div class="crm-section enable_sms left">
				<div class="label">{$form.enable_sms.label}</div>
				<div class="content">
					{$form.enable_sms.html}
					<br />
					<span class="description"><strong>IMPORTANT:</strong> Make sure you have configured SMS Provider for your CiviCRM installation.</span>
				</div>
				<div class="clear"></div>
			</div>
			<div class="crm-section sms_activity_subject left">
				<div class="label">{$form.sms_activity_subject.label}</div>
				<div class="content">
					{$form.sms_activity_subject.html}
				</div>
				<div class="clear"></div>
			</div>
			<div class="crm-section sms_message left">
				<div id='char-count-message'></div>
				<div class="label">{$form.sms_text_message.label}</div>
				<div class="content">
					{$form.sms_text_message.html}
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
			<h3 class="left">Do you want to send enable Word Mailmerge for printing letters?</h3>
			<div class="crm-section enable_word_mailmerge left">
				<div class="label">{$form.enable_word_mailmerge.label}</div>
				<div class="content">
					{$form.enable_word_mailmerge.html}
					<br />
					<span class="description"><strong>IMPORTANT:</strong> Make sure you have installed the word mailmerge extension.</span>
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
			<input class="pre_btn" name="previous" type="button" value="Previous">
			<input class="submit_btn" type="submit" value="Submit">
		</fieldset>
		<!-- End of Enable SMS, Word Mailmerge, Attachment  -->
	</div>
	<!-- End of Lastrst fieldset -->
</div>
<!-- End of multistep form -->

{literal}
<style type="text/css">

h2 {
	font-weight: bold;
}

/*form styles*/
#gk-msform #progressbar{
	margin:0;
	padding:0;
	font-size:14px;
}
#gk-msform .active{
	color: green;
	font-weight: bold;
}
#gk-msform fieldset{
	display:none;
	/*width: 986px;*/
	padding:20px;
	margin: 30px auto;
	border-radius:5px;
	box-shadow: 3px 3px 25px 1px gray;
}
#gk-msform #first{
	display:block;
}
#gk-msform input[type=submit],
#gk-msform input[type=button]{
	width: 100px;
	margin:15px 25px;
	padding: 5px;
	height: 30px;
	/*background: sienna;*/
	border: none;
	border-radius: 4px;
	color: white;
	/*font-family: 'Droid Serif', serif;*/
}
#gk-msform input[type=submit] {
	background: sienna;
}
#gk-msform h2,#gk-msform p{
	text-align:center;
	/*font-family: 'Droid Serif', serif;*/
}
#gk-msform li{
	margin:25px;
	display:inline;
	color:#c1c5cc;
	/*font-family: 'Droid Serif', serif;*/
}
.select2-search-field, .select2-search-choice {
	margin:2px !important;
	color: #3E3E3E !important;
}
#gk-msform p {
	/*font-size: 16px;*/
}
#gk-msform .subtitle {
	font-size: 12px;
}
.center {
	text-align: center !important;
}
.left, .left p {
	text-align: left !important;
}
.select2-search-field, .select2-search-choice {
 margin:2px !important;
 color: #3E3E3E !important;
}
</style>
<script type="text/javascript">
  cj(document).ready(function(){

		// Function Runs On NEXT Button Click
		cj(".next_btn").click(function() {
			cj(this).parent().next().fadeIn('slow');
			cj(this).parent().css({
				'display': 'none'
			});
			// Adding Class Active To Show Steps Forward;
			cj('.active').next().addClass('active');
		});

		// Function Runs On PREVIOUS Button Click
		cj(".pre_btn").click(function() {
			cj(this).parent().prev().fadeIn('slow');
			cj(this).parent().css({
			'display': 'none'
			});
			// Removing Class Active To Show Steps Backward;
			cj('.active:last').removeClass('active');
		});

		// Function Runs On NO Button Click (step 5)
		cj("#same-templates").click(function() {
			// Hide all the selection fields of membership types
			cj('.membership-templates').hide();

			// Set same as default for all the memberships
			cj(".membership-templates select").val("sameasdefault");
		});

		// Function Runs On YES Button Click (step 5)
		cj("#different-templates").click(function() {
			// display the selection fields of membership types
			cj('.membership-templates').show();
		});

  });

  CRM.$(function($) {
  	// show/hide first reminder other amount field
  	showHideElement('#CIVICRM_QFID_0_renewal_first_reminder', '.renewal_first_reminder .other-value');
  	cj(".renewal_first_reminder input:radio").click(function() {
    	showHideElement('#CIVICRM_QFID_0_renewal_first_reminder', '.renewal_first_reminder .other-value');
   	});
   	// show/hide second reminder other amount field
   	showHideElement('#CIVICRM_QFID_0_renewal_second_reminder', '.renewal_second_reminder .other-value');
  	cj(".renewal_second_reminder input:radio").click(function() {
    	showHideElement('#CIVICRM_QFID_0_renewal_second_reminder', '.renewal_second_reminder .other-value');
   	});

  	// Assign renewal_first_reminder other value ot the radio button
  	assignOtherValue('#renewal_first_reminder_other', '#CIVICRM_QFID_0_renewal_first_reminder');
   	cj('#renewal_first_reminder_other').change(function() {
   		assignOtherValue('#renewal_first_reminder_other', '#CIVICRM_QFID_0_renewal_first_reminder');
   	});

   	// Assign renewal_second_reminder other value ot the radio button
		assignOtherValue('#renewal_second_reminder_other', '#CIVICRM_QFID_0_renewal_second_reminder');
		cj('#renewal_second_reminder_other').change(function() {
   		assignOtherValue('#renewal_second_reminder_other', '#CIVICRM_QFID_0_renewal_second_reminder');
		});

		// function to show/hide elemnts
		function showHideElement(checkEle, toHide) {
    if (cj(checkEle).prop('checked')) {
      cj(toHide).show();
    }
    else {
      cj(toHide).hide();
    }
  }

  // function to assign other value to the radio button
  function assignOtherValue(otherEle, radioBtn) {
			value = Number(cj(otherEle).val());
			if(isNaN(value)){
      value = '';
    }
    cj(radioBtn).val(value);
  }

});
</script>
{/literal}
<!-- End of GK 31052017 -->

{literal}
<script>
cj( document ).ready(function() {
	cj('#NewJoinerCommunication').hide();
	cj('#SecondReminder').hide();
	cj('#ThirdReminder').hide();
	cj('#sms_activity_subject').parent().parent().hide();
	cj('#sms_text_message').parent().parent().hide();
	
	if (cj('#include_joiner').prop('checked')) {
	    cj('#NewJoinerCommunication').show();
	} else {
	    cj('#NewJoinerCommunication').hide();
	}

	if (cj('#enable_second_reminder').prop('checked')) {
	    cj('#SecondReminder').show();
	} else {
	    cj('#SecondReminder').hide();
	}

	if (cj('#enable_third_reminder').prop('checked')) {
	    cj('#ThirdReminder').show();
	} else {
	    cj('#ThirdReminder').hide();
	}

	if (cj('#enable_sms').prop('checked')) {
	    cj('#sms_activity_subject').parent().parent().show();
		cj('#sms_text_message').parent().parent().show();
	} else {
	    cj('#sms_activity_subject').parent().parent().hide();
		cj('#sms_text_message').parent().parent().hide();
	} 

	cj('#include_joiner').change(function() {
		if (cj('#include_joiner').prop('checked')) {
		    cj('#NewJoinerCommunication').show();
		} else {
		    cj('#NewJoinerCommunication').hide();
		}    
	});

	cj('#enable_second_reminder').change(function() {
		if (cj('#enable_second_reminder').prop('checked')) {
		    cj('#SecondReminder').show();
		} else {
		    cj('#SecondReminder').hide();
		}
	});

	cj('#enable_third_reminder').change(function() {
		if (cj('#enable_third_reminder').prop('checked')) {
		    cj('#ThirdReminder').show();
		} else {
		    cj('#ThirdReminder').hide();
		}
	});

	cj('#enable_sms').change(function() {
		if (cj('#enable_sms').prop('checked')) {
		    cj('#sms_activity_subject').parent().parent().show();
			cj('#sms_text_message').parent().parent().show();
		} else {
		    cj('#sms_activity_subject').parent().parent().hide();
			cj('#sms_text_message').parent().parent().hide();
		}    
	});
});

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
