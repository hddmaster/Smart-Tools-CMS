<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['setting_value']) &&
   isset($_POST['option_type']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['option_type'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $option_id = intval($_GET['id']);
   $setting_value = $_POST['setting_value'];
   $option_type = $_POST['option_type'];

   switch ($option_type)
    {
      case 1: mysql_query("update cms_options set setting_int_value = $setting_value,
                                                   setting_double_value = 0,
                                                   setting_boolean_value = 0,
                                                   setting_char_value = '',
                                                   setting_text_value = ''
                                                   where option_id = $option_id") or die(mysql_error()); break;
      case 2: mysql_query("update cms_options set setting_int_value = 0,
                                                   setting_double_value = $setting_value,
                                                   setting_boolean_value = 0,
                                                   setting_char_value = '',
                                                   setting_text_value = ''
                                                   where option_id = $option_id") or die(mysql_error()); break;
      case 3: mysql_query("update cms_options set setting_int_value = 0,
                                                   setting_double_value = 0,
                                                   setting_boolean_value = $setting_value,
                                                   setting_char_value = '',
                                                   setting_text_value = ''
                                                   where option_id = $option_id") or die(mysql_error()); break;
      case 4: mysql_query("update cms_options set setting_int_value = 0,
                                                   setting_double_value = 0,
                                                   setting_boolean_value = 0,
                                                   setting_char_value = '$setting_value',
                                                   setting_text_value = ''
                                                   where option_id = $option_id") or die(mysql_error()); break;
      case 5: mysql_query("update cms_options set setting_int_value = 0,
                                                   setting_double_value = 0,
                                                   setting_boolean_value = 0,
                                                   setting_char_value = '',
                                                   setting_text_value = '$setting_value'
                                                   where option_id = $option_id") or die(mysql_error()); break;
    }

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
   $option_name = $row['option_name'];
   $option_sname = $row['option_sname'];
   $option_type = intval($row['option_type']);

   switch ($option_type)
    {
      case 1: $value = $row['setting_int_value']; break;
      case 2: $value = $row['setting_double_value']; break;
      case 3: $value = $row['setting_boolean_value']; break;
      case 4: $value = $row['setting_char_value']; break;
      case 5: $value = $row['setting_text_value']; break;
      case 6: $value = $row['setting_text_value']; break;
    }

 echo '<h2>'.htmlspecialchars($option_name).' ('.htmlspecialchars($option_sname).')</h2>';

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

if ($option_type < 6)
{
 echo '<form action="?id='.$option_id.'" method="post">
       <input type="hidden" name="option_type" value="'.$option_type.'">';

 if ($option_type !== 5)
 {
 echo '<table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Значение</td>
      <td>';

 if ($option_type == 1 || $option_type == 2 || $option_type == 4)
 echo '<input type="text" style="width:280px;" name="setting_value" value="'.htmlspecialchars($value).'">';

 if ($option_type == 3)
  {
    echo '<table cellspacing="0" cellpadding="0">
          <tr>
            <td><input type="radio" name="setting_value" value="1"'; if ($value == 1) echo ' checked'; echo '></td>
            <td>Да &nbsp;</td>
            <td><input type="radio" name="setting_value" value="0"'; if ($value == 0) echo ' checked'; echo '></td>
            <td>Нет &nbsp;</td>
          </tr>
          </table>';
  }

 echo '</td>
    </tr>
   </table>';
 }
if ($option_type == 5)
 {
   $oFCKeditor = new FCKeditor('setting_value') ;
   $oFCKeditor->BasePath = '/admin/fckeditor/';
   $oFCKeditor->ToolbarSet = 'Main' ;
   $oFCKeditor->Value = $value;
   $oFCKeditor->Width  = '100%' ;
   $oFCKeditor->Height = '450' ;
   $oFCKeditor->Create() ;
 }
echo '<br /><br />
   <button type="SUBMIT">Сохранить</button>
  </form>';
}
if ($option_type == 6)
 {
   if ($value) var_dump(unserialize($value));
 }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>