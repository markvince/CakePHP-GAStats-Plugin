<?php

class GastatsVideo extends GastatsAppModel {
	public $name = "GastatsVideo";
	public $useTable = "gastats_videos";
	public $stats_type = 'videos';
	
	
	/**
	*     metrics (totalEvents, avgEventValue)
	*	  dimensions (eventAction, eventLabel)
	*
	*/
	public function processGAStats($start_date=null, $end_date=null, $refresh=false) {
		AppLog::info('Gastats - Processing Video Stats');
		$GastatsRaw = ClassRegistry::init('Gastats.GastatsRaw');
		$GastatsRaw->page_path='';		
		if ($refresh) {
			$GastatsRaw->getGAData($this->stats_type,$start_date,$end_date,true);
			$this->purgeVideoStats($start_date, $end_date);
		}
		$stats = $GastatsRaw->getStats($this->stats_type,$start_date,$end_date);
		$video_corp_ids = $videos = array();
		foreach ($stats as $stat) {
			$stat = $stat['GastatsRaw'];
			$key_details = explode("|", $stat['key']);
			if ($key_details[3] == "totalEvents") {
				$bucket_views = $stat['value'];
				$path = $key_details[0];
				$bucket = $key_details[1];
				$value = $key_details[1];
				$page_tmp = explode("?", $key_details[2]); //help remove URL query strings
				$page = $page_tmp[0];
				$path_array = explode("/", $path);
				$track_type = $path_array[1];
				$view_type = $path_array[2];
				$corp = $path_array[3];
				$ad_id = $path_array[4];
				$ad_type = $path_array[5];
				$corp_id = $path_array[6];
				$slot = $path_array[7];
				//make sure bucket lables are ok
				$bucket_array = explode("-", $bucket);
				if (!isset($bucket_array[1]) && strpos($bucket_array[0], '+') !== false) {
					$bucket_array[0] = str_replace('+', '', $bucket_array[0]);
					$bucket_array[1] = intval($bucket_array[0]) + 25;
					$bucket = implode("-", $bucket_array);
				}

				//build videos array
				//$videos[$ad_id][$corp][$page][$bucket] = $bucket_views;
				if (!isset($validate[$ad_id][$corp][$page][$bucket][$bucket_views])) {
					$validate[$ad_id][$corp][$page][$bucket][$bucket_views] = 1;
					$videos[$ad_id][$page][$bucket] = (isset($videos[$ad_id][$page][$bucket]) ? $videos[$ad_id][$page][$bucket] + $bucket_views : $bucket_views);
					$video_corp_ids[$ad_id] = $corp_id;
				}
			}
		}

		//Sort Buckets
		foreach ($videos as $ad_id => $video) {
			foreach ($video as $page => $buckets) {
				$bucket_keys = array_keys($buckets);
				$ordered_keys = array();
				foreach ($bucket_keys as $key) {
					$key_array = explode("-",$key);
					$value = $key_array[0]+$key_array[1];
					$ordered_keys[$key] = $value;
				}
				asort($ordered_keys);
				foreach ($ordered_keys as $key => $value) {
					$ordered_keys[$key] = $videos[$ad_id][$page][$key];
				}
				$videos[$ad_id][$page] = $ordered_keys;
			}
		}
		//Calc Avg View times
		$video_details = array();
		foreach ($videos as $ad_id => $video) {
			foreach ($video as $page => $buckets) {
				$total_plays = 0;
				$max_view_length = 0;
				$prev_viewers = 0;
				$diff = 0;
				$prev_bmax = 0;
				$group_views_total = 0;
				foreach ($buckets as $bucket_label => $viewers) {
					//right now assuming buckets are in ASC order
					$bucket = explode("-", $bucket_label);
					$bmin = $bucket[0];
					$bmax = $bucket[1];
					if ($bmax > $max_view_length) {
						$max_view_length = $bmax;
					}
					if ($bmin == 0) {
						//first bucket
						$total_plays = $viewers;
						$diff = $viewers;
						$prev_bmax = 0;
						$group_views_total = ($viewers * $bmax);
					} else {
						$diff = $prev_viewers - $viewers;
						$group_views_total += ($diff * $prev_bmax);
						if ($total_plays == 0) {
							$total_plays = $viewers;
						}
					}
					$prev_viewers = $viewers;
					$prev_bmax = $bmax;
				}
				//Perform one last time for the last bucket
				$max_view_length = ($prev_bmax > $max_view_length ? $prev_bmax : $max_view_length);
				$group_views_total += ($prev_viewers * $prev_bmax);
				//Calculate avg
				$avg_view_length = $group_views_total/$total_plays;
				$avg_view_length = (($avg_view_length > $max_view_length) ? $max_view_length : $avg_view_length);
				$videos[$ad_id][$page] = compact('total_plays', 'max_view_length', 'avg_view_length','page');
			}
		}

		foreach ($videos as $video_id => $video) {
			foreach ($video as $page => $details) {
				$data = array(
					'start_date' => $start_date,
					'end_date' => $end_date,
					'corp_id' => $video_corp_ids[$video_id],
					'video_id' => $video_id,
					'details' => json_encode($details),
					);
				$this->create(false);
				$this->save($data);
			}
		}
		AppLog::info('Gastats - Processing Video Stats Complete');
	}
	
	/**
	*
	*/
	function purgeVideoStats($start_date=null, $end_date=null) {
		$conditions = compact('start_date', 'end_date');
		$this->deleteAll($conditions);
	}
	

	function getVideos($corp_id=0, $start_date=null, $end_date=null) {
		if ($corp_id == 0) {
			$conditions = ['OR' => $this->calculateDateRanges($start_date, $end_date)];
		} else {
			$conditions = [
				'corp_id' => $corp_id,
				'OR' => $this->calculateDateRanges($start_date, $end_date),
				];
		}
		$video_array = $this->find('all', compact('conditions'));

		//display details to be decided, return array for now
		return $video_array;
	}	
}
?>
