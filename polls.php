<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['date']) &&
    isset($_POST['date2']) &&
    isset($_POST['question']))
 {
   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['date'])=='' ||
       trim($_POST['date2'])=='' ||
       trim($_POST['question'])=='' ||
       trim($_POST['type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues");exit();}
   $date1 = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2);
   $date2 = substr($_POST['date2'],6,4).substr($_POST['date2'],3,2).substr($_POST['date2'],0,2);

//проверка промежутка времени
   $date_b = intval($date1);
   $date_e = intval($date2);
   if ($date_b > $date_e) {Header("Location: ".$_SERVER['PHP_SELF']."?message=date");exit();}

   $question = $_POST['question'];
   $type = $_POST['type'];

  // проверка а повторное название
  if (use_field($question,'polls_names','question')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}

   //Добавляем информацию...
   $result = mysql_query("insert into polls_names values  (null, '$question', '$date1', '$date2', '', '', $type, 0)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $poll_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

      //удаляем из бд
      $result = mysql_query("delete from polls_results where poll_id=$poll_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

      $result = mysql_query("delete from polls_names where poll_id=$poll_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

      Header("Location: ".$_SERVER['PHP_SELF']);exit();
      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update polls_names set status=1 where poll_id=$poll_id");
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action')) mysql_query("update polls_names set status=0 where poll_id=$poll_id");
      else $user->no_rules('action');
    }

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Опросы</h1>';

if ($user->check_user_rules('view'))
 {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('date', 'Даты заданы некорректно');
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить новый опрос</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Вопрос <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="question" maxlength="255"></td></tr>
    <tr>
      <td>Даты опроса <sup class="red">*</sup></td>
      <td>';
?>
<TABLE cellSpacing=0 cellPadding=0 border=0>
 <TR>
  <TD class=tix>с&nbsp;</TD>
  <TD>
     <SCRIPT language=JavaScript>
    
     // класс LSCalendar должен присутствовать в системе, массив LSCalendars объявлен
     LSCalendars["date"]=new LSCalendar();
     LSCalendars["date"].SetFormat("dd.mm.yyyy");
     LSCalendars["date"].SetDate("<?php echo date("d.m.Y");?>");
     
     </SCRIPT>
    <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
     <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php echo date("d.m.Y");?>" name=date> </TD>
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
   <TD class=tix>&nbsp;&nbsp;по&nbsp;</TD>
   <TD>
     <SCRIPT language=JavaScript>
     
     // класс LSCalendar должен присутствовать в системе, массив LSCalendars объявлен
     LSCalendars["date2"]=new LSCalendar();
     LSCalendars["date2"].SetFormat("dd.mm.yyyy");
     LSCalendars["date2"].SetDate("<?php echo date("d.m.Y");?>");
     
     </SCRIPT>
     <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
      <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php echo date("d.m.Y");?>" name=date2> </TD>
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
echo'      </td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td><input type="radio" name="type" value="0" checked></td>
           <td><span class="grey">1 ответ</span></td>
         </tr>
         <tr>
           <td><input type="radio" name="type" value="1"></td>
           <td><span class="grey">&gt; 1-ого ответа</span></td>
         </tr>
        </table>
      </td></tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сортировка
 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'poll_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select 
           *,
	   date_format(date1, '%d.%m.%Y') as date1,
	   date_format(date2, '%d.%m.%Y') as date2
	   from polls_names $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=poll_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'poll_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=poll_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'poll_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=question&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'question' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=question&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'question' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата начала&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата завершения&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Тип&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=type&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'type' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=type&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'type' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Голосов</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['poll_id'].'</td>
           <td>'.htmlspecialchars($row['question']).'</td>
           <td align="center">'.$row['date1'].'</td>
           <td align="center">'.$row['date2'].'</td>
           <td align="center">';
           if ($row['type'] == 0) echo '<span class="small">1 ответ</span>';
           if ($row['type'] == 1) echo '<span class="small">&gt; 1-ого ответа</span>';
           echo '</td>
           <td align="center">';
           $res = mysql_query("select sum(value) as sum_value from polls_results where poll_id = ".$row['poll_id']." group by poll_id");
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              echo $r['sum_value'];
            } else echo '&nbsp;';
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_polls_descr.php?id='.$row['poll_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_polls.php?id='.$row['poll_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['poll_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['poll_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['poll_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
  }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>