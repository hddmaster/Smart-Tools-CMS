<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['import']))
 {
  if ($user->check_user_rules('add'))
   {
     if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
     $parent_id = $_POST['parent_id'];
     $status = $_POST['status'];
     $filename_to_elementname = $_POST['filename_to_elementname'];
     $capitalize_first_letter = $_POST['capitalize_first_letter'];
 
     $path = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_images";
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
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name_new = $name.'.jpg';

  $element_name = '';
  if($filename_to_elementname == 1) $element_name = $name_clear;
  if($capitalize_first_letter == 1) $element_name = mb_strtoupper(mb_substr($element_name,0,1,'UTF-8'),'UTF-8').
                                                    mb_substr($element_name,1,mb_strlen($element_name,'UTF-8')-1,'UTF-8');

  //уникальная запись! Добавляем в каталог...
  $query = "insert into shop_cat_elements (parent_id, element_name, img_path1, status)
                                   values ($parent_id, '$element_name', '/userfiles/shop_cat_images/$user_file_name_new', $status)";

  $result = mysql_query($query) or(die(mysql_error()));
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$user_file_name_new";
  if (copy($path.'/'.$files_enc[$k], $filename)) unlink($path.'/'.$files_enc[$k]);
  chmod($filename,0666);

  // перенумеровываем
  $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
  if (@mysql_num_rows($result) > 0)
   {
     $i = 1;
     while ($row = mysql_fetch_array($result))
      {
        $id = $row['element_id'];
        mysql_query("update shop_cat_elements set order_id=$i where element_id = $id");
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

if (isset($_POST['element_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_name = mysql_real_escape_string(trim($_POST['element_name']));
  $element_url = mysql_real_escape_string(trim($_POST['element_url']));
  $store_name = mysql_real_escape_string(trim($_POST['store_name'])); 
  $c_store_name = mysql_real_escape_string(trim($_POST['c_store_name'])); 
  $ym_store_name = mysql_real_escape_string(trim($_POST['ym_store_name'])); 
  $producer_store_name = mysql_real_escape_string(trim($_POST['producer_store_name']));
  $parent_id = $_POST['parent_id'];
  $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2);
  $date_begin = substr($_POST['date_begin'],6,4).substr($_POST['date_begin'],3,2).substr($_POST['date_begin'],0,2);
 
  if ($c_store_name !== '')
   {
     $result = mysql_query("select * from shop_cat_elements where c_store_name = '".stripslashes($c_store_name)."'");
     if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}
   }

  $folder = 'shop_cat_images';
  $files = array();
  if (isset($_FILES['picture']))
   {     
     foreach($_FILES['picture']['tmp_name'] as $fn => $tmp_name)
       if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) $files[$fn]['tmp_name'] = $tmp_name;
     
     foreach($_FILES['picture']['type'] as $fn => $type)
      {
        if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) {
        $t = '';
        switch (basename($type))
         {
           case 'jpeg':
           case 'pjpeg': $t = 'jpeg'; break;
           case 'png':
           case 'x-png': $t = 'png'; break;
           case 'gif':  $t = 'gif'; break;
           case 'bmp':
           case 'wbmp':  $t = 'bmp'; break;
           default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
         }
        $files[$fn]['type'] = $t;
	}
      }
     
     foreach($_FILES['picture']['name'] as $fn => $name)
      {
        if(is_uploaded_file($_FILES['picture']['tmp_name'][$fn])) {
        $name = mb_strtolower($name,'UTF-8');
        $file = pathinfo($name);
        $ext = $file['extension'];
        $name_clear = str_replace('.'.$ext, '', $name);
	$name = $name_clear;
	$ext = $files[$fn]['type'];
        $i = 1;
        while (file_exists($_SERVER['DOCUMENT_ROOT'].'/userfiles/'.$folder.'/'.$name.'.'.$ext))
         {
           $name = $name_clear." ($i)";
           $i++;
         }
        $files[$fn]['name'] = '/userfiles/'.$folder.'/'.$name.'.'.$ext;
	}
      }
   }

  //уникальная запись! Добавляем в каталог...
  $query = "insert into shop_cat_elements
            (parent_id, date, date_begin, store_name, c_store_name, ym_store_name, producer_store_name, element_name, element_url)
	    values ($parent_id, $date, $date_begin, '$store_name', '$c_store_name',  '$ym_store_name', '$producer_store_name', '$element_name', '$element_url')";
  $result = mysql_query($query);
  $element_id = mysql_insert_id();
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  if (count($files) > 0)
   {
     foreach($files as $fn => $value)
      {
        $filename = $value['name'];
        copy($value['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$value['name']);
        chmod($value['name'],0666);
	mysql_query("update shop_cat_elements set img_path".($fn+1)." = '".$value['name']."' where element_id = $element_id") or die(mysql_error());
      }
   }
   
   // перенумеровываем
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id and type = 0 order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['element_id'];
         mysql_query("update shop_cat_elements set order_id=$i where element_id = $id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  Header("Location: ".$_SERVER['PHP_SELF']); exit();

  } else $user->no_rules('add');
 }


if ((isset($_POST['action']) && isset($_POST['id'])) ||
    (isset($_GET['action']) && isset($_GET['id'])) ||
    isset($_POST['element_names']))
 {
  if ($user->check_user_rules('edit'))
   {
     foreach ($_POST['element_names'] as $element_id => $element_name)
       mysql_query("update shop_cat_elements set element_name = '".trim($element_name)."', reserve = 0 where element_id = $element_id");
     foreach ($_POST['reserves'] as $element_id => $reserve)
       mysql_query("update shop_cat_elements set reserve = 1 where element_id = $element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   } else $user->no_rules('edit');

   if (isset($_GET['action'])) $action = $_GET['action'];
   if (isset($_POST['action'])) $action = $_POST['action'];
   $elements = array();
   if (isset($_GET['id']))  $elements[] = (int)$_GET['id'];
   if (isset($_POST['id'])) $elements = $_POST['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

      foreach($elements as $element_id)
      {
      
      $result = mysql_query("select * from shop_cat_elements where element_id = $element_id");
      if (mysql_num_rows($result) > 0)
       {
         $row = mysql_fetch_array($result);

         if (true)//$row['amount'] == 0)
          {
            if($row['img_path1'])
             {
               $filename = $row['img_path1'];
               if(!use_file($filename,'shop_cat_elements','img_path1') || !use_file($filename,'shop_cat_elements','img_path2') || !use_file($filename,'shop_cat_elements','img_path3'))
               unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            if($row['img_path2'])
             {
               $filename = $row['img_path2'];
               if (!use_file($filename,'shop_cat_elements','img_path1') || !use_file($filename,'shop_cat_elements','img_path2') || !use_file($filename,'shop_cat_elements','img_path3'))
               unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            if($row['img_path3'])
             {
               $filename = $row['img_path3'];
               if (!use_file($filename,'shop_cat_elements','img_path1') || !use_file($filename,'shop_cat_elements','img_path2') || !use_file($filename,'shop_cat_elements','img_path3'))
               unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            //удаляем из shop_cat_element_grids
            $result = mysql_query("delete from shop_cat_element_grids where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_element_sites
            $result = mysql_query("delete from shop_cat_element_sites where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_sizes_availability
            $result = mysql_query("delete from shop_cat_sizes_availability where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_group_sizes_elements_availability
            $result = mysql_query("delete from shop_cat_group_sizes_elements_availability where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_element_cards
            $result = mysql_query("delete from shop_cat_element_cards where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_option_values
            $result = mysql_query("delete from shop_cat_option_values where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_element_elements
            $result = mysql_query("delete from shop_cat_element_elements where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_elements
            $result = mysql_query("delete from shop_cat_elements where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_ratings
            $result = mysql_query("delete from shop_cat_ratings where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //удаляем из shop_cat_related_elements
            $result = mysql_query("delete from shop_cat_related_elements where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
          }
         else {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
       }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
      }
      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         foreach($elements as $element_id)
           mysql_query("update shop_cat_elements set status=1 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         foreach($elements as $element_id)
           mysql_query("update shop_cat_elements set status=0 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   unset($_POST);
 }

//-----------------------------------------------------------------------------
// AJAX

function text2url($str) {
	$objResponse = new xajaxResponse();

    $rus = array(   '',
                    'а','б','в','г','д','е','ё','ж','з','и','й','к',
                    'л','м','н','о','п','р','с','т','у','ф','х','ц',
                    'ч','ш','щ','ь','ы','ъ','э','ю','я',
                    ' ');
    $eng = array(   '',
                    'a','b','v','g','d','e','e','zh', 'z','i','y','k',
                    'l','m','n','o','p','r','s','t','u','f','h','c',
                    'ch','sh','shch','', 'y', '','e', 'yu','ya',			      
                    '-');
    $stop_chars = array('/', '\'', '"', '`', '(', ')', '[', ']', '{', '}',
                        '|', '~', '!', '?', '&', '+', '^', '%', '$',
                        '#', ':', ';', '<', '>', '.', ',', '\\', '=', '*',
                        '№');

    $str = trim($str);
    
    //двойные пробелы и мусор
    $str = preg_replace('/[\s]{2,}/', '', $str);
    
    //фильтрация символов
    $input = array();
    for($i = 0; $i < mb_strlen($str, 'UTF-8'); $i++) {
        $char = mb_strtolower(mb_substr($str, $i, 1, 'UTF-8'), 'UTF-8');
        if(!in_array($char, $stop_chars))
            $input[] = $char;
    }

    //перевод
    $out = '';
    foreach($input as $char) {
        $pos = array_search($char, $rus);
        $out .= (($pos) ? $eng[$pos] : $char);
    }

	$objResponse->assign('element_url', 'value', $out);
	return $objResponse;  
}

$xajax->registerFunction("text2url");
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

// проверка приходной и расходной накладных в избежании коллизий.
//$result1 = mysql_query("select * from shop_incoming_tmp");
//$result2 = mysql_query("select * from shop_outgoing_tmp");
//if (mysql_num_rows($result1) == 0 && mysql_num_rows($result2) == 0)
// {

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

function path_to_object($e_id, &$path, &$shop_tree)
 {
   foreach($shop_tree as $p_id => $groups)
    {
      foreach($groups as $element_id => $element_name)
       {
	 if ($element_id == $e_id)
          {
  	    $path[] = $element_name;
	    path_to_object($p_id, $path, $shop_tree);	
	  }
       }
    }
 }

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }
 
 echo '<table cellspacing="0" cellpadding="0" width="100%"><tr valign="top"><td style="padding-right: 10px;">';

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить товар</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" maxlength="255" onkeyup="xajax_text2url(this.form.element_name.value)"></td>
      <td><button type="button" onclick="xajax_text2url(this.form.element_name.value)">► URL</button></td>
    </tr>
    <tr>
      <td>URL <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_url" id="element_url" maxlength="255"/></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул</td>
      <td><input style="width:280px" type="text" name="store_name" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул 1С<br/><span class="grey">Уникальный идентификатор</span></td>
      <td><input style="width:280px" type="text" name="c_store_name" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул Яндекс.Маркет</td>
      <td><input style="width:280px" type="text" name="ym_store_name" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул производителя</td>
      <td><input style="width:280px" type="text" name="producer_store_name" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата добавления<sup class="red">*</sup></td>
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
echo'</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата поступления в продажу <sup class="red">*</sup></td>
      <td>';
?>
    <script>
      LSCalendars["date_begin"]=new LSCalendar();
      LSCalendars["date_begin"].SetFormat("dd.mm.yyyy");
      LSCalendars["date_begin"].SetDate("<?=date("d.m.Y");?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date_begin', event); return false;" style="width: 65px;" value="<?=date("d.m.Y");?>" name="date_begin"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date_begin', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="date_beginPtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Фотографии</td>
      <td>
       <table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture[]"/></td></tr>
       <tr><td><input style="width:280px" type="file" name="picture[]"/></td></tr>
       <tr><td><input style="width:280px" type="file" name="picture[]"/></td></tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение товара <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0, '', 0, $shop_tree).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td>&nbsp;</td>
    </tr>
  </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

  global $options; $options = '';

  echo '</td><td width="50%" style="padding-left: 10px;">';

     $path = $_SERVER['DOCUMENT_ROOT']."/userfiles/spool_images";
     $files = 0;

     if ($handle = @opendir($path))
      {
        while (false !== ($file = readdir($handle)))
          if ($file != "." && $file != ".." && !is_dir($file) && !is_link($file)) $files++;
        closedir($handle);
      }

 echo '<div class="dhtmlgoodies_question" align="right">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/database-import.png" alt=""></td>
		   <td><h2 class="nomargins">Загрузить фото из папки на сервере'; if ($files > 0) echo ' <span class="green">(найдено фалов: '.$files.')</span>'; echo '</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <input type="hidden" name="import" value="true">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Расположение товаров <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0, '', 0, $shop_tree).'
          </select>'; global $options; $options = ''; echo '
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
  </form><fieldset><legend>Внимание!</legend>Фотографии импортируются из папки /userfiles/spool_images/</fieldset><br /></div></div>';
  
  echo '</td></tr></table>';


$parent_id = -1; if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '') $parent_id = (int)$_GET['parent_id'];
$producer_id = -1; if (isset($_GET['producer_id']) && trim($_GET['producer_id']) !== '') $producer_id = (int)$_GET['producer_id'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0"  width="100%">
   <tr>

   <td width="50%" style="padding-right: 10px;">
   <table cellpadding="4" cellspacing="0" border="0">
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td nowrap>Фильтр по группе</td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">---Весь каталог---</option>
            <option value="0"'; if (isset($_GET['parent_id']) && $parent_id == 0) echo ' selected'; echo'>---Корень каталога---</option>
            '.show_select(0, '', $parent_id, $shop_tree).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td></td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td nowrap>Фильтр по производителю</td>
      <td><select name="producer_id" style="width:280px;">
            <option value="">---Все производители---</option>
            <option value="0"'; if (isset($_GET['producer_id']) && $producer_id == 0) echo ' selected'; echo'>---НЕТ---</option>
            ';
	   
	    $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
	    if (mysql_num_rows($res) > 0) {
	     while($r = mysql_fetch_object($res))
	         echo '<option value="'.$r->producer_id.'"'.((isset($_GET['producer_id']) && $_GET['producer_id'] == $r->producer_id) ? ' selected' : '').'>'.htmlspecialchars($r->producer_name).'</option>';
	    }
	    
	    echo '
          </select>
      </td>
      <td></td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td nowrap>Фильтр по статусу</td>
      <td>
       <table>
        <tr>
	  <td><input type="checkbox" name="status[]" value="1"'.((!isset($_GET['status']) || (isset($_GET['status']) && in_array(1, $_GET['status']))) ? 'checked' : '').'></td>
	  <td><img src="/admin/images/icons/light-bulb.png" border="0"></td>
	  <td style="padding-left: 15px;"><input type="checkbox" name="status[]" value="0"'.((!isset($_GET['status']) || (isset($_GET['status']) && in_array(0, $_GET['status']))) ? 'checked' : '').'></td>
	  <td><img src="/admin/images/icons/light-bulb-off.png" border="0"></td>
	</tr>
       </table>
      </td>
      <td></td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/magnifier.png" alt=""></td>
      <td>Поиск по фразе</td>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table>
   </td>

   <td width="50%" style="padding-left: 10px;">
  
  </td></tr></table></form>';

	// постраничный вывод
	$page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
	$per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
	$start = abs($page*$per_page);

	// сортировка
	$sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'element_id');
	$order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 
	
	$add = '';
	$params = array();

	if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') {
		$params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
		$query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';
	
		$add .= " 	and (shop_cat_elements.element_id like '$query_str' or
					shop_cat_elements.element_name like '$query_str' or
					shop_cat_elements.store_name like '$query_str' or
					shop_cat_elements.c_store_name like '$query_str' or
					shop_cat_elements.element_url like '$query_str' or
					shop_cat_elements.producer_store_name like '$query_str' or
					shop_cat_elements.description like '$query_str' or
					shop_cat_elements.description_full like '$query_str' or
					shop_cat_producers.producer_name like '$query_str')";
	}
 
	if (isset($_GET['parent_id']) && trim($_GET['parent_id']) !== '') {
		$add .= " and shop_cat_elements.parent_id = ".$_GET['parent_id'];
		$params['parent_id'] = $_GET['parent_id'];
	}

	if (isset($_GET['producer_id']) && trim($_GET['producer_id']) !== '') {
		$add .= " and shop_cat_elements.producer_id = ".$_GET['producer_id'];
		$params['producer_id'] = $_GET['producer_id'];
	}

	if (isset($_GET['status'])) {
		$add .= " and shop_cat_elements.status in (".implode(',', $_GET['status']).")";
		if(in_array(1, $_GET['status'])) $params['status'][] = 1;
		if(in_array(0, $_GET['status'])) $params['status'][] = 0;
	}

    $add_params = '';
    if(is_array($params)) {
		foreach($params as $key => $value) {
			if(is_array($value)) {
				foreach($value as $v)
					$add_params .= '&'.$key.'[]='.rawurlencode($v);
			} else
				$add_params .= '&'.$key.'='.rawurlencode($value);
		}
    }

	$query = "	select
				shop_cat_elements.*,
				date_format(shop_cat_elements.date, '%d.%m.%Y') as date2,
				shop_cat_producers.producer_name
				from shop_cat_elements left join shop_cat_producers on shop_cat_elements.producer_id = shop_cat_producers.producer_id
				where shop_cat_elements.type = 0 $add";
	$result = mysql_query($query); $total_rows = mysql_num_rows($result);          
	$result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page") or die(mysql_error());

	if (mysql_num_rows($result) > 0) {
		$shop_currency = 'руб.';
		$shop_currency = $user->get_cms_option('shop_currency');
		echo '<form id="form" method="post">';
		echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
		navigation($page, $per_page, $total_rows, $params);
		echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
		echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
		echo '<tr align="center" class="header">
				<td align="left" nowrap width="70"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"> №&nbsp;&nbsp;
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td nowrap>Группа</td>
				<td width="100%" nowrap>Название&nbsp;&nbsp;
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td nowrap>Арт.<br />
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=store_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'store_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=store_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'store_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td nowrap>Цена 1,<br />'.$shop_currency.'&nbsp;&nbsp;
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=price1&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'price1' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=price1&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price1' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td nowrap>Цена 2,<br />'.$shop_currency.'&nbsp;&nbsp;
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=price2&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'price2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=price2&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'price2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td nowrap>На заказ</td>
				<td>Карточки описания</td>
				<td nowrap>Производитель<br />
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'producer_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
				<td width="35">&nbsp;</td>
				<td width="120">&nbsp;</td>
			</tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">';
   echo '  <td align="left" class="small" nowrap><input class="cbx" type="checkbox" name="id[]" value="'.$row['element_id'].'"> '.$row['element_id'].'</td>
	   <td class="small">';
           if ($row['parent_id'] == 0) echo '&nbsp;';
           else
            {
	      $str = array();
              path_to_object($row['parent_id'], $str, $shop_tree);
	      $str = array_reverse($str);
              $i = 1;
              foreach ($str as $value)
               {
                 echo $value;
                 if ($i < count($str)) echo ' -&gt; ';
                 $i++;
               }
            }
           echo '</td>
           <td><input type="text" style="width: 100%" name="element_names['.$row['element_id'].']" value="'.(($row['element_name']) ? htmlspecialchars($row['element_name']) : '').'"></td>
           <td>'; if($row['store_name']) echo htmlspecialchars($row['store_name']); else echo '&nbsp;'; echo '</td>
           <td align="center">'.(($row['price1']) ? $row['price1'] : '&nbsp;').'</td>
           <td align="center">'.(($row['price2']) ? $row['price2'] : '&nbsp;').'</td>
           <td align="center"><input type="checkbox" name="reserves['.$row['element_id'].']" value=""'.(($row['reserve']) ? ' checked' : '').'></td>
           <td>';
	   
	    $res = mysql_query("select
	                        shop_cat_cards.card_name,
	                        shop_cat_cards.card_id
	                        from shop_cat_cards, shop_cat_element_cards
	                        where shop_cat_element_cards.element_id = ".$row['element_id']." and
	                        shop_cat_cards.card_id = shop_cat_element_cards.card_id");
	    if (mysql_num_rows($res) > 0) {
		while($r = mysql_fetch_array($res)) {
		    $res_check = mysql_query("select * from shop_cat_option_values where card_id = ".$r['card_id']." and element_id = ".$row['element_id']);
		    echo '<div class="small'.(mysql_num_rows($res_check) == 0 ? ' grey' : ' strong').'">'.htmlspecialchars($r['card_name']).'</div>';
		}
		
	    } else echo '&nbsp;';
	   
	   echo '</td>
           <td align="center">'.(($row['producer_name']) ? htmlspecialchars($row['producer_name']) : '&nbsp;').'</td>
           <td align="center">'; if ($row['img_path1']) echo '<a href="'.$row['img_path1'].'" class="zoom" rel="group" title="'.(($row['element_name']) ? htmlspecialchars($row['element_name']) : '').'"><img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" alt="'.$row['img_path1'].'" border="0"></a>'; else echo '&nbsp;'; echo '</td>';
/*
           <td align="center">';
           $rating = 0; 
           $res = mysql_query("select * from shop_cat_ratings where element_id = ".$row['element_id']);
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
           $res = mysql_query("select * from shop_cat_comments where element_id = ".$row['element_id']);
           if (mysql_num_rows($res) > 0) $comments = mysql_num_rows($res);
           if ($comments) echo $comments; else echo '&nbsp;'; 
           echo '</td>
									 */
           echo '<td nowrap align="center">
           <a href="/admin/editors/show_shop_cat.php?id='.$row['element_id'].'&mode=full" onclick="sw(this.href); return false;"><img align="absmiddle" src="/admin/images/icons/printer.png" border="0" alt="Печатная форма"></a>
           &nbsp;<a href="/admin/editors/edit_shop_cat_descr.php?id='.$row['element_id'].'&mode=full" onclick="sw(this.href); return false;"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="/admin/editors/edit_shop_cat.php?id='.$row['element_id'].'" onclick="sw(this.href); return false;"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="#" onclick="if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'\';} return false;"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table>';
  echo '<input type="hidden" name="action" id="action" value="">
        <table cellspacing="0" cellpadding="4">
         <tr>
           <td style="padding-left: 6px;"><img src="/admin/images/tree/2.gif" alt=""></td>
           <td class="small" nowrap>с отмеченными:</td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'activate\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb.png" alt="Включить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'reserve\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb-off.png" alt="Выключить" border="0"></a></td>
           <td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'del\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>
         </tr>
        </table>';  
 echo '</div>';
 echo '<table cellspacing="0" cellpadding="0"><tr><td width="100%">';
 navigation($page, $per_page, $total_rows, $params);
 echo '</td><td><p align="right"><button type="submit">Сохранить</button></p></td></tr></table>';
 echo '</form>';
}
else echo '<p align="center">Не найдено</p>';

// } else echo 'Добавить или изменить товары в каталоге нельзя до проведения приходной или расходной накладной.';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>