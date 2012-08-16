<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

$users = array();
$result  = mysql_query("select username from auth_site where type = 0");
if (mysql_num_rows($result) > 0)
 {
   while ($row = mysql_fetch_object($result))
     $users[] = $row->username;
 }

if (is_uploaded_file($_FILES['csv']['tmp_name']))
 {
   if ($user->check_user_rules('add'))
    {
      if (trim($_POST['user_type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

      $rows = file($_FILES['csv']['tmp_name']);
      $rows_count = count($rows);

      $s = array('0','1','2','3','4','5','6','7','8','9',
                 'a','b','c','d','e','f','g','h','i','j',
                 'k','l','m','n','o','p','q','r','s','t',
                 'u','v','w','x','y','z','A','B','C','D',
                 'E','F','G','H','I','J','K','L','M','N',
                 'O','P','Q','R','S','T','U','V','W','X',
                 'Y','Z');
      
      $user_type = ((isset($_POST['user_type']) && (int)$_POST['user_type'] > 0) ? (int)$_POST['user_type'] : 0); 
      $parent_id = ((isset($_POST['parent_id']) && (int)$_POST['parent_id'] > 0) ? (int)$_POST['parent_id'] : 0); 
      $lang_id = ((isset($_POST['lang_id']) && (int)$_POST['lang_id'] > 0) ? (int)$_POST['lang_id'] : 0); 
      $generate_password = ((isset($_POST['password']) && (int)$_POST['password'] > 0) ? true : false);
      $status = ((isset($_POST['status']) && (int)$_POST['status'] > 0) ? (int)$_POST['status'] : 0);
      
      for($i = 0; $i < $rows_count; $i++)
       {
         $tds = explode(";", $rows[$i]);
         if (is_array($tds))
          {
            $username = (isset($tds[0]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[0]))) : '');
            $password = (isset($tds[1]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[1]))) : '');

            if($generate_password)
	     {
	       $password = '';
               for ($ip = 0; $ip < 6; $ip++) $password .= $s[rand(0,count($s)-1)];
	     }

	    $user_fio = (isset($tds[2]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[2]))) : ''); 
	    $user_nick = (isset($tds[3]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[3]))) : '');
	    $user_birthday = (isset($tds[4]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[4]))) : '');
	    $user_phone = (isset($tds[5]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[5]))) : '');
	    $user_icq = (isset($tds[6]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[6]))) : '');
	    $user_address = (isset($tds[7]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[7]))) : '');
	    $email = (isset($tds[8]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[8]))) : '');
	    $user_extra = (isset($tds[9]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[9]))) : '');
	    $is_ur = (isset($tds[10]) ? 1 : 0);
	    $ur_company_name = (isset($tds[11]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[11]))) : '');
	    $ur_inn = (isset($tds[12]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[12]))) : '');
	    $ur_kpp = (isset($tds[13]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[13]))) : '');
	    $ur_address = (isset($tds[14]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[14]))) : '');
	    $ur_bank = (isset($tds[15]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[15]))) : '');
	    $ur_rs = (isset($tds[16]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[16]))) : '');
	    $ur_ks = (isset($tds[17]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[17]))) : '');
	    $ur_bik = (isset($tds[18]) ? iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[18]))) : '');
	    if ($username)
	     {
	       $q = "insert into auth_site_import
	             (parent_id, username, password, user_type, user_nick, user_fio, user_birthday, user_phone, user_icq, user_address, email, user_extra, is_ur, ur_company_name, ur_inn, ur_kpp, ur_address, ur_bank, ur_rs, ur_ks, ur_bik, lang_id, status)
		     values
		     ($parent_id, '$username', '$password', $user_type, '$user_nick', '$user_fio', '$user_birthday', '$user_phone', '$user_icq', '$user_address', '$email', '$user_extra', $is_ur, '$ur_company_name', '$ur_inn', '$ur_kpp', '$ur_address', '$ur_bank', '$ur_rs', '$ur_ks', '$ur_bik', $lang_id, $status)";
	       $res = mysql_query($q) or die($q.mysql_error());
               if (!$res) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
	     }
          }
       }
    }
 }

