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
  $result = mysql_query("insert into pub_comments values (null, $element_id, '$date', '$text', '$name', 0, 0)");
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
         mysql_query("delete from pub_comments where comment_id=$comment_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update pub_comments set status=1 where comment_id=$comment_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update pub_comments set status=0 where comment_id=$comment_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }

//-----------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Публикации</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/publications.php')) $tabs->add_tab('/admin/publications.php', 'Публикации');
if ($user->check_user_rules('view','/admin/pub_groups.php')) $tabs->add_tab('/admin/pub_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/pub_structure.php')) $tabs->add_tab('/admin/pub_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/pub_comments.php')) $tabs->add_tab('/admin/pub_comments.php', 'Комментарии');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

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
                  P.head like '$query_str' or
                  PC.name like '$query_str' or
                  A.username like '$query_str' or
		  PC.text like '$query_str')";
 }

 $query = "select
           PC.*,
	   date_format(PC.date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   P.head,
	   P.parent_id,
           A.username
           from pub_comments as PC left join publications as P on PC.pub_id = P.pub_id
                                  left join auth_site as A on A.user_id = PC.user_id
           where 1 $add";
 $result = mysql_query($query) or die(mysql_error()); $total_rows = mysql_num_rows($result);
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
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
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['comment_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td>'.($row['username'] ? htmlspecialchars($row['username']) : ($row['name'] ? htmlspecialchars($row['name']) : '&nbsp;')).'</td>
           <td>';
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
              $res = mysql_query("select head from publications where pub_id = ".$row['parent_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo htmlspecialchars($r['head']);
               }
              else echo '&nbsp;';
            }
           echo '</td>
           <td>'; if ($row['head']) echo htmlspecialchars($row['head']); else echo '&nbsp;'; echo '</td>
           <td><span class="small">'.htmlspecialchars($row['text']).'</span></td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_pub_comment.php?id='.$row['comment_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать комментарий"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['comment_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
 }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>