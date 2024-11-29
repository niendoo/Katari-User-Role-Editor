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
            <button class="button" onclick="document.getElementById('katari-new-role-form').style.display='block'">
                <?php esc_html_e('Add New Role', 'katari-user-role-editor'); ?>
            </button>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=katari-role-editor')); ?>" style="display:inline-block; margin-left: 10px;">
                <?php wp_nonce_field('katari_restore_roles'); ?>
                <button type="submit" name="katari_restore_roles" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to restore default WordPress roles? This will remove all custom roles and reset default role capabilities. Users of custom roles will be assigned to Subscriber role.', 'katari-user-role-editor')); ?>');">
                    <?php esc_html_e('Restore Default Roles', 'katari-user-role-editor'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- New Role Form -->
    <div id="katari-new-role-form" style="display:none" class="katari-modal">
        <form method="post" action="">
            <?php wp_nonce_field('katari_add_role'); ?>
            <h3><?php esc_html_e('Add New Role', 'katari-user-role-editor'); ?></h3>
            <p>
                <label for="role_name"><?php esc_html_e('Role Name:', 'katari-user-role-editor'); ?></label>
                <input type="text" name="role_name" id="role_name" required pattern="[a-z0-9_-]+" title="<?php esc_attr_e('Only lowercase letters, numbers, - and _ allowed', 'katari-user-role-editor'); ?>">
                <span class="description"><?php esc_html_e('Lowercase letters, numbers, - and _ only', 'katari-user-role-editor'); ?></span>
            </p>
            <p>
                <label for="role_display_name"><?php esc_html_e('Display Name:', 'katari-user-role-editor'); ?></label>
                <input type="text" name="role_display_name" id="role_display_name" required>
            </p>
            <p>
                <input type="submit" name="katari_add_role" class="button button-primary" value="<?php esc_attr_e('Add Role', 'katari-user-role-editor'); ?>">
                <button type="button" class="button" onclick="this.parentElement.parentElement.style.display='none'"><?php esc_html_e('Cancel', 'katari-user-role-editor'); ?></button>
            </p>
        </form>
    </div>

    <!-- Roles Table -->
    <table class="wp-list-table widefat fixed striped katari-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Role', 'katari-user-role-editor'); ?></th>
                <th><?php esc_html_e('Users', 'katari-user-role-editor'); ?></th>
                <th><?php esc_html_e('Capabilities', 'katari-user-role-editor'); ?></th>
                <th><?php esc_html_e('Actions', 'katari-user-role-editor'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles->role_names as $role => $name) : ?>
                <?php $role_obj = get_role($role); ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($name); ?></strong>
                        <br>
                        <small><?php echo esc_html($role); ?></small>
                    </td>
                    <td>
                        <?php
                        $user_count = count(get_users(array('role' => $role)));
                        echo esc_html($user_count);
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($role_obj->capabilities)) {
                            $cap_count = count($role_obj->capabilities);
                            printf(
                                /* translators: %d: number of capabilities */
                                esc_html(_n('%d capability', '%d capabilities', $cap_count, 'katari-user-role-editor')),
                                $cap_count
                            );
                        } else {
                            esc_html_e('No capabilities', 'katari-user-role-editor');
                        }
                        ?>
                    </td>
                    <td>
                        <button class="button" onclick="toggleCapabilities('<?php echo esc_attr($role); ?>')">
                            <?php esc_html_e('Edit Capabilities', 'katari-user-role-editor'); ?>
                        </button>
                        <?php if ($role !== 'administrator') : ?>
                            <form method="post" action="" style="display:inline-block;">
                                <?php wp_nonce_field('katari_delete_role'); ?>
                                <input type="hidden" name="role_name" value="<?php echo esc_attr($role); ?>">
                                <button type="submit" name="katari_delete_role" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this role? Users will be assigned to Subscriber role.', 'katari-user-role-editor')); ?>');">
                                    <?php esc_html_e('Delete', 'katari-user-role-editor'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
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
</div>

<script>
function toggleCapabilities(role) {
    var row = document.getElementById('capabilities-' + role);
    if (row) {
        row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    }
}

function searchCapabilities(role) {
    var searchInput = document.getElementById('capability-search-' + role);
    var searchValue = searchInput.value.toLowerCase();
    var capabilityGroups = document.querySelectorAll('[id^="group-"][id$="-' + role + '"]');
    
    capabilityGroups.forEach(function(group) {
        var capabilities = group.querySelectorAll('.katari-capability-item');
        var visibleCount = 0;
        
        capabilities.forEach(function(capability) {
            var capName = capability.querySelector('.capability-name').textContent.toLowerCase();
            if (capName.includes(searchValue)) {
                capability.style.display = '';
                visibleCount++;
            } else {
                capability.style.display = 'none';
            }
        });
        
        // Show/hide the entire group based on whether it has visible capabilities
        group.style.display = visibleCount > 0 ? '' : 'none';
    });
}

function toggleGroupCapabilities(checkbox) {
    var group = checkbox.dataset.group;
    var role = checkbox.dataset.role;
    var groupElement = document.getElementById('group-' + group + '-' + role);
    if (groupElement) {
        var capabilities = groupElement.querySelectorAll('.katari-capability-item');
        capabilities.forEach(function(capability) {
            if (capability.style.display !== 'none') {
                capability.querySelector('input[type="checkbox"]').checked = checkbox.checked;
            }
        });
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
</style>
