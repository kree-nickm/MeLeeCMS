<?

class Form extends Content
{
	public $attributes = array();
	public $elements = array();
	
	public function __construct()
	{
	}
	
	public function get_properties()
	{
		return array(
			'attributes' => array(
				'type' => "array"
			),
			'elements' => array(
				'type' => "array"
			)
		);
	}
	
	public function build_params()
	{
		$result = array();
		foreach($this->attributes as $attr=>$val)
			$result[$attr] = $val;
		foreach($this->elements as $i=>$elem)
			$result['element'.$i] = $elem->build_params();
		return $result;
	}
	
	public function setAttribute($attr, $val)
	{
		$this->attributes[$attr] = $val;
	}
	
	public function addElement($type="text")
	{
		switch($type)
		{
		case "text":
		default:
			$element = new InputText($this);
		}
		return $this->elements[] =& $element;
	}
}

class Input
{
	protected $form;
	protected $attributes = array();
	
	public function __construct(&$form)
	{
		$this->form =& $form;
	}
	
	public function setAttribute($attr, $val)
	{
		$this->attributes[$attr] = $val;
	}
	
	public function build_params()
	{
		$result = array();
		foreach($this->attributes as $attr=>$val)
			$result[$attr] = $val;
		return $result;
	}
}

class InputText extends Input
{
}