<?php
if (!defined('ABSPATH')) exit;

class btw_importer_Redirect_Log {
    public function __construct() {
        add_action('admin_menu', [$this, 'btw_importer_add_redirect_log_menu']);
        add_action('admin_init', [$this, 'btw_importer_handle_clear_log']);
    }

    public function btw_importer_add_redirect_log_menu() {
        add_submenu_page(
            'btw-importer',
            'Redirect Log',
            'Redirect Log',
            'manage_options',
            'btw-redirect-log',
            [$this, 'btw_importer_render_redirect_log_page']
        );
    }

    public function btw_importer_handle_clear_log() {
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

    public function btw_importer_render_redirect_log_page() {
    global $wpdb;

    // Get and sanitize inputs
    $search  = sanitize_text_field((string) filter_input(INPUT_GET, 's', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $paged   = max(1, (int) filter_input(INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT));
    $orderby = sanitize_sql_orderby((string) filter_input(INPUT_GET, 'orderby'));
    $order   = (strtoupper((string) filter_input(INPUT_GET, 'order')) === 'ASC') ? 'ASC' : 'DESC';
    $post_type_filter = sanitize_text_field((string) filter_input(INPUT_GET, 'post_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    $allowed_orderby = ['post_date', 'post_type'];
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'post_date';
    }

    $per_page = 25;
    $offset   = ($paged - 1) * $per_page;

    // Get distinct post types for filter dropdown
    $post_types = $wpdb->get_col( $wpdb->prepare("SELECT DISTINCT p.post_type FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s ORDER BY p.post_type", '_btw_importer_old_permalink') );

    echo '<div class="wrap">';
    echo '<h1>Redirect Log</h1>';
    echo '<p>This table shows old Blogger slugs and the new WordPress URLs that have been created as redirects.</p>';

    $clear_nonce = wp_create_nonce('btw_importer_clear_log');

    // Search + filter form
    echo '<form method="get" style="margin-bottom:10px; display:inline-block; margin-right:10px;">
            <input type="hidden" name="page" value="btw-redirect-log" />
            <input type="search" name="s" placeholder="Search slug..." value="' . esc_attr($search) . '" />
            <select name="post_type">
                <option value="">All Post Types</option>';
    foreach ($post_types as $type) {
        echo '<option value="' . esc_attr($type) . '" ' . selected($post_type_filter, $type, false) . '>' . esc_html($type) . '</option>';
    }
    echo '</select>
            <input type="submit" class="button" value="Filter" />
          </form>';

    echo '<form method="post" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to clear the entire redirect log?\');">
            <input type="hidden" name="btw_importer_clear_log_nonce" value="' . esc_attr($clear_nonce) . '" />
            <input type="submit" class="button button-danger" value="Clear Log" />
          </form>';

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orderby and $order are whitelisted (ASC/DESC, allowed columns only)
    $allowed_orderby = ['post_date', 'post_type'];
    if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
        $orderby = 'post_date';
    }

    $order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';
    $results = $wpdb->get_results(
        $wpdb->prepare( 
            "
            SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_type, p.post_date, pm.meta_value as old_slug
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            ORDER BY p.{$orderby} {$order} 
            LIMIT %d OFFSET %d
            ",
            '_btw_importer_old_permalink',
            $per_page,
            $offset 
        )
    );

    $total_items = (int) $wpdb->get_var("SELECT FOUND_ROWS()");

    if (!$results) {
        echo '<p>No redirects found.</p>';
    } else {
        // Sortable headers
        $base_url = admin_url('admin.php?page=btw-redirect-log');
        if ($search) {
            $base_url = add_query_arg('s', urlencode($search), $base_url);
        }
        if ($post_type_filter) {
            $base_url = add_query_arg('post_type', urlencode($post_type_filter), $base_url);
        }

        $columns = [
            'post_date' => 'Date',
            'post_type' => 'Post Type',
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
                    'post_type' => $post_type_filter,
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
