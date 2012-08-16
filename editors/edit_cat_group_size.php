<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['size_name']) &&
   isset($_POST['size_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['size_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $size_id = (int)$_GET['id'];
   $size_name = $_POST['size_name'];
   $size_descr = $_POST['size_descr'];

   //$result = mysql_query("select * from shop_cat_group_sizes where size_name = '".stripslashes($size_name)."' and size_id!=$size_id");
   //if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_cat_group_sizes set size_name='$size_name', size_descr='$size_descr' where size_id=$size_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $size_id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_cat_group_sizes where size_id=$size_id");
   $row = mysql_fetch_array($result);

   $size_name = $row['size_name'];
   $size_descr = $row['size_descr'];
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$size_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="size_name" value="'.htmlspecialchars($size_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td>
      <input style="width:280px" type="text" name="size_descr" value="'.htmlspecialchars($size_descr).'" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>