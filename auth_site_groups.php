<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['username'])&&
    isset($_POST['parent_id']))
 {

 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['username'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $parent_id = trim($_POST['parent_id']);
   $username = trim($_POST['username']);
   if (use_field($username,'auth_site','username')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   $result = mysql_query("insert into auth_site (parent_id, type, username) values ($parent_id, 1, '$username')");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   // перенумеровываем
   $result = mysql_query("select * from auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['user_id'];
         mysql_query("update auth_site set order_id=$i where user_id = $id");
         $i++;
       }
    }

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
   $user_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
      //проверка на удаление текущего пользователя
      list ($username_session, $str) = unserialize($_SESSION['valid_cms_user']);

      $result = mysql_query("select * from auth_site where user_id = $user_id");
      $row = mysql_fetch_array($result);
      if ($username_session == $row['username']) {Header("Location: ".$_SERVER['PHP_SELF']."?message=this_user"); exit();}

      mysql_query("delete from auth_site where user_id=$user_id");
      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update auth_site set status=1 where user_id=$user_id");
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action')) mysql_query("update auth_site set status=0 where user_id=$user_id");
      else $user->no_rules('action');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Пользователи сайта</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
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

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("SELECT * FROM auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($parent_id_added == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>';
          show_select($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

 function show_select_filter($parent_id = 0, $prefix = '', $parent_id_element = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($parent_id_element == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>';

          show_select_filter($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

function path_to_object($user_id)
 {
   global $path;
   $parh = array();
   $result = mysql_query("select * from auth_site where user_id = $user_id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $path[] = $row['username'];
     
      path_to_object($row['parent_id']);
    }
   return array_reverse($path); 
 }

 $parent_id_added = 0; if (isset($_GET['parent_id'])) $parent_id_added = $_GET['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('admin', 'Нельзя удалить корневого администратора');
   $message->add_message('this_user', 'Нельзя удалить текущего пользователя');
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить группу</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="username" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Расположение <sup class="red">*</sup><br><span class="grey">Выберите группу</span></td>
      <td><select name="parent_id" style="width:280px;">
          <option value="0">---Корень каталога---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>
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
    $sort_by = 'user_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select * from auth_site where type = 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="images/'; if ($sort_by == 'user_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="images/'; if ($sort_by == 'user_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Расположение</td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="images/'; if ($sort_by == 'username' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="images/'; if ($sort_by == 'username' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['user_id'].'</td>
           <td class="small">';
           
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
              $str = path_to_object($row['parent_id']);
              $i = 1;
              foreach ($str as $value)
               {
                 echo $value;
                 if ($i < count($str)) echo ', ';
                 $i++;
               }
              global $path; $path = array();
            }
            
           echo '</td>
           <td align="center">'.htmlspecialchars($row['username']).'</td>
           <td nowrap align="center">';
           echo '<a href="javascript:sw(\'/admin/editors/edit_auth_site_descr.php?id='.$row['user_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_auth_site_user.php?id='.$row['user_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>&nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['user_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['user_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>';
           echo '&nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['user_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';
           echo '</td>
         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>