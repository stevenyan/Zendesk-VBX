<?php
$CI =& get_instance();
$status = @$_REQUEST['status'];
$flow = @AppletInstance::getFlow();
$flow_id = $flow->id;
$instance_id = AppletInstance::getInstanceId();

function zendesk_client($path, $method='GET', $xml = '')
{ // {{{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => ZENDESK_URL.$path,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HEADER => FALSE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_USERPWD => ZENDESK_EMAIL.':'.ZENDESK_PASSWORD,
        CURLOPT_RETURNTRANSFER => TRUE
    ));

    switch($method) {
        case 'GET':
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
            break;

        case 'POST':
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            break;

        case 'PUT':
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;

        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

        default:
            return FALSE;
    }

    $results = curl_exec($ch);
    $ch_info = curl_getinfo($ch);

    if(curl_errno($ch)) {
        error_log('CURL failed due to '.curl_error());
        return FALSE;
    } else {
        if($ch_info['http_code'] >= 200 && $ch_info['http_code'] < 300) return TRUE;
    }

    return FALSE;
} // }}}

$response = new Response(); // start a new Twiml response

if($status == 'save-call' && @$_REQUEST['RecordingUrl']) {
	// add a voice message 
	OpenVBX::addVoiceMessage(
        AppletInstance::getUserGroupPickerValue('permissions'),
        $_REQUEST['CallSid'],
        $_REQUEST['Caller'],
        $_REQUEST['Called'], 
        $_REQUEST['RecordingUrl'],
        $_REQUEST['Duration']
    );		
} else if($status == 'transcribe-call') {
    $zendesk_user = $CI->db->get_where('plugin_store', array('key' => 'zendesk_user'))->row();
    $zendesk_user = json_decode($zendesk_user->value);

    define('ZENDESK_URL', $zendesk_user->url);
    define('ZENDESK_EMAIL', $zendesk_user->email);
    define('ZENDESK_PASSWORD', $zendesk_user->password);
    define('ZENDESK_TIMEZONE', int($zendesk_user->timezone));

    // create a ticket to zendesk
    $xml =
        '<ticket>'.
            '<subject>Phone Call from '.format_phone($_REQUEST['Caller']).' on '.gmdate('M d g:i a', gmmktime()+(ZENDESK_TIMEZONE*60)).'</subject>'.
            '<description>'.$_REQUEST['TranscriptionText']."\n".'Recording:'.$_REQUEST['RecordingUrl'].'</description>'.
        '</ticket>';
    $new_ticket = zendesk_client('/tickets.xml', 'POST', $xml);

    $params = http_build_query($_REQUEST);
    $redirect_url = site_url('twiml/transcribe').'?'.$params;
    header("Location: $redirect_url");
} else {
	$permissions = AppletInstance::getUserGroupPickerValue('permissions'); // get the prompt that the user configured
	$isUser = $permissions instanceOf VBX_User? TRUE : FALSE;

	if($isUser) $prompt = $permissions->voicemail;
	else $prompt = AppletInstance::getAudioSpeechPickerValue('prompt');

	$verb = AudioSpeechPickerWidget::getVerbForValue($prompt, new Say("Please leave a message."));
	$response->append($verb);

	// add a <Record>, and use VBX's default transcription handle$response->addRecord(array('transcribe'=>'TRUE', 'transcribeCallback' => site_url('/twiml/transcribe') ));
    $action_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=save-call";
	$transcribe_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=transcribe-call";
    $response->addRecord(array(
        'transcribe'=>'TRUE', 
        'action' => $action_url,
        'transcribeCallback' => $transcribe_url 
    ));
}

$response->Respond(); // send response
