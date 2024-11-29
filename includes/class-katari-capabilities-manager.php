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

        // Handle restore default roles
        if (isset($_POST['katari_restore_roles']) && isset($_POST['_wpnonce'])) {
            $this->restore_default_roles();
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
                'page' => 'katari-role-editor',
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
                'page' => 'katari-role-editor',
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
                'page' => 'katari-role-editor',
                'message' => 'role_added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Restore WordPress default roles and capabilities.
     */
    private function restore_default_roles() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'katari_restore_roles')) {
            wp_die(__('Security check failed.', 'katari-user-role-editor'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to restore default roles. This action requires manage_options capability.', 'katari-user-role-editor'));
        }

        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // Get all current roles except administrator
        $existing_roles = array_keys($wp_roles->roles);
        foreach ($existing_roles as $role) {
            if ($role !== 'administrator') {
                remove_role($role);
            }
        }

        // Restore default roles with their capabilities
        add_role('editor', __('Editor'), array(
            'moderate_comments' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'upload_files' => true,
            'unfiltered_html' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'edit_pages' => true,
            'read' => true,
            'level_7' => true,
            'level_6' => true,
            'level_5' => true,
            'level_4' => true,
            'level_3' => true,
            'level_2' => true,
            'level_1' => true,
            'level_0' => true,
            'edit_others_pages' => true,
            'edit_published_pages' => true,
            'publish_pages' => true,
            'delete_pages' => true,
            'delete_others_pages' => true,
            'delete_published_pages' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'delete_published_posts' => true,
            'delete_private_posts' => true,
            'read_private_posts' => true,
            'edit_private_posts' => true,
            'delete_private_pages' => true,
            'edit_private_pages' => true,
            'read_private_pages' => true
        ));

        add_role('author', __('Author'), array(
            'upload_files' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'read' => true,
            'level_2' => true,
            'level_1' => true,
            'level_0' => true,
            'delete_posts' => true,
            'delete_published_posts' => true
        ));

        add_role('contributor', __('Contributor'), array(
            'edit_posts' => true,
            'read' => true,
            'level_1' => true,
            'level_0' => true,
            'delete_posts' => true
        ));

        add_role('subscriber', __('Subscriber'), array(
            'read' => true,
            'level_0' => true
        ));

        // Redirect with success message
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'katari-role-editor',
                'message' => 'roles_restored'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Show admin notices.
     */
    public function show_admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'katari-role-editor') {
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
            case 'roles_restored':
                $notice_message = __('Default WordPress roles have been restored successfully.', 'katari-user-role-editor');
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

    /**
     * Get description for a capability.
     *
     * @param string $capability The capability to get description for.
     * @return string The capability description.
     */
    public function get_capability_description($capability) {
        $descriptions = array(
            // Basic capabilities
            'read' => __('Access to read/view content on the site', 'katari-user-role-editor'),
            'level_0' => __('Basic user level', 'katari-user-role-editor'),
            
            // Post capabilities
            'edit_posts' => __('Create and edit own posts', 'katari-user-role-editor'),
            'edit_others_posts' => __('Edit posts created by other users', 'katari-user-role-editor'),
            'edit_published_posts' => __('Edit already published posts', 'katari-user-role-editor'),
            'publish_posts' => __('Publish posts', 'katari-user-role-editor'),
            'delete_posts' => __('Delete own posts', 'katari-user-role-editor'),
            'delete_others_posts' => __('Delete posts created by other users', 'katari-user-role-editor'),
            'delete_published_posts' => __('Delete published posts', 'katari-user-role-editor'),
            'delete_private_posts' => __('Delete private posts', 'katari-user-role-editor'),
            'read_private_posts' => __('Read private posts', 'katari-user-role-editor'),
            'edit_private_posts' => __('Edit private posts', 'katari-user-role-editor'),
            
            // Page capabilities
            'edit_pages' => __('Create and edit own pages', 'katari-user-role-editor'),
            'edit_others_pages' => __('Edit pages created by other users', 'katari-user-role-editor'),
            'edit_published_pages' => __('Edit already published pages', 'katari-user-role-editor'),
            'publish_pages' => __('Publish pages', 'katari-user-role-editor'),
            'delete_pages' => __('Delete own pages', 'katari-user-role-editor'),
            'delete_others_pages' => __('Delete pages created by other users', 'katari-user-role-editor'),
            'delete_published_pages' => __('Delete published pages', 'katari-user-role-editor'),
            'delete_private_pages' => __('Delete private pages', 'katari-user-role-editor'),
            'read_private_pages' => __('Read private pages', 'katari-user-role-editor'),
            'edit_private_pages' => __('Edit private pages', 'katari-user-role-editor'),
            
            // Theme capabilities
            'switch_themes' => __('Switch between different themes', 'katari-user-role-editor'),
            'edit_theme_options' => __('Edit theme options and customize appearance', 'katari-user-role-editor'),
            'install_themes' => __('Install new themes', 'katari-user-role-editor'),
            'update_themes' => __('Update existing themes', 'katari-user-role-editor'),
            'delete_themes' => __('Delete themes', 'katari-user-role-editor'),
            
            // Plugin capabilities
            'activate_plugins' => __('Activate and deactivate plugins', 'katari-user-role-editor'),
            'install_plugins' => __('Install new plugins', 'katari-user-role-editor'),
            'update_plugins' => __('Update existing plugins', 'katari-user-role-editor'),
            'delete_plugins' => __('Delete plugins', 'katari-user-role-editor'),
            'edit_plugins' => __('Edit plugin files', 'katari-user-role-editor'),
            
            // User capabilities
            'list_users' => __('View list of users', 'katari-user-role-editor'),
            'create_users' => __('Create new users', 'katari-user-role-editor'),
            'edit_users' => __('Edit existing users', 'katari-user-role-editor'),
            'delete_users' => __('Delete users', 'katari-user-role-editor'),
            'promote_users' => __('Promote users and change their roles', 'katari-user-role-editor'),
            
            // Core capabilities
            'manage_options' => __('Manage site options and settings', 'katari-user-role-editor'),
            'moderate_comments' => __('Moderate and manage comments', 'katari-user-role-editor'),
            'manage_categories' => __('Manage post categories', 'katari-user-role-editor'),
            'manage_links' => __('Manage navigation links', 'katari-user-role-editor'),
            'upload_files' => __('Upload files to the media library', 'katari-user-role-editor'),
            'import' => __('Import content from other sources', 'katari-user-role-editor'),
            'export' => __('Export site content', 'katari-user-role-editor'),
            'unfiltered_html' => __('Edit content with unrestricted HTML', 'katari-user-role-editor'),
            'edit_dashboard' => __('Edit dashboard appearance and widgets', 'katari-user-role-editor'),
            'update_core' => __('Update WordPress core', 'katari-user-role-editor'),
            'install_languages' => __('Install new language translations', 'katari-user-role-editor'),
            'update_languages' => __('Update language translations', 'katari-user-role-editor')
        );

        return isset($descriptions[$capability]) ? $descriptions[$capability] : __('No description available', 'katari-user-role-editor');
    }

    /**
     * Toggle a capability for a role.
     *
     * @param string $role_name Role name.
     * @param string $capability Capability name.
     * @param bool   $granted Whether to grant or revoke the capability.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function toggle_capability($role_name, $capability, $granted) {
        // Get the role
        $role = get_role($role_name);
        if (!$role) {
            return new WP_Error('invalid_role', __('Invalid role.', 'katari-user-role-editor'));
        }

        // Toggle the capability
        if ($granted) {
            $role->add_cap($capability);
        } else {
            $role->remove_cap($capability);
        }

        // Trigger logging action
        do_action('katari_capability_toggled', $role_name, $capability, $granted);

        return true;
    }
}
