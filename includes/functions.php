<?php

function prevent_html_decode($str)
{
	return preg_replace("/&(\S+);/", "&amp;\\1;", $str);
}

// TODO: make compatible with @media, just in case
function css_to_array($css)
{
	$arr = array();
	$css = preg_replace("!/\*(?:\s|\S)*?\*/!", "", $css);
	$css = preg_split("/^([^{]+){|}([^{]+){|}[^{]*$/D", $css, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
	$matches = floor(count($css)/2);
	for($i = 0; $i < $matches; $i++)
	{
		$select = trim($css[$i*2]);
		$props = explode(";", $css[$i*2+1]);
		foreach($props as $prop)
		{
			$prop = trim($prop);
			if($prop != "")
			{
				list($prop, $val) = explode(":", $prop, 2);
				$arr[$select][trim($prop)] = trim($val);
			}
		}
	}
	return $arr;
}

function get_css_files($tempName)
{
	global $builder;
	if(!in_array($tempName, $builder->themes))
		$tempName = $builder->get_setting('default_theme');
	$result = array();
	$dir = dir($builder->get_setting('server_path') ."themes/". $tempName ."/css");
	if(is_dir($dir->path))
	{
		while($file = $dir->read())
			if(substr($file, -4) == ".css")
				$result[] = substr($file, 0, -4);
	}
	return $result;
}

function cpanel_process_textcontent($object, $i, $itable=true, $canedit=true)
{
	$i = (integer) $i;
	if($itable)
		$html .= "<table id='content". $i ."' class='content'>\n";
	$html .= "	<tr>\n";
	$html .= "		<td class='head alt3' colspan='2'>". get_class($object) ."</td>\n";
	$html .= "	<input type='hidden' name='content[". $i ."][content_class]' value='". get_class($object) ."'/>\n";
	$html .= "	</tr>\n";
	$props = $object->get_properties();
	$alt = 0;
	foreach($props as $prop=>$attr)
	{
		$alt &= 1;
		$html .= "	<tr>\n";
		$html .= "		<td class='property alt". ($alt+1) ."'>". $prop ."</td>\n";
		if($attr['type'] == "paragraph")
		{
			if(!$canedit)
				$html .= "		<td class='alt". ($alt) ."'>". $object->$prop ."</td>\n";
			else
				$html .= "		<td class='alt". ($alt) ."'><textarea name='content[". $i ."][". $prop ."]'>". prevent_html_decode($object->$prop) ."</textarea></td>\n";
		}
		else
		{
			if(!$canedit)
				$html .= "		<td class='alt". ($alt) ."'>". $object->$prop ."</td>\n";
			else
				$html .= "		<td class='alt". ($alt) ."'><input name='content[". $i ."][". $prop ."]' value='". $object->$prop ."'/></td>\n";
		}
		$html .= "	</tr>\n";
		$alt = !$alt;
	}
	if($canedit)
	{
		$html .= "	<tr>\n";
		$html .= "		<td class='foot alt3' colspan='2'><a href=\"javascript:remove_content('content". $i ."')\">Remove</a></td>\n";
		$html .= "	</tr>\n";
	}
	if($itable)
		$html .= "</table>\n";
	return $html;
}

function cpanel_list_css_files($csv, $theme)
{
	$css = explode(",", $csv);
	$all_css = get_css_files($theme);
	$options = "<input id='input_css' readonly type='text' name='css' title='CSS files will be applied in this order (last=highest precedence).' value='". $csv ."'/><br/>";
	foreach($all_css as $i=>$f)
	{
		if($i > 0)
			$options .= ", ";
		$options .= "<a id='css_". $f ."' href=\"javascript:toggle_css_file('". $f ."')\">". (in_array($f, $css)?"-":"+") . $f ."</a>";
	}
	return $options;
}

function syntax_highlight($text)
{
	$text = preg_replace("!(&lt;\?.*?\?&gt;)!", "<span style='color:gray;'>$1</span>", $text);
	$text = preg_replace("!(&lt;/?xsl\:.*?&gt;)!", "<span style='background-color:lightgray;'>$1</span>", $text);
	return $text;
}

// delete?
function diff($old, $new)
{
        foreach($old as  $oindex => $ovalue){
                $nkeys = array_keys($new, $ovalue);
                foreach($nkeys as $nindex){
                        $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                        if($matrix[$oindex][$nindex] > $maxlen){
                                $maxlen = $matrix[$oindex][$nindex];
                                $omax = $oindex + 1 - $maxlen;
                                $nmax = $nindex + 1 - $maxlen;
                        }
                }       
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
                diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
                array_slice($new, $nmax, $maxlen),
                diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))); 
}

function string_diff($old, $new)
{
        $diff = diff(explode(' ', $old), explode(' ', $new));
        foreach($diff as $k){
                if(is_array($k))
                        $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                                (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
                else $ret .= $k . ' ';
        }
        return $ret; 
}