<?php

class FileSystem
{
   protected $cms;
   public $files = [];
   public $phpUserId;
   public $phpUser;
   
   function __construct($cms)
   {
      $this->cms = $cms;
      $this->userId = getmyuid();
      $this->phpUserId = posix_geteuid();
      $this->processDir($cms->get_setting('server_path'));
   }
   
   private function processDir($dirStr)
   {
      $dir = dir($dirStr);
      while(($entry = $dir->read()) !== false)
      {
         if(($entry != ".htaccess" && $entry[0] == ".") || in_array($entry, ["LICENSE", "README.md"]))
            continue;
         $fullpath = $dir->path . DIRECTORY_SEPARATOR . $entry;
         $this->files[$fullpath] = [
            'filename' => $entry,
            'relpath' => substr($fullpath, strlen($this->cms->get_setting('server_path'))),
         ];
         $this->getStats($fullpath);
         if(is_file($fullpath))
         {
            $this->files[$fullpath]['file'] = true;
         }
         if(is_dir($fullpath))
         {
            $this->files[$fullpath]['dir'] = true;
            $this->processDir($fullpath);
         }
      }
      $dir->close();
   }
   
   private function getStats($fullpath)
   {
      $this->files[$fullpath]['ownerId'] = fileowner($fullpath);
      $this->files[$fullpath]['groupId'] = filegroup($fullpath);
      $this->files[$fullpath]['stat'] = stat($fullpath);
      $this->files[$fullpath]['permInt'] = fileperms($fullpath);
      $this->files[$fullpath]['permOct'] = substr(sprintf('%o', $this->files[$fullpath]['permInt']), -4);
      $this->files[$fullpath]['perms'] = [
         'u' => ($this->files[$fullpath]['permInt'] & 0x0800),
         'g' => ($this->files[$fullpath]['permInt'] & 0x0400),
         's' => ($this->files[$fullpath]['permInt'] & 0x0200),
         'or' => ($this->files[$fullpath]['permInt'] & 0x0100),
         'ow' => ($this->files[$fullpath]['permInt'] & 0x0080),
         'ox' => ($this->files[$fullpath]['permInt'] & 0x0040),
         'gr' => ($this->files[$fullpath]['permInt'] & 0x0020),
         'gw' => ($this->files[$fullpath]['permInt'] & 0x0010),
         'gx' => ($this->files[$fullpath]['permInt'] & 0x0008),
         'ar' => ($this->files[$fullpath]['permInt'] & 0x0004),
         'aw' => ($this->files[$fullpath]['permInt'] & 0x0002),
         'ax' => ($this->files[$fullpath]['permInt'] & 0x0001),
      ];
   }
}
