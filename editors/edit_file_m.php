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
   if (trim($_POST['date'])=='' || trim($_POST['hour'])=='' || trim($_POST['minute'])=='' || trim($_POST['second'])=='' || trim($_POST['head'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $file_id = (int)$_GET['id'];
   $parent_id = $_POST['parent_id'];

   $user_id = 0;
   if (isset($_POST['user_id']) && (int)$_POST['user_id'] > 0 &&
       isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) $user_id = (int)$_POST['user_id'];
   elseif (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0 ) $user_id = (int)$_POST['group_id'];

   $head = trim($_POST['head']);
   $tags = trim($_POST['tags']);

   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   $result = mysql_query("select * from files where file_id=$file_id");
   $row = mysql_fetch_array($result);
   $file_path = $row['file_path'];

//если есть картинка, проверяем её тип
  if (isset($_FILES['file']['name']) &&
   is_uploaded_file($_FILES['file']['tmp_name']))
   {
     $user_file_name = mb_strtolower($_FILES['file']['name'],'UTF-8');
     $type = basename($_FILES['file']['type']);

  //удаляем старый,если не используется
  if ($file_path !== '' && !use_file($file_path,'files','file_path')) unlink($_SERVER['DOCUMENT_ROOT'].$file_path);

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

     $result = mysql_query("update files set file_path='/userfiles/files/$user_file_name' where file_id=$file_id");
     copy($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT']."/userfiles/files/$user_file_name");
     chmod($filename,0666);
   }

  $result = mysql_query("update files set date='$date', head='$head', parent_id = $parent_id, user_id = $user_id, tags = '$tags'  where file_id=$file_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$file_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$file_id");
  exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_file']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $file_id = (int)$_GET['id'];
  $result = mysql_query("select file_path from files where file_id=$file_id");
  $row = mysql_fetch_array($result);
  if (!use_file($row['file_path'],'files','file_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['file_path']);

  $result = mysql_query("update files set file_path='' where file_id=$file_id");

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
    $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['file_id'].'"';
          if ($parent_id_element == $row['file_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";

          show_select($row['file_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
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

   $file_id = (int)$_GET['id'];
   $result = mysql_query("select
                          F.*,
                          date_format(F.date, '%d.%m.%Y (%H:%i:%s)') as date,
                          U.type as is_group,
                          U.parent_id as group_id
                          from
                          files as F left join auth_site as U
                          on F.user_id = U.user_id
                          where F.file_id=$file_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);
   
   $hour = substr($row->date,12,2);
   $minute = substr($row->date,15,2);
   $second = substr($row->date,18,2);
   $date = substr($row->date,0,10);
   $group_id = (($row->is_group) ? $row->user_id : $row->group_id);
   
echo '<h2 class="nomargins">'.htmlspecialchars($row->head).'</h2><div>&nbsp;</div>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_file_m.php')) $tabs->add_tab('/admin/editors/edit_file_m.php?id='.$file_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_file_text.php')) $tabs->add_tab('/admin/editors/edit_file_text.php?id='.$file_id.'&mode=brief', 'Краткое описание');
if ($user->check_user_rules('view','/admin/editors/edit_file_text.php')) $tabs->add_tab('/admin/editors/edit_file_text.php?id='.$file_id.'&mode=full', 'Подробное описание');
if ($user->check_user_rules('view','/admin/editors/edit_file_users.php')) $tabs->add_tab('/admin/editors/edit_file_users.php?id='.$file_id, 'Область видимости');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form enctype="multipart/form-data" action="?id='.$file_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" type="text" name="head" value="'.htmlspecialchars($row->head).'" maxlength="255"></td></tr>
    <tr>
      <td>Файл</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="file"></td><td>';
       if ($row->file_path)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_file=1&id=$file_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
 echo '</td></tr></table></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="date" class="datepicker" value="'.$date.'"></td>
    </tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.$hour.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.$minute.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.$second.' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0"'.(($row->parent_id == 0) ? ' selected' : '').'>---Корень каталога---</option>
            '.show_select(0, '', $row->parent_id).'
          </select>'; global $options; $options = ''; echo '
      </td>
    </tr>
    <tr>
      <td>Автор</td>
      <td>
         <select name="group_id" style="width:280px;" onchange="xajax_show_users(this.form.group_id.options[this.form.group_id.selectedIndex].value);">
            <option value="0"'.(($group_id == 0) ? ' selected' : '').'>---НЕТ---</option>'.
         show_select_users(0, '', $group_id)
         .'</select>'; global $options; $options = ''; echo '<div id="users">'.(($row->user_id) ? '<p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>' : '').'</div>
      </td></tr>
    <tr>
        <td>Тэги</td>
        <td><input style="width:280px" type="text" name="tags" value="'.htmlspecialchars($row->tags).'" maxlength="255"></td>
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