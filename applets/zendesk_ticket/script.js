$(document).ready(function() {
    function submitTestZendeskCredForm(app) {
        var url_el = $('input[name="zendesk_url"]', app);
        var email_el = $('input[name="zendesk_email"]', app);
        var password_el = $('input[name="zendesk_password"]', app);

        $('span[class$="_err"]').empty();
        $('div.system_msg').empty().css('color', 'inherit');

        var errors = [];
        if(url_el.val().trim() == '') errors.push({ name:'zendesk_url', msg:'Zendesk URL is required.' });
        else if(!url_el.val().match(/https*:\/\/[a-z0-9]+\.zendesk\.com/)) errors.push({ name:'zendesk_url', msg:'Zendesk URL nees to be like http://yoursite.zendesk.com' });

        if(email_el.val().trim() == '') errors.push({ name:'zendesk_email', msg:'Email is required.' });
        if(password_el.val().trim() == '') errors.push({ name:'zendesk_password', msg:'Password is required.' });

        if(errors.length == 0) {
            $('div.system_msg').html('<a class="ajax_loader"></a> Testing your credentials.');
            $.post(
                base_url + 'config/Zendesk-VBX?op=test_credentials',
                { url:url_el.val(), email:email_el.val(), password:password_el.val() },
                function(resp) {
                    try {
                        resp = resp.match(/JSON_DATA\>(.*)\<\/JSON_DATA/)[1];
                        resp = eval("(" + resp + ")");
                        var sys_msg = '';
                        var sys_msg_type = 'error';
                        sys_msg = resp.msg;
                        sys_msg_type = resp.type;
                    } catch(e) { sys_msg = 'Cannot validate your credentials due to an exception error.'; }

                    $('div.system_msg').html(sys_msg).css('color', sys_msg_type == 'error' ? 'red' : 'green');
                },
                'text'
            );
        } else {
            $('div.system_msg').html('Cannot test credentials because of form validation errors.').css('color', 'red');
            $.each(errors, function(k, v) {
                if(v.name == 'zendesk_url') $('span.zendesk_url_err').text(v.msg);
                else if(v.name == 'zendesk_email') $('span.zendesk_email_err').text(v.msg);
                else if(v.name == 'zendesk_password') $('span.zendesk_password_err').text(v.msg);
            });
        }
    }

    $('#zendesk-test-creds-submit').live('click', function(e) {
        var instance = $(this).parent().parent().parent();
        submitTestZendeskCredForm(instance);
        e.preventDefault();
    });
});
