<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['page_name']) &&
    isset($_POST['tpl_id']) &&
    isset($_POST['page_url']) &&
    isset($_POST['page_title']) &&
    isset($_POST['page_meta_keyw']) &&
    isset($_POST['page_meta_descr'])) {
    foreach($_POST as $key => $value) $_SESSION['st_forms'][$_SERVER['PHP_SELF']][$key] = trim($value); 
    if ($user->check_user_rules('add')) {
        $page_url = '';
        if (trim($_POST['page_name'])=='' || 
            trim($_POST['page_menu_name'])=='' || 
            trim($_POST['parent_id'])=='' || 
            trim($_POST['tpl_id'])=='' || 
            trim($_POST['page_title'])== '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

        $res = mysql_query("select * from pages where page_url = ''");
        if (mysql_num_rows($res) > 0 && trim($_POST['page_url']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
    
        $parent_id=$_POST['parent_id'];
        if (trim($_POST['page_url']) != '') $page_url = mb_strtolower(trim($_POST['page_url']));
        if ($page_url == '.' || $page_url == '..') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        $tpl_id = $_POST['tpl_id'];
        $page_name = trim($_POST['page_name']);
        $page_menu_name = trim($_POST['page_menu_name']);
        $page_title = trim($_POST['page_title']);
        $page_meta_keyw = ''; if (isset($_POST['page_meta_keyw'])) $page_meta_keyw = trim($_POST['page_meta_keyw']);
        $page_meta_descr = ''; if (isset($_POST['page_meta_descr'])) $page_meta_descr = trim($_POST['page_meta_descr']);

        // проверка на повторное название
        //if (use_field($page_name,'pages','page_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

        $result = mysql_query(" insert into pages values
                                (
                                    null,
                                    $parent_id,
                                    '$page_url',
                                    '$page_name', 
                                    '$page_menu_name', 
                                    '$page_title', 
                                    '$page_meta_keyw', 
                                    '$page_meta_descr',
                                    $tpl_id,
                                    '',
                                    0,
                                    0,
                                    0
                                )");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

        // перенумеровываем
        $result = mysql_query("select * from pages where parent_id = $parent_id order by order_id asc");
        if (mysql_num_rows($result) > 0) {
            $i = 1;
            while ($row = mysql_fetch_array($result)) {
                $page_id = $row['page_id'];
                mysql_query("update pages set order_id=$i where page_id=$page_id");
                $i++;
            }
        }
     
        unset($_SESSION['st_forms'][$_SERVER['PHP_SELF']]); 
        Header("Location: ".$_SERVER['PHP_SELF']); exit();
	 
    } else $user->no_rules('add');
}


if (isset($_GET['id']) && $_GET['id'] != '' &&
    isset($_GET['action']) && $_GET['action'] != '') {
 
    $page_id = intval($_GET['id']);
    $action = $_GET['action'];

    $result = mysql_query("select * from pages where page_id=$page_id");
    $row = mysql_fetch_array($result);
    $parent_id = $row['parent_id'];

    if ($action == 'delete') {
        if ($user->check_user_rules('delete')) {
            $page = new Site_generate;
            $page->get_pages($page_id);
            if (count($page->pages) == 0) {
                foreach ($page->pages as $page_id) $page->page_delete($page_id);
                mysql_query("delete from pages where page_id = $page_id");
            } else {
                header("Location: ".$_SERVER['PHP_SELF']."?message=use");
                exit();
            }
        } else $user->no_rules('delete');
    }
  
    if ($action == 'activate') {
        if ($user->check_user_rules('action')) {
            $page = new Site_generate;
            $page->page_create($page_id);
        } else $user->no_rules('action');
    }
  
    if ($action == 'disactivate') {
        if ($user->check_user_rules('action')) {
            $page = new Site_generate;
            $page->pages[] = $page_id;
            $page->get_pages($page_id);
            foreach ($page->pages as $page_id) $page->page_delete($page_id);
        } else $user->no_rules('action');
    }
  
    if ($action == 'activate_menu') {
        if ($user->check_user_rules('action')) mysql_query("update pages set visibility=1 where page_id=$page_id");
        else $user->no_rules('action');
        header('Location: '.$_SERVER['HTTP_REFERER']); exit();
    }
  
    if ($action == 'disactivate_menu') {
        if ($user->check_user_rules('action')) mysql_query("update pages set visibility=0 where page_id=$page_id");
        else $user->no_rules('action');
        header('Location: '.$_SERVER['HTTP_REFERER']); exit();
    }
  
    if ($action == 'up') {
        if ($user->check_user_rules('action')) {
            $old_order = 0;
            //последовательно пронумеровываем элементы в выбранной ветке
            $result = mysql_query("select * from pages where parent_id=$parent_id order by order_id asc");
            if (mysql_num_rows($result) > 0) {
                $order = 1;
                $values = array();
                while ($row = mysql_fetch_array($result)) {
                    $cid = $row['page_id'];
                    mysql_query("update pages set order_id = $order where page_id = $cid");
                    $values[$order] = $cid;
                    if ($cid == $page_id) $old_order = $order;
                    $order++;
                }

                //для текущего
                mysql_query('update pages set order_id = '.($old_order-1).' where page_id = '.$values[$old_order]);
                //для предыдущего
                mysql_query('update pages set order_id = '.$old_order.' where page_id = '.$values[$old_order-1]);
            }
            header('Location: '.$_SERVER['HTTP_REFERER']); exit();
        } else $user->no_rules('action');
    }
  
    if ($action == 'down') {
        if ($user->check_user_rules('action')) {
            $old_order = 0;
            //последовательно пронумеровываем элементы в выбранной ветке
            $result = mysql_query("select * from pages where parent_id=$parent_id order by order_id asc");
            if (mysql_num_rows($result) > 0) {
                $order = 1;
                $values = array();
                while ($row = mysql_fetch_array($result)) {
                    $cid = $row['page_id'];
                    mysql_query("update pages set order_id = $order where page_id = $cid");
                    $values[$order] = $cid;
                    if ($cid == $page_id) $old_order = $order;
                    $order++;
                }

                //для текущего
                mysql_query('update pages set order_id = '.($old_order+1).' where page_id = '.$values[$old_order]);
                //для следующего
                mysql_query('update pages set order_id = '.$old_order.' where page_id = '.$values[$old_order+1]);
            }
            header('Location: '.$_SERVER['HTTP_REFERER']); exit();
        } else $user->no_rules('action');
    }
}
    

function show_tree($parent_id, $start, $per_page) {
    global $ic;
    global $itc;
    $result = mysql_query("select pages.*, designs.tpl_name from pages left join designs on pages.tpl_id = designs.tpl_id where parent_id = $parent_id order by order_id asc");
    if(mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            if ($ic >= $start && $itc < $per_page) {
                echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
                <td nowrap>';
                // определение уровня вложенности
                global $level; $level = 0;
                get_num_level($row['page_id']);
                //построение дерева
                if ($level == 1) {
                    //вывод знака раздела или конца
                    if ($ic == 0) {
                        if (mysql_num_rows($result) > 1)
                            echo '<img align="absmiddle" src="/admin/images/tree/4.gif" border="0">';
                        else
                            echo '<img align="absmiddle" src="/admin/images/px.gif" border="0" width="16" height="28" alt="">';
                    } else {
                        //вывод знака раздела или конца
                        if (is_end($row['page_id'], $row['parent_id']))
                            echo '<img align="absmiddle" src="/admin/images/tree/2.gif" border="0">';
                        else
                            echo '<img align="absmiddle" src="/admin/images/tree/3.gif" border="0">';
                    }
                } else {
                    //Последовательный просмотр всех родителей по уровням.
                    //Если родитель не последний в своей ветке ставим верт. черту, иначе пустое поле.
                    global $ids; $ids = '';
                    $ids = get_parents_cat_ids($row['page_id']);

                    $k = 0; //уровень
                    while ($k < ($level-1)) {
                        if ($k == 0) {if (is_end($ids[$k],0)) echo '<img align="absmiddle" src="/admin/images/px.gif" width="16" height="1">'; else echo '<img align="absmiddle" src="/admin/images/tree/1.gif" border="0">';}
                        else {if (is_end($ids[$k],$ids[$k-1])) echo '<img align="absmiddle" src="/admin/images/px.gif" width="16" height="1">'; else echo '<img align="absmiddle" src="/admin/images/tree/1.gif" border="0">';}
                        $k++;
                    }
                    
                    //вывод знака раздела или конца
                    if (is_end($row['page_id'], $row['parent_id'])) echo '<img align="absmiddle" src="/admin/images/tree/2.gif" border="0">';
                    else echo '<img align="absmiddle" src="/admin/images/tree/3.gif" border="0">';
                }
                
                //end построение дерева
                echo '<img align="absmiddle" src="/admin/images/icons/document.png"> <a href="javascript:sw(\'/admin/editors/edit.php?id='.$row['page_id'].'\');"><strong>'.$row['page_name'].'</strong></a></td>';
                if ($row['page_url']) $page_path = get_reverse_path($row['page_id']); else $page_path = '/';
                if (mb_strlen($page_path,'UTF-8') > 70) $page_path = mb_substr($page_path,0,70,'UTF-8').' ...';
                echo '<td style="padding-left:6px;"><a target="_blanck" a href="'.get_reverse_path($row['page_id']).'" class="small">'.$page_path.'</a></td>';

                echo '<td><a href="javascript:sw(\'/admin/editors/edit_design.php?id='.$row['tpl_id'].'\')" class="grey">'.htmlspecialchars($row['tpl_name']).'</a></td>';
                
                //вывод кнопок статуса и наличия в меню
                if ($row['status'] == 0)
                    echo '<td align="center"><a href="?id='.$row['page_id'].'&action=activate"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" alt="Активность" border="0"></a></td>';
                else
                    echo '<td align="center"><a href="?id='.$row['page_id'].'&action=disactivate"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" alt="Активность" border="0"></a></td>';

                if ($row['visibility'] == 0)
                    echo '<td align="center"><a href="?id='.$row['page_id'].'&action=activate_menu"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" alt="Видимость в меню" border="0"></a></td>';
                else
                    echo '<td align="center"><a href="?id='.$row['page_id'].'&action=disactivate_menu"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" alt="Видимость в меню" border="0"></a></td>';

                echo '<td align="center" nowrap>';
                
                //если элемент первый на определенном уровне, блокируем стрелку "вверх"
                if (is_begin($row['page_id'], $row['parent_id']))
                    echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
                else
                    echo '<a href="?id='.$row['page_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';

                //если элемент последний в ветке, блокируем стрелку "вниз"
                if (is_end($row['page_id'], $row['parent_id']))
                    echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
                else
                    echo '<a href="?id='.$row['page_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
                
                echo '</td>';
                echo '<td nowrap align="center"><a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?id='.$row['page_id'].'&action=delete\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить страницу"></a></td></tr>';
            
                $itc++;
            }    
            $ic++;
            show_tree($row['page_id'], $start, $per_page);
        }
    }
}

function get_path($page_id, $prefix = "/") {
    global $out;
    $result = mysql_query("select * from pages where page_id = $page_id");
    while ($row = mysql_fetch_array($result)) {
        if ($row['page_url'] != '') $out .= $prefix.$row['page_url'];
        get_path($row['parent_id'], "/");
    }
    return $out;
}

function get_reverse_path($page_id) {
    global $out;
    $out = '';
    $path = get_path($page_id);

    $path_values = explode ("/", $path);
    $path_values = array_reverse($path_values);

    $path= "";
    foreach ($path_values as $value)
        $path = $path.'/'.$value;

    return $path;
}

function get_num_level($page_id) {
    global $level;
    $result = mysql_query("select * from pages where page_id = $page_id");
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $level++;
            get_num_level($row['parent_id']);
        }
    }
}

function is_begin($cid, $pid) {
    $result = mysql_query("select * from pages where parent_id = $pid order by order_id asc");
    $num = mysql_num_rows($result);
    if ($num > 0) {
        $k = 1;
        while ($row = mysql_fetch_array($result)) {
            if ($k == 1 && $row['page_id'] == $cid) return true;
            $k++;
        }
    }
    return false;
}

function is_end($cid, $pid) {
    $result = mysql_query("select * from pages where parent_id = $pid order by order_id asc");
    $num = mysql_num_rows($result);
    if ($num > 0) {
        $k = 1;
        while ($row = mysql_fetch_array($result)) {
            if ($k == $num && $row['page_id'] == $cid) return true;
            $k++;
        }
    }
    return false;
}

function get_parents_cat_ids($page_id) {
    global $ids;
    $result = mysql_query("select * from pages where page_id = $page_id");
    while ($row = mysql_fetch_array($result)) {
        $ids[] = $row['page_id'];
        get_parents_cat_ids($row['parent_id']);
    }
   
    $ids_ = array();
    $num = count($ids);
    $k = 1;
    while ($k < ($num)) {
        $ids_[] = $ids[($num-$k)];
        $k++;
    }
    return $ids_;
}

function get_tree(&$tree) {
    $result = mysql_query("select * from pages order by order_id asc");
    if(mysql_num_rows($result) > 0)
        while ($row = mysql_fetch_object($result))
        $tree[$row->parent_id][$row->page_id] = $row->page_name;
}

$tree = array(); get_tree($tree);

function show_select($parent_id = 0, $prefix = '', $selected_page_id = 0, &$tree) {
    global $options;
    foreach($tree[$parent_id] as $page_id => $page_name) {
        $options .= '<option value="'.$page_id.'"'.($selected_page_id == $page_id ? ' selected' : '').'>'.$prefix.htmlspecialchars($page_name).'</option>';
        show_select($page_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_page_id, $tree);
    }
    return $options;
}

//-----------------------------------------------------------------------------
// AJAX
function check_page($page_url, $parent_id) {
    $objResponse = new xajaxResponse();

    $result = mysql_query("select * from pages where page_url = '".mb_strtolower($page_url)."' and parent_id = $parent_id");
    if (mysql_num_rows($result) > 0 && trim($page_url) !== '') {
        $objResponse->assign("submitbutton","disabled",true);
        $objResponse->alert("Такое название уже используется в этой ветке, попробуйте ввести другое");
    } else
        $objResponse->assign("submitbutton","disabled",false);
    return $objResponse;
}

$xajax->registerFunction("check_page");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Структура сайта</h1>';

if ($user->check_user_rules('view')) {

    if (isset($_GET['message'])) {
        $message = new Message;
        $message->add_message('activation', 'Ошибка при активации страницы. Сначала нужно активировать вышестоящие станицы раздела.');
        $message->add_message('deactivation', 'Ошибка при деактивации страницы. Сначала нужно деактивировать нижестоящие станицы раздела.');
        $message->add_message('delete', 'Ошибка при удалении страницы. Сначала нужно удалить нижестоящие станицы раздела.');
        $message->add_message('visibility', 'Ошибка при изменении статуса страницы в меню. Раздел должен быть активирован.');
        $message->add_message('reserved', 'Введенное имя файла зарезервировано на сервере, используйте другое');
        $message->get_message($_GET['message']);
    }

    echo '  <div class="dhtmlgoodies_question">
                <table cellspacing="0" cellpadding="4">
                    <tr>
                        <td><img src="/admin/images/icons/plus.png" alt=""></td>
                        <td><h2 class="nomargins">Добавить страницу</h2></td>
                    </tr>
                </table>   
            </div>
            <div class="dhtmlgoodies_answer"><div>';

    echo '  <form action="" method="post" name="form" id="form">
                <table cellpadding="4" cellspacing="1" border="0" class="form">
                    <tr>
                        <td>Название страницы <sup class="red">*</sup></td>
                        <td><input style="width:280px" type="text" name="page_name" maxlength="255" value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_name'])).'"></td></tr>
                    <tr>
                        <td>Название страницы в меню<sup class="red">*</sup></td>
                        <td><input style="width:280px" type="text" name="page_menu_name" maxlength="255" value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_menu_name'])).'"></td></tr>
                    <tr>
                        <td>Заголовок страницы<sup class="red">*</sup><br /><span class="grey">TITLE</span></td>
                        <td><input style="width:280px" type="text" name="page_title" maxlength="255" value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_title'])).'"></td></tr>
                    <tr>
                        <td>Ссылка';

    $res = mysql_query("select * from pages where page_url = ''");
    if (mysql_num_rows($res) > 0)
        echo '<sup class="red">*</sup>';
    else
        echo '<br/><span class="grey">При создании главной страницы<br/> поле не заполняется</span>';  

    echo ' <br /><span class="grey">URL</span></td>
                        <td><input  style="width:280px" type="text" name="page_url" maxlength="255" onblur="xajax_check_page(this.form.page_url.value, this.form.parent_id.options[this.form.parent_id.selectedIndex].value);"
                                                                                 onkeypress="if (event.keyCode > 31 &&
                                                                                             (event.keyCode < 97 || event.keyCode > 122) &&
                                                                                             (event.keyCode < 48 || event.keyCode > 57) &&
                                                                                             (event.keyCode < 95 || event.keyCode > 95) &&
											     (event.keyCode < 45 || event.keyCode > 45)) event.returnValue = false;"
                                    value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_url'])).'"></td></tr>
                    <tr>
                        <td>Ключевае слова<br /><span class="grey">meta keywords</span></td>
                        <td><input style="width:280px" type="text" name="page_meta_keyw" maxlength="255" value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_meta_keyw'])).'"></td>
                    </tr>
                    <tr>
                        <td>Описание<br /><span class="grey">meta description</span></td>
                        <td><input style="width:280px" type="text" name="page_meta_descr" maxlength="255" value="'.htmlspecialchars(stripcslashes($_SESSION['st_forms'][$_SERVER['PHP_SELF']]['page_meta_descr'])).'"></td>
                    </tr>
                    <tr>
                        <td>Расположение <sup class="red">*</sup><br /><span class="grey">Выберите страницу-родителя</span></td>
                        <td>
                            <select name="parent_id" style="width:280px;" onchange="xajax_check_page(this.form.page_url.value, this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
                                <option value="0">---Корень сайта---</option>
                                '.show_select(0, '', 0, $tree).'
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Шаблон страницы <sup class="red">*</sup></td>
                        <td>
                            <select style="width:280px" name="tpl_id">
                                <option value="">Выберите шаблон...</option>';
            
                                $res = mysql_query("select * from designs order by tpl_name asc");
                                if (mysql_num_rows($res) > 0) {
                                    while ($r = mysql_fetch_array($res)) {
                                        echo '<option value="'.$r['tpl_id'].'"';
                                        if ($r['tpl_id'] == $_SESSION['st_forms'][$_SERVER['PHP_SELF']]['tpl_id']) echo ' selected';
                                        echo '>'.htmlspecialchars($r['tpl_name']).'</option>'."\n";
                                    }
                                }

    echo '                  </select>
                        </td>
                    </tr>
                </table><br />
                <button type="SUBMIT" id="submitbutton">Добавить</button>
            </form><br />
        </div>
    </div>';

    $result = mysql_query("select * from pages");
    if (mysql_num_rows($result) > 0) {
        // постраничный вывод
        $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
        $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
        $start = abs($page*$per_page);
 
        $params = array();

        navigation($page, $per_page, mysql_num_rows($result), $params);
        echo '  <div class="databox">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr align="center" class="header">
                            <td nowrap>Название страницы</td>
                            <td>URL</td>
                            <td>Шаблон</td>
                            <td>Статус</td>
                            <td colspan="2">Меню</td>
                            <td>&nbsp;</td>
                        </tr>'."\n";
                        show_tree(0, $start, $per_page);
        echo '      </table>
                </div>';
        navigation($page, $per_page, mysql_num_rows($result), $params);
    }
} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>