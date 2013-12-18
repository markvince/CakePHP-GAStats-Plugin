<?php
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('GaSource', 'Gastats.Model/Datasource');
App::uses('GastatsAppModel', 'Gastats.Model');

App::uses('AppTestCase', 'Lib');
class GaSourceTestCase extends AppTestCase {

	/**
	 * Autoload entrypoint for fixtures dependecy solver
	 *
	 * @var string
	 * @access public
	 */
	public $plugin = 'Gastats';

	/**
	 * Test to run for the test case (e.g array('testFind', 'testView'))
	 * If this attribute is not empty only the tests from the list will be executed
	 *
	 * @var array
	 * @access protected
	 */
	protected $_testsToRun = array();

	public $fixtureGroups = array(
	);

	/**
	 * Fixtures
	 *
	 * @var array
	 * @access public
	 */
	public $fixtures = array(
		//'app.user',
		'app.member'
	);


	/**
	 * Start Test callback
	 *
	 * @param string $method
	 * @return void
	 * @access public
	 */
	public function startTest($method) {
		parent::startTest($method);
		$this->GastatsAppModel = ClassRegistry::init('Gastats.GastatsAppModel');
		// instantiate a basic, mockup of the class (unconfigured)
		$this->GaSource = new GaSource();
		// add in custom configuration
		$this->config = array();
		// OR this version gets the application configured version
		$this->GaSource = ConnectionManager::getDataSource('gastats');
		// not mocking HttpSocket because we want real requests
		//$this->GaSource->HttpSocket = $this->getMock('HttpSocket');
	}

	/**
	 * End Test callback
	 *
	 * @param string $method
	 * @return void
	 * @access public
	 */
	public function endTest($method) {
		parent::endTest($method);
		unset($this->GastatsAppModel);
		unset($this->GaSource);
		ClassRegistry::flush();
	}

	/*
	public function testRequestBeforeAuth() {
		$this->assertNull($this->GaSource->authkey);
		$this->assertNull($this->GaSource->authHeader);
		$result = $this->GaSource->request('login');
		$this->assertNull($this->GaSource->authkey);
		$this->assertNull($this->GaSource->authHeader);
		$this->assertTrue(strpos($result, 'BadAuth'));
	}
	/*-*/
	public function testLogin() {
		$this->assertNull($this->GaSource->authkey);
		$this->assertNull($this->GaSource->authHeader);
		$this->assertTrue($this->GaSource->login());
		$this->assertFalse(empty($this->GaSource->authkey));
		$this->assertFalse(empty($this->GaSource->authHeader));
	}
	/*
	public function testRequest() {
		$action = 'report';
		$query = array();
		$requestOptions = array();
		$this->assertNull($this->GaSource->authkey);
		$this->assertNull($this->GaSource->authHeader);
		$result = $this->GaSource->request('login', $query, $requestOptions);
		$this->assertNull($this->GaSource->authkey);
		$this->assertNull($this->GaSource->authHeader);
		$this->assertTrue(strpos($result, 'BadAuth'));

		debug($result);
	}

	/*
	public function testReport() {
		$input = array();
		$result = $this->GaSource->report($input);
		debug($result);
	}
	/* -- */
}

