<?php

Class GastatsWebchannel extends GastatsAppModel {
	public $name = "GastatsWebchannel";
	public $useTable = false;//"gastats_webchannels";
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
				$GastatsRaw->purgeStats($stat_type,$start_date,$end_date);
				$this->purgeWebchannelStats($start_date,$end_date);
				foreach ($channels as $channel) {
					$GastatsRaw->page_path = $channel;
					$GastatsRaw->getGAData($stat_type,$start_date,$end_date,true);
				}
			}
			
			//Foreach channel process it's raw data and store it
			$conditions = compact('stat_type','start_date','end_date');
			$gadata = $GastatsRaw->find('all',compact('conditions'));
			debug($gadata);			
		}			
	}
	
	function purgeWebchannelStats($start_date,$end_date) {
		//$this->deleteAll(compact('start_date', 'end_date'));
	}
}

?>
