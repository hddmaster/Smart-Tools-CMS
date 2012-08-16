<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['unit_name']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
  $unit_id = (int)$_GET['id'];
  if (trim($_POST['unit_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$unit_id&message=formvalues");exit();}

  $unit_name = trim($_POST['unit_name']);
  $unit_descr = trim($_POST['unit_descr']);
  $parent_id = 0;

  if ($unit_name !== '')
   {
     $result = mysql_query("select * from shop_units_of_measure where unit_name = '".stripslashes($unique_name)."' and unit_id != $unit_id");
     if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}
   }
   
   //Обновляем...
   $result = mysql_query("update shop_units_of_measure set
                          unit_name = '$unit_name',
                          unit_descr = '$unit_descr'
                          where unit_id = $unit_id") or die(mysql_error());
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$unit_id&message=db"); exit();}

   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();
   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$unit_id"); exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

 $unit_id = (int)$_GET['id'];
 $result = mysql_query("select * from shop_units_of_measure where unit_id=$unit_id");
 if (!$result) exit();
 $row = mysql_fetch_array($result);
 $parent_id = $row['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

   echo '<form name="form" action="?id='.$unit_id.'" method="post">
      <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Обозначение <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="unit_name" value="'.htmlspecialchars($row['unit_name']).'" maxlength="255"></td>
      <td id="w_td"><a href="/admin/editors/edit_lang_database.php?table=shop_units_of_measure&column=unit_name&rowkey=unit_id&row='.$unit_id.'"><img src="/admin/images/globus.gif" alt="" border=""></a></td></tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="unit_descr" value="'.htmlspecialchars($row['unit_descr']).'" maxlength="255"></td>
      <td id="w_td"><a href="/admin/editors/edit_lang_database.php?table=shop_units_of_measure&column=unit_descr&rowkey=unit_id&row='.$unit_id.'"><img src="/admin/images/globus.gif" alt="" border=""></a></td></tr>
  </table><br>
   <button type="SUBMIT">Сохранить</button><div>&nbsp;</div><span class="grey"><sup>1</sup> Производится проверка на уникальность среди обозначений единиц измерений</span>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>