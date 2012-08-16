<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['unit_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['unit_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $unit_name = trim($_POST['unit_name']);
  $unit_descr = trim($_POST['unit_descr']);

  if ($unit_name !== '')
   {
     $result = mysql_query("select * from shop_units_of_measure where unit_name = '".stripslashes($unique_name)."'");
     if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}
   }
   
  //уникальная запись!
  $query = "insert into shop_units_of_measure values (null,
                                                   '$unit_name',
                                                   '$unit_descr',
                                                   0)";
  $result = mysql_query($query); if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   Header("Location: ".$_SERVER['PHP_SELF']);
   exit();

  } else $user->no_rules('add');
 }


if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $unit_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

      $result = mysql_query("select * from shop_cat_elements where unit_id=$unit_id");
      if (mysql_num_rows($result) == 0)
       {
         $res = mysql_query("delete from shop_units_of_measure where unit_id = $unit_id");
         if (!$res) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
       }
      else {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}

      //Обновление кэша связанных модулей на сайте
      $cache = new Cache; $cache->clear_cache_by_module();
      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_units_of_measure set status=1 where unit_id=$unit_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_units_of_measure set status=0 where unit_id=$unit_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог', 1);
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары');
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы', 1);
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
if ($user->check_user_rules('view','/admin/shop_cat_structure.php')) $tabs3->add_tab('/admin/shop_cat_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_group_grids.php')) $tabs3->add_tab('/admin/shop_cat_group_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_units_of_measure.php')) $tabs3->add_tab('/admin/shop_units_of_measure.php', 'Единицы измерения');
if ($user->check_user_rules('view','/admin/shop_cat_group_publications.php')) $tabs3->add_tab('/admin/shop_cat_group_publications.php', 'Публикации');
$tabs3->show_tabs();

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
		   <td><h2 class="nomargins">Добавить единицу измерения</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Обозначение <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="unit_name" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="unit_descr" maxlength="255"></td></tr>
  </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><div>&nbsp;</div><span class="grey"><sup>1</sup> Производится проверка на уникальность среди обозначений единиц измерений</span></div></div>';

global $options; $options = '';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
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
    $sort_by = 'unit_id';
    $order = 'desc';
  }

$add = '';
$params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (unit_id like '$query_str' or
           unit_name like '$query_str' or
           unit_descr like '$query_str')";
 }
 
if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '')
 {

   $add .= " and parent_id = ".$_GET['parent_id'];
   $params['parent_id'] = $_GET['parent_id'];
 }

 $query = "select * from shop_units_of_measure where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Обозначение&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_descr&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=unit_descr&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'unit_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">';
   echo '  <td align="center">'.$row['unit_id'].'</td>
           <td>'; if($row['unit_name']) echo htmlspecialchars($row['unit_name']); else echo '&nbsp;'; echo '</td>
           <td>'; if($row['unit_descr']) echo htmlspecialchars($row['unit_descr']); else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_unit_of_measure.php?id='.$row['unit_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['unit_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['unit_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='"; if($_SERVER['REQUEST_URI']!==$_SERVER['PHP_SELF']) echo $_SERVER['REQUEST_URI']; else echo '?'; echo "&action=del&id=".$row['unit_id']."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
}
else echo '<p align="center">Не найдено</p>';
 
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>