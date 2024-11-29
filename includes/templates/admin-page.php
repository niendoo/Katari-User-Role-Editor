<?php
/**
 * Admin page template
 *
 * @package Katari_User_Role_Editor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$roles = wp_roles();
$capabilities_manager = $GLOBALS['katari_capabilities_manager'];
$all_capabilities = $capabilities_manager->get_all_capabilities();

// Get capability groups
$capability_groups = array(
    'basic' => array(
        'title' => 'Basic',
        'description' => 'Basic reading and access capabilities',
        'capabilities' => array('read', 'level_0')
    ),
    'posts' => array(
        'title' => 'Posts',
        'description' => 'Capabilities for managing posts',
        'capabilities' => array(
            'edit_posts',
            'edit_others_posts',
            'edit_published_posts',
            'publish_posts',
            'delete_posts',
            'delete_others_posts',
            'delete_published_posts',
            'delete_private_posts',
            'read_private_posts',
            'edit_private_posts'
        )
    ),
    'pages' => array(
        'title' => 'Pages',
        'description' => 'Capabilities for managing pages',
        'capabilities' => array(
            'edit_pages',
            'edit_others_pages',
            'edit_published_pages',
            'publish_pages',
            'delete_pages',
            'delete_others_pages',
            'delete_published_pages',
            'delete_private_pages',
            'read_private_pages',
            'edit_private_pages'
        )
    ),
    'themes' => array(
        'title' => 'Themes',
        'description' => 'Capabilities for managing themes',
        'capabilities' => array(
            'switch_themes',
            'edit_theme_options',
            'install_themes',
            'update_themes',
            'delete_themes'
        )
    ),
    'plugins' => array(
        'title' => 'Plugins',
        'description' => 'Capabilities for managing plugins',
        'capabilities' => array(
            'activate_plugins',
            'install_plugins',
            'update_plugins',
            'delete_plugins',
            'edit_plugins'
        )
    ),
    'users' => array(
        'title' => 'Users',
        'description' => 'Capabilities for managing users',
        'capabilities' => array(
            'list_users',
            'create_users',
            'edit_users',
            'delete_users',
            'promote_users'
        )
    ),
    'core' => array(
        'title' => 'Core',
        'description' => 'Core WordPress management capabilities',
        'capabilities' => array(
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
            'update_languages'
        )
    )
);
?>

<div class="wrap katari-wrap">
    <h1><?php esc_html_e('Katari User Role Editor', 'katari-user-role-editor'); ?></h1>

    <div class="katari-header">
        <div class="katari-actions">
            <form method="post" action="" style="display:inline;">
                <?php wp_nonce_field('katari_add_role'); ?>
                <button type="button" class="button" onclick="toggleAddRoleForm()">
                    <?php esc_html_e('Add New Role', 'katari-user-role-editor'); ?>
                </button>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=katari-role-editor')); ?>" style="display:inline-block; margin-left: 10px;">
                <?php wp_nonce_field('katari_restore_roles'); ?>
                <button type="submit" name="katari_restore_roles" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to restore default WordPress roles? This will remove all custom roles and reset default role capabilities. Users of custom roles will be assigned to Subscriber role.', 'katari-user-role-editor')); ?>');">
                    <?php esc_html_e('Restore Default Roles', 'katari-user-role-editor'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Add New Role Form -->
    <div id="katari-add-role-form" style="display:none" class="katari-form-container">
        <form method="post" action="">
            <?php wp_nonce_field('katari_add_role'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="role_name"><?php esc_html_e('Role Name:', 'katari-user-role-editor'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="role_name" id="role_name" class="regular-text" required 
                               pattern="[a-z0-9_-]+" 
                               title="<?php esc_attr_e('Only lowercase letters, numbers, - and _ allowed', 'katari-user-role-editor'); ?>">
                        <p class="description"><?php esc_html_e('Lowercase letters, numbers, - and _ only', 'katari-user-role-editor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="role_display_name"><?php esc_html_e('Display Name:', 'katari-user-role-editor'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="role_display_name" id="role_display_name" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="katari_add_role" class="button button-primary" value="<?php esc_attr_e('Add Role', 'katari-user-role-editor'); ?>">
                <button type="button" class="button" onclick="toggleAddRoleForm()"><?php esc_html_e('Cancel', 'katari-user-role-editor'); ?></button>
            </p>
        </form>
    </div>

    <!-- Roles Table -->
    <table class="wp-list-table widefat fixed striped katari-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php _e('Role', 'katari-user-role-editor'); ?></th>
                <th scope="col" class="manage-column column-users"><?php _e('Users', 'katari-user-role-editor'); ?></th>
                <th scope="col" class="manage-column column-capabilities"><?php _e('Capabilities', 'katari-user-role-editor'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'katari-user-role-editor'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles->role_names as $role => $name) : ?>
                <?php $role_obj = get_role($role); ?>
                <tr>
                    <td class="column-name">
                        <strong><?php echo esc_html($name); ?></strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="#" onclick="toggleCapabilities('<?php echo esc_attr($role); ?>'); return false;">
                                    <?php _e('Edit', 'katari-user-role-editor'); ?>
                                </a> | 
                            </span>
                            <span class="clone">
                                <a href="#" class="katari-clone-role" data-role="<?php echo esc_attr($role); ?>" data-name="<?php echo esc_attr($name); ?>">
                                    <?php _e('Clone', 'katari-user-role-editor'); ?>
                                </a>
                                <?php if ($role !== 'administrator'): ?> | <?php endif; ?>
                            </span>
                            <?php if ($role !== 'administrator'): ?>
                                <span class="delete">
                                    <form method="post" action="" style="display:inline;">
                                        <?php wp_nonce_field('katari_delete_role'); ?>
                                        <input type="hidden" name="role_name" value="<?php echo esc_attr($role); ?>">
                                        <button type="submit" name="katari_delete_role" class="button-link delete-role" 
                                            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this role? Users will be assigned to the default role.', 'katari-user-role-editor')); ?>');">
                                            <?php _e('Delete', 'katari-user-role-editor'); ?>
                                        </button>
                                    </form>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="column-users">
                        <?php echo count(get_users(['role' => $role])); ?>
                    </td>
                    <td class="column-capabilities">
                        <?php echo count($role_obj->capabilities); ?>
                    </td>
                    <td class="column-actions">
                        <button class="button" onclick="toggleCapabilities('<?php echo esc_attr($role); ?>')">
                            <?php _e('Edit Capabilities', 'katari-user-role-editor'); ?>
                        </button>
                    </td>
                </tr>
                <tr id="capabilities-<?php echo esc_attr($role); ?>" style="display:none">
                    <td colspan="4" class="katari-capabilities-container">
                        <div class="katari-capabilities-header">
                            <input type="text" id="capability-search-<?php echo esc_attr($role); ?>" 
                                   class="katari-capability-search" 
                                   placeholder="<?php esc_attr_e('Search capabilities...', 'katari-user-role-editor'); ?>"
                                   onkeyup="searchCapabilities('<?php echo esc_js($role); ?>')">
                        </div>

                        <form method="post" action="">
                            <?php wp_nonce_field('katari_update_role'); ?>
                            <input type="hidden" name="role_name" value="<?php echo esc_attr($role); ?>">
                            
                            <?php foreach ($capability_groups as $group_key => $group): ?>
                                <div class="katari-capability-group" id="group-<?php echo esc_attr($group_key); ?>-<?php echo esc_attr($role); ?>">
                                    <div class="katari-group-header">
                                        <h4><?php echo esc_html($group['title']); ?></h4>
                                        <label class="katari-select-all">
                                            <input type="checkbox" 
                                                   class="katari-select-all-checkbox" 
                                                   data-group="<?php echo esc_attr($group_key); ?>"
                                                   data-role="<?php echo esc_attr($role); ?>"
                                                   onclick="toggleGroupCapabilities(this)">
                                            <?php esc_html_e('Select All', 'katari-user-role-editor'); ?>
                                        </label>
                                    </div>
                                    <p class="description"><?php echo esc_html($group['description']); ?></p>
                                    <div class="katari-capabilities-grid">
                                        <?php foreach ($group['capabilities'] as $capability): ?>
                                            <?php $checked = isset($role_obj->capabilities[$capability]) ? 'checked' : ''; ?>
                                            <?php $description = $capabilities_manager->get_capability_description($capability); ?>
                                            <?php $id = esc_attr($role . '_' . $capability); ?>
                                            <div class="katari-capability-item" data-group="<?php echo esc_attr($group_key); ?>">
                                                <input type="checkbox" 
                                                       id="<?php echo $id; ?>"
                                                       name="capabilities[]" 
                                                       value="<?php echo esc_attr($capability); ?>" 
                                                       <?php echo $checked; ?>>
                                                <div class="katari-content">
                                                    <label for="<?php echo $id; ?>" class="capability-name"><?php echo esc_html($capability); ?></label>
                                                    <?php if ($description) : ?>
                                                        <div class="capability-description"><?php echo esc_html($description); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Other Capabilities -->
                            <?php
                            $other_caps = array_diff($all_capabilities, array_merge(...array_column($capability_groups, 'capabilities')));
                            if (!empty($other_caps)):
                            ?>
                                <div class="katari-capability-group" id="group-other-<?php echo esc_attr($role); ?>">
                                    <div class="katari-group-header">
                                        <h4><?php esc_html_e('Other Capabilities', 'katari-user-role-editor'); ?></h4>
                                        <label class="katari-select-all">
                                            <input type="checkbox" 
                                                   class="katari-select-all-checkbox" 
                                                   data-group="other"
                                                   data-role="<?php echo esc_attr($role); ?>"
                                                   onclick="toggleGroupCapabilities(this)">
                                            <?php esc_html_e('Select All', 'katari-user-role-editor'); ?>
                                        </label>
                                    </div>
                                    <p class="description"><?php esc_html_e('Additional capabilities not in predefined groups', 'katari-user-role-editor'); ?></p>
                                    <div class="katari-capabilities-grid">
                                        <?php foreach ($other_caps as $capability): ?>
                                            <?php $checked = isset($role_obj->capabilities[$capability]) ? 'checked' : ''; ?>
                                            <?php $description = $capabilities_manager->get_capability_description($capability); ?>
                                            <?php $id = esc_attr($role . '_' . $capability); ?>
                                            <div class="katari-capability-item" data-group="other">
                                                <input type="checkbox" 
                                                       id="<?php echo $id; ?>"
                                                       name="capabilities[]" 
                                                       value="<?php echo esc_attr($capability); ?>" 
                                                       <?php echo $checked; ?>>
                                                <div class="katari-content">
                                                    <label for="<?php echo $id; ?>" class="capability-name"><?php echo esc_html($capability); ?></label>
                                                    <?php if ($description) : ?>
                                                        <div class="capability-description"><?php echo esc_html($description); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="submit-button">
                                <input type="submit" name="katari_update_role" class="button button-primary" value="<?php esc_attr_e('Update Capabilities', 'katari-user-role-editor'); ?>">
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Clone Role Modal -->
    <div id="katari-clone-modal" class="katari-modal">
        <div class="katari-modal-content">
            <span class="katari-modal-close">&times;</span>
            <h2><?php esc_html_e('Clone Role', 'katari-user-role-editor'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('katari_clone_role'); ?>
                <input type="hidden" name="source_role" id="source_role">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="source_role_display"><?php esc_html_e('Source Role:', 'katari-user-role-editor'); ?></label>
                        </th>
                        <td>
                            <strong id="source_role_display"></strong>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="new_role_name"><?php esc_html_e('New Role Name:', 'katari-user-role-editor'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="new_role_name" id="new_role_name" class="regular-text" required 
                                   pattern="[a-z0-9_-]+" 
                                   title="<?php esc_attr_e('Only lowercase letters, numbers, - and _ allowed', 'katari-user-role-editor'); ?>">
                            <p class="description"><?php esc_html_e('Lowercase letters, numbers, - and _ only', 'katari-user-role-editor'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="new_role_display_name"><?php esc_html_e('Display Name:', 'katari-user-role-editor'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="new_role_display_name" id="new_role_display_name" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                <div class="submit">
                    <input type="submit" name="katari_clone_role" class="button button-primary" value="<?php esc_attr_e('Clone Role', 'katari-user-role-editor'); ?>">
                    <button type="button" class="button katari-modal-cancel"><?php esc_html_e('Cancel', 'katari-user-role-editor'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleCapabilities(role) {
        var row = document.getElementById('capabilities-' + role);
        if (row) {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Clone role functionality
        var cloneModal = document.getElementById('katari-clone-modal');
        var cloneButtons = document.getElementsByClassName('katari-clone-role');
        var closeButtons = cloneModal.getElementsByClassName('katari-modal-close')[0];
        var cancelButton = cloneModal.getElementsByClassName('katari-modal-cancel')[0];

        Array.from(cloneButtons).forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var role = this.getAttribute('data-role');
                var name = this.getAttribute('data-name');
                
                document.getElementById('source_role').value = role;
                document.getElementById('source_role_display').textContent = name;
                cloneModal.style.display = 'block';
            });
        });

        function closeModal() {
            cloneModal.style.display = 'none';
            document.getElementById('new_role_name').value = '';
            document.getElementById('new_role_display_name').value = '';
        }

        closeButtons.addEventListener('click', closeModal);
        cancelButton.addEventListener('click', closeModal);

        window.addEventListener('click', function(event) {
            if (event.target == cloneModal) {
                closeModal();
            }
        });
    });

    function toggleAddRoleForm() {
        var form = document.getElementById('katari-add-role-form');
        if (form.style.display === 'none') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
            // Clear form fields
            document.getElementById('role_name').value = '';
            document.getElementById('role_display_name').value = '';
        }
    }
    </script>

    <style>
    .katari-capability-group {
        background: #fff;
        border: 1px solid #e2e4e7;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }

    .katari-capability-group h4 {
        margin: 0 0 10px 0;
        color: #1d2327;
    }

    .katari-capabilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .katari-capability-item {
        display: flex;
        align-items: center;
        padding: 5px;
    }

    .katari-content {
        flex: 1;
        margin-left: 10px;
    }

    .katari-capability-item input[type="checkbox"] {
        margin-right: 8px;
    }

    .description {
        color: #646970;
        font-style: italic;
        margin: 5px 0 15px;
    }

    .katari-capabilities-header {
        padding: 10px;
        background: #f7f7f7;
        border-bottom: 1px solid #e2e4e7;
    }

    .katari-capability-search {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #e2e4e7;
    }

    .katari-group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #e2e4e7;
    }

    .katari-select-all {
        margin-left: 10px;
    }

    .katari-select-all-checkbox {
        margin-right: 5px;
    }

    .katari-modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
    }

    .katari-modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    .katari-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .katari-modal-close:hover,
    .katari-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
