<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['action_name']) && isset($_POST['action_descr'])) {
    if ($user->check_user_rules('add')) {
        if (trim($_POST['action_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

        $action_name = mysql_real_escape_string(trim($_POST['action_name']));
        $action_descr = mysql_real_escape_string(trim($_POST['action_descr']));
        $action_value = mysql_real_escape_string(trim($_POST['action_value']));
        
        // проверка а повторное название
        if (use_field($action_name, 'shop_cat_actions', 'action_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}
        
        //Добавляем...
        $result = mysql_query("insert into shop_cat_actions (action_name, action_descr, action_value) values ('$action_name', '$action_descr', $action_value)");
        if (!$result) {
            header("Location: ".$_SERVER['PHP_SELF']."?message=db");
            exit();
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else $user->no_rules('add');
}


if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $action_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        $result = mysql_query("delete from shop_cat_element_action_values where action_id=$action_id");
        $result = mysql_query("delete from shop_cat_element_actions where action_id = $action_id");
        $result = mysql_query("delete from shop_cat_actions where action_id=$action_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }
   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_actions set status=1 where action_id=$action_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_actions set status=0 where action_id=$action_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог', 1);
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары', 1);
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы');
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
if ($user->check_user_rules('view','/admin/shop_cat_structure_elements.php')) $tabs3->add_tab('/admin/shop_cat_structure_elements.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_grids.php')) $tabs3->add_tab('/admin/shop_cat_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_cat_cards.php')) $tabs3->add_tab('/admin/shop_cat_cards.php', 'Карточки описаний');
if ($user->check_user_rules('view','/admin/shop_cat_producers.php')) $tabs3->add_tab('/admin/shop_cat_producers.php', 'Производители');
if ($user->check_user_rules('view','/admin/shop_cat_sites.php')) $tabs3->add_tab('/admin/shop_cat_sites.php', 'Сайты');
if ($user->check_user_rules('view','/admin/shop_cat_actions.php')) $tabs3->add_tab('/admin/shop_cat_actions.php', 'Акции');
if ($user->check_user_rules('view','/admin/shop_cat_spec.php')) $tabs3->add_tab('/admin/shop_cat_spec.php', 'Спецпредложения');
if ($user->check_user_rules('view','/admin/shop_cat_comments.php')) $tabs3->add_tab('/admin/shop_cat_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/shop_cat_publications.php')) $tabs3->add_tab('/admin/shop_cat_publications.php', 'Публикации');
$tabs3->show_tabs();

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
		   <td><h2 class="nomargins">Добавить акцию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="action_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="action_descr" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Скидка</td>
      <td>
       <select name="action_value">
         <option value="0">---НЕТ---</option>';
       for ($i = 5; $i <= 100; $i += 5)  echo '<option value="'.$i.'">'.$i.'%</option>';
       echo '</select>
      </td>
    </tr></table><br>
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
    $sort_by = 'action_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = " select
            *,
            date_format(date_begin, '%d.%m.%Y') as date1,
            date_format(date_end, '%d.%m.%Y') as date2
            from
            shop_cat_actions $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата начала&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date_begin&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date_begin' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date_begin&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date_begin' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата завершения&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date_end&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date_end' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date_end&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date_end' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_descr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_descr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Скидка&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_descr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=action_descr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'action_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
		 <td width="35">&nbsp;</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['action_id'].'</td>
           <td align="center">'.htmlspecialchars($row['date1']).'</td>
           <td align="center">'.htmlspecialchars($row['date2']).'</td>
           <td align="center">'.htmlspecialchars($row['action_name']).'</td>
           <td align="center">'; if(!$row['action_descr']) echo '&nbsp;'; else echo htmlspecialchars($row['action_descr']); echo '</td>
           <td align="center">'; if(!$row['action_value']) echo '&nbsp;'; else echo htmlspecialchars($row['action_value']).'%'; echo '</td>
           <td align="center">'; if ($row['img_path']) echo '<a href="'.$row['img_path'].'" class="zoom" rel="group" title="'.(($row['action_name']) ? htmlspecialchars($row['action_name']) : '').'"><img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" alt="'.$row['img_path'].'" border="0"></a>'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_cat_action_descr.php?id='.$row['action_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_shop_action.php?id='.$row['action_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать свойство"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['action_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['action_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='shop_cat_actions.php?action=del&id=".$row['action_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>