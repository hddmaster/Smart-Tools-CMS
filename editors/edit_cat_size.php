<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['size_name']) &&
   isset($_POST['size_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['size_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $size_id = (int)$_GET['id'];
   $size_name = $_POST['size_name'];
   $size_descr = $_POST['size_descr'];

   $result = mysql_query("select * from shop_cat_sizes where size_name = '".stripslashes($size_name)."' and size_id!=$size_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=duplicate"); exit();}

  $result = mysql_query("select * from shop_cat_sizes where size_id=$size_id");
  $row = mysql_fetch_array($result);
  $size_picture = $row['size_picture'];

  if (isset($_FILES['size_picture']['name']) &&
   is_uploaded_file($_FILES['size_picture']['tmp_name']))
   {
     $user_file_name1 = mb_strtolower($_FILES['size_picture']['name'],'UTF-8');
     $type1 = basename($_FILES['size_picture']['type']);

  switch ($type1)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($size_picture !== '')
   {
     if (!use_file($size_picture,'shop_cat_sizes','size_picture'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$size_picture);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name1);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name1);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_sizes/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name1 =  $name.'.jpg';

   }


   //Обновляем содержимое...
   if (isset($_FILES['size_picture']['name']) &&
   is_uploaded_file($_FILES['size_picture']['tmp_name']))
    {
      $result = mysql_query("update shop_cat_sizes set size_name='$size_name', size_descr='$size_descr', size_picture='/userfiles/shop_cat_sizes/$user_file_name1' where size_id=$size_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=db"); exit();}
      $cache = new Cache;
      $cache->clear_all_image_cache();
    }
   else
    {
      $result = mysql_query("update shop_cat_sizes set size_name='$size_name', size_descr='$size_descr' where size_id=$size_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id&message=db"); exit();}
    }

   if (isset($_FILES['size_picture']['name']) &&
   is_uploaded_file($_FILES['size_picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_sizes/$user_file_name1";
     copy($_FILES['size_picture']['tmp_name'], $filename);
     resize($filename, basename($_FILES['size_picture']['type']));
     chmod($filename,0666);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $size_id = (int)$_GET['id'];
  $delete_img = $_GET['delete_img'];

  if ($delete_img == '1')
   {
     $result = mysql_query("select size_picture from shop_cat_sizes where size_id=$size_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['size_picture'],'shop_cat_sizes','size_picture')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['size_picture']);
     $result = mysql_query("update shop_cat_sizes set size_picture = '' where size_id=$size_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
   }

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$size_id"); exit();
  } else $user->no_rules('delete');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $size_id = (int)$_GET['id'];
   $result = mysql_query("select * from shop_cat_sizes where size_id=$size_id");
   $row = mysql_fetch_array($result);

   $size_name = $row['size_name'];
   $size_descr = $row['size_descr'];
   $size_picture = $row['size_picture'];

 if ($size_picture) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($size_picture).'" border="0"></p>';

   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form enctype="multipart/form-data" action="?id='.$size_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="size_name" value="'.htmlspecialchars($size_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td>
      <input style="width:280px" type="text" name="size_descr" value="'.htmlspecialchars($size_descr).'" maxlength="255"></td></tr>
    <tr>
      <td>Фотография</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="size_picture"/></td><td>';
       if ($size_picture)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$size_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr></table></td></tr>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>