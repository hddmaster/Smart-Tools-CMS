<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}


if (isset($_POST['username']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['username'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

    $user_id = (int)$_GET['id'];

    $password1 = trim($_POST['password1']);
    $password2 = trim($_POST['password2']);
    if ($password1 !== $password2) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=passwords"); exit();}

    $username = trim($_POST['username']);
    $result = mysql_query("select * from auth where username='$username' and user_id != $user_id and type = 0");
    if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=duplicate"); exit();}

    $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
    $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
    $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
    $register_date = substr($_POST['register_date'],6,4).substr($_POST['register_date'],3,2).substr($_POST['register_date'],0,2).$hour.$minute.$second;
    $user_birthday = substr($_POST['user_birthday'],6,4).substr($_POST['user_birthday'],3,2).substr($_POST['user_birthday'],0,2);

    $fields = array();
    $values = array();

    $res = mysql_query("show columns from auth");
      while($r = mysql_fetch_object($res))
         $fields[] = $r->Field;
     
    foreach ($fields as $field)
     {
       if (isset($_POST[$field]))
         $values[$field] = '\''.(is_array($_POST[$field]) ? serialize($_POST[$field]) : trim($_POST[$field])).'\'';
     }
    $values['register_date'] = ((isset($_POST['register_date'])) ? substr($_POST['register_date'],6,4).substr($_POST['register_date'],3,2).substr($_POST['register_date'],0,2).$hour.$minute.$second : '');
    $values['user_birthday'] = ((isset($_POST['user_birthday'])) ? substr($_POST['user_birthday'],6,4).substr($_POST['user_birthday'],3,2).substr($_POST['user_birthday'],0,2) : '');
    unset($values['password']);
    unset($values['user_image']);

    $result = mysql_query("select user_image from auth where user_id=$user_id");
    $row = mysql_fetch_array($result);
    $user_image = $row['user_image'];

  if (isset($_FILES['user_image']['name']) &&
   is_uploaded_file($_FILES['user_image']['tmp_name']))
   {
     $user_file_name = mb_strtolower($_FILES['user_image']['name'],'UTF-8');
     $type = basename($_FILES['user_image']['type']);

  switch ($type)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($user_image !== '' && !use_file($user_image,'auth_site','user_image') && !use_file($user_image,'auth','user_image')) unlink($_SERVER['DOCUMENT_ROOT'].$user_image);

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($user_file_name);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$user_file_name);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/user_images/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }
  $user_file_name =  $name.'.'.$ext;

    $result = mysql_query("update auth set user_image='"."/userfiles/user_images/$user_file_name"."' where user_id=$user_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();

    $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/user_images/$user_file_name";
    copy($_FILES['user_image']['tmp_name'], $filename);
    chmod($filename,0666);
   }

   $query = 'update auth set ';
   $i = 1;
   foreach ($values as $field => $value)
    {
      $query .= "$field = $value";
      if ($i < count($values)) $query .= ', ';
      $i++;
    }
   $query .= ' where user_id = '.$user_id;
   
      
   $result = mysql_query($query) or die(mysql_error());
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=db"); exit();}

   if ($password1 !== '')
    {
      $result = mysql_query("update auth set password = '".md5($password1.SOLT)."' where user_id=$user_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id&message=db"); exit();}
      if($user_id == $user->user_id) $user->login($username, $password1);
    }
    
    
   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $user_id = (int)$_GET['id'];
  $delete_img = $_GET['delete_img'];

  if ($delete_img == 'true')
   {
     $result = mysql_query("select user_image from auth where user_id=$user_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['user_image'],'auth_site','user_image') && !use_file($row['user_image'],'auth','user_image')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['user_image']);
     $result = mysql_query("update auth set user_image='' where user_id=$user_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id"); exit();
  } else $user->no_rules('delete');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 function show_select($parent_id = 0, $prefix = '',$parent_id_element)
  {
    global $options;
    $result = mysql_query("SELECT * FROM auth where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($parent_id_element == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>'."\n";
          show_select($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

   $user_id = (int)$_GET['id'];
   $result = mysql_query("select
                          A.*,
                          date_format(A.register_date, '%d.%m.%Y (%H:%i:%s)') as register_date,
                          date_format(A.user_birthday, '%d.%m.%Y') as user_birthday,
                          U.user_type_name
                          from auth as A left join auth_users as U
                          on A.user_type = U.user_type where A.user_id=$user_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);

    
 if ($row->user_image) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->user_image).'" border="0"></p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_auth_user.php')) $tabs->add_tab('/admin/editors/edit_auth_user.php?id='.$user_id, 'Свойства');
//if ($user->check_user_rules('view','/admin/editors/edit_auth_site_user_on_map.php')) $tabs->add_tab('/admin/editors/edit_auth_site_user_on_map.php?id='.$user_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('duplicate_email', 'Такой e-mail уже используется');
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$user_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Логин <sup class="red">*</sup></td>
      <td width="100%"><input style="width:280px" type="text" name="username" value="'.htmlspecialchars($row->username).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Новый пароль <sup class="red">*</sup></td>
      <td><input style="width:280px" type="password" name="password1" value="" maxlength="255"></td>
    </tr>
    <tr>
      <td>Пароль <sup class="red">*</sup><br/><span class="grey">Еще раз для проверки</span></td>
      <td><input style="width:280px" type="password" name="password2" value="" maxlength="255"></td>
    </tr>
    <tr>
      <td>Ник</td>
      <td><input style="width:280px" type="text" name="user_nick" value="'.htmlspecialchars($row->user_nick).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Ф.И.О.</td>
      <td><input style="width:280px" type="text" name="user_fio" value="'.htmlspecialchars($row->user_fio).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Дата регистрации на сайте</td>
      <td>';
?>
    <script>
      LSCalendars["register_date"]=new LSCalendar();
      LSCalendars["register_date"].SetFormat("dd.mm.yyyy");
      LSCalendars["register_date"].SetDate("<?=substr($row->register_date,0,10)?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('register_date', event); return false;" style="width: 65px;" value="<?=substr($row->register_date,0,10)?>" name="register_date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('register_date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="register_datePtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td></tr>
    <tr>
      <td>Время регистрации на сайте<sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value="'.substr($row->register_date,12,2).'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value="'.substr($row->register_date,15,2).'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value="'.substr($row->register_date,18,2).'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Дата рождения</td>
      <td>';
?>
    <script>
      LSCalendars["user_birthday"]=new LSCalendar();
      LSCalendars["user_birthday"].SetFormat("dd.mm.yyyy");
      LSCalendars["user_birthday"].SetDate("<?=$row->user_birthday?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('user_birthday', event); return false;" style="width: 65px;" value="<?=$row->user_birthday?>" name="user_birthday"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('user_birthday', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="user_birthdayPtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td></tr>
    <tr>
      <td>Телефон</td>
      <td><input style="width:280px" type="text" name="user_phone" value="'.htmlspecialchars($row->user_phone).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>ICQ</td>
      <td><input style="width:280px" type="text" name="user_icq" maxlength="255"></td>
    </tr>
    <tr>
      <td>Адрес</td>
      <td>';

   $oFCKeditor = new FCKeditor('user_address');
   $oFCKeditor->BasePath = '/admin/fckeditor/';
   $oFCKeditor->ToolbarSet = 'Minimal' ;
   $oFCKeditor->Value = $row->user_address;
   $oFCKeditor->Width  = '100%' ;
   $oFCKeditor->Height = '200' ;
   $oFCKeditor->Create() ;

      echo '</td>
    </tr>
    <tr>
      <td>e-mail</td>
      <td><input style="width:280px" type="text" name="email" value="'.htmlspecialchars($row->email).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Фото</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="user_image"/></td><td>';
       if ($row->user_image)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=true&id=$user_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
       echo '</td></tr></table>
    </tr>
    <tr>
      <td>Дополнительная информация</td>
      <td><input style="width:280px" type="text" name="user_extra" value="'.htmlspecialchars($row->user_extra).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Тип пользователя <sup class="red">*</sup></td>
      <td>
       <select style="width:280px" name="user_type">
         <option value="">Выберите тип...</option>';

 $res = mysql_query("select * from auth_users order by user_type_name asc");
 if (mysql_num_rows($res) > 0)
  {
    while ($r = mysql_fetch_object($res))
      echo '<option value="'.$r->user_type.'"'.(($r->user_type == $row->user_type) ? ' selected' : '').'>'.htmlspecialchars($r->user_type_name).'</option>';
  }
  
echo'  </select>
      </td>
    </tr>
    <tr>
      <td>Группа <sup class="red">*</sup><br><span class="grey">Выберите группу</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0"';
            global $options; $options = '';
            if ($row->parent_id == 0) echo ' selected';
            echo '>---Корень каталога---</option>
            '.show_select(0,'',$row->parent_id).'
          </select>
      </td>
    </tr>
    <tr>
     <td>Главный в группе</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="main_in_group" style="width: 16px; height: 16px;"'; if ($row->main_in_group == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="main_in_group" style="width: 16px; height: 16px;"'; if ($row->main_in_group == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
    </tr>
    <tr>
      <td>Язык</td>
      <td><select style="width:280px" name="lang_id">
           <option value="0" selected>Базовый язык</option>';
    $res = mysql_query("select * from languages order by lang_code asc");
    while ($r = mysql_fetch_object($res))
     {
       echo '<option value="'.$r->lang_id.'"';
       if ($r->lang_id == $row->lang_id) echo ' selected'; 
       echo '>'.htmlspecialchars($r->lang_code);
       if ($r->lang_name) echo ' ('.htmlspecialchars($r->lang_name).')';
       echo '</option>'."\n";
     }
      echo'</select></td>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>