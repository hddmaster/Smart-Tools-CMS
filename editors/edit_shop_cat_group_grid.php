<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

function get_grid_tree(&$grid_tree, $grid_id = 0) {
    $result = mysql_query("select * from shop_cat_group_grids where grid_id != $grid_id order by order_id asc");
    if(mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_object($result)) {
            $grid_tree[$row->parent_grid_id][$row->grid_id] = $row->grid_name;
        }
    }
}
global $grid_tree; $grid_tree = array(); get_grid_tree($grid_tree, $_GET['id']);

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

if (isset($_POST['grid_name']) &&
    isset($_POST['grid_descr']) &&
    isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {
   
        if (trim($_POST['grid_name'])=='') {
            header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
            exit();
        }

        $grid_id = (int)$_GET['id'];
        $parent_grid_id = (int)$_POST['parent_grid_id'];
        $grid_name = trim($_POST['grid_name']);
        $grid_descr = trim($_POST['grid_descr']);

        $result = mysql_query("select * from shop_cat_group_grids where grid_name = '".stripslashes($grid_name)."' and grid_id!=$grid_id");
        if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=duplicate"); exit();}

        //Обновляем содержимое...
        $result = mysql_query("update shop_cat_group_grids set grid_name='$grid_name', grid_descr='$grid_descr', parent_grid_id = $parent_grid_id where grid_id=$grid_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=db"); exit();}

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id");
        exit();
    } else $user->no_rules('edit');
}

if (isset($_POST['size_id']) &&
    isset($_GET['id'])) {
    if ($user->check_user_rules('add')) {
        $grid_id = (int)$_GET['id'];
        $size_id = $_POST['size_id'];
        if (trim($_POST['size_id'])=='') {
            header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=formvalues2");
            exit();
        }

        $result = mysql_query("select * from shop_cat_group_grid_sizes where grid_id = $grid_id and size_id = $size_id");
        if (mysql_num_rows($result) > 0) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=duplicate2");
            exit();
        }

        mysql_query("insert into shop_cat_group_grid_sizes values ($grid_id, $size_id, 0)");

        // перенумеровываем
        $result = mysql_query("select * from shop_cat_group_grid_sizes where grid_id = $grid_id order by order_id asc");
        if (mysql_num_rows($result) > 0) {
            $i = 1;
            while ($row = mysql_fetch_array($result)) {
                $id = $row['size_id'];
                mysql_query("update shop_cat_group_grid_sizes set order_id=$i where grid_id = $grid_id and size_id=$id");
                $i++;
            }
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id");
        exit();
    } else $user->no_rules('add');
}

