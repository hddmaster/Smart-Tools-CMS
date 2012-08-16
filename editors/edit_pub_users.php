<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

$get_params = ''; if(count($_GET)) foreach($_GET as $key => $value) $get_params .= '&'.$key.'='.$value;

if (isset($_GET['id']) && isset($_POST['action']) && $_POST['action'] == 'save') {
    if ($user->check_user_rules('edit')) {
        $pub_id = (int)$_GET['id'];
        foreach($_POST['ids'] as $user_id) mysql_query("delete from pub_auth_site where pub_id = $pub_id and user_id = $user_id");
        foreach($_POST['users'] as $user_id) mysql_query("insert into pub_auth_site (pub_id, user_id) values ($pub_id, ".(int)$user_id.")");
      
        $_SESSION['smart_tools_refresh'] = 'enable';
        header('Location: '.$_SERVER['PHP_SELF'].'?'.$get_params); //exit();
    } else $user->no_rules('edit');
}

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
        $pub_id = (int)$_GET['id'];

        $result = mysql_query(" select
                                P.*
                                from
                                publications as P
                                where P.pub_id = $pub_id");
        if (!$result) exit();
        $row = mysql_fetch_object($result);
        
        echo '<h2 class="nomargins">'.htmlspecialchars($row->head).'</h2><div>&nbsp;</div>';
 
        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

        $tabs = new Tabs;
        if ($user->check_user_rules('view','/admin/editors/edit_pub.php')) $tabs->add_tab('/admin/editors/edit_pub.php?id='.$pub_id, 'Свойства');
        if ($user->check_user_rules('view','/admin/editors/edit_pub_text.php')) $tabs->add_tab('/admin/editors/edit_pub_text.php?id='.$pub_id.'&mode=brief', 'Краткое описание');
        if ($user->check_user_rules('view','/admin/editors/edit_pub_text.php')) $tabs->add_tab('/admin/editors/edit_pub_text.php?id='.$pub_id.'&mode=full', 'Подробное описание');
        if ($user->check_user_rules('view','/admin/editors/edit_pub_users.php')) $tabs->add_tab('/admin/editors/edit_pub_users.php?id='.$pub_id, 'Область видимости');
        $tabs->show_tabs();
    
        function get_auth_site_tree(&$auth_site_tree) {
            $result = mysql_query("select * from auth_site where type = 1 order by order_id asc");
                if(mysql_num_rows($result) > 0)
                    while ($row = mysql_fetch_object($result))
                        $auth_site_tree[$row->parent_id][$row->user_id] = $row->username;
        }
        $auth_site_tree = array(); get_auth_site_tree(&$auth_site_tree);
    
        function show_select($parent_id = 0, $prefix = '', $selected_user_id = 0, &$auth_site_tree) {
            global $options;
            foreach($auth_site_tree[$parent_id] as $user_id => $username) {
                $options .= '<option value="'.$user_id.'"'.($selected_user_id == $user_id ? ' selected' : '').'>'.$prefix.htmlspecialchars($username).'</option>';
                show_select($user_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_user_id, $auth_site_tree);
            }
            return $options;
        }
    
        function path_to_object($e_id, &$path, &$auth_site_tree) {
            foreach($auth_site_tree as $p_id => $groups) {
                foreach($groups as $user_id => $username) {
                    if ($user_id == $e_id) {
                        $path[$user_id] = $username;
                        path_to_object($p_id, $path, $auth_site_tree);	
                    }
                }
            }
        }
    
        function get_parents($parent_id = 0, &$auth_site_tree, &$parent_ids) {
            foreach($auth_site_tree[$parent_id] as $user_id => $username) {
                $parent_ids[] = $user_id;
                get_parents($user_id, $auth_site_tree, $parent_ids);	
            }
        }
    
        $parent_id = -1; if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '') $parent_id = (int)$_GET['parent_id'];
        echo '  <form action="" method="GET">
                <input type="hidden" name="id" value="'.$pub_id.'">
                <table cellpadding="4" cellspacing="0" border="0">
    
                    <tr>
                        <td><img src="/admin/images/icons/funnel.png" alt=""></td>
                        <td nowrap>Фильтр по группе</td>
                        <td>
                            <select name="parent_id" style="width:280px;">
                                <option value="">---Весь каталог---</option>
                                <option value="0"'; if (isset($_GET['parent_id']) && $parent_id == 0) echo ' selected'; echo'>---Корень каталога---</option>
                                '.show_select(0, '', $parent_id, &$auth_site_tree).'
                            </select>'; global $options; $options = ''; echo '
                        </td>
                        <td></td>
                    </tr>
    
                    <tr>
                        <td><img src="/admin/images/icons/funnel.png" alt=""></td>
                        <td nowrap>Фильтр по статусу</td>
                        <td>
                            <table>
                                <tr>
                                    <td><input type="checkbox" name="status[]" value="1"'.((!isset($_GET['status']) || (isset($_GET['status']) && in_array(1, $_GET['status']))) ? 'checked' : '').'></td>
                                    <td>отмечен</td>
                                    <td style="padding-left: 15px;"><input type="checkbox" name="status[]" value="0"'.((!isset($_GET['status']) || (isset($_GET['status']) && in_array(0, $_GET['status']))) ? 'checked' : '').'></td>
                                    <td>не отмечен</td>
                                </tr>
                            </table>
                        </td>
                        <td></td>
                    </tr>
                    
                    <tr>
                        <td><img src="/admin/images/icons/magnifier.png" alt=""></td>
                        <td>Поиск по фразе</td>
                        <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
                        <td><button type="submit">Найти</button></td>
                    </tr>
    
                </table>
            </form>';

        // сортировка
        $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'user_id');
        $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

        // постраничный вывод
        $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
        $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
        $start = abs($page*$per_page);
        
        $add = '';
        $params = array();
        
        $params['id'] = $pub_id;

        if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '') {
            $parent_ids[] = (int)$_GET['parent_id']; 
            get_parents((int)$_GET['parent_id'], &$auth_site_tree, &$parent_ids);
            $add .= " and parent_id in (".implode(',', $parent_ids).")";
            $params['parent_id'] = $_GET['parent_id'];
        }
 
        if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') {

            $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
            $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';
            $add .= "   and (   user_id like '$query_str' or
                                username like '$query_str' or
                                user_fio like '$query_str' or
                                user_nick like '$query_str' or
                                user_type_name like '$query_str' or
                                user_extra like '$query_str' or
                                email like '$query_str')";
        }

        if (isset($_GET['status'])) {
            $add .= " and (select count(*) from pub_auth_site where pub_id = $pub_id and user_id = A.user_id) in (".implode(',', $_GET['status']).")";
            if(in_array(1, $_GET['status'])) $params['status'][] = 1;
            if(in_array(0, $_GET['status'])) $params['status'][] = 0;
        }

        $add_params = '';
        if(is_array($params)) {
            foreach($params as $key => $value) {
                if(is_array($value)) {
                    foreach($value as $v)
                        $add_params .= '&'.$key.'[]='.rawurlencode($v);
                } else
                    $add_params .= '&'.$key.'='.rawurlencode($value);
            }
        }

        $query = "  select
                    A.*,
                    (select count(*) from pub_auth_site where pub_id = $pub_id and user_id = A.user_id) as checked,
                    U.user_type_name
                    from auth_site as A left join auth_site_users as U on A.user_type = U.user_type
                    where
                    A.type = 0
                    $add";
        $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
        $result = mysql_query($query." order by $sort_by $order LIMIT $start, $per_page") or die(mysql_error());

        if (mysql_num_rows($result) > 0) {
 
 
 echo '<form id="form" method="post" action="?sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><input type="hidden" name="action" value="save">';
 echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
 navigation($page, $per_page, $total_rows, $params);
 echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"></td>
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Расположение</td>
         <td nowrap>Логин&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'username' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'username' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Тип пользователя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_type_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_type_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_type_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_type_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Ник&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_nick&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_nick' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_nick&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_nick' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Ф.И.О&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_fio&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_fio' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_fio&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_fio' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>e-mail&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Дополнительная<br />информация&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_extra&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_extra' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_extra&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_extra' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
          <td align="center"><input type="hidden" name="ids[]" value="'.$row['user_id'].'"><input class="cbx" type="checkbox" name="users[]" value="'.$row['user_id'].'"'.($row['checked'] ? ' checked' : '').'></td>
          <td align="center">'.$row['user_id'].'</td>
	      <td class="small">';
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
	      $str = array();
              path_to_object($row['parent_id'], &$str, &$auth_site_tree);
	      $str = array_reverse($str);
              $i = 1;
              foreach ($str as $value)
               {
                 echo $value;
                 if ($i < count($str)) echo ' -&gt; ';
                 $i++;
               }
            }
           echo '</td>
           <td align="center" nowrap>'.htmlspecialchars($row['username']).'</td>
           <td align="center">'.htmlspecialchars($row['user_type_name']).'</td>
           <td align="center">'; if ($row['user_nick']) echo htmlspecialchars($row['user_nick']); else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['user_fio']) echo htmlspecialchars($row['user_fio']); else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['email']) echo htmlspecialchars($row['email']); else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['user_extra']) echo htmlspecialchars($row['user_extra']); else echo '&nbsp;'; echo '</td>
         </tr>';
   }
 echo '</table></div>';
 echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
 navigation($page, $per_page, $total_rows, $params);
 echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
 echo '</form>';
  }
else echo '<p align="center">Не найдено</p>';

    } else $user->no_rules('view');
} else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>