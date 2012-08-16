<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['constant_name']) &&
   isset($_POST['constant_value']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   $a = ''; if (isset($_GET['lang_id'])) {$lang_id = $_GET['lang_id']; $a = '&lang_id='.$lang_id;}
   if (trim($_POST['constant_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}{$a}&message=formvalues"); exit();}
   $constant_id = (int)$_GET['id'];
   $constant_name = $_POST['constant_name'];
   $constant_value = $_POST['constant_value'];

   $result = mysql_query("select * from lang_constants constant_name='$constant_name' and constant_id != ".$constant_id);
   if(mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$constant_id}{$a}&message=duplicate"); exit();}

   $result = mysql_query("update lang_constants set constant_name='$constant_name' where constant_id=$constant_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$constant_id}{$a}&message=db"); exit();}
   
   if ($lang_id)
    {
      $res = mysql_query("select * from lang_constant_values where constant_id = $constant_id and lang_id = $lang_id");
      if (mysql_num_rows($res) > 0) $result = mysql_query("update lang_constant_values set constant_value='$constant_value' where constant_id = $constant_id and lang_id = $lang_id");
      else $result = mysql_query("insert into lang_constant_values (lang_id, constant_id, constant_value) values ($lang_id, $constant_id, '$constant_value')");
    } 
   else $result = mysql_query("update lang_constants set constant_value='$constant_value' where constant_id = $constant_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$constant_id}{$a}&message=db"); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id={$constant_id}{$a}");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $constant_id = (int)$_GET['id'];
   $a = ''; if (isset($_GET['lang_id'])) {$lang_id = $_GET['lang_id']; $a = '&lang_id='.$lang_id;}
   $result = mysql_query("select * from lang_constants where constant_id = $constant_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $constant_name = $row['constant_name'];
   $constant_value = $row['constant_value'];
   
   if ($lang_id)
    {
      $result = mysql_query("select * from lang_constant_values where lang_id = $lang_id and constant_id = $constant_id");
      if (mysql_num_rows($result) >= 0)
       {
         $row = mysql_fetch_array($result);
         $constant_value = $row['constant_value'];
       }
    }
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$constant_id.$a.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="constant_name" value="'.htmlspecialchars($constant_name).'" maxlength="255"></td>
    </tr>
   </table><br>';
  
  $tabs = new Tabs;
  $tabs->auto_detect_page = false;
  $flag = ''; if (!isset($_GET['lang_id'])) $flag = 1;
  $tabs->add_tab('/admin/editors/edit_lang_constant.php?id='.$constant_id, 'Базовый язык', $flag);
  $res = mysql_query("select * from languages order by lang_code asc");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        $flag = ''; if (isset($_GET['lang_id']) && $_GET['lang_id'] == $r['lang_id']) $flag = 1;
        $tabs->add_tab('/admin/editors/edit_lang_constant.php?id='.$constant_id.'&lang_id='.$r['lang_id'] , htmlspecialchars($r['lang_code']), $flag);
      }
   }
  $tabs->show_tabs();
  echo '<textarea wrap="off" style="font-family:Courier New;font-size:10pt;width:100%;height:420px" name="constant_value">'.htmlspecialchars($constant_value).'</textarea>
  <div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>