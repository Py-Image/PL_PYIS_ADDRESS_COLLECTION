<?php
/**
 * Creates REST Endpoints
 *
 * @since 1.0.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core/rest
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
        
        // We don't have a very good way to ensure that CognitoForms is the origin of the request
        if ( ! isset( $_SERVER['HTTP_REFERER'] ) || ! strpos( $_SERVER['HTTP_REFERER'], 'cognitoforms.com' ) ) {
            /*
            return json_encode( array(
                'success' => false,
                'message' => _x( 'Access Denied', 'Wrong Source Error', PyIS_Address_Collection_ID ),
            ) );
            */
        }

        $json = file_get_contents( 'php://input' );

        if ( empty( $json ) ) {
            return json_encode( array(
                'success' => false,
                'message' => _x( 'No data payload', 'No JSON Uploaded Error', PyIS_Address_Collection_ID ),
            ) );
        }

        $json = json_decode( $json );
        
        $form_name = $json->Form->Name;
        
        $entry_id = $json->Entry->Number;
        $entry_link = $json->Entry->AdminLink;
        
        $email = $json->Email;
        $full_name = $json->Name->FirstAndLast;

        return true;

    }

}