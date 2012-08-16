<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['distr_name']))
 {
   if ($user->check_user_rules('add'))
   {

  if ($_POST['distr_name']=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
  $distr_name = $_POST['distr_name'];
  //проверка на повторы
  if (use_field($distr_name,'distr','distr_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}

  //уникальная запись! Добавляем...
  $result = mysql_query("insert into distr values (null,'$distr_name',0,0)");
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
   $distr_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

      if (use_field($distr_id,'distr_msg','distr_id')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use");exit();}

      //удаляем из списка рассылок
      $result = mysql_query("delete from distr where distr_id=$distr_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

      //удаляем из таблицы рассылка
      $result = mysql_query("delete from distr_list where distr_id=$distr_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

      Header("Location: ".$_SERVER['PHP_SELF']);exit();

      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update distr set status=1 where distr_id=$distr_id");
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action')) mysql_query("update distr set status=0 where distr_id=$distr_id");
      else $user->no_rules('action');
    }
   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();

 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Рассылка сообщений</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/distribution.php')) $tabs->add_tab('/admin/distribution.php', 'Рассылки');
if ($user->check_user_rules('view','/admin/distr_msg.php')) $tabs->add_tab('/admin/distr_msg.php', 'Сообщения');
if ($user->check_user_rules('view','/admin/distr_templates.php')) $tabs->add_tab('/admin/distr_templates.php', 'Шаблоны');
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
		   <td><h2 class="nomargins">Добавить новую рассылку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="distr_name" maxlength="255"></td></tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';
  
// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'distr_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
 $query = "select
           D.*,
	   M.head,
	   M.msg_id,
	   (select count(*) from distr_list where distr_id = D.distr_id) as c
	   from distr as D left join distr_msg as M
	   on D.msg_id = M.msg_id
	   where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=distr_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'distr_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=distr_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'distr_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="50%">Рассылка&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=distr_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'distr_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=distr_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'distr_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="50%">Сообщение&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Количество подписчиков&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=c&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'c' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=c&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'c' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['distr_id'].'</td>
           <td>'.(($row['distr_name']) ? '<a href="/admin/editors/edit_distr.php?id='.$row['distr_id'].'"><strong>'.htmlspecialchars($row['distr_name']).'</strong></a>' : '&nbsp;').'</td>
           <td>'.(($row['head']) ? '<a href="javascript:sw(\'/admin/editors/edit_distr_msg.php?id='.$row['msg_id'].'\');"><strong>'.htmlspecialchars($row['head']).'</strong></a>' : '&nbsp;').'</td>
           <td align="center">'.(($row['c']) ? htmlspecialchars($row['c']) : '&nbsp;').'</td>
           <td nowrap align="center">
           <a href="/admin/distr_send.php?id='.$row['distr_id'].'"><img align="absmiddle" src="/admin/images/icons/mail-send.png" border="0" alt="Отправить"></a>
	   &nbsp;';
	   if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['distr_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['distr_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['distr_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>