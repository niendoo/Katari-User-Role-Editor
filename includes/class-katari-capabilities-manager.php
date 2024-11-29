<?php
/**
 * Class for managing WordPress roles and capabilities.
 *
 * @package Katari_User_Role_Editor
 */

class Katari_Capabilities_Manager {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_init', array($this, 'handle_role_actions'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Add filter to restrict capabilities
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 4);
    }

    /**
     * Filter user capabilities to ensure proper restrictions.
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        // If user is admin, don't filter
        if (in_array('administrator', $user->roles)) {
            return $allcaps;
        }

        // Get the user's roles
        $roles = $user->roles;
        
        // Initialize capabilities array
        $allowed_caps = array();

        // Get capabilities from all user's roles
        foreach ($roles as $role) {
            $role_obj = get_role($role);
            if ($role_obj) {
                $allowed_caps = array_merge($allowed_caps, array_keys(array_filter($role_obj->capabilities)));
            }
        }

        // Reset all capabilities to false first
        foreach ($allcaps as $cap => $grant) {
            $allcaps[$cap] = false;
        }

        // Only set explicitly allowed capabilities to true
        foreach ($allowed_caps as $cap) {
            $allcaps[$cap] = true;
        }

        return $allcaps;
    }

    /**
     * Handle all role-related actions.
     */
    public function handle_role_actions() {
        // Handle role update
        if (isset($_POST['katari_update_role']) && isset($_POST['_wpnonce'])) {
            $this->update_role();
        }

        // Handle role deletion
        if (isset($_POST['katari_delete_role']) && isset($_POST['_wpnonce'])) {
            $this->delete_role();
        }

        // Handle role creation
        if (isset($_POST['katari_add_role']) && isset($_POST['_wpnonce'])) {
            $this->add_role();
        }
    }

    /**
     * Update role capabilities.
     */
    private function update_role() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'katari_update_role')) {
            wp_die(__('Security check failed.', 'katari-user-role-editor'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'katari-user-role-editor'));
        }

        // Get role name
        $role_name = isset($_POST['role_name']) ? sanitize_text_field($_POST['role_name']) : '';
        if (empty($role_name)) {
            wp_die(__('Role name is required.', 'katari-user-role-editor'));
        }

        // Get role
        $role = get_role($role_name);
        if (!$role) {
            wp_die(__('Role not found.', 'katari-user-role-editor'));
        }

        // Get submitted capabilities
        $submitted_caps = isset($_POST['capabilities']) ? (array) $_POST['capabilities'] : array();
        $submitted_caps = array_map('sanitize_text_field', $submitted_caps);

        // Get all possible capabilities
        $all_caps = $this->get_all_capabilities();

        // Store current capabilities for comparison
        $current_caps = (array) $role->capabilities;

        // First, remove all capabilities
        foreach ($current_caps as $cap => $grant) {
            $role->remove_cap($cap);
        }

        // Then add only the selected capabilities
        foreach ($submitted_caps as $cap) {
            if (in_array($cap, $all_caps)) {
                $role->add_cap($cap, true);
            }
        }

        // Clear user capabilities cache for all users with this role
        $users = get_users(array('role' => $role_name));
        foreach ($users as $user) {
            wp_cache_delete($user->ID, 'user_meta');
        }

        // Redirect with success message
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'katari-user-role-editor',
                'message' => 'role_updated'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Delete a role.
     */
    private function delete_role() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'katari_delete_role')) {
            wp_die(__('Security check failed.', 'katari-user-role-editor'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'katari-user-role-editor'));
        }

        // Get role name
        $role_name = isset($_POST['role_name']) ? sanitize_text_field($_POST['role_name']) : '';
        if (empty($role_name) || $role_name === 'administrator') {
            wp_die(__('Invalid role.', 'katari-user-role-editor'));
        }

        // Get users with this role
        $users = get_users(array('role' => $role_name));

        // Assign these users to subscriber role
        foreach ($users as $user) {
            $user->remove_role($role_name);
            $user->add_role('subscriber');
        }

        // Remove the role
        remove_role($role_name);

        // Redirect with success message
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'katari-user-role-editor',
                'message' => 'role_deleted'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Add a new role.
     */
    private function add_role() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'katari_add_role')) {
            wp_die(__('Security check failed.', 'katari-user-role-editor'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'katari-user-role-editor'));
        }

        // Get role details
        $role_name = isset($_POST['role_name']) ? sanitize_text_field($_POST['role_name']) : '';
        $role_display_name = isset($_POST['role_display_name']) ? sanitize_text_field($_POST['role_display_name']) : '';

        if (empty($role_name) || empty($role_display_name)) {
            wp_die(__('Role name and display name are required.', 'katari-user-role-editor'));
        }

        // Create new role with basic read capability
        $result = add_role($role_name, $role_display_name, array('read' => true));

        if (null === $result) {
            wp_die(__('Failed to create role or role already exists.', 'katari-user-role-editor'));
        }

        // Redirect with success message
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'katari-user-role-editor',
                'message' => 'role_added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Show admin notices.
     */
    public function show_admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'katari-user-role-editor') {
            return;
        }

        $message = isset($_GET['message']) ? $_GET['message'] : '';
        $notice_class = 'notice-success';
        $notice_message = '';

        switch ($message) {
            case 'role_updated':
                $notice_message = __('Role capabilities updated successfully.', 'katari-user-role-editor');
                break;
            case 'role_deleted':
                $notice_message = __('Role deleted successfully.', 'katari-user-role-editor');
                break;
            case 'role_added':
                $notice_message = __('New role created successfully.', 'katari-user-role-editor');
                break;
        }

        if (!empty($notice_message)) {
            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr($notice_class),
                esc_html($notice_message)
            );
        }
    }

    /**
     * Get all WordPress capabilities.
     *
     * @return array Array of capabilities.
     */
    public function get_all_capabilities() {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $capabilities = array();

        // Get capabilities from all roles
        foreach ($wp_roles->roles as $role) {
            if (isset($role['capabilities']) && is_array($role['capabilities'])) {
                $capabilities = array_merge($capabilities, array_keys($role['capabilities']));
            }
        }

        // Define core WordPress capabilities by group
        $core_caps = array(
            // Basic capabilities
            'basic' => array(
                'read',
                'level_0',
            ),
            
            // Post capabilities
            'posts' => array(
                'edit_posts',
                'edit_others_posts',
                'edit_published_posts',
                'publish_posts',
                'delete_posts',
                'delete_others_posts',
                'delete_published_posts',
                'delete_private_posts',
                'read_private_posts',
                'edit_private_posts',
            ),
            
            // Pages capabilities
            'pages' => array(
                'edit_pages',
                'edit_others_pages',
                'edit_published_pages',
                'publish_pages',
                'delete_pages',
                'delete_others_pages',
                'delete_published_pages',
                'delete_private_pages',
                'read_private_pages',
                'edit_private_pages',
            ),
            
            // Theme capabilities
            'themes' => array(
                'switch_themes',
                'edit_theme_options',
                'install_themes',
                'update_themes',
                'delete_themes',
            ),
            
            // Plugin capabilities
            'plugins' => array(
                'activate_plugins',
                'install_plugins',
                'update_plugins',
                'delete_plugins',
                'edit_plugins',
            ),
            
            // User capabilities
            'users' => array(
                'list_users',
                'create_users',
                'edit_users',
                'delete_users',
                'promote_users',
            ),
            
            // Core capabilities
            'core' => array(
                'manage_options',
                'moderate_comments',
                'manage_categories',
                'manage_links',
                'upload_files',
                'import',
                'export',
                'unfiltered_html',
                'edit_dashboard',
                'update_core',
                'install_languages',
                'update_languages',
                'install_plugins',
                'update_plugins',
                'install_themes',
                'update_themes',
            ),
        );

        // Merge all capability groups
        foreach ($core_caps as $group_caps) {
            $capabilities = array_merge($capabilities, $group_caps);
        }

        $capabilities = array_unique($capabilities);
        sort($capabilities);

        return $capabilities;
    }
}
