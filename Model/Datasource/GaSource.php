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
	public $config = array();
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
		$this->config = $this->config($config);
		$this->HttpSocket = new HttpSocket();
		return parent::__construct($config);
	}

	/**
	 * GA OAuth setup
	 *
	 */
	public function setup() {
		if (!empty($this->ga)) {
			return;
		}
		$this->config();
		$file = dirname(dirname(__DIR__)) . DS . 'Vendor' . DS . 'GoogleAnalyticsAPI.class.php';
		App::import('Vendor', 'GoogleAnalyticsAPI', compact('file'));
		if (!class_exists('GoogleAnalyticsAPI')) {
			require_once($file);
		}
		$this->ga = new GoogleAnalyticsAPI('service');
		$this->auth();
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
	 * GA OAuth get auth / access_token
	 * and set into the ga class
	 */
	public function auth() {
		if (!empty($this->authkey)) {
			return;
		}
		$this->ga->auth->setClientId($this->config['auth']['clientId']); // From the APIs console
		$this->ga->auth->setEmail($this->config['auth']['email']); // From the APIs console
		$this->ga->auth->setPrivateKey($this->config['auth']['privateKey']); // Path to the .p12 file
		$auth = $this->ga->auth->getAccessToken();
		if ($auth['http_code'] != 200) {
			AppLog::error('Gastats Auth Failure - Bad Response: ' . json_encode($auth));
			throw new OutOfBoundsException('Unable to get googleAnalytics auth - bad response');
		}
		if (empty($auth['access_token'])) {
			AppLog::error('Gastats Auth Failure - Empty Access Token: ' . json_encode($auth));
			throw new OutOfBoundsException('Unable to get googleAnalytics auth - empty access_token');
		}
		AppLog::info('Gastats Auth Token Success');
		$this->ga->setAccessToken($auth['access_token']);
		$this->ga->setAccountId($this->config['defaults']['accountId']);
		$this->authkey = $auth['access_token'];
		// autoset defaults
		$defaults = $this->config['defaults'];
		unset($defaults['accountId']);
		$this->ga->setDefaultQueryParams($defaults);
	}

   /**
    * Refresh token. Called prior to performing each Gastat import, to prevent the token expiring part-way through.
    */
    public function reAuth() {
        $this->setup();
        $this->authkey = null;
        $this->auth();
        return !empty($this->authkey);
    }

	/**
	 * Print out the Accounts with Id => Name.
	 */
	public function profiles() {
		$this->setup();
		$profiles = $this->ga->getProfiles();
		$this->lookForErrors($profiles);
		$accounts = array();
		foreach ($profiles['items'] as $item) {
			$id = "ga:{$item['id']}";
			$name = $item['name'];
			$accounts[$id] = $name;
		}
		return $accounts;
	}

	/**
	 * Set the default params. For example the start/end dates and max-results
	 */
	public function defaults($defaults) {
		$this->setup();
		return $this->ga->setDefaultQueryParams($defaults);
	}

	/**
	 * Set the Account Id
	 */
	public function setAccountId($accountId) {
		$this->setup();
		if (substr($accountId, 0, 3) != 'ga:') {
			$accountId = 'ga:' . $accountId;
		}
		return $this->ga->setAccountId($accountId);
	}

	/**
	 *
	 */
	public function query($params) {
		$this->setup();
		AppLog::info('Gastats Query: ' . json_encode($params));
		$response = $this->ga->query($params);
		$this->lookForErrors($response);
		return $response;
	}

	/**
	 *
	 */
	public function lookForErrors($response) {
		if ($response['http_code'] != 200) {
			AppLog::error('Gastats Error: ' . json_encode($response));
			throw new OutOfBoundsException("Error: {$response['error']['code']} {$response['error']['message']}");
		}
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
		return $this->query($query);
	}
}


