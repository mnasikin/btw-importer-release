<?php
if (!defined('ABSPATH')) exit;

class Btw_Importer_Redirect_Log {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_redirect_log_menu']);
        add_action('admin_init', [$this, 'handle_clear_log']);
    }

    public function add_redirect_log_menu() {
        add_submenu_page(
            'btw-importer',
            'Redirect Log',
            'Redirect Log',
            'manage_options',
            'btw-redirect-log',
            [$this, 'render_redirect_log_page']
        );
    }

    public function handle_clear_log() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['btw_importer_clear_log_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['btw_importer_clear_log_nonce'])), 'btw_importer_clear_log')) {
            global $wpdb;
            $wpdb->delete(
            $wpdb->postmeta,
            [ 'meta_key' => '_btw_importer_old_permalink' ],
            [ '%s' ]
        );
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Redirect log cleared successfully.', 'btw-importer') . '</p></div>';
            });
        }
    }

    public function render_redirect_log_page() {
    global $wpdb;

    // Get and sanitize inputs
    $search  = sanitize_text_field((string) filter_input(INPUT_GET, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $paged   = max(1, (int) filter_input(INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT));
    $orderby = sanitize_sql_orderby((string) filter_input(INPUT_GET, 'orderby'));
    $order   = (strtoupper((string) filter_input(INPUT_GET, 'order')) === 'ASC') ? 'ASC' : 'DESC';
    // Cache removed to fix SQL preparation error


    $allowed_orderby = ['p.post_date', 'p.post_type'];
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'p.post_date';
    }

    $per_page = 25;
    $offset   = ($paged - 1) * $per_page;

    echo '<div class="wrap">';
    echo '<h1>Redirect Log</h1>';
    echo '<p>This table shows old Blogger slugs and the new WordPress URLs that have been created as redirects.</p>';

    $clear_nonce = wp_create_nonce('btw_importer_clear_log');

    // Search + clear form
    echo '<form method="get" style="margin-bottom:10px; display:inline-block; margin-right:10px;">
            <input type="hidden" name="page" value="btw-redirect-log" />
            <input type="search" name="s" placeholder="Search slug..." value="' . esc_attr($search) . '" />
            <input type="submit" class="button" value="Search" />
          </form>';

    echo '<form method="post" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to clear the entire redirect log?\');">
            <input type="hidden" name="btw_importer_clear_log_nonce" value="' . esc_attr($clear_nonce) . '" />
            <input type="submit" class="button button-danger" value="Clear Log" />
          </form>';

    // Build query safely
    $params   = ['_btw_importer_old_permalink'];
    if ($search) {
        if ($orderby === 'p.post_date' && $order === 'ASC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s ORDER BY p.post_date ASC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', '%' . $wpdb->esc_like($search) . '%', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_date' && $order === 'DESC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s ORDER BY p.post_date DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', '%' . $wpdb->esc_like($search) . '%', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_type' && $order === 'ASC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s ORDER BY p.post_type ASC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', '%' . $wpdb->esc_like($search) . '%', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_type' && $order === 'DESC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s ORDER BY p.post_type DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', '%' . $wpdb->esc_like($search) . '%', $per_page, $offset]) );
        } else {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value LIKE %s ORDER BY p.post_date DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', '%' . $wpdb->esc_like($search) . '%', $per_page, $offset]) );
        }
    } else {
        if ($orderby === 'p.post_date' && $order === 'ASC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_date ASC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_date' && $order === 'DESC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_date DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_type' && $order === 'ASC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_type ASC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', $per_page, $offset]) );
        } elseif ($orderby === 'p.post_type' && $order === 'DESC') {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_type DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', $per_page, $offset]) );
        } else {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_date DESC LIMIT %d OFFSET %d", ['_btw_importer_old_permalink', $per_page, $offset]) );
        }
    }

    $total_items = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );



    if (!$results) {
        echo '<p>No redirects found.</p>';
    } else {
        // Sortable headers
        $base_url = admin_url('admin.php?page=btw-redirect-log');
        if ($search) {
            $base_url = add_query_arg('s', urlencode($search), $base_url);
        }

        $columns = [
            'p.post_date' => 'Date',
            'p.post_type' => 'Post Type',
        ];

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th width="45%">Old URL</th>';
        echo '<th>New URL</th>';
        foreach ($columns as $col => $label) {
            $new_order = ($orderby === $col && $order === 'ASC') ? 'DESC' : 'ASC';
            $link      = add_query_arg(['orderby' => $col, 'order' => $new_order, 'paged' => 1], $base_url);
            $arrow     = ($orderby === $col) ? ($order === 'ASC' ? '↑' : '↓') : '';
            echo '<th><a href="' . esc_url($link) . '">' . esc_html($label) . ' ' . esc_html($arrow) . '</a></th>';
        }
        echo '</tr></thead>';

        echo '<tbody>';
        foreach ($results as $row) {
            $old_url = esc_url(home_url($row->old_slug));
            $new_url = esc_url(get_permalink($row->ID));
            $date    = esc_html(gmdate('Y-m-d', strtotime($row->post_date)));
            $type    = esc_html($row->post_type);

            echo '<tr>';
            echo '<td><a href="' . esc_url($old_url) . '" target="_blank">' . esc_url($old_url) . '</a></td>';
            echo '<td><a href="' . esc_url($new_url) . '" target="_blank">' . esc_url($new_url) . '</a></td>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        // Pagination
        $total_pages = ceil($total_items / $per_page);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            $pagination = paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $paged,
                'total'     => $total_pages,
                'add_args'  => [
                    's'       => $search,
                    'orderby' => $orderby,
                    'order'   => $order,
                ],
                'prev_text' => esc_html__('« Prev', 'btw-importer'),
                'next_text' => esc_html__('Next »', 'btw-importer'),
            ]);
            if ($pagination) {
                echo wp_kses_post($pagination);
            }
            echo '</div></div>';
        }
    }

    echo '</div>';
}


}

new Btw_Importer_Redirect_Log();
