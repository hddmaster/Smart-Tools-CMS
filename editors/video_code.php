<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
    $element_id = (int)$_GET['id'];
    $result = mysql_query("select
                           *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date
                           from video
                           where element_id=$element_id");

    if (!$result) exit();
    $row = mysql_fetch_array($result);
    $code = '<embed align="center"
	            type="application/x-shockwave-flash"
	            src="http://'.$_SERVER['HTTP_HOST'].'/admin/player/player.swf"
	            width="640"
		    height="360"
		    allowscriptaccess="always"
		    allowfullscreen="true"
		    flashvars="file=http://'.$_SERVER['HTTP_HOST'].rawurlencode($row['video_path']).'&image=http://'.$_SERVER['HTTP_HOST'].rawurlencode($row['img_path']).'&width=640&height=360&skin=http://'.$_SERVER['HTTP_HOST'].'/admin/player/modieus.swf"
	     /></embed>';
    echo '<div align="center">'.$code.'</div><div>&nbsp;</div>
          <textarea rows="5" style="width:100%">'.htmlspecialchars($code).'</textarea>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>