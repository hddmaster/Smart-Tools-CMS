<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['template_name']) &&
    isset($_POST['template_description']))
 {

 if ($user->check_user_rules('add'))
  {
  if (trim($_POST['template_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
  $template_name = $_POST['template_name'];
  $template_description = $_POST['template_description'];

//проверка на повторы
  if (use_field($template_name,'templates','template_name')) {Header("Location: /admin/design.php?message=duplicate"); exit();}

  //Добавляем...
  $result = mysql_query("insert into templates values (null,'$template_name','$template_description','')");
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
   $template_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
  
      $result = mysql_query("select * from pages");
      if (mysql_num_rows($result) > 0)
       {
         while ($row = mysql_fetch_array($result))
          {
             $objects = unserialize($row['objects']);
             foreach($objects as $block_id => $value)
              {
                foreach($value as $block => $val) 
				 {
				   if ($template_id == $val[1]) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
				 }
              }
          }
       }

      $result = mysql_query("delete from templates where template_id=$template_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
       
      } else $user->no_rules('delete');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Модули</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/modules.php')) $tabs->add_tab('/admin/modules.php', 'Модули');
if ($user->check_user_rules('view','/admin/templates.php')) $tabs->add_tab('/admin/templates.php', 'Шаблоны');
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
		   <td><h2 class="nomargins">Добавить шаблон</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="template_name" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="template_description" maxlength="255"></td>
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
    $sort_by = 'template_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select * from templates $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_description&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_description' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=template_description&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'template_description' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['template_id'].'</td>
           <td>'.htmlspecialchars($row['template_name']).'</td>
           <td>'; if ($row['template_description']) echo htmlspecialchars($row['template_description']); else echo '&nbsp;'; echo '</td>';
           echo '<td nowrap align="center"><a href="javascript:sw(\'/admin/editors/edit_template.php?id='.$row['template_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать шаблон"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['tpl_id']."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>