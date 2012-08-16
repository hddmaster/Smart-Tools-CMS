<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['user_type']))
 {
 if ($user->check_user_rules('edit'))
  {

   $user_type = $_POST['user_type'];
   $result = mysql_query("select script_id from auth_scripts");
   if (@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         $script_id = $row['script_id'];
         if (isset($_POST['access_'.$script_id]))
          {
            $acc = $_POST['access_'.$script_id];
            $access = array();
            $key = 0;
            for($i = 0; $i < 5; $i++)
             {
               if (array_key_exists($i,$acc))
                {
                  $access[] = 1;
                  $key++;
                }
               else $access[] = 0;
             }

            $access = serialize($access);
            $res = mysql_query("select * from auth_rules where user_type=$user_type and script_id=$script_id");

            //пустые права
            if (mysql_num_rows($res) > 0 && $key == 0)
              mysql_query("delete from auth_rules where user_type=$user_type and script_id=$script_id");

            //обновляем права
            if (mysql_num_rows($res) > 0 && $key > 0)
              mysql_query("update auth_rules set access='$access' where user_type=$user_type and script_id=$script_id");

            //добавляем права
            if (mysql_num_rows($res) == 0 && $key > 0)
              mysql_query("insert into auth_rules values ($user_type,$script_id,'$access')");
          }
         else
          //удаляем запись, если массив не послан
          mysql_query("delete from auth_rules where user_type=$user_type and script_id=$script_id");
       }
    }
  Header("Location: ".$_SERVER['PHP_SELF']."?user_type=".$_POST['user_type']);
  exit();
  } else $user->no_rules('edit');
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Система</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/auth.php')) $tabs->add_tab('/admin/auth.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_groups.php')) $tabs->add_tab('/admin/auth_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_structure.php')) $tabs->add_tab('/admin/auth_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_users.php')) $tabs->add_tab('/admin/auth_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_rules.php')) $tabs->add_tab('/admin/auth_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_scripts.php')) $tabs->add_tab('/admin/auth_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_script_groups.php')) $tabs->add_tab('/admin/auth_script_groups.php', 'Модули');
if ($user->check_user_rules('view','/admin/auth_history.php')) $tabs->add_tab('/admin/auth_history.php', 'История');
if ($user->check_user_rules('view','/admin/cache.php')) $tabs->add_tab('/admin/cache.php', 'Кэш');
if ($user->check_user_rules('view','/admin/languages.php')) $tabs->add_tab('/admin/languages.php', 'Языки');
if ($user->check_user_rules('view','/admin/currencies.php')) $tabs->add_tab('/admin/currencies.php', 'Валюты');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {

 $result = mysql_query("select user_type,user_type_name from auth_users order by user_type_name asc");
 $types = array();
 while ($row = mysql_fetch_array($result))
  $types[$row['user_type']] = $row['user_type_name'];
 asort($types);

//выпадающее меню типов
 echo '<form action="" method="GET">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td><select style="width:280px" name="user_type">
           <option value="" selected>Выберите тип...</option>';
             //подстановка групп из массива
             foreach ($types as $user_type => $user_type_name)
              {
                echo '<option value="'.$user_type.'"';
                if ((isset($_GET['user_type']) && $_GET['user_type'] == $user_type) || count($types) == 1) echo ' selected';
                echo '>'.htmlspecialchars($user_type_name).'</option>'."\n";
              }
      echo'</select></td>
    </tr>
   </table></td>
   <td> &nbsp; <button type="SUBMIT">Показать</button></td></tr></table>
  </form>';

if (isset($_GET['user_type']) && (int)$_GET['user_type'] > 0) {
 $user_type = $_GET['user_type'];
 $result = mysql_query("select * from auth_scripts order by group_id, script_path asc");
 if (mysql_num_rows($result) > 0)
 {
 echo '<form name="form" action="" method="POST">';
 echo '<p align="right"><button type="submit">Сохранить</button></p>';
 echo '<input type="hidden" name="user_type" value="'.$user_type.'">
       <table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td rowspan="2" nowrap width="50">id</td>
         <td rowspan="2" nowrap>Файл</td>
         <td rowspan="2" nowrap>&nbsp;</td>
         <td colspan="5">Права доступа</td>
       </tr>
       <tr class="header">
         <td width="100" align="center"><span class="small">Просмотр<br/>view</span></td>
         <td width="100" align="center"><span class="small">Добавление<br/>add</span></td>
         <td width="100" align="center"><span class="small">Редактирование<br/>edit</span></td>
         <td width="100" align="center"><span class="small">Действие<br/>action</span></td>
         <td width="100" align="center"><span class="small">Удаление<br/>delete</span></td>
       </tr>
       '."\n";

 $group_id = 0;
 while ($row = mysql_fetch_array($result))
  {
   if ($group_id !== $row['group_id'])
    {
      $res = mysql_query("select * from auth_script_groups where group_id = ".$row['group_id']);
      if (mysql_num_rows($res) > 0)
       {
         $r = mysql_fetch_array($res);
         echo '<tr><td colspan="8">&nbsp;</td></tr><tr><td colspan="8"><h3>'.htmlspecialchars($r['group_name']).'</h3></td></tr>';
       }     
    }

   $group_id = $row['group_id']; 
   $script_id = $row['script_id'];
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$script_id.'</td>
           <td>'.basename($row['script_path']).'<br /><span class="small">'.$row['script_path'].'</td>';
           
  
   $access = array(0, 0, 0, 0, 0);
   $res = mysql_query("select access from auth_rules where user_type=$user_type and script_id=$script_id");
   if (mysql_num_rows($res) > 0)
    {
      $r = mysql_fetch_array($res);
      $access = unserialize($r['access']);
    }
    
   $key = false;
   foreach ($access as $value) {if($value == 1) $key = true;}
    
   echo '<td align="center"><input type="checkbox" name="access_'.$script_id.'"
              onclick="if (document.form.access_'.$script_id.'_0.checked)
                        {
                          document.form.access_'.$script_id.'_0.checked=false;
                          document.form.access_'.$script_id.'_1.checked=false;
                          document.form.access_'.$script_id.'_2.checked=false;
                          document.form.access_'.$script_id.'_3.checked=false;
                          document.form.access_'.$script_id.'_4.checked=false;
                         }
                        else
                         {
                          document.form.access_'.$script_id.'_0.checked=true;
                          document.form.access_'.$script_id.'_1.checked=true;
                          document.form.access_'.$script_id.'_2.checked=true;
                          document.form.access_'.$script_id.'_3.checked=true;
                          document.form.access_'.$script_id.'_4.checked=true;
                         }"'; if ($key) echo ' checked'; echo '></td>';

   $i = 0;
   foreach ($access as $value)
    {
      echo '<td align="center"><input type="checkbox" id="access_'.$script_id.'_'.$i.'" name="access_'.$script_id.'['.$i.']"';
      if ($value == 1) echo ' checked';
      echo '></td>';
      $i++;
    }

   echo '</tr>'."\n";
   }
  echo '</table>'."\n";
  echo '<p align="right"><button type="submit">Сохранить</button></p></form>';
  }
}

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>