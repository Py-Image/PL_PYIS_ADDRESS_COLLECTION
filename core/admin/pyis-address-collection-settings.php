<?php
/**
 * PyImageSearch Address Collection Settings
 *
 * @since 0.1.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core/admin
 */

defined( 'ABSPATH' ) || die();

class PyIS_Address_Collection_Settings {

    /**
	 * PyIS_Address_Collection_Settings constructor.
	 *
	 * @since 0.1.0
	 */
    function __construct() {

        add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
        
        add_action( 'admin_init', array( $this, 'register_options' ) );
		
		add_action( 'pyis_address_collection_cognitoforms_instructions', array( $this, 'cognitoforms_instructions' ) );

    }
    
    /**
     * Create the Admin Page to hold our Settings
     * 
     * @access      public
     * @since       0.1.0
     * @return      void
     */
    public function create_admin_page() {
        
        $submenu_page = add_submenu_page(
            'options-general.php',
            _x( 'PyImageSearch Address Collection', 'Admin Page Title', 'pyis-address-collection' ),
            _x( 'Address Collection', 'Admin Menu Title', 'pyis-address-collection' ),
            'manage_options',
            'pyis-address-collection',
            array( $this, 'admin_page_content' )
        );
        
    }
    
    /**
     * Create the Content/Form for our Admin Page
     * 
     * @access      public
     * @since       0.1.0
     * @return      void
     */
    public function admin_page_content() { ?>

        <div class="wrap pyis-address-collection-settings">
			
			<form method="post" action="options.php">
				
				<?php echo wp_nonce_field( 'pyis_address_collection_settings', 'pyis_address_collection_nonce' ); ?>

				<?php settings_fields( 'pyis_address_collection' ); ?>

				<?php do_settings_sections( 'pyis-address-collection' ); ?>

				<?php submit_button(); ?>

			</form>

        </div>

        <?php
        
    }
    
    /**
     * Register our Options so the Admin Page knows what to Save
     * 
     * @access      public
     * @since       0.1.0
     * @return      void
     */
    public function register_options() {
        
        add_settings_section(
            'pyis_address_collection',
            __( 'CognitoForms+Drip Integration Settings', 'pyis-address-collection' ),
            '__return_false',
            'pyis-address-collection'
        );
		
		foreach ( $this->get_settings() as $id => $field ) {
			
			$field = wp_parse_args( $field, array(
				'settings_label' => '',
				'label' => false,
				'name' => $id,
			) );
			
			$callback = 'pyis_address_collection_do_field_' . $field['type'];
			
			add_settings_field(
				$id,
				$field['settings_label'],
				( is_callable( $callback ) ) ? 'pyis_address_collection_do_field_' . $field['type'] : 'pyis_address_collection_missing_callback',
				'pyis-address-collection',
				'pyis_address_collection',
				$field
			);
			
			register_setting( 'pyis_address_collection', $id );
			
		}
        
    }
	
	public function cognitoforms_instructions( $args ) {
		
		?>

		<ol>
			<li>
				<?php _e( 'Edit your Form.', 'pyis-address-collection' ); ?>
			</li>
			<li>
				<?php _e( 'Select "Submission Settings" at the bottom of the screen.', 'pyis-address-collection' ); ?>
			</li>
			<li>
				<?php _e( 'Expand the "Post JSON Data to Website" menu on the left of the screen.', 'pyis-address-collection' ); ?>
			</li>
			<li>
				<?php printf( __( 'Place <code>%s/wp-json/pyis/v1/cognitoforms/practical-python-open-cv-hardcopy/submit</code> into the "Submit Entry Endpoint" text input and save your changes.', 'pyis-address-collection' ), get_site_url() ); ?>
			</li>
		</ol>

		<?php
		
	}
	
