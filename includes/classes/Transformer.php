<?

/**
 */
class Transformer
{
	/**
	 * @var array[] Contains the parsed data from all loaded stylesheets.
	 */
	protected $stylesheets;
	
	/**
	 */
	public function __construct()
	{
		$this->stylesheets = array();
	}
	
	/**
	 * Loads the given XSL code for future transformations.
	 * 
	 * Parses the given code for all XSL stylesheets, templates, tags, etc. Stores all the parsed data in a class variable so that it can be easily referenced for XSLT.
	 * 
	 * @param string $content The XSL code to load.
	 */
	public function set_stylesheet($content="", $file="")
	{
		$this->stylesheets = array('raw'=>$content, 'file'=>$file);
		/*$matches = array();
		preg_match("%<(\\w+):stylesheet\\b[^>]+?\\bxmlns:\\1=[^>]+>(.*?)</\\1:stylesheet[^>]*>%is", $content, $matches);
		$this->stylesheets[0] = array(
			'namespace' => $matches[1],
			'content' => $matches[2],
			'templates' => array(),
			'file' => $file,
		);
		preg_match_all("%(?:(?<=>)\\s+)?<(?P<close>/?)". $this->stylesheets[0]['namespace'] .":(?P<type>[-a-zA-Z]+)(?P<attrs>(?:\\s+[-a-zA-Z]+=([\"'])(?:(?!\\4).)*\\4)*)\\s*(?P<empty>/?)>(?:\\s+(?=<))?%i", $this->stylesheets[0]['content'], $matches, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
		for($i = 0; $i < count($matches); $i++)
		{
			unset($matches[$i][1]);
			unset($matches[$i][2]);
			unset($matches[$i][3]);
			unset($matches[$i][4]);
			unset($matches[$i][5]);
			$attrs = array();
			preg_match_all("%\\s+([-a-zA-Z]+)=([\"'])((?:(?!\\2).)*)\\2%i", $matches[$i]['attrs'][0], $attrs, PREG_SET_ORDER);
			$matches[$i]['parsed_attrs'] = array();
			foreach($attrs as $attr)
				$matches[$i]['parsed_attrs'][$attr[1]] = $attr[3];
			unset($matches[$i]['attrs']);
			unset($attrs);
		}
		for($i = 0; $i < count($matches); $i++)
		{
			if($matches[$i]['type'][0] == "template" && !$matches[$i]['close'][0] && !$matches[$i]['empty'][0])
			{
				$nest_level = 1;
				for($k = $i+1; $k < count($matches); $k++)
				{
					if($matches[$k]['type'][0] == "template")
						$nest_level += ($matches[$k]['close'][0] ? -1 : 1);
					if($nest_level == 0)
					{
						// $matches[$i]['parsed_attrs']['']
						$this->stylesheets[0]['templates'][] = array(
							'name' => $matches[$i]['parsed_attrs']['name'],
							'match' => $matches[$i]['parsed_attrs']['match'],
							'mode' => $matches[$i]['parsed_attrs']['mode'],
							'priority' => $matches[$i]['parsed_attrs']['priority'],
							'content' => substr($this->stylesheets[0]['content'], $matches[$i][0][1]+strlen($matches[$i][0][0]), $matches[$k][0][1]-$matches[$i][0][1]-strlen($matches[$i][0][0])),
							'parsed_tags' => self::alter_offsets(array_slice($matches, $i+1, $k-$i-1), 0, -1*($matches[$i][0][1]+strlen($matches[$i][0][0]))),
						);
						$i = $k;
						break;
					}
				}
				if($nest_level > 0)
				{
					// No closing tag. Maybe error here?
				}
			}
		}*/
	}
	
	/**
	 * Loads the given file as XSLT for future transformations.
	 * @param string $stylesheet The path to the XSL file.
	 */
	public function load_stylesheet($stylesheet)
	{
		$this->set_stylesheet(file_get_contents($stylesheet), $stylesheet);
	}
	
