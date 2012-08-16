<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['head']))
 {
   if ($user->check_user_rules('add'))
   {

   $head = mysql_real_escape_string(trim($_POST['head']));
   $url = mysql_real_escape_string(trim($_POST['url']));
   $parent_id = ((isset($_POST['parent_id']) && (int)$_POST['parent_id'] >= 0) ? (int)$_POST['parent_id'] : 0 );

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   $hour_begin = intval($_POST['hour_begin']); if ($hour_begin > 23) $hour_begin = 00; if ($hour_begin < 10) $hour_begin = '0'.$hour_begin;
   $minute_begin = intval($_POST['minute_begin']); if ($minute_begin > 59) $minute_begin = 00; if ($minute_begin < 10) $minute_begin = '0'.$minute_begin;
   $second_begin = intval($_POST['second_begin']); if ($second_begin > 59) $second_begin = 00; if ($second_begin < 10) $second_begin = '0'.$second_begin;
   $date_begin = substr($_POST['date_begin'],6,4).substr($_POST['date_begin'],3,2).substr($_POST['date_begin'],0,2).$hour_begin.$minute_begin.$second_begin;

   $img_path_db = '';
   if(isset($_FILES['picture']['name']) &&
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
         default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
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
      $img_path_db = "/userfiles/pub_images/$user_file_name";
    }

    //Добавляем...
    $query = "insert into publications (parent_id, date, head, url, img_path, user_id)
                                       values
                                       ($parent_id, '$date', '$head', '$url', '$img_path_db', $user_id)";
    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

    if (isset($_FILES['picture']['name']) &&
        is_uploaded_file($_FILES['picture']['tmp_name']))
     {
       $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$user_file_name";
       copy($_FILES['picture']['tmp_name'], $filename);
       resize($filename, basename($_FILES['picture']['type']));
       chmod($filename, 0666);
     }

   // перенумеровываем
   $result = mysql_query("select * from publications where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['pub_id'];
         mysql_query("update publications set order_id=$i where pub_id = $id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

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
   $elements = array();
   if (is_array($_GET['id'])) $elements = $_GET['id'];
   else $elements[] = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
          foreach ($elements as $pub_id)
          {
         //запоминаем имя файла и удаляем его
         $result = mysql_query("select * from publications where pub_id=$pub_id");
         if (mysql_num_rows($result) > 0)
          {
            $row = mysql_fetch_array($result);

            if ($row['img_path'])
             {
               $filename = $row['img_path'];
               if (!use_file($filename,'publications','img_path'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }
          }

         //удаляем из бд
        $result = mysql_query("delete from publications where pub_id=$pub_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
          }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         foreach ($elements as $pub_id)
           mysql_query("update publications set status=1 where pub_id=$pub_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         foreach ($elements as $pub_id)
           mysql_query("update publications set status=0 where pub_id=$pub_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
 
//-----------------------------------------------------------------------------
// AJAX

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

	$objResponse->assign('element_url', 'value', $out);
	return $objResponse;  
}

$xajax->registerFunction("text2url");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Публикации</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/publications.php')) $tabs->add_tab('/admin/publications.php', 'Публикации');
if ($user->check_user_rules('view','/admin/pub_groups.php')) $tabs->add_tab('/admin/pub_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/pub_structure.php')) $tabs->add_tab('/admin/pub_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/pub_comments.php')) $tabs->add_tab('/admin/pub_comments.php', 'Комментарии');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("select * from publications where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['pub_id'].'"';
          if ($parent_id_added == $row['pub_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";
          show_select($row['pub_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
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

 function show_select_filter($parent_id = 0, $prefix = '', $parent_id_element = '')
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
          show_select_filter($row['pub_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

 $parent_id_added = 0; if (isset($_GET['parent_id'])) $parent_id_added = $_GET['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить публикацию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок</td>
      <td>
       <input style="width:280px" type="text" name="head" maxlength="255">
      </td>
      <td><button type="button" onclick="xajax_text2url(this.form.head.value)">► URL</button></td>
    </tr>
    <tr>
      <td>URL <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="url" id="url" maxlength="255"/></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture"></td></tr>
      <td>&nbsp;</td>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.date('d.m.Y').'"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value="'.date("H").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute" value="'.date("i").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value="'.date("s").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата начала публикации <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date_begin" class="datepicker" value="'.date('d.m.Y').'"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Время начала публикации <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour_begin" value="'.date("H").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute_begin"  value="'.date("i").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second_begin" value="'.date("s").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td>
          <select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0">---НЕТ---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td>&nbsp;</td>
    </tr>
   </table><div>&nbsp;</div>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

global $options; $options = '';
$parent_id = -1; if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '') $parent_id = (int)$_GET['parent_id_filter'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td nowrap>
   <form action="" method="GET">

   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td nowrap>Фильтр по группе</td>
      <td><select name="parent_id_filter" style="width:280px;">
            <option value="">---Весь каталог---</option>
            <option value="0"'; if (isset($_GET['parent_id_filter']) && $parent_id == 0) echo ' selected'; echo'>---Корень каталога---</option>
            '.show_select_filter(0,'',$parent_id).'
          </select>
      </td>
      <td><button type="SUBMIT">OK</button></td>
    </tr>
  </table>
   </td>

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
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'date');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = strtolower(trim($_GET['query_str']));
   $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';
   $add .= " and (P.pub_id like '$query_str' or
                  P.head like '$query_str' or
                  P.text like '$query_str' or
                  P.text_full like '$query_str')";
 }

if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '')
 {
   $add .= " and P.parent_id = ".$_GET['parent_id_filter'];
   $params['parent_id_filter'] = $_GET['parent_id_filter'];
 }
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

    $query = "  select
                P.*,
                date_format(P.date, '%d.%m.%Y (%H:%i:%s)') as date_f,
                A.username
                from publications as P left join auth_site as A on P.user_id = A.user_id
                where P.type = 0 $add";
    $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
    $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
   navigation($page, $per_page, $total_rows, $params);
      echo '<div class="databox"><form id="form" action="" method="get"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
      echo '<tr align="center" class="header">
              <td align="left" nowrap width="80"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"> №&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=pub_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'pub_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=pub_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'pub_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Дата&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Группа</td>
              <td nowrap>Заголовок&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td width="35">&nbsp;</td>
              <td>Пользователь сайта</td>
              <td width="120">&nbsp;</td>
            </tr>';

      while ($row = mysql_fetch_array($result))
       {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
                 <td align="left" class="small" nowrap><input class="cbx" type="checkbox" name="id[]" value="'.$row['pub_id'].'"> '.$row['pub_id'].'</td>
                 <td align="center">'.$row['date_f'].'</td>
           <td>';
           if ($row['parent_id'] == 0) echo '---Корень каталога---';
           else
            {
              $res = mysql_query("select * from publications where pub_id = ".$row['parent_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo htmlspecialchars($r['head']);
               }
              else echo '&nbsp;';
            }
           echo '</td>
                 <td>'; if ($row['head']) echo htmlspecialchars($row['head']); else echo '&nbsp;'; echo '</td>
                 <td align="center">'; if ($row['img_path']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" border="0">'; else echo '&nbsp;'; echo '</td>
                 <td align="center">'.(($row['username']) ? htmlspecialchars($row['username']) : '&nbsp;').'</td>
                 <td nowrap align="center">
                 <a href="javascript:sw(\'/admin/editors/edit_pub.php?id='.$row['pub_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
                 &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
                 &nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
               </tr>';
   }
  echo '</table>';
  echo '<input type="hidden" name="action" id="action" value="">
        <table cellspacing="0" cellpadding="4">
         <tr>
           <td style="padding-left: 6px;"><img src="/admin/images/tree/2.gif" alt=""></td>
           <td class="small" nowrap>с отмеченными:</td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'activate\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb.png" alt="Включить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'reserve\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb-off.png" alt="Выключить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'del\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>
         </tr>
        </table></form>';  
  echo '</div>';
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>