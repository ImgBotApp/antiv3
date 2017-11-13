<?php
$countries = array('AT', 'AUT', 'CAN', 'BR', 'GR', 'HU', 'RU', 'SK', 'SP', 'ES',
'A', 'SWI', 'CH', 'UK', 'USA', 'NL', 'BEL', 'BE', 'ITA', 'FR', 'LUX', 'GER', 'SWE',
'NOR', 'DE', 'SLO', 'SI', 'SL', 'POR', 'POL', 'PL', 'CZ', 'JP', 'JAP', 'CA');

$flags = array('at', 'at', 'ca', 'br', 'gr', 'hu', 'ru', 'sk', 'es', 'es', 'at',
'ch', 'ch', 'gb', 'us', 'nl', 'be', 'be', 'it', 'fr', 'lu', 'de', 'se', 'no', 'de',
'si', 'si', 'si', 'pt', 'pl', 'pl', 'cz', 'jp', 'jp','ca');

$names = array('Austria', 'Austria', 'Canada', 'Brazil', 'Greece', 'Hungary', 'Russia',
'Slovakia', 'Spain', 'Spain', 'Austria', 'Switzerland', 'Switzerland', 'United Kingdom',
'U.S.A', 'Netherlands', 'Belgium', 'Belgium', 'Italy', 'France', 'Luxembourg', 'Germany',
'Sweden', 'Norway', 'Germany', 'Slovenia', 'Slovenia', 'Slovenia', 'Portugal', 'Poland',
'Poland', 'Czech Republic', 'Japan', 'Japan', 'Canada');

$FB = '[FB:';
$TIX = '[TIX:';
$LINK = '[LINK:';
$LINK_SEPARATOR = '|';

function get_country ($atts, $content = null) {
	return countryToflag (do_shortcode ($content));
}

/**
 * [extract country name and replace by a flag image]
 * @param  [string] $string [string to search]
 * @return [string]         [string with flag image, or input string if no country found]
 */
function countryToflag ($string) {
	global $names, $flags, $countries;
	for($o = 0; $o < count($countries); $o++){
		$pattern = $countries[$o].':';
		if (strpos($string, $pattern) !== false) {
			$string = str_replace($pattern, '<img class="flag" alt="' . $names[$o] . '" title="' . $names[$o] . '"
			src="' . get_bloginfo('stylesheet_directory') . '/images/flags-mini/' . $flags[$o] . '.png" />', $string);
			break;
		}

	}
	return $string;
}

/**
 * [extract relevant links from event description]
 * @param  [array] $atts    	[attributes]
 * @param  [string] $content 	[event description]
 * @return [string]						[the parsed description, with other shortcodes applied]
 */
function extract_links ($atts, $content = null) {
	$showDescription = $atts['showdescription'] !== 'false';

	global $FB, $TIX, $LINK, $LINK_SEPARATOR;
	list ($fbLink, $url) = findLink($content, $FB);
	if ($fbLink && $url) {
		$theFBLink = addLink($url, 'RSVP');
		if($showDescription) {
			$content = str_replace ($fbLink, $theFBLink, $content);
		}
	}

	list ($tixLink, $tixUrl) = findLink($content, $TIX);
	if ($tixLink && $tixUrl) {
		$theTixLink = addLink($tixUrl, 'Tickets');
		if ($showDescription) {
			$content = str_replace ($tixLink, $theTixLink, $content);
		}
	}

	//TODO: only 1 [LINK: .. ] is extracted, support multiple links?
	list ($linkLink, $linkUrl) = findLink($content, $LINK);
	if ($linkLink && $linkUrl) {
		list ($linkTitle, $url) = splitBy ($linkUrl, $LINK_SEPARATOR);
		if ($linkTitle && $url) {
			$theLink =  addLink($url, $linkTitle);
		} else {
			$theLink = addLink($linkUrl, 'Link');
		}
		if ($showDescription) {
			$content = str_replace ($linkLink, $theLink, $content);
		}
	}
	if ($showDescription) {
		return do_shortcode ($content);
	} else {
		return '<span class="event-meta">' . $theFBLink . $theTixLink . $theLink . '</span>';
	}
}

/**
 * [find a link prefixed with a string]
 * @param  [string] $string  	[string to search]
 * @param  [string] $pattern 	[prefix]
 * @return [array]          	[entire string, url]
 */
function findLink ($string, $pattern) {
	$found = strpos ($string, $pattern);
	if ($found !== false) {
		$end = strpos ($string, ']', $found);
		$foundString = substr ($string, $found, ($end - $found) + 1);
		list ($first, $second) = splitBy($foundString, ':');
		if ($second) {
				return array ($foundString, substr($second, 0, -1));
		}
		return array();
	}
	return array();
}

/**
 * [split a string by pattern]
 * @param  [type] $string [string to split]
 * @param  [type] $split  [pattern]
 * @return [array]        [first part, second part]
 */
function splitBy ($string, $split) {
	$parts = explode ($split, $string);
	$first = trim ($parts[0]);
	$parts[0] = '';
	$second = substr(trim (implode(':', $parts)), 1);
	return array ($first, $second);
}

/**
 * [create a link]
 * @param [string] $url        		[the url]
 * @param [string] $title       	[link title]
 * @param [boolean] $blankTarget	[open in a _blank window]
 */
function addLink ($url, $title, $blankTarget = true) {
	$target = $blankTarget ? '_blank' : '';
	return '<a href="' . $url . '" target="' . $target . '">' . $title . '</a>';
}