	/**
	 * Performs the XSLT on the given data using a previously loaded stylesheet.
	 * 
	 * The XSLT is performed using PHP's XSLTProcessor. However, since this function takes the XML data in as an array instead of raw XML, you must instead pass in a properly formatted array of data, which will then be converted to raw XML. This conversion is done by Transformer::array_to_xml().
	 * 
	 * @param array $data An array representing XML data. This is converted to raw XML by Transformer::array_to_xml().
	 * @param string $root The tag name to be used for the root node. The XML from $data will be wrapped in this node.
	 * @param array $includes An array of other XSL files to be inserted into the stylesheet via the xsl:include element.
	 * @return string The transformed content.
	 */
	public function transform($data, $root="DATA", $includes=array())
	{
		$xmlcode = self::array_to_xml($root, $data);
		//print_r($xmlcode);
		$xml = new DOMDocument();
		$xml->loadXML($xmlcode);
		$xsl = new DOMDocument();
		if(is_file($this->stylesheets['file']))
			$xsl->load($this->stylesheets['file']);
		else
			$xsl->loadXML($this->stylesheets['raw']);
		if(is_array($includes)) foreach($includes as $inc)
		{
			$node = $xsl->createElementNS($xsl->documentElement->namespaceURI, $xsl->documentElement->prefix .":include");
			$attr = $xsl->createAttribute("href");
			$attr->value = $inc['href'];
			$node->appendChild($attr);
			$xsl->documentElement->appendChild($node);
		}
		$proc = new XSLTProcessor();
		$proc->importStyleSheet($xsl);
		//return $proc->transformToXML($xml);
		$result = $proc->transformToDoc($xml);
		return html_entity_decode($result->saveHTML(), ENT_QUOTES|ENT_HTML5, "UTF-8");
		
		/*$data = array($root=>$data);
		
		$result = $this->apply_template("/", $data, "/");
		if($result)
			return $result;
		
		$result = $this->apply_template($root, $data, "/");
		if($result)
			return $result;
		
		$result = "";
		if(is_array($data[$root])) foreach($data[$root] as $child=>$child_data)
			$result .= $this->apply_template($child, $data, "/". $root);
		if($result)
			return $result;
		
		return "";*/
	}
	
