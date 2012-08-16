<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['delivery_name']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['delivery_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $delivery_id = (int)$_GET['id'];
   $delivery_name = trim($_POST['delivery_name']);
   $delivery_descr = trim($_POST['delivery_descr']);
   $price = (double)(($_POST['price']) ? $_POST['price'] : 0);

   $result = mysql_query("select * from shop_delivery where delivery_name = '".stripslashes($delivery_name)."' and delivery_id!=$delivery_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$delivery_id&message=duplicate"); exit();}

   //Обновляем...
   $result = mysql_query("update shop_delivery set delivery_name = '$delivery_name', delivery_descr = '$delivery_descr', price = $price where delivery_id = $delivery_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$delivery_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$delivery_id");
  exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $delivery_id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_delivery where delivery_id=$delivery_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);
   
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_delivery.php')) $tabs->add_tab('/admin/editors/edit_shop_delivery.php?id='.$delivery_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_shop_delivery_text.php')) $tabs->add_tab('/admin/editors/edit_shop_delivery_text.php?id='.$delivery_id.'&mode=brief', 'Краткое описание');
if ($user->check_user_rules('view','/admin/editors/edit_shop_delivery_text.php')) $tabs->add_tab('/admin/editors/edit_shop_delivery_text.php?id='.$delivery_id.'&mode=full', 'Подробное описание');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

  $shop_currency = 'руб.';
  $shop_currency = $user->get_cms_option('shop_currency');

 echo '<form action="?id='.$delivery_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="delivery_name" value="'.htmlspecialchars($row->delivery_name).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="delivery_descr" value="'.htmlspecialchars($row->delivery_descr).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Стоимость, '.$shop_currency.'</td>
      <td><input style="width:280px" type="text" name="price" value="'.$row->price.'" maxlength="255"></td>
    </tr>
    </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>