if (isset($_GET['action']) && $_GET['action']!=='' &&
    isset($_GET['size_id']) && $_GET['size_id']!=='' &&
    isset($_GET['id']) && $_GET['id']!=='') {
    $grid_id = (int)$_GET['id'];
    $size_id = $_GET['size_id'];
    $action = $_GET['action'];

    if ($action == 'up'){
        if ($user->check_user_rules('action')) {
            $old_order = 0;
            
            //последовательно пронумеровываем элементы
            $result = mysql_query("select * from shop_cat_group_grid_sizes where grid_id = $grid_id order by order_id asc");
            if (mysql_num_rows($result) > 0) {
                $order = 1;
                $values = array();
                while ($row = mysql_fetch_array($result)) {
                    $vid = $row['size_id'];
                    mysql_query("update shop_cat_group_grid_sizes set order_id = $order where grid_id = $grid_id and size_id = $vid");
                    $values[$order] = $vid;
                    if ($vid == $size_id) $old_order = $order;
                    $order++;
                }

                //для текущего
                $q1 = 'update shop_cat_group_grid_sizes set order_id = '.($old_order-1).' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order];
                //для предыдущего
                $q2 = 'update shop_cat_group_grid_sizes set order_id = '.$old_order.' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order-1];
                mysql_query($q1);mysql_query($q2);

                //Обновление кэша связанных модулей на сайте
                $cache = new Cache; $cache->clear_cache_by_module();
            }
        } else $user->no_rules('action');
    }
  
    if ($action == 'down') {
        if ($user->check_user_rules('action')) {
            $old_order = 0;
            //последовательно пронумеровываем элементы
            $result = mysql_query("select * from shop_cat_group_grid_sizes where grid_id = $grid_id order by order_id asc");
            if (mysql_num_rows($result) > 0) {
                $order = 1;
                $values = array();
                while ($row = mysql_fetch_array($result)) {
                    $vid = $row['size_id'];
                    mysql_query("update shop_cat_group_grid_sizes set order_id = $order where grid_id = $grid_id and size_id = $vid");
                    $values[$order] = $vid;
                    if ($vid == $size_id) $old_order = $order;
                    $order++;
                }

                //для текущего
                $q1 = 'update shop_cat_group_grid_sizes set order_id = '.($old_order+1).' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order];
                //для следующего
                $q2 = 'update shop_cat_group_grid_sizes set order_id = '.$old_order.' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order+1];
                mysql_query($q1);mysql_query($q2);

                //Обновление кэша связанных модулей на сайте
                $cache = new Cache; $cache->clear_cache_by_module();
            }
        } else $user->no_rules('action');
    }

    if ($action == 'delete') {
        if ($user->check_user_rules('delete')) {
            mysql_query("delete from shop_cat_group_grid_sizes where grid_id = $grid_id and size_id = $size_id");
            mysql_query("delete from shop_cat_group_sizes_availability where grid_id = $grid_id and size_id = $size_id");
            mysql_query("delete from shop_cat_group_sizes_elements_availability where grid_id = $grid_id and size_id = $size_id");

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
            $_SESSION['smart_tools_refresh'] = 'enable';
        }
        else $user->no_rules('delete');
    }    
}

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
        
        $grid_id = (int)$_GET['id'];
        $result = mysql_query("select * from shop_cat_group_grids where grid_id = $grid_id");

        if (!$result) exit();
        $row = mysql_fetch_object($result);

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }
        
        echo '
        <form action="?id='.$grid_id.'" method="post">
        <table cellpadding="4" cellspacing="1" border="0" class="form">
            <tr>
                <td>Название <sup class="red">*</sup></td>
                <td><input style="width:280px" type="text" name="grid_name" value="'.htmlspecialchars($row->grid_name).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Описание</td>
                <td><input style="width:280px" type="text" name="grid_descr" value="'.htmlspecialchars($row->grid_descr).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Свойство-родитель</td>
                <td>
                    <select name="parent_grid_id" style="width:280px;">
                    <option value="">Выберите свойство...</option>
                    <option value="0"';
                    if ($row->parent_grid_id == '0') echo ' selected';
                    echo '>---Корень справочника свойств---</option>
                    '.show_select(0, '', $row->parent_grid_id, $grid_tree).'
                </select>
                </td>
            </tr>
            <tr>
                <td>Список характеристик</td>
                <td>';
        
                $result = mysql_query("select
                                    shop_cat_group_sizes.size_id,
                                    shop_cat_group_sizes.size_name
                                    from
                                    shop_cat_group_sizes, shop_cat_group_grid_sizes
                                    where shop_cat_group_grid_sizes.grid_id = $grid_id and
                                    shop_cat_group_grid_sizes.size_id = shop_cat_group_sizes.size_id
                                    order by shop_cat_group_grid_sizes.order_id asc");
        $i = 1;
        if (mysql_num_rows($result) > 0)
            {
            echo '<table cellspacing="0" cellpadding="0" border="0">';
            while ($row = mysql_fetch_array($result))
            {
                echo '<tr><td nowrap><span class="grey">'.htmlspecialchars($row['size_name']).'</span> &nbsp; </td>';
        
                //если элемент первый на определенном уровне, блокируем стрелку "вверх"
                echo '<td nowrap>';
                if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
                else echo '<a href="?id='.$grid_id.'&size_id='.$row['size_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
        
                if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
                else echo '<a href="?id='.$grid_id.'&size_id='.$row['size_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
        
                echo '<a href="';
                echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$grid_id&size_id=".$row['size_id']."&action=delete';}";
                echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
        
                $i++;
            }
            echo '</table>';
            }
        else
            echo 'Нет характеристик';
        
        echo ' </td></tr>
        </table><br>
        <button type="SUBMIT">Сохранить</button>
        </form>';
  

        
        $result = mysql_query("select * from shop_cat_group_sizes order by size_name asc");
        if (mysql_num_rows($result) > 0)
        {

        echo '<h2>Добавить характеристику</h2>';
        echo '  <form action="?id='.$grid_id.'" method="post">
        <table cellpadding="4" cellspacing="1" border="0" class="form">
            <tr>
            <td>';
        echo '<select style="width:280px" name="size_id">
                <option value="">Выберите характеристику...</option>';
        while($row = mysql_fetch_array($result))
            {
            echo '<option value='.$row['size_id'].'>'.htmlspecialchars($row['size_name']).' &nbsp; '.htmlspecialchars($row['size_descr']).'</option>';
            }
        echo'</select>';
        echo'</td>
            </tr>
        </table><br>
        <button type="SUBMIT">Добавить</button>
        </form>';
        }
            

    } else $user->no_rules('view');
}
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>