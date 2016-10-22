<?php
/**
 * Creates REST Endpoints
 *
 * @since 1.0.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core
 */

defined( 'ABSPATH' ) || die();

class PyIS_Address_Collection_REST {


    /**
	 * PyIS_Address_Collection_REST constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {

        add_action( 'rest_api_init', array( $this, 'create_routes' ) );

    }

    /**
     * Creates a WP REST API route for CognitoForms to POST JSON tool_box
     * 
     * @since       1.0.0
     * @access      public
     * @return      void
     */
    public function create_routes() {

        register_rest_route( 'pyis/v1', '/cognitoforms/practical-python-open-cv-hardcopy/submit', array(
            'methods' => 'POST',
            'callback' => array( $this, 'send_to_drip' ),
        ) );

    }

    /**
     * Callback for our REST Endpoint
     * 
     * @param       object $request WP_REST_Request Object
     * @return      string JSON
     */
    public function send_to_drip( $request ) {

        $json = file_get_contents( 'php://input' );

        if ( empty( $json ) ) {  
            return json_encode( array(
                'success' => false,
                'message' => _x( 'No data payload', 'No JSON Uploaded', PyIS_Address_Collection_ID ),
            ) );
        }

        $json = json_decode( $json );

        return $json->username;

    }

}