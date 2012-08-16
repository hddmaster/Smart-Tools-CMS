<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name']) &&
    isset($_POST['rules']) &&
    isset($_GET['path']) && $_GET['path'] != '')
 {
 if ($user->check_user_rules('edit'))
  {
   $path_clear = $_GET['path'];
   $path = $_SERVER['DOCUMENT_ROOT'].$_GET['path'];

   if (trim($_POST['name']) == '' || trim($_POST['rules']) == '')
    {Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=formvalues");exit();}

   $name = $_POST['name'];
   if ($path_clear != str_replace(basename($path_clear),$name,$path_clear))
    {
      if (@file_exists(str_replace(basename($path),$name,$path)))
        Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=duplicate");
    }
   $path_clear = str_replace(basename($path_clear),$name,$path_clear);
   if (!rename($path,str_replace(basename($path),$name,$path))) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=no_rules");
   $path = str_replace(basename($path),$name,$path);

   $rules = $_POST['rules'];
   if (!chmod($path,intval($rules,8))) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=norules");

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)); exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['path']) && $_GET['path']!='')
 {
 if ($user->check_user_rules('view'))
  {
   $path_clear = $_GET['path'];
   $path = $_SERVER['DOCUMENT_ROOT'].$_GET['path'];
   $name = basename($path_clear);
   $rules = substr(sprintf('%o', fileperms("$path")), -4);

function is_system($path_to_dir)
 {
  if ($path_to_dir)
   {
     $dir = explode('/',$path_to_dir);
     switch ($dir[1])
      {
        case "admin" :
        case "sql_dumps" :
        case "cache" :
        case "cache_image" : return true; break;
        default: return false; break;
      }
   }
 }
if (is_system($path_clear)) exit();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form action="?path='.urlencode($path_clear).'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название папки <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="name" value="'.htmlspecialchars($name).'" maxlength="255"></td></tr>
    <tr>
      <td>Права доступа <sup class="red">*</sup></td>
      <td><input style="width:30px" type="text" name="rules" value="'.$rules.'" maxlength="4" onKeyPress ="if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"/></td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
      </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>