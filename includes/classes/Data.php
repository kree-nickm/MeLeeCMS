<?php
namespace MeLeeCMS;

class Data
{
   const NO_AUTO_ARRAY = 1;
   const NO_OVERWRITE = 2;
   const NO_JSON_OUT = 4;
   const NO_ARRAY_OUT = 8;
   const CUSTOM = 16;
   
   protected $data = [];
   protected $index_count = [];
   protected $custom_index_count = [];
   
   public function add($index, $data, $flags=0, $errorIfExists=E_USER_NOTICE)
	{
      if(empty($index))
      {
         trigger_error("Failed to add data because index was empty.", E_USER_WARNING);
         return false;
      }
      $new_data = [
         'index' => $index,
         'data' => $data,
         'custom' => ($flags & self::CUSTOM) > 0,
         'js_out' => ($flags & self::NO_JSON_OUT) == 0,
         'xml_out' => ($flags & self::NO_ARRAY_OUT) == 0,
      ];
      
      $already = false;
      if(($flags & self::NO_AUTO_ARRAY) > 0)
      {
         foreach($this->data as $old_data)
         {
            if($old_data['index'] == $new_data['index'] && $old_data['custom'] == $new_data['custom'])
            {
               $already = true;
               break;
            }
         }
      }
      
      if($already)
      {
         if(($flags & self::NO_OVERWRITE) > 0)
         {
            if(!empty($errorIfExists))
               trigger_error("Attempting to set MeLeeCMS ". ($new_data['custom'] ? "custom " : "") ."data with index '". $index ."', but it is already set and isn't allowing an array. Ignoring new data.", $errorIfExists);
            return false;
         }
         else
         {
            if(!empty($errorIfExists))
               trigger_error("Attempting to set MeLeeCMS ". ($new_data['custom'] ? "custom " : "") ."data with index '". $index ."', but it is already set and isn't allowing an array. Overwriting previous data.", $errorIfExists);
            $this->data = array_filter($this->data, function($val) use($new_data) {
               return $val['index'] != $new_data['index'] || $val['custom'] != $new_data['custom'];
            });
            if($new_data['custom'])
               $this->custom_index_count[$new_data['index']] = 1;
            else
               $this->index_count[$new_data['index']] = 1;
            $this->data[] = $new_data;
            return true;
         }
      }
      else
      {
         if($new_data['custom'])
         {
            if(empty($this->custom_index_count[$new_data['index']]))
               $this->custom_index_count[$new_data['index']] = 1;
            else
               $this->custom_index_count[$new_data['index']]++;
         }
         else
         {
            if(empty($this->index_count[$new_data['index']]))
               $this->index_count[$new_data['index']] = 1;
            else
            $this->index_count[$new_data['index']]++;
         }
         $this->data[] = $new_data;
         return true;
      }
	}
   
   protected function output($format)
   {
      $output = [];
      foreach($this->data as $data)
      {
         if($format == 1 && $data['js_out'] || $format == 2 && $data['xml_out'])
         {
            if($data['custom'])
            {
               if(empty($output['custom']))
                  $output['custom'] = [];
               if(!empty($output['custom'][$data['index']]))
                  $output['custom'][$data['index']][] = $data['data'];
               else if($this->custom_index_count[$data['index']] > 1)
                  $output['custom'][$data['index']] = [$data['data']];
               else
                  $output['custom'][$data['index']] = $data['data'];
            }
            else
            {
               if(!empty($output[$data['index']]))
                  $output[$data['index']][] = $data['data'];
               else if($this->index_count[$data['index']] > 1)
                  $output[$data['index']] = [$data['data']];
               else
                  $output[$data['index']] = $data['data'];
            }
         }
      }
      if($format == 1)
      {
         $json = json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR);
         if($json_err = json_last_error())
            trigger_error("Error code '{$json_err}' triggered when encoding MeLeeCMS data to JSON.", E_USER_WARNING);
         if(!empty($json))
            return $json;
         else
            return "{}";
      }
      else if($format == 2)
      {
         return $output;
      }
      else
      {
         throw new \Exception("Invalid format '{$format}' given in Data->output().");
      }
   }
   
   public function toJSON()
   {
      return $this->output(1);
   }
   
   public function toArray()
   {
      return $this->output(2);
   }
}
