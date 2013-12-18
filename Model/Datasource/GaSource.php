<?php
App::uses('DataSource', 'Model/Datasource');
App::uses('HttpSocket', 'Network/Http');
class GaSource extends DataSource {
	protected $_schema = array('tweets'=>array());
	public $description = "Google Analytics Data Source";
	public $HttpSocket = null;

	/**
	 * Default config
	 * @var array
	 */
	public $config = array(
		//needed for auth token
		'auth' => array(
			'accountType' => 'GOOGLE',
			'Email' => 'need-to-configure',	//google email address
			'Passwd' => 'need-to-configure',
			'service' => 'analytics',
			'source' => 'Google-GAStats-0.0.1',
		),
		//Other config
		'url-login' => 'https://www.google.com/accounts/ClientLogin',
		'url-list-accounts' => 'https://www.google.com/analytics/feeds/accounts/default',
		'url-report' => 'https://www.google.com/analytics/feeds/data',
		'ids'=>'need-to-configure',			//numeric id of profile to query
		// other potentially configurable settings
		//'modelLog' => 'GAApiLog',	//store url calls
		//'modelData' => 'GAData',	//store aggregate data

	);
	public $authkey = null;
	public $authHeader = null;

	/**
	 * setup the config
	 * setup the HttpSocket class to issue the requests
	 * @param array $config
	 */
	public function  __construct($config = array()) {
		App::uses('Xml', 'Utility');
		App::uses('HttpSocket', 'Network/Http');
		$this->config = Set::merge($this->config, $config);
		$this->HttpSocket = new HttpSocket();
		return parent::__construct($config);
	}

	/**
	 * Run a report against GA api
	 */
	public function report($options = array()) {
		//comma separate options
		$query = array();
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $valk=>$valv) {
					if (in_array($key, array('ids','dimensions', 'metrics', 'filters')) && strpos($valv, 'ga:') === false) {
						$val[$valk]='ga:'.$valv;
					}
				}
				if ($key == 'filters') {
					$query[$key] = implode(";",$val);
				} else {
					$query[$key] = implode(",",$val);
				}
			} else {
				if (in_array($key, array('ids','dimensions', 'metrics', 'filters')) && strpos($val, 'ga:') === false) {
					$val = 'ga:'.$val;
				}
				$query[$key] = $val;
			}
		}
		//debug($query);
		return $this->request('report', $query);
	}

	/**
	 * send request to Google
	 *
	 */
	public function request($action=null, $query = array(), $requestOptions = array()) {
		$this->HttpSocket->reset();
		if ($action == 'login') {
			$request_url = $this->config['url-login'];
			$response = $this->HttpSocket->get($request_url, $query, $requestOptions);
			#debug($response->raw);
			return $response->body;
		}
		$this->verifyLogin();
		if ($action == 'report') {
			$request_url = $this->config['url-report'];
			$query['authkey'] = $this->authkey;
			if (!isset($query['ids'])) {
				$query['ids'] = 'ga:'.$this->config['ids'];
			}
			$requestOptions['header'] = $this->authHeader;
		} elseif ($action == 'list-accounts') {
			//list accounts
		}
		#debug(compact('request_url', 'query', 'requestOptions'));
		$response = $this->HttpSocket->get($request_url, $query, $requestOptions);
		#debug(compact('response'));
		return $response->body;
	}



	/**
	 * Simple Login functionality
	 * @return bool $loggedIn
	 */
	public function login() {
		if (!empty($this->authkey)) {
			return true;
		}
		$query = $this->config['auth'];
		$response = $this->request('login', $query);
		// strip out auth key from response
		preg_match('/Auth=([0-9A-Za-z_-]+)$/', $response, $auth_matches);
		debug(compact('response', 'auth_matches'));
		if (empty($auth_matches[1])) {
			return false;
		}
		$this->authkey = $auth_matches[1];
		$this->authHeader = array("Content-Type" => "text/xml", "Authorization" => "GoogleLogin auth={$this->authkey}");
		return true;
	}

	/**
	 *
	 */
	public function verifyLogin() {
		$this->login();
		if (empty($this->authkey)) {
			throw OutOfBoundsException('Gastats.GaSource unable to setup authentication with Google Analytics');
		}
		if (empty($this->authHeader)) {
			throw OutOfBoundsException('Gastats.GaSource unable to setup auth details with Google Analytics');
		}
		return true;
	}


	/**
	 * Sets method = GET in request if not already set
	 *
	 * @param AppModel $model
	 * @param array $queryData Unused
	 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$response = '';
		if ($this->login()) {
			$response = $this->request($model, $queryData);
		}
		return $response;
	}


}


