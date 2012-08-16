<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['table']) &&
    isset($_GET['column']) &&
    isset($_GET['rowkey']) &&
    isset($_GET['row']) &&
    isset($_POST['value']))
 {
 if ($user->check_user_rules('edit'))
  {
   $table = $_GET['table'];
   $column = $_GET['column'];
   $rowkey = $_GET['rowkey'];
   $row = $_GET['row'];
   $a = ''; if (isset($_GET['lang_id'])) {$lang_id = $_GET['lang_id']; $a = '&lang_id='.$lang_id;}
   $value = $_POST['value'];

   if ($lang_id)
    {
      $res = mysql_query("select * from lang_database where `lang_id` = $lang_id and `table` = '$table' and `column` = '$column' and `rowkey` = '$rowkey' and `row` = $row");
      if (mysql_num_rows($res) > 0) $result = mysql_query("update lang_database set value='$value' where `lang_id` = $lang_id and `table` = '$table' and `column` = '$column' and `rowkey` = '$rowkey' and `row` = $row");
      else $result = mysql_query("insert into lang_database (`lang_id`, `table`, `column`, `rowkey`, `row`, `value`) values ($lang_id, '$table', '$column', '$rowkey', $row, '$value')");
    } 
   else $result = mysql_query("update $table set $column='$value' where $rowkey = $row");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF'].'?table='.$table.'&column='.$column.'&rowkey='.$rowkey.'&row='.$row.$a.'&message=db'); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF'].'?table='.$table.'&column='.$column.'&rowkey='.$rowkey.'&row='.$row.$a);
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['table']) &&
   isset($_GET['column']) &&
   isset($_GET['rowkey']) &&
   isset($_GET['row']))
 {
 if ($user->check_user_rules('view'))
  {
   $table = $_GET['table'];
   $column = $_GET['column'];
   $rowkey = $_GET['rowkey'];
   $row = $_GET['row'];
   $a = ''; if (isset($_GET['lang_id'])) {$lang_id = $_GET['lang_id']; $a = '&lang_id='.$lang_id;}

   $res = mysql_query("select $column from $table where $rowkey = $row");
   if (!$res) exit();
   $r_value = mysql_fetch_array($res); $value = $r_value[$column];

   if ($lang_id)
    {
      $res = mysql_query("select
                          value
                          from
                          lang_database
                          where
                          lang_id = $lang_id and
                          `table` = '$table' and
                          `column` = '$column' and
                          `rowkey` = '$rowkey' and
                          `row` = $row");
      if (mysql_num_rows($res) >= 0)
       {
         $r_value = mysql_fetch_array($res);
         $value = $r_value['value'];
       }
    }
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

  echo '<form action="?table='.$table.'&column='.$column.'&rowkey='.$rowkey.'&row='.$row.$a.'" method="post">';
  $tabs = new Tabs;
  $tabs->auto_detect_page = false;
  $flag = ''; if (!isset($_GET['lang_id'])) $flag = 1;
  $tabs->add_tab('/admin/editors/edit_lang_database.php?table='.$table.'&column='.$column.'&rowkey='.$rowkey.'&row='.$row, 'Базовый язык', $flag);
  $res = mysql_query("select * from languages order by lang_code asc");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        $flag = ''; if (isset($_GET['lang_id']) && $_GET['lang_id'] == $r['lang_id']) $flag = 1;
        $tabs->add_tab('/admin/editors/edit_lang_database.php?table='.$table.'&column='.$column.'&rowkey='.$rowkey.'&row='.$row.'&lang_id='.$r['lang_id'] , htmlspecialchars($r['lang_code']), $flag);
      }
   }
  $tabs->show_tabs();
  echo '<textarea wrap="off" style="font-family:Courier New;font-size:10pt;width:100%;height:460px" name="value">'.htmlspecialchars($value).'</textarea>
  <div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>