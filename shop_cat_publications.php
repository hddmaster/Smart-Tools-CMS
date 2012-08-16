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
   if (trim($_POST['parent_id'])=='' || trim($_POST['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $head = $_POST['head'];
   $parent_id = $_POST['parent_id'];
   $element_id = $_POST['element_id'];
   $producer_id = ((isset($_POST['producer_id']) && $_POST['producer_id'] > 0) ? $_POST['producer_id'] : 0);

   $hour = intval($_POST['hour']); if ($hour > 23) $hour = 00; if ($hour < 10) $hour = '0'.$hour;
   $minute = intval($_POST['minute']); if ($minute > 59) $minute = 00; if ($minute < 10) $minute = '0'.$minute;
   $second = intval($_POST['second']); if ($second > 59) $second = 00; if ($second < 10) $second = '0'.$second;
   $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2).$hour.$minute.$second;

   if(isset($_FILES['picture']['name']) &&
      is_uploaded_file($_FILES['picture']['tmp_name']))
    {
      $user_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
      $type = basename($_FILES['picture']['type']);

      switch ($type)
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
      $file = pathinfo($user_file_name);
      $ext = $file['extension'];
      $name_clear = str_replace(".$ext",'',$user_file_name);
      $name = $name_clear;
      $i = 1;
      while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$name.$ext"))
       {
         $name = $name_clear." ($i)";
         $i ++;
       }
      $user_file_name =  $name.'.'.$ext;
    }

    //Добавляем...
    $query = "insert into shop_cat_publications values
              (null, $element_id, $producer_id, '$date', '$head', '', ''";
              if(isset($_FILES['picture']['name']) &&
                 is_uploaded_file($_FILES['picture']['tmp_name']))
              $query = $query.",'/userfiles/pub_images/$user_file_name',0)";
              else
                $query = $query.",'',0)";

    $result = mysql_query($query);
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

    if (isset($_FILES['picture']['name']) &&
        is_uploaded_file($_FILES['picture']['tmp_name']))
     {
       $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/pub_images/$user_file_name";
       copy($_FILES['picture']['tmp_name'], $filename);
       resize($filename, basename($_FILES['picture']['type']));
       chmod($filename,0666);
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

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $pub_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
         //удаляем из бд
         mysql_query("delete from shop_cat_publications where pub_id=$pub_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_publications set status=1 where pub_id=$pub_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update shop_cat_publications set status=0 where pub_id=$pub_id") or die(mysql_error());
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id)
{
  $objResponse = new xajaxResponse();
  $select_elements = '<select name="element_id" style="width:280px;" size="11">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].')</option>';
       }
   }
  else $select_elements .= '<option value="">Нет товаров</option>';

  $select_elements .= '</select>';

	$objResponse->assign("elements","innerHTML",$select_elements);
	return $objResponse;
}
$xajax->registerFunction("show_elements");
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
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары');
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

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить публикацию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="0" cellspacing="0" border="0"><tr valign="top"><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Заголовок</td>
      <td><input style="width:280px" type="text" name="head" maxlength="255"></td>
    </tr>
    <tr>
      <td>Фотография</td>
      <td><input style="width:280px" type="file" name="picture"></td></tr>
    <tr>
      <td>Дата <sup class="red">*</sup></td>
      <td>';
?>
    <script>
      LSCalendars["date"]=new LSCalendar();
      LSCalendars["date"].SetFormat("dd.mm.yyyy");
      LSCalendars["date"].SetDate("<?=date("d.m.Y");?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=date("d.m.Y");?>" name="date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>
<?
echo'      </td></tr>
    <tr>
      <td>Время <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td><input type="text" name="hour" value='.date("H").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="minute"  value='.date("i").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
          <input type="text" name="second" value='.date("s").' style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
      </td>
    </tr>
    <tr>
      <td>Группа <sup class="red">*</sup></td>
      <td>
         <select name="parent_id" style="width:280px;" onchange="xajax_show_elements(this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень каталога---</option>'.
         show_select(0, '', 0, $shop_tree)
         .'</select>
      </td>
    </tr>
    <tr>
      <td>Производитель</td>
      <td>';
     $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
     if (mysql_num_rows($res) > 0)
      {
        echo '<select name="producer_id" style="width:280px;"><option value="0">---НЕТ---</option>';
        while ($r = mysql_fetch_array($res))
         {
           echo '<option value="'.$r['producer_id'].'"';
           if ($row->producer_id == $r['producer_id']) echo ' selected';
           echo '>'.htmlspecialchars($r['producer_name']);
           if ($r['producer_descr']) echo '&nbsp; ('.htmlspecialchars($r['producer_descr']).')';
           echo '</option>';
         }
	echo '</select>'; 
      }
    echo '</td>
    </tr>
   </table></td><td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td><td><div id="elements"></div></td></tr></table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

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
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'pub_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
 if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (pub_id like '$query_str' or
                  head like '$query_str' or
                  element_name like '$query_str' or
		  text like '$query_str'  or
                  text_full like '$query_str')";
 }

 $query = "select
           shop_cat_publications.*,
	   date_format(shop_cat_publications.date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   shop_cat_elements.element_name,
	   shop_cat_elements.img_path1,
	   shop_cat_elements.parent_id
           from shop_cat_publications left join shop_cat_elements on shop_cat_publications.element_id = shop_cat_elements.element_id
           where shop_cat_elements.type = 0 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=pub_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=pub_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Заголовок&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
         <td nowrap>Товар</td>
         <td width="35">&nbsp;</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['pub_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td>'.(($row['head']) ? htmlspecialchars($row['head']) : '&nbsp;').'</td>
           <td align="center">'; if ($row['img_path']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" border="0">'; else echo '&nbsp;'; echo '</td>
           <td>'; if ($row['element_name']) echo htmlspecialchars($row['element_name']); else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" border="0">'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_cat_pub_text.php?id='.$row['pub_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_shop_cat_pub.php?id='.$row['pub_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['pub_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>