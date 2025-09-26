<?php
/*
Plugin Name:        BtW Importer - Blogger to WordPress Importer
Plugin URI:         https://github.com/mnasikin/btw-importer
Description:        Simple yet powerful plugin to Migrate Blogger to WordPress in one click. Import .atom from Google Takeout and the plugin will scan & download first image, replace URLs, set featured image, show live progress.
Version:            2.1.0
Author:             Nasikin
Author URI:         https://github.com/mnasikin/
License:            MIT
Domain Path:        /languages
Text Domain:        btw-importer
Requires PHP:       7.4
GitHub Plugin URI:  https://github.com/mnasikin/btw-importer
Primary Branch:     main
*/

function btw_importer_include_files() {
    require_once plugin_dir_path(__FILE__) . 'importer.php';
    require_once plugin_dir_path(__FILE__) . 'redirect.php';
    require_once plugin_dir_path(__FILE__) . 'redirect-log.php';
}
add_action('plugins_loaded', 'btw_importer_include_files');