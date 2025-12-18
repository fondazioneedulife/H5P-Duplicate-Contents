<?php

function checkUrl() {
    $uri = parse_url( $_SERVER['REQUEST_URI'] );
    $path = explode( '/', $uri['path'] ); // array_pop needs reference
    $destination = array_pop( $path );

    if ('admin.php' !== $destination) {
        return 0; // Not relevant
    } else {
        if (array_key_exists("query", $uri)) {
            parse_str($uri['query'], $queries);
            return $queries;
        } else {
            return 0;
        }        
    }
}

function isUserInMainPageH5PContents($queries) {
    if ($queries['page'] === 'h5p' && count($queries) === 1) {
        return 1;
    } else {
        return 0;
    }
}

function isUserInDuplicationPage($queries) {
    if ($queries['page'] === 'h5p' && isset($queries['duplicate']) && count($queries) > 1) {
        return 1;
    } else {
        return 0;
    }
}

function duplicateContent($wpdb, $origin_content_id) {
    // Select the content to duplicate
    $content_to_duplicate = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}h5p_contents 
    WHERE id = '" . $origin_content_id . "'", ARRAY_A);

    if (count($content_to_duplicate) > 0) { // If query had good result

        $new_content = $content_to_duplicate;
        $new_content["id"] = NULL;
        $new_content["created_at"] = date("Y-m-d H:i:s");
        $new_content["updated_at"] = date("Y-m-d H:i:s");
        $new_content["user_id"] = get_current_user_id(); // Replace user content with logged user

        $wpdb->insert("{$wpdb->prefix}h5p_contents", $new_content, array('%s'));

        $new_content_id = $wpdb->insert_id;

        if ($new_content_id) {
            duplicateContentLibraries($wpdb, $origin_content_id, $new_content_id);
            duplicateContentUploads($origin_content_id, $new_content_id);

            insertDuplicationTable($wpdb, $origin_content_id, $new_content_id);
        }
    }
}

function duplicateContentLibraries($wpdb, $origin_content_id, $destination_content_id) {
    // Select libraries to duplicate
    $content_libraries_to_duplicate = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}h5p_contents_libraries 
    WHERE content_id = '" . $origin_content_id . "'", ARRAY_A);
    
    foreach ($content_libraries_to_duplicate as $library_row) {
        $new_library_row = $library_row;
        $new_library_row["content_id"] = $destination_content_id;
        $wpdb->insert("{$wpdb->prefix}h5p_contents_libraries", $new_library_row, array('%s'));
    }
}

function duplicateContentUploads($origin_content_id, $destination_content_id) {
    global $wp_filesystem;

    // It is a Wordpress core file, that's why we manually include it
    require_once (ABSPATH . '/wp-admin/includes/file.php');
    
    // Just instantiate as follows
    WP_Filesystem();

    $origin_content_folder = WP_CONTENT_DIR .'/uploads/h5p/content/' . $origin_content_id;

    if ($wp_filesystem->exists( $origin_content_folder)) {

        $destination_content_folder = WP_CONTENT_DIR .'/uploads/h5p/content/' . $destination_content_id;

        copy_dir($origin_content_folder, $destination_content_folder);
    }
}

function insertDuplicationTable($wpdb, $origin_content_id, $destination_content_id) {

    $origin_content = $wpdb->get_row("SELECT user_id, title FROM {$wpdb->prefix}h5p_contents 
    WHERE id = '" . $origin_content_id . "'", ARRAY_A);

    $origin_content_author = $wpdb->get_row("SELECT user_nicename FROM {$wpdb->prefix}users
    WHERE ID = '" . $origin_content["user_id"] . "'", ARRAY_A);

    $new_duplicate_content_row = [];
    $new_duplicate_content_row["content_id"] = $destination_content_id;
    $new_duplicate_content_row["origin_content_id"] = $origin_content_id;
    $new_duplicate_content_row["origin_content_name"] = $origin_content["title"];
    $new_duplicate_content_row["origin_content_author_nicename"] = $origin_content_author["user_nicename"];

    $wpdb->insert("{$wpdb->prefix}h5p_duplicate_contents", $new_duplicate_content_row, array('%s'));
}

function getDuplicationTableData($wpdb) {
    $duplicate_content_results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}h5p_duplicate_contents", ARRAY_A);
    
    $duplicated_data["duplicationTable"] = [];

    foreach ($duplicate_content_results as $content) {
        $duplicated_data["duplicationTable"][$content["content_id"]] = [
            "origin_content_name" => $content["origin_content_name"],
            "origin_content_author" => $content["origin_content_author_nicename"],
        ];
    }

    return $duplicated_data;
}
