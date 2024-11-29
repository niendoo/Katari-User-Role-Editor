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
                        <form method="post" action="">
                            <?php wp_nonce_field('katari_update_role'); ?>
                            <input type="hidden" name="role_name" value="<?php echo esc_attr($role); ?>">
                            
                            <?php foreach ($capability_groups as $group_key => $group): ?>
                                <div class="katari-capability-group">
                                    <h4><?php echo esc_html($group['title']); ?></h4>
                                    <p class="description"><?php echo esc_html($group['description']); ?></p>
                                    <div class="katari-capabilities-grid">
                                        <?php foreach ($group['capabilities'] as $cap): ?>
                                            <div class="katari-capability-checkbox">
                                                <input type="checkbox" 
                                                       id="<?php echo esc_attr($role . '_' . $cap); ?>"
                                                       name="capabilities[]" 
                                                       value="<?php echo esc_attr($cap); ?>"
                                                       <?php checked(isset($role_obj->capabilities[$cap]) && $role_obj->capabilities[$cap]); ?>>
                                                <span><?php echo esc_html($cap); ?></span>
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
                                <div class="katari-capability-group">
                                    <h4>Other Capabilities</h4>
                                    <p class="description">Additional capabilities not in predefined groups</p>
                                    <div class="katari-capabilities-grid">
                                        <?php foreach ($other_caps as $cap): ?>
                                            <div class="katari-capability-checkbox">
                                                <input type="checkbox" 
                                                       id="<?php echo esc_attr($role . '_' . $cap); ?>"
                                                       name="capabilities[]" 
                                                       value="<?php echo esc_attr($cap); ?>"
                                                       <?php checked(isset($role_obj->capabilities[$cap])); ?>>
                                                <span><?php echo esc_html($cap); ?></span>
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

.katari-capability-checkbox {
    display: flex;
    align-items: center;
    padding: 5px;
}

.katari-capability-checkbox input[type="checkbox"] {
    margin-right: 8px;
}

.description {
    color: #646970;
    font-style: italic;
    margin: 5px 0 15px;
}
</style>
