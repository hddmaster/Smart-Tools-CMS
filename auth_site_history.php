<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');
echo '<h1>Пользователи сайта</h1>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/auth_site.php')) $tabs->add_tab('/admin/auth_site.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_site_groups.php')) $tabs->add_tab('/admin/auth_site_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_site_properties.php')) $tabs->add_tab('/admin/auth_site_properties.php', 'Свойства');
if ($user->check_user_rules('view','/admin/auth_site_structure.php')) $tabs->add_tab('/admin/auth_site_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_site_users.php')) $tabs->add_tab('/admin/auth_site_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_site_rules.php')) $tabs->add_tab('/admin/auth_site_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_site_scripts.php')) $tabs->add_tab('/admin/auth_site_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_site_history.php')) $tabs->add_tab('/admin/auth_site_history.php', 'История');
if ($user->check_user_rules('view','/admin/auth_site_import.php')) $tabs->add_tab('/admin/auth_site_import.php', 'Импорт');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {

 function show_select_filter($user_id)
  {
    $result = mysql_query("select * from auth_site order by username asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($user_id == $row['user_id']) $options .= ' selected';
          $options .= '>'.htmlspecialchars($row['username']).'</option>'."\n";
        }
    }
    return $options;
  }

$user_id = ''; if (isset($_GET['user_id']) && trim($_GET['user_id']) !== '') $user_id = $_GET['user_id'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td nowrap>
   <form action="" method="GET">

   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td nowrap>Фильтр по пользователю</td>
      <td><select name="user_id" style="width:280px;">
            <option value="">---Все пользователи---</option>
            '.show_select_filter($user_id).'
          </select>
      </td>
      <td><button type="SUBMIT">OK</button></td>
    </tr>
  </table>
   </td>

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
    $sort_by = 'history_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = strtolower(trim($_GET['query_str']));
   $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';

   $add .= " and (auth_site.username like '$query_str' or
            auth_site_history.file like '$query_str' or
            auth_site_history.command like '$query_str' or
            auth_site_history.remote_addr like '$query_str' or
	    auth_site_history.data like '$query_str')";
 }

if (isset($_GET['user_id']) && trim($_GET['user_id']) !== '')
 {
   $add .= " and auth_site.user_id = ".$_GET['user_id'];
   $params['user_id'] = $_GET['user_id'];
 }
 
 $query = "select
           auth_site_history.*,
           auth_site.username
           from auth_site_history,auth_site
           where auth_site_history.user_id = auth_site.user_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=history_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'history_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=history_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'history_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Имя пользователя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Файл&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'file' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'file' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Команда&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=command&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'command' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=command&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'command' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>IP адрес&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=remote_addr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'remote_addr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=remote_addr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'remote_addr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>REQUEST DATA&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=data&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'data' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=data&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'data' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['history_id'].'</td>
           <td align="center" nowrap>'.strftime("%d.%m.%Y (%H:%M:%S)",$row['date']).'</td>
           <td align="center">'.$row['username'].'</td>
           <td align="center">'.$row['file'].'</td>
           <td align="center">'.$row['command'].'</td>
           <td align="center">'.$row['remote_addr'].'</td>
           <td align="left">'; if ($row['data']) echo htmlspecialchars($row['data']); else echo '&nbsp;'; echo '</td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }
  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>