<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['user_type_name']) &&
   isset($_POST['user_type_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['user_type_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $user_type = (int)$_GET['id'];
   $user_type_name = $_POST['user_type_name'];
   $user_type_descr = $_POST['user_type_descr'];

   $result = mysql_query("select * from auth_users where user_type_name='$user_type_name'");
   while ($row = mysql_fetch_array($result))
    {
      if ($row['user_type'] !== $user_type) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_type&message=duplicate"); exit();}
    }
   $result = mysql_query("update auth_users set user_type_name='$user_type_name', user_type_descr='$user_type_descr' where user_type=$user_type");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_type&message=db"); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_type");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $user_type = (int)$_GET['id'];
   $result = mysql_query("select * from auth_users where user_type=$user_type");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $user_type_name = $row['user_type_name'];
   $user_type_descr = $row['user_type_descr'];
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$user_type.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="user_type_name" value="'.htmlspecialchars($user_type_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td>
      <input style="width:280px" type="text" name="user_type_descr" value="'.htmlspecialchars($user_type_descr).'" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>