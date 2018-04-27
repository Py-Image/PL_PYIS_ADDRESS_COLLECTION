<?php
/**
 * Creates REST Endpoints
 *
 * @since 0.1.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core/rest
 */

defined( 'ABSPATH' ) || die();

class PyIS_Address_Collection_REST {


    /**
	 * PyIS_Address_Collection_REST constructor.
	 *
	 * @since 0.1.0
	 */
    function __construct() {

        add_action( 'rest_api_init', array( $this, 'create_routes' ) );

    }

    /**
     * Creates a WP REST API route for CognitoForms to POST JSON tool_box
     * 
     * @since       0.1.0
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
                'message' => _x( 'No data payload', 'No JSON Uploaded Error', PyIS_Address_Collection_ID ),
            ) );
        }
        
        $json = json_decode( $json );
        
        if ( (string) $json->Secret !== get_option( 'pyis_cognitoforms_secret_key' ) ) {
            return json_encode( array(
                'success' => false,
                'message' => _x( 'Access Denied', 'Wrong Source Error', PyIS_Address_Collection_ID ),
            ) );
        }
		
		// Simple debugging option for Staging since that site runs the Stop Emails plugin
		$use_mail = get_option( 'pyis_address_collection_use_mail', false );
        
        $form_name = $json->Form->Name;
        
        $entry_id = $json->Entry->Number;
        $entry_link = $json->Entry->AdminLink;
        
        $email = $json->Email;
        $first_name = $json->Name->First;
        $last_name = $json->Name->Last;
        
        $subscriber = PYISADDRESSCOLLECTION()->drip_api->get( 'subscribers/' . $email );
		
		$found_subscriber = true;
		
		if ( property_exists( $subscriber, 'errors' ) ) {
			
			$found_subscriber = array_filter( 
				$subscriber->errors,
				function( $object ) {
					// We want to know if there are errors other than the Subscriber not being found so we can report them
					return $object->code !== 'not_found_error';
				}
			);
			
		}
		
		// $found_subscriber defaults to true, but if one is explictly not found via our error check above, then this error reporting code should run
		if ( $found_subscriber && 
			property_exists( $subscriber, 'errors' ) ) {
			
			$to = get_option( 'pyis_address_collection_admin_email' );
            $to = ( $to ) ? $to : get_option( 'admin_email' ); // Default to the Primary Admin Email
            
            $subject = _x( 'Address Collection Error', 'Error Email Subject Line', PyIS_Address_Collection_ID );
            
            /**
             * Allow the Subject Line of the Notificaiton Emails to be changed
             *
             * @since {{VERSION}}
             */
            $subject = apply_filters( 'pyis_address_collection_error_subject_line', $subject );
            
			$message = '';
            foreach ( $subscriber->errors as $error ) {
				
				$message .= "$error->code: $error->message\n";
				
			}
            
            /**
             * Allow the Email Error Message Body to be changed
             *
             * @since {{VERSION}}
             */
            $message = apply_filters( 
                'pyis_address_collection_error_message_body', 
                $message, 
                $json 
            );
            
            $sitename = strtolower( $_SERVER['SERVER_NAME'] );

            if ( substr( $sitename, 0, 4 ) == 'www.' ) {
                $sitename = substr( $sitename, 4 );
            }
            
            $from_address = 'wordpress@' . $sitename;
            
            /**
             * Allow the "From: " Address Header to be changed
             *
             * @since {{VERSION}}
             */
            $from_address = apply_filters( 'pyis_address_collection_from_address', $from_address, $sitename );
            
            $reply_to_address = 'wordpress@' . $sitename;
            
            /**
             * Allow the "Reply-To: " Address Header to be changed
             *
             * @since {{VERSION}}
             */
            $reply_to_address = apply_filters( 'pyis_address_collection_error_reply_to_address', $reply_to_address, $sitename );
            
            $headers = 'From: ' . $from_address . "\r\n" .
                'Reply-To: ' . $reply_to_address . "\r\n" .
                "Content-type: text/html; charset=iso-8859-1\r\n" . 
                'X-Mailer: PHP/' . phpversion();
            
			if ( $use_mail ) {
            	mail( $to, $subject, $message, $headers );
			}
			else {
				wp_mail( $to, $subject, $message, $headers );
			}
			
