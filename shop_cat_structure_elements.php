<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['element_name'])=='' ||
      trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_name = $_POST['element_name'];
  $element_title = $_POST['element_title'];
  $parent_id = $_POST['parent_id'];
  $unit_id = $_POST['unit_id'];

if (isset($_FILES['picture1']['name']) &&
   is_uploaded_file($_FILES['picture1']['tmp_name']))
{
//проверка формата первой картинки
  $user_file_name1 = mb_strtolower($_FILES['picture1']['name'],'UTF-8');
  $type1 = basename($_FILES['picture1']['type']);

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
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name1 =  $name.'.'.$ext;
}

if (isset($_FILES['picture2']['name']) &&
   is_uploaded_file($_FILES['picture2']['tmp_name']))
{
//проверка формата дополнительной картинки
  $user_file_name2 = mb_strtolower($_FILES['picture2']['name'],'UTF-8');
  $type2 = basename($_FILES['picture2']['type']);

  switch ($type2)
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
$file = pathinfo($user_file_name2);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name2);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name2 = $name.'.jpg';
}

if (isset($_FILES['picture3']['name']) &&
   is_uploaded_file($_FILES['picture3']['tmp_name']))
{
//проверка формата дополнительной картинки
  $user_file_name3 = mb_strtolower($_FILES['picture3']['name'],'UTF-8');
  $type3 = basename($_FILES['picture3']['type']);

  switch ($type3)
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
$file = pathinfo($user_file_name3);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name3);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name3 = $name.'.jpg';
}

  $db_img_path1 = ''; if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name'])) $db_img_path1 = "/userfiles/shop_cat_images/$user_file_name1";
  $db_img_path2 = ''; if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name'])) $db_img_path2 = "/userfiles/shop_cat_images/$user_file_name2";
  $db_img_path3 = ''; if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name'])) $db_img_path3 = "/userfiles/shop_cat_images/$user_file_name3";

  $query = "insert into shop_cat_elements
            (parent_id, type, element_name, element_title, img_path1, img_path2, img_path3, unit_id)
            values
            ($parent_id, 0, '$element_name', '$element_title', '$db_img_path1', '$db_img_path2', '$db_img_path3', $unit_id)";
  $result = mysql_query($query) or die (mysql_error());
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$user_file_name1";
     copy($_FILES['picture1']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture1']['type']));
     chmod($filename,0666);
   }

   // перенумеровываем
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update shop_cat_elements set order_id=$i where element_id = $id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   Header("Location: ".$_SERVER['PHP_SELF']);
   exit();
  } else $user->no_rules('add');
 }

//-----------------------------------------------------------------------------
// AJAX

function save_shop_cat_structure($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  $orders = array();
  
  for($no=0; $no<count($items); $no++)
   {
     $tokens = explode("-",$items[$no]);
     if (array_key_exists($tokens[1],$orders)) $orders[$tokens[1]]++;
     else $orders[$tokens[1]] = 1;
     mysql_query("update shop_cat_elements set parent_id = ".$tokens[1].", order_id = ".$orders[$tokens[1]]." where element_id = ".$tokens[0]);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Структура каталога сохранена");
  return $objResponse;
}

$xajax->registerFunction("save_shop_cat_structure");
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
if ($user->check_user_rules('view','/admin/shop_cat_structure_elements.php')) $tabs3->add_tab('/admin/shop_cat_structure_elements.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_grids.php')) $tabs3->add_tab('/admin/shop_cat_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_cat_cards.php')) $tabs3->add_tab('/admin/shop_cat_cards.php', 'Карточки описаний');
if ($user->check_user_rules('view','/admin/shop_cat_producers.php')) $tabs3->add_tab('/admin/shop_cat_producers.php', 'Производители');
if ($user->check_user_rules('view','/admin/shop_cat_sites.php')) $tabs3->add_tab('/admin/shop_cat_sites.php', 'Сайты');
if ($user->check_user_rules('view','/admin/shop_cat_actions.php')) $tabs3->add_tab('/admin/shop_cat_actions.php', 'Акции');
if ($user->check_user_rules('view','/admin/shop_cat_spec.php')) $tabs3->add_tab('/admin/shop_cat_spec.php', 'Спецпредложения');
if ($user->check_user_rules('view','/admin/shop_cat_comments.php')) $tabs3->add_tab('/admin/shop_cat_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/shop_cat_publications.php')) $tabs3->add_tab('/admin/shop_cat_publications.php', 'Публикации');
$tabs3->show_tabs();

