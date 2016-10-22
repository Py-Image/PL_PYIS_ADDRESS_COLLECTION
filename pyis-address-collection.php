<?php
/*
Plugin Name: PyImageSearch Address Collection
Plugin URL: 
Description: Send data from CognitoForms to Drip using WP as a middleman
Version: 1.0.0
Text Domain: pyis-address-collection
Author: Eric Defore
Author URI: http://realbigmarketing.com
Contributors: d4mation
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PyIS_Address_Collection' ) ) {

    /**
     * Main PyIS_Address_Collection class
     *
     * @since       1.0.0
     */
    class PyIS_Address_Collection {
        
        /**
         * @var         PyIS_Address_Collection $plugin_data Holds Plugin Header Info
         * @since       1.0.0
         */
        public $plugin_data;
        
        /**
         * @var         PyIS_Address_Collection $admin Admin Settings
         * @since       1.0.0
         */
        public $admin;
        
        /**
         * @var         PyIS_Address_Collection $rest REST Endpoints
         * @since       1.0.0
         */
        public $rest;

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true PyIS_Address_Collection
         */
        public static function instance() {
            
            static $instance = null;
            
            if ( null === $instance ) {
                $instance = new static();
            }
            
            return $instance;

        }
        
        protected function __construct() {
            
            $this->setup_constants();
            $this->load_textdomain();
            
            global $wp_settings_errors;
            
            // That's a descriptive class name! /s
            if ( ! class_exists( 'Semper_Fi_Module' ) ) {
                
                $this->admin_notices[] = sprintf( _x( '%s requires %s to be installed!', 'Missing Plugin Dependency Error', PyIS_Address_Collection_ID ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//www.learndash.com/" target="_blank"><strong>LearnDash</strong></a>' );
                
                if ( ! has_action( 'admin_notices', array( $this, 'admin_notices' ) ) ) {
                    add_action( 'admin_notices', array( $this, 'admin_notices' ) );
                }
                
				return;
                
            }
            
            if ( defined( 'LEARNDASH_VERSION' ) 
                && ( version_compare( LEARNDASH_VERSION, '2.2.1.2' ) < 0 ) ) {
                
                $this->admin_notices[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', PyIS_Address_Collection_ID ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '2.2.1.2', '<a href="//www.learndash.com/" target="_blank"><strong>LearnDash</strong></a>' );
                
                if ( ! has_action( 'admin_notices', array( $this, 'admin_notices' ) ) ) {
                    add_action( 'admin_notices', array( $this, 'admin_notices' ) );
                }
                
            }
            
            $this->require_necessities();
            
            // Register our CSS/JS for the whole plugin
            add_action( 'init', array( $this, 'register_scripts' ) );
            
        }

        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            
            // WP Loads things so weird. I really want this function.
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            
            // Only call this once, accessible always
            $this->plugin_data = get_plugin_data( __FILE__ );
            
            if ( ! defined( 'PyIS_Address_Collection_ID' ) ) {
                // Plugin Text Domain
                define( 'PyIS_Address_Collection_ID', $this->plugin_data['TextDomain'] );
            }

            if ( ! defined( 'PyIS_Address_Collection_VER' ) ) {
                // Plugin version
                define( 'PyIS_Address_Collection_VER', $this->plugin_data['Version'] );
            }

            if ( ! defined( 'PyIS_Address_Collection_DIR' ) ) {
                // Plugin path
                define( 'PyIS_Address_Collection_DIR', plugin_dir_path( __FILE__ ) );
            }

            if ( ! defined( 'PyIS_Address_Collection_URL' ) ) {
                // Plugin URL
                define( 'PyIS_Address_Collection_URL', plugin_dir_url( __FILE__ ) );
            }
            
            if ( ! defined( 'PyIS_Address_Collection_FILE' ) ) {
                // Plugin File
                define( 'PyIS_Address_Collection_FILE', __FILE__ );
            }

        }

        /**
         * Internationalization
         *
         * @access      private 
         * @since       1.0.0
         * @return      void
         */
        private function load_textdomain() {

            // Set filter for language directory
            $lang_dir = PyIS_Address_Collection_DIR . '/languages/';
            $lang_dir = apply_filters( 'pyis_address_collection_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), PyIS_Address_Collection_ID );
            $mofile = sprintf( '%1$s-%2$s.mo', PyIS_Address_Collection_ID, $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/' . PyIS_Address_Collection_ID . '/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/pyis-address-collection/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( PyIS_Address_Collection_ID, $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/pyis-address-collection/languages/ folder
                load_textdomain( PyIS_Address_Collection_ID, $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( PyIS_Address_Collection_ID, false, $lang_dir );
            }

        }
        
        /**
         * Include different aspects of the Plugin
         * 
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function require_necessities() {
            
            if ( is_admin() ) {
                
            }
            
            require_once PyIS_Address_Collection_DIR . '/core/rest/pyis-address-collection-cognitoforms-rest.php';
            $this->rest = new PyIS_Address_Collection_REST();
            
        }
        
        /**
         * Register our CSS/JS to use later
         * 
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function register_scripts() {
            
            wp_register_style(
                PyIS_Address_Collection_ID . '-admin',
                PyIS_Address_Collection_URL . '/assets/css/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PyIS_Address_Collection_VER
            );
            
            wp_register_script(
                PyIS_Address_Collection_ID . '-admin',
                PyIS_Address_Collection_URL . '/assets/js/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PyIS_Address_Collection_VER,
                true
            );
            
        }

    }

} // End Class Exists Check

/**
 * The main function responsible for returning the one true PyIS_Address_Collection
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \PyIS_Address_Collection The one true PyIS_Address_Collection
 */
add_action( 'plugins_loaded', 'PyIS_Address_Collection_load' );
function PyIS_Address_Collection_load() {
        
    require_once __DIR__ . '/core/pyis-address-collection-functions.php';
    PYISADDRESSCOLLECTION();

}