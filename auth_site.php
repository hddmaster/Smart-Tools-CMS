<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['username'])&&
    isset($_POST['password']) &&
    isset($_POST['user_type']))
 {

 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['username'])=='' ||
       trim($_POST['password'])=='' ||
       trim($_POST['user_type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   if ($_POST['password1'] !== $_POST['password2']) {Header("Location: ".$_SERVER['PHP_SELF']."?message=passwords"); exit();}

   $parent_id = trim($_POST['parent_id']);
   $main_in_group = $_POST['main_in_group'];
   $email = strtolower(trim($_POST['email']));
   //проверка на корректный e-mail
   if (trim($email) !== '' && !valid_email($email)) {Header("Location: ".$_SERVER['PHP_SELF']."?message=notvalidemail");exit();}
   $username = trim($_POST['username']);
   if (use_field($username,'auth_site','username')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}
   
   $password = md5($_POST['password'].SOLT);
   $user_type = intval($_POST['user_type']);
   $user_nick = $_POST['user_nick'];
   $user_fio = $_POST['user_fio'];
   $register_date = substr($_POST['register_date'],6,4).substr($_POST['register_date'],3,2).substr($_POST['user_register'],0,2).'000000';
   $user_birthday = substr($_POST['user_birthday'],6,4).substr($_POST['user_birthday'],3,2).substr($_POST['user_birthday'],0,2);
   $user_phone = $_POST['user_phone'];
   $user_icq = $_POST['user_icq'];
   $user_address = $_POST['user_address'];
   $user_extra = $_POST['user_extra'];
   $lang_id = $_POST['lang_id'];
   $status = $_POST['status'];

   $user_file_name = '';
   if(isset($_FILES['user_image']['name']) && is_uploaded_file($_FILES['user_image']['tmp_name']))
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
        default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
      }

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
     $user_file_name =  '/userfiles/user_images/'.$name.'.'.$ext;
     copy($_FILES['user_image']['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$user_file_name);
     chmod($filename,0666);
   }

   $result = mysql_query("insert into auth_site
                          (parent_id, username, user_type, password, user_nick, user_fio, register_date, user_birthday, user_phone, user_icq, user_address, email, user_image, user_extra, main_in_group, lang_id, status)
                          values
                          ($parent_id, '$username', $user_type, '$password', '$user_nick', '$user_fio', now(), '$user_birthday', '$user_phone', '$user_icq', '$user_address', '$email', '$user_file_name', '$user_extra', $main_in_group, $lang_id, $status)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   // перенумеровываем
   $result = mysql_query("select * from auth_site where parent_id = $parent_id and type = 0 order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['user_id'];
         mysql_query("update auth_site set order_id=$i where user_id = $id");
         $i++;
       }
    }

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }




if ((isset($_POST['action']) && isset($_POST['id'])) ||
    (isset($_GET['action']) && isset($_GET['id'])) ||
    isset($_POST['user_fios'])) {

    if ($user->check_user_rules('edit')) {
        foreach ($_POST['user_fios'] as $user_id => $user_fio)
            mysql_query("update auth_site set user_fio = '".trim($user_fio)."' where user_id = $user_id");
        foreach ($_POST['user_nicks'] as $user_id => $user_nick)
            mysql_query("update auth_site set user_nick = '".trim($user_nick)."' where user_id = $user_id");
        foreach ($_POST['user_sex'] as $user_id => $user_sex)
            mysql_query("update auth_site set user_sex = '".trim($user_sex)."' where user_id = $user_id") or die(mysql_error());
        
        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
    } else $user->no_rules('edit');

    if (isset($_GET['action'])) $action = $_GET['action'];
    if (isset($_POST['action'])) $action = $_POST['action'];
    $users = array();
    if (isset($_GET['id']))  $users[] = (int)$_GET['id'];
    if (isset($_POST['id'])) $users = $_POST['id'];

    if ($action == 'del') {
        if ($user->check_user_rules('delete')) {
            foreach($users as $user_id) {
                mysql_query("delete from auth_site where user_id=$user_id");
                mysql_query("delete from distr_list where user_id=$user_id");
            }
        } else
            $user->no_rules('delete');
    }

    if ($action == 'activate') {
        if ($user->check_user_rules('action')) {
            foreach($users as $user_id)
                mysql_query("update auth_site set status=1 where user_id=$user_id");
        } else
            $user->no_rules('action');
    }

    if ($action == 'reserve') {
        if ($user->check_user_rules('action')) {
            foreach($users as $user_id)
                mysql_query("update auth_site set status=0 where user_id=$user_id");
        } else
            $user->no_rules('action');
    }

}

//------------------------------------------------------------------------------
//AJAX
function set_auto_password($id) {
    $objResponse = new xajaxResponse();
    $text = '';
    $s = array( '0','1','2','3','4','5','6','7','8','9',
                'a','b','c','d','e','f','g','h','i','j',
                'k','l','m','n','o','p','q','r','s','t',
                'u','v','w','x','y','z','A','B','C','D',
                'E','F','G','H','I','J','K','L','M','N',
                'O','P','Q','R','S','T','U','V','W','X',
                'Y','Z');
    for ($i = 0; $i < 10; $i++) $text .= $s[rand(0,count($s)-1)];
    $objResponse->assign($id,'value',$text);
    return $objResponse;
}

function check_form($username, $email) {
    $objResponse = new xajaxResponse();
    
    $c_uname = false;
    if(trim($username)) {
        $res = mysql_query("select * from auth_site where username = '$username'");
        if(mysql_num_rows($res) > 0) $objResponse->alert('Такой логин уже используется');
        if(mysql_num_rows($res) == 0) $c_uname = true;
    }
    
    $c_email = false;
    if(trim($email)) {
        $res = mysql_query("select * from auth_site where email = '$email'");
        if(mysql_num_rows($res) > 0) $objResponse->alert('Такой email уже используется');
        if(mysql_num_rows($res) == 0) $c_email = true;
    }

    if($c_uname && (($email && $c_email) || !$email)) {
        $objResponse->assign('submitbutton', 'disabled', false);
    } else
        $objResponse->assign('submitbutton', 'disabled', true);

    return $objResponse;
}

$xajax->registerFunction("set_auto_password");
$xajax->registerFunction("check_form");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Пользователи сайта</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/auth_site.php')) $tabs->add_tab('/admin/auth_site.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_site_groups.php')) $tabs->add_tab('/admin/auth_site_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_site_properties.php')) $tabs->add_tab('/admin/auth_site_properties.php', 'Свойства');
if ($user->check_user_rules('view','/admin/auth_site_structure.php')) $tabs->add_tab('/admin/auth_site_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_site_users.php')) $tabs->add_tab('/admin/auth_site_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_site_rules.php')) $tabs->add_tab('/admin/auth_site_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_site_scripts.php')) $tabs->add_tab('/admin/auth_site_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_site_history.php')) $tabs->add_tab('/admin/auth_site_history.php', 'История');
if ($user->check_user_rules('view','/admin/auth_site_import.php')) $tabs->add_tab('/admin/auth_site_import.php', 'Импорт');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("SELECT * FROM auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($parent_id_added == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>';
          show_select($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

 function show_select_filter($parent_id = 0, $prefix = '', $parent_id_element = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM auth_site where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['user_id'].'"';
          if ($parent_id_element == $row['user_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>';

          show_select_filter($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

function path_to_object($user_id)
 {
   global $path;
   $parh = array();
   $result = mysql_query("select * from auth_site where user_id = $user_id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $path[] = $row['username'];
     
      path_to_object($row['parent_id']);
    }
   return array_reverse($path); 
 }

 $parent_id_added = 0; if (isset($_GET['parent_id'])) $parent_id_added = $_GET['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('this_user', 'Нельзя удалить текущего пользователя');
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
	 <tr>
	   <td><img src="/admin/images/icons/plus.png" alt=""></td>
	   <td><h2 class="nomargins">Добавить пользователя</h2></td>
	 </tr>
	</table>   
       </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Логин <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="username" id="username" maxlength="255" onblur="xajax_check_form(this.value, $(\'#email\').val());"></td>
    </tr>
    <tr>
      <td>Пароль <sup class="red">*</sup></td>
      <td><input style="width:280px" type="password" name="password" id="password" maxlength="255">
          <table cellspacing="0" cellpadding="0"><tr><td><input type="checkbox" name="show_password" id="show_password" onclick="if(document.getElementById(\'show_password\').checked) document.getElementById(\'password\').type = \'text\'; else document.getElementById(\'password\').type = \'password\';"></td><td>показать пароль</td></tr></table>
      </td>
    </tr>
    <tr>
      <td>Ник</td>
      <td><input style="width:280px" type="text" name="user_nick" maxlength="255"></td>
    </tr>
    <tr>
      <td>Ф.И.О.</td>
      <td><input style="width:280px" type="text" name="user_fio" maxlength="255"></td>
    </tr>
    <tr>
      <td>Дата регистрации на сайте</td>
      <td>';
?>
    <script>
      LSCalendars["register_date"]=new LSCalendar();
      LSCalendars["register_date"].SetFormat("dd.mm.yyyy");
      LSCalendars["register_date"].SetDate("<?=date("d.m.Y");?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=date("d.m.Y");?>" name="register_date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('register_date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="register_datePtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td></tr>
    <tr>
      <td>Дата рождения</td>
      <td>';
?>
    <script>
      LSCalendars["user_birthday"]=new LSCalendar();
      LSCalendars["user_birthday"].SetFormat("dd.mm.yyyy");
      LSCalendars["user_birthday"].SetDate("<?=date("d.m.Y");?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('user_birthday', event); return false;" style="width: 65px;" value="" name="user_birthday"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="user_birthdayPtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td></tr>
    <tr>
      <td>Телефон</td>
      <td><input style="width:280px" type="text" name="user_phone" maxlength="255"></td>
    </tr>
    <tr>
      <td>ICQ</td>
      <td><input style="width:280px" type="text" name="user_icq" maxlength="255"></td>
    </tr>
    <tr>
      <td>Адрес</td>
      <td><input style="width:280px" type="text" name="user_address" maxlength="255"></td>
    </tr>
    <tr>
      <td>e-mail</td>
      <td><input style="width:280px" type="text" name="email" id="email" maxlength="255" onblur="xajax_check_form($(\'#username\').val(), this.value);"></td>
    </tr>
    <tr>
      <td>Фото</td>
      <td><input style="width:280px" type="file" name="user_image" maxlength="255"></td>
    </tr>
    <tr>
      <td>Дополнительная информация</td>
      <td><input style="width:280px" type="text" name="user_extra" maxlength="255"></td>
    </tr>
    <tr>
      <td>Тип пользователя <sup class="red">*</sup></td>
      <td><select style="width:280px" name="user_type">
          <option value="" selected>Выберите тип...</option>';
    $result = mysql_query("select user_type,user_type_name from auth_site_users order by user_type_name asc");
    while ($row = mysql_fetch_array($result))
      echo '<option value="'.$row['user_type'].'">'.htmlspecialchars($row['user_type_name']).'</option>';
      echo'</select></td></tr>
   <tr>
      <td>Группа <sup class="red">*</sup><br><span class="grey">Выберите группу</span></td>
      <td><select name="parent_id" style="width:280px;">
          <option value="0">---Корень каталога---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>
      </td>
    </tr>
    <tr>
     <td>Главный в группе</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="main_in_group" value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="main_in_group" value="0" checked></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
    </tr>
   <tr>
     <td>Сразу активировать пользователя</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="status" value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="status" value="0" checked></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
    <tr>
      <td>Язык</td>
      <td><select style="width:280px" name="lang_id">
           <option value="0" selected>Базовый язык</option>';
    $result = mysql_query("select * from languages order by lang_code asc");
    while ($row = mysql_fetch_array($result))
     {
       echo '<option value="'.$row['lang_id'].'">'.htmlspecialchars($row['lang_code']);
       if ($row['lang_name']) echo ' ('.htmlspecialchars($row['lang_name']).')';
       echo '</option>';
     }
      echo'</select></td></tr>
</table><br>
   <button type="SUBMIT" id="submitbutton" disabled onmouseover="xajax_check_form($(\'#username\').val(), $(\'#email\').val());">Добавить</button>
  </form><br /></div></div>
  <script>
    xajax_set_auto_password(\'password\');
  </script>';

global $options; $options = '';
$parent_id = -1; if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '') (int)$parent_id = $_GET['parent_id_filter'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td nowrap>
   <form action="" method="GET">

   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td nowrap>Фильтр по группе</td>
      <td><select name="parent_id" style="width:280px;">
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
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'user_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();

if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '')
 {

   $add .= " and parent_id = ".$_GET['parent_id'];
   $params['parent_id'] = $_GET['parent_id'];
 }
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (user_id like '$query_str' or
                  username like '$query_str' or
                  user_fio like '$query_str' or
                  user_nick like '$query_str' or
                  user_type_name like '$query_str' or
                  user_extra like '$query_str' or
                  email like '$query_str')";
 }

 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select
           A.*,
           U.user_type_name
           from auth_site as A left join auth_site_users as U on A.user_type = U.user_type
           where A.type = 0 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
		echo '<form id="form" method="post">';
		echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
		navigation($page, $per_page, $total_rows, $params);
		echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">
        <tr align="center" class="header">
         <td nowrap width="50"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"> №&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Расположение</td>
         <td nowrap>Логин&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'username' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=username&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'username' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Последнее действие</td>
         <td nowrap>Тип пользователя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_type_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_type_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_type_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_type_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Ник&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_nick&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_nick' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_nick&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_nick' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Ф.И.О&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_fio&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_fio' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_fio&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_fio' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Пол&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_sex&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_sex' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_sex&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_sex' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>         
         <td nowrap>e-mail&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Дополнительная<br />информация&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_extra&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_extra' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_extra&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_extra' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">E-mail<br />подтвержден</td>
         <td nowrap>Фото</td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center"><input class="cbx" type="checkbox" name="id[]" value="'.$row['user_id'].'"> '.$row['user_id'].'</td>
           <td class="small">';
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
              $str = path_to_object($row['parent_id']);
              $i = 1;
              foreach ($str as $value)
               {
                 echo $value;
                 if ($i < count($str)) echo ', ';
                 $i++;
               }
              global $path; $path = array();
            }
           echo '</td>
           <td nowrap>';

           $res = mysql_query("select user_id from auth_site_online where user_id = ".$row['user_id']);
           if (mysql_num_rows($res))
              echo '<img src="/admin/images/icons/status.png" alt="" align="absmiddle">'.((mysql_num_rows($res) > 1) ? '<sup>'.mysql_num_rows($res).'</sup>' : '');

           echo htmlspecialchars($row['username']).'</td>
           <td align="center" class="small">';
           
           $res = mysql_query("select date from auth_site_history where history_id = (select max(history_id) from auth_site_history where user_id = ".$row['user_id'].")");
           if(mysql_num_rows($res) > 0) {
               $r = mysql_fetch_array($res);
               echo strftime("%d.%m.%Y (%H:%M:%S)", $r['date']);
           } else echo '&nbsp;';

           echo '</td>
           <td align="center">'.htmlspecialchars($row['user_type_name']).'</td>
           <td><input style="width: 100%" type="text" name="user_nicks['.$row['user_id'].']" value="'.htmlspecialchars($row['user_nick']).'"></td>
           <td><input style="width: 100%" type="text" name="user_fios['.$row['user_id'].']" value="'.htmlspecialchars($row['user_fio']).'"></td>
           <td>';
                ?>
                <table>
                    <tr>
                        <td><input type="radio" name="user_sex[<?=$row['user_id']?>]" value="men"<?=($row['user_sex'] == 'men' ? ' checked' : '')?>></td>
                        <td>М&nbsp;</td>
                        <td><input type="radio" name="user_sex[<?=$row['user_id']?>]" value="woman"<?=($row['user_sex'] == 'woman' ? ' checked' : '')?>></td>
                        <td>Ж&nbsp;</td>
                        <td><input type="radio" name="user_sex[<?=$row['user_id']?>]" value="not selected"<?=($row['user_sex'] == 'not selected' ? ' checked' : '')?>></td>
                        <td>?</td>
                    </tr>
                </table>
                <?
           //if ($row['user_sex']) echo htmlspecialchars($row['user_sex']); else echo '&nbsp;';
           echo '</td>
           <td align="center">'; if ($row['email']) echo htmlspecialchars($row['email']); else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['user_extra']) echo htmlspecialchars($row['user_extra']); else echo '&nbsp;'; echo '</td>
           <td align="center">'.($row['email_status'] ? '<img src="/admin/images/icons/tick.png" alt="">' : '&nbsp;').'</td>
           <td align="center">'; if ($row['user_image']) echo '<a href="'.$row['user_image'].'" class="zoom"><img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['user_image']).'" border="0"></a>'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_auth_site_descr.php?id='.$row['user_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_auth_site_user.php?id='.$row['user_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['user_id'].$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['user_id'].$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['user_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }

  echo '<input type="hidden" name="action" id="action" value="">
        <table cellspacing="0" cellpadding="4">
         <tr>
           <td style="padding-left: 6px;"><img src="/admin/images/tree/2.gif" alt=""></td>
           <td class="small" nowrap>с отмеченными:</td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'activate\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb.png" alt="Включить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'reserve\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb-off.png" alt="Выключить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'del\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>
         </tr>
        </table>';  
 echo '</div>';
 echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
 navigation($page, $per_page, $total_rows, $params);
 echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
 echo '</form>';

  }
else echo '<p align="center">Не найдено</p>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>