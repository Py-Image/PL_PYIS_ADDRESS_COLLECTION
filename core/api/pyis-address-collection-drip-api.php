<?php
/**
 * Drip API v3.0 Communication Class
 *
 * @since 1.0.0
 *
 * @package PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core/api
 */

defined( 'ABSPATH' ) || die();

class PyIS_Address_Collection_Drip_API {

    /**
    * @var         PyIS_Address_Collection_Drip_API $api_key Holds set API Key
    * @since       1.0.0
    */
    private $api_key = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $account_id The Account ID the API Key belongs to. Yep, we need both.
    * @since       1.0.0
    */
    private $account_id = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $password The Account ID the API Key belongs to. Yep, we need both.
    * @since       1.0.0
    */
    private $password = '';
    
    /**
    * @var         PyIS_Address_Collection_Drip_API $api_endpoint Holds set API Endpoint
    * @since       1.0.0
    */
    public $api_endpoint = 'https://api.getdrip.com/v2/<account_id>/';

    /**
	 * PyIS_Address_Collection_Drip_API constructor.
	 * 
	 * @since 1.0.0
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
     * @since       1.0.0
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
     * @since       1.0.0
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
     * @since       1.0.0
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
     * @since       1.0.0
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
     * @since       1.0.0
     * @return      array|false Assoc array of decoded result
     */
    private function make_request( $http_verb, $method, $args = array(), $timeout = 10 ) {

        $args = wp_parse_args( $args, array(
            'method' => $http_verb,
            'timeout' => $timeout,
            'headers' => array(),
        ) );
        
        $url = $this->api_endpoint . '/' . $method;
        
        $args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->api_key . ':' . $this->password );
        
        $response = wp_remote_request( $url, $args );

        return json_decode( $response['body'] );
        
    }
    
    /**
     * Return the API Endpoint
     * 
     * @access      public
     * @since       1.0.0
     * @return      string Drip API Endpoint
     */
    public function get_api_endpoint() {
        return $this->api_endpoint;
    }

}