<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

//-----------------------------------------------------------------------------
// AJAX

function add_iframe($id)
 {
   $objResponse = new xajaxResponse();
   $objResponse->script("newIframe = document.createElement('div');
                            newIframe.setAttribute('id','iframe_div_$id');
                            newIframe.innerHTML = '<iframe id=\"iframe_$id\" src=\"upload.php?iframe=$id\" style=\"background: #dddddd; height: 35px;\" frameborder=\"0\" scrolling=\"no\"></iframe>';
                            document.getElementById('add_fotos_iframes').appendChild(newIframe);
                            document.getElementById('add_fotos_value').value = $id;");
   return $objResponse;
 }

function delete_iframe($id)
 {
   $objResponse = new xajaxResponse();
   $objResponse->script("document.getElementById('add_fotos_iframes').removeChild(document.getElementById('iframe_div_$id'))");
   return $objResponse;

 }

function upload_images($value)
 {
   $objResponse = new xajaxResponse();
   $objResponse->script("document.frames[$value].submitform()");
   return $objResponse;
 }

function import_images($parent_id = 0, $status = true, $filename_to_elementname = true, $capitalize_first_letter = true)
 {
   $objResponse = new xajaxResponse();
   $path = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_images";
   $files = array();

   if ($handle = @opendir($path))
    {
      while (false !== ($file = readdir($handle)))
        if ($file != "." && $file != ".." && !is_dir($file) && !is_link($file)) $files[] = $file;
      closedir($handle);
    }
      
   if (count($files) > 0)
    {
      $k = 0;
      foreach ($files as $user_file_name)
       {
         $user_file_name = mb_strtolower($user_file_name, 'UTF-8');
         //Проверка на наличие файла, замена имени, пока такого файла не будет
         $file = pathinfo($user_file_name); 
         $ext = $file['extension'];
         $name_clear = str_replace(".$ext",'',$user_file_name);
         $name = $name_clear;
         $i = 1;
         while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$name.$ext"))
          {
            $name = $name_clear." ($i)";
            $i++;
          }
         $user_file_name_new = $name.'.jpg';

         $element_name = '';
         if($filename_to_elementname) $element_name = $name_clear;
         if($capitalize_first_letter) $element_name = mb_strtoupper(mb_substr($element_name,0,1,'UTF-8'),'UTF-8').
                                                      mb_substr($element_name,1,mb_strlen($element_name,'UTF-8')-1,'UTF-8');
         $query = "insert into gallery values (null, 
                                               $parent_id, 
		               		       0, 
					       0, 
					       ".date("YmdHis").", 
					       '$element_name', 
					       '', 
					       '', 
					       '/userfiles/gallery_images/$user_file_name_new',
					       '',
					       '',
					       $status, 
					       '',
					       '')";
         $result = mysql_query($query);

         $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/gallery_images/$user_file_name_new";
         if ($result)
          {
            if (copy($path.'/'.$files[$k], $filename)) unlink($path.'/'.$files[$k]);
  
            //перенумеровываем
            $result = mysql_query("select * from gallery where parent_id = $parent_id order by order_id asc");
            if (mysql_num_rows($result) > 0)
             {
               $i = 1;
               while ($row = mysql_fetch_array($result))
                {
                  $id = $row['element_id'];
                  mysql_query("update gallery set order_id=$i where element_id = $id");
                  $i++;
                }
             }
          }
         $k++;
        }
       $objResponse->alert('Фотографии импортированы!'); 
    }
   else $objResponse->alert('no files!');     
	  
   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();
   
   //$objResponse->script("header.location='/admin/gallery.php';");
   return $objResponse;
 }



$xajax->bOutputEntities = true;



$xajax->registerFunction("add_iframe");
$xajax->registerFunction("delete_iframe");
$xajax->registerFunction("upload_images");
$xajax->registerFunction("import_images");

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Галерея</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/gallery.php')) $tabs->add_tab('/admin/gallery.php', 'Публикации');
if ($user->check_user_rules('view','/admin/gallery_groups.php')) $tabs->add_tab('/admin/gallery_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/gallery_structure.php')) $tabs->add_tab('/admin/gallery_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/gallery_comments.php')) $tabs->add_tab('/admin/gallery_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/gallery_import.php')) $tabs->add_tab('/admin/gallery_import.php', 'Импорт <i class="red">beta</i>');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("SELECT * FROM gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_added == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

 function show_select_filter($parent_id = 0, $prefix = '', $parent_id_element = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM gallery where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_element == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";

          show_select_filter($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }


function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from gallery where parent_id = $parent_id order by order_id asc");
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
   $result = mysql_query("select * from gallery where parent_id = $parent_id order by order_id asc");
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

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

global $options; $options = '';

 echo '<form action="" method="post name="add_fotos_form" id="add_fotos_form">
   <input type="hidden" name="add_fotos_value" id="add_fotos_value" value="0">
   <input type="hidden" name="add_fotos" value="true">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Фотографии</td>
      <td><div id="add_fotos_iframes"></div></td>
    </tr>
    <tr>
      <td>Расположение публикаций <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>
      </td>
    </tr>
   <tr>
     <td>Активность</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="status" style="width: 16px; height: 16px;" checked value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="status" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
   <tr>
     <td>Название копировать из названия файла</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="filename_to_elementname" style="width: 16px; height: 16px;" checked value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="filename_to_elementname" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
    <tr>
     <td>Увеличивать первую букву в названии</td>
     <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="capitalize_first_letter" style="width: 16px; height: 16px;" checked value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="capitalize_first_letter" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
 </table><br>
   <button type="button" onclick="document.frames[0].submitform();
                                  lastDiv = \'iframe_div_\' + document.getElementById(\'add_fotos_value\').value; 
                                  document.getElementById(\'add_fotos_iframes\').removeChild(document.getElementById(lastDiv));
                                 ">Загрузить</button>
  </form><br />
  <script>xajax_add_iframe(1);</script>';

  echo '<fieldset><legend>Оганичения</legend>';
  $mp = round((1280*1024*4)/1000000);
  echo 'Оперативная память, Мб: <strong>'.intval(ini_get('memory_limit')).'</strong> (до '.$mp.' Megapixels, 32 bits)<br />';
  echo 'Максимальный размер файла, Мб: <strong>'.intval(ini_get('upload_max_filesize')).'</strong>';
  echo '</fieldset>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>