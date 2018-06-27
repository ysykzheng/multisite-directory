<?php
/**
 * Convenience functions and templating wrappers.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2016 TK-TODO
 *
 * @package WordPress\Plugin\Multisite_Directory
 */
if (!defined('ABSPATH')) { exit(); } // no direct HTTP access

if (!function_exists('get_directory_blog_id')) :
    /**
     * Gets the ID for the site ("blog") housing the current network's Multisite Directory information.
     *
     * This function relies on the WP-Multi-Network plugin's presence.
     *
     * @return int
     */
    function get_directory_blog_id () {
        if (function_exists('get_main_site_for_network')) {
            $id = get_main_site_for_network();
        } else {
            $id = 1; // not a multi-network so main site will have ID 1
        }
        return $id;
    }
endif;

if (!function_exists('get_site_directory_terms')) :
    /**
     * Gets all categories in the site directory.
     *
     * @param array $args
     *
     * @uses get_terms()
     *
     * @return array|false|WP_Error
     */
    function get_site_directory_terms ($args = array()) {
        switch_to_blog(get_directory_blog_id());
        $terms = get_terms(wp_parse_args($args, array(
            'taxonomy'   => Multisite_Directory_Taxonomy::name,
            'hide_empty' => false,
        )));
        restore_current_blog();
        return $terms;
    }
endif;

if (!function_exists('get_site_directory_location_terms')) :
    /**
     * Gets all categories in the site directory that have location metadata.
     *
     * @param array $args
     *
     * @uses get_terms()
     *
     * @return array|false|WP_Error
     */
    function get_site_directory_location_terms ($args = array()) {
        switch_to_blog(get_directory_blog_id());
        $terms = get_terms(wp_parse_args($args, array(
            'taxonomy'   => Multisite_Directory_Taxonomy::name,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'geo',
                )
            ),
        )));
        restore_current_blog();
        return $terms;
    }
endif;

if (!function_exists('get_site_terms')) :
    /**
     * Gets the categories assigned to a given blog in the network directory.
     *
     * @param int $blog_id Optional. The ID of the site in question. Default is the blog ID of the current directory entry.
     *
     * @uses get_the_terms()
     *
     * @return array|false|WP_Error
     */
    function get_site_terms ($blog_id = 0) {
        $cpt = new Multisite_Directory_Entry();
        if (!$blog_id) {
            global $post;
            $blog_id = $post->{$cpt::blog_id_meta_key};
        }

        switch_to_blog(get_directory_blog_id());
        $posts = $cpt->get_posts(array(
            'meta_key'  => $cpt::blog_id_meta_key,
            'meta_value' => $blog_id,
            'post_status' => 'any',
        ));
        $terms = get_the_terms(array_pop($posts), Multisite_Directory_Taxonomy::name);
        restore_current_blog();

        return $terms;
    }
endif;

if (!function_exists('get_sites_in_directory_by_term')) :
    /**
     * Retrieves the details of sites in the directory assigned the given term.
     *
     * @param WP_Term $term
     * @param array $args
     *
     * @return array
     */
    function get_sites_in_directory_by_term ($term, $args = array()) {
        $args = wp_parse_args($args, array(
            'numberposts' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'id',
                    'terms' => array($term->term_id),
                ),
            ),
        ));
        $cpt = new Multisite_Directory_Entry();
        $posts = $cpt->get_posts($args);
        $details = array();
        switch_to_blog(get_directory_blog_id());
        foreach ($posts as $post) {
            $details[] = get_blog_details($post->{$cpt::blog_id_meta_key});
        }
        restore_current_blog();
        return $details;
    }
endif;

if (!function_exists('get_site_directory_logo')) :
    /**
     * Gets HTML for the site's custom logo or the site directory entry's featured image, if it has one.
     *
     * @uses get_the_post_thumbnail()
     *
     * @param int $blog_id Optional. The ID of the site whose logo to get. Default is the blog ID of the current directory entry.
     * @param string|int[] $size
     * @param string|string[] $attr
     *
     * @return string
     */
    function get_site_directory_logo ($blog_id, $size = 'post-thumbnail', $attr = '') {
        $cpt = new Multisite_Directory_Entry();
        if (!$blog_id) {
            global $post;
            $blog_id = $post->{$cpt::blog_id_meta_key};
        }

        switch_to_blog(get_directory_blog_id());
        $posts = $cpt->get_posts(array(
            'meta_key'  => $cpt::blog_id_meta_key,
            'meta_value' => $blog_id,
            'post_status' => 'any',
        ));
        $html = get_the_post_thumbnail(array_pop($posts), $size, $attr);
        restore_current_blog();

        if (empty($html) && function_exists('the_custom_logo')) {
            // No post thumbnail, so use the site's custom logo.
            return get_custom_logo($blog_id);
        } else {
            return $html;
        }
    }
endif;

if (!function_exists('the_site_directory_logo')) :
    /**
     * Prints the site's custom logo or the site directory entry's featured image, if it has one.
     *
     * @uses get_the_post_thumbnail()
     *
     * @param int $blog_id Optional. The ID of the site whose logo to get. Default is the blog ID of the current directory entry.
     * @param string|int[] $size
     * @param string|string[] $attr
     *
     * @return void
     */
    function the_site_directory_logo ($blog_id = 0, $size = 'post-thumbnail', $attr = '') {
        print get_site_directory_logo($blog_id, $size, $attr);
    }
endif;

if (!function_exists('get_site_permalink')) :
    /**
     * Gets the URL of the site.
     *
     * @param int $blog_id Optional. The ID of the site in question. Default is the blog ID of the current directory entry.
     * @param string $scheme Optional. Scheme to give the site URL context. Accepts 'http', 'https', 'login', 'login_post', 'admin', or 'relative'.
     *
     * @return string
     */
    function get_site_permalink ( $blog_id = 0, $scheme = null ) {
        $cpt = new Multisite_Directory_Entry();
        if ( ! $blog_id ) {
            global $post;
            $blog_id = $post->{$cpt::blog_id_meta_key};
        }

        return get_site_url( $blog_id, '', $scheme );
    }
endif;

if (!function_exists('the_site_permalink')) :
    /**
     * Prints the URL of the site.
     *
     * @param int $blog_id Optional. The ID of the site in question. Default is the blog ID of the current directory entry.
     */
    function the_site_permalink ($blog_id = 0) {
        print get_site_permalink($blog_id);
    }
endif;
