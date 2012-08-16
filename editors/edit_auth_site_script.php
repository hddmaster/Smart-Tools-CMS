<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['script_descr']) &&
   isset($_POST['script_path']) &&
   isset($_GET['id']))
 {
  if ($user->check_user_rules('edit'))
  {
  if (trim($_POST['script_path'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $script_id = (int)$_GET['id'];
   $script_descr = $_POST['script_descr'];
   $script_path = $_POST['script_path'];

   // проверка на наличие файла
   if (!file_exists($_SERVER['DOCUMENT_ROOT']."$script_path")) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=filenotexists");exit();}

   $result = mysql_query("select * from auth_site_scripts where script_path='$script_path' and script_id != $script_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=duplicate"); exit();}

   $result = mysql_query("update auth_site_scripts set script_descr='$script_descr', script_path='$script_path' where script_id=$script_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$script_id&message=db"); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $script_id = (int)$_GET['id'];
   $result = mysql_query("select * from auth_site_scripts where script_id=$script_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $script_descr = $row['script_descr'];
   $script_path = $row['script_path'];
   

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$script_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Путь <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="script_path" value="'.htmlspecialchars($script_path).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание файла</td>
      <td>
      <input style="width:280px" type="text" name="script_descr" value="'.htmlspecialchars($script_descr).'" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>