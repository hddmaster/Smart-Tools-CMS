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

   $head = $_POST['head'];
   $parent_id = ((isset($_POST['parent_id']) && (int)$_POST['parent_id'] >= 0) ? (int)$_POST['parent_id'] : 0 );

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   $file_path_db = '';
   if(isset($_FILES['file']['name']) &&
      is_uploaded_file($_FILES['file']['tmp_name']))
    {
      $user_file_name = mb_strtolower($_FILES['file']['name'],'UTF-8');
      $type = basename($_FILES['file']['type']);

      //Проверка на наличие файла, замена имени, пока такого файла не будет
      $file = pathinfo($user_file_name);
      $ext = $file['extension'];
      $name_clear = str_replace(".$ext",'',$user_file_name);
      $name = $name_clear;
      $i = 1;
      while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/files/$name.$ext"))
       {
         $name = $name_clear." ($i)";
         $i ++;
       }
      $user_file_name =  $name.'.'.$ext;
      $file_path_db = "/userfiles/files/$user_file_name";
    }

    $query = "insert into files (parent_id, date, head, file_path, user_id)
                               values
                               ($parent_id, '$date', '$head', '$file_path_db', $user_id)";
    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

    if (isset($_FILES['file']['name']) &&
        is_uploaded_file($_FILES['file']['tmp_name']))
     {
       $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/files/$user_file_name";
       copy($_FILES['file']['tmp_name'], $filename);
       resize($filename, basename($_FILES['file']['type']));
       chmod($filename,0666);
     }

   // перенумеровываем
   $result = mysql_query("select * from files where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['file_id'];
         mysql_query("update files set order_id=$i where file_id = $id");
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
         foreach ($elements as $file_id)
         {
         //запоминаем имя файла и удаляем его
         $result = mysql_query("select * from files where file_id=$file_id");
         if (mysql_num_rows($result) > 0)
          {
            $row = mysql_fetch_array($result);

            if ($row['img_path'])
             {
               $filename = $row['img_path'];
               if (!use_file($filename,'files','img_path'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }
          }

         //удаляем из бд
        $result = mysql_query("delete from files where file_id=$file_id");
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
         foreach ($elements as $file_id)
           mysql_query("update files set status=1 where file_id=$file_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         foreach ($elements as $file_id)
           mysql_query("update files set status=0 where file_id=$file_id");
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
$xajax->registerFunction("show_users");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Файлы</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/files.php')) $tabs->add_tab('/admin/files.php', 'Файлы');
if ($user->check_user_rules('view','/admin/file_groups.php')) $tabs->add_tab('/admin/file_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/file_structure.php')) $tabs->add_tab('/admin/file_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/file_import.php')) $tabs->add_tab('/admin/file_import.php', 'Импорт');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['file_id'].'"';
          if ($parent_id_added == $row['file_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";
          show_select($row['file_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
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
    $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['file_id'].'"';
          if ($parent_id_element == $row['file_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";

          show_select_filter($row['file_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
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
		   <td><h2 class="nomargins">Добавить файл</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="head" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Файл</td>
      <td><input style="width:280px" type="file" name="file"></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.date('d.m.Y').'"></td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value="'.date("H").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute" value="'.date("i").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value="'.date("s").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Дата начала публикации <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date_begin" class="datepicker" value="'.date('d.m.Y').'"></td>
    </tr>
    <tr>
      <td>Время начала публикации <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour_begin" value="'.date("H").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute_begin"  value="'.date("i").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second_begin" value="'.date("s").'" style="width:20px;" maxlength="2" onKeyPress="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td>
          <select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>'; global $options; $options = ''; echo '
      </td>
    </tr>
   </table><div>&nbsp;</div>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

$parent_id = -1; if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '') $parent_id = $_GET['parent_id_filter'];
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
            <option value="0"'; if (isset($_GET['parent_id_filter']) && ($parent_id === 0 || $parent_id == 0)) echo ' selected'; echo'>---Корень каталога---</option>
            '.show_select_filter(0,'',$parent_id).'
          </select>'; global $options; $options = ''; echo '
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

   $add .= " and (file_id like '$query_str' or
           head like '$query_str' or
           text like '$query_str' or
           text_full like '$query_str')";
 }

if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '')
 {
   $add .= " and parent_id = ".$_GET['parent_id'];
   $params['parent_id'] = $_GET['parent_id'];
 }
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select
           files.*,
           date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   auth_site.username
           from files left join auth_site on files.user_id = auth_site.user_id
           where files.type = 0 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
   navigation($page, $per_page, $total_rows, $params);
      echo '<div class="databox"><form id="form" action="" method="get"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
      echo '<tr align="center" class="header">
              <td align="left" nowrap width="80"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"> №&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'file_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'file_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Дата&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td nowrap>Группа</td>
              <td nowrap>Заголовок&nbsp;&nbsp;
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
                <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
              <td>Пользователь сайта</td>
              <td width="120">&nbsp;</td>
            </tr>'."\n";

      while ($row = mysql_fetch_array($result))
       {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
                 <td align="left" class="small" nowrap><input class="cbx" type="checkbox" name="id[]" value="'.$row['file_id'].'"> '.$row['file_id'].'</td>
                 <td align="center">'.$row['date2'].'</td>
           <td>';
           if ($row['parent_id'] == 0) echo '---Корень каталога---';
           else
            {
              $res = mysql_query("select * from files where file_id = ".$row['parent_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo htmlspecialchars($r['head']);
               }
              else echo '&nbsp;';
            }
           echo '</td>
                 <td>'.(($row['head']) ? '<a href="'.$row['file_path'].'"><strong>'.htmlspecialchars($row['head']).'</strong></a>' : '&nbsp;').'</td>
                 <td align="center">'.(($row['username']) ? htmlspecialchars($row['username']) : '&nbsp;').'</td>
                 <td nowrap align="center">
                 <a href="javascript:sw(\'/admin/editors/edit_file_m.php?id='.$row['file_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
                 &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['file_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['file_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
                 &nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['file_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'\'};"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
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