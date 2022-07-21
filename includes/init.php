<?php
/**
This file handles a few definitions that need to be in place before MeLeeCMS loads.
The following things are handled by this file:
- Setting `display_errors` to `false`.
- Setting `log_errors` to `true`.
- Setting the error log file to a directory and file in `includes/logs` based on today's date.
- Records the current system time and memory before MeLeeCMS runs, so that usage data can be calculated later.
- Defines a function that performs said calculation.
*/
namespace MeLeeCMS;

ini_set("display_errors", 0);
ini_set("log_errors", 1);
$error_dir = __DIR__ . DIRECTORY_SEPARATOR ."logs". DIRECTORY_SEPARATOR ."errors-". date("Y-m");
$error_file = $error_dir . DIRECTORY_SEPARATOR . date("Y-m-d") .".log";
if(!is_dir($error_dir))
{
   mkdir($error_dir, 0770, true);
}
if(!is_file($error_file))
{
   touch($error_file);
   chmod($error_file, 0660);
}
ini_set("error_log", $error_file);
// Note: Permissions for the log directory and log files is going to be totally screwed. They will be owned by the PHP/Apache user, and the group will also be the PHP/Apache group like every other file here, so the actual logged-in linux user is just SOL. No clue how to fix this other than with a root script, way outside the jurisdiction of this CMS.

/** @var int The current memory usage at the time the page starts loading. */
define("START_MEMORY", memory_get_usage());
/** @var float The current microsecond timestamp at the time the page starts loading. */
define("START_TIME", microtime(true));
/**
Prints out the time elapsed and net memory usage since the page first started loading, in the form of an HTML comment.
@return void
*/
function print_load_statistics()
{
	$time = (round((microtime(true) - START_TIME)*1000000)/1000) ." ms";
	
	$mem = memory_get_usage() - START_MEMORY;
	if($mem > 1048576*1.5)
		$mem = round($mem/1048576, 3) ." MB";
	else if($mem > 1024*1.5)
		$mem = round($mem/1024, 2) ." kB";
	else
		$mem = $mem ." B";
	
	$peak = memory_get_peak_usage() - START_MEMORY;
	if($peak > 1048576*1.5)
		$peak = round($peak/1048576, 3) ." MB";
	else if($peak > 1024*1.5)
		$peak = round($peak/1024, 2) ." kB";
	else
		$peak = $peak ." B";
	
	echo("<!-- MeLeeCMS Load Statistics; Time: ". $time .", Memory: ". $mem ." (Peak: ". $peak .") -->");
}
   
function stack_trace_string($start=0, $steps=0)
{
   if($start < 0)
      $start = 0;
   // Start at 1, because 0 is literally just this method.
   $start += 1;
   $result = "";
   foreach(debug_backtrace(0) as $i=>$step)
   {
      if($i < $start)
         continue;
      if($steps > 0 && $i == $start + $steps)
         break;
      $result .= "\tStack-{$i}: ";
      if(!empty($step['class']))
         $result .= $step['class'];
      if(!empty($step['type']))
         $result .= $step['type'];
      if(!empty($step['function']))
         $result .= $step['function'];
      if(!empty($step['args']))
      {
         $result .= "(";
         foreach($step['args'] as $arg=>$val)
            if(is_array($val))
               $result .= "array[". count($val) ."],";
            else if(is_object($val))
               $result .= get_class($val) .",";
            else if(is_string($val))
               $result .= "\"{$val}\",";
            else
               $result .= "{$val},";
         $result = substr($result,0,-1) .")";
      }
      else if(!empty($step['function']))
         $result .= "()";
      if(!empty($step['file']))
         $result .= " in {$step['file']}";
      if(!empty($step['line']))
         $result .= " at line {$step['line']}";
      $result .= "\n";
   }
   return $result;
}
