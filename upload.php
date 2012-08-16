<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_FILES['picture']['name']) &&
   is_uploaded_file($_FILES['picture']['tmp_name']) &&
   isset($_GET['iframe']))
 {
   $user_file_name = '';
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
      default: header ("Location: ".$_SERVER['PHP_SELF']."?iframe_upload=".(intval($_GET['iframe']))); exit(); break;
    }
   
   //Проверка на наличие файла, замена имени, пока такого файла не будет
   $file = pathinfo($user_file_name);
   $ext = $file['extension'];
   $name_clear = str_replace(".$ext",'',$user_file_name);
   $name = $name_clear;
   $i = 1;
   while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/spool_images/$name.$ext"))
    {
      $name = $name_clear." ($i)";
      $i++;
    }
   $user_file_name = $name.'.jpg';

   $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_images/$user_file_name";
   copy($_FILES['picture']['tmp_name'], $filename);
   resize($filename, basename($_FILES['picture']['type']));
   chmod($filename,0666);
   header ("Location: ".$_SERVER['PHP_SELF']."?iframe_upload=".(intval($_GET['iframe']))."&image=".rawurlencode('/userfiles/spool_images/'.$user_file_name));
 }
// -----------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if ($user->check_user_rules('view'))
 {
?>
<script type="text/javascript">
document.body.style.background = '#dddddd';
document.body.style.padding = '0px';
</script>
<table cellspacing="0" cellpadding="0" width="100%" height="100%" style="background:#dddddd;"><tr><td width="100%" height="100%">
<?

if (isset($_GET['iframe'])) {
?>
<script type="text/javascript">
function submitform()
 {
   document.getElementById('picture_name').innerHTML = '<img src="/admin/images/loading.gif" alt="">';
   document.uform.submit();
 }
function basename(path, suffix) {    // Returns filename component of path
    // 
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Ash Searle (http://hexmen.com/blog/)
    // +   improved by: Lincoln Ramsay
    // +   improved by: djmix
 
    var b = path.replace(/^.*[\/\\]/g, '');
    if (typeof(suffix) == 'string' && b.substr(b.length-suffix.length) == suffix) {
        b = b.substr(0, b.length-suffix.length);
    }
    return b;
}
</script>

<form name="uform" id="uform" action="?iframe=<?=intval($_GET['iframe']);?>" method="post" enctype="multipart/form-data">
<div id="picture_div"><input style="width:280px" type="file" name="picture" onchange="document.getElementById('picture_name').innerHTML = '<table cellspacing=\'0\' cellpadding=\'0\'><tr><td class=\'help\' nowrap>' + basename(document.getElementById('picture').value) + '</td><td><a style=\'cursor: pointer;\' onclick=\'parent.xajax_delete_iframe(<?=intval($_GET['iframe']);?>);\'><img src=\'/admin/images/icons/cross.png\' alt=\'Удалить\' border=\'0\'></a></td></tr></table>';
                                                                                      document.getElementById('picture_div').style.display = 'none';
                                                                                      parent.xajax_add_iframe(<?=intval($_GET['iframe'])+1;?>);"
                             style="background: #ffffff;"/></div>
<div id="picture_name"></div>
</form>
<?
}
if (isset($_GET['iframe_upload']))
{
  echo '<script>
          parent.xajax_upload_images('.intval($_GET['iframe_upload']).');
          if (parent.document.getElementById(\'add_fotos_value\').value-1 == '.intval($_GET['iframe_upload']).')
          parent.xajax_import_images();
        </script>';
  echo '<img src="/admin/images/img_resize.php?image='.rawurlencode($_GET['image']).'&size=30" alt="">';
}

echo '</td></tr></table>';
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>