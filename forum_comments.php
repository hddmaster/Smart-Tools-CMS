<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name']) &&
    isset($_POST['text']))
 {

   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['name'])=='' || trim($_POST['text'])=='' || trim($_POST['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues");exit();}
   $name = $_POST['name'];
   $text = $_POST['text'];
   $element_id = $_POST['element_id'];
   $date = date("YmdHis");

  //Добавляем...
  $result = mysql_query("insert into forum_comments values (null, $element_id, '$date', '$text', '$name', 0, 0)");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

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
   $comment_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
         //удаляем из бд
         mysql_query("delete from forum_comments where comment_id=$comment_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update forum_comments set status=1 where comment_id=$comment_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update forum_comments set status=0 where comment_id=$comment_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id)
{
  $objResponse = new xajaxResponse();
  $select_elements = '<select name="element_id" style="width:280px;" size="15">';

  $result = mysql_query("select * from forum where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].')</option>';
       }
   }
  else $select_elements .= '<option value="">Нет публикаций</option>';

  $select_elements .= '</select>';

	$objResponse->assign("elements","innerHTML",$select_elements);
	return $objResponse;
}



$xajax->bOutputEntities = true;
$xajax->setLogFile("/logs/xajax_errors.log");



$xajax->registerFunction("show_elements");


//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Форум</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/forum.php')) $tabs->add_tab('/admin/forum.php', 'Темы');
if ($user->check_user_rules('view','/admin/forum_groups.php')) $tabs->add_tab('/admin/forum_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/forum_structure.php')) $tabs->add_tab('/admin/forum_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/forum_comments.php')) $tabs->add_tab('/admin/forum_comments.php', 'Комментарии');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM forum where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_added == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
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
		   <td><h2 class="nomargins">Добавить комментарий</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellspacing="0" cellpadding="0"><tr valign="top"><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Имя <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="name" maxlength="255"></td></tr>
    <tr>
      <td>Текст <sup class="red">*</sup></td>
      <td><textarea style="width:280px" name="text" cols="52" rows="10"></textarea></td></tr>
    <tr>
      <td>Группа <sup class="red">*</sup></td>
      <td>
         <select name="parent_id" style="width:280px;" onchange="xajax_show_elements(this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень форума---</option>'.
         show_select()
         .'</select>
      </td></tr>
   </table></td><td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td><td><div id="elements"></div></td></tr></table><br>
   <button type="SUBMIT">Добавить</button>
   </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>
   
   <td width="100%">&nbsp;</td> 
    
   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

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
    $sort_by = 'comment_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (comment_id like '$query_str' or
           element_name like '$query_str' or
		   text like '$query_str')";
 }

 $query = "select
           forum_comments.*,
	   date_format(forum_comments.date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   forum.element_name,
	   forum.img_path1,
	   forum.parent_id
           from forum_comments, forum
           where forum_comments.element_id = forum.element_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=comment_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=comment_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Имя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Группа</td>
         <td nowrap>Название</td>
         <td nowrap>Текст&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'text' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'text' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['comment_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td>'.htmlspecialchars($row['name']).'</td>
           <td>';
           if ($row['parent_id'] == 0) echo '---Корень форума---';
           else
            {
              $res = mysql_query("select element_name from forum where element_id = ".$row['parent_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo htmlspecialchars($r['element_name']);
               }
              else echo '&nbsp;';
            }
           echo '</td>
           <td>'; if ($row['element_name']) echo htmlspecialchars($row['element_name']); else echo '&nbsp;'; echo '</td>
           <td><span class="small">'.htmlspecialchars($row['text']).'</span></td>
           <td align="center">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" border="0">'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_forum_comment.php?id='.$row['comment_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать комментарий"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>