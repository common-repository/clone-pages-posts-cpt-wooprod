<?php
/*
Plugin Name: Clone Pages/Posts/CPT/Woo Products
Description: Clones pages, posts, custom post types, and WooCommerce products.
Version: 1.0.1
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: MN Shariff
Author URI: https://mnshariff.com
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add clone button to post/page row actions
function mnsp_clone_post_link($actions, $post) {
    if ($post->post_type != 'attachment') {
        $actions['clone'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=mnsp_clone_post_as_draft&post=' . $post->ID), basename(__FILE__), 'mnsp_clone_nonce') . '" title="Clone this item" rel="permalink">Clone</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'mnsp_clone_post_link', 10, 2);
add_filter('page_row_actions', 'mnsp_clone_post_link', 10, 2);

// Clone post action
function mnsp_clone_post_as_draft() {
    if (!isset($_GET['post']) || !isset($_GET['mnsp_clone_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['mnsp_clone_nonce'])), basename(__FILE__))) {
        return;
    }

    $post_id = intval($_GET['post']);
    $post = get_post($post_id);

    if (empty($post)) {
        return;
    }

    $new_post_author = isset($post->post_author) ? $post->post_author : '';
    $new_post_content = isset($post->post_content) ? $post->post_content : '';
    $new_post_content_filtered = isset($post->post_content_filtered) ? $post->post_content_filtered : '';
    $new_post_excerpt = isset($post->post_excerpt) ? $post->post_excerpt : '';
    $new_post_status = 'draft';
    $new_post_title = isset($post->post_title) ? $post->post_title . ' (Cloned)' : '';
    $new_post_type = isset($post->post_type) ? $post->post_type : '';
    $new_post_parent = isset($post->post_parent) ? $post->post_parent : '';

    $new_post_args = array(
        'post_author'           => $new_post_author,
        'post_content'          => $new_post_content,
        'post_content_filtered' => $new_post_content_filtered,
        'post_excerpt'          => $new_post_excerpt,
        'post_status'           => $new_post_status,
        'post_title'            => $new_post_title,
        'post_type'             => $new_post_type,
        'post_parent'           => $new_post_parent
    );

    $new_post_id = wp_insert_post($new_post_args);

    if ($new_post_id) {
        $post_taxonomies = get_object_taxonomies($post->post_type);
        foreach ($post_taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }
    }

    wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
    exit;
}
add_action('admin_action_mnsp_clone_post_as_draft', 'mnsp_clone_post_as_draft');
