<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['producer_name']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['producer_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $producer_name = $_POST['producer_name'];
   $producer_descr = $_POST['producer_descr'];
   $producer_title = $_POST['producer_title'];
   $producer_meta_keywords = trim($_POST['producer_meta_keywords']);
   $producer_meta_description = trim($_POST['producer_meta_description']);

   // проверка а повторное название
   if (use_field($producer_name,'shop_cat_producers','producer_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

if (isset($_FILES['producer_picture']['name']) &&
   is_uploaded_file($_FILES['producer_picture']['tmp_name']))
{
//проверка формата первой картинки
  $user_file_name1 = mb_strtolower($_FILES['producer_picture']['name'],'UTF-8');
  $type1 = basename($_FILES['producer_picture']['type']);

  switch ($type1)
   {
    case 'jpeg': break;
    case 'pjpeg': break;
    case 'png': break;
    case 'x-png': break;
    case 'gif': break;
    case 'bmp': break;
    case 'wbmp': break;
    default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
   }
//Проверка на наличие файла, замена имени, пока такого файла не будет
$file = pathinfo($user_file_name1);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name1);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_producers/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name1 =  $name.'.'.$ext;
}

   
   //Добавляем...
   $img_path = '';
   if (isset($_FILES['producer_picture']['name']) && is_uploaded_file($_FILES['producer_picture']['tmp_name'])) $img_path = '/userfiles/shop_cat_producers/'.$user_file_name1;
   $result = mysql_query("insert into shop_cat_producers (producer_name,
                                                          producer_descr,
                                                          producer_title,
                                                          producer_meta_keywords,
                                                          producer_meta_description,
                                                          producer_picture)
                                                         values
                                                         ('$producer_name',
                                                          '$producer_descr',
                                                          '$producer_title',
                                                          '$producer_meta_keywords',
                                                          '$producer_meta_description',
                                                          '$img_path')");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   if (isset($_FILES['producer_picture']['name']) &&
   is_uploaded_file($_FILES['producer_picture']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_producers/$user_file_name1";
     copy($_FILES['producer_picture']['tmp_name'], $filename);
     //resize($filename, basename($_FILES['producer_picture']['type']));
     chmod($filename,0666);
   }

   // перенумеровываем
   $result = mysql_query("select * from shop_cat_producers order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['producer_id'];
         mysql_query("update shop_cat_producers set order_id = $i where producer_id = $id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }


if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $producer_id = (int)$_GET['id'];

    if ($action == 'del') {
        if ($user->check_user_rules('delete')) {
            $result = mysql_query("select * from shop_cat_elements where producer_id = $producer_id");
            if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}

            $result = mysql_query("delete from shop_cat_producers where producer_id=$producer_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module(); 
        } else $user->no_rules('delete');
    }

    if ($action == 'activate') {
        if ($user->check_user_rules('action')) {
            mysql_query("update shop_cat_producers set status=1 where producer_id=$producer_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('action');
    }
   
    if ($action == 'reserve') {
        if ($user->check_user_rules('action')) {
            mysql_query("update shop_cat_producers set status=0 where producer_id=$producer_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('action');
    }
}
//-----------------------------------------------------------------------------
// AJAX

function save_shop_cat_producers_order($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  
  $objResponse->alert($save_string);

  $i = 1;
  for($no=0; $no<count($items); $no++)
   {
     $tokens = explode("-",$items[$no]);
     mysql_query("update shop_cat_producers set order_id  = $i where producer_id = ".$tokens[0]);
     $i++;
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Порядок сортировки производителей сохранен");
  return $objResponse;
}

$xajax->registerFunction("save_shop_cat_producers_order");
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
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары', 1);
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы');
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_cat_structure_elements.php')) $tabs3->add_tab('/admin/shop_cat_structure_elements.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_grids.php')) $tabs3->add_tab('/admin/shop_cat_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_cat_cards.php')) $tabs3->add_tab('/admin/shop_cat_cards.php', 'Карточки описаний');
if ($user->check_user_rules('view','/admin/shop_cat_producers.php')) $tabs3->add_tab('/admin/shop_cat_producers.php', 'Производители', 1);
if ($user->check_user_rules('view','/admin/shop_cat_sites.php')) $tabs3->add_tab('/admin/shop_cat_sites.php', 'Сайты');
if ($user->check_user_rules('view','/admin/shop_cat_actions.php')) $tabs3->add_tab('/admin/shop_cat_actions.php', 'Акции');
if ($user->check_user_rules('view','/admin/shop_cat_spec.php')) $tabs3->add_tab('/admin/shop_cat_spec.php', 'Спецпредложения');
if ($user->check_user_rules('view','/admin/shop_cat_comments.php')) $tabs3->add_tab('/admin/shop_cat_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/shop_cat_publications.php')) $tabs3->add_tab('/admin/shop_cat_publications.php', 'Публикации');
$tabs3->show_tabs();

$tabs4 = new Tabs;
$tabs4->level = 3;
if ($user->check_user_rules('view','/admin/shop_cat_producer_publications.php')) $tabs4->add_tab('/admin/shop_cat_producer_publications.php', 'Публикации');
$tabs4->show_tabs();

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
		   <td><h2 class="nomargins">Добавить производителя</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data"  action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="producer_name" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="producer_descr" maxlength="255"></td>
    </tr>
    <tr>
      <td>Заголовок страницы сайта<br /><span class="grey">TITLE</span></td>
      <td><input style="width:280px" type="text" name="producer_title" maxlength="255"></td></tr>
    <tr>
      <td>Ключевые слова<br /><span class="grey">meta keyrords</span></td>
      <td><input style="width:280px" type="text" name="producer_meta_keywords" maxlength="255"></td></tr>
    <tr>
      <td>Описание<br /><span class="grey">meta description</span></td>
      <td><input style="width:280px" type="text" name="producer_meta_description" maxlength="255"></td></tr>
    <tr>
     <td>Фотография</td>
     <td><input style="width:280px" type="file" name="producer_picture"/></td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0"  width="100%">
   <tr>

   <td width="50%" style="padding-right: 10px;">
   </td>

   <td width="50%" style="padding-left: 10px;">

   <table cellspacing="0" cellpadding="4" align="right">
    <tr>
      <td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table>
  
  </td></tr></table></form>';

// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'producer_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (producer_id like '$query_str' or
                  producer_name like '$query_str' or
                  producer_descr like '$query_str')";
 }
 
if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '')
 {
   $add .= " and parent_id = ".$_GET['parent_id'];
   $params['parent_id'] = $_GET['parent_id'];
 }

 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);
 
 $query = "select * from shop_cat_producers where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Артикул 1С&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=c_store_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'c_store_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=c_store_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'c_store_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_descr&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_descr&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Заголовок страницы сайта&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_title&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_title&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
         <td>Товаров<br />в каталоге</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['producer_id'].'</td>
           <td align="center">'.htmlspecialchars($row['producer_name']).'</td>
           <td align="center">'.(($row['c_store_name']) ? htmlspecialchars($row['c_store_name']) : '&nbsp;').'</td>
           <td align="center">'.(($row['producer_descr']) ? htmlspecialchars($row['producer_descr']) : '&nbsp;').'</td>
           <td align="center">'.(($row['producer_title']) ? htmlspecialchars($row['producer_title']) : '&nbsp;').'</td>
           <td align="center">'.(($row['producer_picture']) ? '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['producer_picture']).'" border="0">' : '&nbsp;').'</td>
           <td align="center">';
           
           $res = mysql_query("select count(*) as c from shop_cat_elements where type = 0 and producer_id = ".$row['producer_id']);
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_object($res);
              echo (($r->c) ? $r->c : '&nbsp;');
            } else echo '&nbsp;';
           
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_cat_producer_descr.php?id='.$row['producer_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_shop_cat_producer.php?id='.$row['producer_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать производителя"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['producer_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['producer_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['producer_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
	<script type="text/javascript">
	function save_tree()
   {
  	 saveString = treeObj.getNodeOrders();
     xajax_save_shop_cat_producers_order(saveString);
   }

	treeObj = new JSDragDropTree();
	treeObj.setTreeId('dhtmlgoodies_tree2');
	treeObj.setMaximumDepth(7);
	treeObj.setMessageMaximumDepthReached('Достигнуто максимальное число вложенности структуры!'); // If you want to show a message when maximum depth is reached, i.e. on drop.
	treeObj.initTree();
	treeObj.expandAll();
	</script>