<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && $_GET['id']!='' &&
    isset($_POST['tpl_name']) &&
    isset($_POST['tpl_description']) &&
    isset($_POST['data'])) {
    if ($user->check_user_rules('edit')) {
        $tpl_id = (int)$_GET['id'];
        if (trim($_POST['tpl_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&error=formvalues");exit();}
        
        $tpl_name = ((!get_magic_quotes_gpc()) ? addslashes($_POST['tpl_name']) : $_POST['tpl_name']);
        $tpl_description = ((!get_magic_quotes_gpc()) ? addslashes($_POST['tpl_description']) : $_POST['tpl_description']);
        $data = ((!get_magic_quotes_gpc()) ? addslashes($_POST['data']) : $_POST['data']);
        
        //проверка на повторы
        $res = mysql_query("select * from designs where tpl_name='$tpl_name' and tpl_id != $tpl_id");
        if(mysql_num_rows($res) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&error=duplicate");exit();}
        
        $result = mysql_query("update designs set tpl_name='$tpl_name', tpl_description='$tpl_description', data='$data' where tpl_id=$tpl_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id&error=db"); exit();}
        
        //Обновление страниц сайта с этим модулем
        //$page = new Site_generate;
        //$page->site_generate_by_array($page->find_pages('design', $tpl_id));

         $cache = new Cache;
         $cache->clear_all_cache();
        
        $_SESSION['smart_tools_refresh'] = 'enable';
        header("Location: ".$_SERVER['PHP_SELF']."?id=$tpl_id"); exit();
    } else $user->no_rules('edit');
}

// -----------------------------------------------------------------------------
if(isset($_GET['id']))
 {
   $tpl_id = (int)$_GET['id'];
   $result = mysql_query("select * from designs where tpl_id=$tpl_id");
   if (!$result) exit();
   define ('BODY_ADDED_ATTR', 'onResize="document.getElementById(\'frame_data\').style.height = (window.innerHeight || document.body.clientHeight) - 130 + \'px\';"');
 }
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $tpl_id = (int)$_GET['id'];

   $result = mysql_query("select * from designs where tpl_id=$tpl_id");
   if (!$result) exit();
   $row = mysql_fetch_object($result);

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

echo '<form action="?id='.$tpl_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="tpl_name" value="'.htmlspecialchars($row->tpl_name).'" maxlength="255"></td></tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="tpl_description" value="'.htmlspecialchars($row->description).'" maxlength="255"></input></td></tr>
   </table>
   <div>&nbsp;</div>
   <textarea style="width: 100%; height: 370px; visibility: hidden;" name="data" id="data">'.htmlspecialchars($row->data).'</textarea>
   <div>&nbsp;</div><button type="SUBMIT">Сохранить</button>
      </form>';
?>
<script>
editAreaLoader.init({
id: "data"	// id of the textarea to transform		
,start_highlight: true	// if start with highlight
,allow_resize: "both"
,allow_toggle: false
,toolbar: "EditArea"
,word_wrap: false
,language: "en"
,syntax: "php"
,max_undo: 500
});

function setareasize() {document.getElementById('frame_data').style.height = (window.innerHeight || document.body.clientHeight) - 130 + 'px';}
setTimeout("setareasize();", 1500);
</script>
<?
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>