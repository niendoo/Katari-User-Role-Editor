<?php
/**
 * Activity monitoring functionality
 *
 * @package Katari_User_Role_Editor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class Katari_Activity_Monitor {
    /**
     * Initialize the activity monitor
     */
    public function __construct() {
        // Post related actions
        add_action('transition_post_status', array($this, 'log_post_status_change'), 10, 3);
        add_action('delete_post', array($this, 'log_post_deletion'));
        add_action('post_updated', array($this, 'log_post_update'), 10, 3);
        add_action('add_post_meta', array($this, 'log_post_meta_addition'), 10, 3);
        add_action('update_post_meta', array($this, 'log_post_meta_update'), 10, 4);
        add_action('delete_post_meta', array($this, 'log_post_meta_deletion'), 10, 4);
        
        // Comment related actions
        add_action('wp_insert_comment', array($this, 'log_comment_creation'), 10, 2);
        add_action('edit_comment', array($this, 'log_comment_update'));
        add_action('delete_comment', array($this, 'log_comment_deletion'));
        add_action('spam_comment', array($this, 'log_comment_spam'));
        add_action('unspam_comment', array($this, 'log_comment_unspam'));
        add_action('trash_comment', array($this, 'log_comment_trash'));
        add_action('untrash_comment', array($this, 'log_comment_untrash'));
        
        // User related actions
        add_action('user_register', array($this, 'log_user_registration'));
        add_action('profile_update', array($this, 'log_profile_update'), 10, 2);
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('delete_user', array($this, 'log_user_deletion'));
        add_action('set_user_role', array($this, 'log_user_role_change'), 10, 3);
        add_action('add_user_meta', array($this, 'log_user_meta_addition'), 10, 3);
        add_action('update_user_meta', array($this, 'log_user_meta_update'), 10, 4);
        add_action('delete_user_meta', array($this, 'log_user_meta_deletion'), 10, 4);
        
        // Plugin/Theme actions
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
        add_action('switch_theme', array($this, 'log_theme_switch'), 10, 3);
        add_action('customize_save_after', array($this, 'log_theme_customization'));
        add_action('upgrader_process_complete', array($this, 'log_wordpress_updates'), 10, 2);
        
        // Attachment/Media actions
        add_action('add_attachment', array($this, 'log_media_upload'));
        add_action('edit_attachment', array($this, 'log_media_update'));
        add_action('delete_attachment', array($this, 'log_media_deletion'));
        add_action('wp_handle_upload', array($this, 'log_media_upload_complete'), 10, 2);
        add_filter('wp_generate_attachment_metadata', array($this, 'log_media_generate_metadata'), 10, 2);
        
        // Menu actions
        add_action('wp_update_nav_menu', array($this, 'log_menu_update'));
        add_action('wp_create_nav_menu', array($this, 'log_menu_creation'));
        add_action('wp_delete_nav_menu', array($this, 'log_menu_deletion'));
        
        // Widget actions
        add_action('update_option_sidebars_widgets', array($this, 'log_widget_update'), 10, 2);
        
        // Category/Term actions
        add_action('created_term', array($this, 'log_term_creation'), 10, 3);
        add_action('edited_term', array($this, 'log_term_update'), 10, 3);
        add_action('delete_term', array($this, 'log_term_deletion'), 10, 4);
        
        // Option changes
        add_action('updated_option', array($this, 'log_option_update'), 10, 3);
        add_action('added_option', array($this, 'log_option_addition'), 10, 2);
        add_action('deleted_option', array($this, 'log_option_deletion'));
        
        // Core WordPress updates
        add_action('_core_updated_successfully', array($this, 'log_wordpress_core_update'));
    }

    /**
     * Log post status changes
     */
    public function log_post_status_change($new_status, $old_status, $post) {
        if ($old_status === $new_status) {
            return;
        }

        $user = wp_get_current_user();
        $post_type = get_post_type_object($post->post_type)->labels->singular_name;

        if ($new_status === 'publish' && $old_status !== 'publish') {
            $description = sprintf(
                /* translators: 1: User display name, 2: Post type, 3: Post title */
                __('User %1$s published a new %2$s: "%3$s"', 'katari-user-role-editor'),
                $user->display_name,
                $post_type,
                $post->post_title
            );
        } elseif ($new_status === 'trash') {
            $description = sprintf(
                /* translators: 1: User display name, 2: Post type, 3: Post title */
                __('User %1$s moved %2$s "%3$s" to trash', 'katari-user-role-editor'),
                $user->display_name,
                $post_type,
                $post->post_title
            );
        } else {
            $description = sprintf(
                /* translators: 1: User display name, 2: Post type, 3: Post title, 4: Old status, 5: New status */
                __('User %1$s changed %2$s "%3$s" status from "%4$s" to "%5$s"', 'katari-user-role-editor'),
                $user->display_name,
                $post_type,
                $post->post_title,
                $old_status,
                $new_status
            );
        }

        $this->log_activity('post_status', $description);
    }

    /**
     * Log post deletion
     */
    public function log_post_deletion($post_id) {
        $post = get_post($post_id);
        if (!$post) return;

        $user = wp_get_current_user();
        $post_type = get_post_type_object($post->post_type)->labels->singular_name;

        $description = sprintf(
            /* translators: 1: User display name, 2: Post type, 3: Post title */
            __('User %1$s permanently deleted %2$s "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post_type,
            $post->post_title
        );

        $this->log_activity('post_deletion', $description);
    }

    /**
     * Log post update
     */
    public function log_post_update($post_ID, $post_after, $post_before) {
        $user = wp_get_current_user();
        $post_type = get_post_type_object($post_after->post_type)->labels->singular_name;

        $description = sprintf(
            /* translators: 1: User display name, 2: Post type, 3: Post title */
            __('User %1$s updated %2$s "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post_type,
            $post_after->post_title
        );

        $this->log_activity('post_update', $description);
    }

    /**
     * Log user login
     */
    public function log_user_login($user_login, $user) {
        $description = sprintf(
            /* translators: 1: User display name */
            __('User %1$s logged in', 'katari-user-role-editor'),
            $user->display_name
        );

        $this->log_activity('user_login', $description);
    }

    /**
     * Log user logout
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        if (!$user->exists()) return;

        $description = sprintf(
            /* translators: 1: User display name */
            __('User %1$s logged out', 'katari-user-role-editor'),
            $user->display_name
        );

        $this->log_activity('user_logout', $description);
    }

    /**
     * Log media upload
     */
    public function log_media_upload($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'attachment') {
            return;
        }

        $user = wp_get_current_user();
        $file_type = wp_check_filetype(get_attached_file($post_id))['ext'];
        $file_name = basename(get_attached_file($post_id));

        $description = sprintf(
            /* translators: 1: User display name, 2: File name, 3: File type */
            __('User %1$s uploaded a new file: "%2$s" (%3$s)', 'katari-user-role-editor'),
            $user->display_name,
            $file_name,
            strtoupper($file_type)
        );

        $this->log_activity('media_upload', $description);
    }

    /**
     * Log media upload completion
     */
    public function log_media_upload_complete($upload, $context) {
        $user = wp_get_current_user();
        $file_name = basename($upload['file']);
        $file_type = strtoupper($upload['type']);

        $description = sprintf(
            /* translators: 1: User display name, 2: File name, 3: File type */
            __('User %1$s completed uploading file: "%2$s" (%3$s)', 'katari-user-role-editor'),
            $user->display_name,
            $file_name,
            $file_type
        );

        $this->log_activity('media_upload_complete', $description);
        return $upload;
    }

    /**
     * Log media metadata generation
     */
    public function log_media_generate_metadata($metadata, $attachment_id) {
        if (!$metadata) {
            return $metadata;
        }

        $post = get_post($attachment_id);
        if (!$post || $post->post_type !== 'attachment') {
            return $metadata;
        }

        $user = wp_get_current_user();
        $file_name = basename(get_attached_file($attachment_id));

        // If it's an image, include dimensions
        if (isset($metadata['width']) && isset($metadata['height'])) {
            $description = sprintf(
                /* translators: 1: User display name, 2: File name, 3: Width, 4: Height */
                __('User %1$s uploaded image "%2$s" (Dimensions: %3$dx%4$dpx)', 'katari-user-role-editor'),
                $user->display_name,
                $file_name,
                $metadata['width'],
                $metadata['height']
            );
        } else {
            $description = sprintf(
                /* translators: 1: User display name, 2: File name */
                __('User %1$s processed media file "%2$s"', 'katari-user-role-editor'),
                $user->display_name,
                $file_name
            );
        }

        $this->log_activity('media_process', $description);
        return $metadata;
    }

    /**
     * Log media update
     */
    public function log_media_update($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'attachment') {
            return;
        }

        $user = wp_get_current_user();
        $file_name = basename(get_attached_file($post_id));

        $description = sprintf(
            /* translators: 1: User display name, 2: File name */
            __('User %1$s updated media file "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $file_name
        );

        $this->log_activity('media_update', $description);
    }

    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        $user = wp_get_current_user();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

        $description = sprintf(
            /* translators: 1: User display name, 2: Plugin name */
            __('User %1$s activated plugin: %2$s', 'katari-user-role-editor'),
            $user->display_name,
            $plugin_data['Name']
        );

        $this->log_activity('plugin_activation', $description);
    }

    /**
     * Log theme switch
     */
    public function log_theme_switch($new_name, $new_theme, $old_theme) {
        $user = wp_get_current_user();

        $description = sprintf(
            /* translators: 1: User display name, 2: Old theme name, 3: New theme name */
            __('User %1$s switched theme from "%2$s" to "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $old_theme->get('Name'),
            $new_name
        );

        $this->log_activity('theme_switch', $description);
    }

    /**
     * Log term creation
     */
    public function log_term_creation($term_id, $tt_id, $taxonomy) {
        $user = wp_get_current_user();
        $term = get_term($term_id, $taxonomy);
        $tax_object = get_taxonomy($taxonomy);

        $description = sprintf(
            /* translators: 1: User display name, 2: Taxonomy name, 3: Term name */
            __('User %1$s created new %2$s: "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $tax_object->labels->singular_name,
            $term->name
        );

        $this->log_activity('term_creation', $description);
    }

    /**
     * Log user role change
     */
    public function log_user_role_change($user_id, $role, $old_roles) {
        $user = get_userdata($user_id);
        $current_user = wp_get_current_user();

        $description = sprintf(
            /* translators: 1: Admin user name, 2: Target user name, 3: New role */
            __('User %1$s changed role of %2$s to "%3$s"', 'katari-user-role-editor'),
            $current_user->display_name,
            $user->display_name,
            $role
        );

        $this->log_activity('user_role_change', $description);
    }

    /**
     * Log theme customization
     */
    public function log_theme_customization($wp_customize) {
        $user = wp_get_current_user();

        $description = sprintf(
            /* translators: 1: User display name */
            __('User %1$s modified theme customization settings', 'katari-user-role-editor'),
            $user->display_name
        );

        $this->log_activity('theme_customization', $description);
    }

    /**
     * Log WordPress updates
     */
    public function log_wordpress_updates($upgrader, $options) {
        if (!isset($options['action'])) {
            return;
        }

        $user = wp_get_current_user();
        $description = '';

        switch ($options['action']) {
            case 'update':
                if (isset($options['type'])) {
                    switch ($options['type']) {
                        case 'plugin':
                            $description = sprintf(
                                /* translators: 1: User display name */
                                __('User %1$s updated plugins', 'katari-user-role-editor'),
                                $user->display_name
                            );
                            break;
                        case 'theme':
                            $description = sprintf(
                                /* translators: 1: User display name */
                                __('User %1$s updated themes', 'katari-user-role-editor'),
                                $user->display_name
                            );
                            break;
                    }
                }
                break;
        }

        if ($description) {
            $this->log_activity('wordpress_update', $description);
        }
    }

    /**
     * Log option changes
     */
    public function log_option_update($option_name, $old_value, $new_value) {
        // Skip some common and noisy options
        $skip_options = array('_site_transient_', '_transient_', 'cron', 'session_tokens');
        foreach ($skip_options as $skip) {
            if (strpos($option_name, $skip) !== false) {
                return;
            }
        }

        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Option name */
            __('User %1$s updated option: %2$s', 'katari-user-role-editor'),
            $user->display_name,
            $option_name
        );

        $this->log_activity('option_update', $description);
    }

    /**
     * Log WordPress core updates
     */
    public function log_wordpress_core_update($wp_version) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: WordPress version */
            __('User %1$s updated WordPress core to version %2$s', 'katari-user-role-editor'),
            $user->display_name,
            $wp_version
        );

        $this->log_activity('core_update', $description);
    }

    /**
     * Log activity to database
     */
    private function log_activity($action, $description) {
        global $wpdb;
        
        $data = array(
            'user_id' => get_current_user_id(),
            'action' => $action,
            'description' => $description,
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert("{$wpdb->prefix}katari_logs", $data);
    }
}
