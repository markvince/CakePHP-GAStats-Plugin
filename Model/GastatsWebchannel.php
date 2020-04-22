<?php

Class GastatsWebchannel extends GastatsAppModel {
	public $name = "GastatsWebchannel";
	public $useTable = "gastats_webchannels";
	public $stat_type = 'webchannels';
	public $channel_prefix = ['expo', 'partners'];
	
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		AppLog::info('Gastats - Processing Webchannel Stats');
		$stat_type = $this->stat_type;
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
		$GastatsRaw->page_path='';
		$this->loadGA(false);//load datasource but don't log into google
		if (isset($this->GoogleAnalytics->config['channels'])) {
			$config = explode('/',$this->GoogleAnalytics->config['channels']);
			$model = $config[0];
			$fields = $config[1];
			$active = (isset($config[2]) ? explode(':',$config[2]) : null);
			if (!is_null($active)) {
				$is_active = $active[0].' = '.$active[1];
			}
			
			//Load model and get list of channel urls
			$CM = ClassRegistry::init($model);
			$conditions=array($fields.' <> ""');
			if (isset($is_active)) {
					$conditions[] = $is_active;
			}
			$channels = $CM->find('list',compact('conditions','fields'));
			$channels_by_path = array_flip($channels);
			if ($refresh) {
				$GastatsRaw->purgeStats($stat_type,$start_date,$end_date); //remove data collected from GA
				$this->purgeWebchannelStats($start_date,$end_date);			//remove aggregate data
				foreach ($channels as $corp_id => $channel) {
					foreach ($this->channel_prefix as $prefix) {
						$GastatsRaw->page_path = $prefix.'/'.$channel;
						$channels[$prefix][$corp_id] = $GastatsRaw->page_path;
						$channels_by_path[$GastatsRaw->page_path] = $corp_id;
						$GastatsRaw->getGAData($stat_type,$start_date,$end_date,true);
					}
				}
			}
			
			//Foreach channel process it's raw data and store it
			$conditions = compact('stat_type','start_date','end_date');
			$gadata = $GastatsRaw->find('all',compact('conditions'));
			foreach ($gadata as $stat) {
				$stat = $stat['GastatsRaw'];
				$data = array();
				$channel_metric = explode("|",$stat['key']); //channel|metric
				$corp_id = $channels_by_path[$channel_metric[0]];
				$corp_id = empty($corp_id) ? 0 : $corp_id;
				if ($corp_id > 0) {
					$data = array(
					'start_date' => $start_date,
					'end_date' => $end_date,
					'corp_id' => $corp_id,
					'channel' => $channel_metric[0],
					'metric' => $channel_metric[1],
					'value'	=> $stat['value'],
					);	
				} else {
					echo "Problem finding corp_id for channel: ".$channel_metric[0];
				}
				
				if (count($data) > 0) {
					$this->create();
					$this->save($data);
				}
				
			}
		}
		AppLog::info('Gastats - Processing Webchannel Stats Complete');
	}
	
	function purgeWebchannelStats($start_date,$end_date) {
		$this->deleteAll(compact('start_date', 'end_date'));
	}
	
	function getWebchannels($corp_id, $start_date, $end_date) {
		if ($corp_id == 0) {
			$conditions = compact('start_date','end_date');
		} else {
			$conditions = compact('corp_id','start_date','end_date');
		}
		$webchannels = array();
		$order = 'channel ASC, metric ASC';
		$channels_array = $this->find('all',compact('conditions','order'));
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
		
		return $webchannels;
	}
}

?>
