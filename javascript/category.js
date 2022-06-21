
jQueryExaport(function ($) {

    $('#sharing-userlist').html('loading userlist...');
    $('#sharing-grouplist').html('loading grouplist...');

    $('#structure_sharing-userlist').html('loading userlist...');
    $('#structure_sharing-grouplist').html('loading grouplist...');

    // Sharing.
    function update_sharing() {
        var share_text = '';
        var $form = $('#categoryform');

        if ($form.find(':input[name="internshare"]').is(':checked')) {
            $('#internaccess-settings').show();
            $('#internaccess-groups').hide();

            if ($form.find(':input[name=shareall]:checked').val() == 1) {
                $('#internaccess-users').hide();
                $('#internaccess-groups').hide();
            } else if ($form.find(':input[name=shareall]:checked').val() == 2) {
                $('#internaccess-users').hide();
                $('#internaccess-groups').show();
                ExabisEportfolio.load_grouplist('cat_mod');
            } else {
                $('#internaccess-groups').hide();
                $('#internaccess-users').show();
                ExabisEportfolio.load_userlist('cat_mod');
            }
        } else {
            $('#internaccess-settings').hide();
        }
    }

    $(function () {
        // Changing the checkboxes / radiobuttons update the sharing text, visible options, etc.
        $('#categoryform input[type="checkbox"], #categoryform input[type="radio"]').on('click', function () {
            update_sharing();
        });
        update_sharing();
    });
});
