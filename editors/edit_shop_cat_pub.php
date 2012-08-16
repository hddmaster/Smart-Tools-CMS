<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['date']) &&
   isset($_POST['hour']) &&
   isset($_POST['minute']) &&
   isset($_POST['second']) &&
   isset($_POST['head']) &&
   isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {
        if (trim($_POST['date'])=='' || trim($_POST['hour'])=='' || trim($_POST['minute'])=='' || trim($_POST['second'])=='') {
            header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
            exit();
        }

        $pub_id = (int)$_GET['id'];
        $producer_id = ((isset($_POST['producer_id']) && $_POST['producer_id'] > 0) ? $_POST['producer_id'] : 0);
        $head = trim($_POST['head']);
        $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
        $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
        $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
        $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;
        $title = trim($_POST['title']);
        $meta_keywords = trim($_POST['meta_keywords']);
        $meta_description = trim($_POST['meta_description']);
        $url = trim($_POST['url']);

        $result = mysql_query("select * from shop_cat_publications where pub_id=$pub_id");
        $row = mysql_fetch_array($result);
        $img_path = $row['img_path'];

        //если есть картинка, проверяем её тип
        if (isset($_FILES['picture']['name']) &&
            is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $user_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
            $type = basename($_FILES['picture']['type']);

  switch ($type)
   {
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
     if (!use_file($img_path,'shop_cat_publications','img_path'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path);
   }

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name =  $name.'.'.$ext;

   }

  //проверка на повторы отсутствует, т.к. новости часто могут дублироваться...

  //Обновляем содержимое...

  if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name']))
   {
    $result = mysql_query("update shop_cat_publications set img_path='/userfiles/pub_images/$user_file_name' where pub_id=$pub_id");
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
    $result = mysql_query(" update
                            shop_cat_publications
                            set
                            date='$date',
                            head='$head',
                            title = '$title',
                            meta_keywords = '$meta_keywords',
                            meta_description = '$meta_description',
                            url = '$url',
                            producer_id = $producer_id
                            where
                            pub_id=$pub_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id&message=db"); exit();}

  if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$user_file_name";
     copy($_FILES['picture']['tmp_name'], $filename);
     chmod($filename,0666);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id");
  exit();
  } else $user->no_rules('edit');
 }


if (    isset($_GET['element_id']) &&
        isset($_GET['id']) &&
        !isset($_GET['action'])) {
    if ($user->check_user_rules('add')) {
    
        $pub_id = (int)$_GET['id'];		
        $element_id = $_GET['element_id'];
        
        mysql_query("
                        insert into shop_cat_publication_elements
                        (
                            pub_id,
                            element_id
                        )
                        values
                        (
                            $pub_id,
                            $element_id
                        )
                        ");

        $cache = new Cache; $cache->clear_cache_by_module();
        $_SESSION['smart_tools_refresh'] = 'enable';
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id"); exit();
    } else $user->no_rules('add');
}

if (    isset($_GET['action']) && $_GET['action'] !=='' &&
        isset($_GET['element_id']) && $_GET['element_id']!=='' &&
        isset($_GET['id']) && $_GET['id']!=='') {
    $pub_id = (int)$_GET['id'];
    $element_id = $_GET['element_id'];
    $action = $_GET['action'];
    
    if ($action == 'delete') {
        if ($user->check_user_rules('delete')) {
            mysql_query("delete from shop_cat_publication_elements where pub_id = $pub_id and element_id = $element_id");

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
            $_SESSION['smart_tools_refresh'] = 'enable';
        } else
            $user->no_rules('delete');
    }

}


if (isset($_GET['delete_img']) && isset($_GET['id'])) {
    if ($user->check_user_rules('delete')) {
        $pub_id = (int)$_GET['id'];
        $result = mysql_query("select img_path from shop_cat_publications where pub_id=$pub_id");
        $row = mysql_fetch_array($result);
        if (!use_file($row['img_path'],'shop_cat_publications','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);

        $result = mysql_query("update shop_cat_publications set img_path='' where pub_id=$pub_id");

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
        $_SESSION['smart_tools_refresh'] = 'enable';
    } else $user->no_rules('delete');
}

//-----------------------------------------------------------------------------
// AJAX

function find_elements($pub_id, $str) {
    $objResponse = new xajaxResponse();
    $query = '%'.mb_strtolower(trim($str), 'UTF-8').'%';
    $text = '';

    $q = "  select
            E.*
            from
            shop_cat_elements as E left join shop_cat_producers as P
            on E.producer_id = P.producer_id
            where
                (
                    E.element_id like '$query' or
                    E.element_name like '$query' or
                    E.store_name like '$query' or
                    E.c_store_name like '$query' or
                    E.producer_store_name like '$query' or
                    E.description like '$query' or
                    E.description_full like '$query' or
                    P.producer_name like '$query' or
                    P.producer_descr like '$query' or
                    P.producer_url like '$query'
                )
            order by E.type desc, E.element_name asc
            limit 10";
    $result = mysql_query($q);
    if(mysql_num_rows($result) > 0) {
        $text .= '  <table>
                        <tr>
                            <th>№</th>
                            <th>Название</th>
                            <th>Артикул</th>
                            <th>Артикул 1С</th>
                            <th>Артикул производителя</th>
                            <th>Цена 1</th>
                            <th>Цена 2</th>
                            <th>Цена 3</th>
                            <th>Цена 4</th>
                            <th>Цена 5</th>
                            <th>Добавить</th>
                        </tr>';
        while($row = mysql_fetch_object($result)) {
            $text .= '  <tr>
                            <td align="center">'.$row->element_id.'</td>
                            <td><a href="?id='.$pub_id.'&element_id='.$row->element_id.'">'.($row->type == 1 ? '<strong>'.htmlspecialchars($row->element_name).'</strong>' : htmlspecialchars($row->element_name)).'</a></td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->store_name).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->c_store_name).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->producer_store_name).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->price1).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->price2).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->price3).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->price4).'</td>
                            <td>'.($row->type == 1 ? '&nbsp;' : $row->price5).'</td>
                            <td align="center"><a href="?id='.$pub_id.'&element_id='.$row->element_id.'"><img src="/admin/images/icons/plus.png" alt="Добавить"></a></td>
                        </tr>';
        }
        $text .= '</table>';        
    } else {
        $text .= '<div>поиск не дал результатов</div>';
    }

    $objResponse->assign('elements', 'innerHTML', $text);
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

