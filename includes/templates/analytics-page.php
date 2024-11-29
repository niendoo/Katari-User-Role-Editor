<?php
/**
 * Analytics page template
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$analytics = new Katari_Analytics();
$data = $analytics->get_analytics();
$trends = $analytics->get_role_usage_trends();
$active_users = $analytics->get_most_active_users();
?>

<div class="wrap katari-wrap">
    <div class="katari-header">
        <h1><?php esc_html_e( 'Role Analytics Dashboard', 'katari-user-role-editor' ); ?></h1>
        <div class="katari-header-actions">
            <button class="katari-button katari-button-secondary" onclick="window.print()">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export Report', 'katari-user-role-editor' ); ?>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="katari-stats-grid">
        <div class="katari-stat-card">
            <div class="katari-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="katari-stat-content">
                <h3><?php esc_html_e( 'Total Roles', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-stat-number"><?php echo esc_html( $data['roles_count'] ); ?></div>
                <div class="katari-stat-trend katari-trend-up">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    <span>5% from last month</span>
                </div>
            </div>
        </div>

        <div class="katari-stat-card">
            <div class="katari-stat-icon katari-icon-purple">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="katari-stat-content">
                <h3><?php esc_html_e( 'Total Users', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-stat-number">
                    <?php echo esc_html( array_sum($data['users_per_role']) ); ?>
                </div>
                <div class="katari-stat-trend katari-trend-up">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    <span>12% from last month</span>
                </div>
            </div>
        </div>

        <div class="katari-stat-card">
            <div class="katari-stat-icon katari-icon-orange">
                <span class="dashicons dashicons-shield"></span>
            </div>
            <div class="katari-stat-content">
                <h3><?php esc_html_e( 'Capabilities', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-stat-number">
                    <?php echo esc_html( count($data['capabilities_usage']) ); ?>
                </div>
                <div class="katari-stat-trend">
                    <span>Active capabilities</span>
                </div>
            </div>
        </div>

        <div class="katari-stat-card">
            <div class="katari-stat-icon katari-icon-green">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="katari-stat-content">
                <h3><?php esc_html_e( 'Changes Today', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-stat-number">
                    <?php 
                    $today_changes = array_filter($data['recent_role_changes'], function($change) {
                        return date('Y-m-d') === date('Y-m-d', strtotime($change->created_at));
                    });
                    echo count($today_changes);
                    ?>
                </div>
                <div class="katari-stat-trend">
                    <span>Role modifications</span>
                </div>
            </div>
        </div>
    </div>

    <div class="katari-analytics-grid">
        <!-- Users per Role Chart -->
        <div class="katari-card katari-chart-card">
            <div class="katari-card-header">
                <h3><?php esc_html_e( 'Users per Role Distribution', 'katari-user-role-editor' ); ?></h3>
            </div>
            <div class="katari-chart-container">
                <canvas id="usersPerRoleChart"></canvas>
            </div>
            <div class="katari-chart-legend">
                <?php 
                $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
                $roles = array_keys($data['users_per_role']);
                foreach ($roles as $index => $role) :
                    $color = $colors[$index % count($colors)];
                ?>
                    <div class="katari-legend-item">
                        <div class="katari-legend-color" style="background-color: <?php echo esc_attr($color); ?>"></div>
                        <div class="katari-legend-label"><?php echo esc_html(wp_roles()->role_names[$role]); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Most Used Capabilities -->
        <div class="katari-card">
            <div class="katari-card-header">
                <h3><?php esc_html_e( 'Top Capabilities', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-card-actions">
                    <button class="katari-button-icon" data-tooltip="View All">
                        <span class="dashicons dashicons-ellipsis"></span>
                    </button>
                </div>
            </div>
            <div class="katari-card-content">
                <div class="katari-capabilities-list">
                    <?php
                    $top_capabilities = array_slice( $data['capabilities_usage'], 0, 5, true );
                    foreach ( $top_capabilities as $cap => $count ) :
                        $percentage = ($count / $data['roles_count']) * 100;
                    ?>
                        <div class="katari-capability-item">
                            <div class="katari-capability-info">
                                <span class="katari-capability-name"><?php echo esc_html( $cap ); ?></span>
                                <span class="katari-capability-count"><?php echo esc_html( $count ); ?> roles</span>
                            </div>
                            <div class="katari-capability-bar">
                                <div class="katari-capability-progress" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="katari-card">
            <div class="katari-card-header">
                <h3><?php esc_html_e( 'Recent Activity', 'katari-user-role-editor' ); ?></h3>
                <div class="katari-card-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=katari-activity-log' ) ); ?>" class="katari-button-secondary">
                        <?php esc_html_e( 'View All', 'katari-user-role-editor' ); ?>
                    </a>
                </div>
            </div>
            <div class="katari-card-content">
                <div class="katari-activity-list">
                    <?php 
                    $recent_changes = array_slice($data['recent_role_changes'], 0, 5);
                    if (!empty($recent_changes)) : 
                    ?>
                        <?php foreach ( $recent_changes as $change ) : ?>
                            <div class="katari-activity-item">
                                <div class="katari-activity-icon">
                                    <?php
                                    $icon_class = 'dashicons-';
                                    $badge_class = '';
                                    switch ($change->action) {
                                        case 'role_update':
                                            $icon_class .= 'edit';
                                            $badge_class = 'katari-badge-info';
                                            break;
                                        case 'role_creation':
                                            $icon_class .= 'plus-alt';
                                            $badge_class = 'katari-badge-success';
                                            break;
                                        case 'role_deletion':
                                            $icon_class .= 'trash';
                                            $badge_class = 'katari-badge-error';
                                            break;
                                        case 'capability_toggle':
                                            $icon_class .= 'admin-generic';
                                            $badge_class = 'katari-badge-warning';
                                            break;
                                        default:
                                            $icon_class .= 'admin-generic';
                                            $badge_class = 'katari-badge-default';
                                    }
                                    ?>
                                    <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                </div>
                                <div class="katari-activity-content">
                                    <div class="katari-activity-meta">
                                        <span class="katari-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $change->action))); ?>
                                        </span>
                                        <span class="katari-activity-time">
                                            <?php echo esc_html(human_time_diff(strtotime($change->created_at), current_time('timestamp')) . ' ago'); ?>
                                        </span>
                                    </div>
                                    <div class="katari-activity-description">
                                        <?php echo esc_html($change->description); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="katari-no-activity">
                            <p><?php esc_html_e('No recent activity found.', 'katari-user-role-editor'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Most Active Users -->
        <div class="katari-card">
            <div class="katari-card-header">
                <h3><?php esc_html_e( 'Most Active Users', 'katari-user-role-editor' ); ?></h3>
            </div>
            <div class="katari-card-content">
                <div class="katari-users-list">
                    <?php foreach ( $active_users as $index => $user ) : ?>
                        <div class="katari-user-item">
                            <div class="katari-user-rank"><?php echo esc_html( $index + 1 ); ?></div>
                            <div class="katari-user-avatar">
                                <?php echo get_avatar( $user['user_id'], 40 ); ?>
                            </div>
                            <div class="katari-user-info">
                                <div class="katari-user-name"><?php echo esc_html( $user['display_name'] ); ?></div>
                                <div class="katari-user-actions">
                                    <?php echo esc_html( sprintf( _n( '%s action', '%s actions', $user['action_count'], 'katari-user-role-editor' ), number_format_i18n( $user['action_count'] ) ) ); ?>
                                </div>
                            </div>
                            <div class="katari-user-trend">
                                <span class="dashicons dashicons-arrow-up-alt"></span>
                                <span>12%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare chart data for JavaScript
$chart_data = array(
    'labels' => array_map(function($role) {
        return wp_roles()->role_names[$role];
    }, array_keys($data['users_per_role'])),
    'data' => array_values($data['users_per_role']),
    'colors' => array(
        '#4e73df',
        '#1cc88a',
        '#36b9cc',
        '#f6c23e',
        '#e74a3b',
        '#858796'
    )
);
wp_localize_script('katari-admin-script', 'katariChartData', $chart_data);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