if (isset($_GET['action']) && trim($_GET['action']) !== '')
 {
   $action = $_GET['action'];

   if ($action == 'import' && $user->check_user_rules('action'))
    {
      $users_i_usernames = array();
      $users_i_ids = array();
      $result = mysql_query("select * from auth_site_import");
      if (mysql_num_rows($result) > 0)
       {
         while ($row = mysql_fetch_object($result))
           if(!in_array($row->username, $users_i_usernames) && !in_array($row->username, $users)) $users_i_ids[] = $row->user_id; 
       }
       
      $result = mysql_query("select * from auth_site_import");
      if (mysql_num_rows($result) > 0)
       {
         while ($row = mysql_fetch_object($result))
          {
	    if(in_array($row->user_id, $users_i_ids))
	     {
	       $q = "insert into auth_site
	             (parent_id, username, password, user_type, user_nick, user_fio, user_birthday, user_phone, user_icq, user_address, email, user_extra, is_ur, ur_company_name, ur_inn, ur_kpp, ur_address, ur_bank, ur_rs, ur_ks, ur_bik, lang_id, status)
		     values
		     ($row->parent_id, '".addslashes($row->username)."', '".addslashes($row->password)."', $row->user_type, '".addslashes($row->user_nick)."', '".addslashes($row->user_fio)."', '$row->user_birthday', '".addslashes($row->user_phone)."', '".addslashes($row->user_icq)."', '".addslashes($row->user_address)."', '".addslashes($row->email)."', '".addslashes($row->user_extra)."', $row->is_ur, '".addslashes($row->ur_company_name)."', '".addslashes($row->ur_inn)."', '".addslashes($row->ur_kpp)."', '".addslashes($row->ur_address)."', '".addslashes($row->ur_bank)."', '".addslashes($row->ur_rs)."', '".addslashes($row->ur_ks)."', '".addslashes($row->ur_bik)."', $row->lang_id, $row->status)";
	       $res = mysql_query($q) or die($q.mysql_error());
               if (!$res) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
	     }
	  }
       }

      mysql_query("truncate table auth_site_import");
      header("Location: ".$_SERVER['PHP_SELF']."?message=imported");  
    }
   
   if ($action == 'clear' && $user->check_user_rules('action'))
    {
      mysql_query("truncate table auth_site_import");
      header("Location: ".$_SERVER['PHP_SELF']."?message=cleared");  
    }
 }
 
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

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

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
          $options .= '>'.$prefix.htmlspecialchars($row['username']).'</option>'."\n";
          show_select($row['user_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

$res = mysql_query("select * from auth_site_import");
if (mysql_num_rows($res) == 0)
{
 echo '<form action="" method="post" enctype="multipart/form-data">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>CSV <sup class="red">*</sup></td>
      <td>
      <input style="width:280px" name="csv" type="file" maxlength="255"></td></tr>
    <tr>
      <td>Тип пользователя <sup class="red">*</sup></td>
      <td><select style="width:280px" name="user_type">
          <option value="" selected>Выберите тип...</option>';
    $result = mysql_query("select user_type,user_type_name from auth_site_users order by user_type_name asc");
    while ($row = mysql_fetch_array($result))
      echo '<option value="'.$row['user_type'].'">'.htmlspecialchars($row['user_type_name']).'</option>'."\n";
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
      <td>Язык</td>
      <td><select style="width:280px" name="lang_id">
           <option value="0" selected>Базовый язык</option>';
    $result = mysql_query("select * from languages order by lang_code asc");
    while ($row = mysql_fetch_array($result))
     {
       echo '<option value="'.$row['lang_id'].'">'.htmlspecialchars($row['lang_code']);
       if ($row['lang_name']) echo ' ('.htmlspecialchars($row['lang_name']).')';
       echo '</option>'."\n";
     }
      echo'</select></td></tr>
    <tr>
      <td>Формировать пароль автоматически</td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
          <td><input type="radio" name="password" value="1" checked></td>
          <td>&nbsp;Да</td>
          <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
          <td><input type="radio" name="password" value="0"></td>
          <td>&nbsp;Нет</td>
         </tr>
        </table>
      </td>
    </tr>
   <tr>
     <td>Сразу активировать пользователей</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="status" value="1" checked></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="status" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
   </table><br>
   <fieldset><legend>Описание формата импортируемого файла</legend>CSV-разделитель ";".<br /><br />Логин ; Пароль; Ник ; Ф.И.О. ; Дата рождения ; Телефон ; ICQ ; Адрес ; email ; Доп. информация ; Юр. лицо? ; Название организации ; ИНН ; КПП ; Юридический адрес ; Банк ; Расчетный счет ; Корреспондентский счет ; БИК</fieldset>
   <div>&nbsp;</div>
   <button type="SUBMIT">Загрузить файл</button>
  </form>';
}
elseif (mysql_num_rows($res) > 0)
{
      echo '<div>Просмотрите содержимое импортируемого файла. Если все верно, нажмите клавишу "Загрузить данные", в противном случае "Очистить".</div>';
      echo '<p align="right"><button onclick="location.href=\'?action=import\'">Загрузить данные</button> &nbsp; <button onclick="location.href=\'?action=clear\'" class="red">Очистить</button></p>'; 

      echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">
            <tr class="header">
	      <td>Группа</td>
	      <td>Логин</td>
	      <td>Пароль</td>
	      <td>Тип пользователя</td>
	      <td>Ник</td>
	      <td>Ф.И.О.</td>
	      <td>Дата рождения</td>
	      <td>Телефон</td>
	      <td>ICQ</td>
	      <td>Адрес</td>
	      <td>e-mail</td>
	      <td>Дополнительная информация</td>
	      <td>Юр. лицо?</td>
	      <td>Название организации</td>
	      <td>ИНН</td>
	      <td>КПП</td>
	      <td>Юридический адрес</td>
	      <td>Банк</td>
	      <td>Расчетный счет</td>
	      <td>Корреспондентский счет</td>
	      <td>БИК</td>
	      <td>Язык</td>
            </tr>';

      while ($r = mysql_fetch_object($res))
       {
         echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#efefef\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline'.((in_array($r->username, $users)) ? ' red' : '').'">
                 <td>'.($r->group_name ? $r->group_name : '-').'</td>
                 <td>'.($r->username ? $r->username : '-').'</td>
                 <td>'.($r->password ? $r->password : '-').'</td>
                 <td>'.($r->user_type_name ? $r->user_type_name : '-').'</td>
                 <td>'.($r->user_nick ? $r->user_nick : '-').'</td>
                 <td>'.($r->user_fio ? $r->user_fio : '-').'</td>
                 <td>'.($r->user_birthday ? $r->user_birthday : '-').'</td>
                 <td>'.($r->user_phone ? $r->user_phone : '-').'</td>
                 <td>'.($r->user_icq ? $r->user_icq : '-').'</td>
                 <td>'.($r->user_address ? $r->user_address : '-').'</td>
                 <td>'.($r->email ? $r->email : '-').'</td>
                 <td>'.($r->user_extra ? $r->user_extra : '-').'</td>
                 <td>'.($r->is_ur ? $r->is_ur : '-').'</td>
                 <td>'.($r->ur_comapny_name ? $r->ur_comapny_name : '-').'</td>
                 <td>'.($r->ur_inn ? $r->ur_inn : '-').'</td>
                 <td>'.($r->ur_kpp ? $r->ur_kpp : '-').'</td>
                 <td>'.($r->ur_address ? $r->ur_address : '-').'</td>
                 <td>'.($r->ur_bank ? $r->ur_bank : '-').'</td>
                 <td>'.($r->ur_rs ? $r->ur_rs : '-').'</td>
                 <td>'.($r->ur_ks ? $r->ur_ks : '-').'</td>
                 <td>'.($r->ur_bik ? $r->ur_bik : '-').'</td>
                 <td>'.($r->lang_name ? $r->lang_name : '-').'</td>
               </tr>';
	 if(!in_array($r->username, $users)) $users[] = $r->username;
       }
      echo '</table>';
      echo '<p align="right"><button onclick="location.href=\'?action=import\'">Загрузить данные</button> &nbsp; <button onclick="location.href=\'?action=clear\'" class="red">Очистить</button></p>'; 
}

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>