if(typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/, ''); 
    }
}

$(document).ready(function() {
    function submitTestZendeskCredForm(app) {
        var url_el = $('input[name="zendesk_url"]', app);
        var email_el = $('input[name="zendesk_email"]', app);
        var password_el = $('input[name="zendesk_password"]', app);

        $('span[class$="_err"]').empty();
        $('div.system_msg', app).empty().css('color', 'inherit');

        var errors = [];
        if(url_el.val().trim() == '') errors.push({ name:'zendesk_url', msg:'Zendesk URL is required.' });
        else if(!url_el.val().match(/https*:\/\/[a-z0-9]+\.zendesk\.com/)) errors.push({ name:'zendesk_url', msg:'Zendesk URL nees to be like http or https://yoursite.zendesk.com' });

        if(email_el.val().trim() == '') errors.push({ name:'zendesk_email', msg:'Email is required.' });
        if(password_el.val().trim() == '') errors.push({ name:'zendesk_password', msg:'Password is required.' });

        if(errors.length == 0) {
            var timezone = -(new Date()).getTimezoneOffset()/60;
            $('div.system_msg', app).html('<a class="ajax_loader"></a> Testing your credentials.');
            $.post(
                base_url + 'config/' + plugin_dir + '?op=test_credentials',
                { url:url_el.val(), email:email_el.val(), password:password_el.val(), timezone:timezone },
                function(resp) {
                    try {
                        resp = resp.match(/JSON_DATA\>(.*)\<\/JSON_DATA/)[1];
                        resp = eval("(" + resp + ")");
                        var sys_msg = '';
                        var sys_msg_type = 'error';
                        sys_msg = resp.msg;
                        sys_msg_type = resp.type;
                    } catch(e) { sys_msg = 'Cannot validate your credentials due to an exception error.'; sys_msg_type = 'error';  }

                    $('div.system_msg', app).html(sys_msg).css('color', sys_msg_type == 'error' ? 'red' : 'green');
                },
                'text'
            );
        } else {
            $('div.system_msg', app).html('Cannot test credentials because of form validation errors.').css('color', 'red');
            $.each(errors, function(k, v) {
                if(v.name == 'zendesk_url') $('span.zendesk_url_err', app).text(v.msg);
                else if(v.name == 'zendesk_email') $('span.zendesk_email_err', app).text(v.msg);
                else if(v.name == 'zendesk_password') $('span.zendesk_password_err', app).text(v.msg);
            });
        }
    }

    $('button.zendesk_test_creds_btn').live('click', function(e) {
        var instance = $(this).parent().parent().parent();
        submitTestZendeskCredForm(instance);
        e.preventDefault();
    });

    // detect when voicemail applet user or group is chosen
    $(".zendesk_ticket_applet .usergroup-container").live('usergroup-selected', function(e, usergroup_label, type) {
    	// If a group was set, then we need the user to manually configure the prompt
    	$('.prompt-for-group', $(e.target).parent())[ type == 'group' ? 'show' : 'hide' ]();

		// If an invidual was set, then we just use whatever VM prompt has been configured for that person
		$('.prompt-for-individual', $(e.target).parent())[ type == 'user' ? 'show' : 'hide' ]();
    });
});
