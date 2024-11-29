<?php
/**
 * Frontend class
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Frontend class
 */
class Katari_Frontend {
    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'katari_user_roles', array( $this, 'render_user_roles_shortcode' ) );
        add_shortcode( 'katari_user_capabilities', array( $this, 'render_user_capabilities_shortcode' ) );
    }

    /**
     * Render user roles shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_user_roles_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'show_capabilities' => 'no',
            ),
            $atts,
            'katari_user_roles'
        );

        $user = wp_get_current_user();
        $output = '<div class="katari-user-roles">';
        $output .= '<h3>' . esc_html__( 'Your Roles', 'katari-user-role-editor' ) . '</h3>';
        $output .= '<ul>';

        foreach ( $user->roles as $role ) {
            $role_obj = get_role( $role );
            $output .= '<li>' . esc_html( wp_roles()->role_names[ $role ] );

            if ( 'yes' === $atts['show_capabilities'] && ! empty( $role_obj->capabilities ) ) {
                $output .= '<ul class="katari-capabilities">';
                foreach ( $role_obj->capabilities as $cap => $granted ) {
                    if ( $granted ) {
                        $output .= '<li>' . esc_html( $cap ) . '</li>';
                    }
                }
                $output .= '</ul>';
            }

            $output .= '</li>';
        }

        $output .= '</ul></div>';

        return $output;
    }

    /**
     * Render user capabilities shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_user_capabilities_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'group_by_role' => 'no',
            ),
            $atts,
            'katari_user_capabilities'
        );

        $user = wp_get_current_user();
        $output = '<div class="katari-user-capabilities">';
        $output .= '<h3>' . esc_html__( 'Your Capabilities', 'katari-user-role-editor' ) . '</h3>';

        if ( 'yes' === $atts['group_by_role'] ) {
            foreach ( $user->roles as $role ) {
                $role_obj = get_role( $role );
                $output .= '<h4>' . esc_html( wp_roles()->role_names[ $role ] ) . '</h4>';
                $output .= '<ul>';
                
                if ( ! empty( $role_obj->capabilities ) ) {
                    foreach ( $role_obj->capabilities as $cap => $granted ) {
                        if ( $granted ) {
                            $output .= '<li>' . esc_html( $cap ) . '</li>';
                        }
                    }
                }
                
                $output .= '</ul>';
            }
        } else {
            $output .= '<ul>';
            $all_caps = $user->allcaps;
            ksort( $all_caps );
            
            foreach ( $all_caps as $cap => $granted ) {
                if ( $granted ) {
                    $output .= '<li>' . esc_html( $cap ) . '</li>';
                }
            }
            
            $output .= '</ul>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Check if user has specific capability.
     *
     * @param string $capability Capability to check.
     * @param int    $user_id User ID (optional).
     * @return bool
     */
    public function user_has_capability( $capability, $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return false;
        }

        $user = get_user_by( 'id', $user_id );
        return $user && $user->has_cap( $capability );
    }

    /**
     * Get all capabilities for a user.
     *
     * @param int $user_id User ID (optional).
     * @return array
     */
    public function get_user_capabilities( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return array();
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return array();
        }

        $capabilities = array();
        foreach ( $user->allcaps as $cap => $granted ) {
            if ( $granted ) {
                $capabilities[] = $cap;
            }
        }

        sort( $capabilities );
        return $capabilities;
    }

    /**
     * Get roles for a user.
     *
     * @param int $user_id User ID (optional).
     * @return array
     */
    public function get_user_roles( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return array();
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return array();
        }

        return array_map( function( $role ) {
            return array(
                'role' => $role,
                'name' => wp_roles()->role_names[ $role ],
            );
        }, $user->roles );
    }
}
