<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['property_name']))
 {

 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['property_name']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $property_name = trim($_POST['property_name']);
   if (use_field($property_name, 'auth_site_properties', 'property_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}
   
   $result = mysql_query("insert into auth_site_properties
                          (property_name)
                          values
                          ('$property_name')");
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
   $property_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
       mysql_query("delete from auth_site_properties where property_id=$property_id");
     else
       $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update auth_site_properties set status=1 where property_id=$property_id");
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action')) mysql_query("update auth_site_properties set status=0 where property_id=$property_id");
      else $user->no_rules('action');
    }
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Пользователи сайта</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/auth_site.php')) $tabs->add_tab('/admin/auth_site.php', 'Пользователи', 1);
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

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить свойство</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="property_name" maxlength="255">
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
    $sort_by = 'property_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (property_id like '$query_str' or
                  property_name like '$query_str' or
                  text like '$query_str' or
                  text_full like '$query_str')";
 }

 $query = "select
           *
           from auth_site_properties
           where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=property_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'property_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=property_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'property_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=property_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'property_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=property_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'property_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['property_id'].'</td>
           <td align="center">'.htmlspecialchars($row['property_name']).'</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_auth_site_property_descr.php?id='.$row['property_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_auth_site_property.php?id='.$row['property_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['property_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['property_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['property_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>