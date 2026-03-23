jQuery(document).ready(function($) {
    // Revoca licenza
    $('.mc-ems-revoke-license').on('click', function() {
        if (!confirm(mcEmsAdmin ? mcEmsAdmin.i18n.confirm_revoke : 'Sei sicuro di voler revocare questa licenza?')) return;
        var licenseKey = $(this).data('license-key');
        var i18n = (typeof mcEmsAdmin !== 'undefined') ? mcEmsAdmin.i18n : {};
        $.ajax({
            url: (typeof mcEmsAdmin !== 'undefined') ? mcEmsAdmin.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'mc_ems_revoke_license',
                license_key: licenseKey,
                nonce: (typeof mcEmsAdmin !== 'undefined') ? mcEmsAdmin.nonce : ''
            },
            success: function(response) {
                if (response.success) {
                    alert(i18n.revoked || 'License revoked successfully!');
                    location.reload();
                } else {
                    alert(i18n.error || 'Failed to revoke license');
                }
            },
            error: function() {
                alert(i18n.error || 'Error occurred while revoking license');
            }
        });
    });

    // Status filter: auto-submit on change
    $('#status_filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Auto-dismiss admin notices after 4 seconds
    setTimeout
