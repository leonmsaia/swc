<?php
/**
 * Plugin Name: Simple Word Counter Dashboard
 * Plugin URI:  https://github.com/leonmsaia/swc
 * Description: Adds a dashboard widget that displays the total word count for each public post type (Posts, Pages, etc.) in WordPress.
 * Version:     1.0.0
 * Author:      Leon M. Saia
 * Author URI:  https://github.com/leonmsaia
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-word-counter-dashboard
 */

add_action('wp_dashboard_setup', 'swcd_add_dashboard_widget');

function swcd_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'swcd_widget_word_counter',
        '📊 Word Count Overview',
        'swcd_display_word_count_widget'
    );
}

function swcd_display_word_count_widget() {
    $post_types = get_post_types(['public' => true], 'objects');
    echo "<ul style='padding-left: 1.5em;'>";
    
    foreach ($post_types as $type) {
        $total_words = swcd_count_words_by_type($type->name);
        echo "<li><strong>{$type->labels->name}:</strong> {$total_words} words</li>";
    }
    
    echo "</ul>";
}

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
        $words = str_word_count(strip_tags($content));
        $total += $words;
    }

    return $total;
}
