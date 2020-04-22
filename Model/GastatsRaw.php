<?php

class GastatsRaw extends GastatsAppModel {
	public $name = "GastatsRaw";
	public $useTable = "gastats_raws";
	protected $stats_data = array();
	public $max_results = 5000;
	protected $results_cap = 10000;
	public $errors = array();
	public $page_path = '';
	public $stat_type = 'generic-notracking-noadmin';
	protected $metric_count = 0;

	/*Prdefined stat types.  Don't add ga: in front of the metrics/dimensions/filters (they will be added later)*/
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
			'filters' => array('pagePath!@track_', 'pagePath!~^/admin.*', 'pagePath!~^/api.*'),
			),
		//-------
		'webads' => array(
			'metrics' => array('pageviews'),
			'dimensions' => array('pagePath'),
			'filters' => array('pagePath=~^/track_.*'),
			),
		'webad-events' => array(
			'metrics' => array('totalEvents','uniqueEvents'),
			'dimensions' => array('eventAction'),
			'filters' => array('eventAction=~^/track_[banner|spotlight|logo]'),
			),
		//-------
		'webstats' => array(
			'metrics' => array('pageviews','visitors','visits','sessionDuration', 'avgSessionDuration'),
			'dimensions' => array('year'),
			),
		//-------
		'webchannels' => array(
			'metrics' => array('pageviews','uniquePageviews','timeOnpage', 'avgTimeOnPage','exits'),
			'dimensions' => array('pagePath'),
			'filters' => array(), //dynamically set at run time via $this->page_path, requires 'channels' data in database.php
			),
		//-------
		'country' => array(
			'metrics' => array('visits'),
			'dimensions' => array('country'),
			),
		//-------
		'videos' => array(
			//'metrics' => array('totalEvents','avgEventValue'),
			'metrics' => array('totalEvents'),
			'dimensions' => array('eventAction','eventLabel','pagePath'),
			'filters' => array('eventAction=~^/track_video_view-range'),
			),
		//-------
		);

	/**
	*
	*/
	public function config($config = array()) {
		$this->loadGA();
		if (!empty($config) && is_array($config)) {
			$this->GoogleAnalytics->config = Set::merge($this->GoogleAnalytics->config, $config);
		}
		return $this->GoogleAnalytics->config;
	}

	/**
	*
	*
	*/
	public function processGAStats($start_date=null,$end_date=null) {
		//run defaults
		AppLog::info('Gastats - Processing Raw Stats');
		$this->page_path='';
		$this->purgeStats($this->stat_type, $start_date, $end_date);
		return $this->getGAData($this->stat_type, $start_date, $end_date, true);
	}

	/**
	* Query gastats_raws table
	*
	*/
	public function getStats($stat_type=null,$start_date=null,$end_date=null,$key=null) {
		$conditions = [
			'stat_type' => $stat_type,
			'OR' => $this->calculateDateRanges($start_date, $end_date),
			];
		if (!empty($key)) {
			$conditions['GastatsRaw.key'] = $key;
		}
		$results = $this->find('all',compact('conditions'));
		return $results;
	}

	/**
	*
	*/
	public function purgeStats($stat_type = null, $start_date=null, $end_date=null, $authorize=false) {
		if ($stat_type == 'all' && $start_date == 'all' && $end_date == 'all' && $authorize) {
			//this will remove all data from the table
			$conditions = array('1');
		} elseif ($stat_type == 'all' && $start_date == 'all' && $end_date == 'all') {
			$this->errors[] = 'Missing authorization.  Stats not purged.';
			return false;
		} else if ($stat_type == 'all' && !empty($start_date) && !empty($end_date)) {
			$conditions = array('start_date' => $start_date, 'end_date' => $end_date);
		} else {
			$conditions = array('stat_type'=>$stat_type, 'start_date' => $start_date, 'end_date' => $end_date);
		}
		return $this->deleteAll($conditions);
	}

	/**
	*
	*/
	public function getGAData($stat_type=null, $start_date=null, $end_date=null, $paginate=false, $options = array()) {
		$this->loadGA();
		$this->stats_data = null;
		$this->stat_types = (isset($this->GoogleAnalytics->config['stat_types']) ? Set::merge($this->stat_types, $this->GoogleAnalytics->config['stat_types']) : $this->stat_types);
		if(!empty($start_date) && !empty($end_date)) {
			if (!empty($stat_type)) {
				$options = array_merge($this->stat_types[$stat_type], $options); //add/replace stat_type parameters
			}
			$options['start-date'] = $start_date;
			$options['end-date'] = $end_date;
			$options['max-results'] = (isset($options['max-results']) ? $options['max-results'] : $this->max_results);
			if ($options['max-results'] >= $this->results_cap) {
				$this->errors[] = "Maximum allowed results of $this->results_cap exceeded: ".$options['max-results'];
				return false;
			}
			if (!empty($this->page_path)) {
				$options['filters'] = Set::merge($options['filters'],array('pagePath=~^/'.$this->page_path.'/?$'));
			}
			$this->metric_count = count($options['metrics']);//will define how the data is stored
			$response = $this->GoogleAnalytics->report($options);
			$num_entries = (empty($response['rows']) ? 0 : count($response['rows']));
			if ($num_entries > 0) {
				$this->storeGAData($response,$stat_type,$start_date,$end_date);
					$page_count = 1;
					if ($paginate && ($num_entries == $options['max-results'])) {
						$start_index = 1; //default
						echo "Pulled page $page_count with $num_entries results.\n";
						AppLog::info('Gastats results page '. $page_count .': ' . $num_entries . ' results');
						//Loop until no more data
						while (isset($response['rows']) && count($response['rows']) > 0) {
							$start_index += $options['max-results'];
							$options['start-index'] = $start_index;
							$page_count++;
							$num_entries = 0;
							$response = $this->GoogleAnalytics->report($options);
							if (isset($response['rows']) && is_array($response['rows'])) {
								$num_entries =  count($response['rows']);
								echo "Pulled page $page_count with $num_entries results.\n";
								AppLog::info('Gastats results page '. $page_count .': ' . $num_entries . ' results');
								$this->storeGAData($response,$stat_type,$start_date,$end_date);
							} else if (!empty($response['totalResults'])) {
								AppLog::info('Gastats total raw results: '. $response['totalResults']);
							}
						}
					}
			}
			if (!$this->errors()) {
				//Purge old stats matching stat type and date range
				if ($stat_type != 'webchannels') {
					$this->purgeStats($stat_type, $start_date, $end_date);
				}
				return $this->storeGAData('save', $stat_type, $start_date, $end_date);
			}
		}
		return false;
	}

	/**
	*
	*/
	function storeGAData ($response=null, $stat_type=null, $start_date=null, $end_date=null) {
		if ($response == 'save' && !empty($stat_type) && !empty($start_date) && !empty($end_date)) {
			$this->stats_data = (is_array($this->stats_data) ? $this->stats_data : array());
			foreach ($this->stats_data as $stat_type => $stat_details) {
				foreach ($stat_details as $key =>$val) {
					$savedata = array('start_date'=>$start_date, 'end_date' => $end_date, 'key' => $key, 'value' =>$val, 'stat_type'=>$stat_type);
					//debug($savedata);
					$this->create(false);
					if (!$this->save($savedata)) {
						$this->errors[] = "Error saving GA data. $stat_type $start_date $end_date";
						return false;
					}
				}
			}

		} else {
			//store gathered data in array while gathering more data
			if (strpos($stat_type,'webchannels') !== false && $this->metric_count > 1) {
				//store multiple metrics for specified page path
				//should only be one result per channel
				foreach ($response['rows'] as $entry) {
					foreach ($entry as $col_index => $col_val) {
						if ($response['columnHeaders'][$col_index]['columnType'] == "METRIC") {
							$metric = str_replace("ga:", "", $response['columnHeaders'][$col_index]['name']);
							$this->stats_data[$stat_type][$this->page_path.'|'.$metric] = $col_val;
						}
					}
				}
			} elseif (strpos($stat_type,'videos') !== false) {
				//store multiple metrics for specified page path
				$action_index = $label_index = $page_index = 0;
				foreach ($response['columnHeaders'] as $header_index => $header) {
					if ($header['name'] == "ga:eventAction") {
						$action_index = $header_index;
					}
					if ($header['name'] == "ga:eventLabel") {
						$label_index = $header_index;
					}
					if ($header['name'] == "ga:pagePath") {
						$page_index = $header_index;
					}
				}
				foreach ($response['rows'] as $entry) {
					foreach ($entry as $col_index => $col_val) {
						if ($response['columnHeaders'][$col_index]['columnType'] == "METRIC") {
							$metric = str_replace("ga:", "", $response['columnHeaders'][$col_index]['name']);
							$metric_key = $entry[$action_index].'|'.$entry[$label_index].'|'.$entry[$page_index].'|'.$metric;
							$this->stats_data[$stat_type][$metric_key] = $col_val;
						}
					}
				}
			} elseif (strpos($stat_type,'webad-events') !== false) {
				//store multiple metrics for specified event
				$eventAction_index = $totalEvent_index = $uniqueEvent_index = 0;
				foreach ($response['columnHeaders'] as $header_index => $header) {
					if ($header['name'] == "ga:eventAction") {
						$eventAction_index = $header_index;
					}
					if ($header['name'] == "ga:totalEvents") {
						$totalEvent_index = $header_index;
					}
					if ($header['name'] == "ga:uniqueEvents") {
						$uniqueEvent_index = $header_index;
					}
				}
				foreach ($response['rows'] as $entry) {
					foreach ($entry as $col_index => $col_val) {
						if ($response['columnHeaders'][$col_index]['columnType'] == "METRIC") {
							$metric = str_replace("ga:", "", $response['columnHeaders'][$col_index]['name']);
							if (strpos($entry[$eventAction_index], "_click") && $metric == 'uniqueEvents') {
								continue; //skip the unique counts for clicks
							}
							$metric_key = $entry[$eventAction_index].'|'.$metric;
							$this->stats_data[$stat_type][$metric_key] = $col_val;
						}
					}
				}
			} elseif ($this->metric_count == 1) {
				//store single metric value for single dimension
				foreach ($response['rows'] as $entry) {
					foreach ($entry as $col_index => $col_val) {
						if ($response['columnHeaders'][$col_index]['columnType'] == "DIMENSION") {
							$key = $col_val;
						} elseif ($response['columnHeaders'][$col_index]['columnType'] == "METRIC") {
							$value = $col_val;
						}
					}
					if (isset($this->stats_data[$stat_type][$key])) {
						$this->stats_data[$stat_type][$key] = $this->stats_data[$stat_type][$key] + $value;
					} else {
						$this->stats_data[$stat_type][$key] = $value;
					}
				}
			} elseif ($this->metric_count > 1) {
				//store multiple metrics for site
				foreach ($response['rows'] as $entry) {
					foreach ($entry as $col_index => $col_val) {
						if ($response['columnHeaders'][$col_index]['columnType'] == "METRIC") {
							$metric = str_replace("ga:", "", $response['columnHeaders'][$col_index]['name']);
							$this->stats_data[$stat_type][$metric] = $col_val;
						}
					}
				}
			}
		}

		return true;
	}

	//====================================

	function parseGAData($data) {
		if (is_array($data)) {
			return $data;
		}
		$xml = null;
		if (stripos($data,'xml')!==false) {
			$xml = $this->xml2array($data);
		} else {
			//possible error returned
			$this->errors[] = $data;
		}
		return $xml;
	}

	public function errors($display=false) {
		if (count($this->errors) > 0) {
			if ($display) {
				print_r($this->errors);
			}
			return true;
		}
		return false;
	}

	//======================================

	public function getContent($start_date=null, $end_date=null, $limit=null, $path='', $wildcard=false) {
		//Get 'generic' stat type
		$stat_type = $this->stat_type;
		if (!is_null($start_date) && !is_null($end_date)) {
			$conditions = compact('start_date', 'end_date','stat_type');
		} else {
			$conditions = compact('stat_type');
		}
		$wildcard = ($wildcard ? '%' : '');
		if (!empty($path)) {
			$conditions['GastatsRaw.key LIKE'] = "$path$wildcard";
		}
		$order = 'GastatsRaw.start_date ASC, GastatsRaw.key ASC';
		$content = $this->find('all',compact('conditions','order','limit'));
		return $content;

	}

	//====================================

	public function processGAStatsAll($start=null, $stop=null) {
		if (empty($start) || empty($stop)) {
			//default to last month
			$start = date('Y-m-01', strtotime(date('Y-m-01').' -1 month'));
			$stop = date('Y-m-t', strtotime(date('Y-m-01').' -1 month'));
			echo "Date range not supplied, using: $start to $stop\n";
		}
		$GastatsAd = ClassRegistry::init('Gastats.GastatsAd');
		$GastatsCountry = ClassRegistry::init('Gastats.GastatsCountry');
		$GastatsWebchannel = ClassRegistry::init('Gastats.GastatsWebchannel');
		$GastatsWebstat = ClassRegistry::init('Gastats.GastatsWebstat');
		$GastatsVideo = ClassRegistry::init('Gastats.GastatsVideo');

		echo "Pulling the Raw generic stats\n";
		$this->processGAStats($start, $stop);

		echo "Pulling the Ad stats\n";
		$GastatsAd->loadGA();
		$GastatsAd->GoogleAnalytics->reAuth();
		$GastatsAd->processGAStats($start,$stop,true);

		echo "Pulling the Country stats\n";
		$GastatsCountry->loadGA();
		$GastatsCountry->GoogleAnalytics->reAuth();
		$GastatsCountry->processGAStats($start,$stop,true);


		echo "Pulling the Webchannel stats\n";
		$GastatsWebchannel->loadGA();
		$GastatsWebchannel->GoogleAnalytics->reAuth();
		$GastatsWebchannel->processGAStats($start,$stop,true);

		echo "Pulling the Webstat stats\n";
		$GastatsWebstat->loadGA();
		$GastatsWebstat->GoogleAnalytics->reAuth();
		$GastatsWebstat->processGAStats($start,$stop,true);

		echo "Pulling the Video stats\n";
		$GastatsVideo->loadGA();
		$GastatsVideo->GoogleAnalytics->reAuth();
		$GastatsVideo->processGAStats($start,$stop,true);

		return true;
	}
}
?>
