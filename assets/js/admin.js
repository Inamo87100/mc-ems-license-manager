/**
 * MC EMS License Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // AJAX revoke via data-license attribute (legacy / programmatic use).
        $('.revoke-license').on('click', function(e) {
            e.preventDefault();

            var licenseKey = $(this).data('license');
            var i18n = (typeof mcEmsAdmin !== 'undefined') ? mcEmsAdmin.i18n : {};

            if (confirm(i18n.confirm_revoke || 'Are you sure you want to revoke this license?')) {
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
            }
        });

        // Status filter: auto-submit on change.
        $('#status_filter').on('change', function() {
            $(this).closest('form').submit();
        });

        // Auto-dismiss admin notices after 4 seconds.
        setTimeout(function() {
            $('.mc-ems-admin .notice.is-dismissible').fadeOut(500);
        }, 4000);

    });

})(jQuery);