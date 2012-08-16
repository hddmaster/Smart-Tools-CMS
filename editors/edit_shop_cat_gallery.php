<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']) &&
    isset($_GET['element_id']))
 {
  if ($user->check_user_rules('add'))
   {

  $element_name = ''; if (isset($_POST['element_name'])) $element_name = $_POST['element_name'];
  $parent_id = 0; if (isset($_POST['parent_id']) && trim($_POST['parent_id']) !== '') $parent_id = $_POST['parent_id'];
  $element_id = $_GET['element_id'];
  $status = $_POST['status'];

if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']))
{
//проверка формата первой картинки
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
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }
//Проверка на наличие файла, замена имени, пока такого файла не будет
$file = pathinfo($user_file_name);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_gallery_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name = $name.'.'.$ext;
}

  $query = "insert into shop_cat_gallery values (null, $parent_id, $element_id, 0, 0, '$element_name'";
  if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) $query .= ", '"."/userfiles/shop_cat_gallery_images/$user_file_name"."'";
  else $query .= ", ''";
  $query = $query.", $status)";

  //echo $query;
  $result = mysql_query($query);
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}

  if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_gallery_images/$user_file_name";
     copy($_FILES['picture']['tmp_name'], $filename);
     chmod($filename,0666);
   }

   // перенумеровываем
   $result = mysql_query("select * from shop_cat_gallery where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update shop_cat_gallery set order_id=$i where element_id = $id");
         $i++;
       }
    }

   //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id");
   exit();

  } else $user->no_rules('add');
 }

if (isset($_POST['element_names']) &&
    isset($_POST['parent_ids']) &&
    isset($_GET['element_id']))
 {
 if ($user->check_user_rules('edit'))
  {
    
   $id = $_GET['element_id']; 
   foreach($_POST['element_names'] as $element_id => $element_name)
      mysql_query("update shop_cat_gallery set element_name = '$element_name' where element_id = $element_id");
   foreach($_POST['parent_ids'] as $element_id => $parent_id)
      mysql_query("update shop_cat_gallery set parent_id = $parent_id where element_id = $element_id");

   Header("Location: ".$_SERVER['PHP_SELF']."?id=$id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['eid']))
 {
   $action = $_GET['action'];
   $element_id = (int)$_GET['eid'];
   $main_element_id = $_GET['id'];
      
   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
         $result = mysql_query("select * from shop_cat_gallery where element_id = $element_id");
            if (mysql_num_rows($result) > 0)
             {
               $row = mysql_fetch_array($result);
               if($row['img_path'] && !use_file($row['img_path'],'shop_cat_gallery','img_path')) unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path']);
             }
             
            //удаляем из gallery
            $result = mysql_query("delete from shop_cat_gallery where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$main_element_id&message=db"); exit();}

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_gallery set status=1 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_gallery set status=0 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$main_element_id"); exit();
 }

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

  $element_id = (int)$_GET['id'];
  $result = mysql_query("select
                        *
                        from shop_cat_elements
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $element_name = $row['element_name'];
   $file1 = $row['img_path1'];
   $file2 = $row['img_path2'];
   $file3 = $row['img_path3'];

 if ($file1 || $file2 || $file3) echo '<p>';
 if ($file1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file1).'" border="0"> &nbsp;';
 if ($file2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file2).'" border="0"> &nbsp;';
 if ($file3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file3).'" border="0">';
 if ($file1 || $file2 || $file3) echo '</p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat.php')) $tabs->add_tab('/admin/editors/edit_shop_cat.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_card_values.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_card_values.php?id='.$element_id, 'Карточки описаний');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_gallery.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_gallery.php?id='.$element_id, 'Фотогалерея');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_files.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_files.php?id='.$element_id, 'Файлы');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_on_map.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_gallery_groups.php')) $tabs2->add_tab('/admin/editors/edit_shop_cat_gallery_groups.php?id='.$element_id, 'Группы');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_gallery_structure.php')) $tabs2->add_tab('/admin/editors/edit_shop_cat_gallery_structure.php?id='.$element_id, 'Структура');
$tabs2->show_tabs();

define ('ELEMENT_ID', $element_id);

 function show_select($parent_id = 0, $prefix = '', $sel_id = 0)
  {
    global $options;
    $result = mysql_query("SELECT * FROM shop_cat_gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($row['element_id'] == $sel_id) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $sel_id);
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
		   <td><h2 class="nomargins">Добавить новую публикацию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="?element_id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" maxlength="255"></td></tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture"/></td>
    </tr>
    <tr>
      <td>Расположение публикации <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'').'
          </select>
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
  </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'element_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
 $params['id'] = $element_id;
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select
           *
           from shop_cat_gallery
           where type = 0 and catalog_element_id = $element_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<form action="?element_id='.$element_id.'" method="post">
       <p align="right"><button type="submit">Сохранить</button></p>
       <table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Группа</td>
         <td nowrap width="100%">Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   global $options;
   $options = '';
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['element_id'].'</td>
           <td><select name="parent_ids['.$row['element_id'].']" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'',$row['parent_id']).'
           </select></td>
           <td><input type="text" name="element_names['.$row['element_id'].']" value="'.htmlspecialchars($row['element_name']).'" style="width: 100%"></td>
           <td align="center">'; if ($row['img_path']) echo '<a href="'.$row['img_path'].'" class="zoom" rel="group"><img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" alt="'.$row['img_path'].'" border="0"></a>'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">';
           if($row['status'] == 0) echo '<a href="?action=activate&eid='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&eid='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&eid=".$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   }
  echo '</table>
        <p align="right"><button type="submit">Сохранить</button></p>
        </form>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>