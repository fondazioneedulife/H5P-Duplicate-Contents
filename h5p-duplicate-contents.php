<?php

/*
 * Plugin Name:       H5P Duplicate Contents
 * Description:       This plugin allow to duplicate contents created by H5P plugin.
 * Version:           1.0.0
 * Author:            Iacopo Zerbetto
 */

namespace H5PDUPLICATECONTENTS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once(dirname(__FILE__) . '/h5p-duplicate-functions.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

register_activation_hook(__FILE__, function() {
    global $wpdb;

    $charsetCollate = $wpdb->get_charset_collate();

    $table = $wpdb->prefix . 'h5p_duplicate_contents';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql = "CREATE TABLE $table (
            `content_id` INT(11) NOT NULL,
            `origin_content_id` INT(11) NOT NULL,
            `origin_content_name` VARCHAR(100) NOT NULL,
            `origin_content_author_nicename` VARCHAR(50) NOT NULL
        ) $charsetCollate;";
        dbDelta($sql);
    }
});

register_deactivation_hook( __FILE__, function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'h5p_duplicate_contents';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    delete_option("my_plugin_db_version");
});

function enqueue_custom_h5p_column_script() {
    global $wpdb; // For use db

    $queries = checkUrl();
    if (!$queries) {
        return;  // Not relevant
    }

    if (isUserInMainPageH5PContents($queries)) { // Check if in h5p plugin's main page

        $duplicated_contents_array = getDuplicationTableData($wpdb);

        // Call custom-h5p-column.js to inject js
        wp_enqueue_script('custom-h5p-column', plugins_url( '/js/custom-h5p-column.js', __FILE__ ), array('jquery'), null, true);
        wp_localize_script( 'custom-h5p-column', 'php_vars', $duplicated_contents_array );

    } elseif (isUserInDuplicationPage($queries)) { // Check if duplication page

        $content_id_to_duplicate = $queries['content_id'];
        duplicateContent($wpdb, $content_id_to_duplicate);
    
        header('Location: ' . $_SERVER['PHP_SELF'] . "?page=h5p"); // Redirect to h5p plugin's main page
    }
}

add_action('admin_enqueue_scripts', __NAMESPACE__.'\enqueue_custom_h5p_column_script');
