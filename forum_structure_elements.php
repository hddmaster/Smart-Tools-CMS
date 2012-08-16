<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

//-----------------------------------------------------------------------------
// AJAX

function save_forum_structure($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  $orders = array();
  for($no=1; $no<count($items); $no++)
   {
	 $tokens = explode("-",$items[$no]);
 	 if (array_key_exists($tokens[1],$orders)) $orders[$tokens[1]]++;
     else $orders[$tokens[1]] = 1;
     mysql_query("update forum set parent_id = ".$tokens[1].", order_id = ".$orders[$tokens[1]]." where element_id = ".$tokens[0]);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Структура сохранена");
  return $objResponse;
}





$xajax->registerFunction("save_forum_structure");


//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Форум</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/forum.php')) $tabs->add_tab('/admin/forum.php', 'Темы');
if ($user->check_user_rules('view','/admin/forum_groups.php')) $tabs->add_tab('/admin/forum_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/forum_structure.php')) $tabs->add_tab('/admin/forum_structure.php', 'Структура', 1);
if ($user->check_user_rules('view','/admin/forum_comments.php')) $tabs->add_tab('/admin/forum_comments.php', 'Комментарии');
$tabs->show_tabs();

$tabs2 = new Tabs;
if ($user->check_user_rules('view','/admin/forum_structure.php')) $tabs2->add_tab('/admin/forum_structure.php', 'Группы');
if ($user->check_user_rules('view','/admin/forum_structure_elements.php')) $tabs2->add_tab('/admin/forum_structure_elements.php', 'Темы');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_selected = 0)
  {
    global $options;
    $result = mysql_query("select * from forum where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($row['element_id'] == $parent_id_selected) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'], $prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_selected);
        }
    }
    return $options;
  }

 function show_tree($parent_id = 0)
  {
    $result = mysql_query("select * from forum where parent_id = $parent_id order by order_id asc");
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
          if ($row['type'] == 1) echo ' <span class="grey">&lt;группа&gt;</span>';
          echo ' <span class="grey"> (id: '.$row['element_id'].')</span>';
          show_tree($row['element_id']);
          echo '</li>'."\n";
          if (is_end($row['element_id'], $row['parent_id'])) echo '</ul>'."\n";
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from forum where parent_id = $parent_id order by order_id asc");
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
   $result = mysql_query("select * from forum where parent_id = $parent_id order by order_id asc");
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


 $parent_id = 0;
 $element_name = 'Корень форума';
 if (isset($_GET['parent_id']) && $_GET['parent_id'] !== '0')
  {
    $parent_id = $_GET['parent_id'];
    $result = mysql_query("select * from forum where element_id = $parent_id");
    $row = mysql_fetch_array($result);
    $element_name = $row['element_name'];
  }
 echo '<form action="" method="GET">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td>Группа<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень форума---</option>
            '.show_select(0,'',$parent_id).'
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


echo '<div style="border: #CCCCCC 1px solid;">
	<ul id="dhtmlgoodies_tree2" class="dhtmlgoodies_tree">
		<li id="node'.$parent_id.'" noDrag="true" noSiblings="true" noDelete="true" noRename="true"><a href="#"><strong class="red">'.htmlspecialchars($element_name).'</strong></a>';
		
  show_tree($parent_id,'');

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

//------------------------------------------------------------------------------
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
<script type="text/javascript">
function save_tree()
 {
   saveString = treeObj.getNodeOrders();
   xajax_save_forum_structure(saveString);
 }

function init()
 {
   treeObj = new JSDragDropTree();
   treeObj.setTreeId('dhtmlgoodies_tree2');
   treeObj.setMaximumDepth(7);
   treeObj.setMessageMaximumDepthReached('Достигнуто максимальное число вложенности структуры!'); // If you want to show a message when maximum depth is reached, i.e. on drop.
   treeObj.initTree();
   //treeObj.expandAll();
 }

begin_delay = 2000;
setTimeout("init()", begin_delay);
</script>