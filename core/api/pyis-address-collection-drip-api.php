<?php
/**
 * Drip API v3.0 Communication Class
 *
 * @since 0.1.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core/api
 */

defined( 'ABSPATH' ) || die();

class PyIS_Address_Collection_Drip_API {

    /**
    * @var         PyIS_Address_Collection_Drip_API $api_key Holds set API Key
    * @since       0.1.0
    */
    private $api_key = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $account_id The Account ID the API Key belongs to. Yep, we need both.
    * @since       0.1.0
    */
    private $account_id = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $password The Account ID the API Key belongs to. Yep, we need both.
    * @since       0.1.0
    */
    private $password = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $api_endpoint Holds set API Endpoint
    * @since       0.1.0
    */
    public $api_endpoint = 'https://api.getdrip.com/v2/<account_id>/';

    /**
	 * PyIS_Address_Collection_Drip_API constructor.
	 * 
	 * @since 0.1.0
	 */
    function __construct( $api_key, $account_id, $password ) {

        $this->api_key = trim( $api_key );
        
        // Construct the appropriate API Endpoint        
        $this->account_id = trim( $account_id );
        $this->api_endpoint  = str_replace( '<account_id>', $this->account_id, $this->api_endpoint );
        
        $this->password = $password;

    }

    /**
     * Make an HTTP DELETE request - for deleting data
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (if any)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       0.1.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function delete( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'delete', $method, $args, $timeout );
    }

    /**
     * Make an HTTP GET request - for retrieving data
     * 
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function get( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'get', $method, $args, $timeout );
    }

    /**
     * Make an HTTP PATCH request - for performing partial updates
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       0.1.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function patch( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'patch', $method, $args, $timeout );
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       0.1.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function post( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'post', $method, $args, $timeout );
    }

    /**
     * Make an HTTP PUT request - for creating new items
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     * 
     * @access      public
     * @since       0.1.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function put( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'put', $method, $args, $timeout );
    }

    /**
     * Performs the underlying HTTP request
     * 
     * @param       string      $http_verb The HTTP verb to use: get, post, put, patch, delete
     * @param       string      $method    The API method to be called
     * @param       array       $args      Assoc array of parameters to be passed
     * @param       int $timeout
     *                  
     * @access      private
     * @since       0.1.0
     * @return      array|false Assoc array of decoded result
     */
    private function make_request( $http_verb, $method, $args = array(), $timeout = 10 ) {

        $args = wp_parse_args( $args, array(
            'method' => $http_verb,
            'timeout' => $timeout,
            'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':' . $this->password ),
				'Content-Type' => 'application/vnd.api+json',
				'Accept' => 'application/json, text/javascript, */*; q=0.01',
			),
        ) );
		
		$headers = array();
		foreach ( $args['headers'] as $key => $value ) {
			
			$headers[] = "$key: $value";
			
		}
        
        $url = $this->api_endpoint . '/' . $method;
		
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
        curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		
		if ( $http_verb !== 'get' ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, strtoupper( $http_verb ) );
		}
		
		if ( ! empty( $args ) ) {
			if ( ( isset( $args['__req'] ) && strtolower( $args['__req'] ) == 'get' ) || 
				$http_verb == 'get' ) {
				
                unset( $args['__req'] );
                $url .= '?' . http_build_query( $args );
				
            }
			elseif ( $http_verb == 'post' || 
					$http_verb == 'delete' ) {
				
                $params_str = is_array( $args['body'] ) ? json_encode( $args['body'] ) : $args['body'];
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $params_str );
				
            }
			
        }
		
		curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$buffer = curl_exec( $ch );
		return json_decode( $buffer );
        
    }
    
    /**
     * Return the API Endpoint
     * 
     * @access      public
     * @since       0.1.0
     * @return      string Drip API Endpoint
     */
    public function get_api_endpoint() {
        return $this->api_endpoint;
    }

}