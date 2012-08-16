<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

   $path_clear = '';
   $path = $_SERVER['DOCUMENT_ROOT'];
   chdir($path);

if (isset($_GET['path']) && $_GET['path'] != '' && $_GET['path'] != '.' && $_GET['path'] != '..' && $_GET['path'] != '/')
 {
   if (!is_dir($_SERVER['DOCUMENT_ROOT'].$_GET['path'])) {Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrect_path");}
   $path_clear = $_GET['path'];
   $path = $_SERVER['DOCUMENT_ROOT'].$path_clear;
   chdir($path);
 }
//------------------------------------------------------------------------------
if (isset($_GET['path_']) && $_GET['path_']!='' &&
    isset($_GET['action']) && $_GET['action'] != '')
 {
   $action = $_GET['action'];
   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
      $handler = $_SERVER['DOCUMENT_ROOT'].$_GET['path_'];
      if (is_dir($handler)) {if(!@rmdir($handler)) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=not_empty");}
      else {if(!@unlink($handler)) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=norules");}
      } else $user->no_rules('delete');
    }
 }

if (isset($_FILES['file']['name']))
 {
 if ($user->check_user_rules('action'))
  {
   if (!is_uploaded_file($_FILES['file']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=formvalues");exit();}   $user_file_name = strtolower($_FILES['file']['name']);
   if (file_exists("$path/$user_file_name")) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=duplicate");
   copy($_FILES['file']['tmp_name'],"$path/$user_file_name");
   chmod("$path/$user_file_name", 0666);
   Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear));
  } else $user->no_rules('action');
 }

if (isset($_POST['dir_name']) && isset($_POST['rules']))
 {
 if ($user->check_user_rules('action'))
  {
   if (trim($_POST['dir_name'])=='' || trim($_POST['rules'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=formvalues");exit();}
   $dir_name = "$path/".$_POST['dir_name'];
   $rules = $_POST['rules'];
   if (file_exists($dir_name)) Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear)."&message=duplicate");
   umask(0);
   mkdir($dir_name, 0777);
   chmod($dir_name, intval($rules,8));
   Header("Location: ".$_SERVER['PHP_SELF']."?path=".urlencode($path_clear));
  } else $user->no_rules('action');
 }

//------------------------------------------------------------------------------
//директории для блокирования
function is_system($path_to_dir)
 {
  if ($path_to_dir)
   {
     $dir = explode('/', $path_to_dir);
     switch ($dir[1])
      {
        case "admin" :
        case "sql_dumps" :
        default: return false; break;
      }
   }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Файловый менеджер</h1>';

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

if (!is_system("$path_clear"))
 {

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Создать папку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="?path='.urlencode($path_clear).'" method="post">
  <table cellpadding="0" cellspacing="0 border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="dir_name" maxlength="255"></td>
      <td><input style="width:30px" type="text" name="rules" value="0777" maxlength="4" onKeyPress ="if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td>
    </tr>
   </table></td><td width="10">&nbsp;</td><td><button type="SUBMIT">Создать</button></td></table>
  </form><br/></div></div>';
  
 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Загрузить файл в текущую папку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';
  

 echo '<table cellpadding="0" cellspacing="0 border="0"><tr><td>
  <form enctype="multipart/form-data" action="?path='.urlencode($path_clear).'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Файл</td>
      <td><input style="width:280px" type="file" name="file"></input></td>
    </tr>
   </table></td><td width="10">&nbsp;</td><td><button type="SUBMIT">Загрузить</button></td></table>
  </form><br /></div></div>';
  
 }

    // постраничный вывод
    if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
    if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
    $start=abs($page*$per_page);

    $dirs = array();
    $links = array();
    $files = array();


    if ($handle = opendir($path)) {
	while (false !== ($file = readdir($handle))) {
	    if ($file != "." && $file != "..") {
	        if (is_dir($file)) $dirs[] = $file;
	        elseif (is_link($file)) $links[] = $file;
	        else $files[] = $file;
	    }
	}
	closedir($handle);
    }	

echo '<div style="padding: 4px;border: #CCCCCC 1px solid;" class="grey">';
      if ($path_clear == '') echo 'Корневая папка';
      else echo $path_clear;
      echo '</div>';

$params = array();
if (isset($_GET['path']) && trim($_GET['path']) !== '')
  $params['path'] = trim($_GET['path']);      

navigation($page, $per_page, count($files) + count($dirs), $params);
     
echo '<div class="databox">
      <table cellpadding="4" cellspacing="0" border="0" width="100%">
      <tr align="center" class="header"><td colspan="2">Имя</td>
        <td>Размер</td>
        <td>Права</td>
        <td>Дата</td>
        <td>&nbsp;</td>
      </tr>';

  if ($path !== $_SERVER['DOCUMENT_ROOT'])
   {
     $cur_dir = '/'.basename($path_clear);
     $path_up = str_replace($cur_dir,'',$path_clear);
     echo '<tr align="center" nowrap>
             <td width="16px"><a href="?path='.urlencode($path_up).'&per_page='.$per_page.'"><img src="/admin/images/icons/folder-horizontal-open.png" alt="" border="0"></a></td>
             <td align="left"><strong>[<a href="?path='.urlencode($path_up).'&per_page='.$per_page.'">..</a>]</strong></td>
           </tr>';
   }

if (count($files) > 0 || count($dirs) > 0)
 {
  $i = 1;
  asort($dirs);
  foreach ($dirs as $dir)
   {
    if ($i > $start && $i <= $start+$per_page)
     {
             echo '<tr align="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
             <td width="16px"><a href="?path='.urlencode("$path_clear/$dir").'"><img src="/admin/images/icons/folder.png" alt="" border="0"></a></td>
             <td nowrap align="left"><strong><a href ="?path='.urlencode("$path_clear/$dir").'">['.$dir.']</a></strong></td>
             <td><span class="grey">&lt;DIR&gt;</span></td>
             <td>'.substr(sprintf('%o', fileperms("$path/$dir")), -4).'</td>
             <td nowrap>'.date("d.m.Y (H:i:s)", filemtime("$path/$dir")).'</td>
             <td nowrap width="120">';
             if (!is_system("$path_clear/$dir"))
              {
                echo '<a href="javascript:sw(\'/admin/editors/edit_dir.php?path='.urlencode("$path_clear/$dir").'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать директорию"></a>
                &nbsp;<a href="';
                echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?path=".urlencode(urlencode("$path_clear"))."&action=del&path_=".urlencode(urlencode("$path_clear/$dir"))."';}";
                echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';
              }
             else echo '<span class="grey">системная папка</span>';
             echo '</td>
           </tr>'."\n";
     }
     $i++;
   }

  asort($links);
  foreach ($links as $link)
   {
    if ($i > $start && $i <= $start+$per_page)
     {
     echo '<tr align="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
             <td width="16px"><a target="_blank" href="'."$path_clear/$link".'"><img src="/admin/images/icons/document-globe.png" alt="Открыть файл в новом окне" border="0"></a></td>
             <td nowrap align="left">'.$link.'</td>
             <td><span class="small">link</span></td>
             <td>'.substr(sprintf('%o', fileperms("$path/$link")), -4).'</td>
             <td nowrap>'.date("d.m.Y (H:i:s)", filemtime("$path/$link")).'</td>
             <td nowrap width="120">';
             if (!is_system("$path_clear"))
              {
                echo '<a href="';
                echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?path=".urlencode(urlencode("$path_clear"))."&action=del&path_=".urlencode(urlencode("$path_clear/$link"))."';}";
                echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';
              }
             else echo '<span class="grey">системный файл</span>';
             echo '</td>
         </tr>'."\n";
     }
     $i++;
   }

  asort($files);
  foreach ($files as $file)
   {
    if ($i > $start && $i <= $start+$per_page)
     {
      //rename("$path/$file", "$path/".iconv('WINDOWS-1251','UTF-8',$file));
     echo '<tr align="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
             <td width="16px"><a target="_blank" href="'."$path_clear/$file".'"><img src="/admin/images/icons/document.png" alt="Открыть файл в новом окне" border="0"></a></td>
             <td nowrap align="left">'.$file.'</td>
             <td>'.round(filesize("$path/$file")/1024,2).' Kb</td>
             <td>'.substr(sprintf('%o', fileperms("$path/$file")), -4).'</td>
             <td nowrap>'.date("d.m.Y (H:i:s)", filemtime("$path/$file")).'</td>
             <td nowrap width="120">';
             if (!is_system("$path_clear"))
              {
                echo '<a href="javascript:sw(\'/admin/editors/edit_file.php?path='.urlencode("$path_clear/$file").'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать файл"></a>
                &nbsp;<a href="';
                echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?path=".urlencode(urlencode("$path_clear"))."&action=del&path_=".urlencode(urlencode("$path_clear/$file"))."';}";
                echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';
              }
             else echo '<span class="grey">системный файл</span>';
             echo '</td>
         </tr>'."\n";
     }
     $i++;
   }
}
  echo '</table></div>';
  navigation($page, $per_page, count($files) + count($dirs), $params);

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>