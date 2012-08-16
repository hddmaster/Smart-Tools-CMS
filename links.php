<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['head_input']) && isset($_POST['link']))
 {
   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['link'])=='' || trim($_POST['head_input'])=='' || !isset($_POST['category'])) {Header("Location: /admin/links.php?message=formvalues"); exit();}

   $head = $_POST['head_input'];
   $cat_id = $_POST['category'];
   $link = $_POST['link'];
   $date = date("YmdHis");

  //Добавляем...
  $result = mysql_query("insert into links values (null,$cat_id,'$date','$head','$link',0)");
  if (!$result) {Header("Location: /admin/links.php?message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
   } else $user->no_rules('add');
 }

//анализатор параметров запуска и процессор
if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $link_id = $_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
      //удаляем из бд
      $result = mysql_query("delete from links where link_id=$link_id");
      if (!$result) {Header("Location: /admin/links.php?message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

       } else $user->no_rules('delete');
    }
   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
	 mysql_query("update links set status=1 where link_id=$link_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
	 mysql_query("update links set status=0 where link_id=$link_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }

//-----------------------------------------------------------------------------
// AJAX

function check_name($value)
{
  $text = "";
  $value = trim($value);
  
  if ($value !== '')
   {
     $result = mysql_query("select * from links where head = '$value'");
     if (mysql_num_rows($result) > 0)
      {
        $text .= 'Найдено '.mysql_num_rows($result).' записей';
      }
     else $text .= 'Записей не найдено';
   }
  else $text = 'Введите пожалуйста значание';

	$objResponse = new xajaxResponse('windows-1251');
	$objResponse->alert($text);
	$objResponse->assign("check_button","value","Проверить");
	$objResponse->assign("check_button","disabled",false);

	return $objResponse;
}

$xajax->registerFunction("check_name");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Ссылки</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/links.php')) $tabs->add_tab('/admin/links.php', 'Ссылки');
if ($user->check_user_rules('view','/admin/links_cats.php')) $tabs->add_tab('/admin/links_cats.php', 'Cписок категорий');
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
		   <td><img src="/admin/images/icons/plus.gif" alt=""></td>
		   <td><h2 class="nomargins">Добавить ссылку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="links.php" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="head_input" maxlength="255"><button type="button" id="check_button" onclick="document.getElementById(\'check_button\').disabled = true; document.getElementById(\'check_button\').value = \'Проверка...\'; xajax_check_name(document.getElementById(\'head_input\').value);">Проверить</button>
      </td>
    </tr>
    <tr>
      <td>Текст ссылки <sup class="red">*</sup><br><span class="grey">HTML-код ссылки</span></td>
      <td><textarea style="width:280px" name="link" cols="52" rows="10"></textarea></td></tr>
    <tr>
      <td>Категория ссылки <sup class="red">*</sup></td>
      <td nowrap>';
    $result = mysql_query ("select * from links_cats order by order_id asc");
    if (@mysql_num_rows($result) > 0)
     {
       echo '<select name="category" style="width:280px;">
             <option value="">Выберите категорию...</option>';
       while ($row = mysql_fetch_object($result))
        {
          echo '<option value="'.$row->cat_id.'">'.htmlspecialchars($row->cat_name).'</option>';
        }
       echo '</select>';
     }
    else echo 'Нет категорий';
echo '</td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сорировка
 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'date';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select
           links_cats.cat_name,
           links.*,
           date_format(links.date, '%d.%m.%Y (%H:%i:%s)') as date2
           from
           links, links_cats
           where links.cat_id = links_cats.cat_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">id&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=link_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'link_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=link_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'link_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Категория&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=cat_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'cat_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=cat_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'cat_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['link_id'].'</td>
           <td align="center">'.$row['date2'].'</td>
           <td>'.htmlspecialchars($row['cat_name']).'</td>
           <td>';
           if ($row['head'] !== '') echo htmlspecialchars($row['head']);
           else echo '&nbsp;';
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_link.php?id='.$row['link_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать ссылку"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['link_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Видимость на сайте"></a>';
           else echo '<a href="?action=reserve&id='.$row['link_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Видимость на сайте"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['link_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

} else $user->no_rules('view');
require_once ("$DOCUMENT_ROOT/admin/tpl/admin_footer.php");
?>