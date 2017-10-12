<?php
/**
 * Stick Posts To Term
 *
 * Simple tern-specific sticky posts
 *
 * @package   Stick_Posts_To_Term
 * @author    Joe Buckle <joe@white-fire.co.uk>
 * @license   GPL-2.0+
 * @link      http://joebuckle.me
 * @copyright 2016 Joe Buckle
 *
 * @wordpress-plugin
 * Plugin Name: Stick Posts To Tern
 * Plugin URI: 	http://joebuckle.me
 * Description: Simple term-specific sticky posts
 * Version:     1.0.3
 * Author:      Joe Buckle
 * Author URI:  http://joebuckle.me
 * Text Domain: stick-posts-to-term
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if(!class_exists('StickyPost')) {
    include_once __DIR__ . '/src/StickyPost.php';
}

if(!class_exists('StickyPost_Admin')) {
    include_once __DIR__ . '/src/StickyPost_Admin.php';
}

add_action( 'plugins_loaded', array( 'StickyPost', 'get_instance' ) );