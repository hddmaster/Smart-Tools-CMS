<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['ip1']) &&
    isset($_POST['ip2']) &&
    isset($_POST['ip3']) &&
    isset($_POST['ip4']) &&
    isset($_POST['description']))
 {

 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['ip1'])=='' || trim($_POST['ip2'])=='' || trim($_POST['ip3'])=='' || trim($_POST['ip4'])=='' || trim($_POST['description'])=='') {Header("Location: /admin/stat_ips.php?message=formvalues"); exit();}

   $ip = trim($_POST['ip1']).'.'.trim($_POST['ip2']).'.'.trim($_POST['ip3']).'.'.trim($_POST['ip4']);
   if (use_field($ip,'stat_ips_detect','ip')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   $description = $_POST['description'];
   $disabled_in_statistic = $_POST['disabled_in_statistic'];

   $result = mysql_query("insert into stat_ips_detect values (null, '$ip', '$description', $disabled_in_statistic)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   header("Location: ".$_SERVER['PHP_SELF']); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $ip_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
         mysql_query("delete from stat_ips_detect where ip_id=$ip_id");
      } else $user->no_rules('delete');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');
echo '<h1>Посещения сайта</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/statistic.php')) $tabs->add_tab('/admin/statistic.php', 'Суммарный отчет за период');
if ($user->check_user_rules('view','/admin/stat_ips.php')) $tabs->add_tab('/admin/stat_ips.php', 'IP-адреса');
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
		   <td><h2 class="nomargins">Добавить IP-адрес</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>IP-адрес <sup class="red">*</sup></td>
      <td>
       <input style="width:25px; text-align: center;" type="text" name="ip1" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip2" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip3" maxlength="3">.<input style="width:25px; text-align: center;" type="text" name="ip4" maxlength="3">
      </td>
    </tr>
    <tr>
      <td>Описание <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="description" maxlength="255">
      </td>
    </tr>
   <tr>
     <td>Учёт в статистике</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="disabled_in_statistic" value="0"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="disabled_in_statistic" value="1" checked></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
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
    $sort_by = 'ip_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select * from stat_ips_detect $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=ip_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'ip_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=ip_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'ip_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>IP-адрес&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=ip&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'ip' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=ip&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'ip' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=description&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'description' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=description&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'description' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
	 <td>Учёт в статистике</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['ip_id'].'</td>
           <td align="center">'.$row['ip'].'</td>
           <td align="center">'.htmlspecialchars($row['description']).'</td>
           <td align="center">'.(($row['disabled_in_statistic']) ? '<span class="red">нет</span>' : '<span class="green">да</span>').'</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_stat_ip.php?id='.$row['ip_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать IP-адрес"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='stat_ips.php?action=del&id=".$row['ip_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

 //Параметры хостинга
 echo '<fieldset>
       <legend>Информация</legend>
       Для большей информативности отчетов к определенным IP-адресам можно привязывать названия.<br/>
       Ваш IP-адрес: '.$_SERVER['REMOTE_ADDR'].'
       </fieldset>';

  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
