<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && $_GET['id']!='' &&
    isset($_POST['template_name']) &&
    isset($_POST['template_description']) &&
    isset($_POST['html_data']))
{
 if ($user->check_user_rules('edit'))
  {
  $template_id = (int)$_GET['id'];
  if (trim($_POST['template_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&message=formvalues");exit();}
  $template_name = $_POST['template_name'];
  $template_description = $_POST['template_description'];
  $data = $_POST['html_data'];

  //проверка на повторы
  $res = @mysql_query("select * from templates where template_name='$template_name' and template_id != $template_id");
  if(mysql_num_rows($res) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$template_id&message=duplicate");exit();}

  $result = mysql_query("update templates set template_name='$template_name', template_description='$template_description', data='$data' where template_id=$template_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$template_id&message=db"); exit();}

  //Обновление страниц сайта с этим модулем
  $page = new Site_generate;
  $page->site_generate_by_array($page->find_pages('template', $template_id));

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$template_id"); exit();
  } else $user->no_rules('edit');
}

// -----------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $template_id = (int)$_GET['id'];

   $result = mysql_query("select * from templates where template_id=$template_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $template_name = $row['template_name'];
   $template_description = $row['template_description'];
   $data = $row['data'];
 
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

//------------------------------------------------------------------------------
echo '<form action="?id='.$template_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="template_name" value="'.htmlspecialchars($template_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="template_description" value="'.htmlspecialchars($template_description).'" maxlength="255"></input></td></tr>
   </table><br>
      <textarea wrap="off" style="font-family:Courier New;font-size:10pt;width:100%;height:415px" name="html_data">'.htmlspecialchars($data).'</textarea>
      <br><br><button type="SUBMIT">Сохранить</button>
      </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>