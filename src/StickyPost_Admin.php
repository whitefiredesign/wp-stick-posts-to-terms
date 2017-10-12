<?php
/**
 * Responsible for the Admin UI
 * Class StickyPost_Admin
 */

class StickyPost_Admin extends StickyPost {

    private $slug = 'stick-posts-to-term';

    public function __construct() {
        if(is_admin()) {

            parent::__construct();

            add_action( 'admin_menu', function() {

                if(class_exists('Fuse\\config')) {
                    add_submenu_page(Fuse\config::$slug, 'Sticky Posts', 'Sticky Posts', 'manage_options', $this->slug, function () {
                        include_once(__DIR__ . '/views/admin/settings.php');
                    });
                }

            });
        }

        return false;
    }

    /**
     * Method for Purging stickies
     * @param bool $type
     * @return bool
     * @throws mixed
     */
    private function purge_stickies($type = false) {
        global $wpdb;

        if(!$type) {
            return false;
        }
        
        if($type=='terms') {
            $wpdb->query( "DELETE FROM " . $wpdb->postmeta . " WHERE meta_key LIKE 'category_stickies_%'");

            if($wpdb->last_error !== '') {
                throw $wpdb->print_error;
            }
        }

        if($type=='home') {
            delete_option('sticky_posts');
        }

        return false;
        
    }
}

new StickyPost_Admin();