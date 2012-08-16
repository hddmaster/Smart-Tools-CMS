<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['ip1']) &&
    isset($_POST['ip2']) &&
    isset($_POST['ip3']) &&
    isset($_POST['ip4']) &&
    isset($_POST['description']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['ip1'])=='' || trim($_POST['ip2'])=='' || trim($_POST['ip3'])=='' || trim($_POST['ip4'])=='' || trim($_POST['description'])=='') {Header("Location: /admin/stat_ips.php?message=formvalues"); exit();}

   $ip_id = (int)$_GET['id'];
   $ip = trim($_POST['ip1']).'.'.trim($_POST['ip2']).'.'.trim($_POST['ip3']).'.'.trim($_POST['ip4']);
   $description = $_POST['description'];
   $disabled_in_statistic = $_POST['disabled_in_statistic'];

   $result = mysql_query("select * from stat_ips_detect where ip='$ip' && ip_id != $ip_id");
   if(mysql_num_rows($result) > 0){Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=duplicate"); exit();}

   $result = mysql_query("update stat_ips_detect set ip = '$ip', description = '$description', disabled_in_statistic = $disabled_in_statistic where ip_id = $ip_id");

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
   $ip_id = (int)$_GET['id'];
   $result = mysql_query("select * from stat_ips_detect where ip_id=$ip_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   list($ip1, $ip2, $ip3, $ip4) = explode('.', $row['ip']);

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$row['ip_id'].'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>IP-адрес <sup class="red">*</sup></td>
      <td><input style="width:25px; text-align: center;" type="text" name="ip1" value="'.$ip1.'" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip2" value="'.$ip2.'" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip3" value="'.$ip3.'" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip4" value="'.$ip4.'" maxlength="3"></td>
    </tr>
    <tr>
      <td>Описание <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="description" value="'.htmlspecialchars($row['description']).'" maxlength="255"></td>
    </tr>
   <tr>
   <tr>
     <td>Учёт в статистике</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="disabled_in_statistic" value="0""'.(($row['disabled_in_statistic']) ? '' : ' checked').'></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="disabled_in_statistic" value="1"'.(($row['disabled_in_statistic']) ? ' checked' : '').'></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
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