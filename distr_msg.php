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
   $date = date("YmdHis");

   $tpl_id = $_POST['tpl_id'];
   $data = '';
   
   $result = mysql_query("select data from distr_templates where tpl_id = $tpl_id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $data = addslashes($row['data']);
    }

//Добавляем...
   $result = mysql_query("insert into distr_msg values (null, '$date', '$head', '$data','')");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

if (isset($_FILES['distr_file']['name']) && is_uploaded_file($_FILES['distr_file']['tmp_name']))
 {
   $id = mysql_insert_id();
   $user_file_name = mb_strtolower($_FILES['distr_file']['name'],'UTF-8');

   $distribution_files_path = $user->get_cms_option('distribution_files_path');
   $filename = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$id.'/'.$user_file_name;
   $dirname = $_SERVER['DOCUMENT_ROOT'].$distribution_files_path.$id;

   mkdir($dirname,  0777);
   if (file_exists($filename)) {Header("Location: ".$_SERVER['PHP_SELF']."?message=fileexist"); exit();}
   copy($_FILES['distr_file']['tmp_name'], $filename);
   chmod($filename,0666);
 }

   Header("Location: ".$_SERVER['PHP_SELF']);
   exit();
  } else $user->no_rules('add');
 }


if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $msg_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
      //удаляем из бд
      $result = mysql_query("select file_path from distr_msg where msg_id=$msg_id");
      $row = mysql_fetch_array($result);
      $filename = $row['file_path'];
      $dirname = $_SERVER['DOCUMENT_ROOT']."/userfiles/mailfiles/$msg_id";
      @unlink($filename);
      @rmdir($dirname);

      $result = mysql_query("delete from distr_msg where msg_id=$msg_id");
      if (!$result) {Header("Location: /admin/distr_msg?message=db"); exit();}
      $result = mysql_query("update distr set msg_id=0 where msg_id=$msg_id");
      if (!$result) {Header("Location: /admin/distr_msg?message=db"); exit();}
      else {Header("Location: ".$_SERVER['PHP_SELF']);exit();}
      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action')) mysql_query("update distr_msg set status=1 where msg_id=$msg_id");
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         //при изменении рассылки обнуляем msg_id в distr
         $result = mysql_query("select msg_id from distr where msg_id=$msg_id");
         if (mysql_num_rows($result) > 0)
           mysql_query("update distr set msg_id=0 where msg_id=$msg_id");

         mysql_query("update distr_msg set status=0 where msg_id=$msg_id");
       }
      else $user->no_rules('action');
    }

 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Рассылка сообщений</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/distribution.php')) $tabs->add_tab('/admin/distribution.php', 'Рассылки');
if ($user->check_user_rules('view','/admin/distr_msg.php')) $tabs->add_tab('/admin/distr_msg.php', 'Сообщения');
if ($user->check_user_rules('view','/admin/distr_templates.php')) $tabs->add_tab('/admin/distr_templates.php', 'Шаблоны');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить сообщение</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="head" maxlength="255"></td></tr>
    <tr>
      <td>Шаблон</td>
      <td><select name="tpl_id" style="width: 280px;">
        <option value="0">Выберите шаблон...</option>';
 $result = mysql_query("select * from distr_templates order by tpl_name asc");
 if (mysql_num_rows($result) > 0)
  {
   while($row = mysql_fetch_array($result))
    echo '<option value="'.$row['tpl_id'].'">'.htmlspecialchars($row['tpl_name']).'</option>'."\n";
  }
echo '</select></td>
    </td>
    <tr>
      <td>Файл<br><span class="grey">Вложенный в сообщение файл<br>(будет храниться на сервере)</span></td>
      <td><input style="width:280px" type="file" name="distr_file"></td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

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
    $sort_by = 'msg_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select
           *,
           date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2
           from distr_msg $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=msg_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'msg_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=msg_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'msg_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="100%" nowrap>Заголовок&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['msg_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td><strong><a href="javascript:sw(\'/admin/editors/edit_distr_msg.php?id='.$row['msg_id'].'\');">'.htmlspecialchars($row['head']).'</a></strong></td>
           <td nowrap align="center"><a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='distr_msg.php?action=del&id=".$row['msg_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

  echo '<fieldset><legend>Внимание</legend>Вы можете использовать в шаблонах переменные {username}, {user_fio}, {user_nick}, {user_address}, {email}.
  Во всех сообщениях при отправке они будут заменяться соответствующими данными пользователей, если они указаны. В случае, если одно из полей {user_fio} или {user_nick} не заполненено, оно будет заменено значением поля {username}.
  Использование специальной переменной {key} позволяет сделать ссылку для автоматической авторизации на сайте: например, http://'.$_SERVER['HTTP_HOST'].'/?key={key}.
  </fieldset>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>