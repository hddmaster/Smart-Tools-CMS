<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['lang_code']) &&
   isset($_POST['lang_name']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['lang_code'])=='' || trim($_POST['lang_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $lang_id = (int)$_GET['id'];
   $lang_code = $_POST['lang_code'];
   $lang_name = $_POST['lang_name'];

   $result = mysql_query("select * from languages lang_code='$lang_code' and lang_id != ".$lang_id);
   if(mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$lang_id&message=duplicate"); exit();}

   $result = mysql_query("update languages set lang_code='$lang_code', lang_name='$lang_name' where lang_id=$lang_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$lang_id&message=db"); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$lang_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $lang_id = (int)$_GET['id'];
   $result = mysql_query("select * from languages where lang_id = $lang_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $lang_code = $row['lang_code'];
   $lang_name = $row['lang_name'];
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$lang_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Код <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="lang_code" value="'.htmlspecialchars($lang_code).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="lang_name" value="'.htmlspecialchars($lang_name).'" maxlength="255"></td>
      <td id="w_td"><a href="/admin/editors/edit_lang_database.php?table=languages&column=lang_name&rowkey=lang_id&row='.$lang_id.'"><img src="/admin/images/globus.gif" alt="" border=""></a></td></tr>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>