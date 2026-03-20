/**
 * MC EMS License Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Revoke license button handler
        $('.revoke-license').on('click', function(e) {
            e.preventDefault();
            
            var licenseKey = $(this).data('license');
            
            if (confirm('Are you sure you want to revoke this license?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'revoke_license',
                        license_key: licenseKey,
                        nonce: mcEmsNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('License revoked successfully!');
                            location.reload();
                        } else {
                            alert('Failed to revoke license');
                        }
                    },
                    error: function() {
                        alert('Error occurred while revoking license');
                    }
                });
            }
        });
    });

})(jQuery);