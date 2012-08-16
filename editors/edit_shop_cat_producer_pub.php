<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['date']) &&
   isset($_POST['hour']) &&
   isset($_POST['minute']) &&
   isset($_POST['second']) &&
   isset($_POST['head']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['date'])=='' || trim($_POST['hour'])=='' || trim($_POST['minute'])=='' || trim($_POST['second'])=='' || trim($_POST['producer_id']) == '')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $pub_id = (int)$_GET['id'];
   $producer_id = $_POST['producer_id'];
   $head = $_POST['head'];
   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   $result = mysql_query("select * from shop_cat_producer_publications where pub_id=$pub_id");
   $row = mysql_fetch_array($result);
   $img_path = $row['img_path'];

//если есть картинка, проверяем её тип
  if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
   {
     $user_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
     $type = basename($_FILES['picture']['type']);

  switch ($type)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path != '')
   {
     if (!use_file($img_path,'shop_cat_publications','img_path'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name =  $name.'.jpg';

   }

  //проверка на повторы отсутствует, т.к. новости часто могут дублироваться...

  //Обновляем содержимое...

  if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name']))
   {
    $result = mysql_query("update shop_cat_producer_publications set img_path='/userfiles/pub_images/$user_file_name' where pub_id=$pub_id");
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  $result = mysql_query("update shop_cat_producer_publications set date='$date', head='$head', producer_id = $producer_id where pub_id=$pub_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id&message=db"); exit();}

  if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$user_file_name";
     copy($_FILES['picture']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture']['type']));
     chmod($filename,0666);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id");
  exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $pub_id = (int)$_GET['id'];
  $result = mysql_query("select img_path from shop_cat_publications where pub_id=$pub_id");
  $row = mysql_fetch_array($result);
  if (!use_file($row['img_path'],'shop_cat_publications','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);

  $result = mysql_query("update shop_cat_publications set img_path='' where pub_id=$pub_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  $_SESSION['smart_tools_refresh'] = 'enable';

  } else $user->no_rules('delete');
 }

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id, $selected_element_id)
{
  $objResponse = new xajaxResponse();
  $select_elements = '<select name="element_id" style="width:280px;" size="10">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
         $select_elements .= '<option value="'.$row['element_id'].'" '.(($selected_element_id == $row['element_id']) ? 'selected' : '').'>'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].')</option>';
   }
  else $select_elements .= '<option value="">Нет товаров</option>';

  $select_elements .= '</select>';

	$objResponse->assign("elements","innerHTML",$select_elements);
	return $objResponse;
}
$xajax->registerFunction("show_elements");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

   $pub_id = (int)$_GET['id'];
   $result = mysql_query("select *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date from shop_cat_producer_publications where pub_id=$pub_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $producer_id = $row['producer_id'];
   $head = $row['head'];
   $date = $row['date'];
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   $date = substr($date,0,10);
   $file = $row['img_path'];
   
   if($file) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file).'" border="0"></p>';

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form enctype="multipart/form-data" action="?id='.$pub_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок</td>
      <td>
      <input style="width:280px" type="text" name="head" value="'.htmlspecialchars($head).'" maxlength="255"></td></tr>
    <tr>
      <td>Фотография</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture"></td><td>';
       if ($file)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$pub_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
 echo '</td></tr></table></td></tr>
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
echo'      </td></tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.$hour.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Производитель <sup class="red">*</sup></td>
      <td><select name="producer_id" style="width:280px;">
          <option value="0">---НЕТ---</option>';
     $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
         {
           echo '<option value="'.$r['producer_id'].'"';
           if ($producer_id == $r['producer_id']) echo ' selected';
           echo '>'.htmlspecialchars($r['producer_name']);
           if ($r['producer_descr']) echo '&nbsp; ('.htmlspecialchars($r['producer_descr']).')';
           echo '</option>';
         }
      }
   echo '</td></tr></table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  if($element_id) echo '<script>setTimeout("xajax_show_elements('.$parent_id.', '.$element_id.');", 2000);</script>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>