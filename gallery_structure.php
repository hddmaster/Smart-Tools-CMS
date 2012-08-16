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

  if (trim($_POST['element_name'])=='' || trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_name = $_POST['element_name'];
  $parent_id = $_POST['parent_id'];

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
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name1 =  $name.'.'.$ext;
}

  //уникальная запись! Добавляем в каталог...
  $query = "insert into gallery values (null,
                                        $parent_id,
                                        0,
                                        1,
                                        ".date("YmdHis").",
                                        '$element_name',
                                        '',
                                        ''";
  if (isset($_FILES['picture1']['name']) &&
   is_uploaded_file($_FILES['picture1']['tmp_name']))
  $query .= ", '"."/userfiles/gallery_images/$user_file_name1"."'";
  else $query .= ", ''";

  $query = $query.", '', '', 0, 0, '', '')";

  $result = mysql_query($query) or die (mysql_error());
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$user_file_name1";
     copy($_FILES['picture1']['tmp_name'], $filename);
     resize($filename, basename($_FILES['picture1']['type']));
     chmod($filename,0666);
   }

   // перенумеровываем
   $result = mysql_query("select * from gallery where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update gallery set order_id=$i where element_id = $id");
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

function save_gallery_structure($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  $orders = array();
  for($no=0; $no<count($items); $no++)
   {
	   $tokens = explode("-",$items[$no]);
	   if (array_key_exists($tokens[1],$orders)) $orders[$tokens[1]]++;
	   else $orders[$tokens[1]] = 1;

     mysql_query("update gallery set parent_id = ".$tokens[1].", order_id = ".$orders[$tokens[1]]." where element_id = ".$tokens[0]);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Структура галереи сохранена");
  return $objResponse;
}

$xajax->registerFunction("save_gallery_structure");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Галерея</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/gallery.php')) $tabs->add_tab('/admin/gallery.php', 'Публикации');
if ($user->check_user_rules('view','/admin/gallery_groups.php')) $tabs->add_tab('/admin/gallery_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/gallery_structure.php')) $tabs->add_tab('/admin/gallery_structure.php', 'Структура', 1);
if ($user->check_user_rules('view','/admin/gallery_comments.php')) $tabs->add_tab('/admin/gallery_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/gallery_import.php')) $tabs->add_tab('/admin/gallery_import.php', 'Импорт');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/gallery_structure.php')) $tabs2->add_tab('/admin/gallery_structure.php', 'Группы');
if ($user->check_user_rules('view','/admin/gallery_structure_elements.php')) $tabs2->add_tab('/admin/gallery_structure_elements.php', 'Публикации');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("select * from gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'">'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

 function show_tree($parent_id = 0)
  {
    $result = mysql_query("select * from gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if (is_begin($row['element_id'], $row['parent_id'])) echo '<ul>'."\n";
          echo '<li id="node'.$row['element_id'].'"';
          if ($row['type'] == 0) echo ' noChildren="true"';
          echo '>';
          echo '<a href="#">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" alt="'.$row['img_path1'].'" border="0"> &nbsp; ';
          echo htmlspecialchars($row['element_name']);
          echo '</a>';
          show_tree($row['element_id']);
          echo '</li>'."\n";
          if (is_end($row['element_id'], $row['parent_id'])) echo '</ul>'."\n";
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from gallery where parent_id = $parent_id and type = 1 order by order_id asc");
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
   $result = mysql_query("select * from gallery where parent_id = $parent_id and type = 1 order by order_id asc");
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

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить группу</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название группы <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="element_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture1"/></td>
    </tr>
    <tr>
      <td>Расположение группы <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'').'
          </select>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form></div></div>';

echo '<div>&nbsp;</div>
      <form action="" method="post">
	    <table cellspacing="0" cellpadding="4">
	     <tr>
	       <td><button type="button" onclick="treeObj.collapseAll()">Свернуть</button></td>
	       <td><button type="button" onclick="treeObj.expandAll()">Развернуть</button></td>
           <td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td>
 	       <td><button id="submitbutton" type="button" onclick="save_tree()"><strong>Сохранить</strong></button></td>
         </tr>
        </table>  
	  </form><div>&nbsp;</div>';
echo '<div class="databox">
      <ul id="dhtmlgoodies_tree2" class="dhtmlgoodies_tree">
      <li id="node0" noDrag="true" noSiblings="true" noDelete="true" noRename="true"><a href="#"><strong class="red">Корень галереи</strong></a>';
      show_tree(0,"");
echo '</li></ul></div>';
echo '<div>&nbsp;</div>
      <form action="" method="post">
	    <table cellspacing="0" cellpadding="4">
	     <tr>
	       <td><button type="button" onclick="treeObj.collapseAll()">Свернуть</button></td>
	       <td><button type="button" onclick="treeObj.expandAll()">Развернуть</button></td>
           <td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td>
 	       <td><button id="submitbutton" type="button" onclick="save_tree()"><strong>Сохранить</strong></button></td>
         </tr>
        </table>  
	  </form>';

//------------------------------------------------------------------------------
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
<script type="text/javascript">
function save_tree()
 {
   saveString = treeObj.getNodeOrders();
   xajax_save_gallery_structure(saveString);
 }

function init()
 {
   treeObj = new JSDragDropTree();
   treeObj.setTreeId('dhtmlgoodies_tree2');
   treeObj.setMaximumDepth(7);
   treeObj.setMessageMaximumDepthReached('Достигнуто максимальное число вложенности структуры галереи!'); // If you want to show a message when maximum depth is reached, i.e. on drop.
   treeObj.initTree();
   //treeObj.expandAll();
 }

begin_delay = 2000;
setTimeout("init()", begin_delay);
</script>