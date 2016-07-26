<?php
/**
 * Stick Post To Category
 *
 * Simple category-specific sticky posts
 *
 * @package   Stick_Post_To_Category
 * @author    Joe Buckle <joe@white-fire.co.uk>
 * @license   GPL-2.0+
 * @link      http://joebuckle.me
 * @copyright 2016 Joe Buckle
 *
 * @wordpress-plugin
 * Plugin Name: Stick Post To Category
 * Plugin URI: 	http://joebuckle.me
 * Description: Simple category-specific sticky posts
 * Version:     0.0.1
 * Author:      Joe Buckle
 * Author URI:  http://joebuckle.me
 * Text Domain: stick-post-to-category
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class StickyPost {

    private static $instance = null;

    /**
     * Method used to provide a single instance of this
     *
     * @since    0.0.1
     */
    public static function get_instance() {

        if( null == self::$instance ) {
            self::$instance = new StickyPost();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes',   array( $this, 'add_meta_box' ) );
        add_action( 'save_post',        array( $this, 'save_meta_box' ) );
    }

    public function add_meta_box() {
        $post_types = get_post_types( '', 'names' );

        foreach( $post_types as $post_type ) {

            if ( 'page' === $post_type ) {
                continue;
            } // end if

            add_meta_box(
                'post_is_sticky',
                __( 'Category Sticky', 'stick-post-to-category' ),
                array( $this, 'display_meta_box' ),
                $post_type,
                'side',
                'high'
            );

        }
    }

    public function display_meta_box($post) {

        wp_nonce_field( plugin_basename( __FILE__ ), 'stick_post_to_category_nonce' );

        $output = '<p>Note: You can only stick posts to terms you have assigned to them.</p>';

        /**
         * Get available taxonomies
         */
        $taxonomies = get_taxonomies();
        foreach($taxonomies as $k => $v) {
            $tax = get_taxonomy($v);

            /**
             * Check first that terms are actually assigned
             * in any taxonomy
             */
            if (is_array($terms = get_the_terms($post->ID, $tax->name))) {
                $output .= '<h4>' . $tax->label . '</h4>';

                $output .= '<select autocomplete="off" name="category_stickies['.$tax->name.']">';
                $output .= '<option value="0">' . __('Select a '.$tax->name.'...', 'stick-post-to-category') . '</option>';

                foreach ($terms as $term) {
                    $output .= '<option value="' . $term->term_id . '" ' . selected(get_post_meta($post->ID, 'category_stickies_' . $tax->name, true), $term->term_id, false) . '>';
                    $output .= $term->name;
                    $output .= '</option>';
                }

                $output .= '</select>';
            }
        }

        echo $output;
    }

    public function save_meta_box($post_id) {

        if( isset( $_POST['stick_post_to_category_nonce'] ) && isset( $_POST['post_type'] ) && $this->user_can_save( $post_id, 'stick_post_to_category_nonce' ) ) {
            if(isset($_POST['category_stickies'])) {
                foreach($_POST['category_stickies'] as $k => $v) {
                    if($v==0) {
                        delete_post_meta($post_id, 'category_stickies_' . $k);
                    } else {
                        update_post_meta($post_id, 'category_stickies_' . $k, $v);
                    }
                }
            }
        }
    }


    private function user_can_save( $post_id, $nonce ) {

        $is_autosave = wp_is_post_autosave( $post_id );
        $is_revision = wp_is_post_revision( $post_id );
        $is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) );

        // Return true if the user is able to save; otherwise, false.
        return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;

    }

    /**
     * @param $tax = string
     * @param $term = id
     * @param bool/int $count
     * @return WP_Query
     */
    public static function query($tax, $term, $count=false) {

        if(!$count) {
            $count = 1;
        }

        $args = array(
            'post_status'           => 'publish',
            'post_type'             => 'post',
            'posts_per_page'        => $count,
            'ignore_sticky_posts'   => true,
            'meta_query'        => array(
                array(
                    'key'       =>  'category_stickies_' . $tax,
                    'value'     =>  $term,
                    'compare'   => 'IN'
                )
            )
        );

        global $object;
        $object = new stdClass();
        $object->query = new WP_Query($args);
        $object->post_ids = wp_list_pluck( $object->query->posts, 'ID' );
        
        
        return $object;
    }

}


add_action( 'plugins_loaded', array( 'StickyPost', 'get_instance' ) );