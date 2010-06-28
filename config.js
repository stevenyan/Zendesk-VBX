if(typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/, ''); 
    }
}

var zendesk_app = {
    version: 1.0
}

var config_page = {
    initialize: function() {
        config_page.render();
    },

    submit_delete_creds: function() {
        $.post(
            base_url + 'config/' + plugin_dir + '?op=delete_credentials',
            function(resp) {
                resp = resp.match(/JSON_DATA\>(.*)\<\/JSON_DATA/)[1];
                $('input[name="zendesk_url"]').val('');
                $('input[name="zendesk_email"]').val('');
                $('input[name="zendesk_password"]').val('');
                $('a.delete_creds_btn').css('display', 'none');
                $('div.system_msg').html('').css('color', 'inherit');
            },
            'text'
        );
    },

    submit_save_creds: function() {
        var url_el = $('input[name="zendesk_url"]');
        var email_el = $('input[name="zendesk_email"]');
        var password_el = $('input[name="zendesk_password"]');

        $('span[class$="_err"]').empty();
        $('div.system_msg').empty().css('color', 'inherit');

        var errors = [];
        if(url_el.val().trim() == '') errors.push({ name:'zendesk_url', msg:'Zendesk URL is required.' });
        else if(!url_el.val().match(/https*:\/\/[a-z0-9]+\.zendesk\.com/)) errors.push({ name:'zendesk_url', msg:'Zendesk URL nees to be like https or http://yoursite.zendesk.com' });

        if(email_el.val().trim() == '') errors.push({ name:'zendesk_email', msg:'Token is required.' });
        if(password_el.val().trim() == '') errors.push({ name:'zendesk_password', msg:'Password is required.' });

        if(errors.length == 0) {
            var timezone = -(new Date()).getTimezoneOffset()/60;
            $('div.system_msg').html('<a class="ajax_loader"></a> Testing your credentials.');
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

                        if(resp.key == 'SUCCESS') $('a.delete_creds_btn').css('display', 'inline-block');
                    } catch(e) { sys_msg = 'Cannot validate your credentials due to an exception error.'; sys_msg_type = 'error'; }

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
    },

    render: function(name) {
        var that = config_page; 

        switch(name) {
            case 'zendesk_api_access':
                var section_el = $('#zendesk_api_access');

                $('#save_cred_btn', section_el).click(function() {
                    that.submit_save_creds();
                });

                $('a.delete_creds_btn', section_el).click(function() {
                    if(confirm('Are you sure you want to delete your Zendesk credentials?')) that.submit_delete_creds();
                });
                
                if($('input[name="zendesk_email"]').val() == '') $('a.delete_creds_btn').css('display', 'none');
                break;

            case undefined: 
                that.render('zendesk_api_access');
                break;
        }
    }
}

$(document).ready(function() {
    config_page.initialize(); 
});
