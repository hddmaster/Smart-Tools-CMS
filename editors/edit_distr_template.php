<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && $_GET['id']!='' &&
    isset($_POST['tpl_name']) &&
    isset($_POST['data']))
{
 if ($user->check_user_rules('edit'))
  {

  $tpl_id = (int)$_GET['id'];
  if (trim($_POST['tpl_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&message=formvalues");exit();}
  $tpl_name = $_POST['tpl_name'];
  $data = $_POST['data'];

  //проверка на повторы
  $res = mysql_query("select * from distr_templates where tpl_name='$tpl_name' and tpl_id != $tpl_id");
  if(mysql_num_rows($res) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&message=duplicate");exit();}

  $result = mysql_query("update distr_templates set tpl_name='$tpl_name', data='$data' where tpl_id=$tpl_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&message=db"); exit();}

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id");
  exit();
  } else $user->no_rules('edit');
}

// -----------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $tpl_id = (int)$_GET['id'];

   $result = mysql_query("select * from distr_templates where tpl_id=$tpl_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $tpl_name = $row['tpl_name'];
   $data = $row['data'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form action="?id='.$tpl_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="tpl_name" value="'.htmlspecialchars($tpl_name).'" maxlength="255"></td></tr>
   </table><br>';
   
$oFCKeditor = new FCKeditor('data') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $data;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '440' ;
$oFCKeditor->Create() ;

echo '<br /><br /><button type="SUBMIT">Сохранить</button>
      </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>