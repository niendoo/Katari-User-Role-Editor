<?php
/**
 * Analytics class
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Analytics class
 */
class Katari_Analytics {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'schedule_analytics' ) );
        add_action( 'katari_daily_analytics', array( $this, 'generate_daily_analytics' ) );
    }

    /**
     * Schedule analytics generation.
     */
    public function schedule_analytics() {
        if ( ! wp_next_scheduled( 'katari_daily_analytics' ) ) {
            wp_schedule_event( time(), 'daily', 'katari_daily_analytics' );
        }
    }

    /**
     * Generate daily analytics.
     */
    public function generate_daily_analytics() {
        $analytics = array(
            'roles_count'           => $this->get_roles_count(),
            'users_per_role'        => $this->get_users_per_role(),
            'capabilities_usage'    => $this->get_capabilities_usage(),
            'recent_role_changes'   => $this->get_recent_role_changes(),
            'generated_at'          => current_time( 'mysql' ),
        );

        update_option( 'katari_analytics', $analytics );
    }

    /**
     * Get total number of roles.
     *
     * @return int
     */
    public function get_roles_count() {
        return count( wp_roles()->roles );
    }

    /**
     * Get number of users per role.
     *
     * @return array
     */
    public function get_users_per_role() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $users_per_role = array();
        foreach ( $wp_roles->role_names as $role => $name ) {
            $users_per_role[ $role ] = count( get_users( array( 'role' => $role ) ) );
        }

        // Sort roles by user count in descending order
        arsort( $users_per_role );
        
        return $users_per_role;
    }

    /**
     * Get capabilities usage statistics.
     *
     * @return array
     */
    public function get_capabilities_usage() {
        $roles = wp_roles();
        $capabilities_usage = array();

        foreach ( $roles->roles as $role ) {
            if ( ! isset( $role['capabilities'] ) || ! is_array( $role['capabilities'] ) ) {
                continue;
            }

            foreach ( $role['capabilities'] as $cap => $granted ) {
                if ( ! isset( $capabilities_usage[ $cap ] ) ) {
                    $capabilities_usage[ $cap ] = 0;
                }
                if ( $granted ) {
                    $capabilities_usage[ $cap ]++;
                }
            }
        }

        arsort( $capabilities_usage );
        return $capabilities_usage;
    }

    /**
     * Get recent role changes from logs.
     *
     * @param int $limit Number of changes to retrieve.
     * @return array
     */
    public function get_recent_role_changes( $limit = 10 ) {
        $logs = new Katari_Logs();
        return $logs->get_logs( array(
            'number'  => $limit,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ) );
    }

    /**
     * Get analytics data.
     *
     * @return array
     */
    public function get_analytics() {
        $analytics = array(
            'roles_count' => $this->get_roles_count(),
            'users_per_role' => $this->get_users_per_role(),
            'capabilities_usage' => $this->get_capabilities_usage(),
            'recent_role_changes' => $this->get_recent_role_changes(),
            'generated_at' => current_time('mysql')
        );

        return $analytics;
    }

    /**
     * Get role usage trends.
     *
     * @param int $days Number of days to analyze.
     * @return array
     */
    public function get_role_usage_trends( $days = 30 ) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'katari_logs';
        $trends = array();

        $start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) as date, action, COUNT(*) as count
            FROM {$logs_table}
            WHERE created_at >= %s
            GROUP BY DATE(created_at), action
            ORDER BY date ASC",
            $start_date
        ) );

        foreach ( $results as $row ) {
            if ( ! isset( $trends[ $row->date ] ) ) {
                $trends[ $row->date ] = array();
            }
            $trends[ $row->date ][ $row->action ] = $row->count;
        }

        return $trends;
    }

    /**
     * Get most active users managing roles.
     *
     * @param int $limit Number of users to retrieve.
     * @return array
     */
    public function get_most_active_users( $limit = 5 ) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'katari_logs';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, COUNT(*) as action_count
            FROM {$logs_table}
            GROUP BY user_id
            ORDER BY action_count DESC
            LIMIT %d",
            $limit
        ) );

        $users = array();
        foreach ( $results as $row ) {
            $user = get_user_by( 'id', $row->user_id );
            if ( $user ) {
                $users[] = array(
                    'user_id'      => $row->user_id,
                    'display_name' => $user->display_name,
                    'action_count' => $row->action_count,
                );
            }
        }

        return $users;
    }
}
