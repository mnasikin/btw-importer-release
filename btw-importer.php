    <?php
    /*
    Plugin Name:        BtW Importer
    Plugin URI:         https://github.com/mnasikin/btw-importer-release
    Description:        Simple yet powerful plugin to migrate Blogger to WordPress in one click. Import .atom from Google Takeout, scan & download first image, replace URLs, set featured image, and show live progress.
    Version:            1.0.0
    Author:             Nasikin
    License:            MIT
    Network:            true
    Requires PHP:       7.4
    */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Btw_Importer {

        public function __construct() {
            // Register menu and scripts
            add_action( 'admin_menu', array( $this, 'btw_importer_add_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'btw_importer_enqueue_scripts' ) );
            add_action( 'wp_ajax_btw_importer_prepare_import', array( $this, 'btw_importer_ajax_prepare_import' ) );
            add_action( 'wp_ajax_btw_importer_import_single_post', array( $this, 'btw_importer_ajax_import_single_post' ) );
        }

        public function btw_importer_add_menu() {
            add_menu_page(
                'BtW Importer',
                'BtW Importer',
                'manage_options',
                'btw_importer',
                array( $this, 'btw_importer_import_page' ),
                'dashicons-upload'
            );
        }

        public function btw_importer_enqueue_scripts( $hook ) {
            if ( 'toplevel_page_btw_importer' !== $hook ) {
                return;
            }
            wp_enqueue_script('btw_importer_script', plugin_dir_url(__FILE__) . 'btw-importer.js', array('jquery'), '1.0', true);
            wp_localize_script('btw_importer_script', 'btw_importer_data', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('btw_importer_nonce'),
            ));
        }

        public function btw_importer_import_page() {
            // Only admins
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Insufficient permissions' );
            }
            echo '<div class="wrap">
                <h1>BtW Import Blogger .atom</h1>
                <input type="file" id="atomFile" accept=".xml,.atom" />
                <button id="startImport" class="button button-primary">Start Import</button>
                <div id="progress" style="margin-top:20px; max-height:400px; overflow:auto; background:#fff; padding:10px; border:1px solid #ddd;"></div>
            </div>';
        }

        public function btw_importer_ajax_prepare_import() {
            // Only admins
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( 'Unauthorized' );
            }
            check_ajax_referer('btw_importer_nonce', 'nonce');

            // Retrieve raw XML without sanitizing tags
            $raw_input = filter_input(INPUT_POST, 'atom_content', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
            $raw_input = null === $raw_input ? '' : wp_unslash($raw_input);
            $raw_input = preg_replace('/^\x{FEFF}/u', '', $raw_input);
            $raw_input = preg_replace('/[^\P{C}\n\r\t]+/u', '', $raw_input);

            if ( empty($raw_input) ) {
                wp_send_json_error('No data received.');
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($raw_input);
            if (false === $xml) {
                $errors = libxml_get_errors();
                $messages = array_map(function($e){ return trim($e->message); }, $errors);
                libxml_clear_errors();
                wp_send_json_error('XML parse errors: ' . implode('; ', $messages));
            }

            $namespaces = $xml->getNamespaces(true);
            $entries    = $xml->entry;
            if (empty($entries) && isset($namespaces['atom'])) {
                $xml->registerXPathNamespace('a', $namespaces['atom']);
                $entries = $xml->xpath('//a:entry');
            }

            $posts = array();
            foreach ($entries as $entry) {
                $title    = (string) $entry->title;
                $content  = isset($entry->content) ? (string) $entry->content : (string) $entry->summary;
                $dateStr  = isset($entry->published) ? (string) $entry->published : (string) $entry->updated;
                $author   = isset($entry->author) ? (string) $entry->author->name : '';

                $posts[] = array(
                    'title'   => sanitize_text_field($title),
                    'content' => wp_kses_post($content),
                    'date'    => sanitize_text_field(date_i18n('Y-m-d H:i:s', strtotime($dateStr))),
                    'author'  => sanitize_text_field($author),
                );
            }

            wp_send_json_success(array('posts' => $posts));
        }

        public function btw_importer_ajax_import_single_post() {
            // Only admins
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( 'Unauthorized' );
            }
            check_ajax_referer('btw_importer_nonce', 'nonce');

            $raw_posts = filter_input(INPUT_POST, 'post', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $raw_posts = is_array($raw_posts) ? array_map('wp_unslash', $raw_posts) : array();
            $sanitized = array_map('sanitize_text_field', $raw_posts);

            if (empty($sanitized)) {
                wp_send_json_error('Missing post data.');
            }

            $title       = $sanitized['title'] ?? '';
            $raw_content = $raw_posts['content'] ?? '';
            $date        = $sanitized['date'] ?? '';
            $author      = $sanitized['author'] ?? '';

            $msgs = array('📄 Importing post: ' . esc_html($title));
            $author_id = 1;
            if ($author) {
                $user = get_user_by('login', sanitize_user($author, true));
                if ($user) {
                    $author_id = $user->ID;
                }
            }

            if (!function_exists('media_handle_sideload')) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }

            $post_id = wp_insert_post(array(
                'post_title'   => $title,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_date'    => $date,
                'post_author'  => $author_id,
            ));

            if (is_wp_error($post_id)) {
                wp_send_json_error('❌ Failed to insert post: ' . $title);
            }

            preg_match_all('/https?:\/\/[^"\']+\.(jpg|jpeg|png|gif|webp|bmp|svg|tiff|avif|ico)/i', $raw_content, $matches);
            $urls = array_unique($matches[0]);
            if (!empty($urls)) {
                $first = $urls[0];
                $msgs[] = '⏳ Downloading image: ' . esc_url($first);
                $tmp    = download_url($first);
                if (is_wp_error($tmp)) {
                    $msgs[] = '⚠ Failed to download image';
                } else {
                    $desc = basename(wp_parse_url($first, PHP_URL_PATH));
                    $file = array('name' => $desc, 'tmp_name' => $tmp);
                    $mid  = media_handle_sideload($file, $post_id);
                    if (is_wp_error($mid)) {
                        wp_delete_file($tmp);
                        $msgs[] = '⚠ Failed to sideload image';
                    } else {
                        $new = wp_get_attachment_url($mid);
                        foreach ($urls as $old) {
                            $raw_content = str_replace($old, $new, $raw_content);
                            $msgs[]      = '✅ Replaced: ' . esc_url($old);
                        }
                        set_post_thumbnail($post_id, $mid);
                        $msgs[] = '⭐ Featured image set';
                    }
                }
            }

            $content = wp_kses_post($raw_content);
            wp_update_post(array('ID' => $post_id, 'post_content' => $content));

            $msgs[] = '✅ Completed: ' . esc_html($title);
            wp_send_json_success($msgs);
        }
    }

    new Btw_Importer();
