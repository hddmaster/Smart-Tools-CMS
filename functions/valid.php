<?
function valid_email($email)
 {
   return (preg_match('/^[a-z0-9_\.\-]+@[a-z0-9_\.\-]+\.[a-z0-9_\-]{2,4}$/', strtolower($email))) ? true : false;
 }
?>
