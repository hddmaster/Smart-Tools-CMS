<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['element_name'])=='' ||
      trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_name = trim($_POST['element_name']);
  $element_url = trim($_POST['element_url']);
  $element_title = trim($_POST['element_title']);
  $element_meta_keywords = trim($_POST['element_meta_keywords']);
  $element_meta_description = trim($_POST['element_meta_description']);
  $parent_id = $_POST['parent_id'];
  $unit_id = $_POST['unit_id'];

if (isset($_FILES['picture1']['name']) &&
   is_uploaded_file($_FILES['picture1']['tmp_name']))
{
//проверка формата первой картинки
  $user_file_name1 = mb_strtolower($_FILES['picture1']['name'],'UTF-8');
  $type1 = basename($_FILES['picture1']['type']);

  switch ($type1)
   {
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
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name1 =  $name.'.'.$ext;
}

if (isset($_FILES['picture2']['name']) &&
   is_uploaded_file($_FILES['picture2']['tmp_name']))
{
//проверка формата дополнительной картинки
  $user_file_name2 = mb_strtolower($_FILES['picture2']['name'],'UTF-8');
  $type2 = basename($_FILES['picture2']['type']);

  switch ($type2)
   {
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
$file = pathinfo($user_file_name2);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name2);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name2 = $name.'.'.$ext;
}

if (isset($_FILES['picture3']['name']) &&
   is_uploaded_file($_FILES['picture3']['tmp_name']))
{
//проверка формата дополнительной картинки
  $user_file_name3 = mb_strtolower($_FILES['picture3']['name'],'UTF-8');
  $type3 = basename($_FILES['picture3']['type']);

  switch ($type3)
   {
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
$file = pathinfo($user_file_name3);
$ext = $file['extension'];
$name_clear = str_replace(".$ext",'',$user_file_name3);
$name = $name_clear;
$i = 1;
 while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$name.$ext"))
  {
   $name = $name_clear." ($i)";
   $i ++;
  }
$user_file_name3 = $name.'.'.$ext;
}

  $db_img_path1 = ''; if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name'])) $db_img_path1 = "/userfiles/shop_cat_images/$user_file_name1";
  $db_img_path2 = ''; if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name'])) $db_img_path2 = "/userfiles/shop_cat_images/$user_file_name2";
  $db_img_path3 = ''; if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name'])) $db_img_path3 = "/userfiles/shop_cat_images/$user_file_name3";

  //уникальная запись! Добавляем в каталог...
  $query = "insert into shop_cat_elements
            (parent_id, type, element_name, element_url, element_title, element_meta_keywords, element_meta_description, img_path1, img_path2, img_path3, unit_id)
            values
            ($parent_id, 1, '$element_name', '$element_url', '$element_title', '$element_meta_keywords', '$element_meta_description', '$db_img_path1', '$db_img_path2', '$db_img_path3', $unit_id)";
  $result = mysql_query($query);
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$user_file_name1";
     copy($_FILES['picture1']['tmp_name'], $filename);
     chmod($filename,0666);
   }
  if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$user_file_name2";
     copy($_FILES['picture2']['tmp_name'], $filename);
     chmod($filename,0666);
   }
  if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
     $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_images/$user_file_name3";
     copy($_FILES['picture3']['tmp_name'], $filename);
     chmod($filename,0666);
   }

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

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page&add_parent_id=".$parent_id);
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

      $result = @mysql_query("select * from shop_cat_elements where parent_id=$element_id");
      if (@mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
      else
       {
           $result = mysql_query("select * from shop_cat_elements where element_id=$element_id");
           $row = mysql_fetch_array($result);
           if($row['img_path1'])
             {
               $filename = $row['img_path1'];
               if(!use_file($filename,'shop_cat_elements','img_path1') || !use_file($filename,'shop_cat_elements','img_path2') || !use_file($filename,'shop_cat_elements','img_path3'))
               @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }

            //удаляем из shop_cat_elements
            $result = mysql_query("delete from shop_cat_elements where element_id=$element_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
       }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
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
         mysql_query("update shop_cat_elements set status=0 where element_id=$element_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
if (isset($_POST['action']))
 {
 if ($user->check_user_rules('add'))
  {
  $parent_id = $_POST['parent_id'];
  $recursion = $_POST['recursion'];
  $price = 'price'.$_POST['price'];
  $price_old = 'price'.$_POST['price'].'_old';
  
  if ($_POST['action'] == 'add_discount')
  {
  $discount = $_POST['discount'];
  $round = $_POST['round'];
  function update_elements($parent_id, $price, $price_old, $discount, $round, $recursion)
   {
     $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id");
     if(mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
          $el_id = $row['element_id'];
          if ($row['type'] == 0)
           {
             if ($discount == 100) $new_price = 0;
             else
              {
                if ($row[$price_old] == 0) $new_price = round($row[$price]*(100-$discount)/100);
                else $new_price = round($row[$price_old]*(100-$discount)/100);
              }
             switch ($round)
              {
                case '10000': $new_price = ceil($new_price/10000)*10000; break;
                case '1000': $new_price = ceil($new_price/1000)*1000; break;
                case '100': $new_price = ceil($new_price/100)*100; break;
                case '10': $new_price = ceil($new_price/10)*10; break;
                case '1': $new_price = ceil($new_price); break;
                case '0': $new_price = $new_price; break;
              }
            if ($row[$price_old] == 0) mysql_query("update shop_cat_elements set ".$price_old." = ".$row[$price].", $price = $new_price where element_id = ".$row['element_id']);
            else mysql_query("update shop_cat_elements set $price = $new_price where element_id = ".$row['element_id']);
           }
          if ($row['type'] == 1 && $recursion == 1) update_elements($el_id, $price, $price_old, $discount, $round, $recursion);
        }
     }
   }
  update_elements($parent_id, $price, $price_old, $discount, $round, $recursion);
  }

 if ($_POST['action'] == 'delete_discount')
 {
 function update_elements($parent_id, $price, $price_old, $recursion)
   {
     $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id");
     if(mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
          $el_id = $row['element_id'];
          if ($row['type'] == 0 && $row[$price_old] !== '0') mysql_query("update shop_cat_elements set ".$price_old." = 0, $price = ".$row[$price_old]." where element_id = ".$row['element_id']);
          if ($row['type'] == 1 && $recursion == 1) update_elements($el_id, $price, $price_old, $recursion);
        }
     }
   }
  update_elements($parent_id, $price, $price_old, $recursion);
  }
  
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
  
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
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары');
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы', 1);
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
if ($user->check_user_rules('view','/admin/shop_cat_structure.php')) $tabs3->add_tab('/admin/shop_cat_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_group_grids.php')) $tabs3->add_tab('/admin/shop_cat_group_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_units_of_measure.php')) $tabs3->add_tab('/admin/shop_units_of_measure.php', 'Единицы измерения');
if ($user->check_user_rules('view','/admin/shop_cat_group_publications.php')) $tabs3->add_tab('/admin/shop_cat_group_publications.php', 'Публикации');
$tabs3->show_tabs();

if ($user->check_user_rules('view'))
 {
// проверка приходной и расходной накладных в избежании коллизий.
//$result1 = mysql_query("select * from shop_incoming_tmp");
//$result2 = mysql_query("select * from shop_outgoing_tmp");
//if (mysql_num_rows($result1) == 0 && mysql_num_rows($result2) == 0)
// {

function get_shop_tree($parent_id = 0, $selected_element_id = '', &$shop_tree)
 {
   $result = mysql_query("select * from shop_cat_elements where type = 1 order by order_id asc");
   if(mysql_num_rows($result) > 0)
     while ($row = mysql_fetch_object($result))
       $shop_tree[$row->parent_id][$row->element_id] = $row->element_name;
 }
$shop_tree = array(); get_shop_tree(0, 0, $shop_tree);

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

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить товарную группу</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

// ◄ ►

echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название группы <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_name" maxlength="255" onkeyup="xajax_text2url(this.form.element_name.value)"></td>
      <td><button type="button" onclick="xajax_text2url(this.form.element_name.value)">► URL</button></td>
    </tr>
    <tr>
      <td>Заголовок страницы сайта<br /><span class="grey">TITLE</span></td>
      <td><input style="width:280px" type="text" name="element_title" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Ключевые слова<br /><span class="grey">meta keyrords</span></td>
      <td><input style="width:280px" type="text" name="element_meta_keywords" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>URL <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_url" id="element_url" maxlength="255"/></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Описание<br /><span class="grey">meta description</span></td>
      <td><input style="width:280px" type="text" name="element_meta_description" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Фотографии</td>
      <td>
       <table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture1"/></td></tr>
       <tr><td><input style="width:280px" type="file" name="picture2"/></td></tr>
       <tr><td><input style="width:280px" type="file" name="picture3"/></td></tr>
       </table>
       </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Единица измерения</td>
      <td><select name="unit_id" style="width:280px;">
            <option value="0">---НЕТ---</option>';
            
    $res = mysql_query("select * from shop_units_of_measure order by unit_name asc");
    if (mysql_num_rows($res) > 0)
     {
       while ($r = mysql_fetch_array($res))
        {
          echo '<option value="'.$r['unit_id'].'">'.htmlspecialchars($r['unit_name']).(($r['unit_descr']) ? ' &nbsp; ('.htmlspecialchars($r['unit_descr']).')' : '').'</option>';
        }
     }
          echo '</select>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение группы <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0, '', (isset($_GET['add_parent_id']) ? (int)$_GET['add_parent_id'] : 0), $shop_tree).'
          </select>
      </td>
      <td>&nbsp;</td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

global $options; $options = '';
echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Сделать скидку</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form action="" method="post">
   <input type="hidden" name="action" value="add_discount">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Группа <sup class="red">*</sup><br><span class="grey">Выберите группу...</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'', 0, $shop_tree).'
          </select>
      </td>
    </tr>
    <tr>
      <td>Цена</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="1"></td>
         <td>&nbsp;цена 1</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="2" checked></td>
         <td>&nbsp;цена 2</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="3" checked></td>
         <td>&nbsp;цена 3</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="4" checked></td>
         <td>&nbsp;цена 4</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="5" checked></td>
         <td>&nbsp;цена 5</td>
        </tr>
       </table>
      </td>
    </tr>
    <tr>
      <td>Скидка</td>
      <td><select name="discount">
            <option value="0">0%</option>
            <option value="5">5%</option>
            <option value="10">10%</option>
            <option value="15">15%</option>
            <option value="20">20%</option>
            <option value="25">25%</option>
            <option value="30">30%</option>
            <option value="35">35%</option>
            <option value="40">40%</option>
            <option value="45">45%</option>
            <option value="55">55%</option>
            <option value="60">60%</option>
            <option value="65">65%</option>
            <option value="70">70%</option>
            <option value="75">75%</option>
            <option value="80">80%</option>
            <option value="85">85%</option>
            <option value="90">90%</option>
            <option value="100">100%</option>
          </select>
      </td>
    </tr>
    <tr>
      <td>Изменять цены во всех подгруппах</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="recursion" style="width: 16px; height: 16px;" value="1" checked></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="recursion" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
    </tr>
    <tr>
      <td>Округление</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="10000"></td>
         <td>&nbsp;10000</td>
        </tr>
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="1000"></td>
         <td>&nbsp;1000</td>
        </tr>
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="100" checked></td>
         <td>&nbsp;100</td>
        </tr>
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="10"></td>
         <td>&nbsp;10</td>
        </tr>
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="1"></td>
         <td>&nbsp;до целого</td>
        </tr>
        <tr>
         <td><input type="radio" name="round" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;нет</td>
        </tr>
       </table>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

global $options; $options = '';
echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/cross.png" alt=""></td>
		   <td><h2 class="nomargins">Удалить скидки</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form action="" method="post">
   <input type="hidden" name="action" value="delete_discount">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Группа <sup class="red">*</sup><br><span class="grey">Выберите группу...</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'', 0, $shop_tree).'
          </select>
      </td>
    </tr>
    <tr>
      <td>Цена</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="1"></td>
         <td>&nbsp;цена 1</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="price" style="width: 16px; height: 16px;" value="2" checked></td>
         <td>&nbsp;цена 2</td>
        </tr>
       </table>
      </td>
    </tr>
    <tr>
      <td>Удалять скидки во всех подгруппах</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="recursion" style="width: 16px; height: 16px;" value="1" checked></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="recursion" style="width: 16px; height: 16px;" value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
    </tr>
   </table><br>
   <button class="red" type="SUBMIT">Удалить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

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
 
    if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') {
        $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
        $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';
        $add .= "   and (element_id like '$query_str' or
                    element_name like '$query_str' or
                    element_url like '$query_str' or
                    store_name like '$query_str' or
                    c_store_name like '$query_str' or
                    description like '$query_str' or
                    description_full like '$query_str')";
    }

 $query = "select
           *
           from shop_cat_elements
           where type = 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
		 <td nowrap>Группа</td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Заголовок страницы сайта&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_title&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_title&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>Товаров</td>
         <td width="35">&nbsp;</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
            <td align="center">'.$row['element_id'].'</td>
            <td class="small">';
           
            if ($row['parent_id'] == 0) echo '&nbsp;';
            else {
                $str = array();
                path_to_object($row['parent_id'], $str, $shop_tree);
                $str = array_reverse($str);
                $i = 1;
                foreach ($str as $value) {
                    echo $value;
                    if ($i < count($str)) echo ' -&gt; ';
                    $i++;
                }
            }
            echo '</td>
            <td>'.htmlspecialchars($row['element_name']).'</td>
            <td>'.(($row['element_title']) ? htmlspecialchars($row['element_title']) : '&nbsp;').'</td>
            <td align="center">';
           
           $res = mysql_query("select * from shop_cat_elements where parent_id = ".$row['element_id']." and type = 0");
           echo ((mysql_num_rows($res) > 0) ? mysql_num_rows($res) : '&nbsp;'); 
           
           echo '</td>
           <td align="center">'; if ($row['img_path1']) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=30&image='.rawurlencode($row['img_path1']).'" border="0">'; else echo '&nbsp;'; echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_cat_descr.php?id='.$row['element_id'].'&mode=full\');"><img align="absmiddle" src="/admin/images/icons/edit.png" border="0" alt="Редактировать текст"></a>
           &nbsp;<a href="javascript:sw(\'/admin/editors/edit_shop_cat_group.php?id='.$row['element_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['element_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
}
else echo '<p align="center">Не найдено</p>';

// } else echo 'Добавить или изменить товары в каталоге нельзя до проведения приходной или расходной накладной.';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>