<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && $_GET['id']!='' &&
   isset($_GET['type']) && $_GET['type']!='' &&
   isset($_POST['content_name'])) {
    if ($user->check_user_rules('edit')) {

        $obj_id = (int)$_GET['id'];
        $type = $_GET['type'];
        if (trim($_POST['content_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$obj_id&message=formvalues"); exit();}
        
        $content_name = ((!get_magic_quotes_gpc()) ? addslashes($_POST['content_name']) : $_POST['content_name']);
        $data = ((!get_magic_quotes_gpc()) ? addslashes($_POST['data']) : $_POST['data']);
        
        // проверка на повторное название
        $result = mysql_query("select * from content where content_name='".stripslashes($content_name)."' and type = $type");
        while ($row = mysql_fetch_array($result)) {
            if ($row['obj_id'] !== $obj_id) {
                header("Location: ".$_SERVER['PHP_SELF']."?id=$obj_id&message=duplicate");
                exit();
            }
        }
   
        if ($type == 'text' || $type == 'code') {
            $result = mysql_query("update content set content_name='$content_name',data='$data' where obj_id=$obj_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$obj_id&message=db"); exit();}
  
            //Обновление кэша модулей
            if ($type == 'code') {
                $cache = new Cache;
                $cache->clear_cache_by_content($obj_id);
            }

            //Обновление страниц сайта с этим модулем
            //$page = new Site_generate;
            //$page->site_generate_by_array($page->find_pages('module', $obj_id));

            $cache = new Cache;
            $cache->clear_all_cache();
        } else {
            header("Location: ".$_SERVER['PHP_SELF']."?id=$obj_id&message=error");
            exit();
        }

        $_SESSION['smart_tools_refresh'] = 'enable';
        header("Location: ".$_SERVER['PHP_SELF']."?id=$obj_id");
        exit();
    } else $user->no_rules('edit');
}

//------------------------------------------------------------------------------
if(isset($_GET['id']))
 {
   $obj_id = (int)$_GET['id'];
   $result = mysql_query("select * from content where obj_id=$obj_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $type = (int)$row['type'];
   if ($type == 1) define ('BODY_ADDED_ATTR', 'onResize="document.getElementsById(\'frame_data\').style.height = (window.innerHeight || document.body.clientHeight) - 100 + \'px\';"');
   if ($type == 0) define ('BODY_ADDED_ATTR', 'onResize="document.getElementById(\'data___Frame\').style.height = (window.innerHeight || document.body.clientHeight) - 100 + \'px\';"');
 }

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $obj_id = (int)$_GET['id'];
   $result = mysql_query("select * from content where obj_id=$obj_id"); if (!$result) exit();
   $row = mysql_fetch_array($result);
   $type = (int)$row['type'];
   $content_name = $row['content_name'];
   $data = $row['data'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

//------------------------------------------------------------------------------
 if ($type == 1)
 {

echo '<form action="?id='.$obj_id.'&type=code" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="content_name" id="content_name" value="'.htmlspecialchars($content_name).'" maxlength="255"></td></tr>
   </table><div>&nbsp;</div>
      <textarea style="width: 100%; height: 390px; visibility: hidden;" name="data" id="data">'.htmlspecialchars($data).'</textarea>
      <div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';

echo '<script>
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
      </script>';

 }

 if ($type == 0)
 {

echo '<form action="?id='.$obj_id.'&type=text" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="content_name" id="" value="'.htmlspecialchars($content_name).'" maxlength="255"></td></tr>
   </table><div>&nbsp;</div>';
   
$oFCKeditor = new FCKeditor('data') ;
$oFCKeditor->BasePath = '/admin/fckeditor/';
$oFCKeditor->ToolbarSet = 'Main' ;
$oFCKeditor->Value = $data;
$oFCKeditor->Width  = '100%';
$oFCKeditor->Height = '350';
$oFCKeditor->Create() ;
echo '<div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';

 }
   if ($type == 1) echo '<script>function setareasize() {document.getElementById(\'frame_data\').style.height = (window.innerHeight || document.body.clientHeight) - 100 + \'px\';} setTimeout("setareasize();", 1500);</script>';
   if ($type == 0) echo '<script>document.body.setAttribute(\'onLoad\',\'document.getElementById("data___Frame").style.height = (window.innerHeight || document.body.clientHeight) - 100 + "px";\');</script>';
	
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>