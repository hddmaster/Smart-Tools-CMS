<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && isset($_GET['mode']) && (isset($_POST['FCKeditor1']) || isset($_POST['FCKeditor2'])))
 {
 if ($user->check_user_rules('edit'))
  {
   $file_id = (int)$_GET['id'];
   $mode = $_GET['mode'];

   if (isset($_POST['FCKeditor1']))
    {
      $text = $_POST['FCKeditor1'];
      $result = mysql_query("update files set text='$text' where file_id=$file_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$file_id&mode=$mode&message=db"); exit();}
    }

   if (isset($_POST['FCKeditor2']))
    {
      $text_full = $_POST['FCKeditor2'];
      $result = mysql_query("update files set text_full='$text_full' where file_id=$file_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$file_id&mode=$mode&message=db"); exit();}
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

 $_SESSION['smart_tools_refresh'] = 'enable';
 Header("Location: ".$_SERVER['PHP_SELF']."?id=$file_id&mode=$mode"); exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $file_id = (int)$_GET['id'];

   $result = mysql_query("select * from files where file_id=$file_id");
   $row = mysql_fetch_array($result);

   $head = $row['head'];
   $text = $row['text'];
   $text_full = $row['text_full'];

   echo '<h2 class="nomargins">'.htmlspecialchars($head).'</h2><div>&nbsp;</div>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_file_m.php')) $tabs->add_tab('/admin/editors/edit_file_m.php?id='.$file_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_file_text.php')) $tabs->add_tab('/admin/editors/edit_file_text.php?id='.$file_id.'&mode=brief', 'Краткое описание');
if ($user->check_user_rules('view','/admin/editors/edit_file_text.php')) $tabs->add_tab('/admin/editors/edit_file_text.php?id='.$file_id.'&mode=full', 'Подробное описание');
if ($user->check_user_rules('view','/admin/editors/edit_file_users.php')) $tabs->add_tab('/admin/editors/edit_file_users.php?id='.$file_id, 'Область видимости');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

if (isset($_GET['mode']) && $_GET['mode']!=='')
 {
 if ($_GET['mode'] == 'brief')
 {
echo '<form action="?id='.$file_id.'&mode=brief" method="post">';
$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $text;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '380' ;
$oFCKeditor->Create() ;
echo '<br /><br /><button type="SUBMIT">Сохранить</button>
      </form>';
 }//brief
 if ($_GET['mode'] == 'full')
 {
echo '<form action="?id='.$file_id.'&mode=full" method="post">';
$oFCKeditor = new FCKeditor('FCKeditor2') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $text_full;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '380' ;
$oFCKeditor->Create() ;
echo '<br /><br /><button type="SUBMIT">Сохранить</button>
      </form>';
 }//full
 }//set mode
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>