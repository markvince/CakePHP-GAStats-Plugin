<?php
/**
* This is the blank layout - just renders the view content, 
* DOES NOT process the extra "end of page" JS
*/
$this->Output->ga_scriptloaded=true;
if (isset($note) && !empty($note)) {
	echo '<div class="notediv"><div class="note">'.implode('</div><div class="note">',asArray($note)).'</div></div>';
}
if (isset($errors) && !empty($errors)) {
	echo '<div class="errdiv"><div class="err">'.implode('</div><div class="err">',asArray($errors)).'</div></div>';
}
echo $content_for_layout;
// set to debug=0, so no extra output shows up
Configure::write('debug',0);
?>
