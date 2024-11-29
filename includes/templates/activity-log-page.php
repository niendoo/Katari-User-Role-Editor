<?php
/**
 * Activity Log page template
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get current page number
$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$items_per_page = 20;

// Initialize logs
$logs = new Katari_Logs();

// Get total items for pagination
$total_items = $logs->get_total_logs();
$total_pages = ceil( $total_items / $items_per_page );

// Get logs with pagination
$activity_logs = $logs->get_logs( array(
    'number' => $items_per_page,
    'offset' => ( $page - 1 ) * $items_per_page,
) );

// Get action types for filtering
$action_types = $logs->get_action_types();
?>

<div class="wrap katari-wrap">
    <div class="katari-header">
        <h1><?php esc_html_e( 'Activity Log', 'katari-user-role-editor' ); ?></h1>
        <div class="katari-header-actions">
            <button class="katari-button katari-button-secondary" onclick="window.print()">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export Log', 'katari-user-role-editor' ); ?>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="katari-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="katari-activity-log">
            
            <!-- Action Type Filter -->
            <select name="action_type">
                <option value=""><?php esc_html_e( 'All Actions', 'katari-user-role-editor' ); ?></option>
                <?php foreach ( $action_types as $type ) : ?>
                    <option value="<?php echo esc_attr( $type ); ?>" <?php selected( isset( $_GET['action_type'] ) ? $_GET['action_type'] : '', $type ); ?>>
                        <?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Date Range Filter -->
            <input type="date" name="date_from" value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( $_GET['date_from'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'From Date', 'katari-user-role-editor' ); ?>">
            <input type="date" name="date_to" value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( $_GET['date_to'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'To Date', 'katari-user-role-editor' ); ?>">

            <!-- Search -->
            <input type="search" name="search" value="<?php echo isset( $_GET['search'] ) ? esc_attr( $_GET['search'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Search logs...', 'katari-user-role-editor' ); ?>">

            <button type="submit" class="katari-button"><?php esc_html_e( 'Apply Filters', 'katari-user-role-editor' ); ?></button>
        </form>
    </div>

    <!-- Activity Log Table -->
    <div class="katari-card">
        <div class="katari-activity-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'katari-user-role-editor' ); ?></th>
                        <th><?php esc_html_e( 'User', 'katari-user-role-editor' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'katari-user-role-editor' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'katari-user-role-editor' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $activity_logs ) ) : ?>
                        <?php foreach ( $activity_logs as $log ) : ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo esc_html( date_i18n( 
                                        get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), 
                                        strtotime( $log->created_at ) 
                                    ) ); 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $user = get_user_by( 'id', $log->user_id );
                                    if ( $user ) {
                                        echo esc_html( $user->display_name );
                                    } else {
                                        esc_html_e( 'Unknown User', 'katari-user-role-editor' );
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="katari-action-badge katari-action-<?php echo esc_attr( $log->action ); ?>">
                                        <?php echo esc_html( ucwords( str_replace( '_', ' ', $log->action ) ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $log->description ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="katari-no-records">
                                <?php esc_html_e( 'No activity logs found.', 'katari-user-role-editor' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="katari-pagination">
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => __( '&laquo; Previous', 'katari-user-role-editor' ),
                    'next_text' => __( 'Next &raquo;', 'katari-user-role-editor' ),
                    'total'     => $total_pages,
                    'current'   => $page,
                ) );
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
