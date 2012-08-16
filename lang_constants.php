<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['constant_name']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['constant_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $constant_name = trim($_POST['constant_name']);
   $constant_value = trim($_POST['constant_value']);
   if (use_field($constant_name,'lang_constants','constant_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   $result = mysql_query("insert into lang_constants values (null, '$constant_name', '$constant_value', 0)");
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
   $constant_id = (int)$_GET['id'];
   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
	 mysql_query("delete from lang_constant_values where constant_id=$constant_id");
	 mysql_query("delete from lang_constants where constant_id=$constant_id");
       }
      else $user->no_rules('delete');
    }
   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update lang_constants set status=1 where constant_id=$constant_id");
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action')) mysql_query("update lang_constants set status=0 where constant_id=$constant_id");
      else $user->no_rules('action');
    }
 }
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Система</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/auth.php')) $tabs->add_tab('/admin/auth.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_groups.php')) $tabs->add_tab('/admin/auth_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_structure.php')) $tabs->add_tab('/admin/auth_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_users.php')) $tabs->add_tab('/admin/auth_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_rules.php')) $tabs->add_tab('/admin/auth_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_scripts.php')) $tabs->add_tab('/admin/auth_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_script_groups.php')) $tabs->add_tab('/admin/auth_script_groups.php', 'Модули');
if ($user->check_user_rules('view','/admin/auth_history.php')) $tabs->add_tab('/admin/auth_history.php', 'История');
if ($user->check_user_rules('view','/admin/cache.php')) $tabs->add_tab('/admin/cache.php', 'Кэш');
if ($user->check_user_rules('view','/admin/languages.php')) $tabs->add_tab('/admin/languages.php', 'Языки', 1);
if ($user->check_user_rules('view','/admin/currencies.php')) $tabs->add_tab('/admin/currencies.php', 'Валюты');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/languages.php')) $tabs2->add_tab('/admin/languages.php', 'Языки');
if ($user->check_user_rules('view','/admin/lang_constants.php')) $tabs2->add_tab('/admin/lang_constants.php', 'Константы');
$tabs2->show_tabs();

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
		   <td><h2 class="nomargins">Добавить константу</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="constant_name" maxlength="255"></td>
    </tr>
    <tr>
      <td>Значение в базовом языке<br /><span class="grey">Если значение пустое, будет<br />использоваться название константы</span></td>
      <td><textarea style="width:280px" name="constant_value" cols="52" rows="10"></textarea></td>
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
    $sort_by = 'constant_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (constant_id like '$query_str' or
                  constant_name like '$query_str' or
                  constant_value like '$query_str')";
 }

 $query = "select * from lang_constants where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Значение&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_value&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_value' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=constant_value&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'constant_value' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>';
 $res = mysql_query("select * from languages order by lang_code asc");
 if (mysql_num_rows($res) > 0)
  {
    while ($r = mysql_fetch_array($res)) echo '<td>'.htmlspecialchars($r['lang_code']).'</td>';
  }
 echo '<td width="120">&nbsp;</td></tr>'."\n";
 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['constant_id'].'</td>
           <td align="center">'.htmlspecialchars($row['constant_name']).'</td>
           <td>'; if($row['constant_value']) echo htmlspecialchars($row['constant_value']); else echo '&nbsp;'; echo '</td>';
           $res = mysql_query("select * from languages order by lang_code asc");
           if (mysql_num_rows($res) > 0)
            {
              while ($r = mysql_fetch_array($res))
               {
		 echo '<td align="center">';
		 echo '<a href="javascript:sw(\'/admin/editors/edit_lang_constant.php?id='.$row['constant_id'].'&lang_id='.$r['lang_id'].'\');">';
		 $rc = mysql_query("select * from lang_constant_values where lang_id = ".$r['lang_id']." and constant_id = ".$row['constant_id']." and constant_value != ''");
		 if (mysql_num_rows($rc) > 0) echo '<img src="/admin/images/select.gif" alt="" border="0">';
		 else echo '<img src="/admin/images/select_na.gif" alt="" border="0">';
                 echo '</a>';
		 echo '</td>';
	       }
            }
           echo '<td align="center"><a href="javascript:sw(\'/admin/editors/edit_lang_constant.php?id='.$row['constant_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>&nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['constant_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['constant_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>';
           echo '&nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['constant_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';
	   echo '</td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>