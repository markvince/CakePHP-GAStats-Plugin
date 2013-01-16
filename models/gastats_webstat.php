<?php

class GastatsWebstat extends GastatsAppModel {
	public $name = "GastatsWebstat";
	public $useTable = "gastats_webstats";
	public $stats_type = 'webstats';
			
	/**
	* Pull webads stats and aggregate the URLs and store in gastats_ads
	* If refresh is set to true it will repull the data from Google.
	*
	*/
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
				
		if ($refresh) {
			$GastatsRaw->getGAData($this->stats_type,$start_date,$end_date,true);
			$this->purgeWebStats($start_date, $end_date);
		}
		
		$stats = $GastatsRaw->getStats($this->stats_type,$start_date,$end_date);
		
		foreach ($stats as $stat) {
			$stat = $stat['GastatsRaw'];
			$data = array(
				'start_date'	=> $start_date,
				'end_date' 		=> $end_date,
				'metric' 		=> $stat['key'],
				'value' 		=> $stat['value'],
				);
			$this->create();
			$this->save($data);
		}
	}
	
	/**
	*
	*/
	function purgeWebStats($start_date=null, $end_date=null) {
		$conditions = compact('start_date', 'end_date');
		$this->deleteAll($conditions);
	}
	
	
	/**
	*
	*
	*/
	function getWebStats($start_date, $end_date) {
		$conditions = compact('start_date','end_date');
		$order = 'metric ASC';
		$stats_array = $this->find('all',compact('conditions','order'));
		$stats = array();
		foreach ($stats_array as $stat) {
			$stat = $stat['GastatsWebstat'];
			if (isset($this->metrics[$stat['metric']]) && $this->metrics[$stat['metric']]['display'] == true) {
				if (isset($this->metrics[$stat['metric']]['uom'])) {
					if (in_array($this->metrics[$stat['metric']]['uom'],array('time'))) {
						//GA defaults to seconds, convert to hms
						$stats[$this->metrics[$stat['metric']]['header']] = $this->_secondsDisplay($stat['value'],$this->metrics[$stat['metric']]['uom']);
					}
				} else {
						$stats[$this->metrics[$stat['metric']]['header']] = $stat['value'];		
				}	
			} elseif (isset($this->metrics[$stat['metric']]) && $this->metrics[$stat['metric']]['display'] == false) {
				//flagged to not display
				
			} else {
				//unknown, display by default
				$stats[$stat['metric']] = $stat['value'];
			}
		}
		
		return $stats;
	}
	
}
?>
