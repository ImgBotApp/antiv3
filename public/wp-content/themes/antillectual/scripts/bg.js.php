<?php
header("Content-type: text/javascript");
?>
files = new Array();
<?
$dir = "../images/bg/";
$handle = opendir($dir);
$files = array();
	while (false!==($file = readdir($handle))) {
		if ($file != "." && $file != ".." && is_file($dir.$file)) {
		$files[] = $file;
?>
files[files.length] = "<?=$dir.$file?>";
<?
	}
}
$numfiles = count($files);
$theFile = $files[rand(0,$numfiles-1)];
?>
function setBG(){
	rand = <?=rand(0,$numfiles-1)?>;
	bgURL = "/wp-content/themes/antillectual/js/"+files[rand]+"";
	$('#bg .bg-image').attr('src', bgURL);
}
jQuery(function($){$(document).ready(function(){
	setBG();
})});
