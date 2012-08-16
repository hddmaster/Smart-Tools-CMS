<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['clear_cache']) && $_GET['clear_cache'] == 'true' && $user->check_user_rules('action')) {
    $cache = new Cache;
    $cache->clear_all_cache();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['clear_cache_image']) && $_GET['clear_cache_image'] == 'true' && $user->check_user_rules('action')) {
    $cache = new Cache;
    $cache->clear_all_image_cache();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Система</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/auth.php')) $tabs->add_tab('/admin/auth.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_groups.php')) $tabs->add_tab('/admin/auth_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_structure.php')) $tabs->add_tab('/admin/auth_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_users.php')) $tabs->add_tab('/admin/auth_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_rules.php')) $tabs->add_tab('/admin/auth_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_scripts.php')) $tabs->add_tab('/admin/auth_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_script_groups.php')) $tabs->add_tab('/admin/auth_script_groups.php', 'Модули');
if ($user->check_user_rules('view','/admin/auth_history.php')) $tabs->add_tab('/admin/auth_history.php', 'История');
if ($user->check_user_rules('view','/admin/cache.php')) $tabs->add_tab('/admin/cache.php', 'Кэш');
if ($user->check_user_rules('view','/admin/languages.php')) $tabs->add_tab('/admin/languages.php', 'Языки');
if ($user->check_user_rules('view','/admin/currencies.php')) $tabs->add_tab('/admin/currencies.php', 'Валюты');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {

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
    $sort_by = 'cache_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();

 $query = "select
           cache.*,
           date_format(date, '%d.%m.%Y (%H:%i:%s)') as date_f,
           content.content_name
           from cache, content
           where cache.module_id = content.obj_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result); 
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page") or die(mysql_error());

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=cache_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'cache_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=cache_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'cache_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Модуль&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=content_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'content_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=content_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'content_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>URL&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=url&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'url' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=url&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'url' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>URL родителя</td>
         <td nowrap>Размер</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['cache_id'].'</td>
           <td align="center" nowrap>'.$row['date_f'].'</td>
           <td align="center">'.htmlspecialchars($row['content_name']).'</td>
           <td align="center">'.$row['url'].'</td>
           <td align="center">';
           if ($row['parent_id'] > 0)
            {
              $res = mysql_query("select url from cache where cache_id = ".$row['parent_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo '<span class="green">'.$r['url'].'</span>';
               }
            } else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['parent_id'] == 0) echo round(filesize($_SERVER['DOCUMENT_ROOT'].'/cache/'.$row['module_id'].'/'.$row['file'])/1024,2).' Kb'; else echo '&nbsp;'; echo '</td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }
echo '<table cellspacing="" cellpadding="0" width="100%">
	<tr valign="top">
	  <td width="50%">
      
      <h2>Кэш модулей</h2>
      <table cellspacing="" cellpadding="0">
	<tr>
	  <td><a href="?clear_cache=true"><img src="/admin/images/reactivate.gif" alt="" border="0"></a></td>
          <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
	  <td><a href="?clear_cache=true"><strong>Очистить кэш модулей</strong></a></td>
	</tr>
       </table>

      </td><td width="50%">

      <h2>Кэш изображений</h2>
      <table cellspacing="0" cellpadding="0">
	<tr>
	  <td><a href="?clear_cache_image=true"><img src="/admin/images/reactivate.gif" alt="" border="0"></a></td>
          <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
	  <td><a href="?clear_cache_image=true"><strong>Очистить кэш изображений</strong></a></td>
	</tr>
       </table>
       
       </td></tr></table>';

  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>