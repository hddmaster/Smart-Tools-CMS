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
   isset($_POST['head_input']) &&
   isset($_POST['link']) &&
   isset($_GET['id']) &&
   isset($_POST['category']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['date'])=='' || trim($_POST['hour'])=='' || trim($_POST['minute'])=='' || trim($_POST['second'])=='' || trim($_POST['link'])=='' || trim($_POST['head_input'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $link_id = $_GET['id'];
   $cat_id = $_POST['category'];
   $head = $_POST['head_input'];
   $link = $_POST['link'];
   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   //Обновляем содержимое...
   $result = mysql_query("update links set date='$date', head='$head', link='$link', cat_id = $cat_id where link_id=$link_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$link_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$link_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $link_id = $_GET['id'];
   $result = mysql_query("select *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date from links where link_id=$link_id");
   $row = mysql_fetch_array($result);

   $cat_id = $row['cat_id'];
   $head = $row['head'];
   $link = $row['link'];
   $date = $row['date'];
   
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   
   $date = substr($date,0,10);
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="edit_link.php?id='.$link_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="head_input" value="'.htmlspecialchars($head).'"></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup><br><span class="grey">Дата публикации</span></td>
      <td>';
?>
    <script>
      LSCalendars["date"]=new LSCalendar();
      LSCalendars["date"].SetFormat("dd.mm.yyyy");
      LSCalendars["date"].SetDate("<?=$row['date']?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=$row['date']?>" name="date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td></tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Время публикации ссылки.<br>Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.$hour.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Текст ссылки <sup class="red">*</sup><br><span class="grey">HTML-код ссылки</span></td>
      <td><textarea style="width:280px" name="link" cols="52" rows="10">'.htmlspecialchars($link).'</textarea></td></tr>
    <tr>
      <td>Категория ссылки <sup class="red">*</sup></td>
      <td nowrap>';

    $result = mysql_query ("select * from links_cats order by order_id asc");
    if (@mysql_num_rows($result) > 0)
     {
       echo '<select name="category" style="width:280px;">
             <option value="">Выберите категорию...</option>';
       while ($row = mysql_fetch_object($result))
        {
          echo '<option value="'.$row->cat_id.'"';
          if ($row->cat_id == $cat_id) echo ' selected';
          echo '> '.htmlspecialchars($row->cat_name).'</option>';
        }
       echo '</select>';
     }

echo '      </td>
    </tr>

   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>