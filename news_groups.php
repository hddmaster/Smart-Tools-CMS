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

   if (trim($_POST['head'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $head = $_POST['head'];
   $parent_id = $_POST['parent_id'];

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

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
      while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/news_images/$name.$ext"))
       {
         $name = $name_clear." ($i)";
         $i ++;
       }
      $user_file_name =  $name.'.jpg';
      $img_path_db = "/userfiles/news_images/$user_file_name";
    }

    $date = date("YmdHis");

    //Добавляем...
    $query = "insert into news (parent_id, type, date, head, img_path, user_id)
                                values
                               ($parent_id, 1, '$date', '$head', '$img_path_db', $user_id)";
    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

    if (isset($_FILES['picture']['name']) &&
        is_uploaded_file($_FILES['picture']['tmp_name']))
     {
       $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/news_images/$user_file_name";
       copy($_FILES['picture']['tmp_name'], $filename);
       resize($filename, basename($_FILES['picture']['type']));
       chmod($filename,0666);
     }

   // перенумеровываем
   $result = mysql_query("select * from news where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['news_id'];
         mysql_query("update news set order_id=$i where news_id = $id");
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
   $news_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
      $result = @mysql_query("select * from news where parent_id=$news_id");
      if (@mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
      else
       {
           $result = mysql_query("select * from news where news_id=$news_id");
           $row = mysql_fetch_array($result);
           if($row['img_path'])
             {
               $filename = $row['img_path'];
               if(!use_file($filename,'news','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

           //удаляем из gallery
           $result = mysql_query("delete from news where news_id=$news_id");
           if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
       }

      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update news set status=1 where news_id=$news_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update news set status=0 where news_id=$news_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
 
//-----------------------------------------------------------------------------
// AJAX

function show_users($parent_id)
 {
   $objResponse = new xajaxResponse();
   $select_users = '<select name="user_id" style="width:280px;" size="14">';
   $result = mysql_query("select * from auth_site where type = 0 and parent_id = $parent_id order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $select_users .= '<option value="0">---НЕТ---</option>';
      while ($row = mysql_fetch_array($result))
         $select_users .= '<option value="'.$row['user_id'].'">'.htmlspecialchars($row['username']).' (id: '.$row['user_id'].')</option>';
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

	$objResponse->assign('element_url', 'value', $out);
	return $objResponse;  
}

$xajax->registerFunction("text2url");
$xajax->registerFunction("show_users");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Новости</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/news.php')) $tabs->add_tab('/admin/news.php', 'Новости');
if ($user->check_user_rules('view','/admin/news_groups.php')) $tabs->add_tab('/admin/news_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/news_structure.php')) $tabs->add_tab('/admin/news_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/news_import.php')) $tabs->add_tab('/admin/news_import.php', 'Импорт');
if ($user->check_user_rules('view','/admin/news_comments.php')) $tabs->add_tab('/admin/news_comments.php', 'Комментарии');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("select * from news where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['news_id'].'">'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";
          show_select($row['news_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
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

if (isset($_GET['message']))
 {
   $message = new Message;
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

echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="0" cellspacing="0" border="0"><tr valign="top"><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="head" maxlength="255"></td>
      <td><button type="button" onclick="xajax_text2url(this.form.head.value)">► URL</button></td>
    </tr>
    <tr>
      <td>URL <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="url" id="url" maxlength="255"/></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---НЕТ---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Группа пользователей сайта</td>
      <td>
         <select name="group_id" style="width:280px;" onchange="xajax_show_users(this.form.group_id.options[this.form.group_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---НЕТ---</option>'.
         show_select_users()
         .'</select>'; global $options; $options = ''; echo '
        </td>
      <td>&nbsp;</td>
      </tr>
   </table></td><td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td><td><div id="users"></div></td></tr></table><div>&nbsp;</div>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

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
    $sort_by = 'date';
    $order = 'desc';
  }

 $add = '';
 $params = array();
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = strtolower(trim($_GET['query_str']));
   $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';

   $add .= " and (news_id like '$query_str' or
           head like '$query_str' or
           text like '$query_str' or
           text_full like '$query_str')";
 }

if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '')
 {
   $add .= " and parent_id = ".$_GET['parent_id'];
   $params['parent_id'] = $_GET['parent_id'];
 }
 
 $query = "select
           news.*,
           date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   auth_site.username
           from news left join auth_site on news.user_id = auth_site.user_id
           where news.type = 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
      echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
      echo '<tr align="center" class="header">
              <td nowrap width="50">№&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=news_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'news_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=news_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'news_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Дата&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Заголовок&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td width="35">&nbsp;</td>
              <td>Пользователь сайта</td>
              <td width="120">&nbsp;</td>
            </tr>';

      while ($row = mysql_fetch_array($result))
       {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
                 <td align="center">'.$row['news_id'].'</td>
                 <td align="center">'.$row['date2'].'</td>
                 <td>'.htmlspecialchars($row['head']).'</td>
                 <td align="center">'; if ($row['img_path']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" border="0">'; else echo '&nbsp;'; echo '</td>
                 <td align="center">'.(($row['username']) ? htmlspecialchars($row['username']) : '&nbsp;').'</td>
                 <td nowrap align="center">
                 <a href="javascript:sw(\'/admin/editors/edit_news.php?id='.$row['news_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
                 &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['news_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['news_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
                 &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['news_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
               </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
}
else echo '<p align="center">Не найдено</p>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>