	/**
	 * Converts a properly-formatted multi-dimensional array into raw XML.
	 * 
	 * Array indexes should correspond to element node names, optionally including a list of attributes separated by `@`. Array values should be one of the following:
	 * + The text content of the element node, for nodes with no child nodes.
	 * + An array formatted the same way as this one, with indexes corresponding to the element node names of the child nodes of this one. This function will be called recursively on that array.
	 * + A numerically indexed array, with each value being one of the two above. This is for multiple sibling elements that all have the same node name, that node name being the index of this element.
	 * Attributes can be specified as noted above by including them in that array element's index, or if the element is an array, they can be elements of that array with indexes in the format: `__attr:attr-name-here` and the value being the value of the attribute.
	 * 
	 * Below is an example of some PHP code that might call this function, followed by the XML that would be produced (tabs and newlines have been added for readability; the function does not output any).
	 * ```php
	 * $data = array(
	 * 	'solo' => array(
	 * 		'something' => "Text"
	 * 	),
	 * 	'child' => array(
	 * 		array(
	 * 			'grandchild@age=1' => array(
	 * 				'trait' => "Tall"
	 * 			)
	 * 		),
	 * 		array(
	 * 			'grandchild' => array(
	 * 				array(
	 * 					'__attr:age' => "2"
	 * 					'trait' => "Short"
	 * 				),
	 * 				array(
	 * 					'__attr:age' => "3"
	 * 					'trait' => "Average"
	 * 				)
	 * 			)
	 * 		)
	 * 	)
	 * );
	 * Transformer::array_to_xml("root title=\"demo\"", $data);
	 * ```
	 * Outputs:
	 * ```xml
	 * <root title="demo">
	 * 	<solo>
	 * 		<something>Text</something>
	 * 	</solo>
	 * 	<child>
	 * 		<grandchild age="1">
	 * 			<trait>Tall</trait>
	 * 		</grandchild>
	 * 	</child>
	 * 	<child>
	 * 		<grandchild age="2">
	 * 			<trait>Short</trait>
	 * 		</grandchild>
	 * 		<grandchild age="3">
	 * 			<trait>Average</trait>
	 * 		</grandchild>
	 * 	</child>
	 * </root>
	 * ```
	 *
	 * If $data is an empty array, then this function will return an empty string with no XML. There is no way to guess whether you wanted an empty XML element back or no XML at all. If you need this element to be present even if empty and you have to have $data be an array, then you will need to guarantee that another element will be inside of it.
	 * 
	 * @param string $tag The XML tag that the $data XML will be wrapped in. This tag should include any attributes and appear exactly as it would in an opening tag of the resulting XML code.
	 * @param array|string $data The properly-formatted array of data to be converted into XML. If this is a string, then this function will simply return this string wrapped in the tag specified by $tag, including if the string is empty.
	 * @return string Raw XML representing the given arguments.
	 */
	public static function array_to_xml($tag, $data)
	{
		if(is_array($data))
		{
			$result = "";
			$multiplets = -1;
			// Handle attributes first and add them directly to the XML tag.
			$innerattrs = 0;
			foreach($data as $subtag=>$subdata)
			{
				if(substr($subtag, 0, 7) == "__attr:")
				{
					if(strlen($subtag) > 7)
						$tag .= " ". substr($subtag, 7) ."=\"". htmlentities($subdata, ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8") ."\"";
					$data[$subtag] = null;
					$innerattrs++;
				}
			}
			// TODO: There is currently no clean way to let the user determine if an empty array comes back as a empty element or blank string. For now, it is always a blank string.
			foreach($data as $subtag=>$subdata)
			{
				if($subdata === null) continue;
				$parts = explode("@", $subtag);
				$attrs = "";
				for($i=1; $i<count($parts); $i++)
				{
					list($name, $value) = explode("=", $parts[$i], 2);
					if($name != "")
						$attrs .= " ". $name ."=\"". htmlentities($value, ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8") ."\"";
				}
				if(is_numeric($subtag) || $parts[0] == "")
				{
					if($multiplets === 0)
						throw new Exception("Malformed data array cannot be converted to XML.");
					$result .= self::array_to_xml($tag . $attrs, $subdata);
					$multiplets = 1;
				}
				else
				{
					if($multiplets === 1)
						throw new Exception("Malformed data array cannot be converted to XML.");
					$result .= self::array_to_xml($parts[0] . $attrs, $subdata);
					$multiplets = 0;
				}
			}
			if($multiplets)
				return $result;
			else
				return "<". $tag .">". $result ."</". explode(" ", $tag, 2)[0] .">";
		}
		else
		{
			return "<". $tag .">". htmlentities($data, ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8") ."</". explode(" ", $tag, 2)[0] .">";
		}
	}
	
	/**
	 * Canonicalizes the XPath defined by the given parameters, and returns an associative array with all the relevant components.
	 * 
	 * This function combines the staring node specified by the `$parent` parameter with the selector specified by the `$select` parameter, and resolves parts of the XPath that are not nodes, such as `.` and `..`. It also parses for any predicates attached to any nodes. The returned array contains three child arrays: one for the XPath as given, one for the nodes with predicates removed, and one with predicates only.
	 * 
	 * Calling this function like so: `print_r(Transformer::get_xpath_components("/data/child[1]/descendant", "../grandchild[2]"));` will return the following array:
	 * ```
	 * Array
	 * (
	 * 	[full] => Array
	 * 		(
	 * 			[0] => data
	 * 			[1] => child[1]
	 * 			[2] => grandchild[2]
	 * 		)
	 * 	[basic] => Array
	 * 		(
	 * 			[0] => data
	 * 			[1] => child
	 * 			[2] => grandchild
	 * 		)
	 * 	[predicate] => Array
	 * 		(
	 * 			[0] => 
	 * 			[1] => 1
	 * 			[2] => 2
	 * 		)
	 * )
	 * ```
	 * 
	 * This function will echo output when errors are encountered. They will appear in the source as HTML comments.
	 * 
	 * @param array|string $parent Either an array that was returned by a previous call to this function, or an XPath string that starts with `/` (the root).
	 * @param string $select An XPath string representing a path to a node. Uses `$parent` to determine its starting node.
	 * @return array[] An associative array of child arrays. The returned array will have three elements with the indexes 'full', 'basic', and 'predicate', each of which is an array representing different components of each node of the canonicalized XPath.
	 * + `array['full']` will contain the node names along with their predicates.
	 * + `array['basic']` will contain just the node names with no predicates.
	 * + `array['predicate']` will contain only the predicates of each node, or a blank string for nodes with no predicate.
	 */
	/*public static function get_xpath_components($parent, $select)
	{
		// TODO: support for '//' in the XPath
		$regAclass = "-:_a-zA-Z0-9";
		$regB = "\\[([".$regAclass."+.*@=><!]+)]";
		// If $select starts with '/', then we start from root regardless of what $parent is.
		if(substr($select, 0, 1) != "/")
		{
			// If $parent is an array previously returned by another call to this function, we can just use it. Otherwise, we need to validate the $parent string and build such an array.
			if(is_array($parent) && is_array($parent['full']))
			{
				$ancestors = $parent['full'];
			}
			else
			{
				if(gettype($parent) != "string" || !preg_match("%^/([".$regAclass."]|(?<!/)/|".$regB.")*$%", $parent))
				{
					echo("<!-- Parameter `parent` of get_xpath_components() must be a valid path starting from root; ". str_replace("--", "˗˗", $parent) ." given. -->");
					$ancestors = array();
				}
				else
					$ancestors = explode("/", $parent);
			}
		}
		else
			$ancestors = array();
		
		// Validate the $select string and build an array of the XPath nodes.
		if(!preg_match("%^([".$regAclass.".@]|(?<!/)/|".$regB.")*$%", $select))
		{
			echo("<!-- Parameter `select` of get_xpath_components() must be a valid XPath; ". str_replace("--", "˗˗", $select) ." given. -->");
			$path = array();
		}
		else
			$path = explode("/", $select);
		
		$result = array_merge($ancestors, $path);
		$num = count($result);
		for($i=0; $i<$num; $i++)
		{
			if($result[$i] == "." || $result[$i] == "")
				unset($result[$i]);
			if($result[$i] == "..")
			{
				$k = 1;
				while(!isset($result[$i-$k]) && $k<=$i)
					$k++;
				if($k > $i)
				{
					echo("<!-- Error while looking for parent in XPath expression in get_xpath_components(). Tried to parse: /". implode("/", array_merge($ancestors, $path)) ." -->");
					return array();
				}
				unset($result[$i-$k]);
				unset($result[$i]);
			}
		}
		
		$results = array();
		$results['full'] = array_values($result);
		$results['basic'] = array();
		$results['predicate'] = array();
		foreach($results['full'] as $i=>$p)
		{
			$a = array();
			$m = preg_match("%^([".$regAclass."@]+)(?:". $regB .")?$%", $p, $a);
			if($m)
			{
				$results['basic'][$i] = $a[1];
				$results['predicate'][$i] = $a[2];
			}
			else
			{
				echo("<!-- Parse error while looking at XPath in get_xpath_components(). Tried to parse: ". $p ." -->");
				$results['basic'][$i] = "";
				$results['predicate'][$i] = "";
			}
		}
		return $results;
	}
	
	protected function apply_template($template_selector, $data, $parent_path="/", $template_mode="", $name_not_match=false)
	{
		// TODO: If the root match is "data", this is not consistent with the built-in XSLT. Built-in treats it like "/data", but this class treats it like "/".
		if($name_not_match)
		{
			foreach($this->stylesheets as $stylesheet)
				if(is_array($stylesheet['templates']))
					foreach($stylesheet['templates'] as $template)
						if($template['name'] == $template_selector)
							return $this->transform_block($template['content'], $data, $parent_path, $template['parsed_tags'])['result'];
			return "";
		}
		
		$path_components = self::get_xpath_components($parent_path, $template_selector);
		$path = "/". implode("/", $path_components['basic']);
		$result = "";
		foreach($this->stylesheets as $stylesheet)
		{
			$priority = false;
			if(is_array($stylesheet['templates'])) foreach($stylesheet['templates'] as $template)
			{
				if(	$template['mode'] == $template_mode &&
					($priority === false || (float)$template['priority'] >= $priority) )
				{
					if(substr($template['match'], 0, 1) == "/")
					{
						$match = (strpos($path, $template['match']) === 0);
					}
					else
					{
						$match = true;
						$match_components = self::get_xpath_components("/", "/".$template['match']);
						for($i=1; $i>=count($match_components); $i++)
						{
							if($match_components['basic'][count($match_components['full'])-$i] != $path_components['basic'][count($path_components['full'])-$i])
							{
								$match = false;
								break;
							}
						}
					}
					if($match)
					{
						$result = $this->transform_block($template['content'], $data, $path_components, $template['parsed_tags'])['result'];
						if(is_numeric($template['priority']))
							$priority = (float)$template['priority'];
					}
				}
			}
			if($result)
				return $result;
		}
		return $result;
	}
	
	public static function alter_offsets($matches, $start, $amount)
	{
		for($i = $start; $i < count($matches); $i++)
			foreach($matches[$i] as &$m)
				$m[1] += $amount;
		return $matches;
	}
	
	public static function resolve_select_recursive($path_components, $i, $data)
	{
		//echo("resolve_select_recursive(". implode("|", $path_components['full']) .", ". $i .", ". (is_array($data) ? implode("|",array_keys($data)) : $data) .")\n");
		if($i == count($path_components['basic']))
			return $data;
		else if(is_array($data))
		{
			$data = $data[$path_components['basic'][$i]];
			if(is_numeric($path_components['predicate'][$i]))
			{
				if(is_array($data) && isset($data[0]) && isset($data[count($data)-1]))
					$data = $data[$path_components['predicate'][$i]-1];
				else if($path_components['predicate'][$i] > 1)
					return "";
			}
			else if(is_array($data) && isset($data[0]) && isset($data[count($data)-1]))
			{
				$result = array('.merger'=>true);
				foreach($data as $k=>$d)
				{
					$temp = self::resolve_select_recursive($path_components, $i+1, $d);
					if(is_array($temp) && $temp['.merger'])
					{
						foreach($temp as $c=>$t)
							if($c != ".merger")
								$result[$i.'.'.($k+1).'/'.$c] = $t;
					}
					else
						$result[$i.'.'.($k+1)] = $temp;
				}
				return $result;
			}
			return self::resolve_select_recursive($path_components, $i+1, $data);
		}
		else
			return "";
	}*/
	
	/**
	 * Resolves the given XPath into the actual node data that it is referencing.
	 * @param string $value The XPath selector, using `$parent_path` as a starting point.
	 * @param array $data The data formatted to correspond to XML. See the transform method for more info.
	 * @param string|array $parent_path An XPath string, or an array returned by a call to `get_xpath_components()`.
	 * @param boolean $foreach Whether this was called by a for-each directive.
	 * @return string|array The data contained within `$data` that was selected by the given XPath. If `$foreach` is false, this is a string. If `$foreach` is true, then this is an array to be looped through; its values are the XPath components to use during each iteration of the loop.
	 */
	/*public static function resolve_select($value, $data, $parent_path, $foreach=false)
	{
		//echo("resolve_select(".$value.", array:".count($data).", ".$parent_path.", ".(int)$foreach.")\n");
		$path_components = self::get_xpath_components($parent_path, $value);
		$newdata = self::resolve_select_recursive($path_components, 0, $data);
		if(is_array($newdata) && $newdata['.merger'])
		{
			unset($newdata['.merger']);
			$result = array();
			foreach($newdata as $mods=>$subdata)
			{
				$n = count($result);
				$result[$n] = $path_components;
				$mods = explode("/", $mods);
				foreach($mods as $mod)
				{
					$ids = explode(".", $mod);
					$result[$n]['full'][$ids[0]] = $result[$n]['basic'][$ids[0]]."[". $ids[1] ."]";
					$result[$n]['predicate'][$ids[0]] = $ids[1];
				}
			}
			reset($newdata);
			$newdata = current($newdata);
		}
		if($foreach)
		{
			if(is_array($result))
				return $result;
			else if($newdata == "" || is_array($newdata) && count($newdata) == 0)
				return array();
			else
				return array($path_components);
		}
		else if(is_array($newdata))
			return array_reduce($newdata, "self::data_reduce", "");
		else
			return $newdata;
	}
	
	public static function data_reduce($carry, $item)
	{
		if(is_array($item))
			return $carry . array_reduce($item, "self::data_reduce", "");
		else
			return $carry . $item;
	}*/
	
	/**
	 * @param string $value The value of the TEST attribute.
	 * @param array $data The array-format XML data to use for any selectors.
	 * @param string $parent_path XPath being used by the current element with respect to the $data parameter.
	 * @param int $level Which set of operands to resolve, so that order of operations can be correctly implemented. This should always be 0 or omitted by any user implementation; this function will recurse through all necessary levels on its own.
	 */
	/*public static function resolve_test($value, $data, $parent_path, $level=-1, $strings=array())
	{
		switch($level)
		{
			// Case -1 handles quoted text and makes sure it's treated as a string
			case -1:
				//echo("\n-1: ". $value);
				$value = html_entity_decode($value);
				$quotes = array();
				preg_match("/([\"'])((?:(?!\\1).)*)\\1/", $value, $quotes, PREG_OFFSET_CAPTURE);
				if(count($quotes))
				{
					$value = substr_replace($value, "#".count($strings), $quotes[0][1], strlen($quotes[0][0]));
					$strings[] = $quotes[2][0];
					return self::resolve_test($value, $data, $parent_path, $level, $strings);
				}
				else
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
			// Case 0 handles parinthesized components of the TEST string.
			case 0:
				//echo("\n0: ". $value);
				$parins = array();
				preg_match_all("/(\((?>[^()]+|(?1))*\))/", $value, $parins, PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
				for($i=count($parins)-1; $i>-1; $i--)
					$value = substr_replace($value, self::resolve_test(substr($parins[$i][0][0], 1, -1), $data, $parent_path, $level, $strings), $parins[$i][0][1], strlen($parins[$i][0][0]));
				return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
			// Case 1 handles any logical 'or' operators.
			case 1:
				//echo("\n1: ". $value);
				$parts = preg_split("%\\bor\\b%i", $value);
				if(count($parts) == 1)
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
				foreach($parts as $part)
				{
					if(self::resolve_test($part, $data, $parent_path, $level+1, $strings))
						return true;
				}
				return false;
			// Case 2 handles any logical 'and' operators.
			case 2:
				//echo("\n2: ". $value);
				$parts = preg_split("%\\band\\b%i", $value);
				if(count($parts) == 1)
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
				foreach($parts as $part)
				{
					if(!self::resolve_test($part, $data, $parent_path, $level+1, $strings))
						return false;
				}
				return true;
			// Case 3 handles any comparison operators that evaulate to booleans.
			case 3:
				//echo("\n3: ". $value);
				$parts = preg_split("%(!=|<=|>=|!|<|>|=)%i", $value, null, PREG_SPLIT_DELIM_CAPTURE);
				//print_r($parts);
				if(count($parts) == 1)
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
				$parsed = array();
				for($i=1; $i<count($parts); $i+=2)
				{
					if(!isset($parsed[$i+1]))
						$parsed[$i+1] = self::resolve_test($parts[$i+1], $data, $parent_path, $level+1, $strings);
					if(!isset($parsed[$i-1]))
						$parsed[$i-1] = self::resolve_test($parts[$i-1], $data, $parent_path, $level+1, $strings);
					switch($parts[$i])
					{
						case "=": if($parsed[$i-1] != $parsed[$i+1]) return false; break;
						case "!=": if($parsed[$i-1] == $parsed[$i+1]) return false; break;
						case ">=": if($parsed[$i-1] < $parsed[$i+1]) return false; break;
						case "<=": if($parsed[$i-1] > $parsed[$i+1]) return false; break;
						case ">": if($parsed[$i-1] <= $parsed[$i+1]) return false; break;
						case "<": if($parsed[$i-1] >= $parsed[$i+1]) return false; break;
						default: return false;
					}
				}
				return true;
			// Case 4 handles any addition and subtraction.
			case 4:
				//echo("\n4: ". $value);
				$parts = preg_split("%(\\+|-)%i", $value, null, PREG_SPLIT_DELIM_CAPTURE);
				if(count($parts) == 1)
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
				$parsed = array();
				for($i=0; $i<count($parts); $i+=2)
					$parsed[$i] = self::resolve_test($parts[$i], $data, $parent_path, $level+1, $strings);
				$result = (float)$parsed[0];
				for($i=1; $i<count($parts); $i+=2)
				{
					switch($parts[$i])
					{
						case "+": $result += (float)$parsed[$i+1]; break;
						case "-": $result -= (float)$parsed[$i+1]; break;
					}
				}
				return $result;
			// Case 5 handles any multiplication and division.
			case 5:
				//echo("\n5: ". $value);
				$parts = preg_split("%(\\*|\\bdiv\\b|\\bmod\\b)%i", $value, null, PREG_SPLIT_DELIM_CAPTURE);
				if(count($parts) == 1)
					return self::resolve_test($value, $data, $parent_path, $level+1, $strings);
				$parsed = array();
				for($i=0; $i<count($parts); $i+=2)
					$parsed[$i] = self::resolve_test($parts[$i], $data, $parent_path, $level+1, $strings);
				$result = (float)$parsed[0];
				for($i=1; $i<count($parts); $i+=2)
				{
					switch($parts[$i])
					{
						case "*": $result *= (float)$parsed[$i+1]; break;
						case "div": $result /= (float)$parsed[$i+1]; break;
						case "mod": $result %= (float)$parsed[$i+1]; break;
					}
				}
				return $result;
			// Case 6 handles any XPath components by fetching their actual values, or returns the input value if it is not XPath.
			case 6:
				//echo("\n6: ". $value);
				//print_r($strings);
				$value = trim($value);
				if(is_numeric($value))
					return (float)$value;
				else if(substr($value, 0, 1) == "#")
					return $strings[substr($value, 1)];
				else if(is_array($data))
					return self::resolve_select($value, $data, $parent_path);
				else
					return $value;
			default:
				return false;
		}
	}

	public static function transform_block($markup, $data, $parent_path, $matches, $extra=array())
	{
		//echo("transform_block(string:".strlen($markup).", array:".count($data).", ".$parent_path.", array:".count($matches).")\n");
		for($i = 0; $i < count($matches); $i++)
		{
			if($matches[$i]['close'][0])
			{
				throw new Exception("An unprocessed XSL closing tag was found by the transformer. Check your XSLT syntax involving the following tag: ". trim($matches[$i][0][0]));
			}
			else if($matches[$i]['empty'][0])
			{
				$processed = self::process_tag($matches[$i]['type'][0], $matches[$i]['parsed_attrs'], "", $data, $parent_path);
				if(is_array($processed))
				{
					if(is_array($processed['extra']))
						$extra = array_merge($extra, $processed['extra']);
					if($processed['result'] != "")
						$processed = $processed['result'];
					else
						$processed = "";
				}
				$markup = substr_replace($markup, $processed, $matches[$i][0][1], strlen($matches[$i][0][0]));
				$matches = self::alter_offsets($matches, $i+1, strlen($processed)-strlen($matches[$i][0][0]));
			}
			else if($matches[$i]['type'][0])
			{
				$nest_level = 1;
				for($k = $i+1; $k < count($matches); $k++)
				{
					if($matches[$k]['type'][0] == $matches[$i]['type'][0])
						$nest_level += ($matches[$k]['close'][0] ? -1 : 1);
					if($nest_level == 0)
					{
						$processed = self::process_tag(
							$matches[$i]['type'][0],
							$matches[$i]['parsed_attrs'],
							substr($markup, $matches[$i][0][1]+strlen($matches[$i][0][0]), $matches[$k][0][1]-$matches[$i][0][1]-strlen($matches[$i][0][0])),
							$data,
							$parent_path,
							self::alter_offsets(array_slice($matches, $i+1, $k-$i-1), 0, -1*($matches[$i][0][1]+strlen($matches[$i][0][0])))
						);
						if(is_array($processed))
						{
							if(is_array($processed['extra']))
								$extra = array_merge($extra, $processed['extra']);
							if($processed['result'] != "")
								$processed = $processed['result'];
							else
								$processed = "";
						}
						$markup = substr_replace($markup, $processed, $matches[$i][0][1], $matches[$k][0][1]+strlen($matches[$k][0][0])-$matches[$i][0][1]);
						$matches = self::alter_offsets($matches, $i+1, strlen($processed)-($matches[$k][0][1]+strlen($matches[$k][0][0])-$matches[$i][0][1]));
						$i = $k;
						break;
					}
				}
				if($nest_level > 0)
				{
					throw new Exception("An unclosed XSL tag was found. Check your XSLT syntax involving the following tag: ". trim($matches[$i][0][0]));
				}
			}
		}
		return array('result'=>$markup, 'extra'=>$extra);
	}
	
	public static function process_tag($type, $attrs, $innerML, $data, $parent_path, $matches=null)
	{
		//echo("process_tag(".$type.", array:".count($attrs).", string:".strlen($innerML).", array:".count($data).", ".$parent_path.", array:".count($matches).")\n");
		if($type == "value-of")
		{
			return self::resolve_select($attrs['select'], $data, $parent_path);
		}
		else if($type == "text")
		{
			$result = self::transform_block($innerML, $data, $parent_path, $matches)['result'];
			if($attrs['disable-output-escaping'] == "yes")
				return html_entity_decode($result);
			else
				return $result;
		}
		else if($type == "for-each")
		{
			$target = self::resolve_select($attrs['select'], $data, $parent_path, true);
			if(is_array($target))
			{
				// TODO: Support for attributes.
				// TODO: Support for sort type.
				$result = "";
				foreach($target as $path)
				{
					$result .= self::transform_block($innerML, $data, $path, $matches)['result'];
				}
				return $result;
			}
		}
		else if($type == "if")
		{
			if(self::resolve_test($attrs['test'], $data, $parent_path))
				return self::transform_block($innerML, $data, $parent_path, $matches);
			else
				return "";
		}
		else if($type == "element")
		{
			//if($attrs['use-attribute-sets']) TODO: This.
			$result = self::transform_block($innerML, $data, $parent_path, $matches);
			$elemattrs = "";
			foreach($result['extra'] as $i=>$ex)
				if($ex['type'] == "attribute")
				{
					$elemattrs .= " ". $ex['name'] ."=\"". $ex['value'] ."\"";
					unset($result['extra'][$i]);
				}
			if($attrs['name'])
				return "<". $attrs['name'] . $elemattrs . ($attrs['namespace'] ? " xmlns=\"". $attrs['namespace'] ."\"" : "") . ($result['result']!=""||$attrs['name']=="script" ? ">". $result['result'] ."</". $attrs['name'] : "/") .">";
			else
				return "<!-- Transformer error: ELEMENT tag does not have a valid NAME attribute: ". str_replace("--", "˗˗", print_r($target, true)) ." -->";
		}
		else if($type == "attribute")
		{
			if($attrs['name'])
				return array('extra'=>array(array('type'=>$type, 'name'=>$attrs['name'], 'namespace'=>$attrs['namespace'], 'value'=>self::transform_block($innerML, $data, $parent_path, $matches)['result'])));
			else
				return "<!-- Transformer error: ATTRIBUTE tag does not have a valid NAME attribute: ". str_replace("--", "˗˗", print_r($target, true)) ." -->";
		}
		else if($type == "variable")
		{
			if($attrs['name'])
			{
				$result = array('extra'=>array(array('type'=>$type, 'name'=>$attrs['name'])));
				if($attrs['select'])
					$result['value'] = self::resolve_select($attrs['select'], $data, $parent_path, true);
				else
					$result['value'] = self::transform_block($innerML, $data, $parent_path, $matches)['result'];
				return $result;
			}
			else
				return "<!-- Transformer error: VARIABLE tag does not have a valid NAME attribute: ". str_replace("--", "˗˗", print_r($target, true)) ." -->";
		}
		else if($type == "template")
		{
			return "<!-- Transformer error: Nested TEMPLATE tags are not allowed. -->";
		}
		else
			return "<!-- Transformer error: Unknown tag type: ". str_replace("--", "˗˗", $type) ." -->";
		return "<!-- Transformer error: Unknown error with tag: ". str_replace("--", "˗˗", $type) ." -->";
	}*/
}