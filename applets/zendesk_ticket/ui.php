<?php
$CI =& get_instance();
$plugin_info = $plugin->getInfo();
$zendesk_user = PluginData::get('zendesk_user');
$currentlyIsUser = AppletInstance::getUserGroupPickerValue('permissions') instanceof VBX_User; 
?>
<style>
a.ajax_loader { background:url(<?php echo base_url() ?>assets/i/ajax-loader.gif); display:inline-block; width:16px; height:11px; vertical-align:middle; }
div.system_msg { display:inline-block; line-height:30px; vertical-align:center; }
div.system_msg > * { vertical-align:middle; }
div.vbx-applet div.section { margin-bottom:20px; }
span[class$="err"] { color:red; }
</style>

<div class="vbx-applet zendesk_ticket_applet">
    <?php if(empty($zendesk_user)): ?>
    <div id="zendesk_api_access" class="section">
        <h2>Zendesk API Access</h2>
        <p>It looks like you are setting up for the first time. Please enter your access credentials so we can connect to Zendesk.</p>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Zendesk Url - the url to your Zendesk which is something like https or http://yoursite.zendesk.com.</label>
            <input name="zendesk_url" class="medium" type="text" value="" />
            <span class="zendesk_url_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Email - your email used to login to Zendesk</label>
            <input name="zendesk_email" class="medium" type="text" value="" />
            <span class="zendesk_email_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:5px;">
            <label>Password - your password used to login to Zendesk</label>
            <input name="zendesk_password" class="medium" type="password" value="" />
            <span class="zendesk_password_err"></span>
        </div>

        <div style="line-height:30px;">
            <button class="inline-button submit-button zendesk_test_creds_btn" style="margin-top:5px; vertical-align:center;">
                <span>Test</span>
            </button>
            <div class="system_msg"></div>
        </div>

        <div style="clear:both;"></div>
    </div>
    <?php endif; ?>

    <div class="prompt-for-group" style="display: <?php echo $currentlyIsUser ? "none" : ""  ?>">
        <h2>Prompt</h2>
        <p>What will the caller hear before leaving their message?</p>
        <?php echo AppletUI::AudioSpeechPicker('prompt') ?>
    </div>
    
    <div class="prompt-for-individual" style="display: <?php echo !$currentlyIsUser ? "none" : ""  ?>">
        <h2>Prompt</h2>
        
        <div class="vbx-full-pane">
            <fieldset class="vbx-input-container">
                The individual's personal voicemail greeting will be played.
            </fieldset>
        </div>
    </div>
    <br />

    <h2>Take voicemail</h2>
    <p>Which individual or group should receive the voicemail?</p>
    <?php echo AppletUI::UserGroupPicker('permissions'); ?>
</div>

<script>
var base_url = '<?php echo base_url() ?>';
var zendesk_user_data = <?php echo empty($zendesk_user) ? 'false' : 'true' ?>;
var plugin_dir = '<?php echo $plugin_info['dir_name'] ?>';
</script>
