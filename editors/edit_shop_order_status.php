<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['status_name']) &&
   isset($_POST['status_descr']) &&
   isset($_POST['status_color']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['status_name'])=='' || strlen(trim($_POST['status_color'])) !== 7) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $status_id = (int)$_GET['id'];
   $status_name = $_POST['status_name'];
   $status_descr = $_POST['status_descr'];
   $status_color = strtolower(substr($_POST['status_color'],1,6));

   $result = mysql_query("select * from shop_order_status where status_name = '".stripslashes($status_name)."' and status_id!=$status_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$status_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_order_status set status_name='$status_name', status_descr='$status_descr', status_color='$status_color' where status_id=$status_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$status_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$status_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $status_id = (int)$_GET['id'];
 $result = mysql_query("select * from shop_order_status where status_id = $status_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $status_name = $row['status_name'];
   $status_descr = $row['status_descr'];
   $status_color = $row['status_color'];

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

 echo '<form action="?id='.$status_id.'" method="post" name="form">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="status_name" value="'.htmlspecialchars($status_name).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="status_descr" value="'.htmlspecialchars($status_descr).'" maxlength="255">
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
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>