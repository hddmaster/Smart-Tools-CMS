<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['status_name']) &&
    isset($_POST['status_descr']) &&
    isset($_POST['status_color']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['status_name'])=='' || strlen(trim($_POST['status_color'])) !== 7) {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $status_name = $_POST['status_name'];
   $status_descr = $_POST['status_descr'];
   $status_color = strtolower(substr($_POST['status_color'],1,6));

   // проверка на повторное название
   if (use_field($status_name,'shop_order_status','status_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   //Добавляем...
   $result = mysql_query("insert into shop_order_status values (null, '$status_name', '$status_descr', '$status_color`')");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

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
   $status_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        $result = mysql_query("select * from shop_orders where status_id = $status_id");
        if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
        else
         {
           $result = mysql_query("delete from shop_order_status where status_id=$status_id");
           if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
         }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы', 1);
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/shop_delivery.php')) $tabs2->add_tab('/admin/shop_delivery.php', 'Виды доставки');
if ($user->check_user_rules('view','/admin/shop_order_status.php')) $tabs2->add_tab('/admin/shop_order_status.php', 'Статусы заказов');
if ($user->check_user_rules('view','/admin/shop_couriers.php')) $tabs2->add_tab('/admin/shop_couriers.php', 'Курьеры');
if ($user->check_user_rules('view','/admin/shop_order_statistic.php')) $tabs2->add_tab('/admin/shop_order_statistic.php', 'Статистика');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }
?>
<script>
rr = '<?=substr($status_color,0,2)?>';
gg = '<?=substr($status_color,2,2)?>';
bb = '<?=substr($status_color,4,2)?>';

function setColor(r, g, b) {
	if (r != null) rr = decToHexColor(r);
	if (g != null) gg = decToHexColor(g);
	if (b != null) bb = decToHexColor(b);
	document.getElementById("colorId").style.backgroundColor = "#" + rr + gg + bb;
        document.getElementById("status_color").value = "#" + rr + gg + bb;
}
function decToHexColor(dec) {
	var hex = ['0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F']; 
	dec = parseInt(dec); 
	return hex[parseInt(dec / 16)] + hex[dec % 16]; 
} 

$(document).ready(function(){
	$('#zxc1').trackbar({
		onMove : function() {
			setColor(this.leftValue, null, null);
		},
		dual : false, // two intervals
		width : 200, // px
		leftLimit : 0, // unit of value
		leftValue : <?=hexdec(substr($status_color,0,2))?>, // unit of value
		rightLimit : 255, // unit of value
		rightValue : <?=hexdec(substr($status_color,0,2))?>, // unit of value
		hehe : ":-)"
	});
	$('#zxc2').trackbar({
		onMove : function() {
			setColor(null, this.leftValue, null);
		},
		dual : false, // two intervals
		width : 200, // px
	 	leftLimit : 0, // unit of value
		leftValue : <?=hexdec(substr($status_color,2,2))?>, // unit of value
		rightLimit : 255, // unit of value
		rightValue : <?=hexdec(substr($status_color,2,2))?>, // unit of value
		hehe : ":-)"
	});
	$('#zxc3').trackbar({
		onMove : function() {
			setColor(null, null, this.leftValue);
		},
		dual : false, // two intervals
		width : 200, // px
		leftLimit : 0, // unit of value
		leftValue : <?=hexdec(substr($status_color,4,2))?>, // unit of value
		rightLimit : 255, // unit of value
		rightValue : <?=hexdec(substr($status_color,4,2))?>, // unit of value
		hehe : ":-)"
	});
});
</script>
<?
 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить статус</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post" name="form">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="status_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="status_descr" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Цвет <sup class="red">*</sup><br /><span class="grey">формат: #xxxxxx</span></td>
      <td>
        <fieldset><legend><input style="width:80px" type="text" name="status_color" id="status_color" value="#'.htmlspecialchars($status_color).'" maxlength="7"></legend>
        <div id="colorId" style="border:1px solid #000; background-color:#000; width:50px; height:50px;"></div>
        <div id="zxc1"></div>
        <div id="zxc2"></div>
        <div id="zxc3"></div></fieldset>
      </td>
    </tr>
    </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'status_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
 $query = "select * from shop_order_status $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_descr&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_descr&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Цвет&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_color&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_color' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_color&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'status_color' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['status_id'].'</td>
           <td align="center">'.htmlspecialchars($row['status_name']).'</td>
           <td align="center">'; if(!$row['status_descr']) echo '&nbsp;'; else echo htmlspecialchars($row['status_descr']); echo '</td>
           <td align="center">'; if(!$row['status_color']) echo '&nbsp;'; else echo '<div style="background: #'.$row['status_color'].'; width: 100px;" class="color small white">'.strtoupper(htmlspecialchars($row['status_color'])).'</div>'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_order_status.php?id='.$row['status_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['status_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>