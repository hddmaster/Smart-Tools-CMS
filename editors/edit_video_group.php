<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {

  $element_id = (int)$_GET['id'];
  if (trim($_POST['element_name'])=='' || trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues");exit();}

  $element_name = $_POST['element_name'];
  $parent_id = $_POST['parent_id'];

  if ($element_id == $parent_id) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=group_error");exit();}

  $result = mysql_query("select * from video where element_id=$element_id");
  $row = mysql_fetch_array($result);
  $img_path = $row['img_path'];
  $old_parent_id =$row['parent_id'];
  $order_id = $row['order_id'];
  
  $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
  $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
  $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
  $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

// image --------------------------------------------------------------------
  if (isset($_FILES['image']['name']) &&
   is_uploaded_file($_FILES['image']['tmp_name']))
   {
     $user_file_name = mb_strtolower($_FILES['image']['name'],'UTF-8');
     $type = basename($_FILES['image']['type']);

  switch ($type)
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
  if ($img_path != '')
   {
     if (!use_file($img_path,'video','img_path'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name =  $name.'.'.$ext;

   }
// image end ----------------------------------------------------------------


  //уникальная запись! Обновляем содержимое...
  if (isset($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name']))
   {
    $result = mysql_query("update video set img_path='"."/userfiles/videos/$user_file_name"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }

   //Обновляем...
   if ($parent_id !== $old_parent_id) $order_id = 0;
   $result = mysql_query("update video set parent_id=$parent_id, element_name='$element_name', date='$date', order_id = $order_id where element_id=$element_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}

   if ($parent_id !== $old_parent_id)
    {
   // перенумеровываем
   $result = mysql_query("select * from video where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update video set order_id=$i where element_id = $id");
         $i++;
       }
    }
    }

//копируем файлы, если необходимо
if (isset($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$user_file_name";
     copy($_FILES['image']['tmp_name'], $filename);
     resize($filename, basename($_FILES['image']['type']));
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

  if ($delete_img == 'true')
   {
     $result = mysql_query("select img_path from video where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path'],'video','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);
     $result = mysql_query("update video set img_path='' where element_id=$element_id");
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
 function show_select($parent_id = 0, $prefix = '', $parent_id_element, $element_id)
  {
    global $options;
    $result = mysql_query("SELECT * FROM video where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if ($element_id !== $row['element_id'])
           { 
             $options .= '<option value="'.$row['element_id'].'"';
             if ($parent_id_element == $row['element_id']) $options .= ' selected';
             $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
           }
          if ($element_id !== $row['element_id']) show_select($row['element_id'], $prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_element, $element_id);
        }
    }
    return $options;
  }

 $element_id = (int)$_GET['id'];
 $result = mysql_query("select
                        *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date
                        from video
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $element_name = $row['element_name'];
   $image = $row['img_path'];
   $parent_id = $row['parent_id'];

   $date = $row['date'];
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   $date = substr($date,0,10);

 if ($image) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($image).'" border="0"></p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_video_group.php')) $tabs->add_tab('/admin/editors/edit_video_group.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_video_group_on_map.php')) $tabs->add_tab('/admin/editors/edit_video_group_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('group_error', 'Группа не может ссылаться на себя');
   $message->get_message($_GET['message']);
 }

 echo '<form name="form" enctype="multipart/form-data" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_name" value="'.htmlspecialchars($element_name).'" maxlength="255"/></td></tr>
    <tr>
      <td>Фотография<br /><span class="grey">JPEG, PNG, GIF, BMP</span></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
            <td><input style="width:280px" type="file" name="image"/></td>';
            if ($image)
             {
               echo '<td><a href="';
               echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=true&id=$element_id';}";
               echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>';
             }
    echo '</td></tr></table>
      </td>
    </tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td>';
?>
    <script>
      LSCalendars["date"]=new LSCalendar();
      LSCalendars["date"].SetFormat("dd.mm.yyyy");
      LSCalendars["date"].SetDate("<?=$date?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=$date?>" name="date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>
<?
    echo'</td></tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.$hour.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Расположение группы <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0"'; if ($parent_id == 0) echo ' selected'; echo '>---Корень галереи---</option>
            '.show_select(0,'',$parent_id, $element_id).'
          </select>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>