<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']) &&
   isset($_POST['store_name']) &&
   isset($_POST['price']) &&
   isset($_GET['id']))
 {
  if ($user->check_user_rules('edit'))
  {
  if (trim($_POST['store_name'])=='' || trim($_POST['element_name'])=='' || trim($_POST['price'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $id = (int)$_GET['id'];
   $store_name = $_POST['store_name'];
   $producer_store_name = $_POST['producer_store_name'];
   $element_name = $_POST['element_name'];
   $price = $_POST['price'];

   $result = mysql_query("update shop_prices_tmp set store_name = '$store_name', producer_store_name = '$producer_store_name', element_name='$element_name', price=$price where price_id=$id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$id&message=db"); exit();}

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
   $id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_prices_tmp where price_id=$id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $store_name = $row['store_name'];
   $producer_store_name = $row['producer_store_name'];
   $element_name = $row['element_name'];
   $price = $row['price'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td>
      <input style="width:280px" type="text" name="element_name" value="'.$element_name.'" maxlength="255"></td></tr>
    <tr>
      <td>Цена</td>
      <td>
      <input style="width:280px" type="text" name="price" value="'.$price.'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td></tr>
    <tr>
      <td>Артикул производителя</td>
      <td><input style="width:280px" type="text" name="producer_store_name" value="'.$producer_store_name.'" maxlength="255"></td></tr>
    <tr>
      <td>Артикул</td>
      <td>
      <input style="width:280px" type="text" name="store_name" value="'.$store_name.'" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>