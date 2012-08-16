<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name']) &&
   isset($_POST['text']) &&
   isset($_GET['id']) &&
   isset($_POST['date']) &&
   isset($_POST['hour']) &&
   isset($_POST['minute']) &&
   isset($_POST['second']))
 {

 if ($user->check_user_rules('edit'))
  {
   $comment_id = (int)$_GET['id'];
   if (trim($_POST['name'])=='' || trim($_POST['text'])=='' ||
       trim($_POST['date'])=='' || trim($_POST['hour'])=='' || ($_POST['minute'])=='' || trim($_POST['second'])=='')
    {Header("Location: ".$_SERVER['PHP_SELF']."?id=$text_id&message=formvalues");exit();}

   $name = $_POST['name'];
   $text = $_POST['text'];
   $date = substr($_POST['date'],6,4).
   substr($_POST['date'],3,2).
   substr($_POST['date'],0,2).
   $_POST['hour'].
   $_POST['minute'].
   $_POST['second'];

  //Обновляем содержимое...
  $result = mysql_query("update forum_comments set date='$date', name='$name', text='$text' where comment_id=$comment_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$comment_id&message=db"); exit();}

  if (isset($_POST['element_id']))
   {
     $result = mysql_query("update forum_comments set element_id = {$_POST['element_id']} where comment_id=$comment_id");
     if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$comment_id&message=db"); exit();}
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$comment_id"); exit();
  } else $user->no_rules('edit');
 }

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id)
{
	$objResponse = new xajaxResponse();
  $select_elements = '<select name="element_id" style="width:280px;" size="6">';

  $result = mysql_query("select * from forum where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].')</option>';
       }
   }
  else $select_elements .= '<option value="">Нет публикаций</option>';

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

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM forum where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_added == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from forum where parent_id = $parent_id order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == 1 && $row['element_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

function is_end($element_id, $parent_id)
 {
   $result = mysql_query("select * from forum where parent_id = $parent_id order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == $num && $row['element_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

   $comment_id = (int)$_GET['id'];
   $result = mysql_query("select *,
                          date_format(forum_comments.date, '%d.%m.%Y (%H:%i:%s)') as date2
                          from forum, forum_comments
                          where forum_comments.element_id = forum.element_id and
                          forum_comments.comment_id=$comment_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $name = $row['name'];
   $text = $row['text'];

   $date = $row['date2'];
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   $date = substr($date,0,10);

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<h2>';
 if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row['img_path1']).'" border="0"> &nbsp;';
 if ($row['img_path2']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row['img_path2']).'" border="0"> &nbsp;';
 if ($row['img_path3']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row['img_path3']).'" border="0"> &nbsp; ';
 echo htmlspecialchars($row['element_name']).'</h2>';

 echo '<form action="?id='.$comment_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Имя <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="name" value="'.htmlspecialchars($name).'"></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
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
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php echo $date;?>" name=date> </TD>
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
      <td><input type="text" name="hour" value='.$hour.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Текст <sup class="red">*</sup></td>
      <td><textarea style="width:280px" name="text" cols="52" rows="5">'.htmlspecialchars($text).'</textarea></td></tr>
    <tr>
      <td>Публикация <sup class="red">*</sup></td>
      <td>
         <select name="parent_id" style="width:280px;" onchange="xajax_show_elements(this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень форума---</option>'.
         show_select()
         .'</select>
         <div id="elements"></div>
      </td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>