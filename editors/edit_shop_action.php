<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['action_name']) &&
   isset($_POST['action_descr']) &&
   isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {
        if (trim($_POST['action_name'])=='') {
            header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
            exit();
        }

        $action_id = (int)$_GET['id'];
        $action_name = trim($_POST['action_name']);
        $action_descr = trim($_POST['action_descr']);
        $action_value = trim($_POST['action_value']);
        $date_begin = substr($_POST['date_begin'],6,4).substr($_POST['date_begin'],3,2).substr($_POST['date_begin'],0,2);
        $date_end = substr($_POST['date_end'],6,4).substr($_POST['date_end'],3,2).substr($_POST['date_end'],0,2);
        $title = trim($_POST['title']);
        $meta_keywords = trim($_POST['meta_keywords']);
        $meta_description = trim($_POST['meta_description']);
        $url = trim($_POST['url']);
        
        $result = mysql_query("select * from shop_cat_actions where action_name = '".stripslashes($action_name)."' and action_id != $action_id");
        if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id&message=duplicate"); exit();}

        //если есть картинка, проверяем её тип
        if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $user_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
            $type = basename($_FILES['picture']['type']);
        
            switch ($type) {
                case 'jpeg': break;
                case 'pjpeg': break;
                case 'png': break;
                case 'x-png': break;
                case 'gif': break;
                case 'bmp': break;
                case 'wbmp': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=incorrectfiletype"); exit(); break;
            }
        
            //удаляем старый,если не используется
            if ($img_path != '')
            {
                if (!use_file($img_path, 'shop_cat_actions', 'img_path'))
                unlink($_SERVER['DOCUMENT_ROOT'].$img_path);
            }
        
            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($user_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext",'',$user_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_action_images/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            }
            $user_file_name =  $name.'.'.$ext;
        
            $result = mysql_query("update shop_cat_actions set img_path = '/userfiles/shop_cat_action_images/$user_file_name' where action_id = $action_id");
            $cache = new Cache;
            $cache->clear_all_image_cache();

            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_action_images/$user_file_name";
            copy($_FILES['picture']['tmp_name'], $filename);
            resize($filename, basename($_FILES['picture']['type']));
            chmod($filename,0666);
        }
        
        //Обновляем содержимое...
        $result = mysql_query(" update
                                shop_cat_actions
                                set
                                action_name = '$action_name',
                                action_descr = '$action_descr',
                                title = '$title',
                                meta_keywords = '$meta_keywords',
                                meta_description = '$meta_description',
                                url = '$url',
                                action_value = '$action_value',
                                date_begin = '$date_begin',
                                date_end = '$date_end'
                                where action_id=$action_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id&message=db"); exit();}

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id");
        exit();
    } else $user->no_rules('edit');
}

