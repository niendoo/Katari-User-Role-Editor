jQuery(document).ready(function($) {
    'use strict';

    // Role management functionality
    $('.katari-role-update').on('click', function(e) {
        e.preventDefault();
        var roleData = $(this).closest('form').serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'katari_update_role',
                nonce: katariAdmin.nonce,
                role_data: roleData
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while processing your request.');
            }
        });
    });

    // Import/Export functionality
    $('#katari-export-roles').on('click', function(e) {
        e.preventDefault();
        window.location.href = ajaxurl + '?action=katari_export_roles&nonce=' + katariAdmin.nonce;
    });

    // Show notification helper
    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'katari-success' : 'katari-error';
        var notice = $('<div class="katari-notice ' + noticeClass + '">' + message + '</div>');
        
        $('.katari-notices').html(notice);
        setTimeout(function() {
            notice.fadeOut();
        }, 3000);
    }

    // Capability management
    $('.katari-capability-toggle').on('change', function() {
        var capability = $(this).data('capability');
        var role = $(this).data('role');
        var granted = $(this).is(':checked');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'katari_toggle_capability',
                nonce: katariAdmin.nonce,
                capability: capability,
                role: role,
                granted: granted
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            }
        });
    });
});
