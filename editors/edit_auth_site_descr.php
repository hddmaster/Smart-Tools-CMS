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
   $user_id = (int)$_GET['id'];
   $mode = $_GET['mode'];

   if (isset($_POST['FCKeditor1']))
    {
      $text = $_POST['FCKeditor1'];
      $result = mysql_query("update auth_site set text='$text' where user_id=$user_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&mode=$mode&message=db"); exit();}
    }

   if (isset($_POST['FCKeditor2']))
    {
      $text_full = $_POST['FCKeditor2'];
      $result = mysql_query("update auth_site set text_full='$text_full' where user_id=$user_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&mode=$mode&message=db"); exit();}
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&mode=$mode"); exit();
  } else $user->no_rules('edit');
 }

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $user_id = (int)$_GET['id'];
   $result = mysql_query("select * from auth_site where user_id = $user_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $username = $row['username'];
   $text = $row['text'];
   $text_full = $row['text_full'];

 echo '<h2>'.htmlspecialchars($name).'</h2>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_auth_site_descr.php')) $tabs->add_tab('/admin/editors/edit_auth_site_descr.php?id='.$user_id.'&mode=brief', 'Краткое описание', ((preg_match('/brief/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
if ($user->check_user_rules('view','/admin/editors/edit_auth_site_descr.php')) $tabs->add_tab('/admin/editors/edit_auth_site_descr.php?id='.$user_id.'&mode=full', 'Подробное описание', ((preg_match('/full/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
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
echo '<form action="?id='.$user_id.'&mode=brief" method="post">';
$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $text;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '410' ;
$oFCKeditor->Create() ;
echo'<br /><br /><button type="SUBMIT">Сохранить</button>
     </form>';
 }//full
 if ($_GET['mode'] == 'full')
 {
echo '<form action="?id='.$user_id.'&mode=full" method="post">';
$oFCKeditor = new FCKeditor('FCKeditor2') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $text_full;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '410' ;
$oFCKeditor->Create() ;
echo'<br><br /><button type="SUBMIT">Сохранить</button>
     </form>';
 }//full
 }//mode

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>