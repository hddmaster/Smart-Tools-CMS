<?
function use_file($filename,$table,$column)
 {
   $result = @mysql_query("select * from $table where $column = '$filename'");
   if (@mysql_num_rows($result) > 1)
     return true;
   else
     return false;
 }

function use_field($field,$table,$column,$add='')
 {
   $result = @mysql_query("select * from $table where $column = '".stripslashes($field)."' $add");
   if (@mysql_num_rows($result) > 0)
     return true;
   else
     return false;
 }
?>
