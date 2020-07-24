<?php

class GastatsAd extends GastatsAppModel {
	public $name = "GastatsAd";
	public $useTable = "gastats_ads";
	//public $stats_type = 'webads';
	public $stats_type = 'webad-events';
	
	
	/**
	* Pull webads stats and aggregate the URLs and store in gastats_ads
	* If refresh is set to true it will repull the data from Google.
	*
	*/
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		AppLog::info('Gastats - Processing Ad Stats');
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
		$GastatsRaw->page_path='';		
		if ($refresh) {
			$GastatsRaw->getGAData($this->stats_type,$start_date,$end_date,true);
			$this->purgeAdStats($start_date, $end_date);
		}
		$stats = $GastatsRaw->getStats($this->stats_type,$start_date,$end_date);
		//0 - track_banner_view
		//1 - GEN (location)
		//2 - ABC (sponsor)
		//3 - 42 (ad_id)
		//4 - img (type of banner)
		//5 - 71 (corp_id)
		//6 - cp_banner_600x154?ref=... (banner slot and page banner was on) 

		//0 - track_spotlight_view
		//1 - {event_id}
		//2 = slot (hp_spotlight/ceu_spotlight)
		$agstats = array();
		foreach ($stats as $stat) {
			$stat = $stat['GastatsRaw'];
			if (stripos($stat['key'], 'track_') === false) {
				continue;
			}
			$stat_key_split = explode('|',$stat['key']);
			$stat_count_type = (isset($stat_key_split[1]) ? $stat_key_split[1] : 'totalEvents');
			$metric_view_types = array('totalEvents' => 'view', 'uniqueEvents' => 'unique-view');
			$metric_view = $metric_view_types[$stat_count_type];
			$url = explode('?',$stat_key_split[0]);
			$urla = explode('/',$url[0]);
			if ($urla[0] == "") {
				array_shift($urla);
			}
			if (in_array($urla[0], array("track_banner_click", "track_logo_click"))) {
				if(isset($agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]])) {
						$agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] += $stat['value'];
				} else {
					$agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] = $stat['value'];
				}
			} else if (in_array($urla[0], array("track_banner_view", "track_logo_view"))) {
				if(isset($agstats[$metric_view][$urla[1]][$urla[5]][$urla[3]][$urla[6]])) {
						$agstats[$metric_view][$urla[1]][$urla[5]][$urla[3]][$urla[6]] += $stat['value'];
				} else {
					$agstats[$metric_view][$urla[1]][$urla[5]][$urla[3]][$urla[6]] = $stat['value'];
				}
			} else if (in_array($urla[0], array("track_spotlight_view"))) {
				$corp_id = 0; //will be backfilled later
				if (isset($agstats[$metric_view]['GEN'][0][$urla[1]][$urla[2]])) {
					$agstats[$metric_view]['GEN'][$corp_id][$urla[1]][$urla[2]] += $stat['value'];
				}
				else {
					$agstats[$metric_view]['GEN'][$corp_id][$urla[1]][$urla[2]] = $stat['value'];
				}
			} else if (in_array($urla[0], array("track_spotlight_click"))) {
				$corp_id = 0; //will be backfilled later
				if (isset($agstats['click']['GEN'][0][$urla[1]][$urla[2]])) {
					$agstats['click']['GEN'][$corp_id][$urla[1]][$urla[2]] += $stat['value'];
				}
				else {
					$agstats['click']['GEN'][$corp_id][$urla[1]][$urla[2]] = $stat['value'];
				}
			}
		}

		//attempt to import aggregated data into mysql table
		foreach ($agstats as $ad_stat_type => $adstats) {
			foreach ($adstats as $location => $locstats) {
				foreach ($locstats as $corp_id => $corpstats) {
					foreach ($corpstats as $ad_id => $ad_slots) {
						foreach ($ad_slots as $ad_slot => $value) {
							$data = compact('start_date','end_date','ad_stat_type','location','corp_id','ad_id','ad_slot','value');
							$this->create();
							$this->save($data);
						}
					}
				}
			}
		}

		//DFP Data
		//$this->processDFPTotals($start_date, $end_date);
		//$this->processDFPReach($start_date, $end_date);

		AppLog::info('Gastats - Processing Ad Stats Complete');
		
	}
	
	/**
	*
	*/
	function purgeAdStats($start_date=null, $end_date=null) {
		$conditions = compact('start_date', 'end_date');
		$this->deleteAll($conditions);
	}
	
	/**
	*
	*/
	public function getAds($corp_id=0, $start_date=null, $end_date=null, $slots = array()) {
		if ($corp_id == 0) {
			$conditions = ['OR' => $this->calculateDateRanges($start_date, $end_date)];
		} else {
			$conditions = [
				'corp_id' => $corp_id,
				'OR' => $this->calculateDateRanges($start_date, $end_date),
			];

		}		
		$ads_array = $this->find('all',compact('conditions'));
		$ads = array();
		$corps=array();
		//prep data for display
		foreach ($ads_array as $ad) {
			$ad = $ad['GastatsAd'];
			$ad_id = $ad['ad_id'];
			foreach ($slots as $id_prefix) {
				if (strpos($ad['ad_slot'], $id_prefix) !== FALSE) {
					$ad_id = $id_prefix.'-'.$ad_id;
				}
			}
			if (isset($ads['unique'][$ad_id][$ad['location']][$ad['ad_slot']][$ad['ad_stat_type']])){
				$ads['unique'][$ad_id][$ad['location']][$ad['ad_slot']][$ad['ad_stat_type']] += $ad['value'];
			} else {
				$ads['unique'][$ad_id][$ad['location']][$ad['ad_slot']][$ad['ad_stat_type']] = $ad['value'];
			}
			$corps[$ad_id] = $ad['corp_id'];
			//breakdown
			//Total by stat type only (click/view)
			if (isset($ads['group'][$ad_id][$ad['ad_stat_type']])) {
				$ads['group'][$ad_id][$ad['ad_stat_type']]['total'] += $ad['value'];
			} else {
				$ads['group'][$ad_id][$ad['ad_stat_type']]['total'] = $ad['value'];
				$ads['group'][$ad_id]['ad_stat_types'][$ad['ad_stat_type']] = 1;
			}
			//Total by location and ad stat type
			if (isset($ads['group'][$ad_id][$ad['ad_stat_type']][$ad['location']]['total'])) {
				$ads['group'][$ad_id][$ad['ad_stat_type']][$ad['location']]['total'] += $ad['value'];
			} else {
				$ads['group'][$ad_id][$ad['ad_stat_type']][$ad['location']]['total'] = $ad['value'];
				$ads['group'][$ad_id]['ad_locations'][$ad['location']] = 1;
			}
			
		}
		return compact('corp_id','start_date','end_date','ads','corps','slots');
	}

	public function getMaxViewsBySlot($ad_slot='', $start_date=null, $end_date=null) {
		//To Enable multi-month query we need to run this in a loop pulling the 'max' for each month
		$start_year = date('Y', strtotime($start_date));
		$start_month = date('m', strtotime($start_date));
		$end_year = date('Y', strtotime($end_date));
		$end_month = date('m', strtotime($end_date));
		//Build list of months
		$year = $start_year;
		$month = $start_month;
		$months = array();
		while($year < $end_year || ($year == $end_year && $month <= $end_month)) {
			$months[] = array('start' => date('Y-m-01', strtotime("$year-$month-01")), 'end' => date('Y-m-t', strtotime("$year-$month-01")));
			$month++;
			if ($month == 13) {
				$month = 1;
				$year++;
			}
		}
		$total_max = 0;
		foreach ($months as $month) {
			$start_date = $month['start'];
			$end_date = $month['end'];
			$conditions = compact('ad_slot', 'start_date', 'end_date');
			$conditions['ad_stat_type'] = 'view';
			$conditions['location'] = 'GEN';
			$fields = array('MAX(value) views');
			$result = $this->find('first', compact('conditions', 'fields'));
			$total_max += (isset($result['GastatsAd']['views']) ? $result['GastatsAd']['views'] : 0);
		}
		return ($total_max === 0 ? '' : $total_max);
	}

	public function validateERMA($start_date, $end_date, $slot='hp_erma') {
		$ermas = $this->find('all', ['conditions' => ['start_date' => $start_date, 'end_date' => $end_date, 'ad_slot' => $slot]]);
		$delete_ids = [];
		$max_views = $max_ad_id = $ad_id = $click_key = $view_key = 0;
		foreach ($ermas as $erma_key => $erma) {
			if ($erma['GastatsAd']['ad_stat_type'] == 'view') {
				if ($erma['GastatsAd']['value'] > $max_views) {
					$max_views = $erma['GastatsAd']['value'];
					$delete_ids[] = $max_ad_id;
					$max_ad_id = $erma['GastatsAd']['id'];
					$view_key = $erma_key;
				} else {
					$delete_ids[] = $erma['GastatsAd']['id'];
				}
			} else if ($erma['GastatsAd']['ad_stat_type'] == 'click') {
				$click_key = $erma_key;
			}
		}
		$this->deleteAll(['GastatsAd.id' => $delete_ids], false);
		if (isset($ermas[$click_key]['GastatsAd']['ad_id']) && isset($ermas[$view_key]['GastatsAd']['ad_id']) && ($ermas[$click_key]['GastatsAd']['ad_id'] != $ermas[$view_key]['GastatsAd']['ad_id'])) {
			$ermas[$click_key]['GastatsAd']['ad_id'] = $ermas[$view_key]['GastatsAd']['ad_id'];
			$data = $ermas[$click_key];
			$this->create(false);
			$this->save($data);
		}
		
	}
	
	/*
	*   Query and store DFP ad data
	*/
	public function processDFPTotals($start_date=null, $end_date=null) {
		if (empty($start_date) || empty($end_date)) {
			return false;
		}
		$path = APP.'Plugin/Gastats/Lib/googleads-php-lib/src';
		set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
		require_once 'Google/Api/Ads/Dfp/Util/v201608/ReportDownloader.php';
		require_once 'Google/Api/Ads/Dfp/Util/v201608/StatementBuilder.php';
		require_once 'Google/Api/Ads/Common/Lib/ValidationException.php';
		require_once 'Google/Api/Ads/Common/Util/OAuth2Handler.php';

		try {
		  $user = new DfpUser(APP.'Config/dfp-auth.ini');
		  $reportService = $user->GetService('ReportService', 'v201608');
		  $networkService = $user->GetService('NetworkService', 'v201608');
		  $statementBuilder = new StatementBuilder();
		  
		  // Create report query.
		  $reportQuery = new ReportQuery();
		  //$reportQuery->dimensions = array('ADVERTISER_NAME', 'CREATIVE_SIZE', 'AD_UNIT_NAME', 'PLACEMENT_NAME', 'MONTH_AND_YEAR');
		  $reportQuery->dimensions = array('ADVERTISER_NAME', 'AD_UNIT_NAME', 'PLACEMENT_NAME');
		  $reportQuery->columns = array('TOTAL_INVENTORY_LEVEL_IMPRESSIONS', 'TOTAL_INVENTORY_LEVEL_CLICKS', 'TOTAL_INVENTORY_LEVEL_CTR');

		  // Set the filter statement.
		  $reportQuery->statement = $statementBuilder->ToStatement();

		  // Set the ad unit view to hierarchical.
		  $reportQuery->adUnitView = 'TOP_LEVEL';
		  $reportQuery->dateRangeType = 'CUSTOM_DATE';
 		  $reportQuery->startDate = DateTimeUtils::ToDfpDateTime(new DateTime($start_date, new DateTimeZone('America/New_York')))->date;
 		  $reportQuery->endDate = DateTimeUtils::ToDfpDateTime(new DateTime($end_date, new DateTimeZone('America/New_York')))->date;

		  // Create report job.
		  $reportJob = new ReportJob();
		  $reportJob->reportQuery = $reportQuery;

		  // Run report job.
		  $reportJob = $reportService->runReportJob($reportJob);

		  // Create report downloader.
		  $reportDownloader = new ReportDownloader($reportService, $reportJob->id);

		  // Wait for the report to be ready.
		  $reportDownloader->waitForReportReady();

		  // Change to your file location.
		  $csv_path = APP.'tmp/dfp-'.$start_date.'_'.$end_date.'-historical';
		  $filePath = sprintf('%s.csv.gz', $csv_path);
		  //$reportDownloader->downloadReport('CSV_DUMP', $filePath);
		  $result = gzdecode($reportDownloader->downloadReport('CSV_DUMP'));
		  $result_array = explode("\n", $result);

		  $result_keys = array_flip(explode(",", $result_array[0]));
		  unset($result_array[0]);
		  foreach ($result_array as $result_row) {
		  	if (empty(trim($result_row))) {
		  		continue;
		  	}
		  	$row_array = explode(",", $result_row);
		  	$data = [
		  		'start_date' => $start_date,
		  		'end_date' =>$end_date,
		  		'ad_stat_type' => 'view',
		  		'location' => 'GEN',
		  		'corp_id' => $row_array[$result_keys['Dimension.ADVERTISER_ID']],
		  		'ad_id' => $row_array[$result_keys['Dimension.AD_UNIT_ID']],
		  		'ad_slot' => $row_array[$result_keys['Dimension.AD_UNIT_NAME']],
		  		'value' => $row_array[$result_keys['Column.TOTAL_INVENTORY_LEVEL_IMPRESSIONS']],
		  		];

			$this->create(false);
			$this->save($data);
			//Clicks
			$data['ad_stat_type'] = 'click';
			$data['value'] = $row_array[$result_keys['Column.TOTAL_INVENTORY_LEVEL_CLICKS']];
			$this->create(false);
			$this->save($data);
		  }

		  printf("done.\n");
		} catch (OAuth2Exception $e) {
		  //ExampleUtils::CheckForOAuth2Errors($e);
		} catch (ValidationException $e) {
		  //ExampleUtils::CheckForOAuth2Errors($e);
		} catch (Exception $e) {
		  printf("%s\n", $e->getMessage());
		}
	}

	/*
	*   Query and store DFP ad data
	*/
	public function processDFPReach($start_date=null, $end_date=null) {
		if (empty($start_date) || empty($end_date)) {
			return false;
		}
		$path = APP.'Plugin/Gastats/Lib/googleads-php-lib/src';
		set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
		require_once 'Google/Api/Ads/Dfp/Util/v201608/ReportDownloader.php';
		require_once 'Google/Api/Ads/Dfp/Util/v201608/StatementBuilder.php';
		require_once 'Google/Api/Ads/Common/Lib/ValidationException.php';
		require_once 'Google/Api/Ads/Common/Util/OAuth2Handler.php';

		try {
		  $user = new DfpUser(APP.'Config/dfp-auth.ini');
		  $reportService = $user->GetService('ReportService', 'v201608');
		  $networkService = $user->GetService('NetworkService', 'v201608');
		  $statementBuilder = new StatementBuilder();
		  
		  // Create report query.
		  $reportQuery = new ReportQuery();
		  //$reportQuery->dimensions = array('ADVERTISER_NAME', 'CREATIVE_SIZE', 'AD_UNIT_NAME', 'PLACEMENT_NAME', 'MONTH_AND_YEAR');
		  $reportQuery->dimensions = array('ADVERTISER_NAME', 'MONTH_AND_YEAR');
		  $reportQuery->columns = array('REACH');

		  // Set the filter statement.
		  $reportQuery->statement = $statementBuilder->ToStatement();

		  // Set the ad unit view to hierarchical.
		  $reportQuery->adUnitView = 'TOP_LEVEL';
		  $reportQuery->dateRangeType = 'CUSTOM_DATE';
 		  $reportQuery->startDate = DateTimeUtils::ToDfpDateTime(new DateTime($start_date, new DateTimeZone('America/New_York')))->date;
 		  $reportQuery->endDate = DateTimeUtils::ToDfpDateTime(new DateTime($end_date, new DateTimeZone('America/New_York')))->date;

		  // Create report job.
		  $reportJob = new ReportJob();
		  $reportJob->reportQuery = $reportQuery;

		  // Run report job.
		  $reportJob = $reportService->runReportJob($reportJob);

		  // Create report downloader.
		  $reportDownloader = new ReportDownloader($reportService, $reportJob->id);

		  // Wait for the report to be ready.
		  $reportDownloader->waitForReportReady();

		  // Change to your file location.
		  $csv_path = APP.'tmp/dfp-'.$start_date.'_'.$end_date.'-reach';
		  $filePath = sprintf('%s.csv.gz', $csv_path);
		  //$reportDownloader->downloadReport('CSV_DUMP', $filePath);
		  $result = gzdecode($reportDownloader->downloadReport('CSV_DUMP'));

		  $result_array = explode("\n", $result);

		  $result_keys = array_flip(explode(",", $result_array[0]));
		  unset($result_array[0]);
		  foreach ($result_array as $result_row) {
		  	if (empty(trim($result_row))) {
		  		continue;
		  	}
		  	$row_array = explode(",", $result_row);
		  	$data = [
		  		'start_date' => $start_date,
		  		'end_date' =>$end_date,
		  		'ad_stat_type' => 'unique-view',
		  		'location' => 'GEN',
		  		'corp_id' => $row_array[$result_keys['Dimension.ADVERTISER_ID']],
		  		'ad_id' => '1',//$row_array[$result_keys['Dimension.AD_UNIT_ID']],
		  		'ad_slot' => 'dfp-reach',//$row_array[$result_keys['Dimension.AD_UNIT_NAME']],
		  		'value' => $row_array[$result_keys['Column.REACH']],
		  		];

			$this->create(false);
			$this->save($data);
		  }

		  printf("done.\n");
		} catch (OAuth2Exception $e) {
		  //ExampleUtils::CheckForOAuth2Errors($e);
		} catch (ValidationException $e) {
		  //ExampleUtils::CheckForOAuth2Errors($e);
		} catch (Exception $e) {
		  printf("%s\n", $e->getMessage());
		}
	}
	
}
?>
