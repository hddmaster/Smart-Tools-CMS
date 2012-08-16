<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']) &&
   isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {

        $element_id = (int)$_GET['id'];
        if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues");exit();}

        $element_name = mysql_real_escape_string(trim($_POST['element_name']));
        $parent_id = $_POST['parent_id'];
        $user_id = $_POST['user_id'];
        $tags = mysql_real_escape_string(trim($_POST['tags']));

        $result = mysql_query("select * from gallery where element_id=$element_id");
        $row = mysql_fetch_array($result);
        $img_path1 = $row['img_path1'];
        $img_path2 = $row['img_path2'];
        $img_path3 = $row['img_path3'];
        $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
        $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
        $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
        $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;
        $is_rating = $_POST['is_rating'];
        $is_commentation = $_POST['is_commentation'];

// picture1 --------------------------------------------------------------------
  if (isset($_FILES['picture1']['name']) &&
   is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
     $user_file_name1 = mb_strtolower($_FILES['picture1']['name'],'UTF-8');
     $type1 = basename($_FILES['picture1']['type']);

  switch ($type1)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path1 != '')
   {
     if (!use_file($img_path1,'gallery','img_path1') || !use_file($img_path1,'gallery','img_path2') || !use_file($img_path1,'gallery','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path1);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name1);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name1);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name1 =  $name.'.'.$ext;

   }
// picture1 end ----------------------------------------------------------------

// picture2 --------------------------------------------------------------------
  if (isset($_FILES['picture2']['name']) &&
   is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
     $user_file_name2 = mb_strtolower($_FILES['picture2']['name'],'UTF-8');
     $type2 = basename($_FILES['picture2']['type']);

  switch ($type2)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path2 != '')
   {
     if (!use_file($img_path2,'gallery','img_path1') || !use_file($img_path2,'gallery','img_path2') || !use_file($img_path2,'gallery','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path2);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name2);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name2);
  $name = $name_clear;

  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name2 = $name.'.'.$ext;

   }
// picture2 end-----------------------------------------------------------------

// picture3 --------------------------------------------------------------------
  if (isset($_FILES['picture3']['name']) &&
   is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
     $user_file_name3 = mb_strtolower($_FILES['picture3']['name'],'UTF-8');
     $type3 = basename($_FILES['picture3']['type']);

  switch ($type3)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path3 != '')
   {
     if (!use_file($img_path3,'gallery','img_path1') || !use_file($img_path3,'gallery','img_path2') || !use_file($img_path3,'gallery','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path3);
   }
  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name3);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name3);
  $name = $name_clear;

  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name3 = $name.'.'.$ext;

   }
// picture3 end-----------------------------------------------------------------

  //уникальная запись! Обновляем содержимое...
  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
    $result = mysql_query("update gallery set img_path1='"."/userfiles/gallery_images/$user_file_name1"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
    $result = mysql_query("update gallery set img_path2='"."/userfiles/gallery_images/$user_file_name2"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
    $result = mysql_query("update gallery set img_path3='"."/userfiles/gallery_images/$user_file_name3"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }

   //Обновляем...
    $result = mysql_query(" update
                            gallery
                            set
                            parent_id = $parent_id,
                            user_id = $user_id,
                            element_name='$element_name',
                            date='$date',
                            tags = '$tags'
                            where element_id = $element_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}


//копируем файлы, если необходимо
if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$user_file_name1";
     copy($_FILES['picture1']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture1']['type']));
    chmod($filename,0666);
   }
if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
    $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$user_file_name2";
    copy($_FILES['picture2']['tmp_name'], $filename);
    resize($filename, basename($_FILES['picture2']['type']));
    chmod($filename,0666);
   }
if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
    $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$user_file_name3";
    copy($_FILES['picture3']['tmp_name'], $filename);
    resize($filename, basename($_FILES['picture3']['type']));
    chmod($filename,0666);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $element_id = (int)$_GET['id'];
  $delete_img = $_GET['delete_img'];

  if ($delete_img == '1')
   {
     $result = mysql_query("select img_path1 from gallery where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path1'],'gallery','img_path1')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path1']);
     $result = mysql_query("update gallery set img_path1='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '2')
   {
     $result = mysql_query("select img_path2 from gallery where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path2'],'gallery','img_path2')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path2']);
     $result = mysql_query("update gallery set img_path2='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '3')
   {
     $result = mysql_query("select img_path3 from gallery where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path3'],'gallery','img_path3')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path3']);
     $result = mysql_query("update gallery set img_path3='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();
  } else $user->no_rules('delete');
 }
// -----------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 function show_select($parent_id = 0, $prefix = '',$parent_id_element)
  {
    global $options;
    $result = mysql_query("SELECT * FROM gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_element == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";

          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

    $element_id = (int)$_GET['id'];
    $result = mysql_query(" select
                            *,
                            date_format(date, '%d.%m.%Y (%H:%i:%s)') as date
                            from gallery
                            where element_id = $element_id");
    if (!$result) exit();
    $row = mysql_fetch_object($result);

    $date = $row->date;
    $hour = substr($date,12,2);
    $minute = substr($date,15,2);
    $second = substr($date,18,2);
    $date = substr($date,0,10);

    if ($row->img_path1 || $row->img_path2 ||$row->img_path3) echo '<p>';
    if ($row->img_path1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path1).'" border="0"> &nbsp;';
    if ($row->img_path2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path2).'" border="0"> &nbsp;';
    if ($row->img_path3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path3).'" border="0">';
    if ($row->img_path1 || $row->img_path2 || $row->img_path3) echo '</p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_gallery.php')) $tabs->add_tab('/admin/editors/edit_gallery.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_gallery_on_map.php')) $tabs->add_tab('/admin/editors/edit_gallery_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form name="form" enctype="multipart/form-data" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" value="'.htmlspecialchars($row->element_name).'" maxlength="255"/></td></tr>
    <tr>
      <td>Фотография<br /><span class="grey">Форматы: JPEG, PNG, GIF, BMP</span></td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture1"/></td><td>';
       if ($row->img_path1)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture2"/></td><td>';
       if ($row->img_path2)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=2&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture3"/></td><td>';
       if ($row->img_path3)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=3&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr></table></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.$date.'"></td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.$hour.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
     <tr>
      <td>Расположение <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0"';
            if ($row->parent_id == 0) echo ' selected';
            echo '>---Корень галереи---</option>
            '.show_select(0,'',$row->parent_id).'
          </select>
      </td>
    </tr>
    <tr>
      <td>Пользователь сайта</td>
      <td><select name="user_id" style="width:280px;">
            <option value="0">---НЕТ---</option>';
      $res = mysql_query("select * from auth_site where type = 0 order by username asc");
      if(mysql_num_rows($res) > 0)
       {
         while($r = mysql_fetch_object($res))
            echo '<option value="'.$r->user_id.'" '.(($r->user_id == $row->user_id) ? 'selected' : '').'>'.htmlspecialchars($r->username).'</option>';
       }
      echo '</select>
      </td>
    </tr>
    
    <tr>
      <td>Участвуйет в рейтинге</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
    </tr>
    <tr>
      <td>Комментирование</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
    </tr>
    <tr>
        <td>Тэги</td>
        <td><input style="width:280px" type="text" name="tags" value="'.htmlspecialchars($row->tags).'" maxlength="255"></td>
    </tr>    
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>