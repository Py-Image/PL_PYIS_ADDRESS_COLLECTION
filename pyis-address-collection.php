<?php
/*
 * Plugin Name: PyImageSearch Address Collection
 * Plugin URL: https://github.com/Py-Image/PL_PYIS_ADDRESS_COLLECTION
 * Description: Send data from CognitoForms to Drip using WP as a middleman
 * Version: 1.1.0
 * Text Domain: pyis-address-collection
 * Author: Eric Defore
 * Author URI: http://realbigmarketing.com
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PyIS_Address_Collection' ) ) {

    /**
     * Main PyIS_Address_Collection class
     *
     * @since       0.1.0
     */
    class PyIS_Address_Collection {
        
        /**
         * @var         PyIS_Address_Collection $plugin_data Holds Plugin Header Info
         * @since       0.1.0
         */
        public $plugin_data;
        
        /**
         * @var         PyIS_Address_Collection $settings Admin Settings
         * @since       0.1.0
         */
        public $settings;
        
        /**
         * @var         PyIS_Address_Collection $rest REST Endpoints
         * @since       0.1.0
         */
        public $rest;
        
        /**
         * @var         PyIS_Address_Collection $drip_api Drip API Class
         * @since       0.1.0
         */
        public $drip_api;
		
		/**
         * @var         PyIS_Address_Collection $field_helpers RBM Field Helpers
         * @since       1.1.0
         */
		public $field_helpers;

        /**
         * Get active instance
         *
         * @access      public
         * @since       0.1.0
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
            
            global $wp_version;
            global $wp_settings_errors;
			
			require_once __DIR__ . '/core/library/rbm-field-helpers/rbm-field-helpers.php';
		
			$this->field_helpers = new RBM_FieldHelpers( array(
				'ID'   => 'pyis_address_collection', // Your Theme/Plugin uses this to differentiate its instance of RBM FH from others when saving/grabbing data
				'l10n' => array(
					'field_table'    => array(
						'delete_row'    => __( 'Delete Row', 'pyis-address-collection' ),
						'delete_column' => __( 'Delete Column', 'pyis-address-collection' ),
					),
					'field_select'   => array(
						'no_options'       => __( 'No select options.', 'pyis-address-collection' ),
						'error_loading'    => __( 'The results could not be loaded', 'pyis-address-collection' ),
						/* translators: %d is number of characters over input limit */
						'input_too_long'   => __( 'Please delete %d character(s)', 'pyis-address-collection' ),
						/* translators: %d is number of characters under input limit */
						'input_too_short'  => __( 'Please enter %d or more characters', 'pyis-address-collection' ),
						'loading_more'     => __( 'Loading more results...', 'pyis-address-collection' ),
						/* translators: %d is maximum number items selectable */
						'maximum_selected' => __( 'You can only select %d item(s)', 'pyis-address-collection' ),
						'no_results'       => __( 'No results found', 'pyis-address-collection' ),
						'searching'        => __( 'Searching...', 'pyis-address-collection' ),
					),
					'field_repeater' => array(
						'collapsable_title' => __( 'New Row', 'pyis-address-collection' ),
						'confirm_delete'    => __( 'Are you sure you want to delete this element?', 'pyis-address-collection' ),
						'delete_item'       => __( 'Delete', 'pyis-address-collection' ),
						'add_item'          => __( 'Add', 'pyis-address-collection' ),
					),
					'field_media'    => array(
						'button_text'        => __( 'Upload / Choose Media', 'pyis-address-collection' ),
						'button_remove_text' => __( 'Remove Media', 'pyis-address-collection' ),
						'window_title'       => __( 'Choose Media', 'pyis-address-collection' ),
					),
					'field_checkbox' => array(
						'no_options_text' => __( 'No options available.', 'pyis-address-collection' ),
					),
				),
			) );
            
            if ( is_admin() ) {
            
                if ( version_compare( $wp_version, '4.4' ) == -1 ) {

                    $this->admin_notices[] = sprintf(
                        _x( '%s requires your WordPress installation to be at least v%s or higher!', 'Super Old WordPress Installation Error', 'pyis-address-collection' ),
                        '<strong>' . $this->plugin_data['Name'] . '</strong>',
                        '4.4'
                    );

                    if ( ! has_action( 'admin_notices', array( $this, 'admin_notices' ) ) ) {
                        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
                    }

                    return;

                }

                $api_key = get_option( 'pyis_drip_api_key' );
                $api_key = ( $api_key ) ? $api_key : '';

                $account_id = get_option( 'pyis_drip_account_id' );
                $account_id = ( $account_id ) ? $account_id : '';

                $account_password = get_option( 'pyis_drip_account_password' );
                $account_password = ( $account_password ) ? $account_password : '';

                if ( ! $api_key || ! $account_id || ! $account_password ) {

                    $this->admin_notices[] = sprintf( 
                        _x( 'In order for data to be sent to Drip, you must enter some credentials in the %s%s Settings Page%s!', 'Drip API Credentials Needed', 'pyis-address-collection' ), 
                        '<a href="' . get_admin_url( null, 'options-general.php?page=pyis-address-collection' ) . '" title="' . sprintf( _x( '%s Settings', 'Settings Page Link from Error Message', 'pyis-address-collection' ), $this->plugin_data['Name'] ) . '">',
                        '<strong>' . $this->plugin_data['Name'] . '</strong>', '</a>'
                    );

                    if ( ! has_action( 'admin_notices', array( $this, 'admin_notices' ) ) ) {
                        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
                    }

                    // Not breaking execution for this error

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
         * @since       0.1.0
         * @return      void
         */
        private function setup_constants() {
            
            // WP Loads things so weird. I really want this function.
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            
            // Only call this once, accessible always
            $this->plugin_data = get_plugin_data( __FILE__ );

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
         * @since       0.1.0
         * @return      void
         */
        private function load_textdomain() {
            
            $lang_dir = PyIS_Address_Collection_DIR . '/languages/';
            
            /**
             * Allows the ability to override the translation directory within the plugin to check.
             *
             * @since 0.1.0
             */
            $lang_dir = apply_filters( 'pyis_address_collection_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'pyis-address-collection' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'pyis-address-collection', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/pyis-address-collection/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/pyis-address-collection/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( 'pyis-address-collection', $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/pyis-address-collection/languages/ folder
                load_textdomain( 'pyis-address-collection', $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( 'pyis-address-collection', false, $lang_dir );
            }

        }
        
        /**
         * Include different aspects of the Plugin
         * 
         * @access      private
         * @since       0.1.0
         * @return      void
         */
        private function require_necessities() {
            
            if ( is_admin() ) {
                
                require_once PyIS_Address_Collection_DIR . '/core/admin/pyis-address-collection-settings.php';
                $this->settings = new PyIS_Address_Collection_Settings();
                
            }
            
            $api_key = get_option( 'pyis_drip_api_key' );
            $api_key = ( $api_key ) ? $api_key : '';
            
            $account_id = get_option( 'pyis_drip_account_id' );
            $account_id = ( $account_id ) ? $account_id : '';
            
            $account_password = get_option( 'pyis_drip_account_password' );
            $account_password = ( $account_password ) ? $account_password : '';
            
            require_once PyIS_Address_Collection_DIR . '/core/api/pyis-address-collection-drip-api.php';
            $this->drip_api = new PyIS_Address_Collection_Drip_API( $api_key, $account_id, $account_password );
            
            require_once PyIS_Address_Collection_DIR . '/core/rest/pyis-address-collection-cognitoforms-rest.php';
            $this->rest = new PyIS_Address_Collection_REST();
            
        }
        
        /**
         * Register our CSS/JS to use later
         * 
         * @access      public
         * @since       0.1.0
         * @return      void
         */
        public function register_scripts() {
            
            wp_register_style(
                'pyis-address-collection-admin',
                PyIS_Address_Collection_URL . '/assets/css/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PyIS_Address_Collection_VER
            );
            
            wp_register_script(
                'pyis-address-collection-admin',
                PyIS_Address_Collection_URL . '/assets/js/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PyIS_Address_Collection_VER,
                true
            );
            
        }
        
        /**
        * Show admin notices.
        * 
        * @access    public
        * @since     0.1.0
        * @return    HTML
        */
        public function admin_notices() {
            ?>
            <div class="error">
                <?php foreach ( $this->admin_notices as $notice ) : ?>
                    <p>
                        <?php echo $notice; ?>
                    </p>
                <?php endforeach; ?>
            </div>
            <?php
        }

    }

} // End Class Exists Check

/**
 * The main function responsible for returning the one true PyIS_Address_Collection
 * instance to functions everywhere
 *
 * @since       0.1.0
 * @return      \PyIS_Address_Collection The one true PyIS_Address_Collection
 */
add_action( 'plugins_loaded', 'PyIS_Address_Collection_load' );
function PyIS_Address_Collection_load() {
        
    require_once __DIR__ . '/core/pyis-address-collection-functions.php';
    PYISADDRESSCOLLECTION();

}