			return json_encode( array(
				'success' => false,
				'message' => __( 'Error', PyIS_Address_Collection_ID ),
			) );
			
		}
        
        $purchased_hardcopy_bundle = array_filter( 
            $subscriber->subscribers,
            function( $object ) {
                /**
                 * Allows the Tag we check against for Hardcopy Bundle Purchase to be changed
                 *
                 * @since 0.1.0
                 */
                return in_array( apply_filters( 'pyis_address_collection_tag_check', 'purchased hardcopy bundle' ), $object->tags );
            }
        );
        
        // If the Email is associated with a User that has purchased the Hard Copy Bundle
        if ( $purchased_hardcopy_bundle ) {
            
            $tag_subscriber = PYISADDRESSCOLLECTION()->drip_api->post(
                'tags',
                array(
                    'body' => json_encode( array(
                        'tags' => array(
                            array(
                                'email' => $email,
                                /**
                                 * Allow the "Address Collected" Tag to be changed
                                 *
                                 * @since 0.1.0
                                 */
                                'tag' => apply_filters( 'pyis_address_collection_collected_tag', 'ppao collected address' ),
                            ),
                        ),
                    ) ),
                )
            );
            
        }
        else {
            
            // Only Tag the Subscriber if they exist
            if ( $found_subscriber ) {
            
                $tag_subscriber = PYISADDRESSCOLLECTION()->drip_api->post(
                    'tags',
                    array(
                        'body' => json_encode( array(
                            'tags' => array(
                                array(
                                    'email' => $email,
                                    /**
                                     * Allow the "Address Suspect" Tag to be changed
                                     *
                                     * @since 0.1.0
                                     */
                                    'tag' => apply_filters( 'pyis_address_collection_suspect_tag', 'ppao address suspect' ),
                                ),
                            ),
                        ) ),
                    )
                );
                
            }
            
            $to = get_option( 'pyis_address_collection_admin_email' );
            $to = ( $to ) ? $to : get_option( 'admin_email' ); // Default to the Primary Admin Email
            
            $subject = _x( 'Address Collection Notice', 'User Suspect Email Subject Line', PyIS_Address_Collection_ID );
            
            /**
             * Allow the Subject Line of the Notificaiton Emails to be changed
             *
             * @since 0.1.0
             */
            $subject = apply_filters( 'pyis_address_collection_subject_line', $subject );
            
            $message = sprintf( 
                _x( "%s has recieved a suspicious entry.<br /><br />Entry ID: <a href='%s' target='_blank'>%s</a><br /><br />First Name: %s<br /><br />Last Name: %s<br /><br />Email Address: %s", 'User Suspect Email Message Body', PyIS_Address_Collection_ID ),
                $form_name,
                $entry_link,
                $entry_id,
                $first_name,
                $last_name,
                $email
            );
            
            /**
             * Allow the Email Message Body to be changed using the same data we've pulled via JSON
             *
             * @since 0.1.0
             */
            $message = apply_filters( 
                'pyis_address_collection_message_body', 
                $message, 
                $json 
            );
            
            $sitename = strtolower( $_SERVER['SERVER_NAME'] );

            if ( substr( $sitename, 0, 4 ) == 'www.' ) {
                $sitename = substr( $sitename, 4 );
            }
            
            $from_address = 'wordpress@' . $sitename;
            
            /**
             * Allow the "From: " Address Header to be changed
             *
             * @since 0.1.0
             */
            $from_address = apply_filters( 'pyis_address_collection_from_address', $from_address, $sitename );
            
            $reply_to_address = 'wordpress@' . $sitename;
            
            /**
             * Allow the "Reply-To: " Address Header to be changed
             *
             * @since 0.1.0
             */
            $reply_to_address = apply_filters( 'pyis_address_collection_reply_to_address', $reply_to_address, $sitename );
            
            $headers = 'From: ' . $from_address . "\r\n" .
                'Reply-To: ' . $reply_to_address . "\r\n" .
                "Content-type: text/html; charset=iso-8859-1\r\n" . 
                'X-Mailer: PHP/' . phpversion();
            
			if ( $use_mail ) {
            	mail( $to, $subject, $message, $headers );
			}
			else {
				wp_mail( $to, $subject, $message, $headers );
			}
			
			return json_encode( array(
				'success' => true,
				'message' => __( 'Success! Suspicious email sent.', PyIS_Address_Collection_ID ),
			) );
            
        }
		
		$to = get_option( 'pyis_address_collection_admin_email' );
		$to = ( $to ) ? $to : get_option( 'admin_email' ); // Default to the Primary Admin Email

		$subject = __( 'Address Collection Success', PyIS_Address_Collection_ID );

		/**
		 * Allow the Subject Line of the Notificaiton Emails to be changed
		 *
		 * @since {{VERSION}}
		 */
		$subject = apply_filters( 'pyis_address_collection_success_subject_line', $subject );

		$message = sprintf( 
			__( "%s has recieved a valid entry.<br /><br />Entry ID: <a href='%s' target='_blank'>%s</a><br /><br />First Name: %s<br /><br />Last Name: %s<br /><br />Email Address: %s", PyIS_Address_Collection_ID ),
			$form_name,
			$entry_link,
			$entry_id,
			$first_name,
			$last_name,
			$email
		);

		/**
		 * Allow the Email Message Body to be changed using the same data we've pulled via JSON
		 *
		 * @since {{VERSION}}
		 */
		$message = apply_filters( 
			'pyis_address_collection_success_message_body', 
			$message, 
			$json 
		);

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_address = 'wordpress@' . $sitename;

		/**
		 * Allow the "From: " Address Header to be changed
		 *
		 * @since {{VERSION}}
		 */
		$from_address = apply_filters( 'pyis_address_collection_from_address', $from_address, $sitename );

		$reply_to_address = 'wordpress@' . $sitename;

		/**
		 * Allow the "Reply-To: " Address Header to be changed
		 *
		 * @since {{VERSION}}
		 */
		$reply_to_address = apply_filters( 'pyis_address_collection_reply_to_address', $reply_to_address, $sitename );

		$headers = 'From: ' . $from_address . "\r\n" .
			'Reply-To: ' . $reply_to_address . "\r\n" .
			"Content-type: text/html; charset=iso-8859-1\r\n" . 
			'X-Mailer: PHP/' . phpversion();

		if ( $use_mail ) {
			mail( $to, $subject, $message, $headers );
		}
		else {
			wp_mail( $to, $subject, $message, $headers );
		}
        
        return json_encode( array(
            'success' => true,
            'message' => __( 'Success! This User has purchased the hardcopy.', PyIS_Address_Collection_ID ),
        ) );

    }

}