<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['action']))
 {
   $action = $_GET['action'];

   if ($action == 'del' && isset($_GET['id']))
    {
     if ($user->check_user_rules('delete'))
     {
      $dump_id = (int)$_GET['id'];
      $result = mysql_query("select file_path from sql_dumps where dump_id=$dump_id");
      if (mysql_num_rows($result) > 0)
       {
          $row = mysql_fetch_array($result);
          $filename = $_SERVER['DOCUMENT_ROOT']."/sql_dumps/".$row['file_path'];
          @unlink($filename);
          mysql_query("delete from sql_dumps where dump_id=$dump_id");
       }
     } else $user->no_rules('delete');
    }

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');
echo '<h1>Администрирование БД</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/sql.php')) $tabs->add_tab('/admin/sql.php', 'SQL');
if ($user->check_user_rules('view','/admin/reserv.php')) $tabs->add_tab('/admin/reserv.php', 'Резервирование базы данных');
$tabs->add_tab('/admin/phpmyadmin/', 'phpMyAdmin');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {

 echo '<br /><form name="form" action="" method="get" target="sql_dump" onsubmit="javascript:sw(\'/admin/sql_dump.php?action=reserv\');">
       <button type="submit">Создать копию</button></form>';

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
    $sort_by = 'dump_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2 from sql_dumps $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=dump_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'dump_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=dump_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'dump_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название файла&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file_path&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'file_path' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=file_path&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'file_path' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Размер файла</td>
         <td nowrap width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   $filename = $_SERVER['DOCUMENT_ROOT']."/sql_dumps/".$row['file_path'];
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['dump_id'].'</td>
           <td align="center">'.$row['date2'].'</td>
           <td align="center"><a href="/sql_dumps/'.$row['file_path'].'">'.$row['file_path'].'</a></td>
           <td align="center">'.round(filesize($filename)/1024,2).' Kb</td>
           <td align="center"><a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='reserv.php?action=del&id=".$row['dump_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }

 echo '<fieldset>
       <legend>Внимание!</legend>
       В целях повышения безопасности папка, в которой храняться файлы, защищена паролем.
       </fieldset>';

  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>