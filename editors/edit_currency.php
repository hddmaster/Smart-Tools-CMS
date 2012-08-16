<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['currency_name']) &&
   isset($_POST['currency_descr']) &&
   isset($_GET['id']))
 {
  if ($user->check_user_rules('edit'))
  {
  if (trim($_POST['currency_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $id = (int)$_GET['id'];
   $currency_name = $_POST['currency_name'];
   $currency_descr = $_POST['currency_descr'];

   $result = mysql_query("select * from currencies where currency_name = '".stripslashes($currency_name)."' and currency_id != $id");
   if (mysql_num_rows($result) > 0)
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=duplicate"); exit();}

   $result = mysql_query("update currencies set currency_name='$currency_name', currency_descr='$currency_descr' where currency_id=$id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$id&message=db"); exit();}

   Header("Location: ".$_SERVER['PHP_SELF']."?id=$id");
   exit();
  } else $user->no_rules('edit');
 }
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {

 if ($user->check_user_rules('view'))
  {
   $id = (int)$_GET['id'];
   $result = mysql_query("select * from currencies where currency_id=$id");
   if (!$result) {echo 'Ошибка базы данных!';exit();}
   $row = mysql_fetch_array($result);

   $currency_name = $row['currency_name'];
   $currency_descr = $row['currency_descr'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <span class="red">*</span><br><span class="grey">Название из 3-х символов в верхнем регистре</span></td>
      <td><input size="3" type="text" name="currency_name" maxlength="3" value="'.htmlspecialchars($currency_name).'"></td>
      <td id="w_td"><a href="/admin/editors/edit_lang_database.php?table=currencies&column=currency_name&rowkey=currency_id&row='.$id.'"><img src="/admin/images/globus.gif" alt="" border=""></a></td></tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="currency_descr" maxlength="255" value="'.htmlspecialchars($currency_descr).'"></td>
      <td id="w_td"><a href="/admin/editors/edit_lang_database.php?table=currencies&column=currency_descr&rowkey=currency_id&row='.$id.'"><img src="/admin/images/globus.gif" alt="" border=""></a></td></tr>
   </table><br><button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>