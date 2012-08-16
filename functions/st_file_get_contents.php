<?

function st_file_get_contents($file)
 {
   if ($handle = fopen($file,'rb'))
    {
      $contents = '';
      while (!feof($handle)) $contents .= fread($handle, 8192);
      fclose($handle);
      if ($contents) return $contents; else return false;
    }
   else return false;
 }
 

?>