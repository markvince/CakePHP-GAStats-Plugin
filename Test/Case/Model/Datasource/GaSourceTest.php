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
	// ---------------------

	public function testProfiles() {
		$result = $this->GaSource->profiles();
		$this->assertTrue(is_array($result));
		$this->assertFalse(empty($result));
		$this->assertEqual(substr(key($result), 0, 3), 'ga:');
	}
	public function testQuery() {
		$params = array(
			'metrics' => 'ga:pageviews',
			'dimensions' => 'ga:pagePath',
			'filters' => 'ga:pagePath!@track_;ga:pagePath!~^/admin.*;ga:pagePath!~^/api.*',
			'start-date' => date('Y-m-01'),
			'end-date' => date('Y-m-t'),
			'max-results' => (int) 20,
		);
		$result = $this->GaSource->query($params);
		$this->assertTrue(array_key_exists('rows', $result));
		$this->assertEqual(count($result['rows']), 20);
		debug($result);
	}
	/* -- */
}

