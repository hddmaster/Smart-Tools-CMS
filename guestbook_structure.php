<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name']))
 {
   if ($user->check_user_rules('add'))
   {

   if (trim($_POST['name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $name = $_POST['name'];
   $parent_id = $_POST['parent_id'];
   $user_id = $_POST['user_id'];


    //Добавляем...
    $query = "insert into guestbook (parent_id, type, date, name, user_id)
                                    values
                                    ($parent_id, 1, now(), '$name', $user_id)";
    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   // перенумеровываем
   $result = mysql_query("select * from guestbook where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['text_id'];
         mysql_query("update guestbook set order_id=$i where text_id = $id");
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

function save_guestbook_structure($save_string)
{
  $objResponse = new xajaxResponse();
  $items = explode(",",$save_string);
  $orders = array();
  for($no=0; $no<count($items); $no++)
   {
	   $tokens = explode("-",$items[$no]);
	   if (array_key_exists($tokens[1],$orders)) $orders[$tokens[1]]++;
	   else $orders[$tokens[1]] = 1;

     mysql_query("update guestbook set parent_id = ".$tokens[1].", order_id = ".$orders[$tokens[1]]." where text_id = ".$tokens[0]);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $objResponse->alert("Структура каталога сохранена");
  return $objResponse;
}

$xajax->registerFunction("save_guestbook_structure");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Вопрос - ответ</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/guestbook.php')) $tabs->add_tab('/admin/guestbook.php', 'Сообщения');
if ($user->check_user_rules('view','/admin/guestbook_groups.php')) $tabs->add_tab('/admin/guestbook_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/guestbook_structure.php')) $tabs->add_tab('/admin/guestbook_structure.php', 'Структура');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['text_id'].'">'.$prefix.htmlspecialchars($row['name']).'</option>'."\n";
          show_select($row['text_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

 function show_tree($parent_id = 0)
  {
    $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if (is_begin($row['text_id'], $row['parent_id'])) echo '<ul>'."\n";
          echo '<li id="node'.$row['text_id'].'"';
          if ($row['type'] == 0) echo ' noChildren="true"';
          echo '>';
          echo '<a href="#">';
          echo htmlspecialchars($row['name']);
          echo '</a>';
          show_tree($row['text_id']);
          echo '</li>'."\n";
          if (is_end($row['text_id'], $row['parent_id'])) echo '</ul>'."\n";
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == 1 && $row['text_id'] == $element_id) {return true; break;}
         $k++;
       }
    }
   return false;
 }

function is_end($element_id, $parent_id)
 {
   $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
   $num = mysql_num_rows($result);
   if ($num > 0)
    {
      $k = 1;
      while ($row = mysql_fetch_array($result))
       {
         if ($k == $num && $row['text_id'] == $element_id) {return true; break;}
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

echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Расположение <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'').'
          </select>
      </td>
    </tr>
    <tr>
      <td>Пользователь сайта</td>
      <td><select name="user_id" style="width:280px;">
            <option value="0">---НЕТ---</option>';
      $res = mysql_query("select * from auth_site where type = 0 order by username asc");
      if(mysql_num_rows($res) > 0)
       {
         while($r = mysql_fetch_object($res))
            echo '<option value="'.$r->user_id.'">'.htmlspecialchars($r->username).'</option>';
       }
      echo '</select>
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
   xajax_save_guestbook_structure(saveString);
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