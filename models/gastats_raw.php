<?php
class GastatsRaw extends GastatsAppModel {
	public $name = 'GastatsRaw';
	public $useTable = false;
	public $useDbConfig = 'gastats';
	public $max_results = 1000;
	protected $stats_data = null;
	public $stat_types = array(
		'generic-notracking' => array(
			'metrics' => array('ga:pageviews'),
			'dimensions' => array('ga:pagePath'),
			'filters' => array('ga:pagePath!@track_'), 
			),
		'webstats' => array(
			'metrics' => array('ga:pageviews','ga:visitors','ga:visits','ga:timeOnSite'),
			'dimensions' => array('year'),
			),
		);
	
	/**
	*
	*
	*/
	public function getStats() {
		return $this->stats_data;
	}
	
	/**
    * Simple function to return the $config array
    * @param array $config if set, merge with existing array
    * @return array $config
    */
	public function config($config = array()) {
		if (!empty($config) && is_array($config)) {
			$this->GoogleAnalytics->config = set::merge($this->GoogleAnalytics->config, $config);
		}
		return $this->GoogleAnalytics->config;
	}
	
	public function request($req_type = 'report', $options = array()) {
		return $this->GoogleAnalytics->request($req_type, $options);
		
	}
	
	public function report($options = array()) {
		//comma separate options
		$query = array();
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				$query[$key] = implode(",",$val);	
			} else {
				$query[$key] = $val;
			}
			
		}
		return $this->request('report', $query);
	}
	
	/**
	* date start_date (YYYY-MM-DD)
	* date end_date (YYYY-MM-DD)
	* 
	*/
	function getGAData($stat_type=null, $start_date=null, $end_date=null, $paginate=false, $options = array()) {
		if(!empty($start_date) && !empty($end_date)) {
			if (!empty($stat_type)) {
				$options = array_merge($this->stat_types[$stat_type], $options);				
			}
			$options['start-date'] = $start_date;
			$options['end-date'] = $end_date;
			$options['max-results'] = (isset($options['max-results']) ? $options['max-results'] : $this->max_results);
			$response = $this->report($options);
			
			$xml = $this->parseGAData($response);
			if (isset($xml['feed']['entry']) && is_array($xml['feed']['entry'])) {
				$num_entries =  count($xml['feed']['entry']);
				if ($num_entries > 0) {
					$this->storeGAData($xml,$stat_type,$start_date,$end_date);
					$page_count = 1;
					if ($paginate && ($num_entries == $options['max-results'])) {
						$start_index = 1; //default
						echo "Pulled page $page_count with $num_entries results.";
						//Loop until no more data
						while (isset($xml['feed']['entry']) && count($xml['feed']['entry']) > 0) {
							$start_index += $options['max-results'];
							$options['start-index'] = $start_index;
							$page_count++;
							$num_entries = 0;
							$response = $this->report($options);
							$xml = $this->parseGAData($response);
							if (isset($xml['feed']['entry']) && is_array($xml['feed']['entry'])) {
								$num_entries =  count($xml['feed']['entry']);
								echo "Pulled page $page_count with $num_entries results.";
								$this->storeGAData($xml,$stat_type,$start_date,$end_date);
							}
						}
					}	
				}	
			}
		}
		return $this->stats_data;
	}
	
	/**
	* 
	*
	*/
	function storeGAData ($xml=null, $stat_type=null, $start_date=null, $end_date=null) {
		$entries = (isset($xml['feed']['entry']) ? $xml['feed']['entry'] : array()); 
		foreach ($entries as $data) {
			if ($stat_type == 'generic-notracking') {
				if ($data['dxp:metric_attr']['name'] == 'ga:pageviews' && $data['dxp:metric_attr']['value']>0) {
				 $key = $data['dxp:dimension_attr']['value'];
				 $value = $data['dxp:metric_attr']['value'];
				 if (isset($totals[$key])) {
					 $this->stats_data[$key] = $this->stats_data[$key] + $value;
				 } else {
					 $this->stats_data[$key] = $value;
				 }
				}	
			}
		}
		/*
		foreach ($totals as $tkey=>$tval) {
			$savedata = array();
			$savedata = array('start_date'=>$start_date, 'end_date' => $end_date, 'key' => $tkey, 'value' =>$tval);
			$this->create();
			$saved = $this->save($savedata);
			$last_id = $this->getInsertId(); 
			debug($saved);
			debug($last_id);
			die();
			if ($this->save($savedata)) {
				$log = $this->getDataSource()->getLog(false, false);
				debug($log);
				die();
			} else {
				echo 'Cannot save data';
				die();
			}
		}
		$this->useDbConfig = $savedDbConfig;
		*/
	}
	
	function parseGAData($data) {
		$xml = null;
		if(stripos($data,'xml')!==false) {
			$xml = $xml = $this->xml2array($data); 
		}
		return $xml;
	}
}
?>
