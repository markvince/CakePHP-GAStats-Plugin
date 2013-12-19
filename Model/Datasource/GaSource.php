<?php
App::uses('HttpSocket', 'Network/Http');
class GASource extends DataSource {
	protected $_schema = array('tweets'=>array());
	public $description = "Google Analytics Data Source";
	public $HttpSocket = null;

	/**
	 * config placeholder
	 * configure by editing app/Config/gastats.php
	 *
	 * @var array
	 */
	public $config = array();
	protected $authkey = null;
	protected $authHeader = null;

	/**
	 * setup the config
	 * setup the HttpSocket class to issue the requests
	 * @param array $config
	 */
	public function  __construct($config = array()) {
		App::uses('Xml', 'Utility');
		App::uses('HttpSocket', 'Network/Http');
		$this->HttpSocket = new HttpSocket();
		$this->config();
		$this->login();
		return parent::__construct($config);
	}

	/**
	 * Set and get $this->config - setups values from Configure::load('gastats');
	 */
	public function config($config = array()) {
		if (!empty($this->config)) {
			$this->config = Hash::merge($this->config, $config);
			return $this->config;
		}
		Configure::load('gastats');
		$this->config = Hash::merge(Configure::read('Gastats'), $config);
		return $this->config;
	}

	/**
	 *
	 *
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
		if ($action == 'login') {
			$request_url = $this->config['url-login'];
		} elseif ($action == 'report') {
			$request_url = $this->config['url-report'];
			$query['authkey']=$this->authkey;
			if (!isset($query['ids'])) {
				$query['ids'] ='ga:'.$this->config['ids'];
			}
			$requestOptions['header'] = $this->authHeader;
		} elseif ($action == 'list-accounts') {
			//list accounts
		}
		return $this->HttpSocket->get($request_url, $query, $requestOptions);
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
		$response = $this->request('login',$query);
		//strip out auth key
		preg_match('/Auth=([0-9A-Za-z_-]+)$/',$response,$auth_matches);
		if (isset($auth_matches) && isset($auth_matches[1]) && !empty($auth_matches[1])) {
			$this->authkey = $auth_matches[1];
			$this->authHeader = array("Content-Type" => "text/xml", "Authorization" => "GoogleLogin auth=$this->authkey");
			return true;
		}
		return false;
	}

	/**
	 * Sets method = GET in request if not already set
	 * @param AppModel $model
	 * @param array $queryData Unused
	 */
	public function read(&$model, $queryData = array()) {
		$response = '';
		if ($this->login()) {
			$response = $this->request($model, $queryData);
		}
		return $response;
	}

	public function listSources() {
		return array('tweets');
	}
	public function describe($model) {
		return $this->_schema['tweets'];
	}

}


