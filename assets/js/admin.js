/**
 * MC EMS License Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        var admin = (typeof mcEmsAdmin !== 'undefined') ? mcEmsAdmin : {};
        var i18n  = admin.i18n || {};

        // AJAX revoke via data-license attribute (legacy / programmatic use).
        $('.revoke-license').on('click', function(e) {
            e.preventDefault();

            var licenseKey = $(this).data('license');

            if (confirm(i18n.confirm_revoke || 'Are you sure you want to revoke this license?')) {
                $.ajax({
                    url: admin.ajaxurl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mc_ems_revoke_license',
                        license_key: licenseKey,
                        nonce: admin.nonce || ''
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

        // ---------------------------------------------------------------
        // Product inline edit modal
        // ---------------------------------------------------------------

        var $modal = $('#mc-ems-edit-modal');

        // Open modal when "Edit" button is clicked on a product row.
        $(document).on('click', '.mc-ems-edit-product', function() {
            var productId = $(this).data('product-id');
            var duration  = $(this).data('duration');

            $('#edit_product_id').val(productId);
            $('#edit_duration_days').val(duration);

            $modal.css('display', 'flex');
        });

        // Close modal on backdrop click or Cancel button.
        $modal.on('click', '.mc-ems-edit-modal__backdrop, .mc-ems-edit-modal__cancel', function() {
            $modal.css('display', 'none');
        });

        // Close modal on Escape key.
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.css('display') !== 'none') {
                $modal.css('display', 'none');
            }
        });

        // Save product via AJAX when modal form is submitted.
        $('#mc-ems-edit-product-form').on('submit', function(e) {
            e.preventDefault();

            var productId    = $('#edit_product_id').val();
            var durationDays = $('#edit_duration_days').val();
            var $submitBtn   = $(this).find('[type="submit"]');

            $submitBtn.prop('disabled', true);

            $.ajax({
                url: admin.ajaxurl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'mc_ems_edit_product',
                    product_id: productId,
                    duration_days: durationDays,
                    nonce: admin.product_nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        $modal.css('display', 'none');
                        location.reload();
                    } else {
                        alert((response.data && response.data.message) ? response.data.message : (i18n.error || 'An error occurred. Please try again.'));
                    }
                },
                error: function() {
                    alert(i18n.error || 'An error occurred. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        });

        // Delete product via AJAX.
        $(document).on('click', '.mc-ems-delete-product', function(e) {
            e.preventDefault();

            if (!confirm(i18n.confirm_delete_product || 'Are you sure you want to remove this product association?')) {
                return;
            }

            var productId  = $(this).data('product-id');
            var $row       = $(this).closest('tr');
            var deleteUrl  = $(this).attr('href');

            $.ajax({
                url: admin.ajaxurl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'mc_ems_delete_product',
                    product_id: productId,
                    nonce: admin.product_nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert((response.data && response.data.message) ? response.data.message : (i18n.error || 'An error occurred. Please try again.'));
                        if (deleteUrl) {
                            window.location.href = deleteUrl;
                        }
                    }
                },
                error: function() {
                    if (deleteUrl) {
                        window.location.href = deleteUrl;
                    } else {
                        alert(i18n.error || 'An error occurred. Please try again.');
                    }
                }
            });
        });

    });

})(jQuery);
