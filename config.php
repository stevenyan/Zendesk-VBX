<?php
$CI =& get_instance();
$op = @$_REQUEST['op'];
$zendesk_user = PluginData::get('zendesk_user');

if($op == 'test_credentials') 
{ // {{{
    try {
        $email = @$_REQUEST['email'];
        $password = @$_REQUEST['password'];
        $url = @$_REQUEST['url'];
        $timezone = (int) @$_REQUEST['timezone'];

        $errors = array();
        if(empty($email)) $errors[] = array( 'msg'=>'Email is required.', 'name'=>'zendesk_email' );
        if(empty($password)) $errors[] = array( 'msg'=>'Password is required.', 'name'=>'zendesk_password' );
        if(empty($url)) $errors[] = array( 'msg'=>'URL to your Zendesk is required.', 'name'=>'zendesk_url' );
        else if(strpos($url, 'zendesk') === false) $errors[] = array( 'msg'=>'This is an invalid Zendesk URL.', 'name'=>'zendesk_url' );

        if(!empty($errors)) throw new Exception('FORM_VALIDATION_ERROR');

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url.'/users.json',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERPWD => "$email:$password",
            CURLOPT_RETURNTRANSFER => true
        ));

        $results = curl_exec($ch);
        $ch_info = curl_getinfo($ch);

        if(curl_errno($ch)) {
            error_log('CURL failed due to '.curl_error());
            return FALSE;
        } else {
            if($ch_info['http_code'] >= 200 && $ch_info['http_code'] < 300) {
                throw new Exception('SUCCESS');
            } else {
                throw new Exception('INVALID_CREDENTIALS');
            }
        }

        throw new Exception('EXCEPTION');
    } catch(Exception $e) {
        switch($e->getMessage()) {
            case 'CANNOT_CONNECT_TO_HOST':
                $results = array(
                    'msg' => "Cannot connect to $url.",
                    'key' => 'CANNOT_CONNECT_TO_HOST',
                    'type' => 'error',
                    'data' => array(
                        'url' => $url,
                        'errors' => array(
                            'name' => 'zendesk_url',
                            'msg' => 'Cannot connect to this url.'
                        )
                    )
                );
                break;

            case 'FORM_VALIDATION_ERROR':
                $results = array(
                    'msg' => 'There are errors on the form. Please fix and try again.',
                    'key' => 'FORM_VALIDATION_ERROR',
                    'type' => 'error',
                    'data' => array(
                        'errors' => $errors
                    )
                );
                break;

            case 'INVALID_CREDENTIALS':
                $results = array(
                    'msg' => 'The credentials you entered are invalid.',
                    'key' => 'INVALID_CREDENTIALS',
                    'type' => 'error'
                );
                break;

            case 'OP_REQUIRED':
                $results = array(
                    'msg' => 'No operation selected.',
                    'key' => 'OP_REQUIRED',
                    'type' => 'error'
                );
                break;

            case 'SUCCESS':
                // If credentials are valid, store it to plugin store for this user
                PluginData::set('zendesk_user', array(
                    'url' => $url,
                    'email' => $email,
                    'password' => $password,
                    'timezone' => $timezone
                ));

                $results = array(
                    'msg' => 'Awesome! Your credentials are valid.',
                    'key' => 'SUCCESS',
                    'type' => 'success'
                );
                break;

            default:
            case 'EXCEPTION':
                $results = array(
                    'msg' => 'An unexpected error occurred.',
                    'key' => 'EXCEPTION',
                    'type' => 'error'
                );
                break;
        }
    }
    echo '<JSON_DATA>'.json_encode($results).'</JSON_DATA>';
} // }}}

elseif($op == 'delete_credentials')
{ // {{{
    PluginData::set('zendesk_user', '');
    $results = array(
        'msg' => 'Zendesk credentials erased.',
        'key' => 'SUCCESS',
        'type' => 'success'
    );
    echo '<JSON_DATA>'.json_encode($results).'</JSON_DATA>';
} // }}}
?>

<?php if(empty($op)): ?>
<style>
span[class$="_err"] { color:red; }
a.ajax_loader { background:url(<?php echo base_url() ?>assets/i/ajax-loader.gif); display:inline-block; width:16px; height:11px; vertical-align:middle; }
div.system_msg { display:inline-block; line-height:30px; vertical-align:center; }
div.system_msg > * { vertical-align:middle; }
</style>

<div class="vbx-applet">
    <div id="zendesk_api_access" class="section">
        <h2>API Access Credentials</h2>
        <p>Please enter your access info so we can update Zendesk with incoming messages.</p>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Zendesk URL - the URL to your Zendesk which is something like http or https://yoursite.zendesk.com.</label>
            <input name="zendesk_url" class="medium" type="text" value="<?php echo @$zendesk_user->url ?>" />
            <span class="zendesk_url_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:10px;">
            <label>Email - your email used to login to Zendesk</label>
            <input name="zendesk_email" class="medium" type="text" value="<?php echo @$zendesk_user->email ?>" />
            <span class="zendesk_email_err"></span>
        </div>

        <div class="vbx-input-container input" style="margin-bottom:5px;">
            <label>Password - your password used to login to Zendesk</label>
            <input name="zendesk_password" class="medium" type="password" value="<?php echo @$zendesk_user->password ?>" />
            <span class="zendesk_password_err"></span>
        </div>

        <div style="line-height:30px;">
            <button id="save_cred_btn" class="inline-button submit-button" style="margin-top:5px; vertical-align:center;">
                <span>Save</span>
            </button>
            <a class="delete_creds_btn" href="#">Delete</a>
            <div class="system_msg"></div>
        </div>

        <div style="clear:both;"></div>
    </div><!-- #zendesk_api_access -->
</div>

<script>
var base_url = '<?php echo base_url() ?>';
</script>

<?php $CI->template->add_js('plugins/Zendesk-VBX/config.js') ?>

<?php endif; ?>
