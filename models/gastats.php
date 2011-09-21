<?php

class Gastats extends GastatsAppModel {
	public $name = "Gastats";
	public $useTable = "gastats";
	protected $source = 'gastats';
	protected $stats_data = array();
	public $max_results = 1000;
	public $stat_types = array(
		'generic' => array(
			'metrics' => array('pageviews'),
			'dimensions' => array('pagePath'),
			),
		'generic-notracking' => array(
			'metrics' => array('pageviews'),
			'dimensions' => array('pagePath'),
			'filters' => array('pagePath!@track_'), 
			),
		'generic-notracking-noadmin' => array(
			'metrics' => array('pageviews'),
			'dimensions' => array('pagePath'),
			'filters' => array('pagePath!@track_', 'pagePath!~%5E%2Fadmin.*', 'pagePath!~%5E%2Fapi.*'), 
			),
		'webstats' => array(
			'metrics' => array('pageviews','visitors','visits','timeOnSite'),
			'dimensions' => array('year'),
			),
		);
	
	/**
	*
	*/
	public function config($config = array()) {
		$this->loadGA();
		if (!empty($config) && is_array($config)) {
			$this->GoogleAnalytics->config = set::merge($this->GoogleAnalytics->config, $config);
		}
		return $this->GoogleAnalytics->config;
	}
	
	/**
	*
	*/
	public function purgeStats($start_date=null, $end_date=null) {
		$conditions = array('start_date' => $start_date, 'end_date' => $end_date);
		$this->deleteAll($conditions);
	}
	
	/**
	*
	*/
	public function getGAData($stat_type=null, $start_date=null, $end_date=null, $paginate=false, $options = array()) {
		$this->loadGA();
		$this->stats_data = null;
		if(!empty($start_date) && !empty($end_date)) {
			if (!empty($stat_type)) {
				$options = array_merge($this->stat_types[$stat_type], $options);				
			}
			$options['start-date'] = $start_date;
			$options['end-date'] = $end_date;
			$options['max-results'] = (isset($options['max-results']) ? $options['max-results'] : $this->max_results);
			$response = $this->GoogleAnalytics->report($options);
			
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
							$response = $this->GoogleAnalytics->report($options);
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
			$this->storeGAData('save', $stat_type, $start_date, $end_date);
		}
		return $this->stats_data;		
	}
	
	/**
	*
	*/
	function storeGAData ($xml=null, $stat_type=null, $start_date=null, $end_date=null) {
		
		if ($xml == 'save' && !empty($stat_type) && !empty($start_date) && !empty($end_date)) {
			foreach ($this->stats_data as $stat_type => $stat_details) {
				foreach ($stat_details as $key =>$val) {
					$savedata = array('start_date'=>$start_date, 'end_date' => $end_date, 'key' => $key, 'value' =>$val, 'stat_type'=>$stat_type);
					$this->create();
					if (!$this->save($savedata)) {
						die("Error saving GA data. $stat_type $start_date $end_date");
					}		
				}
			}
			
		} else {
			//store gathered data in array while gathering more data
			$entries = (isset($xml['feed']['entry']) ? $xml['feed']['entry'] : array());
			if (in_array($stat_type, array('generic', 'generic-notracking', 'generic-notracking-noadmin'))) {
				foreach ($entries as $data) {
					if ($data['dxp:metric_attr']['name'] == 'ga:pageviews' && $data['dxp:metric_attr']['value']>0) {
					 $key = $data['dxp:dimension_attr']['value'];
					 $value = $data['dxp:metric_attr']['value'];
					 if (isset($this->stats_data[$key])) {
						 $this->stats_data[$stat_type][$key] = $this->stats_data[$key] + $value;
					 } else {
						 $this->stats_data[$stat_type][$key] = $value;
					 }
					}	
				} 
			}	
			elseif (in_array($stat_type, array('webstats'))) {
				$attr = 0;
				foreach ($this->stat_types[$stat_type]['metrics'] as $metric) {
					$this->stats_data[$stat_type][$metric] = $entries['dxp:metric'][$attr.'_attr']['value'];
					$attr++;
				}
			}
		}
	}
	
	//====================================
	
	public function loadGA() {
		App::import('Core','ConnectionManager');
		$this->GoogleAnalytics = ConnectionManager::getDataSource($this->source);
		$this->GoogleAnalytics->login();
	}
	
	function parseGAData($data) {
		$xml = null;
		if(stripos($data,'xml')!==false) {
			$xml = $this->xml2array($data); 
		}
		return $xml;
	}
	
	public function getStats() {
		return $this->stats_data;
	}
}
?>
