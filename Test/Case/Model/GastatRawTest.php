<?php
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('GastatsAppModel', 'Gastats.Model');
App::uses('GastatsRaw', 'Gastats.Model');

App::uses('AppTestCase', 'Lib');
class GastatsRawTestCase extends AppTestCase {

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
		$this->GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
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
		unset($this->GastatsRaw);
		ClassRegistry::flush();
	}

	/**
	 */
	public function testReport() {
		//$result = $this->GastatsRaw->getGAData($stat_type, $start_date, $end_date, $paginate, $options) {

			$start = date('Y-m-01', strtotime(date('Y-m-01').' -1 month'));
			$stop = date('Y-m-t', strtotime(date('Y-m-01').' -1 month'));

		$result = $this->GastatsRaw->getGAData('generic-notracking-noadmin', $start, $stop, false, array());
		debug($result);
	}
}

