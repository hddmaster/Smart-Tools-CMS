<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['head']))
 {
   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['date'])=='' || trim($_POST['date2'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $head = $_POST['head'];
   
   $hour1 = intval($_POST['hour1']); if ($hour1 > 23) $hour1 = 00; if ($hour1 < 10) $hour1 = '0'.$hour1;
   $minute1 = intval($_POST['minute1']); if ($minute1 > 59) $minute1 = 00; if ($minute1 < 10) $minute1 = '0'.$minute1;
   $second1 = intval($_POST['second1']); if ($second1 > 59) $second1 = 00; if ($second1 < 10) $second1 = '0'.$second1;
   $date1 = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour1.$minute1.$second1;

   $hour2 = intval($_POST['hour2']); if ($hour2 > 23) $hour2 = 00; if ($hour2 < 10) $hour2 = '0'.$hour2;
   $minute2 = intval($_POST['minute2']); if ($minute2 > 59) $minute2 = 00; if ($minute2 < 10) $minute2 = '0'.$minute2;
   $second2 = intval($_POST['second2']); if ($second2 > 59) $second2 = 00; if ($second2 < 10) $second2 = '0'.$second2;
   $date2 = substr($_POST['date2'],6,4).substr($_POST['date2'],3,2).substr($_POST['date2'],0,2).$hour2.$minute2.$second2;

    //Добавляем...
    $query = "insert into advertising values (null,0,1,'$date1','$date2','$head','','','','',0)";
    
    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();

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
   $adv_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update advertising set status=1 where adv_id=$adv_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update advertising set status=0 where adv_id=$adv_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Реклама</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/advertising.php')) $tabs->add_tab('/admin/advertising.php', 'Баннеры');
if ($user->check_user_rules('view','/admin/adv_groups.php')) $tabs->add_tab('/admin/adv_groups.php', 'Рекламные кампании');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить кампанию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="head" maxlength="255"></td></tr>
    <tr>
      <td>Даты показа <sup class="red">*</sup></td>
      <td>';
?>
    <script>
      LSCalendars["date"]=new LSCalendar();
      LSCalendars["date"].SetFormat("dd.mm.yyyy");
      LSCalendars["date"].SetDate("<?=date('d.m.Y')?>");
      LSCalendars["date2"]=new LSCalendar();
      LSCalendars["date2"].SetFormat("dd.mm.yyyy");
      LSCalendars["date2"].SetDate("<?=date('d.m.Y')?>");
    </script>
    
    <table cellspacing="0" cellpadding="0"><tr>
    
    <td style="padding-right: 4px;">с</td><td>
    
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=date('d.m.Y')?>" name="date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>
    
    </td><td style="padding-left: 8px;padding-right: 4px;">по</td><td>
          
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date2', event); return false;" style="width: 65px;" value="<?=date('d.m.Y')?>" name="date2"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date2', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="date2Ptr" style="width: 1px; height: 1px;"></div>

    </td></tr></table>
<?
    echo '</td>
    </tr>
    <tr>
      <td>Время показа <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td>с&nbsp;</td>
           <td>
             <input type="text" name="hour1" value="00" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute1"  value="00" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second1" value="01" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
           <td>&nbsp;&nbsp;по&nbsp;</td>
           <td>
             <input type="text" name="hour2" value="23" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute2"  value="59" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second2" value="59" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
         </tr>
        </table> 
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars($_GET['query_str']); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

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
      $sort_by = 'adv_id';
      $order = 'desc';
    }

 $add = '';
 $params = array();
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = strtolower(trim($_GET['query_str']));
   $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';

   $add .= " and (adv_id like '$query_str' or
           head like '$query_str')";
 }

 $query = "select
           *,
           date_format(date1, '%d.%m.%Y (%H:%i:%s)') as date1_,
           date_format(date2, '%d.%m.%Y (%H:%i:%s)') as date2_
           from
           advertising where type = 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата начала&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата завершения&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Просмотров</td>
         <td nowrap>Переходов</td>
         <td>&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['adv_id'].'</td>
           <td>'; if ($row['head']) echo htmlspecialchars($row['head']); else echo '&nbsp;'; echo '</td>
           <td align="center">'.$row['date1_'].'</td>
           <td align="center">'.$row['date2_'].'</td>
           <td align="center">&nbsp;</td>
           <td nowrap align="center">&nbsp;</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_adv.php?id='.$row['adv_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>&nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['adv_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['adv_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>';
           echo '&nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['adv_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a><td>
         </tr>'."\n";
  }
  echo '</table></div>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';
} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>