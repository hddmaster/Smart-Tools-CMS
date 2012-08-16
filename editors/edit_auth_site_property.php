<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) &&
    isset($_POST['text']) &&
    isset($_POST['text_full']))
 {
 if ($user->check_user_rules('edit'))
  {
    $property_id = (int)$_GET['id'];
    
    $property_name = trim($_POST['property_name']);
    $text = trim($_POST['text']);
    $text_full = trim($_POST['text_full']);
    
    $result = mysql_query("update auth_site_properties set property_name = '$property_name', text = '$text', text_full = '$text_full' where property_id=$property_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$property_id&message=db"); exit();}

    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();

    Header("Location: ".$_SERVER['PHP_SELF']."?id=$property_id"); exit();
  } else $user->no_rules('edit');
 }

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $property_id = (int)$_GET['id'];
   $result = mysql_query("select * from auth_site_properties where property_id = $property_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form action="?id='.$property_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form" width="100%">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td width="100%"><input style="width:280px" type="text" name="property_name" value="'.htmlspecialchars($row->property_name).'" maxlength="255"></td>
    </tr>
   </table><h3>Краткое описание</h3>';

$oFCKeditor = new FCKeditor('text') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $row->text;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '180' ;
$oFCKeditor->Create();

echo '<h3>Подробное описание</h3>';

$oFCKeditor = new FCKeditor('text_full') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $row->text_full;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '180' ;
$oFCKeditor->Create();

echo'<div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>