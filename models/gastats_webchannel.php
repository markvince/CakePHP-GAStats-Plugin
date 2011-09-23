<?php

Class GastatsWebchannel extends GastatsAppModel {
	public $name = "GastatsWebchannel";
	public $useTable = "gastats_webchannels";
	public $stat_type = 'webchannels';
	
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		$stat_type = $this->stat_type;
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
		$this->loadGA(false);//load datasource but don't log into google
		if (isset($this->GoogleAnalytics->config['channels'])) {
			$config = explode('/',$this->GoogleAnalytics->config['channels']);
			$model = $config[0];
			$field = $config[1];
			//Load model and get list of channel urls
			$CM = ClassRegistry::init($model);
			$channels = $CM->find('list',array('conditions'=>array($field.' <> ""')
				,'fields' => $field));
			if ($refresh) {
				$GastatsRaw->purgeStats($stat_type,$start_date,$end_date); //remove data collected from GA
				$this->purgeWebchannelStats($start_date,$end_date);			//remove aggregate data
				foreach ($channels as $channel) {
					$GastatsRaw->page_path = $channel;
					$GastatsRaw->getGAData($stat_type,$start_date,$end_date,true);
				}
			}
			
			//Foreach channel process it's raw data and store it
			$conditions = compact('stat_type','start_date','end_date');
			$gadata = $GastatsRaw->find('all',compact('conditions'));
			foreach ($gadata as $stat) {
				$stat = $stat['GastatsRaw'];
				$data = array();
				$channel_metric = explode("|",$stat['key']); //channel|metric
				$corp_id = array_keys($channels, $channel_metric[0]);
				$corp_id = (is_array($corp_id) && count($corp_id) > 0 ? $corp_id[0] : 0);
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
	}
	
	function purgeWebchannelStats($start_date,$end_date) {
		$this->deleteAll(compact('start_date', 'end_date'));
	}
}

?>
