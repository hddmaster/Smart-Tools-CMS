<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['option_name']) &&
   isset($_POST['option_sname']) &&
   isset($_POST['option_descr']) &&
   isset($_POST['option_type']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['option_name'])=='' ||
       trim($_POST['option_sname'])=='' ||
       trim($_POST['option_type'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $option_id = (int)$_GET['id'];
   $option_name = $_POST['option_name'];
   $option_sname = $_POST['option_sname'];
   $option_descr = $_POST['option_descr'];
   $option_type = $_POST['option_type'];

   $result = mysql_query("select * from cms_options where option_sname = '".stripslashes($option_sname)."' and option_id!=$option_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update cms_options set option_name='$option_name', option_sname='$option_sname', option_descr='$option_descr', option_type=$option_type where option_id=$option_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=db"); exit();}

  //Очистка кэша
  $cache = new Cache; $cache->clear_all_cache();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $option_id = (int)$_GET['id'];
 $result = mysql_query("select * from cms_options where option_id = $option_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $payment_id = $row['payment_id'];
   $option_name = $row['option_name'];
   $option_sname = $row['option_sname'];
   $option_descr = $row['option_descr'];
   $option_type = $row['option_type'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$option_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input type="text" style="width:280px;" name="option_name" value="'.htmlspecialchars($option_name).'"></td>
    </tr>
    <tr>
      <td>Короткое название <sup class="red">*</sup><br/><span class="grey">Название уникальной переменной<br/> для быстрого доступа к значению</span></td>
      <td><input type="text" style="width:280px;" name="option_sname" value="'.htmlspecialchars($option_sname).'"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input type="text" style="width:280px;" name="option_descr" value="'.htmlspecialchars($option_descr).'"></td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <select style="width:280px;" name="option_type">
          <option value="">Выберите тип параметра...</option>
          <option value="1"'; if ($option_type == 1) echo ' selected'; echo '>INT (целое число)</option>
          <option value="2"'; if ($option_type == 2) echo ' selected'; echo '>DOUBLE (число с плавающей точкой)</option>
          <option value="3"'; if ($option_type == 3) echo ' selected'; echo '>BOOLEAN (да/нет)</option>
          <option value="4"'; if ($option_type == 4) echo ' selected'; echo '>CHAR (строка)</option>
          <option value="5"'; if ($option_type == 5) echo ' selected'; echo '>TEXT (текст)</option>
          <option value="6"'; if ($option_type == 6) echo ' selected'; echo '>ARRAY (спаравочник)</option>
        </select>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>