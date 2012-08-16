<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['courier_name']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['courier_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $courier_id = (int)$_GET['id'];
   $courier_name = trim($_POST['courier_name']);
   $courier_phone = trim($_POST['courier_phone']);
   $courier_icq = trim($_POST['courier_icq ']);
   $courier_address = trim($_POST['courier_address']);
   $courier_email = trim($_POST['courier_email']);
   $courier_descr = trim($_POST['courier_descr']);
   $price = $_POST['price'];

   $result = mysql_query("select * from shop_courier where courier_name = '".stripslashes($courier_name)."' and courier_id!=$courier_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$courier_id&message=duplicate"); exit();}

   //Обновляем...
   $result = mysql_query("update shop_couriers set courier_name = '$courier_name',
                                                   courier_phone = '$courier_phone',
                                                   courier_icq = '$courier_icq',
                                                   courier_address = '$courier_address',
                                                   courier_email = '$courier_email',
                                                   courier_descr = '$courier_descr'
                                                   where courier_id = $courier_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$courier_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$courier_id");
  exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $courier_id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_couriers where courier_id=$courier_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$courier_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Ф.И.О. <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="courier_name" value="'.htmlspecialchars($row->courier_name).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Телефон</td>
      <td>
       <input style="width:280px" type="text" name="courier_phone" value="'.htmlspecialchars($row->courier_phone).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>ICQ</td>
      <td>
       <input style="width:280px" type="text" name="courier_icq" value="'.htmlspecialchars($row->courier_icq).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Адрес</td>
      <td>
       <input style="width:280px" type="text" name="courier_address" value="'.htmlspecialchars($row->courier_address).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>e-mail</td>
      <td>
       <input style="width:280px" type="text" name="courier_email" value="'.htmlspecialchars($row->courier_email).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Дополнительная информация</td>
      <td><input style="width:280px" type="text" name="courier_descr" value="'.htmlspecialchars($row->courier_descr).'" maxlength="255"></td>
    </tr>
    </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>