$xajax->registerFunction("find_elements");
$xajax->registerFunction("show_elements");
$xajax->registerFunction("text2url");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
        $pub_id = (int)$_GET['id'];
        $result = mysql_query(" select
                                *,
                                date_format(date, '%d.%m.%Y (%H:%i:%s)') as date
                                from
                                shop_cat_publications
                                where
                                pub_id = $pub_id");
        if (!$result) exit();
        $row = mysql_fetch_object($result);
        
        $element_id = 0;
        $parent_id = 0;
        $date = $row->date;
        $hour = substr($date,12,2);
        $minute = substr($date,15,2);
        $second = substr($date,18,2);
        $date = substr($date,0,10);
   
        $res = mysql_query("select * from shop_cat_elements where element_id = $row->element_id");
        if (mysql_num_rows($res)) {
            $r = mysql_fetch_array($res);
            if ($r['type'] == 0) {$element_id = $row->element_id; $parent_id = $r['parent_id'];}
            else $parent_id = $row->element_id;
            
            $type = $r['type'];
        }
    
        if($row->img_path) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path).'" border="0"></p>';

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

        ?>
        <style>
            #query {
                border: #ccc 1px solid;
                background: none;
                width: 100%;
            }
            
            #elements {
                margin: 10px 0px;
                width: 100%;
            }

            #elements table {
                border-collapse: collapse;
                border: none;
                empty-cells: show;
                width: 100%;
            }

            #elements table tr {
            }
            
            #elements table tr th {
                padding: 4px;
                border: #eee 1px solid;
                background: #efefef;
            }

            #elements table tr td {
                padding: 4px;
                border: #eee 1px solid;
            }


        </style>
        
        <script type="text/javascript">
            var default_value = 'добавить товар или группу к публикации: поиск по ключевому слову';
			$(document).ready(function() {
				$('input[name="pickup"]').click(function() {
					if(this.checked) {
						$('select[name=delivery_id]').attr('disabled','disabled');
						$('input[name=delivery_extra_name]').attr('disabled','disabled');
						$('input[name=delivery_extra_price]').attr('disabled','disabled');
					}else{
						$('select[name=delivery_id]').removeAttr('disabled');
						$('input[name=delivery_extra_price]').removeAttr('disabled');
					}
				});
			});
        </script>
    
        <div style="border-top: #ccc 1px dashed; clear: both;">&nbsp;</div>
        <input type="text" id="query" name="query" value="добавить товар или группу к публикации: поиск по ключевому слову" onclick="if(this.value == default_value) this.value = '';" onfocus="if(this.value == default_value) this.value = '';" onkeyup="if(this.value.length > 2) xajax_find_elements(<?=$pub_id?>, this.value);" style="width: 100%;">
        <div id="elements" style="width: 100%;"></div>
        <div style="border-bottom: #ccc 1px dashed; clear: both;">&nbsp;</div><div>&nbsp;</div>
        <?

        echo '<form enctype="multipart/form-data" action="?id='.$pub_id.'" method="post">
            <table cellpadding="4" cellspacing="1" border="0" class="form">
                <tr>
                    <td>Заголовок</td>
                    <td><input style="width:280px" type="text" name="head" value="'.htmlspecialchars($row->head).'" maxlength="255"></td>
                    <td><button type="button" onclick="xajax_text2url(this.form.head.value)">► URL</button></td>
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
                    <td><table cellspacing="0" cellpadding="0">
                    <tr><td><input style="width:280px" type="file" name="picture"></td><td>';
                    if ($row->img_path)
                        {
                        echo '<a href="';
                        echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$pub_id';}";
                        echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
                        }
                    echo '</td></tr></table></td>
                            <td>&nbsp;</td>
                </tr>
            <tr>
                <td>Дата <sup class="red">*</sup></td>
                <td>';
                ?>
                    <script>
                    LSCalendars["date"]=new LSCalendar();
                    LSCalendars["date"].SetFormat("dd.mm.yyyy");
                    LSCalendars["date"].SetDate("<?=$date?>");
                    </script>
                    <table cellspacing="0" cellpadding="0">
                    <tr>
                    <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=$date?>" name="date"></td>
                    <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
                    </tr>
                    </table>
                    <div id="datePtr" style="width: 1px; height: 1px;"></div>
                <?
                echo '      </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
                    <td>
                        <input type="text" name="hour" value="'.$hour.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
                        <input type="text" name="minute"  value="'.$minute.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
                        <input type="text" name="second" value="'.$second.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
                    </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Связанные группы и товары</td>
                    <td>';

                    $res = mysql_query(" select
                                            shop_cat_publication_elements.element_id,
                                            shop_cat_elements.element_name,
                                            shop_cat_elements.store_name,
                                            shop_cat_elements.type
                                            from
                                            shop_cat_elements, shop_cat_publication_elements
                                            where
                                            shop_cat_publication_elements.pub_id = $pub_id and
                                            shop_cat_publication_elements.element_id = shop_cat_elements.element_id
                                            order by shop_cat_elements.type desc, shop_cat_elements.element_name asc");
                             
                    if (mysql_num_rows($res) > 0) {
                        $i = 1; 
                        echo '<table cellspacing="0" cellpadding="0" border="0">';
                        while($r = mysql_fetch_array($res)) {
                            echo  '<tr><td><span class="grey">'.($r['type'] == 1 ? '<strong>'.htmlspecialchars($r['element_name']).'</strong>' : htmlspecialchars($r['element_name']));
                            echo ' &nbsp; (id: '.$r['element_id'].')';
                            echo '</span></td><td>';
                            echo '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=delete&id='.$pub_id.'&element_id='.$r['element_id'].'\';} return false;"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td></tr>';
                            $i++;           
                        }
                        echo '</table>';
                    }
                    else echo '<p align="center">Нет товаров и групп</p>';
                    
                    echo '</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Производитель</td>
                    <td>';
                    $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
                    if (mysql_num_rows($res) > 0) {
                        echo '<select name="producer_id" style="width:280px;"><option value="0">---НЕТ---</option>';
                        while ($r = mysql_fetch_array($res))
                        {
                            echo '<option value="'.$r['producer_id'].'"';
                            if ($row->producer_id == $r['producer_id']) echo ' selected';
                            echo '>'.htmlspecialchars($r['producer_name']);
                            if ($r['producer_descr']) echo '&nbsp; ('.htmlspecialchars($r['producer_descr']).')';
                            echo '</option>';
                        }
                        echo '</select>'; 
                    }
                    echo '</td>
                    <td>&nbsp;</td>
                </tr>   
            </table><br>
            <button type="SUBMIT">Сохранить</button>
            </form>';
  
        if($element_id) echo '<script>setTimeout("xajax_show_elements('.$parent_id.', '.$element_id.');", 2000);</script>';
  
    } else $user->no_rules('view');
} else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>