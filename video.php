<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_names'])) {
    if ($user->check_user_rules('edit')) {
        foreach ($_POST['element_names'] as $element_id => $element_name)
            mysql_query("update video set element_name = '".trim($element_name)."' where element_id = $element_id") or die(mysql_error());
        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit();
    } else $user->no_rules('edit');
}

if (isset($_POST['import'])) {
    if ($user->check_user_rules('add')) {
        if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        $parent_id = $_POST['parent_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        $filename_to_elementname = $_POST['filename_to_elementname'];
        $capitalize_first_letter = $_POST['capitalize_first_letter'];
 
        $path = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_videos";
        $files = array();
        $files_enc = array();

     if ($handle = @opendir($path))
      {
        while (false !== ($file = readdir($handle)))
         {
           if ($file != "." && $file != ".." && !is_dir($file) && !is_link($file))
            {
              $files[] = mb_convert_encoding($file,'UTF-8','WINDOWS-1251');
              $files_enc[] = $file; 
            }
         }
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
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name_new = $name.'.'.$ext;

  $element_name = '';
  if($filename_to_elementname == 1) $element_name = $name_clear;
  if($capitalize_first_letter == 1) $element_name = mb_strtoupper(mb_substr($element_name,0,1,'UTF-8'),'UTF-8').
                                                    mb_substr($element_name,1,mb_strlen($element_name,'UTF-8')-1,'UTF-8');
  //уникальная запись! Добавляем в каталог...
  $query = "insert into video values (null, 
                                      $parent_id, 
				      0, 
				      0, 
				      ".date("YmdHis").", 
				      '$element_name', 
				      '', 
				      '', 
				      '', 
				      '/userfiles/videos/$user_file_name_new',
				      $user_id,
				      $status, 
				      '',
				      '')";

  $result = mysql_query($query) or(die(mysql_error()));
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$user_file_name_new";
  if (copy($path.'/'.$files_enc[$k], $filename)) unlink($path.'/'.$files_enc[$k]);
  chmod($filename,0666);

  // перенумеровываем
  $result = mysql_query("select * from video where parent_id = $parent_id order by order_id asc");
  if (@mysql_num_rows($result) > 0)
   {
     $i = 1;
     while ($row = mysql_fetch_array($result))
      {
        $id = $row['element_id'];
        mysql_query("update video set order_id=$i where element_id = $id");
        $i++;
      }
   }
        $k++;
         }
	  } 
	  
 
   //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

    Header("Location: ".$_SERVER['PHP_SELF']."?parent_id=$parent_id");
     exit();

   } else $user->no_rules('add');
 }

