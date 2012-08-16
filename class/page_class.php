<?
class Page
 {
   public $script;
   public $page_id;
   public $page_title = 'Smart Tools CMS';
   public $separator = ' - ';
                
   function __construct($script = '')
    {
      if ($script) $this->script = $script;
      else $this->script = $_SERVER['PHP_SELF'];

      $result = mysql_query("select * from auth_scripts where script_path = '$this->script'");
      if (mysql_num_rows($result) > 0)
       {
         $row = mysql_fetch_array($result);
         if($row['script_descr']) $this->page_title .= $this->separator;
         $this->page_title .= $row['script_descr'];
         $this->page_id = $row['page_id'];
       }      
    }
 }
?>
