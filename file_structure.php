<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['head']))
 {
   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['head'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $head = $_POST['head'];
   $parent_id = $_POST['parent_id'];

   if(isset($_FILES['file']['name']) &&
      is_uploaded_file($_FILES['file']['tmp_name']))
    {
      $user_file_name = mb_strtolower($_FILES['file']['name'],'UTF-8');
      $type = basename($_FILES['file']['type']);

      //Проверка на наличие файла, замена имени, пока такого файла не будет
      $file = pathinfo($user_file_name);
      $ext = $file['extension'];
      $name_clear = str_replace(".$ext",'',$user_file_name);
      $name = $name_clear;
      $i = 1;
      while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/files/$name.$ext"))
       {
         $name = $name_clear." ($i)";
         $i ++;
       }
      $user_file_name =  $name.'.'.$ext;
    }

    $date = date("YmdHis");

    //Добавляем...
    $query = "insert into files values
              (null,$parent_id,0,1,'$date','$head','',''";
              if(isset($_FILES['file']['name']) &&
                 is_uploaded_file($_FILES['file']['tmp_name']))
              $query = $query.",'/userfiles/files/$user_file_name',0)";
              else
                $query = $query.",'',0)";

    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

    if (isset($_FILES['file']['name']) &&
        is_uploaded_file($_FILES['file']['tmp_name']))
     {
       $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/files/$user_file_name";
       copy($_FILES['file']['tmp_name'], $filename);
       resize($filename, basename($_FILES['file']['type']));
       chmod($filename,0666);
     }

   // перенумеровываем
   $result = mysql_query("select * from files where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['file_id'];
         mysql_query("update files set order_id=$i where file_id = $id");
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

//-----------------------------------------------------------------------------
// AJAX

function save_file_structure($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  $orders = array();
  for($no=0; $no<count($items); $no++)
   {
	   $tokens = explode("-",$items[$no]);
	   if (array_key_exists($tokens[1],$orders)) $orders[$tokens[1]]++;
	   else $orders[$tokens[1]] = 1;

     mysql_query("update files set parent_id = ".$tokens[1].", order_id = ".$orders[$tokens[1]]." where file_id = ".$tokens[0]);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Структура каталога сохранена");
  return $objResponse;
}

$xajax->registerFunction("save_file_structure");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Файлы</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/files.php')) $tabs->add_tab('/admin/files.php', 'Файлы');
if ($user->check_user_rules('view','/admin/file_groups.php')) $tabs->add_tab('/admin/file_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/file_structure.php')) $tabs->add_tab('/admin/file_structure.php', 'Структура', 1);
if ($user->check_user_rules('view','/admin/file_import.php')) $tabs->add_tab('/admin/file_import.php', 'Импорт');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/file_structure.php')) $tabs2->add_tab('/admin/file_structure.php', 'Группы');
if ($user->check_user_rules('view','/admin/file_structure_elements.php')) $tabs2->add_tab('/admin/file_structure_elements.php', 'Файлы');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['file_id'].'">'.$prefix.htmlspecialchars($row['head']).'</option>'."\n";
          show_select($row['file_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

 function show_tree($parent_id = 0)
  {
    $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if (is_begin($row['file_id'], $row['parent_id'])) echo '<ul>'."\n";
          echo '<li id="node'.$row['file_id'].'"';
          if ($row['type'] == 0) echo ' noChildren="true"';
          echo '>';
          echo '<a href="#">'; if ($row['img_path']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" alt="'.$row['img_path1'].'" border="0"> &nbsp; ';
          echo htmlspecialchars($row['head']);
          echo '</a>';
          show_tree($row['file_id']);
          echo '</li>'."\n";
          if (is_end($row['file_id'], $row['parent_id'])) echo '</ul>'."\n";
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == 1 && $row['file_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

function is_end($element_id, $parent_id)
 {
   $result = mysql_query("select * from files where parent_id = $parent_id and type = 1 order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == $num && $row['file_id'] == $element_id) {return true; break;}
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
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="head" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Файл</td>
      <td><input style="width:280px" type="file" name="file"></td></tr>
    <tr>
      <td>Расположение <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'').'
          </select>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

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
	  </form><p>';


echo '<div class="databox">
	<ul id="dhtmlgoodies_tree2" class="dhtmlgoodies_tree">
		<li id="node0" noDrag="true" noSiblings="true" noDelete="true" noRename="true"><a href="#"><strong class="red">Корень каталога</strong></a>';
		
  show_tree(0,"");

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
	  </form><p>';

//------------------------------------------------------------------------------
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
<script type="text/javascript">
function save_tree()
 {
   saveString = treeObj.getNodeOrders();
   xajax_save_file_structure(saveString);
 }

function init()
 {
   treeObj = new JSDragDropTree();
   treeObj.setTreeId('dhtmlgoodies_tree2');
   treeObj.setMaximumDepth(7);
   treeObj.setMessageMaximumDepthReached('Достигнуто максимальное число вложенности структуры каталога!'); // If you want to show a message when maximum depth is reached, i.e. on drop.
   treeObj.initTree();
   //treeObj.expandAll();
 }

begin_delay = 2000;
setTimeout("init()", begin_delay);
</script>