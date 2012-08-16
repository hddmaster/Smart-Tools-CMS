<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) &&
    isset($_POST['head']) &&
    isset($_POST['FCKeditor1']))
 {
 if ($user->check_user_rules('edit'))
  {
  $msg_id = (int)$_GET['id'];
  if (trim($_POST['head'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id&message=formvalues");exit();}
  $head = $_POST['head'];
  $text = trim($_POST['FCKeditor1']);

//изменяем содержимое...
  $result = mysql_query("update distr_msg set head='$head', text='$text' where msg_id=$msg_id");
  if(!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id&message=db");exit();}

if (isset($_FILES['distr_file']['name']) && is_uploaded_file($_FILES['distr_file']['tmp_name']))
 {
   $result = mysql_query("select file_path from distr_msg where msg_id=$msg_id");
   if(!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id&message=db");exit();}
   $row = mysql_fetch_array($result);
   $old_filename = $row['file_path'];

   $user_file_name = mb_strtolower($_FILES['distr_file']['name'],'UTF-8');

   $distribution_files_path = $user->get_cms_option('distribution_files_path');
   $filename = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$msg_id.'/'.$user_file_name;
   $dirname = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$msg_id;
   mkdir($dirname,  0777);
   //if (file_exists($filename)) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id&message=fileexist"); exit();} //если с таким именем уже есть
   if($old_filename !== '') unlink ($dirname.'/'.$old_filename); //удаляем старый файл, если он есть
   copy($_FILES['distr_file']['tmp_name'], $filename);
   chmod($filename,0666);
   mysql_query("update distr_msg set file_path='$user_file_name' where msg_id=$msg_id");
 }

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id");
  exit();
  } else $user->no_rules('edit');
 }

//удаление вложенного файла
if (isset($_GET['del_attach_file']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
   $msg_id = (int)$_GET['id'];
   $result = mysql_query("select file_path from distr_msg where msg_id=$msg_id");
   $row = mysql_fetch_array($result);
   $distribution_files_path = $user->get_cms_option('distribution_files_path');
   $filename = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$msg_id.'/'.$row['file_path'];
   $dirname = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$msg_id;
   @unlink($filename);
   @rmdir($dirname);
   mysql_query("update distr_msg set file_path='' where msg_id=$msg_id");

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$msg_id"); exit();
  } else $user->no_rules('delete');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $msg_id = (int)$_GET['id'];
   $result = mysql_query("select * from distr_msg where msg_id=$msg_id");
   $row = mysql_fetch_array($result);

   $head = $row['head'];
   $text = $row['text'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

   $distribution_files_path = $user->get_cms_option('distribution_files_path');
   $filename = $distribution_files_path.$msg_id.'/'.$row['file_path'];

 echo '<form enctype="multipart/form-data" action="?id='.$msg_id.'" method="post">
   <table width="100%" cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок <sup class="red">*</sup></td>
      <td><input type="text" name="head" value="'.htmlspecialchars($head).'" style="width:100%" maxlength="255"></td></tr>
    <tr>
      <td>Файл<br><span class="grey">Вложенный в сообщение файл<br>(будет храниться на сервере)</span></td>
      <td><input style="width:280px" type="file" name="distr_file">';
      if ($row['file_path'])
       {
         echo '<div style="padding:6px;"><a href="http://'.$_SERVER['HTTP_HOST'].$filename.'">'.basename(htmlspecialchars($filename)).'</a> &nbsp;
               <a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='edit_distr_msg.php?id=$msg_id&del_attach_file=1';}";
           echo '">удалить вложенный файл</a>
              </div>';
       }
      echo '</td>
    </tr>
   </table><br>';

$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'email' ;
$oFCKeditor->Value = $text;
$oFCKeditor->Width  = '100%' ;
$oFCKeditor->Height = '350' ;
$oFCKeditor->Create() ;
echo'<div>&nbsp;</div><button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>