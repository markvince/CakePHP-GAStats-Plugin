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
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
				
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
	}
	
	/**
	*
	*/
	function purgeCountryStats($start_date=null, $end_date=null) {
		$conditions = compact('start_date', 'end_date');
		$this->deleteAll($conditions);
	}
	
	
}
?>
