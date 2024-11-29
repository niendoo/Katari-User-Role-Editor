jQuery(document).ready(function($) {
    // Handle capability toggle
    $('.katari-capability-toggle').on('change', function() {
        var $checkbox = $(this);
        var capability = $checkbox.data('capability');
        var role = $checkbox.data('role');
        var granted = $checkbox.is(':checked');

        // Show loading state
        $checkbox.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'katari_toggle_capability',
                nonce: katari_vars.nonce,
                capability: capability,
                role: role,
                granted: granted
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice('success', response.data.message);
                } else {
                    // Show error message and revert checkbox
                    showNotice('error', response.data.message);
                    $checkbox.prop('checked', !granted);
                }
            },
            error: function() {
                // Show error message and revert checkbox
                showNotice('error', 'An error occurred while updating the capability.');
                $checkbox.prop('checked', !granted);
            },
            complete: function() {
                // Re-enable checkbox
                $checkbox.prop('disabled', false);
            }
        });
    });

    // Handle role update form submission
    $('.katari-role-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');

        // Show loading state
        $submitButton.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'katari_update_role',
                nonce: katari_vars.nonce,
                role_data: $form.serialize()
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', 'An error occurred while updating the role.');
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });

    // Toggle capabilities section
    $('.katari-toggle-capabilities').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $capabilitiesRow = $button.closest('tr').next('.katari-capabilities-row');
        
        if ($capabilitiesRow.is(':visible')) {
            $capabilitiesRow.hide();
            $button.find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        } else {
            $capabilitiesRow.show();
            $button.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }
    });

    // Initialize Users per Role Chart
    function initUsersPerRoleChart() {
        const canvas = document.getElementById('usersPerRoleChart');
        if (!canvas || !window.katariChartData) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const chartData = {
            labels: katariChartData.labels,
            datasets: [{
                data: katariChartData.data,
                backgroundColor: katariChartData.colors,
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        };

        const chartConfig = {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} users (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };

        new Chart(ctx, chartConfig);
    }

    // Call chart initialization when document is ready
    initUsersPerRoleChart();

    // Helper function to show notices
    function showNotice(type, message) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
