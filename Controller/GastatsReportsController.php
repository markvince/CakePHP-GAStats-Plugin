<?php
class GastatsReportsController extends GastatsAppController {
	var $name = "GastatsReports";
	var $uses = array('Gastats.GastatsAd','Gastats.GastatsCountry',
		'Gastats.GastatsWebchannel','Gastats.GastatsWebstat','Gastats.GastatsRaw',
		);
	//var $layout = 'blank';
	public $metrics = array(
		'avgTimeOnPage' => array('display'=>true,'header' => 'Avg Time On Page (h:m:s)','uom'=>'time'),
			'exists' => array('display'=>false,'header' => 'Exits',),
			'pageviews' => array('display'=>true,'header' => 'Page Views',),
			'timeOnpage' => array('display'=>false,'header' => 'Time On Page',),
			'uniquePageviews' => array('display'=>true,'header' => 'Unique Page Views',),
			'avgTimeOnSite' => array('display'=>true,'header' => 'Avg Time On Site (h:m:s)','uom'=>'time'),
			'timeOnSite' => array('display'=>false,'header' => 'Time On Site (h:m:s)','uom'=>'time'),
			'avgSessionDuration' => array('display'=>true,'header' => 'Avg Time On Site (h:m:s)','uom'=>'time'),
			'sessionDuration' => array('display'=>false,'header' => 'Time On Site (h:m:s)','uom'=>'time'),
			'visitors' => array('display'=>true,'header' => 'Visitors',),
			'visits' => array('display'=>true,'header' => 'Visits',),
			);
	
	public function index() {
		die();
	}
	
	/**
	* Query and display ad records by corp_id and date range.
	* @param corp_id
	* @param start_date
	* @param end_date
	*/
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
	
	/**
	* Query and display webchannel records by corp_id and date range.
	* @param corp_id
	* @param start_date
	* @param end_date
	*/
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
	
	/**
	* Query and display site-wide stats by date range.
	* @param start_date
	* @param end_date
	*/
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
	
	/**
	* Query and display country visit stats date range.
	* @param start_date
	* @param end_date
	*/
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
	
	/**
	* Query Raw data by date and/or path, will display path and page views
	* @param start_date
	* @param end_date
	* path (optional) - will query with 'like' match
	* wildcard (optional) - if true then will add wildcard % to end of path
	*/
	public function content($start_date=null, $end_date=null, $limit=null) {
		if (isset($this->params['url']['path'])) {
			$path = urldecode($this->params['url']['path']);
		} else {
			$path = '';
		}	
		//Check for wildcard
		if (isset($this->params['url']['wildcard']) && $this->params['url']['wildcard'] == true) {
			$wildcard = true;
		}
		
		if (!empty($path)) {
			$conditions['GastatsRaw.key LIKE'] = "$path$wildcard";
		}
		$content_array = $this->GastatsRaw->getContent($start_date, $end_date, $limit, $path, $wildcard);
		$contents = array();
		foreach ($content_array as $item) {
			$contents[$item['key']] = $item['value'];
		}
		
		$this->set(compact('start_date','end_date','contents'));
	}
	
	/**
	* @param content_type_slug - content_type-slug //articles-my-article-xxyy
	* 
	*/
	public function contentBySlug($content_type_slug, $start_date=null, $end_date=null) {
		$content_type_slug = explode("-",$content_type_slug);
		$content_type = array_shift($content_type_slug);
		$slug = (isset($content_type_slug[0]) ? implode("-",$content_type_slug) : '');
		$wildcard=false;
		if (isset($this->params['url']['wildcard']) && $this->params['url']['wildcard'] == true) {
			$wildcard = true;
		}
		if ($wildcard == false && empty($slug)) {
			$wildcard = true;
		}
		$contents_array = $this->GastatsRaw->getContent($start_date, $end_date, null, "/$content_type/$slug", $wildcard);
		
		foreach ($contents_array as $item) {
			$item = $item['GastatsRaw'];
			$path = explode("?",$item['key']);
			$path = $path[0];
			if (isset($contents[$path])) {
				$contents[$path] += $item['value'];
			} else {
				$contents[$path] = $item['value'];	
			}
			
		}
		
		$this->set(compact('start_date','end_date','contents'));
		$this->render('content');
	}
	
	/**
	* @param content_id - id at end of slug
	* 
	*/
	public function contentById($content_id, $start_date=null, $end_date=null) {
		$content_id = explode("-",$content_id);
		$content_type = '';
		if (count($content_id) == 2) {
			$content_type = array_shift($content_id);
			$content_type = "/$content_type/";
		}
		$content_id = array_shift($content_id);
		$slug = "$content_type%-$content_id";
		$contents_array = $this->GastatsRaw->getContent($start_date, $end_date, null, "$slug", false);
				
		foreach ($contents_array as $item) {
			$item = $item['GastatsRaw'];
			$path = explode("?",$item['key']);
			$path = $path[0];
			if (isset($contents[$path])) {
				$contents[$path] += $item['value'];
			} else {
				$contents[$path] = $item['value'];	
			}
			
		}
		
		$this->set(compact('start_date','end_date','contents'));
		$this->render('content');
		
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
