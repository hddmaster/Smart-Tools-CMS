<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['option_name']) &&
    isset($_POST['option_sname']) &&
    isset($_POST['option_descr']) &&
    isset($_POST['option_type']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['option_name'])=='' ||
       trim($_POST['option_sname'])=='' ||
       trim($_POST['option_type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $option_name = $_POST['option_name'];
   $option_sname = $_POST['option_sname'];
   $option_descr = $_POST['option_descr'];
   $option_type = $_POST['option_type'];

   // проверка на повторное название
   if (use_field($option_sname,'cms_options','option_sname')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   //Добавляем...
   $result = mysql_query("insert into cms_options values (null, '$option_sname', '$option_name', '$option_descr', $option_type, 0, 0, 0, '', '')") or die(mysql_error());
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

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
   $option_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        $result = mysql_query("delete from cms_options where option_id = $option_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
      } else $user->no_rules('delete');
    }
   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
 }


//-----------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Настройки</h1>';

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
		   <td><h2 class="nomargins">Добавить настройку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input type="text" style="width:280px;" name="option_name"></td>
    </tr>
    <tr>
      <td>Короткое название <sup class="red">*</sup><br/><span class="grey">Название уникальной переменной<br/> для быстрого доступа к значению</span></td>
      <td><input type="text" style="width:280px;" name="option_sname"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input type="text" style="width:280px;" name="option_descr"></td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <select style="width:280px;" name="option_type">
          <option value="1">INT (целое число)</option>
          <option value="2">DOUBLE (число с плавающей точкой)</option>
          <option value="3">BOOLEAN (да/нет)</option>
          <option value="4" selected>CHAR (строка)</option>
          <option value="5">TEXT (текст)</option>
          <option value="6">ARRAY (справочник)</option>
         </select>
      </td>
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
    $sort_by = 'option_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select * from cms_options $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Короткое название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_sname&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_sname' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_sname&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_sname' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_descr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_descr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Тип&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_type&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_type' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_type&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'option_type' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>Значение</td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['option_id'].'</td>
           <td align="center">'.htmlspecialchars($row['option_name']).'</td>
           <td align="center"><span class="grey">'.htmlspecialchars($row['option_sname']).'</span></td>
           <td align="center">'; if(!$row['option_descr']) echo '&nbsp;'; else echo htmlspecialchars($row['option_descr']); echo '</td>
           <td align="center">';
           switch ($row['option_type'])
            {
              case 1: echo 'INT (целое число)'; break;
              case 2: echo 'DOUBLE (число с плавающей точкой)'; break;
              case 3: echo 'BOOLEAN (да/нет)'; break;
              case 4: echo 'CHAR (строка)'; break;
              case 5: echo 'TEXT (текст)'; break;
              case 6: echo 'ARRAY (справочник)'; break;
              default: echo '&nbsp;'; break;
            }
           echo '<td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_cms_setting.php?id='.$row['option_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать значение"></a>

           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_cms_option.php?id='.$row['option_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать настройку"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['option_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }

 echo '<fieldset>
       <legend>Внимание!</legend>
       Список всех настроек можно посмотреть в разделе "<a class="grey" href="/admin/help_options.php">Настройки системы</a>"
       </fieldset>';


} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>