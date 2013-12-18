<?php
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('GastatsAppModel', 'Model');

App::uses('AppTestCase', 'Lib');
class GastatsAppModelTestCase extends AppTestCase {

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
		ClassRegistry::flush();
	}

	/**
	 * Test validation rules
	 *
	 * @return void
	 * @access public
	 */
	public function testReport() {
		$result = $this->GastatsAppModel->report($input);
		debug($result);
	}
}

