<?php

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
		$this->stylesheets = [];
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
	public function transform($data, $root="DATA", $includes=[])
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
		// Note: Don't know if we should care about this, but using ENT_HTML5 means we require PHP>=5.4.0
		return html_entity_decode($result->saveHTML(), ENT_QUOTES|ENT_HTML5, "UTF-8");
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
		if(is_object($data))
			$data = json_decode(json_encode($data), true);
		// Note: Don't know if we should care about this, but using ENT_XML1|ENT_DISALLOWED in this method means we require PHP>=5.4.0
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
					$subparts = explode("=", $parts[$i], 2);
					if($subparts[0] != "")
						$attrs .= " ". $subparts[0] ."=\"". (empty($subparts[1]) ? "" : htmlentities($subparts[1], ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8")) ."\"";
				}
				if(is_numeric($subtag) || $parts[0] == "")
				{
					if($multiplets === 0)
						throw new Exception("Malformed data array cannot be converted to XML. Numeric index (". $subtag .") when container is already not an array. Element: <". $tag . $attrs .">");
					$result .= self::array_to_xml($tag . $attrs, $subdata);
					$multiplets = 1;
				}
				else
				{
					if($multiplets === 1)
						throw new Exception("Malformed data array cannot be converted to XML. Non-numeric index when container is already an array. Element: <". $parts[0] . $attrs .">");
               $fixedtag = preg_replace("/[^a-zA-Z0-9_]/i", "", $parts[0]);
               $fixedtag .= " original_tag=\"". htmlentities($parts[0], ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8") ."\"";
					$result .= self::array_to_xml($fixedtag . $attrs, $subdata);
					$multiplets = 0;
				}
			}
			if($multiplets)
				return $result;
			else
			{
				if(is_numeric(substr($tag, 0, 1)))
					$tag = "_". $tag;
				// Note: Don't know if we should care about this, but using array dereferencing here and below means we require PHP>=5.4.0
				return "<". $tag .">". $result ."</". explode(" ", $tag, 2)[0] .">";
			}
		}
		else
		{
			if(is_numeric(substr($tag, 0, 1)))
				$tag = "_". $tag;
			return "<". $tag .">". htmlentities($data, ENT_QUOTES|ENT_XML1|ENT_DISALLOWED, "UTF-8") ."</". explode(" ", $tag, 2)[0] .">";
		}
	}
}