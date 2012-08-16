<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['group_name']) &&
   isset($_POST['group_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['group_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $group_id = (int)$_GET['id'];
   $group_name = $_POST['group_name'];
   $group_descr = $_POST['group_descr'];

   $result = mysql_query("select * from auth_script_groups where group_name = '".stripslashes($group_name)."' and group_id!=$group_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$group_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update auth_script_groups set group_name='$group_name', group_descr='$group_descr' where group_id=$group_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$group_id&message=db"); exit();}

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$group_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['script_id']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('add'))
  {
    $group_id = (int)$_GET['id'];
    $script_id = $_POST['script_id'];
    mysql_query("update auth_scripts set group_id = $group_id where script_id = $script_id");

    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$group_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['script_id']) &&
   isset($_GET['id']) &&
   isset($_GET['action']) && $_GET['action'] == 'delete')
 {
     if ($user->check_user_rules('delete'))
      {
        $script_id = $_GET['script_id'];
        mysql_query("update auth_scripts set group_id = 0 where script_id = $script_id");
        $_SESSION['smart_tools_refresh'] = 'enable';
      }
     else $user->no_rules('delete');
  }

if (isset($_POST['module_id']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('add'))
  {
    if (trim($_POST['module_id'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}"); exit();}
    $group_id = (int)$_GET['id'];
    $module_id = $_POST['module_id'];

    $result = mysql_query("select * from auth_script_group_content where group_id = $group_id and module_id = $module_id");
    if (mysql_num_rows($result) == 0) mysql_query("insert into auth_script_group_content values ($group_id, $module_id)");
    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$group_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['module_id']) &&
   isset($_GET['id']) &&
   isset($_GET['action']) && $_GET['action'] == 'delete')
 {
     if ($user->check_user_rules('delete'))
      {
        $group_id = (int)$_GET['id'];
        $module_id = $_GET['module_id'];
        mysql_query("delete from auth_script_group_content where group_id = $group_id and module_id = $module_id");
        $_SESSION['smart_tools_refresh'] = 'enable';
      }
     else $user->no_rules('delete');
  }

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $group_id = (int)$_GET['id'];
 $result = mysql_query("select * from auth_script_groups where group_id = $group_id") or die(mysql_error());

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $group_name = $row['group_name'];
   $group_descr = $row['group_descr'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$group_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="group_name" value="'.htmlspecialchars($group_name).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="group_descr" value="'.htmlspecialchars($group_descr).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Файлы</td>
      <td>';

   $result = mysql_query("select * from auth_scripts where group_id = $group_id order by script_path asc");
   if (@mysql_num_rows($result) > 0)
    {
      echo '<table cellspacing="0" cellpadding="0" border="0">';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr><td nowrap><span class="grey">'.htmlspecialchars($row['script_path']).'</span> &nbsp; </td>';
         echo '<td nowrap>';
         echo '<a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$group_id&script_id=".$row['script_id']."&action=delete';}";
         echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
       }
      echo '</table>';
    }
   else
     echo 'Нет файлов';
 echo ' </td></tr>
    <tr>
      <td>Модули сайта</td>
      <td>';

   $result = mysql_query("select
                          *
                          from content, auth_script_group_content
                          where content.obj_id = auth_script_group_content.module_id and
                          auth_script_group_content.group_id = $group_id order by content.content_name asc");
   if (@mysql_num_rows($result) > 0)
    {
      echo '<table cellspacing="0" cellpadding="0" border="0">';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr><td nowrap><span class="grey">'.htmlspecialchars($row['content_name']).'</span> &nbsp; </td>';
         echo '<td nowrap>';
         echo '<a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$group_id&module_id=".$row['obj_id']."&action=delete';}";
         echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
       }
      echo '</table>';
    }
   else
     echo 'Нет модулей';
 echo ' </td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
 echo '<h2>Добавить файл</h2>';
 echo '  <form action="edit_auth_script_group.php?id='.$group_id.'" method="post">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td>';
$result = mysql_query("select * from auth_scripts where group_id = 0 order by script_path asc");
if (mysql_num_rows($result) > 0)
 {
   echo '<select style="width:280px" name="script_id">
         <option value="">Выберите файл...</option>';
   while($row = mysql_fetch_array($result))
     echo '<option value='.$row['script_id'].'>'.htmlspecialchars($row['script_path']).'</option>';
   echo'</select>';
 }
echo'</td>
    </tr>
   </table></td>
   <td> &nbsp; <button type="SUBMIT">Добавить</button></td></tr></table>
  </form>';

 echo '<h2>Добавить модуль</h2>';
 echo '  <form action="edit_auth_script_group.php?id='.$group_id.'" method="post">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td>';
$result = mysql_query("select * from content where type = 1 order by content_name asc");
if (mysql_num_rows($result) > 0)
 {
   echo '<select style="width:280px" name="module_id">
         <option value="">Выберите модуль...</option>';
   while($row = mysql_fetch_array($result))
     echo '<option value='.$row['obj_id'].'>'.htmlspecialchars($row['content_name']).'</option>';
   echo'</select>';
 }
echo'</td>
    </tr>
   </table></td>
   <td> &nbsp; <button type="SUBMIT">Добавить</button></td></tr></table>
  </form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>