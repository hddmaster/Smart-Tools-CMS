<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

function get_grid_tree(&$grid_tree) {
    $result = mysql_query("select * from shop_cat_group_grids order by order_id asc");
    if(mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_object($result)) {
            $grid_tree[$row->parent_grid_id][$row->grid_id] = $row->grid_name;
        }
    }
}
global $grid_tree; $grid_tree = array(); get_grid_tree($grid_tree);

function show_select($parent_grid_id = 0, $prefix = '', $selected_grid_id = 0, &$grid_tree) {
    global $options;
    foreach($grid_tree[$parent_grid_id] as $grid_id => $grid_name) {
        $options .= '   <option value="'.$grid_id.'"'.($selected_grid_id == $grid_id ? ' selected' : '').'>'.
                        $prefix.htmlspecialchars($grid_name).'</option>';
        show_select($grid_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_grid_id, $grid_tree);
    }
    return $options;
}

function path_to_object($g_id, &$path, &$grid_tree) {
    foreach($grid_tree as $p_id => $groups) {
        foreach($groups as $grid_id => $grid_name) {
            if ($grid_id == $g_id) {
                $path[] = $grid_name;
                path_to_object($p_id, $path, $grid_tree);	
            }
         }
    }
}

if (    isset($_POST['size_name']) &&
        isset($_POST['size_descr']) &&
        isset($_POST['parent_grid_id'])) {
    if ($user->check_user_rules('add')) {
        if (trim($_POST['size_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

        $size_name = $_POST['size_name'];
        $size_descr = $_POST['size_descr'];

        // проверка а повторное название
        //if (use_field($size_name,'shop_cat_group_sizes','size_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

        //Добавляем...
        $result = mysql_query("insert into shop_cat_group_sizes values (null, '$size_name', '$size_descr', '', '')");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
        $size_id = mysql_insert_id();
        
        if($_POST['parent_grid_id'] > 0) {
            $grid_id = (int)$_POST['parent_grid_id'];
            $result = mysql_query("insert into shop_cat_group_grid_sizes (grid_id, size_id) values ($grid_id, $size_id)");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}            
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        Header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else $user->no_rules('add');
}


if (isset($_GET['action']) && isset($_GET['id'])) {
   $action = $_GET['action'];
   $size_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        if (use_field($size_id, 'shop_cat_group_grid_sizes', 'size_id')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}

        $result = mysql_query("delete from shop_cat_group_grid_sizes where size_id=$size_id");
        $result = mysql_query("delete from shop_cat_group_sizes_availability where size_id=$size_id");
        $result = mysql_query("delete from shop_cat_group_sizes_elements_availability where size_id=$size_id");
        $result = mysql_query("delete from shop_cat_group_sizes where size_id=$size_id");
        if (!$result) {Header("Location: /admin/shop_cat_group_sizes.php?message=db"); exit();}

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }
   Header("Location: ".$_SERVER['PHP_SELF']);
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
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары');
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы', 1);
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
$tabs3->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_cat_structure.php')) $tabs3->add_tab('/admin/shop_cat_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_group_grids.php')) $tabs3->add_tab('/admin/shop_cat_group_grids.php', 'Свойства', 1);
if ($user->check_user_rules('view','/admin/shop_units_of_measure.php')) $tabs3->add_tab('/admin/shop_units_of_measure.php', 'Единицы измерения');
if ($user->check_user_rules('view','/admin/shop_cat_group_publications.php')) $tabs3->add_tab('/admin/shop_cat_group_publications.php', 'Публикации');
$tabs3->show_tabs();

$tabs4 = new Tabs;
$tabs4->level = 3;
if ($user->check_user_rules('view','/admin/shop_cat_group_sizes.php')) $tabs4->add_tab('/admin/shop_cat_group_sizes.php', 'Характеристики');
$tabs4->show_tabs();

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
		   <td><h2 class="nomargins">Добавить характеристику</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="size_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="size_descr" maxlength="255">
      </td>
    </tr>
    <tr>
        <td>Свойство-родитель</td>
        <td>
            <select name="parent_grid_id" style="width:280px;">
            <option value="0">---Корень справочника свойств---</option>
            '.show_select(0, '', 0, $grid_tree).'
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
    $sort_by = 'size_id';
    $order = 'desc';
  }


 $add = '';
 $params = array();
 
 $query = "select * from shop_cat_group_sizes $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'size_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'size_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'size_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'site_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_descr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'size_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=size_descr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'size_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>Свойства</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['size_id'].'</td>
           <td align="center">'.htmlspecialchars($row['size_name']).'</td>
           <td align="center">'; if(!$row['size_descr']) echo '&nbsp;'; else echo htmlspecialchars($row['size_descr']); echo '</td>
           <td class="grey">';
           
            $res = mysql_query("select GG.* from shop_cat_group_grids as GG left join shop_cat_group_grid_sizes as GGS on GG.grid_id = GGS.grid_id left join shop_cat_group_sizes as GS on GGS.size_id = GS.size_id where GGS.size_id = ".$row['size_id']) or die(mysql_error());
            if (mysql_num_rows($res) > 0) {
                $i = 1;
                while ($r = mysql_fetch_object($res)) {
                    $str = array();
                    $path = '';
                    path_to_object($r->grid_id, $str, $grid_tree);
                    $str = array_reverse($str);
                    $i = 1;
                    foreach ($str as $value) {
                        $path .= htmlspecialchars($value);
                        if ($i < count($str)) $path .= ' -&gt; ';
                        $i++;
                    }
                    
                    echo '<a class="grey" href="javascript:sw(\'/admin/editors/edit_shop_cat_group_grid.php?id='.$r->grid_id.'\');">'.$path.'</a>';
                    if($i < mysql_num_rows($res)) echo '<br />';
                    $i++;
                }
            } else echo '&nbsp;';

           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_cat_group_size_descr.php?id='.$row['size_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_cat_group_size.php?id='.$row['size_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать характеристику"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='shop_cat_group_sizes.php?action=del&id=".$row['size_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>