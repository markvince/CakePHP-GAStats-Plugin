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
		$agstats = array();
		foreach ($stats as $stat) {
			$stat = $stat['GastatsRaw'];
			if (stripos($stat['key'], 'track_banner') === false) {
				continue;
			}
			$url = explode('?',$stat['key']);
			$urla = explode('/',$url[0]);
			if ($urla[0] == "") {
				array_shift($urla);
			}
			if ($urla[0] == "track_banner_click") {
				if(isset($agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]])) {
						$agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] += $stat['value'];
				} else {
					$agstats['click'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] = $stat['value'];
				}
			} else if ($urla[0] == "track_banner_view") {
				if(isset($agstats['view'][$urla[1]][$urla[5]][$urla[3]][$urla[6]])) {
						$agstats['view'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] += $stat['value'];
				} else {
					$agstats['view'][$urla[1]][$urla[5]][$urla[3]][$urla[6]] = $stat['value'];
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
	public function getAds($corp_id=0, $start_date=null, $end_date=null) {
		if ($corp_id == 0) {
			$conditions = compact('start_date','end_date');
		} else {
			$conditions = compact('corp_id','start_date','end_date');
		}
		
		$ads_array = $this->find('all',compact('conditions'));
		$ads = array();
		$corps=array();
		//prep data for display
		foreach ($ads_array as $ad) {
			$ad = $ad['GastatsAd'];
			$ads['unique'][$ad['ad_id']][$ad['location']][$ad['ad_slot']][$ad['ad_stat_type']] = $ad['value'];
			$corps[$ad['ad_id']] = $ad['corp_id'];
			//breakdown
			//Total by stat type only (click/view)
			if (isset($ads['group'][$ad['ad_id']][$ad['ad_stat_type']])) {
				$ads['group'][$ad['ad_id']][$ad['ad_stat_type']]['total'] += $ad['value'];
			} else {
				$ads['group'][$ad['ad_id']][$ad['ad_stat_type']]['total'] = $ad['value'];
				$ads['group'][$ad['ad_id']]['ad_stat_types'][$ad['ad_stat_type']] = 1;
			}
			//Total by location and ad stat type
			if (isset($ads['group'][$ad['ad_id']][$ad['ad_stat_type']][$ad['location']]['total'])) {
				$ads['group'][$ad['ad_id']][$ad['ad_stat_type']][$ad['location']]['total'] += $ad['value'];
			} else {
				$ads['group'][$ad['ad_id']][$ad['ad_stat_type']][$ad['location']]['total'] = $ad['value'];
				$ads['group'][$ad['ad_id']]['ad_locations'][$ad['location']] = 1;
			}
			
		}
		return compact('corp_id','start_date','end_date','ads','corps');
	}

	public function getMaxViewsBySlot($ad_slot='', $start_date=null, $end_date=null) {
		$conditions = compact('ad_slot', 'start_date', 'stop_date');
		$conditions['ad_stat_type'] = 'view';
		$conditions['location'] = 'GEN';
		$fields = array('MAX(value) views');
		$result = $this->find('first', compact('conditions', 'fields'));
		return (isset($result['GastatsAd']['views']) ? $result['GastatsAd']['views'] : '');
	}
	
	
}
?>
