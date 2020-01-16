<?php

/**
 * Including required file
 */
require_once ABSPATH . 'wp-admin/includes/user.php';

/**
 * Class Moosend API
 * @package whello-moosend
 * @since 1.0..0 
 */
class WHMoosendApi {

	/**
	 * Declare variables
	 * @var string
	 */
	private $apiKey;
	private $mailingList;
	private $mailingListRole;
	private $role;
	private $condition;
	private $endpoint;
	private $cache;

	/**
	 * Init class constructor
	 */
	public function __construct(){
		add_action('wp_ajax_update_cache', [$this, 'updateCacheData']);
		add_action('wp_ajax_nopriv_update_cache', [$this, 'updateCacheData']);
		$this->apiKey = get_option('api_key');
		$this->condition = get_option('select_condition');
		$this->cache = new Cache([
			'name' => 'whmoosend',
			'path' => WM_PLUGIN_DIR . 'cache/',
			'extension' => '.cache'
		]);

		if(empty($this->getCacheData())){
			$this->setCacheData();
		}
	}

	/**
	 * Get available roles
	 * @return array
	 */
	public function getRole(){
		$temp = array();
		foreach ( get_editable_roles() as $role_name => $role_info){
			$temp[] = $role_name;
		}

		$this->role = array_diff($temp, ['administrator']);

		return $this->role;
	}

	/**
	 * Endpoint url handling
	 * only accept 2 arguments
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public function endpointUrl($type, $maillist = null){

		$this->endpoint = "https://api.moosend.com/v3/";

		switch ($type) {
			case 'get_mail_list' : 
				$this->endpoint .= "lists.json?apikey={$this->apiKey}&WithStatistics=true&ShortBy=CreatedOn&SortMethod=ASC";
				break;

			case 'create_new_subs' :
				$this->endpoint .= "subscribers/{$maillist}/subscribe.json?apikey={$this->apiKey}";
				break;

			case 'get_maillist_detail' :
				$this->endpoint .= "lists/{$maillist}/details.json?apikey={$this->apiKey}";
		}

		return $this->endpoint;
	}

	/**
	 * Get mailing list object
	 * @return array
	 */
	public function getMailingList(){
		
		$response = wp_remote_get( "https://api.moosend.com/v3/lists.json?apikey={$this->apiKey}&WithStatistics=true&ShortBy=CreatedOn&SortMethod=ASC" );
 
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		    $headers = $response['headers']; // array of http header lines
		    $body    = $response['body']; // use the content
		}

		$result = json_decode($body, true);

		return $result;
	}

	public function setCacheData(){
		date_default_timezone_set('Europe/Amsterdam');
		$data = $this->getMailingList();
		$date = date('F j, Y g:i:s a');
		$this->cache->store('wmdata', $data['Context']['MailingLists']);
		$this->cache->store('lastUpdate', $date);
	}

	public function getCacheData(){
		return $this->cache->retrieve('wmdata');
	}

	public function getLastUpdate(){
		return $this->cache->retrieve('lastUpdate');
	}

	public function updateCacheData(){
		$this->cache->eraseAll();
		$this->setCacheData();
		echo 'Cache updated';
		die();
	}

	/**
	 * Create new subscriber data
	 * only accept 2 arguments
	 * @param  array
	 * @param  string
	 * @return object
	 */
	public function createNewSubsciber($b, $mailingList){

		$url = "https://api.moosend.com/v3/subscribers/".$mailingList."/subscribe.json?apikey=".$this->apiKey;

		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'headers'     => array("Content-type" => "application/json;charset=UTF-8"),
			'body'        => json_encode($b),
			'cookies'     => array()
		));

		return $response;
	}
}