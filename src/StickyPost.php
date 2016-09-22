<?php

class StickyPost {

    private static $instance            = null;

    private static $disabled_tax        = array('post_tag', 'format', 'tag', 'type');

    private static $disabled_post_types = array('attachment');

    private static $post_states         = array(
        'category_sticky'   => 'Category Sticky'
    );

    public static function allowed() {
        $allowed = get_option('category-sticky-allowed');
        if($allowed) {
            return $allowed;
        }

        return array();
    }

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

        if( ! is_admin() ) {
            return false;
        }

        add_action( 'add_meta_boxes',           array( $this, 'add_meta_box'));
        add_action( 'save_post',                array( $this, 'save_meta_box'));

        add_filter( 'display_post_states',      array( $this, 'post_state'));

        add_action( 'restrict_manage_posts',    array( $this, 'filter'));
        add_filter( 'parse_query',              array( $this, 'sort'));


        // Settings page
        add_action('admin_menu', function() {
            add_submenu_page('options-general.php', 'Category Stickies', 'Category Stickies', 'manage_options', 'manage-stickies', array($this, 'settings'));
        });
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


        $taxonomies = get_taxonomies();

        foreach($taxonomies as $k => $v) {

            if(!in_array($k, self::$disabled_tax)) {

                $tax = get_taxonomy($v);

                /**
                 * Check first that terms are actually assigned
                 * in any taxonomy
                 */
                if (is_array($terms = get_the_terms($post->ID, $tax->name))) {
                    $output .= '<h4>' . $tax->label . '</h4>';

                    $output .= '<select autocomplete="off" name="category_stickies[' . $tax->name . ']">';
                    $output .= '<option value="0">' . __('Select a ' . $tax->name . '...', 'stick-post-to-category') . '</option>';

                    foreach ($terms as $term) {
                        $output .= '<option value="' . $term->term_id . '" ' . selected(get_post_meta($post->ID, 'category_stickies_' . $tax->name, true), $term->term_id, false) . '>';
                        $output .= $term->name;
                        $output .= '</option>';
                    }

                    $output .= '</select>';
                }
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

    /**
     * @param $post_states
     * Added 0.0.2
     * Displays the post state in the admin posts table
     */
    public function post_state($post_states) {

        $post_states    = array_merge($post_states, self::$post_states);
        $post_id        = get_the_ID();

        if(!empty($post_states)) {
            foreach($post_states as $key=>&$state) {
                switch ($key) {
                    case 'sticky' :
                        echo '<br /><span class="dashicons dashicons-sticky"></span> home';
                        break;

                    case 'category_sticky' :
                        $taxonomies = get_taxonomies();
                        foreach($taxonomies as $k => $v) {

                            if(!in_array($k, self::$disabled_tax)) {
                                $tax    = get_taxonomy($v);
                                $sticky = get_post_meta($post_id, 'category_stickies_' . $tax->name, true);
                                $term   = get_term_by('id', absint($sticky), $tax->name);
                                if ($sticky) {
                                    echo '<br /><span class="dashicons dashicons-sticky"></span> ' . $tax->name . ' | ' . $term->name;
                                }
                            }
                        }
                        break;
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
    public static function query($tax, $term, $post_types=false, $count=false) {

        if(!$post_types) {
            $post_types = array_keys(self::allowed());
        }
        
        if(!$count) {
            $count = 1;
        }

        $args = array(
            'post_status'           => 'publish',
            'post_type'             => $post_types,
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


    /**
     * Post admin table filter
     */
    public function filter() {
        global $typenow;
        $select = array();
        $allowed = self::allowed();

        if (in_array($typenow, array_keys($allowed))) {

            $taxonomies = get_taxonomies();

            $i = 0;
            foreach($taxonomies as $k => $v) {

                if (isset($allowed[$typenow]) && in_array($k, $allowed[$typenow])) {

                    $tax    = get_taxonomy($v);
                    $terms  = get_terms(array(
                        'taxonomy' => $tax->name
                    ));
                    $select[$i]['optgroup']     = $tax->name;
                    $select[$i]['values']       = array();
                    foreach($terms as $term) {
                        $select[$i]['values'][$term->name]    = $term->term_id;
                    }

                    $i++;
                }
            }

            ?>
            <select name="filter-sticky">
                <option value="">Sticky</option>
                <?php
                $filter_sticky = explode(';', urldecode($_GET['filter-sticky']));

                foreach($select as $group) {
                    echo '<optgroup label="'.$group['optgroup'].'">';
                    foreach ($group['values'] as $label => $value) {
                        printf(
                            '<option value="%s"%s>%s</option>',
                            $group['optgroup'].';'.$value,
                            $value == $filter_sticky[1]? ' selected="selected"':'',
                            $label
                        );
                    }
                }

                ?>
            </select>
            <?php
        }
    }


    /**
     * @param $query
     * Custom sort by taxonomy for post table
     */
    public function sort($query) {

        global $typenow;
        global $pagenow;

        if(in_array($typenow, array_keys(self::allowed()))) {
            $q_vars    = &$query->query_vars;

            if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && isset($_GET['filter-sticky'])) {

                $filter_sticky = explode(';', urldecode($_GET['filter-sticky']));

                $q_vars['meta_query'] = array(
                    array(
                        'key'       =>  'category_stickies_' . $filter_sticky[0],
                        'value'     =>  $filter_sticky[1],
                        'compare'   => 'IN'
                    )
                );
            }
        }
    }

    /**
     * The settings page
     */
    public function settings() {
        echo '<h1>Category stickies settings</h1>';

        if(isset($_POST['category-sticky-allowed'])) {
            update_option('category-sticky-allowed', $_POST['category-sticky-allowed']);
        }

        $allowed = get_option('category-sticky-allowed');

        echo '<form method="post" action="">';
        $types = get_post_types(array(
            'public'                => true,
            'publicly_queryable'    => true
        ));

        foreach($types as $type) {
            if(!in_array($type, self::$disabled_post_types)) {
                echo '<ul>';
                echo '<li><label><b>' . $type . '</b></label></li>';
                $taxonomy_objects = get_object_taxonomies( $type, 'objects' );
                if($taxonomy_objects) {
                    echo '<ul>';
                    foreach($taxonomy_objects as $tax) {
                        $checked = '';
                        if(isset($allowed[$type]) && in_array($tax->rewrite['slug'], $allowed[$type])) {
                            $checked = 'checked';
                        }
                        if(!in_array($tax->rewrite['slug'], self::$disabled_tax)) {
                            echo '<li><label><input type="checkbox" name="category-sticky-allowed['.$type.'][]" value="'.$tax->rewrite['slug'].'" '.$checked.'/>' . $tax->label . '</li>';
                        }
                    }
                    echo '</ul>';
                }

                echo '</ul>';
            }
        }

        echo '<button type="submit">Save</button>';
        echo '</form>';

    }


    /**
     * A handy function that gets all stickies available
     * 
     * @param string $type
     * @param bool $tax
     * @param bool $term_id
     * @return array|bool|WP_Query
     */
    public static function get_stickies($type='ids', $tax = false, $term_id = false) {
        global $wpdb;

        $output     = false;
        $meta_key   = "category_stickies_%";
        $meta_val   = "";
        
        if($tax) {
            $meta_key = "category_stickies_" . $tax;
        }
        
        if($term_id) {
            $meta_val = "AND meta_value=%s";
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE %s $meta_val", $meta_key, $term_id
            ), OBJECT
        );

        $post_ids = array();
        foreach($results as $result) {
            foreach($result as $k => $v) {
                $post_ids[] = $v;
            }
        }


        if($type=='ids') {
            $output = $post_ids;
        }

        if($type=='query') {
            $output = new WP_Query(array(
                'post_status' => 'publish',
                'post__in' => $post_ids
            ));
        }

        return $output;
    }

    public static function theme_activate() {
        add_action('init', 'StickyPost::get_instance');
    }

}