if (isset($_POST['element_name'])) {
    if ($user->check_user_rules('add')) {
        if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

        $element_name = mysql_real_escape_string(trim($_POST['element_name']));
        $embed = mysql_real_escape_string(trim($_POST['embed']));
        $parent_id = $_POST['parent_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];

        if (isset($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $user_file_name1 = mb_strtolower($_FILES['image']['name'],'UTF-8');
            $type = basename($_FILES['image']['type']);

            switch ($type) {
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
            $file = pathinfo($user_file_name1);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext",'',$user_file_name1);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            } 
            $user_file_name1 = $name.'.'.$ext;
        }

        if (isset($_FILES['video']['name']) && is_uploaded_file($_FILES['video']['tmp_name'])) {
            $user_file_name2 = mb_strtolower($_FILES['video']['name'],'UTF-8');
            $type = basename($_FILES['video']['type']);

            switch ($type) {
                case 'jpeg': break;
                case 'pjpeg': break;
                case 'png': break;
                case 'x-png': break;
                case 'gif': break;
                case 'bmp': break;
                case 'wbmp': break;
                //default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
            }

            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($user_file_name2);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext",'',$user_file_name2);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            }
            $user_file_name2 = $name.'.'.$ext;
        }


        //уникальная запись! Добавляем в каталог...
        $query = "insert into video values (null, $parent_id, 0, 0, ".date("YmdHis").", '$element_name', '', ''";

        if (isset($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name']))
        $query .= ", '"."/userfiles/videos/$user_file_name1"."'";
        else $query .= ", ''";

        if (isset($_FILES['video']['name']) && is_uploaded_file($_FILES['video']['tmp_name']))
        $query .= ", '"."/userfiles/videos/$user_file_name2"."'";
        else $query .= ", ''";

        $query = $query.", '$embed', $user_id, $status, '', '', '')";

        $result = mysql_query($query);
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

        if (isset($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$user_file_name1";
            copy($_FILES['image']['tmp_name'], $filename);
            resize($filename, basename($_FILES['image']['type']));
            chmod($filename,0666);
        }

        if (isset($_FILES['video']['name']) && is_uploaded_file($_FILES['video']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/videos/$user_file_name2";
            copy($_FILES['video']['tmp_name'], $filename);
            chmod($filename,0666);
        }

        // перенумеровываем
        $result = mysql_query("select * from video where parent_id = $parent_id and type = 0 order by order_id asc");
        if (mysql_num_rows($result) > 0) {
            $i = 1;
            while ($row = mysql_fetch_array($result)) {
                $id = $row['element_id'];
                mysql_query("update video set order_id=$i where element_id = $id");
                $i++;
            }
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        Header("Location: ".$_SERVER['PHP_SELF']."?parent_id=$parent_id");
        exit();

    } else $user->no_rules('add');
}


if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $element_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
            $result = mysql_query("select * from video where element_id = $element_id");
            if (mysql_num_rows($result) > 0)
            {
            $row = mysql_fetch_array($result);

            if($row['img_path1'])
             {
               $filename = $row['img_path1'];
               if(!use_file($filename,'video','img_path1') || !use_file($filename,'video','img_path2') || !use_file($filename,'video','img_path3'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }
             
            if($row['img_path2'])
             {
               $filename = $row['img_path2'];
               if (!use_file($filename,'video','img_path1') || !use_file($filename,'video','img_path2') || !use_file($filename,'video','img_path3'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            if($row['img_path3'])
             {
               $filename = $row['img_path3'];
               if (!use_file($filename,'video','img_path1') || !use_file($filename,'video','img_path2') || !use_file($filename,'video','img_path3'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            //удаляем из video
            $result = mysql_query("delete from video where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
            }
      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update video set status=1 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update video set status=0 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Видео</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/video.php')) $tabs->add_tab('/admin/video.php', 'Публикации');
if ($user->check_user_rules('view','/admin/video_groups.php')) $tabs->add_tab('/admin/video_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/video_structure.php')) $tabs->add_tab('/admin/video_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/video_comments.php')) $tabs->add_tab('/admin/video_comments.php', 'Комментарии');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("SELECT * FROM video where parent_id = $parent_id and type = 1 order by order_id asc");
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
    $result = mysql_query("SELECT * FROM video where parent_id = $parent_id and type = 1 order by order_id asc");
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

function path_to_object($element_id, &$path)
 {
   $result = mysql_query("select * from video where element_id = $element_id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $path[] = $row['element_name'];
      path_to_object($row['parent_id'], $path);
    }
 }

 $parent_id_added = 0; if (isset($_GET['parent_id'])) $parent_id_added = $_GET['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить новую публикацию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" maxlength="255"></td></tr>
    <tr>
      <td>Код вставки</td>
      <td><input style="width:280px" type="text" name="embed" maxlength="255"></td></tr>
    <tr>
      <td>Видео<br /><span class="grey">FLV, MPEG4</span></td>
      <td><input style="width:280px" type="file" name="video"/></td>
    </tr>
    <tr>
      <td>Фотография<br /><span class="grey">JPEG, PNG, GIF, BMP</span></td>
      <td><input style="width:280px" type="file" name="image"/></td>
    </tr>
    <tr>
      <td>Расположение <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'',$parent_id_added).'
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
            echo '<option value="'.$r->user_id.'" '.(($r->user_id == $user_id) ? 'selected' : '').'>'.htmlspecialchars($r->user_id).'</option>';
       }
      echo '</select>
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
  </table><br />
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

global $options; $options = '';

     $path = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_videos";
     $files = 0;

     if ($handle = @opendir($path))
      {
        while (false !== ($file = readdir($handle)))
          if ($file != "." && $file != ".." && !is_dir($file) && !is_link($file)) $files++;
        closedir($handle);
      }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Загрузить видео из папки на сервере'; if ($files > 0) echo ' <span class="green">(найдено фалов: '.$files.')</span>'; echo '</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <input type="hidden" name="import" value="true">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Расположение публикаций <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень галереи---</option>
            '.show_select(0,'',$parent_id_added).'
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
            echo '<option value="'.$r->user_id.'" '.(($r->user_id == $user_id) ? 'selected' : '').'>'.htmlspecialchars($r->username).'</option>';
       }
      echo '</select>
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
         <td><input type="radio" name="filename_to_elementname" style="width: 16px; height: 16px;" value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="filename_to_elementname" style="width: 16px; height: 16px;" checked value="0"></td>
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
         <td><input type="radio" name="capitalize_first_letter" style="width: 16px; height: 16px;" value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="capitalize_first_letter" style="width: 16px; height: 16px;" checked value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
     </td>
   </tr>
  </table><br>
   <button type="SUBMIT"'; if ($files == 0) echo ' disabled'; echo '>Импортироваить</button>
  </form><fieldset><legend>Внимание!</legend>Фотографии импортируются из папки /userfiles/spool_videos/</fieldset><br /></div></div>';
 
global $options; $options = '';
$parent_id = -1; if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '') $parent_id = $_GET['parent_id_filter'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td nowrap>
   <form action="" method="GET">

   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td nowrap>Фильтр по группе</td>
      <td><select name="parent_id_filter" style="width:280px;">
            <option value="">---Вся галерея---</option>
            <option value="0"'; if (isset($_GET['parent_id']) && ($parent_id === 0 || $parent_id == 0)) echo ' selected'; echo'>---Корень галереи---</option>
            '.show_select_filter(0,'',$parent_id).'
          </select>
      </td>
      <td><button type="SUBMIT">OK</button></td>
    </tr>
  </table>
   </td>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сортировка
 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'element_id';
    $order = 'desc';
  }

$add = '';
$params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = mb_strtolower(trim($_GET['query_str']), 'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']), 'UTF-8').'%';

   $add .= " and (video.element_id like '$query_str' or
           video.element_name like '$query_str' or
           video.description like '$query_str' or
           video.description_full like '$query_str')";
 }

if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '')
 {
   $add .= " and video.parent_id = ".$_GET['parent_id_filter'];
   $params['parent_id_filter'] = $_GET['parent_id_filter'];
 }

 $query = "select
           video.*,
	   date_format(video.date, '%d.%m.%Y (%H:%i:%s)') as date2,
	   auth_site.username
           from video left join auth_site on video.user_id = auth_site.user_id
           where video.type = 0 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<form action="" method="post">';
 echo '<p align="right"><button type="submit">Сохранить</button></p>';
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Группа</td>
         <td nowrap width="40%">Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="35">&nbsp;</td>
         <td>&nbsp;</td>
         <td>Пользователь сайта</td>
         <td>Рейтинг</td>
         <td>Комментарии</td>
         <td>&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['element_id'].'</td>
           <td align="center" nowrap>'.$row['date2'].'</td>
           <td>';
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
	      $str = '';
              path_to_object($row['parent_id'], $str);
	      $str = array_reverse($str);
              $i = 1;
              foreach ($str as $value)
               {
                 echo $value;
                 if ($i < count($str)) echo ' -&gt; ';
                 $i++;
               }
              global $path; $path = array();
            }
           echo '</td>
           <td><input type="text" style="width: 100%" name="element_names['.$row['element_id'].']" value="'.(($row['element_name']) ? htmlspecialchars($row['element_name']) : '').'"></td>
           <td align="center">'; if ($row['img_path']) echo '<a href="'.$row['img_path'].'" class="zoom" rel="group" title="'.(($row['element_name']) ? htmlspecialchars($row['element_name']) : '').'"><img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path']).'" alt="'.$row['img_path'].'" border="0"><a>'; else echo '&nbsp;'; echo '</td>
           <td align="center">';
	   
	   if ($row['video_path'])
	     echo '<a class="zoom iframe" href="/admin/editors/video_code.php?id='.$row['element_id'].'"><img src="/admin/images/icons/control.png" alt="посмотреть видео" border="0"></a>'; else echo '&nbsp;'; echo '</td>
           <td align="center">'; if ($row['username']) echo htmlspecialchars($row['username']); else echo '&nbsp;'; echo '</td>
           <td align="center">';
           $rating = 0; 
           $res = mysql_query("select * from video_ratings where element_id = ".$row['element_id']);
           if (mysql_num_rows($res) > 0)
            {
              $s = 0;
              while ($r = mysql_fetch_array($res))
                $s += $r['rate_value'];
              $rating = round($s/mysql_num_rows($res));  
            }
           if ($rating) echo $rating; else echo '&nbsp;'; 
           echo '</td>
           <td align="center">';
           $comments = 0; 
           $res = mysql_query("select * from video_comments where element_id = ".$row['element_id']);
           if (mysql_num_rows($res) > 0) $comments = mysql_num_rows($res);
           if ($comments) echo $comments; else echo '&nbsp;'; 
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_video_descr.php?id='.$row['element_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_video.php?id='.$row['element_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
 echo '</table></div>';
 echo '<p align="right"><button type="submit">Сохранить</button></p>';
 echo '</form>';
 navigation($page, $per_page, $total_rows, $params);
 }
else echo '<p align="center">Не найдено</p>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>