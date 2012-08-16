<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['place_name']) &&
   isset($_POST['place_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['place_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $place_id = (int)$_GET['id'];
   $place_name = $_POST['place_name'];
   $place_descr = $_POST['place_descr'];

   $result = mysql_query("select * from shop_places where place_name = '".stripslashes($place_name)."' and place_id!=$place_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$place_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_places set place_name='$place_name', place_descr='$place_descr' where place_id=$place_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$place_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$place_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $place_id = (int)$_GET['id'];
 $result = mysql_query("select * from shop_places where place_id = $place_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $place_name = $row['place_name'];
   $place_descr = $row['place_descr'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$place_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="place_name" value="'.htmlspecialchars($place_name).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="place_descr" value="'.htmlspecialchars($place_descr).'" maxlength="255">
      </td>
    </tr></table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>