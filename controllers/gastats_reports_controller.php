<?php
class GastatsReportsController extends GastatsAppController {
	var $uses = array('Gastats.GastatsAd','Gastats.GastatsCountry',
		'Gastats.GastatsWebchannel','Gastats.GastatsWebstat','Gastats.GastatsRaw',
		);
	public $metrics = array(
		'avgTimeOnPage' => array('display'=>true,'header' => 'Avg Time On Page (h:m:s)','uom'=>'time'),
			'exists' => array('display'=>false,'header' => 'Exits',),
			'pageviews' => array('display'=>true,'header' => 'Page Views',),
			'timeOnpage' => array('display'=>false,'header' => 'Time On Page',),
			'uniquePageviews' => array('display'=>true,'header' => 'Unique Page Views',),
			'avgTimeOnSite' => array('display'=>true,'header' => 'Avg Time On Site (h:m:s)','uom'=>'time'),
			'timeOnSite' => array('display'=>false,'header' => 'Time On Site (h:m:s)','uom'=>'time'),
			'visitors' => array('display'=>true,'header' => 'Visitors',),
			'visits' => array('display'=>true,'header' => 'Visits',),
			);
	
	public function index() {
		
	}
	
	public function ads($corp_id=0,$start_date=null,$end_date=null) {
		if ($corp_id == 0) {
			$conditions = compact('start_date','end_date');
		} else {
			$conditions = compact('corp_id','start_date','end_date');
		}
		
		$ads_array = $this->GastatsAd->find('all',compact('conditions'));
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
		$this->set(compact('corp_id','start_date','end_date','ads','corps'));
	}
	
	public function webchannels ($corp_id=0,$start_date=null,$end_date=null) {		
		if ($corp_id == 0) {
			$conditions = compact('start_date','end_date');
		} else {
			$conditions = compact('corp_id','start_date','end_date');
		}
		$order = 'channel ASC, metric ASC';
		$channels_array = $this->GastatsWebchannel->find('all',compact('conditions','order'));
		foreach ($channels_array as $webchannel) {
			$webchannel = $webchannel['GastatsWebchannel'];
			if (isset($this->metrics[$webchannel['metric']]) && $this->metrics[$webchannel['metric']]['display'] == true) {
				$webchannels[$webchannel['corp_id']]['channel'] = $webchannel['channel'];
				if (isset($this->metrics[$webchannel['metric']]['uom'])) {
					if (in_array($this->metrics[$webchannel['metric']]['uom'],array('time'))) {
						//GA defaults to seconds, convert to hms
						$webchannels[$webchannel['corp_id']]['metrics'][$this->metrics[$webchannel['metric']]['header']] = $this->_secondsDisplay($webchannel['value'],$this->metrics[$webchannel['metric']]['uom']);
					}
				} else {
						$webchannels[$webchannel['corp_id']]['metrics'][$this->metrics[$webchannel['metric']]['header']] = $webchannel['value'];		
				}	
			}
		}
		
		$this->set(compact('corp_id','start_date','end_date','webchannels'));
		
	}
	
	public function webstats($start_date=null,$end_date=null) {
		$conditions = compact('start_date','end_date');
		$order = 'metric ASC';
		$stats_array = $this->GastatsWebstat->find('all',compact('conditions','order'));
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
		
		$this->set(compact('start_date','end_date','stats'));
	}
	
	public function countries($start_date=null,$end_date=null, $limit=null) {
		$conditions = compact('start_date','end_date');
		$conditions[] = 'country <> "(not set)"'; //GA result with no set country name
		$order = "visits DESC";
		$country_array = $this->GastatsCountry->find('all',compact('conditions','order','limit'));
		foreach ($country_array as $country) {
			$country = $country['GastatsCountry'];
			$countries[$country['country']] = $country['visits'];
		}
		$this->set(compact('start_date','end_date','countries'));
	}
	
	// -------------------
	
	function _secondsDisplay($sec) {
		$hour = intval($sec/3600); //hours = 3600 per hour
		$min = intval(($sec/60)%60);	   //minutes = 60 sec per minute, then take remainder not used up by the hours 
		$sec = intval($sec%60);
		$hour = str_pad($hour,2,"0",STR_PAD_LEFT);
		$min = str_pad($min,2,"0",STR_PAD_LEFT);
		$sec = str_pad($sec,2,"0",STR_PAD_LEFT);
		return "$hour:$min:$sec";
	}
	
}
?>
