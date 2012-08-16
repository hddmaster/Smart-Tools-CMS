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
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['date'])=='' || trim($_POST['hour'])=='' || trim($_POST['minute'])=='' || trim($_POST['second'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $pub_id = (int)$_GET['id'];
   $parent_id = $_POST['parent_id'];
   $order_id = ((int)$_POST['order_id'] > 0 ? (int)$_POST['order_id'] : 0);

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

   $head = mysql_real_escape_string(trim($_POST['head']));
   $title = mysql_real_escape_string(trim($_POST['title']));
   $meta_keywords = mysql_real_escape_string(trim($_POST['meta_keywords']));
   $meta_description = mysql_real_escape_string(trim($_POST['meta_description']));
   $source = mysql_real_escape_string(trim($_POST['source']));
   $source_url = mysql_real_escape_string(trim($_POST['source_url']));
   $tags = mysql_real_escape_string(trim($_POST['tags']));
   $url = mysql_real_escape_string(trim($_POST['url']));
   
   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   $hour_begin = intval($_POST['hour_begin']); if ($hour_begin > 23) $hour_begin = 00; if ($hour_begin < 10) $hour_begin = '0'.$hour_begin;
   $minute_begin = intval($_POST['minute_begin']); if ($minute_begin > 59) $minute_begin = 00; if ($minute_begin < 10) $minute_begin = '0'.$minute_begin;
   $second_begin = intval($_POST['second_begin']); if ($second_begin > 59) $second_begin = 00; if ($second_begin < 10) $second_begin = '0'.$second_begin;
   $date_begin = substr($_POST['date_begin'],6,4).substr($_POST['date_begin'],3,2).substr($_POST['date_begin'],0,2).$hour_begin.$minute_begin.$second_begin;

   $result = mysql_query("select * from publications where pub_id=$pub_id");
   $row = mysql_fetch_array($result);
   $img_path = $row['img_path'];
   $is_rating = $_POST['is_rating'];
   $is_commentation = $_POST['is_commentation'];

//если есть картинка, проверяем её тип
  if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
   {
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
     if (!use_file($img_path,'publications','img_path'))
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
  $user_file_name =  $name.'.jpg';

   }

  //Обновляем содержимое...
  if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name']))
   {
    $result = mysql_query("update publications set img_path='/userfiles/pub_images/$user_file_name' where pub_id=$pub_id");
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
   
    $result = mysql_query(" update
                            publications
                            set
                            date = '$date',
                            date_begin = '$date_begin',
                            head = '$head',
                            title = '$title',
                            meta_keywords = '$meta_keywords',
                            meta_description = '$meta_description',
                            source = '$source',
                            source_url = '$source_url',
                            parent_id = $parent_id,
                            user_id = $user_id,
                            is_rating = $is_rating,
                            is_commentation = $is_commentation,
                            tags = '$tags',
                            url = '$url',
                            order_id = $order_id
                            where pub_id=$pub_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id&message=db"); exit();}

  if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$user_file_name";
     copy($_FILES['picture']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture']['type']));
     chmod($filename,0666);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$pub_id");
  exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $pub_id = (int)$_GET['id'];
  $result = mysql_query("select img_path from publications where pub_id=$pub_id");
  $row = mysql_fetch_array($result);
  if (!use_file($row['img_path'],'publications','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);

  $result = mysql_query("update publications set img_path='' where pub_id=$pub_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  $_SESSION['smart_tools_refresh'] = 'enable';

  } else $user->no_rules('delete');
 }
//-----------------------------------------------------------------------------
// AJAX

function show_users($parent_id, $user_id)
 {
   $objResponse = new xajaxResponse();
   $select_users = '<select name="user_id" style="width:280px;" size="5">';
   $result = mysql_query("select * from auth_site where type = 0 and parent_id = $parent_id order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $select_users .= '<option value="0">---НЕТ---</option>';
      while ($row = mysql_fetch_array($result))
         $select_users .= '<option value="'.$row['user_id'].'"'.(($user_id == $row['user_id']) ? ' selected' : '').'>'.htmlspecialchars($row['username']).' (id: '.$row['user_id'].')</option>';
    }
   else $select_users .= '<option value="">Нет пользователей</option>';
   $select_users .= '</select>';
   $objResponse->assign("users","innerHTML",$select_users);
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
$xajax->registerFunction("show_users");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

  function show_select($parent_id = 0, $prefix = '',$parent_id_element)
  {
    global $options;
    $result = mysql_query("select * from publications where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['pub_id'].'"';
          if ($parent_id_element == $row['pub_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";

          show_select($row['pub_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

 function show_select_users($parent_id = 0, $prefix = '', $group_id = 0)
  {
    global $options;
    $result = mysql_query("select * from auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($group_id == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>'."\n";
          show_select_users($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

   $pub_id = (int)$_GET['id'];
   $result = mysql_query("select
                          P.*,
                          date_format(P.date, '%d.%m.%Y (%H:%i:%s)') as date,
                          date_format(P.date_begin, '%d.%m.%Y (%H:%i:%s)') as date_begin,
                          U.type as is_group,
                          U.parent_id as group_id
                          from
                          publications as P left join auth_site as U
                          on P.user_id = U.user_id
                          where P.pub_id=$pub_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);
   
   $hour = substr($row->date,12,2);
   $minute = substr($row->date,15,2);
   $second = substr($row->date,18,2);
   $date = substr($row->date,0,10);

   $hour_begin = substr($row->date_begin,12,2);
   $minute_begin = substr($row->date_begin,15,2);
   $second_begin = substr($row->date_begin,18,2);
   $date_begin = substr($row->date_begin,0,10);

   $group_id = (($row->is_group) ? $row->user_id : $row->group_id);
   
echo '<h2 class="nomargins">'.htmlspecialchars($row->head).'</h2><div>&nbsp;</div>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_pub.php')) $tabs->add_tab('/admin/editors/edit_pub.php?id='.$pub_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_pub_text.php')) $tabs->add_tab('/admin/editors/edit_pub_text.php?id='.$pub_id.'&mode=brief', 'Краткое описание');
if ($user->check_user_rules('view','/admin/editors/edit_pub_text.php')) $tabs->add_tab('/admin/editors/edit_pub_text.php?id='.$pub_id.'&mode=full', 'Подробное описание');
if ($user->check_user_rules('view','/admin/editors/edit_pub_users.php')) $tabs->add_tab('/admin/editors/edit_pub_users.php?id='.$pub_id, 'Область видимости');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form enctype="multipart/form-data" action="?id='.$pub_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок</td>
      <td>
      <input style="width:280px" type="text" name="head" value="'.htmlspecialchars($row->head).'" maxlength="255"></td>
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
      <td>Источник</td>
      <td>
       <input style="width:280px" type="text" name="source" value="'.htmlspecialchars($row->source).'" maxlength="255">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>URL источника</td>
      <td>
       <input style="width:280px" type="text" name="source_url" value="'.htmlspecialchars($row->source_url).'" maxlength="255">
      </td>
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
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.$date.'"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value="'.$hour.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value="'.$minute.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value="'.$second.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата начала публикации <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date_begin" class="datepicker" value="'.$date_begin.'"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Время начала публикации <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour_begin" value="'.$hour_begin.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute_begin"  value="'.$minute_begin.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second_begin" value="'.$second_begin.'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0"'.(($row->parent_id == 0) ? ' selected' : '').'>---Корень каталога---</option>
            '.show_select(0, '', $row->parent_id).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Автор</td>
      <td>
         <select name="group_id" style="width:280px;" onchange="xajax_show_users(this.form.group_id.options[this.form.group_id.selectedIndex].value);">
            <option value="0"'.(($group_id == 0) ? ' selected' : '').'>---НЕТ---</option>'.
         show_select_users(0, '', $group_id)
         .'</select>'; global $options; $options = ''; echo '<div id="users">'.(($row->user_id) ? '<p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>' : '').'</div>
      </td>
      <td>&nbsp;</td>
      </tr>
    <tr>
      <td>Участвуйет в рейтинге</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Комментирование</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Тэги</td>
        <td><input style="width:280px" type="text" name="tags" value="'.htmlspecialchars($row->tags).'" maxlength="255"></td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Порядок сортировки в текущей группе</td>
        <td><input style="width:280px" type="text" name="order_id" value="'.htmlspecialchars($row->order_id).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td>
        <td>&nbsp;</td>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';

  if($row->user_id) echo '<script>setTimeout("xajax_show_users('.$group_id.', '.(($row->user_id) ? $row->user_id : 0).');", 2000);</script>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>