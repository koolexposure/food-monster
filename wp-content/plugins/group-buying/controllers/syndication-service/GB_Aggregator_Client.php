<?php

/**
 * GBS Syndication Service Client.
 * Connects with the web service to make requests and return results
 *
 * @package GBS
 * @subpackage Syndication
 */

class GB_Aggregator_Client extends GB_Aggregator_Plugin {
	private $web_service_url = 'http://groupbuyingsite.net/dealserver/';
	private $response_code = 0;
	private $location_header = '';
	private $response = array();
	private $body = '';

	public function __construct( $url = '' ) {
		if ( defined( 'GBS_WEB_SERVICE_URL' ) ) {
			$this->web_service_url = trailingslashit( GBS_WEB_SERVICE_URL );
		} elseif ( $url ) {
			$this->web_service_url = trailingslashit( $url );
		}
		if ( !$this->web_service_url ) {
			trigger_error( self::__( 'Web service URL must be supplied' ), E_USER_ERROR );
		}
	}

	public function location() {
		return $this->location_header;
	}

	public function code() {
		return $this->response_code;
	}

	/**
	 * Get the feed of updates from the server
	 *
	 * @return array
	 */
	public function get_feed( $args = array() ) {
		foreach ( $args as $key => $arg ) {
			if ( is_array( $arg ) ) {
				$args[$key] = implode( ',', $arg );
			}
			if ( empty( $arg ) ) {
				unset( $args[$key] ); // don't send empty args
			}
		}
		$url = add_query_arg( $args, $this->web_service_url );
		$this->request( $url );
		$feed = json_decode( $this->body, TRUE );
		if ( !$feed ) {
			$feed = array();
		}
		return $feed;
	}

	/**
	 * Get the list of all taxonomy terms from the server
	 *
	 * @return array
	 */
	public function get_taxonomy_terms() {
		$url = $this->web_service_url.'taxa/';
		$this->request( $url );
		$taxa = json_decode( $this->body, FALSE );
		if ( !$taxa ) {
			$taxa = array();
		}
		return $taxa;
	}

	/**
	 * Get a deal from the server
	 *
	 * @param unknown $deal_id
	 * @return array
	 */
	public function get_deal( $url ) {
		$this->request( $url );
		$deal = json_decode( $this->body, TRUE );
		if ( !$deal ) {
			$deal = array();
		}
		return $deal;
	}

	/**
	 * Post a new deal to the server
	 *
	 * @param array   $data
	 * @return array The new deal
	 */
	public function post_deal( $data ) {
		$url = $this->web_service_url;
		$args = array(
			'method' => 'POST',
			'body' => array( 'deal' => json_encode( $data ) ),
		);
		$this->request( $url, $args );
		$deal = json_decode( $this->body, TRUE );
		if ( !$deal ) {
			$deal = array();
		}
		return $deal;
	}

	/**
	 * Update the deal in the given location with $data
	 *
	 * @param string  $url
	 * @param array   $data
	 * @return array The updated deal
	 */
	public function put_deal( $url, $data ) {
		$args = array(
			'method' => 'PUT',
			'body' => array( 'deal' => json_encode( $data ) ),
		);
		$this->request( $url, $args );
		$deal = json_decode( $this->body, TRUE );
		if ( !$deal ) {
			$deal = array();
		}
		return $deal;
	}

	/**
	 * Delete the deal at the given URL
	 *
	 * @param string  $url
	 * @return bool Whether the operation succeeded
	 */
	public function delete_deal( $url ) {
		$args = array(
			'method' => 'DELETE',
		);
		$this->request( $url, $args );
		if ( strpos( $this->response_code, '2' ) === 0 ) { // look for a 2xx response
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the affiliate information
	 *
	 * @param string  $url
	 * @return bool Whether the operation succeeded
	 */
	public function get_affiliate_info( $url = 'http://gbs.dev' ) {
		// get the cache
		$cache_key = md5( $url.'2' );
		$cache = get_transient( $cache_key );
		if ( !empty( $cache ) ) {
			return $cache;
		}
		$this->request( add_query_arg( array( 'GB_Route'=>'gb_aggregation_affiliate_info' ), $url ) );
		$info = json_decode( $this->body, TRUE );
		if ( !is_array( $info ) ) {
			$info = array( time() ); // need to set something in case the site doesn't return the info
		}
		set_transient( $cache_key, $info, 60*60*24*3 ); // cache for a few days
		return $info;
	}

	public function request( $url, $args = array() ) {
		$defaults = array(
			'method' => 'GET',
			'headers' => $this->get_headers(),
			'sslverify' => FALSE,
			'timeout' => 10,
		);
		$args = wp_parse_args( $args, $defaults );

		// A hack to work around wp_remote_request()'s lack of support for DELETE
		// DELETE requests will add a delete=1 query parameter
		if ( $args['method'] == 'DELETE' ) {
			$url = add_query_arg( array( 'delete' => 1 ), $url );
		}

		$this->response = wp_remote_request( $url, $args );
		if ( self::DEBUG ) {
			error_log( '===== GB_Aggregator_Client::request() =====' );
			error_log( 'URL: '.$url );
			error_log( 'Args: '.print_r( $args, TRUE ) );
			error_log( 'Response: '.print_r( $this->response, TRUE ) );
		}
		$this->response_code = wp_remote_retrieve_response_code( $this->response );
		$this->location_header = wp_remote_retrieve_header( $this->response, 'location' );
		$this->body = wp_remote_retrieve_body( $this->response );
	}

	private function get_headers() {
		$headers = array(
			'x-gbs-api-key' => Group_Buying_Update_Check::get_api_key(),
			'x-gbs-url' => home_url(),
			'x-gbs-plugin' => Group_Buying_Update_Check::PLUGIN_NAME, // TODO make this the aggregation plugin, since only certain users will have access.
			'x-gbs-version' => Group_Buying::GB_VERSION,
			'x-wp-version' => get_bloginfo( 'version' ),
		);
		do_action( 'gbs_aggregator_client_headers', $headers );
		return $headers;
	}

}
