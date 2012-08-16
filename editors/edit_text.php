<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name']) &&
   isset($_POST['name_answ']) &&
   isset($_POST['text']) &&
   isset($_POST['text_answ']) &&
   isset($_POST['email']) &&
   isset($_GET['id']) &&
   isset($_POST['date']) &&
   isset($_POST['date2']) &&
   isset($_POST['hour']) &&
   isset($_POST['minute']) &&
   isset($_POST['second']) &&
   isset($_POST['hour_answ']) &&
   isset($_POST['minute_answ']) &&
   isset($_POST['second_answ']))
 {

 if ($user->check_user_rules('edit'))
  {
   $text_id = (int)$_GET['id'];
   if (trim($_POST['text'])=='' ||
       trim($_POST['date'])=='' ||
       trim($_POST['hour'])=='' ||
       trim($_POST['minute'])=='' ||
       trim($_POST['second'])=='' ||
       trim($_POST['date2'])=='' ||
       trim($_POST['hour_answ'])=='' ||
       trim($_POST['minute_answ'])=='' ||
       trim($_POST['second_answ'])=='')
    {Header("Location: ".$_SERVER['PHP_SELF']."?id=$text_id&message=formvalues");exit();}

   $name = $_POST['name'];
   $name_answ = $_POST['name_answ'];
   $text = $_POST['text'];
   $text_answ = $_POST['text_answ'];
   $email = $_POST['email'];
   $date = substr($_POST['date'],6,4).
   substr($_POST['date'],3,2).
   substr($_POST['date'],0,2).
   $_POST['hour'].
   $_POST['minute'].
   $_POST['second'];
   $date_answ = substr($_POST['date2'],6,4).
   substr($_POST['date2'],3,2).
   substr($_POST['date2'],0,2).
   $_POST['hour_answ'].
   $_POST['minute_answ'].
   $_POST['second_answ'];

  //Обновляем содержимое...
  $result = mysql_query("update guestbook set date='$date', date_answ='$date_answ', name='$name', name_answ='$name_answ', text='$text', text_answ='$text_answ', email='$email' where text_id=$text_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$text_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$text_id"); exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {



 function show_select($parent_id = 0, $prefix = '', &$parent_id_selected)
  {
    global $options;
    $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['text_id'].'"';
          if ($parent_id_selected == $row['text_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['name']).'</option>'."\n";
          show_select($row['text_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

   $text_id = (int)$_GET['id'];
   $result = mysql_query("select *,
                          date_format(date, '%d.%m.%Y (%H:%i:%s)') as date,
                          date_format(date_answ, '%d.%m.%Y (%H:%i:%s)') as date_answ
                          from guestbook where text_id=$text_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $name = $row['name'];
   $name_answ = $row['name_answ'];
   $email = $row['email'];
   $text = $row['text'];
   $text_answ = $row['text_answ'];

   $date = $row['date'];
   $hour = substr($date,12,2);
   $minute = substr($date,15,2);
   $second = substr($date,18,2);
   $date = substr($date,0,10);

   $date_answ = $row['date_answ'];
   $hour_answ = substr($date_answ,12,2);
   $minute_answ = substr($date_answ,15,2);
   $second_answ = substr($date_answ,18,2);
   $date_answ = substr($date_answ,0,10);

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<h2>Сообщение</h2>';
 echo '<form action="?id='.$text_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'',$row['parent_id']).'
          </select>'; global $options; $options = ''; echo '
      </td>
    </tr>
    <tr>
      <td>Имя</td>
      <td><input style="width:280px" type="text" name="name" value="'.htmlspecialchars($name).'" maxlength="255"></td></tr>
    <tr>
      <td>e-mail</td>
      <td><input style="width:280px" type="text" name="email" value="'.$email.'" maxlength="255"></td></tr>
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
    </tr></table><div>&nbsp;</div>';
    $oFCKeditor = new FCKeditor('text') ;
    $oFCKeditor->BasePath = '/admin/fckeditor/';
    $oFCKeditor->ToolbarSet = 'Main' ;
    $oFCKeditor->Value = $text;
    $oFCKeditor->Width  = '100%' ;
    $oFCKeditor->Height = '200' ;
    $oFCKeditor->Create() ;
    echo '<div>&nbsp;</div>';
  
 echo '<div style="border-top: #cccccc 1px dotted;">&nbsp;</div><h2>Ответ на сообщение</h2>';

 echo '<table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Имя</td>
      <td><input style="width:280px" type="text" name="name_answ" value="'.htmlspecialchars($name_answ).'" maxlength="255"></td></tr>
   <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td>';
?>
<TABLE cellSpacing=0 cellPadding=0 border=0>
 <TR>
  <TD>
     <SCRIPT language=JavaScript>
     LSCalendars["date2"]=new LSCalendar();
     LSCalendars["date2"].SetFormat("dd.mm.yyyy");
     LSCalendars["date2"].SetDate("<?php echo $date_answ;?>");

     </SCRIPT>
     <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
      <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php echo $date_answ;?>" name=date2> </TD>
      <TD><button style="WIDTH: 34px; HEIGHT: 17px" onclick="showCalendarForElement('date2', event); return false;"><img src="/admin/images/icons/calendar.png"></button></TD>
      </TR>
      <TR>
       <TD colSpan=2>
         <DIV id=date2Ptr style="WIDTH: 1px; HEIGHT: 1px">
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
      <td><input type="text" name="hour_answ" value='.$hour_answ.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute_answ"  value='.$minute_answ.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second_answ" value='.$second_answ.' style="width:22px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr></table><div>&nbsp;</div>';
    $oFCKeditor = new FCKeditor('text_answ') ;
    $oFCKeditor->BasePath = '/admin/fckeditor/';
    $oFCKeditor->ToolbarSet = 'Main' ;
    $oFCKeditor->Value = $text_answ;
    $oFCKeditor->Width  = '100%' ;
    $oFCKeditor->Height = '200' ;
    $oFCKeditor->Create() ;
    echo '<div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>