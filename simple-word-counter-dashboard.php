<?php
/**
 * Plugin Name: Simple Word Counter Dashboard
 * Plugin URI:  https://github.com/leonmsaia/simple-word-counter-dashboard
 * Description: Adds a dashboard widget that displays the total word count for each public post type (Posts, Pages, etc.) in WordPress.
 * Version:     1.0.0
 * Author:      Leon M. Saia
 * Author URI:  https://github.com/leonmsaia
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-word-counter-dashboard
 */

add_action('wp_dashboard_setup', 'swcd_add_dashboard_widget');
add_filter('manage_post_posts_columns', 'swcd_add_word_count_column');
add_action('manage_post_posts_custom_column', 'swcd_display_word_count_column', 10, 2);
add_filter('manage_edit-post_sortable_columns', 'swcd_make_word_count_sortable');
add_action('pre_get_posts', 'swcd_order_by_word_count_column');
add_action('save_post', 'swcd_save_word_count_meta');

/**
 * Adds the dashboard widget.
 */
function swcd_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'swcd_widget_word_counter',
        esc_html__('📊 Word Count Overview', 'simple-word-counter-dashboard'),
        'swcd_display_word_count_widget'
    );
}

/**
 * Displays the word count widget in the dashboard.
 */
function swcd_display_word_count_widget() {
    $post_types = get_post_types(['public' => true], 'objects');

    echo '<ul style="padding-left: 1.5em;">';

    foreach ($post_types as $type) {
        $total_words = swcd_count_words_by_type($type->name);
        echo '<li><strong>' . esc_html($type->labels->name) . ':</strong> ' . esc_html($total_words) . ' words</li>';
    }

    echo '</ul>';
}

/**
 * Counts words for all published posts of a given type.
 */
function swcd_count_words_by_type($type) {
    $args = [
        'post_type'      => $type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $posts = get_posts($args);
    $total = 0;

    foreach ($posts as $post_id) {
        $content = get_post_field('post_content', $post_id);
        $words = str_word_count(wp_strip_all_tags($content));
        $total += $words;
    }

    return $total;
}

/**
 * Adds the "Words" column in the post list table.
 */
function swcd_add_word_count_column($columns) {
    $columns['swcd_word_count'] = esc_html__('Words', 'simple-word-counter-dashboard');
    return $columns;
}

/**
 * Displays the word count for each post in the custom column.
 */
function swcd_display_word_count_column($column_name, $post_id) {
    if ($column_name === 'swcd_word_count') {
        $count = get_post_meta($post_id, '_swcd_word_count', true);
        echo esc_html($count ? $count : '0');
    }
}

/**
 * Saves word count to post meta when a post is saved.
 */
function swcd_save_word_count_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(wp_strip_all_tags($content));
    update_post_meta($post_id, '_swcd_word_count', $word_count);
}

/**
 * Makes the "Words" column sortable.
 */
function swcd_make_word_count_sortable($columns) {
    $columns['swcd_word_count'] = 'swcd_word_count';
    return $columns;
}

/**
 * Modifies the query to sort by word count.
 */
function swcd_order_by_word_count_column($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('orderby') === 'swcd_word_count') {
        $query->set('meta_key', '_swcd_word_count');
        $query->set('orderby', 'meta_value_num');
    }
}
