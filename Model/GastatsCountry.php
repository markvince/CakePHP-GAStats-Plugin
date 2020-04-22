<?php

class GastatsCountry extends GastatsAppModel {
	public $name = "GastatsCountry";
	public $useTable = "gastats_countries";
	public $stats_type = 'country';

	/**
	 * Pull webads stats and aggregate the URLs and store in gastats_ads
	 * If refresh is set to true it will repull the data from Google.
	 *
	 */
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		AppLog::info('Gastats - Processing Country Stats');
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
		$GastatsRaw->page_path='';
		if ($refresh) {
			$GastatsRaw->getGAData($this->stats_type,$start_date,$end_date,true);
			$this->purgeCountryStats($start_date, $end_date);
		}
		$stats = $GastatsRaw->getStats($this->stats_type,$start_date,$end_date);
		foreach ($stats as $stat) {
			$stat = $stat['GastatsRaw'];
			$data = array(
				'start_date'	=> $start_date,
				'end_date' 		=> $end_date,
				'country' 		=> $stat['key'],
				'visits' 		=> $stat['value'],
			);
			$this->create();
			$this->save($data);
		}

		AppLog::info('Gastats - Processing Country Stats Complete');
	}

	/**
	 *
	 *
	 */
	public function getUSPercentage($start_date=null, $end_date=null) {
		$conditions = ['OR' => $this->calculateDateRanges($start_date, $end_date)];
		$conditions['country'] = 'United States';
		$fields = array('SUM(visits) visits');
		$us_total = $this->find('first', compact('conditions', 'fields'));
		unset($conditions['country']);
		$fields = array('SUM(visits) visits');
		$total = $this->find('first', compact('conditions', 'fields'));
		$us_percent = $us_total['GastatsCountry']['visits'] / max(1, $total['GastatsCountry']['visits']);
		return round($us_percent * 100, 2);
	}


	/**
	 *
	 */
	public function purgeCountryStats($start_date=null, $end_date=null) {
		$conditions = compact('start_date', 'end_date');
		$this->deleteAll($conditions);
	}

}

