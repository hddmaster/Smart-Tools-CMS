<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['element_name'])=='' || trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_name = ''; if (isset($_POST['element_name'])) $element_name = $_POST['element_name'];
  $parent_id = $_POST['parent_id'];

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

  $status = $_POST['status'];
  $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
  $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
  $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
  $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

  $folder = 'gallery_images';
  $files = array();
  if (isset($_FILES['picture']))
   {     
     foreach($_FILES['picture']['tmp_name'] as $fn => $tmp_name)
       if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) $files[$fn]['tmp_name'] = $tmp_name;
     
     foreach($_FILES['picture']['type'] as $fn => $type)
      {
        if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) {
        $t = '';
        switch (basename($type))
         {
           case 'jpeg':
           case 'pjpeg': $t = 'jpeg'; break;
           case 'png':
           case 'x-png': $t = 'png'; break;
           case 'gif':  $t = 'gif'; break;
           case 'bmp':
           case 'wbmp':  $t = 'bmp'; break;
           default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
         }
        $files[$fn]['type'] = $t;
	}
      }
     
     foreach($_FILES['picture']['name'] as $fn => $name)
      {
        if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) {
        $name = mb_strtolower($name,'UTF-8');
        $file = pathinfo($name);
        $ext = $file['extension'];
        $name_clear = str_replace('.'.$ext, '', $name);
	$name = $name_clear;
	$ext = $files[$fn]['type'];
        $i = 1;
        while (file_exists($_SERVER['DOCUMENT_ROOT'].'/userfiles/'.$folder.'/'.$name.'.'.$ext))
         {
           $name = $name_clear." ($i)";
           $i++;
         }
        $files[$fn]['name'] = '/userfiles/'.$folder.'/'.$name.'.'.$ext;
	}
      }
   }

  //уникальная запись! Добавляем в каталог...
  $query = "insert into gallery (parent_id, type, date, element_name, user_id, status)
                                values
				($parent_id, 1, $date, '$element_name', $user_id, $status)";

  $result = mysql_query($query) or die(mysql_error());
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
  
  $element_id = mysql_insert_id();

  if (count($files) > 0)
   {
     foreach($files as $fn => $value)
      {
        $filename = $value['name'];
        copy($value['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$value['name']);
        chmod($value['name'],0666);
	mysql_query("update gallery set img_path".($fn+1)." = '".$value['name']."' where element_id = $element_id");
      }
   }

   // перенумеровываем
   $result = mysql_query("select * from gallery where parent_id = $parent_id and type = 1 order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update gallery set order_id=$i where element_id = $id");
         $i++;
       }
    }

   //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   Header("Location: ".$_SERVER['PHP_SELF']."?parent_id=$parent_id");
   exit();

  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $element_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

      $result = @mysql_query("select * from gallery where parent_id=$element_id");
      if (@mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
      else
       {
           $result = mysql_query("select * from gallery where element_id=$element_id");
           $row = mysql_fetch_array($result);
           if($row['img_path1'])
             {
               $filename = $row['img_path1'];
               if(!use_file($filename,'gallery','img_path1') || !use_file($filename,'gallery','img_path2') || !use_file($filename,'gallery','img_path3'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

           //удаляем из gallery
           $result = mysql_query("delete from gallery where element_id=$element_id");
           if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
 
           //Обновление кэша связанных модулей на сайте
           $cache = new Cache; $cache->clear_cache_by_module();
       }
      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update gallery set status=1 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update gallery set status=0 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
//------------------------------------------------------------------------------
// AJAX

function show_users($parent_id, $div, $rows)
 {
   $objResponse = new xajaxResponse();
   $select_users = '<select name="user_id" style="width:280px;" size="'.$rows.'">';
   $result = mysql_query("select * from auth_site where type = 0 and parent_id = $parent_id order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $select_users .= '<option value="0">---НЕТ---</option>';
      while ($row = mysql_fetch_array($result))
         $select_users .= '<option value="'.$row['user_id'].'">'.htmlspecialchars($row['username']).' (id: '.$row['user_id'].')</option>';
    }
   else $select_users .= '<option value="">Нет пользователей</option>';
   $select_users .= '</select>';
   $objResponse->assign($div, "innerHTML", $select_users);
   return $objResponse;
 }
$xajax->registerFunction("show_users");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Галерея</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/gallery.php')) $tabs->add_tab('/admin/gallery.php', 'Публикации');
if ($user->check_user_rules('view','/admin/gallery_groups.php')) $tabs->add_tab('/admin/gallery_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/gallery_structure.php')) $tabs->add_tab('/admin/gallery_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/gallery_comments.php')) $tabs->add_tab('/admin/gallery_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/gallery_import.php')) $tabs->add_tab('/admin/gallery_import.php', 'Импорт');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'">'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

 function show_select_users($parent_id = 0, $prefix = '', $group_id = 0)
  {
    global $options;
    $result = mysql_query("select * from auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(mysql_num_rows($result) > 0)
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
      <td>Название группы <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="element_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture1"/></td>
    </tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.date('d.m.Y').'"></td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.date("H").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.date("i").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.date("s").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Расположение группы <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'').'
          </select>'; global $options; $options = ''; echo '
      </td>
    </tr>
   <tr>
     <td>Активность</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="status" style="width: 16px; height: 16px;" checked value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="status" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
    <tr>
      <td>Группа пользователей сайта</td>
      <td>
         <select name="group_id" style="width:280px;" onchange="xajax_show_users(this.form.group_id.options[this.form.group_id.selectedIndex].value, \'users\', 14);">
            <option value="">Выберите группу...</option>
            <option value="0">---НЕТ---</option>'.
         show_select_users()
         .'</select>'; global $options; $options = ''; echo '
      </td></tr>
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
    $sort_by = 'element_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']), 'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']), 'UTF-8').'%';

   $add .= " and (element_id like '$query_str' or
           element_name like '$query_str' or
           description like '$query_str' or
           description_full like '$query_str')";
 }
 
 $query = "select
           gallery.*,
           date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2,
           auth_site.username
           from gallery left join auth_site on gallery.user_id = auth_site.user_id
           where gallery.type = 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
        <td>Пользователь сайта</td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['element_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td>'.htmlspecialchars($row['element_name']).'</td>
           <td align="center">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" border="0">'; else echo '&nbsp;'; echo '</td>
           <td align="center">'.(($row['username']) ? htmlspecialchars($row['username']) : '&nbsp;').'</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_gallery_descr.php?id='.$row['element_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_gallery_group.php?id='.$row['element_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
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