if (isset($_POST['element_id']) &&
   isset($_GET['id'])) {
    if ($user->check_user_rules('add')) {
        $action_id = (int)$_GET['id'];
        $element_id = $_POST['element_id'];
        if (trim($_POST['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id&message=formvalues2");exit();}

        $result = mysql_query("select * from shop_cat_element_actions where action_id = $action_id and element_id = $element_id");
        if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id&message=duplicate2");exit();}

        mysql_query("insert into shop_cat_element_actions values ($element_id, $action_id, 0, 0)") or die(mysql_error());

        // перенумеровываем
        $result = mysql_query("select * from shop_cat_element_actions where action_id = $action_id order by a_order_id asc");
        if (@mysql_num_rows($result) > 0) {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update shop_cat_element_actions set a_order_id=$i where action_id = $action_id and element_id=$id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$action_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && $_GET['action']!=='' &&
    isset($_GET['element_id']) && $_GET['element_id']!=='' &&
    isset($_GET['id']) && $_GET['id']!=='')
 {
   $action_id = (int)$_GET['id'];
   $element_id = $_GET['element_id'];
   $action = $_GET['action'];

   if ($action == 'up')
   {
    if ($user->check_user_rules('action'))
     {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from shop_cat_element_actions where action_id = $action_id order by a_order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['element_id'];
           mysql_query("update shop_cat_element_actions set a_order_id = $order where action_id = $action_id and element_id = $vid");
           $values[$order] = $vid;
           if ($vid == $element_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_element_actions set a_order_id = '.($old_order-1).' where action_id = '.$action_id.' and element_id = '.$values[$old_order];
        //для предыдущего
        $q2 = 'update shop_cat_element_actions set a_order_id = '.$old_order.' where action_id = '.$action_id.' and element_id = '.$values[$old_order-1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      }
     } else $user->no_rules('action');
   }
  if ($action == 'down')
   {
    if ($user->check_user_rules('action'))
      {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from shop_cat_element_actions where action_id = $action_id order by a_order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['element_id'];
           mysql_query("update shop_cat_element_actions set a_order_id = $order where action_id = $action_id and element_id = $vid");
           $values[$order] = $vid;
           if ($vid == $element_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_element_actions set a_order_id = '.($old_order+1).' where action_id = '.$action_id.' and element_id = '.$values[$old_order];
        //для следующего
        $q2 = 'update shop_cat_element_actions set a_order_id = '.$old_order.' where action_id = '.$action_id.' and element_id = '.$values[$old_order+1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

     }
    } else $user->no_rules('action');
   }

  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
        mysql_query("delete from shop_cat_element_actions where action_id = $action_id and element_id = $element_id");
        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
        $_SESSION['smart_tools_refresh'] = 'enable';
     }
     else $user->no_rules('delete');
   }

  }

if (isset($_GET['delete_img']) && isset($_GET['id'])) {
    if ($user->check_user_rules('delete')) {
        $action_id = (int)$_GET['id'];
        $result = mysql_query("select img_path from shop_cat_actions where action_id = $action_id");
        $row = mysql_fetch_array($result);
        if (!use_file($row['img_path'], 'shop_cat_actions', 'img_path')) unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);
        
        $result = mysql_query("update shop_cat_actions set img_path = '' where action_id = $action_id");
        
        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
        $_SESSION['smart_tools_refresh'] = 'enable';
    } else $user->no_rules('delete');
 }
//-----------------------------------------------------------------------------
// AJAX

function search_elements($str, $element_id)
{
  $objResponse = new xajaxResponse();
  $str = mb_strtolower(trim($str), 'UTF-8');

  $text = ''; 
  $result = mysql_query("   select
                            *
                            from
                            shop_cat_elements
                            where
                            type = 0 and
                            (
                                element_name like '%$str%' or
                                store_name like '%$str%' or
                                c_store_name like '%$str%' or
                                element_id like '%$str%' or
                                description like '%$str%' or
                                description_full like '%$str%' or
                                description_extra like '%$str%'
                            )");
  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellspacing="0" cellpadding="0">';
     while ($row = mysql_fetch_array($result))
      {
        $similar_element_id = $row['element_id'];
        $text .= '<tr><td><input type="checkbox" name="action_element_'.$similar_element_id.'"';

        $res = mysql_query("select * from shop_cat_element_elements where element_id = $element_id and similar_element_id = $similar_element_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_delete_record(\'element\','.$similar_element_id.','.$element_id.');"';
         }
        else $text .= ' onclick="xajax_add_element('.$similar_element_id.','.$element_id.');"';
        $text .= '></td><td><label for="action_element_'.$similar_element_id.'"> &nbsp; '.htmlspecialchars($row['element_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
    }
  else $text .= '<p align="center">Нет товаров</p>';

  $objResponse->assign("elements","innerHTML",$text);
  return $objResponse;
}

function text2url($str) {
	$objResponse = new xajaxResponse();

    $rus = array(   '',
                    'а','б','в','г','д','е','ё','ж','з','и','й','к',
                    'л','м','н','о','п','р','с','т','у','ф','х','ц',
                    'ч','ш','щ','ь','ы','ъ','э','ю','я',
                    ' ');
    $eng = array(   '',
                    'a','b','v','g','d','e','e','zh', 'z','i','y','k',
                    'l','m','n','o','p','r','s','t','u','f','h','c',
                    'ch','sh','shch','', 'y', '','e', 'yu','ya',			      
                    '-');
    $stop_chars = array('/', '\'', '"', '`', '(', ')', '[', ']', '{', '}',
                        '|', '~', '!', '?', '&', '+', '^', '%', '$',
                        '#', ':', ';', '<', '>', '.', ',', '\\', '=', '*',
                        '№');

    $str = trim($str);
    
    //двойные пробелы и мусор
    $str = preg_replace('/[\s]{2,}/', '', $str);
    
    //фильтрация символов
    $input = array();
    for($i = 0; $i < mb_strlen($str, 'UTF-8'); $i++) {
        $char = mb_strtolower(mb_substr($str, $i, 1, 'UTF-8'), 'UTF-8');
        if(!in_array($char, $stop_chars))
            $input[] = $char;
    }

    //перевод
    $out = '';
    foreach($input as $char) {
        $pos = array_search($char, $rus);
        $out .= (($pos) ? $eng[$pos] : $char);
    }

	$objResponse->assign('url', 'value', $out);
	return $objResponse;  
}

$xajax->registerFunction("text2url");
$xajax->registerFunction("search_elements");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
 
        $action_id = (int)$_GET['id'];
        $result = mysql_query(" select
                                *,
                                date_format(date_begin, '%d.%m.%Y') as date1,
                                date_format(date_end, '%d.%m.%Y') as date2
                                from
                                shop_cat_actions
                                where
                                action_id = $action_id");

        if (!$result) exit();
        $row = mysql_fetch_object($result);

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

        echo '<form enctype="multipart/form-data" action="?id='.$action_id.'" method="post">
        <table cellpadding="4" cellspacing="1" border="0" class="form">
            <tr>
                <td>Название <sup class="red">*</sup></td>
                <td><input style="width:280px" type="text" name="action_name" value="'.htmlspecialchars($row->action_name).'" maxlength="255"></td>
                <td><button type="button" onclick="xajax_text2url(this.form.action_name.value)">► URL</button></td>
            </tr>
            <tr>
                <td>Описание</td>
                <td><input style="width:280px" type="text" name="action_descr" value="'.htmlspecialchars($row->action_descr).'" maxlength="255"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>URL <sup class="red">*</sup></td>
                <td><input style="width:280px" type="text" name="url" id="url" value="'.htmlspecialchars($row->url).'" maxlength="255"/></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Заголовок страницы сайта<br /><span class="grey">TITLE</span></td>
                <td><input style="width:280px" type="text" name="title" value="'.htmlspecialchars($row->title).'" maxlength="255"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Ключевые слова<br /><span class="grey">meta keyrords</span></td>
                <td><input style="width:280px" type="text" name="meta_keywords" value="'.htmlspecialchars($row->meta_keywords).'" maxlength="255"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Описание<br /><span class="grey">meta description</span></td>
                <td><input style="width:280px" type="text" name="meta_description" value="'.htmlspecialchars($row->meta_description).'" maxlength="255"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Фотография</td>
                <td>
                    <table cellspacing="0" cellpadding="0">
                        <tr><td><input style="width:280px" type="file" name="picture"></td><td>';
                        if ($row->img_path)
                            {
                            echo '<a href="';
                            echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=true&id=$action_id';}";
                            echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
                            }
                        echo '</td></tr></table></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Дата начала</td>
                <td><input type="text" name="date_begin" class="datepicker" value="'.htmlspecialchars($row->date1).'"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Дата завершения</td>
                <td><input type="text" name="date_end" class="datepicker" value="'.htmlspecialchars($row->date2).'"></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Скидка</td>
                <td>
                <select name="action_value">
                <option value="0">---НЕТ---</option>';
                for ($i = 5; $i <= 100; $i += 5)
                {
                echo '<option value="'.$i.'"';
                if ($i == $row->action_value) echo ' selected';
                echo '>'.$i.'%</option>';
                }
                echo '</select>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Список товаров</td>
                <td>';
        
            $res = mysql_query("    select
                                    *
                                    from
                                    shop_cat_elements, shop_cat_element_actions
                                    where shop_cat_element_actions.action_id = $action_id and
                                    shop_cat_element_actions.element_id = shop_cat_elements.element_id
                                    order by shop_cat_element_actions.a_order_id asc");
        $i = 1;
        if (mysql_num_rows($res) > 0)
            {
            echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
            while ($r = mysql_fetch_array($res))
            {
                echo '<tr><td width="100%"><span class="grey">'.htmlspecialchars($row['element_name']).'</span> &nbsp; </td>';
        
                //если элемент первый на определенном уровне, блокируем стрелку "вверх"
                echo '<td nowrap>';
                if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
                else echo '<a href="?id='.$action_id.'&element_id='.$r['element_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
        
                if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
                else echo '<a href="?id='.$action_id.'&element_id='.$r['element_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
        
                echo '<a href="';
                echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$action_id&element_id=".$r['element_id']."&action=delete';}";
                echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
        
                $i++;
            }
            echo '</table>';
            }
        else
            echo 'Нет товаров';
        
        echo ' </td>
                <td>&nbsp;</td>
            </tr>
            </table><br>
        <button type="SUBMIT">Сохранить</button>
        </form>';

    } else $user->no_rules('view');
} else
    echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>