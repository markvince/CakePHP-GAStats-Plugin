<?php
App::import('Core', 'HttpSocket');
class GASource extends DataSource {
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
			'source' => 'Google-GAStats-AlliedHealthMedia.com-0.0.1',
		),
		//Other config
		'url-login' => 'https://www.google.com/accounts/ClientLogin',
		'url-list-accounts' => 'https://www.google.com/analytics/feeds/accounts/default',
		'url-report' => 'https://www.google.com/analytics/feeds/data',
		'id'=>'need-to-configure',			//numeric id of profile to query 
		// other potentially configurable settings
		//'modelLog' => 'GAApiLog',	//store url calls
		//'modelData' => 'GAData',	//store aggregate data
		
		);
	protected $authkey = null;
	protected $authHeader = null;

	/**
	* setup the config
	* setup the HttpSocket class to issue the requests
	* @param array $config
	*/
	public function  __construct($config = array()) {
		App::import('Core', array('Xml', 'HttpSocket'));
		$this->config = set::merge($this->config, $config);
		$this->HttpSocket = new HttpSocket();
		$this->login();
				
		//App::import('Model', $this->config['modelLog']);
		//$this->modelLog =& ClassRegistry::init($this->config['modelLog']);
		//if (!is_object($this->modelLog)) {
		//	return $this->cakeError('missingModel', 'Missing "modelLog" model: '.$this->config['modelLog']);
		//}
		return parent::__construct($config);
	}
	
	
	public function report($options = array()) {
		//comma separate options
		$query = array();
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $valk=>$valv) {
					if (in_array($key, array('dimensions', 'metrics', 'filters')) && strpos($valv, 'ga:') === false) {
						$val[$valk]='ga:'.$valv;
					}
				}
				
				if ($key == 'filters') {
					$query[$key] = implode(";",$val);
				} else {
					$query[$key] = implode(",",$val);
				}
			} else {
				if (in_array($key, array('dimensions', 'metrics', 'filters')) && strpos($val, 'ga:') === false) {
					$val = 'ga:'.$val;
				}
				$query[$key] = $val;
			}
			
		}
		//debug($query); die();
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
			$query['ids'] = 'ga:'.$this->config['id'];
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
    	if (is_null($this->authkey)) {
    		$query = $this->config['auth'];
    		$response = $this->request('login',$query);
    		//strip out auth key
    		preg_match('/Auth=([0-9A-Za-z_-]+)$/',$response,$auth_matches);
    		if (isset($auth_matches) && isset($auth_matches[1]) && !empty($auth_matches[1])) {
    			$this->authkey = $auth_matches[1];
    			$this->authHeader = array("Content-Type" => "text/xml", "Authorization" => "GoogleLogin auth=$this->authkey");
    			return true;
    		} else {
    			return false;
    		}
    	} else{
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

?>
