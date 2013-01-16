<?php

class GastatsAppModel extends AppModel {
	var $useDbConfig = "gastats_plugin";
	public $GoogleAnalytics = null;
	public $source = 'gastats';
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
	
	
	public function loadGA($login=true) {
		App::import('Core','ConnectionManager');
		$this->GoogleAnalytics = ConnectionManager::getDataSource($this->source);
		if ($login) {
			$this->GoogleAnalytics->login();	
		}
	}
	
	function _secondsDisplay($sec) {
		$hour = intval($sec/3600); //hours = 3600 per hour
		$min = intval(($sec/60)%60);	   //minutes = 60 sec per minute, then take remainder not used up by the hours 
		$sec = intval($sec%60);
		$hour = str_pad($hour,2,"0",STR_PAD_LEFT);
		$min = str_pad($min,2,"0",STR_PAD_LEFT);
		$sec = str_pad($sec,2,"0",STR_PAD_LEFT);
		return "$hour:$min:$sec";
	}
	
	public function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
    if (!function_exists('xml_parser_create')) {
        return array ();
    }
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array (); 
    foreach ($xml_values as $data)
    {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        { 
            $parent[$level -1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                { 
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    ); 
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    ); 
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        { 
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}
	
	
}

?>