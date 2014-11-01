<?php
/**
 * Fired when the h5p plugin is uninstalled.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

global $wpdb;
global $wp_roles;

if (!isset($wp_roles)) {
  $wp_roles = new WP_Roles();
}

// Drop tables
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_results");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_languages");

// Remove settings
delete_option('h5p_version');
delete_option('h5p_export');
delete_option('h5p_icon');

// Remove capabilities
$all_roles = $wp_roles->roles;
foreach ($all_roles as $role_name => $role_info) {
  $role = get_role($role_name);

  if (isset($role_info['capabilities']['manage_h5p_libraries'])) {
    $role->remove_cap('manage_h5p_libraries');
  }
  if (isset($role_info['capabilities']['edit_others_h5p_contents'])) {
    $role->remove_cap('edit_others_h5p_contents');
  }
  if (isset($role_info['capabilities']['edit_h5p_contents'])) {
    $role->remove_cap('edit_h5p_contents');
  }
  if (isset($role_info['capabilities']['view_h5p_results'])) {
    $role->remove_cap('view_h5p_results');
  }
}

/**
 * Recursively remove file or directory.
 *
 * @since 1.0.0
 * @param string $file
 */
function _h5p_recursive_unlink($file) {
  if (is_dir($file)) {
    // Remove all files in dir.
    $subfiles = array_diff(scandir($file), array('.','..'));
    foreach ($subfiles as $subfile)  {
      _h5p_recursive_unlink($file . '/' . $subfile);
    }
    rmdir($file);
  }
  elseif (file_exists($file)) {
    unlink($file); // TODO: Remove from file_managed!!
  }
}

// Clean out file dirs.
$upload_dir = wp_upload_dir();
$path = $upload_dir['basedir'] . '/h5p';

// Remove these regardless of their content.
foreach (array('tmp', 'temp', 'libraries', 'content', 'exports', 'editor') as $directory) {
  _h5p_recursive_unlink($path . '/' . $directory);
}

// Only remove development dir if it's empty.
$dir = $path . '/development';
if (is_dir($dir) && count(scandir($dir)) === 2) {
  rmdir($dir);

  // Remove parent if empty.
  if (count(scandir($path)) === 2) {
    rmdir($path);
  }
}