	/**
	 * Holds the Settings Array
	 * 
	 * @access		public
	 * @since		{{VERSION}}
	 * @return		array Settings Array
	 */
	public function get_settings() {
		
		return apply_filters( 'pyis_address_collection', array(
			'cognitoforms_instructions' => array(
				'type' => 'hook',
				'settings_label' => __( 'CognitoForms API Setup', 'pyis-address-collection' ),
			),
			'pyis_cognitoforms_secret_key' => array(
				'type' => 'text',
				'settings_label' => __( 'CognitoForms Secret Key', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'description' => '<p class="description">' .
									__( "This is used to help ensure people aren't abusing your API Endpoint.", 'pyis-address-collection' ) . 
								 '</p>' . 
								 '<ol>' . 
									'<li>' . 
										__( 'Edit your Form.', 'pyis-address-collection' ) . 
									'</li>' . 
									'<li>' . 
										__( 'Click a "+" button at the bottom of your Form to add a Field.', 'pyis-address-collection' ) . 
									'</li>' . 
									'<li>' . 
										__( 'Choose a Basic Form Input, such as Text or Number.', 'pyis-address-collection' ) . 
									'</li>' . 
									'<li>' . 
										__( 'Set "Label" to <code>Secret</code>.', 'pyis-address-collection' ) . 
									'</li>' . 
									'<li>' . 
										__( 'Set "Default Vaue" to the same value entered above.', 'pyis-address-collection' ) . 
									'</li>' . 
									'<li>' . 
										__( 'Set "Show This Field" to <em>Never</em> and "Require This Field" to <em>Always</em>.', 'pyis-address-collection' ) . 
									'</li>' . 
								 '</ol>' . 
								 '<p class="description">' . 
									__( 'This value is only sent in the JSON Data, so it cannot be found by Inspecting the Page Source or by Viewing the Entry.', 'pyis-address-collection' ) . 
								 '</p>',
				'description_tip' => false,
				'input_atts' => array(
					'required' => true,
				),
			),
			'pyis_drip_api_key' => array(
				'type' => 'text',
				'settings_label' => __( 'Drip API Token', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'description' => '<a href="//www.getdrip.com/user/edit" target="_blank">' . 
									__( 'Find your API Token Here', 'pyis-address-collection' ) . 
								 '</a>',
				'description_tip' => false,
				'input_atts' => array(
					'required' => true,
				),
			),
			'pyis_drip_account_id' => array(
				'type' => 'text',
				'settings_label' => __( 'Drip Account ID', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'description' => '<p class="description">' . 
									__( 'Your Account ID is found in the Address Bar after logging in. <code>https://www.getdrip.com/&lt;account_id&gt;/</code>', 'pyis-address-collection' ) . 
								 '</p>',
				'description_tip' => false,
				'input_atts' => array(
					'required' => true,
				),
			),
			'pyis_drip_account_password' => array(
				'type' => 'text',
				'settings_label' => __( 'Drip Account Password', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'description' => '<p class="description">' . 
									__( 'Your Password is needed to Authenticate the API Request.', 'pyis-address-collection' ) . 
								 '</p>',
				'description_tip' => false,
				'input_atts' => array(
					'required' => true,
					'type' => 'password',
				),
			),
			'pyis_address_collection_admin_email' => array(
				'type' => 'text',
				'settings_label' => __( 'Send Notification Emails To:', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'description' => '<p class="description">' . 
									sprintf( __( 'This will default to the Admin Email: %s.', 'pyis-address-collection' ), get_option( 'admin_email', '' ) ) . 
								 '</p>',
				'description_tip' => false,
				'input_atts' => array(
					'placeholder' => get_option( 'admin_email', '' ),
				),
			),
			'pyis_address_collection_use_mail' => array(
				'type' => 'checkbox',
				'settings_label' => __( 'Use <code>mail()</code>?', 'pyis-address-collection' ),
				'no_init' => true,
				'option_field' => true,
				'options' => array(
					'1' => __( 'If checked, this plugin will use <code>mail()</code> rather than <code>wp_mail()</code>. This is useful for the Staging Site.', 'pyis-address-collection' ),
				),
			),
		) );
		
	}
    
}