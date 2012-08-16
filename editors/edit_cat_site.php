<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['site_name']) &&
   isset($_POST['site_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['site_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $site_id = (int)$_GET['id'];
   $site_name = $_POST['site_name'];
   $site_descr = $_POST['site_descr'];

   $result = mysql_query("select * from shop_cat_sites where site_name = '".stripslashes($site_name)."' and site_id!=$site_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$site_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_cat_sites set site_name='$site_name', site_descr='$site_descr' where site_id=$site_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$site_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$site_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $site_id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_cat_sites where site_id=$site_id");
   $row = mysql_fetch_array($result);

   $site_name = $row['site_name'];
   $site_descr = $row['site_descr'];
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$site_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="site_name" value="'.htmlspecialchars($site_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td>
      <input style="width:280px" type="text" name="site_descr" value="'.htmlspecialchars($site_descr).'" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>