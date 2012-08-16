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
  if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues");exit();}

  $element_name = ''; if (isset($_POST['element_name'])) $element_name = $_POST['element_name'];
  $parent_id = $_POST['parent_id'];

  $result = mysql_query("select * from forum where element_id=$element_id");
  $row = mysql_fetch_array($result);
  $img_path1 = $row['img_path1'];
  $img_path2 = $row['img_path2'];
  $img_path3 = $row['img_path3'];
  $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
  $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
  $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
  $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

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
     if (!use_file($img_path1,'forum','img_path1') || !use_file($img_path1,'forum','img_path2') || !use_file($img_path1,'forum','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path1);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name1);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name1);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name1 =  $name.'.jpg';

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
     if (!use_file($img_path2,'forum','img_path1') || !use_file($img_path2,'forum','img_path2') || !use_file($img_path2,'forum','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path2);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name2);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name2);
  $name = $name_clear;

  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name2 = $name.'.jpg';

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
     if (!use_file($img_path3,'forum','img_path1') || !use_file($img_path3,'forum','img_path2') || !use_file($img_path3,'forum','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path3);
   }
  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name3);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name3);
  $name = $name_clear;

  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name3 = $name.'.jpg';

   }
// picture3 end-----------------------------------------------------------------

  //уникальная запись! Обновляем содержимое...
  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
    $result = mysql_query("update forum set img_path1='"."/userfiles/forum_images/$user_file_name1"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
    $result = mysql_query("update forum set img_path2='"."/userfiles/forum_images/$user_file_name2"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
    $result = mysql_query("update forum set img_path3='"."/userfiles/forum_images/$user_file_name3"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }

   //Обновляем...
   $result = mysql_query("update forum set parent_id = $parent_id, element_name='$element_name', date='$date' where element_id = $element_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}


//копируем файлы, если необходимо
if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$user_file_name1";
     copy($_FILES['picture1']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture1']['type']));
    chmod($filename,0666);
   }
if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
    $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$user_file_name2";
    copy($_FILES['picture2']['tmp_name'], $filename);
    resize($filename, basename($_FILES['picture2']['type']));
    chmod($filename,0666);
   }
if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
    $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/forum_images/$user_file_name3";
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
     $result = mysql_query("select img_path1 from forum where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path1'],'forum','img_path1')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path1']);
     $result = mysql_query("update forum set img_path1='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '2')
   {
     $result = mysql_query("select img_path2 from forum where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path2'],'forum','img_path2')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path2']);
     $result = mysql_query("update forum set img_path2='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '3')
   {
     $result = mysql_query("select img_path3 from forum where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path3'],'forum','img_path3')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path3']);
     $result = mysql_query("update forum set img_path3='' where element_id=$element_id");
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
    $result = mysql_query("SELECT * FROM forum where parent_id = $parent_id and type = 1 order by order_id asc");
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
 $result = mysql_query("select
                        *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date
                        from forum
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $element_name = $row['element_name'];
   $file1 = $row['img_path1'];
   $file2 = $row['img_path2'];
   $file3 = $row['img_path3'];
   $parent_id = $row['parent_id'];

   $date = $row['date'];
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   $date = substr($date,0,10);

 if ($file1 || $file2 || $file3) echo '<p>';
 if ($file1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file1).'" border="0"> &nbsp;';
 if ($file2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file2).'" border="0"> &nbsp;';
 if ($file3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file3).'" border="0">';
 if ($file1 || $file2 || $file3) echo '</p>';

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form name="form" enctype="multipart/form-data" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" value="'.htmlspecialchars($element_name).'" maxlength="255"/></td></tr>
    <tr>
      <td>Фотографии</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture1"/></td><td>';
       if ($file1)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture2"/></td><td>';
       if ($file2)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=2&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture3"/></td><td>';
       if ($file3)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=3&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr></table></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup><br><span class="grey">Дата публикации</span></td>
      <td>';
?>
<TABLE cellSpacing=0 cellPadding=0 border=0>
 <TR>
  <TD>
    <SCRIPT language=JavaScript>
    LSCalendars["date"]=new LSCalendar();
    LSCalendars["date"].SetFormat("dd.mm.yyyy");
    LSCalendars["date"].SetDate("<?php echo $date;?>");
    </SCRIPT>
    <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
     <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value=<?php echo $date;?> name=date> </TD>
      <TD><button style="WIDTH: 34px; HEIGHT: 17px" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar.png"></button></TD>
     </TR>
     <TR>
      <TD colSpan=2>
        <DIV id=datePtr style="WIDTH: 1px; HEIGHT: 1px">
        <SPACER height="1" width="1" type="block"/>
        </DIV>
      </TD>
     </TR>
    </TABLE>

   </TD>
 </TR>
</TABLE>
<?php

echo'      </td></tr>
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
            if ($parent_id == 0) echo ' selected';
            echo '>---Корень форума---</option>
            '.show_select(0,'',$parent_id).'
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