if ($user->check_user_rules('view'))
 {

//// проверка приходной и расходной накладных в избежании коллизий.
//$result1 = mysql_query("select * from shop_incoming_tmp");
//$result2 = mysql_query("select * from shop_outgoing_tmp");
//if (mysql_num_rows($result1) == 0 && mysql_num_rows($result2) == 0)
// {

function get_shop_tree(&$shop_tree)
 {
   $result = mysql_query("select * from shop_cat_elements where type = 1 order by order_id asc");
   if(mysql_num_rows($result) > 0)
     while ($row = mysql_fetch_object($result))
       $shop_tree[$row->parent_id][$row->element_id] = $row->element_name;
 }
$shop_tree = array(); get_shop_tree($shop_tree);

function show_select($parent_id = 0, $prefix = '', $selected_element_id = 0, &$shop_tree)
 {
   global $options;
   foreach($shop_tree[$parent_id] as $element_id => $element_name)
    {
      $options .= '<option value="'.$element_id.'"'.($selected_element_id == $element_id ? ' selected' : '').'>'.
                  $prefix.htmlspecialchars($element_name).'</option>';
      show_select($element_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_element_id, $shop_tree);
    }
   return $options;
 }

 function show_tree($parent_id = 0, $selected_parent_id = 0)
  {
    $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if ($row['type'] == 1 || ($row['type'] == 0 && $row['parent_id'] == $selected_parent_id))
           {
             if (is_begin($row['element_id'], $row['parent_id'])) echo '<ul>'."\n";
             echo '<li id="node'.$row['element_id'].'"';
             if ($row['type'] == 0) echo ' noChildren="true"';
             echo '>';
             echo '<a href="#">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" alt="'.$row['img_path1'].'" border="0"> &nbsp; ';
             echo htmlspecialchars($row['element_name']);
             echo '</a>';
             if ($row['type'] == 1) echo ' <span class="grey">&lt;группа&gt;</span>';
             echo ' <span class="grey"> (id: '.$row['element_id'].')</span>';
           }
          show_tree($row['element_id'], $selected_parent_id);
          if ($row['type'] == 1 || ($row['type'] == 0 && $row['parent_id'] == $selected_parent_id))
           {
             echo '</li>'."\n";
             if (is_end($row['element_id'], $row['parent_id'])) echo '</ul>'."\n";
           }
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == 1 && $row['element_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

function is_end($element_id, $parent_id)
 {
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == $num && $row['element_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

 $parent_id = ((isset($_GET['parent_id']) && (int)$_GET['parent_id'] > 0) ? (int)$_GET['parent_id'] :  0);
  
 echo '<form action="" method="GET">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td>Группа<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0, '', $parent_id, $shop_tree).'
          </select>
      </td>
    </tr>
   </table></td>
   <td> &nbsp; <button type="SUBMIT">Показать</button></td></tr></table>
  </form>';

if (isset($_GET['parent_id']))
{

echo '<br/><p>
      <form action="" method="post">
	    <table cellspacing="0" cellpadding="4">
	     <tr>
	       <td><button type="button" onclick="treeObj.collapseAll()">Свернуть</button></td>
	       <td><button type="button" onclick="treeObj.expandAll()">Развернуть</button></td>
           <td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td>
 	       <td><button id="submitbutton" type="button" onclick="save_tree()"><strong>Сохранить</strong></button></td>
         </tr>
        </table>  
	  </form><p>';

echo '<div class="databox">
	<ul id="dhtmlgoodies_tree2" class="dhtmlgoodies_tree">
		<li id="node0" noDrag="true" noSiblings="true" noDelete="true" noRename="true"><a href="#"><strong class="red">Корень каталога</strong></a>';

  show_tree(0, $parent_id);

echo '
		</li>
	</ul></div>';

echo '<p>
      <form action="" method="post">
	    <table cellspacing="0" cellpadding="4">
	     <tr>
	       <td><button type="button" onclick="treeObj.collapseAll()">Свернуть</button></td>
	       <td><button type="button" onclick="treeObj.expandAll()">Развернуть</button></td>
           <td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td>
 	       <td><button id="submitbutton" type="button" onclick="save_tree()"><strong>Сохранить</strong></button></td>
         </tr>
        </table>  
	  </form><p><br/>';
 }
// } else echo 'Добавить или изменить товары в каталоге нельзя до проведения приходной или расходной накладной.';
//------------------------------------------------------------------------------
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
<script type="text/javascript">
function save_tree()
 {
   saveString = treeObj.getNodeOrders();
   xajax_save_shop_cat_structure(saveString);
 }

function init()
 {
   treeObj = new JSDragDropTree();
   treeObj.setTreeId('dhtmlgoodies_tree2');
   treeObj.setMaximumDepth(7);
   treeObj.setMessageMaximumDepthReached('Достигнуто максимальное число вложенности структуры галереи!'); // If you want to show a message when maximum depth is reached, i.e. on drop.
   treeObj.initTree();
   treeObj.expandAll();
 }

begin_delay = 2000;
setTimeout("init()", begin_delay);
</script>