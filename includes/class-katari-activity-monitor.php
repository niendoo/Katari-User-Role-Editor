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
        add_action('post_updated', array($this, 'log_post_update'), 10, 3);
        
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
        
        // Plugin/Theme actions
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
        add_action('switch_theme', array($this, 'log_theme_switch'), 10, 3);
        add_action('customize_save_after', array($this, 'log_theme_customization'));
        add_action('upgrader_process_complete', array($this, 'log_wordpress_updates'), 10, 2);
        
        // Attachment/Media actions
        add_action('add_attachment', array($this, 'log_media_upload'));
        add_action('edit_attachment', array($this, 'log_media_update'));
        add_action('delete_attachment', array($this, 'log_media_deletion'), 1);
        add_action('wp_ajax_trash-post', array($this, 'intercept_media_trash'), 1);
        add_action('wp_ajax_delete-post', array($this, 'intercept_media_permanent_delete'), 1);
        
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

        // Role management actions
        add_action('katari_role_cloned', array($this, 'log_role_clone'), 10, 2);
    }

    /**
     * Log post status changes
     */
    public function log_post_status_change($new_status, $old_status, $post) {
        if ($old_status === $new_status) {
            return;
        }

        // Skip if this is a new post being created
        if ($old_status === 'new') {
            return;
        }

        $user = wp_get_current_user();
        $post_type = get_post_type_object($post->post_type)->labels->singular_name;

        if ($new_status === 'trash') {
            $description = sprintf(
                /* translators: 1: User display name, 2: Post type, 3: Post title */
                __('User %1$s moved %2$s "%3$s" to trash', 'katari-user-role-editor'),
                $user->display_name,
                $post_type,
                $post->post_title
            );
            $this->log_activity('post_trash', $description);
            return;
        }

        if ($new_status === 'publish' && $old_status !== 'publish') {
            $description = sprintf(
                /* translators: 1: User display name, 2: Post type, 3: Post title */
                __('User %1$s published %2$s "%3$s"', 'katari-user-role-editor'),
                $user->display_name,
                $post_type,
                $post->post_title
            );
            $this->log_activity('post_publish', $description);
            return;
        }

        // For other status changes
        $description = sprintf(
            /* translators: 1: User display name, 2: Post type, 3: Post title, 4: Old status, 5: New status */
            __('User %1$s changed %2$s "%3$s" status from "%4$s" to "%5$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post_type,
            $post->post_title,
            $old_status,
            $new_status
        );
        $this->log_activity('post_status_change', $description);
    }

    /**
     * Log post update
     */
    public function log_post_update($post_ID, $post_after, $post_before) {
        // Skip logging if the post is being trashed or if status is changing
        if ($post_after->post_status !== $post_before->post_status) {
            return;
        }

        // Skip if only the modified date was updated
        if ($post_after->post_modified !== $post_before->post_modified 
            && $post_after->post_content === $post_before->post_content
            && $post_after->post_title === $post_before->post_title
            && $post_after->post_excerpt === $post_before->post_excerpt) {
            return;
        }

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
     * Log media deletion
     * 
     * @param int $post_id Post ID
     */
    public function log_media_deletion($post_id) {
        try {
            $post = get_post($post_id);
            
            // Only proceed if this is an attachment
            if (!$post || $post->post_type !== 'attachment') {
                return;
            }

            $user = wp_get_current_user();
            
            // Try to get filename from different sources
            $file_name = '';
            
            // First try to get from attachment metadata
            $metadata = wp_get_attachment_metadata($post_id);
            if (!empty($metadata['file'])) {
                $file_name = basename($metadata['file']);
                error_log('Found filename from metadata: ' . $file_name);
            }
            
            // If that fails, try to get from _wp_attached_file meta
            if (empty($file_name)) {
                $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
                if (!empty($attached_file)) {
                    $file_name = basename($attached_file);
                    error_log('Found filename from _wp_attached_file: ' . $file_name);
                }
            }
            
            // If that fails, try to get from post title
            if (empty($file_name)) {
                $file_name = $post->post_title;
                error_log('Using post title as filename: ' . $file_name);
            }
            
            // Add a fallback if we still don't have a name
            if (empty($file_name)) {
                $file_name = sprintf(__('Media #%d', 'katari-user-role-editor'), $post_id);
                error_log('Using fallback filename: ' . $file_name);
            }
            
            // Add a fallback if we still don't have a name
            if (empty($file_name)) {
                $file_name = sprintf(__('Media #%d', 'katari-user-role-editor'), $post_id);
                error_log('Using fallback filename: ' . $file_name);
            }

            error_log('Final filename used: ' . $file_name);
            error_log('Post data: ' . print_r($post, true));
            error_log('Metadata: ' . print_r($metadata, true));

            $description = sprintf(
                /* translators: 1: User display name, 2: File name */
                __('User %1$s permanently deleted media file "%2$s"', 'katari-user-role-editor'),
                $user->display_name,
                $file_name
            );

            $this->log_activity('media_deletion', $description);
        } catch (Exception $e) {
            error_log('Katari User Role Editor - Error in media deletion logging: ' . $e->getMessage());
        }
    }

    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        $user = wp_get_current_user();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;

        $description = sprintf(
            /* translators: 1: User display name, 2: Plugin name */
            __('User %1$s activated plugin "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $plugin_name
        );

        $this->log_activity('plugin_activation', $description);
    }

    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin) {
        $user = wp_get_current_user();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;

        $description = sprintf(
            /* translators: 1: User display name, 2: Plugin name */
            __('User %1$s deactivated plugin "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $plugin_name
        );

        $this->log_activity('plugin_deactivation', $description);
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
     * Log option addition
     */
    public function log_option_addition($option_name, $value) {
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
            __('User %1$s added new option: %2$s', 'katari-user-role-editor'),
            $user->display_name,
            $option_name
        );

        $this->log_activity('option_addition', $description);
    }

    /**
     * Log option deletion
     */
    public function log_option_deletion($option_name) {
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
            __('User %1$s deleted option: %2$s', 'katari-user-role-editor'),
            $user->display_name,
            $option_name
        );

        $this->log_activity('option_deletion', $description);
    }

    /**
     * Log widget update
     */
    public function log_widget_update($old_value, $new_value) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name */
            __('User %1$s updated widget settings', 'katari-user-role-editor'),
            $user->display_name
        );

        $this->log_activity('widget_update', $description);
    }

    /**
     * Log term update
     */
    public function log_term_update($term_id, $tt_id, $taxonomy) {
        $user = wp_get_current_user();
        $term = get_term($term_id, $taxonomy);
        $tax_object = get_taxonomy($taxonomy);

        if (!$term || is_wp_error($term)) {
            return;
        }

        $description = sprintf(
            /* translators: 1: User display name, 2: Taxonomy name, 3: Term name */
            __('User %1$s updated %2$s: "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $tax_object->labels->singular_name,
            $term->name
        );

        $this->log_activity('term_update', $description);
    }

    /**
     * Log term deletion
     */
    public function log_term_deletion($term_id, $tt_id, $taxonomy, $deleted_term) {
        $user = wp_get_current_user();
        $tax_object = get_taxonomy($taxonomy);

        if (!$deleted_term || is_wp_error($deleted_term)) {
            return;
        }

        $description = sprintf(
            /* translators: 1: User display name, 2: Taxonomy name, 3: Term name */
            __('User %1$s deleted %2$s: "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $tax_object->labels->singular_name,
            $deleted_term->name
        );

        $this->log_activity('term_deletion', $description);
    }

    /**
     * Log comment update
     */
    public function log_comment_update($comment_ID) {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }

        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s edited a comment on "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post ? $post->post_title : __('(no title)', 'katari-user-role-editor')
        );

        $this->log_activity('comment_update', $description);
    }

    /**
     * Log comment spam
     */
    public function log_comment_spam($comment_ID) {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }

        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s marked a comment as spam on "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post ? $post->post_title : __('(no title)', 'katari-user-role-editor')
        );

        $this->log_activity('comment_spam', $description);
    }

    /**
     * Log comment unspam
     */
    public function log_comment_unspam($comment_ID) {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }

        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s unmarked a comment as spam on "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post ? $post->post_title : __('(no title)', 'katari-user-role-editor')
        );

        $this->log_activity('comment_unspam', $description);
    }

    /**
     * Log comment trash
     */
    public function log_comment_trash($comment_ID) {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }

        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s trashed a comment on "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post ? $post->post_title : __('(no title)', 'katari-user-role-editor')
        );

        $this->log_activity('comment_trash', $description);
    }

    /**
     * Log comment untrash
     */
    public function log_comment_untrash($comment_ID) {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }

        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s restored a comment on "%2$s"', 'katari-user-role-editor'),
            $user->display_name,
            $post ? $post->post_title : __('(no title)', 'katari-user-role-editor')
        );

        $this->log_activity('comment_untrash', $description);
    }

    /**
     * Log user meta addition
     */
    public function log_user_meta_addition($meta_id, $user_id, $meta_key) {
        // Skip logging for internal WordPress user settings
        if (strpos($meta_key, 'wp_user-settings') === 0) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) return;

        $current_user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Target user display name */
            __('User %1$s added user meta "%2$s" for user %3$s', 'katari-user-role-editor'),
            $current_user->display_name,
            $meta_key,
            $user->display_name
        );

        $this->log_activity('user_meta_addition', $description);
    }

    /**
     * Log user meta update
     */
    public function log_user_meta_update($meta_id, $user_id, $meta_key, $meta_value) {
        // Skip logging for internal WordPress user settings
        if (strpos($meta_key, 'wp_user-settings') === 0) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) return;

        $current_user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Target user display name */
            __('User %1$s updated user meta "%2$s" for user %3$s', 'katari-user-role-editor'),
            $current_user->display_name,
            $meta_key,
            $user->display_name
        );

        $this->log_activity('user_meta_update', $description);
    }

    /**
     * Log user meta deletion
     */
    public function log_user_meta_deletion($meta_ids, $user_id, $meta_key, $meta_value) {
        // Skip logging for internal WordPress user settings
        if (strpos($meta_key, 'wp_user-settings') === 0) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) return;

        $current_user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Target user display name */
            __('User %1$s deleted user meta "%2$s" for user %3$s', 'katari-user-role-editor'),
            $current_user->display_name,
            $meta_key,
            $user->display_name
        );

        $this->log_activity('user_meta_deletion', $description);
    }

    /**
     * Log post meta addition
     */
    public function log_post_meta_addition($meta_id, $post_id, $meta_key) {
        // Skip logging for internal WordPress meta
        if (strpos($meta_key, '_wp_') === 0) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) return;

        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Post title */
            __('User %1$s added post meta "%2$s" for post "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $meta_key,
            $post->post_title
        );

        $this->log_activity('post_meta_addition', $description);
    }

    /**
     * Log post meta update
     */
    public function log_post_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        // Skip logging for internal WordPress meta
        if (strpos($meta_key, '_wp_') === 0) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) return;

        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Post title */
            __('User %1$s updated post meta "%2$s" for post "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $meta_key,
            $post->post_title
        );

        $this->log_activity('post_meta_update', $description);
    }

    /**
     * Log post meta deletion
     */
    public function log_post_meta_deletion($meta_ids, $post_id, $meta_key, $meta_value) {
        // Skip logging for internal WordPress meta
        if (strpos($meta_key, '_wp_') === 0) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) return;

        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Meta key, 3: Post title */
            __('User %1$s deleted post meta "%2$s" from post "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $meta_key,
            $post->post_title
        );

        $this->log_activity('post_meta_deletion', $description);
    }

    /**
     * Intercept media deletion AJAX request
     */
    public function intercept_media_trash() {
        if (!isset($_POST['id']) || !isset($_POST['type'])) {
            return;
        }

        $post_id = intval($_POST['id']);
        try {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'attachment') {
                return;
            }

            $user = wp_get_current_user();
            
            // Try to get filename from different sources
            $file_name = '';
            
            // First try to get from attachment metadata
            $metadata = wp_get_attachment_metadata($post_id);
            if (!empty($metadata['file'])) {
                $file_name = basename($metadata['file']);
                error_log('Found filename from metadata: ' . $file_name);
            }
            
            // If that fails, try to get from _wp_attached_file meta
            if (empty($file_name)) {
                $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
                if (!empty($attached_file)) {
                    $file_name = basename($attached_file);
                    error_log('Found filename from _wp_attached_file: ' . $file_name);
                }
            }
            
            // If that fails, try to get from post title
            if (empty($file_name)) {
                $file_name = $post->post_title;
                error_log('Using post title as filename: ' . $file_name);
            }
            
            // Add a fallback if we still don't have a name
            if (empty($file_name)) {
                $file_name = sprintf(__('Media #%d', 'katari-user-role-editor'), $post_id);
                error_log('Using fallback filename: ' . $file_name);
            }

            error_log('Final filename used: ' . $file_name);
            error_log('Post data: ' . print_r($post, true));
            error_log('Metadata: ' . print_r($metadata, true));

            $description = sprintf(
                /* translators: 1: User display name, 2: File name */
                __('User %1$s moved media file "%2$s" to trash', 'katari-user-role-editor'),
                $user->display_name,
                $file_name
            );

            $this->log_activity('media_trash', $description);
        } catch (Exception $e) {
            error_log('Katari User Role Editor - Error in media trash interception: ' . $e->getMessage());
        }
    }

    /**
     * Intercept media permanent deletion request
     */
    public function intercept_media_permanent_delete() {
        if (!isset($_POST['id']) || !isset($_POST['type'])) {
            return;
        }

        $post_id = intval($_POST['id']);
        try {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'attachment') {
                return;
            }

            $user = wp_get_current_user();
            
            // Try to get filename from different sources
            $file_name = '';
            
            // First try to get from attachment metadata
            $metadata = wp_get_attachment_metadata($post_id);
            if (!empty($metadata['file'])) {
                $file_name = basename($metadata['file']);
                error_log('Found filename from metadata: ' . $file_name);
            }
            
            // If that fails, try to get from _wp_attached_file meta
            if (empty($file_name)) {
                $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
                if (!empty($attached_file)) {
                    $file_name = basename($attached_file);
                    error_log('Found filename from _wp_attached_file: ' . $file_name);
                }
            }
            
            // If that fails, try to get from post title
            if (empty($file_name)) {
                $file_name = $post->post_title;
                error_log('Using post title as filename: ' . $file_name);
            }
            
            // Add a fallback if we still don't have a name
            if (empty($file_name)) {
                $file_name = sprintf(__('Media #%d', 'katari-user-role-editor'), $post_id);
                error_log('Using fallback filename: ' . $file_name);
            }

            error_log('Final filename used: ' . $file_name);
            error_log('Post data: ' . print_r($post, true));
            error_log('Metadata: ' . print_r($metadata, true));

            $description = sprintf(
                /* translators: 1: User display name, 2: File name */
                __('User %1$s permanently deleted media file "%2$s"', 'katari-user-role-editor'),
                $user->display_name,
                $file_name
            );

            $this->log_activity('media_permanent_delete', $description);
        } catch (Exception $e) {
            error_log('Katari User Role Editor - Error in media permanent deletion interception: ' . $e->getMessage());
        }
    }

    /**
     * Log activity to database
     * 
     * @param string $action The action being logged
     * @param string $description Description of the action
     * @return bool Whether the logging was successful
     */
    private function log_activity($action, $description) {
        try {
            global $wpdb;
            
            // Ensure we have valid data
            if (empty($action) || empty($description)) {
                return false;
            }
            
            $data = array(
                'user_id' => get_current_user_id(),
                'action' => $action,
                'description' => $description,
                'created_at' => current_time('mysql')
            );
            
            $result = $wpdb->insert("{$wpdb->prefix}katari_logs", $data);
            
            if ($result === false) {
                error_log('Katari User Role Editor - Error inserting log: ' . $wpdb->last_error);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Katari User Role Editor - Error in log_activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log user profile update
     * 
     * @param int $user_id User ID
     * @param WP_User $old_user_data Object containing user's data prior to update
     */
    public function log_profile_update($user_id, $old_user_data) {
        $user = get_userdata($user_id);
        $current_user = wp_get_current_user();

        // If the user is updating their own profile
        if ($user_id === $current_user->ID) {
            $description = sprintf(
                /* translators: 1: User display name */
                __('User %1$s updated their profile', 'katari-user-role-editor'),
                $user->display_name
            );
        } else {
            $description = sprintf(
                /* translators: 1: Admin user name, 2: Target user name */
                __('User %1$s updated profile of %2$s', 'katari-user-role-editor'),
                $current_user->display_name,
                $user->display_name
            );
        }

        $this->log_activity('profile_update', $description);
    }

    /**
     * Log user registration
     * 
     * @param int $user_id User ID
     */
    public function log_user_registration($user_id) {
        $user = get_userdata($user_id);
        $current_user = wp_get_current_user();

        // If this is a user registering themselves
        if (!$current_user->exists()) {
            $description = sprintf(
                /* translators: 1: User display name */
                __('New user registration: %1$s', 'katari-user-role-editor'),
                $user->display_name
            );
        } else {
            $description = sprintf(
                /* translators: 1: Admin user name, 2: New user name */
                __('User %1$s created new user account: %2$s', 'katari-user-role-editor'),
                $current_user->display_name,
                $user->display_name
            );
        }

        $this->log_activity('user_registration', $description);
    }

    /**
     * Log user deletion
     * 
     * @param int $user_id User ID
     */
    public function log_user_deletion($user_id) {
        $deleted_user = get_userdata($user_id);
        $current_user = wp_get_current_user();

        if ($deleted_user) {
            $description = sprintf(
                /* translators: 1: Admin user name, 2: Deleted user name */
                __('User %1$s deleted user account: %2$s', 'katari-user-role-editor'),
                $current_user->display_name,
                $deleted_user->display_name
            );

            $this->log_activity('user_deletion', $description);
        }
    }

    /**
     * Log comment creation
     * 
     * @param int $comment_id Comment ID
     * @param object $comment Comment object
     */
    public function log_comment_creation($comment_id, $comment) {
        $user = wp_get_current_user();
        $post = get_post($comment->comment_post_ID);

        $description = sprintf(
            /* translators: 1: User display name, 2: Post title */
            __('User %1$s commented on "%2$s"', 'katari-user-role-editor'),
            $user->exists() ? $user->display_name : $comment->comment_author,
            $post->post_title
        );

        $this->log_activity('comment_creation', $description);
    }

    /**
     * Log menu creation
     * 
     * @param int $menu_id Menu ID
     */
    public function log_menu_creation($menu_id) {
        $user = wp_get_current_user();
        $menu = wp_get_nav_menu_object($menu_id);

        if ($menu) {
            $description = sprintf(
                /* translators: 1: User display name, 2: Menu name */
                __('User %1$s created new menu: %2$s', 'katari-user-role-editor'),
                $user->display_name,
                $menu->name
            );

            $this->log_activity('menu_creation', $description);
        }
    }

    /**
     * Log menu update
     * 
     * @param int $menu_id Menu ID
     */
    public function log_menu_update($menu_id) {
        $user = wp_get_current_user();
        $menu = wp_get_nav_menu_object($menu_id);

        if ($menu) {
            $description = sprintf(
                /* translators: 1: User display name, 2: Menu name */
                __('User %1$s updated menu: %2$s', 'katari-user-role-editor'),
                $user->display_name,
                $menu->name
            );

            $this->log_activity('menu_update', $description);
        }
    }

    /**
     * Log role cloning
     * 
     * @param string $source_role The source role that was cloned
     * @param string $new_role_name The name of the newly created role
     */
    public function log_role_clone($source_role, $new_role_name) {
        $user = wp_get_current_user();
        $wp_roles = wp_roles();
        
        $source_display_name = isset($wp_roles->role_names[$source_role]) ? $wp_roles->role_names[$source_role] : $source_role;
        $new_display_name = isset($wp_roles->role_names[$new_role_name]) ? $wp_roles->role_names[$new_role_name] : $new_role_name;

        $description = sprintf(
            /* translators: 1: User display name, 2: Source role display name, 3: New role display name */
            __('User %1$s cloned role "%2$s" to create "%3$s"', 'katari-user-role-editor'),
            $user->display_name,
            $source_display_name,
            $new_display_name
        );

        $this->log_activity('role_clone', $description);
    }
}
