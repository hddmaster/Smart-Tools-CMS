<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

function get_shop_tree(&$shop_tree) {
    $result = mysql_query("select * from shop_cat_elements where type = 1 order by order_id asc");
    if(mysql_num_rows($result) > 0)
        while ($row = mysql_fetch_object($result))
            $shop_tree[$row->parent_id][$row->element_id] = $row->element_name;
}
global $shop_tree; $shop_tree = array(); get_shop_tree($shop_tree);

function show_select($parent_id = 0, $prefix = '', $selected_element_id = 0, &$shop_tree) {
    global $options;
    foreach($shop_tree[$parent_id] as $element_id => $element_name) {
        $options .= '   <option value="'.$element_id.'"'.($selected_element_id == $element_id ? ' selected' : '').'>'.
                        $prefix.htmlspecialchars($element_name).'</option>';
        show_select($element_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_element_id, $shop_tree);
    }
    return $options;
}

function get_grid_tree(&$grid_tree) {
    $result = mysql_query("select * from shop_cat_group_grids order by order_id asc");
    if(mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_object($result)) {
            $grid_tree[$row->parent_grid_id][$row->grid_id] = $row->grid_name;
        }
    }
}
global $grid_tree; $grid_tree = array(); get_grid_tree($grid_tree);

function path_to_grid($g_id, &$path, &$grid_tree) {
    foreach($grid_tree as $p_id => $groups) {
        foreach($groups as $grid_id => $grid_name) {
            if ($grid_id == $g_id) {
                $path[] = $grid_name;
                path_to_grid($p_id, $path, $grid_tree);	
            }
         }
    }
}



if (isset($_POST['element_name']) &&
   isset($_GET['id'])) {

    if ($user->check_user_rules('edit')) {
    
        $price1 = 0; if (isset($_POST['price1']) && $_POST['price1'] > 0) $price1 = (double)$_POST['price1'];
        $price2 = 0; if (isset($_POST['price2']) && $_POST['price2'] > 0) $price2 = (double)$_POST['price2'];
        $price3 = 0; if (isset($_POST['price3']) && $_POST['price3'] > 0) $price3 = (double)$_POST['price3'];
        $price4 = 0; if (isset($_POST['price4']) && $_POST['price4'] > 0) $price4 = (double)$_POST['price4'];
        $price5 = 0; if (isset($_POST['price5']) && $_POST['price5'] > 0) $price5 = (double)$_POST['price5'];
        $price1_old = 0; if (isset($_POST['price1_old']) && $_POST['price1_old'] > 0) $price1_old = (double)$_POST['price1_old'];
        $price2_old = 0; if (isset($_POST['price2_old']) && $_POST['price2_old'] > 0) $price2_old = (double)$_POST['price2_old'];
        $price3_old = 0; if (isset($_POST['price3_old']) && $_POST['price3_old'] > 0) $price3_old = (double)$_POST['price3_old'];
        $price4_old = 0; if (isset($_POST['price4_old']) && $_POST['price4_old'] > 0) $price4_old = (double)$_POST['price4_old'];
        $price5_old = 0; if (isset($_POST['price5_old']) && $_POST['price5_old'] > 0) $price5_old = (double)$_POST['price5_old'];
        $element_admin_rating = 0; if (isset($_POST['element_admin_rating']) && $_POST['element_admin_rating'] > 0) $element_admin_rating = (double)$_POST['element_admin_rating'];
        $order_id = 0; if (isset($_POST['order_id']) && $_POST['order_id'] > 0) $order_id = (int)$_POST['order_id'];
        
        $element_id = (int)$_GET['id'];
        if (trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues");exit();}

        $element_name = mysql_real_escape_string(trim($_POST['element_name']));
        $element_url = mysql_real_escape_string(trim($_POST['element_url']));
        $element_title = mysql_real_escape_string(trim($_POST['element_title']));
        $element_meta_keywords = mysql_real_escape_string(trim($_POST['element_meta_keywords']));
        $element_meta_description = mysql_real_escape_string(trim($_POST['element_meta_description']));
        $tags = mysql_real_escape_string(trim($_POST['tags']));
        $store_name = mysql_real_escape_string(trim($_POST['store_name']));
        $c_store_name = mysql_real_escape_string(trim($_POST['c_store_name']));
        $ym_store_name = mysql_real_escape_string(trim($_POST['ym_store_name']));
        $producer_store_name = mysql_real_escape_string(trim($_POST['producer_store_name']));
        $parent_id = $_POST['parent_id'];
        if (isset($_POST['producer_id'])) $producer_id = $_POST['producer_id']; else $producer_id = 0;
        $reserve = $_POST['reserve'];
        $special = $_POST['special'];
        $new = $_POST['new'];
        $hit = $_POST['hit'];
        $unit_id = 0; if(isset($_POST['unit_id'])) $unit_id = $_POST['unit_id'];
        $date = substr($_POST['date'],6,4).substr($_POST['date'],3,2).substr($_POST['date'],0,2);
        $date_begin = substr($_POST['date_begin'],6,4).substr($_POST['date_begin'],3,2).substr($_POST['date_begin'],0,2);
        $element_weight = (double)$_POST['element_weight'];
        $element_quantity = (double)$_POST['element_quantity'];
        $is_rating = $_POST['is_rating'];
        $is_commentation = $_POST['is_commentation'];

        if ($c_store_name !== '') {
            $result = mysql_query("select * from shop_cat_elements where c_store_name = '".stripslashes($c_store_name)."' and element_id != $element_id");
            if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=duplicate"); exit();}
        }

        $result = mysql_query("select * from shop_cat_elements where element_id=$element_id");
        $row = mysql_fetch_array($result);
        $img_path1 = $row['img_path1'];
        $img_path2 = $row['img_path2'];
        $img_path3 = $row['img_path3'];
        $old_parent_id = $row['parent_id'];
  
        //очистка свойств группы
        if ($parent_id !== $old_parent_id) mysql_query("delete from shop_cat_group_sizes_elements_availability where element_id = $id");

// picture1 --------------------------------------------------------------------
  if (isset($_FILES['picture1']['name']) &&
   is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
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
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path1 != '')
   {
     if (!use_file($img_path1,'shop_cat_elements','img_path1') || !use_file($img_path1,'shop_cat_elements','img_path2') || !use_file($img_path1,'shop_cat_elements','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path1);
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
// picture1 end ----------------------------------------------------------------

// picture2 --------------------------------------------------------------------
  if (isset($_FILES['picture2']['name']) &&
   is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
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
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path2 != '')
   {
     if (!use_file($img_path2,'shop_cat_elements','img_path1') || !use_file($img_path2,'shop_cat_elements','img_path2') || !use_file($img_path2,'shop_cat_elements','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path2);
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
// picture2 end-----------------------------------------------------------------

// picture3 --------------------------------------------------------------------
  if (isset($_FILES['picture3']['name']) &&
   is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
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
    default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=incorrectfiletype"); exit(); break;
   }

  //удаляем старый,если не используется
  if ($img_path3 != '')
   {
     if (!use_file($img_path3,'shop_cat_elements','img_path1') || !use_file($img_path3,'shop_cat_elements','img_path2') || !use_file($img_path3,'shop_cat_elements','img_path3'))
     @unlink($_SERVER['DOCUMENT_ROOT'].$img_path3);
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
// picture3 end-----------------------------------------------------------------

  //уникальная запись! Обновляем содержимое...
  if (isset($_FILES['picture1']['name']) && is_uploaded_file($_FILES['picture1']['tmp_name']))
   {
    $result = mysql_query("update shop_cat_elements set img_path1='"."/userfiles/shop_cat_images/$user_file_name1"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture2']['name']) && is_uploaded_file($_FILES['picture2']['tmp_name']))
   {
    $result = mysql_query("update shop_cat_elements set img_path2='"."/userfiles/shop_cat_images/$user_file_name2"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }
  if (isset($_FILES['picture3']['name']) && is_uploaded_file($_FILES['picture3']['tmp_name']))
   {
    $result = mysql_query("update shop_cat_elements set img_path3='"."/userfiles/shop_cat_images/$user_file_name3"."' where element_id=$element_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}
    $cache = new Cache;
    $cache->clear_all_image_cache();
   }

   //Обновляем...
   $result = mysql_query("update shop_cat_elements set parent_id = $parent_id,
                                                       date = $date,
                                                       date_begin = $date_begin,
                                                       element_name = '$element_name',
                                                       element_url = '$element_url',
                                                       element_title = '$element_title',
                                                       element_meta_keywords = '$element_meta_keywords',
                                                       element_meta_description = '$element_meta_description',
                                                       tags = '$tags',
                                                       store_name = '$store_name',
                                                       c_store_name = '$c_store_name',
                                                       ym_store_name = '$ym_store_name',
                                                       producer_store_name = '$producer_store_name',
                                                       producer_id = $producer_id,
                                                       reserve = $reserve,
                                                       special = $special,
                                                       new = $new,
                                                       hit = $hit,
                                                       unit_id = $unit_id,
                                                       price1 = $price1,
                                                       price2 = $price2,
                                                       price3 = $price3,
                                                       price4 = $price4,
                                                       price5 = $price5,
                                                       price1_old = $price1_old,
                                                       price2_old = $price2_old,
                                                       price3_old = $price3_old,
                                                       price4_old = $price4_old,
                                                       price5_old = $price5_old,
                                                       element_weight = $element_weight,
                                                       quantity = $element_quantity,
                                                       element_admin_rating = $element_admin_rating,
                                                       order_id = $order_id,
                                                       is_rating = $is_rating,
                                                       is_commentation = $is_commentation
                                                       where element_id = $element_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}

//копируем файлы, если необходимо
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

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $element_id = (int)$_GET['id'];
  $delete_img = $_GET['delete_img'];

  if ($delete_img == '1')
   {
     $result = mysql_query("select img_path1 from shop_cat_elements where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path1'],'shop_cat_elements','img_path1')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path1']);
     $result = mysql_query("update shop_cat_elements set img_path1='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '2')
   {
     $result = mysql_query("select img_path2 from shop_cat_elements where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path2'],'shop_cat_elements','img_path2')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path2']);
     $result = mysql_query("update shop_cat_elements set img_path2='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }
  if ($delete_img == '3')
   {
     $result = mysql_query("select img_path3 from shop_cat_elements where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path3'],'shop_cat_elements','img_path3')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path3']);
     $result = mysql_query("update shop_cat_elements set img_path3='' where element_id=$element_id");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();
  } else $user->no_rules('delete');
 }

//-----------------------------------------------------------------------------
// AJAX

function add_site($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста сайт");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_sites where element_id = $id and site_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такой сайт уже используется, попробуйте выбрать другой");
     return $objResponse;
   }
  else
   {
     mysql_query("insert into shop_cat_element_sites values ($id, $value)");
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_sites($id);");
  return $objResponse;
}

function add_element($parent_id, $value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста товар");
     return $objResponse;
   }

    $result = mysql_query("select * from shop_cat_element_elements where element_id = $id and similar_element_id = $value");
    if (mysql_num_rows($result) > 0){
        $objResponse->alert("Такой товар уже используется, попробуйте выбрать другой");
        return $objResponse;
    }

  if ($value == $id)
   {
     $objResponse->alert("Товар не может ссылаться на себя");
     return $objResponse;
   }

  mysql_query("insert into shop_cat_element_elements values ($id, $value, 0)");
  $i = 1;
  $res = mysql_query("select * from shop_cat_element_elements order by order_id");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        mysql_query("update shop_cat_element_elements set order_id = $i where element_id = ".$r['element_id']." and similar_element_id = ".$r['similar_element_id']);
        $i++;
      }
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->assign('elements', 'innerHTML', '<p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>');
  $objResponse->script("xajax_show_elements_in_group($parent_id, $id)");
  $objResponse->script("xajax_show_elements($id);");
  return $objResponse;
}

function add_related_element($parent_id, $value, $id) {
    $objResponse = new xajaxResponse();
    if (trim($value) == '') {
        $objResponse->alert("Выберите пожалуйста товар");
        return $objResponse;
    }

    $result = mysql_query("select * from shop_cat_related_elements where element_id = $id and related_element_id = $value");
    if (mysql_num_rows($result) > 0) {
        $objResponse->alert("Такой товар уже используется, попробуйте выбрать другой");
        return $objResponse;
    }

    if ($value == $id) {
        $objResponse->alert("Товар не может ссылаться на себя");
        return $objResponse;
    }

    mysql_query("insert into shop_cat_related_elements values ($id, $value, 0)");
    $i = 1;
    $res = mysql_query("select * from shop_cat_related_elements order by order_id");
    if (mysql_num_rows($res) > 0) {
        while ($r = mysql_fetch_array($res)) {
            mysql_query("update shop_cat_related_elements set order_id = $i where element_id = ".$r['element_id']." and related_element_id = ".$r['related_element_id']);
            $i++;
        }
    }
    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();

    $_SESSION['smart_tools_refresh'] = 'enable';
    $objResponse->assign('related_elements', 'innerHTML', '<p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>');
    $objResponse->script("xajax_show_related_elements_in_group($parent_id, $id)");
    $objResponse->script("xajax_show_related_elements($id);");
    return $objResponse;
}

function add_element_a($parent_id, $value, $action_id, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста товар");
     return $objResponse;
   }

    $result = mysql_query("select * from shop_cat_element_action_values where element_id = $id and action_id = $action_id and action_element_id = $value");
    if (mysql_num_rows($result) > 0) {
        $objResponse->alert("Такой товар уже используется, попробуйте выбрать другой");
        return $objResponse;
    }

  if ($value == $id)
   {
     $objResponse->alert("Товар не может ссылаться на себя");
     return $objResponse;
   }

  mysql_query("insert into shop_cat_element_action_values values ($id, $action_id, $value, 0)");
  $i = 1;
  $res = mysql_query("select * from shop_cat_element_action_values order by order_id");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        mysql_query("update shop_cat_element_action_values set order_id = $i where element_id = ".$r['element_id']." and action_id = ".$r['action_id']." and action_element_id = ".$r['similar_element_id']);
        $i++;
      }
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  
  $parent_id = 0;
  $res = mysql_query("select * from shop_cat_elements where element_id = $value");
  if (mysql_num_rows($res) > 0) {$r = mysql_fetch_array($res); $parent_id = $r['parent_id'];}
  

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_edit_action($action_id, $id);");
  $objResponse->script("xajax_show_elements_in_group_a($parent_id, $action_id, $id)");
  return $objResponse;
}

function add_group($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста группу");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_elements where element_id = $id and similar_element_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такая группа уже используется, попробуйте выбрать другую");
     return $objResponse;
   }

  mysql_query("insert into shop_cat_element_elements values ($id, $value, 0)");
  $res = mysql_query("select * from shop_cat_element_elements order by order_id");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        mysql_query("update shop_cat_element_elements set order_id = $i where element_id = ".$r['element_id']." and similar_element_id = ".$r['similar_element_id']);
        $i++;
      }
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_elements($id);");
  return $objResponse;
}

function add_related_group($value, $id) {
    $objResponse = new xajaxResponse();
  
    if (trim($value) == '') {
        $objResponse->alert("Выберите пожалуйста группу");
        return $objResponse;
    }

    $result = mysql_query("select * from shop_cat_related_elements where element_id = $id and similar_element_id = $value");
    if (mysql_num_rows($result) > 0) {
        $objResponse->alert("Такая группа уже используется, попробуйте выбрать другую");
        return $objResponse;
    }

    mysql_query("insert into shop_cat_related_elements values ($id, $value, 0)");
    $res = mysql_query("select * from shop_cat_related_elements order by order_id");
    if (mysql_num_rows($res) > 0) {
        while ($r = mysql_fetch_array($res)) {
            mysql_query("update shop_cat_related_elements set order_id = $i where element_id = ".$r['element_id']." and related_element_id = ".$r['related_element_id']);
            $i++;
        }
    }
  
    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();

    $_SESSION['smart_tools_refresh'] = 'enable';
    $objResponse->script("xajax_show_related_elements($id);");
    return $objResponse;
}

function add_group_a($value, $action_id, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста группу");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_action_values where element_id = $id and action_id = $action_id and action_element_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такая группа уже используется, попробуйте выбрать другую");
     return $objResponse;
   }

  mysql_query("insert into shop_cat_element_action_values values ($id, $action_id, $value, 0)");
  $res = mysql_query("select * from  shop_cat_element_action_values order by order_id");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        mysql_query("update shop_cat_element_action_values set order_id = $i where element_id = ".$r['element_id']." and action_id = ".$r['action_id']." and action_element_id = ".$r['action_element_id']);
        $i++;
      }
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_edit_action($action_id, $id);");
  return $objResponse;
}

function add_grid($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста свойство");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_grids where element_id = $id and grid_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такое свойство уже используется, попробуйте выбрать другое");
     return $objResponse;
   }
  else
   {
     mysql_query("insert into shop_cat_element_grids values ($id, $value, 0)");
     $i = 1;
     $res = mysql_query("select * from shop_cat_element_grids order by order_id");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
         {
           mysql_query("update shop_cat_element_grids set order_id = $i where element_id = ".$r['element_id']." and grid_id = ".$r['grid_id']);
           $i++;
         }
      }
    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_grids($id);");
  $objResponse->script("xajax_show_grids_edit($id);");
  return $objResponse;
}

function add_card($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста карточку");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_cards where element_id = $id and card_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такая карточка уже используется, попробуйте выбрать другую");
     return $objResponse;
   }
  else
   {
     mysql_query("insert into shop_cat_element_cards values ($id, $value, 0)");
     $res = mysql_query("select * from shop_cat_element_cards order by order_id");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
         {
           mysql_query("update shop_cat_element_cards set order_id = $i where element_id = ".$r['element_id']." and card_id = ".$r['card_id']);
           $i++;
         }
      }
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_cards($id);");
  return $objResponse;
}

function add_action($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста акцию");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_actions where element_id = $id and action_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такая акция уже используется, попробуйте выбрать другую");
     return $objResponse;
   }
  else
   {
     mysql_query("insert into shop_cat_element_actions values ($id, $value, 0, 0)");
     $res = mysql_query("select * from shop_cat_element_actions order by order_id");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
         {
           mysql_query("update shop_cat_element_actions set order_id = $i where element_id = ".$r['element_id']." and action_id = ".$r['action_id']);
           $i++;
         }
      }
     //Обновление кэша связанных модулей на сайте
     $cache = new Cache; $cache->clear_cache_by_module();
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_actions($id);");
  return $objResponse;
}

function show_sites($id)
{
  $objResponse = new xajaxResponse();
  $text_sites = "";
  $result = mysql_query("select
                         shop_cat_sites.site_id,
                         shop_cat_sites.site_name
                         from
                         shop_cat_sites, shop_cat_element_sites
                         where shop_cat_element_sites.element_id = $id and
                         shop_cat_element_sites.site_id = shop_cat_sites.site_id");
   if (mysql_num_rows($result) > 0)
    {
      $text_sites .= '<table cellspacing="0" cellpadding="2" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_sites .=  '<tr><td><span class="grey">'.htmlspecialchars($row['site_name']).'</span></td>
                    <td><a onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'site\','.$row['site_id'].','.$id.');}">
                    <img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
       }
      $text_sites .= '</table>';
    }
   else $text_sites .= '<p align="center">Нет сайтов</p>';

  $select_sites = '<select style="width:280px;" name="site_id">
             <option value="">Выберите сайт...</option>';
             $res = mysql_query("select * from shop_cat_sites order by site_name asc");
             if (mysql_num_rows($res) > 0)
             {
               while ($row = mysql_fetch_array($res))
               $select_sites .= '<option value="'.$row['site_id'].'">'.htmlspecialchars($row['site_name']).'</option>';
             }
  $select_sites .= '</select><button type="button" title="Добавить сайт" onclick="xajax_add_site(this.form.site_id.options[this.form.site_id.selectedIndex].value,'.$id.');">Добавить</button>';

	$objResponse->assign("site_values","innerHTML",$text_sites);
	$objResponse->assign("site_select","innerHTML",$select_sites);
	return $objResponse;
}

function show_elements($id)
{
  $objResponse = new xajaxResponse();
  $text_elements = "";
  $result = mysql_query("select
                         shop_cat_element_elements.similar_element_id,
                         shop_cat_elements.element_name,
                         shop_cat_elements.store_name,
                         shop_cat_elements.type
                         from
                         shop_cat_elements, shop_cat_element_elements
                         where shop_cat_element_elements.element_id = $id and
                         shop_cat_element_elements.similar_element_id = shop_cat_elements.element_id
                         order by shop_cat_element_elements.order_id asc");
                             
   if (mysql_num_rows($result) > 0)
    {
      $i = 1; 
      $text_elements .= '<table cellspacing="0" cellpadding="0" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_elements .=  '<tr><td><span class="grey">'.htmlspecialchars($row['element_name']);
         $text_elements .= ' &nbsp; (id: '.$row['similar_element_id'].')';
         $text_elements .= '</span></td><td>';
         if ($i == 1) $text_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'element\','.$row['similar_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'element\','.$row['similar_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_elements .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'element\','.$row['similar_element_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_elements .= '</td></tr>';
         $i++;           
       }
      $text_elements .= '</table>';
    }
   else $text_elements .= '<p align="center">Нет товаров и групп</p>';

	$objResponse->assign("elements_values","innerHTML",$text_elements);
	return $objResponse;
}

function show_related_elements($id) {
    $objResponse = new xajaxResponse();
    $text_elements = "";
    $result = mysql_query("select
                         shop_cat_related_elements.related_element_id,
                         shop_cat_elements.element_name,
                         shop_cat_elements.store_name,
                         shop_cat_elements.type
                         from
                         shop_cat_elements, shop_cat_related_elements
                         where shop_cat_related_elements.element_id = $id and
                         shop_cat_related_elements.related_element_id = shop_cat_elements.element_id
                         order by shop_cat_related_elements.order_id asc");
                             
   if (mysql_num_rows($result) > 0)
    {
      $i = 1; 
      $text_elements .= '<table cellspacing="0" cellpadding="0" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_elements .=  '<tr><td><span class="grey">'.htmlspecialchars($row['element_name']);
         $text_elements .= ' &nbsp; (id: '.$row['related_element_id'].')';
         $text_elements .= '</span></td><td>';
         if ($i == 1) $text_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'related_element\','.$row['related_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'related_element\','.$row['related_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_elements .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'related_element\','.$row['related_element_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_elements .= '</td></tr>';
         $i++;           
       }
      $text_elements .= '</table>';
    }
   else $text_elements .= '<p align="center">Нет товаров и групп</p>';

	$objResponse->assign("related_elements_values", "innerHTML", $text_elements);
	return $objResponse;
}

function show_elements_in_group($parent_id, $element_id) {
  $objResponse = new xajaxResponse();
  $text = ''; 
  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellspacing="0" cellpadding="0">';
     while ($row = mysql_fetch_array($result))
      {
        $similar_element_id = $row['element_id'];
        $text .= '<tr><td><input type="checkbox" id="eig'.$similar_element_id.'" name="action_element_'.$similar_element_id.'"';

        $res = mysql_query("select * from shop_cat_element_elements where element_id = $element_id and similar_element_id = $similar_element_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_delete_record(\'element\','.$similar_element_id.','.$element_id.');"';
         }
        else $text .= ' onclick="xajax_add_element('.$parent_id.', '.$similar_element_id.','.$element_id.');"';
        $text .= '></td><td> &nbsp; <label for="eig'.$similar_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
    }
  else $text .= '<p align="center">Нет товаров</p>';

  $objResponse->assign("elements","innerHTML",$text);
  return $objResponse;
}

function show_related_elements_in_group($parent_id, $element_id) {
    $objResponse = new xajaxResponse();
    $text = ''; 
    $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
    if (mysql_num_rows($result) > 0) {
        $text .= '<table cellspacing="0" cellpadding="0">';
        while ($row = mysql_fetch_array($result)) {
            $related_element_id = $row['element_id'];
            $text .= '<tr><td><input type="checkbox" id="reig'.$related_element_id.'" name="action_element_'.$related_element_id.'"';

            $res = mysql_query("select * from shop_cat_related_elements where element_id = $element_id and related_element_id = $related_element_id");
            if (mysql_num_rows($res) > 0)  {
                $text .= ' checked';
                $text .= ' onclick="xajax_delete_record(\'related_element\','.$related_element_id.','.$element_id.');"';
            }
            else $text .= ' onclick="xajax_add_related_element('.$parent_id.', '.$related_element_id.', '.$element_id.');"';
            $text .= '></td><td> &nbsp; <label for="reig'.$related_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
        }
        $text .= '</table><br/>';
    }
    else $text .= '<p align="center">Нет товаров</p>';

    $objResponse->assign("related_elements","innerHTML",$text);
    return $objResponse;
}

function show_elements_in_group_a($parent_id, $action_id, $element_id)
{
  $objResponse = new xajaxResponse();
  $text = ''; 
  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellspacing="0" cellpadding="0">';
     while ($row = mysql_fetch_array($result))
      {
        $action_element_id = $row['element_id'];
        $text .= '<tr><td><input type="checkbox" id="eiga'.$action_element_id.'" name="action_element_'.$action_element_id.'"';

        $res = mysql_query("select * from shop_cat_element_action_values where element_id = $element_id and action_id = $action_id and action_element_id = $action_element_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_delete_record(\'action_element\','.$action_element_id.','.$element_id.','.$action_id.');"';
         }
        else $text .= ' onclick="xajax_add_element_a('.$parent_id.', '.$action_element_id.', '.$action_id.', '.$element_id.');"';
        $text .= '></td><td> &nbsp; <label for="eiga'.$action_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
    }
  else $text .= '<p align="center">Нет товаров</p>';

  $objResponse->assign("elements_a","innerHTML",$text);
  return $objResponse;
}

function show_element_info($element_id)
{
  $objResponse = new xajaxResponse();
  $text = '<br/><div class="small" style="padding: 4px;">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and element_id = $element_id");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $text .= '<strong>'.htmlspecialchars($row['element_name']).'</strong><br/>
		           id: '.$element_id.'<br/>
		           арт.: '.htmlspecialchars($row['store_name']).'<br/>
		           арт. произв.: '.htmlspecialchars($row['producer_store_name']);
       }
   }
  else $text .= '';

  $text .= '</div>';
  $objResponse->assign("element_info","innerHTML",$text);
  return $objResponse;
}

function show_related_element_info($element_id)
{
  $objResponse = new xajaxResponse();
  $text = '<br/><div class="small" style="padding: 4px;">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and element_id = $element_id");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $text .= '<strong>'.htmlspecialchars($row['element_name']).'</strong><br/>
		           id: '.$element_id.'<br/>
		           арт.: '.htmlspecialchars($row['store_name']).'<br/>
		           арт. произв.: '.htmlspecialchars($row['producer_store_name']);
       }
   }
  else $text .= '';

  $text .= '</div>';
  $objResponse->assign("element_info","innerHTML",$text);
  return $objResponse;
}

function show_element_info_a($element_id)
{
  $objResponse = new xajaxResponse();
  $text = '<br/><div class="small" style="padding: 4px;">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and element_id = $element_id");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $text .= '<strong>'.htmlspecialchars($row['element_name']).'</strong><br/>
		           id: '.$element_id.'<br/>
		           арт.: '.htmlspecialchars($row['store_name']).'<br/>
		           арт. произв.: '.htmlspecialchars($row['producer_store_name']);
       }
   }
  else $text .= '';

  $text .= '</div>';
  $objResponse->assign("element_info_a","innerHTML",$text);
  return $objResponse;
}

function search_elements($str, $element_id) {
    $objResponse = new xajaxResponse();
    $str = mb_strtolower(trim($str), 'UTF-8');

    $text = ''; 
    $result = mysql_query(" select
                            *
                            from
                            shop_cat_elements
                            where
                            type = 0 and
                            (
                                element_name like '%$str%' or
                                store_name like '%$str%' or
                                c_store_name like '%$str%' or
                                element_id like '%$str%' or
                                description like '%$str%' or
                                description_full like '%$str%' or
                                description_extra like '%$str%'
                            )");
    if (mysql_num_rows($result) > 0) {
        $text .= '<table cellspacing="0" cellpadding="0">';
        while ($row = mysql_fetch_array($result)) {
            $similar_element_id = $row['element_id'];
            $text .= '<tr><td><input type="checkbox" id="se'.$similar_element_id.'" name="action_element_'.$similar_element_id.'"';

            $res = mysql_query("select * from shop_cat_element_elements where element_id = $element_id and similar_element_id = $similar_element_id");
            if (mysql_num_rows($res) > 0) {
                $text .= ' checked';
                $text .= ' onclick="xajax_delete_record(\'element\','.$similar_element_id.','.$element_id.');"';
            } else
                $text .= ' onclick="xajax_add_element(0, '.$similar_element_id.','.$element_id.');"';
            $text .= '></td><td> &nbsp; <label for="se'.$similar_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
        }
        $text .= '</table><br/>';
    } else
        $text .= '<p align="center">Нет товаров</p>';

    $objResponse->assign("elements","innerHTML",$text);
    return $objResponse;
}

function search_related_elements($str, $element_id) {
    $objResponse = new xajaxResponse();
    $str = mb_strtolower(trim($str), 'UTF-8');

    $text = ''; 
    $result = mysql_query(" select
                            *
                            from
                            shop_cat_elements
                            where
                            type = 0 and
                            (
                                element_name like '%$str%' or
                                store_name like '%$str%' or
                                c_store_name like '%$str%' or
                                element_id like '%$str%' or
                                description like '%$str%' or
                                description_full like '%$str%' or
                                description_extra like '%$str%'
                            )");
    if (mysql_num_rows($result) > 0) {
        $text .= '<table cellspacing="0" cellpadding="0">';
        while ($row = mysql_fetch_array($result)) {
            $related_element_id = $row['element_id'];
            $text .= '<tr><td><input type="checkbox" id="sre'.$related_element_id.'" name="action_element_'.$related_element_id.'"';

            $res = mysql_query("select * from shop_cat_related_elements where element_id = $element_id and related_element_id = $related_element_id");
            if (mysql_num_rows($res) > 0) {
                $text .= ' checked';
                $text .= ' onclick="xajax_delete_record(\'related_element\','.$related_element_id.','.$element_id.');"';
            } else
                $text .= ' onclick="xajax_add_related_element(0, '.$related_element_id.','.$element_id.');"';
            $text .= '></td><td> &nbsp; <label for="sre'.$related_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
        }
        $text .= '</table><br/>';
    } else
        $text .= '<p align="center">Нет товаров</p>';

    $objResponse->assign("related_elements","innerHTML",$text);
    return $objResponse;
}

function show_grids($id)
{
  $objResponse = new xajaxResponse();
  $text_grids = "";
  $result = mysql_query("select
                         shop_cat_grids.grid_id,
                         shop_cat_grids.grid_name
                         from
                         shop_cat_grids, shop_cat_element_grids
                         where shop_cat_element_grids.element_id = $id and
                         shop_cat_element_grids.grid_id = shop_cat_grids.grid_id
                         order by shop_cat_element_grids.order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1; 
      $text_grids .= '<table cellspacing="0" cellpadding="0" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_grids .=  '<tr><td><span class="grey">'.htmlspecialchars($row['grid_name']).'</span></td><td>';
         if ($i == 1) $text_grids .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_grids .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'grid\','.$row['grid_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_grids .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_grids .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'grid\','.$row['grid_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_grids .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'grid\','.$row['grid_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_grids .= '</td></tr>';
         $i++;           
       }
      $text_grids .= '</table>';

      $objResponse->script("xajax_show_grids_edit($id);");
    }
   else
    {
      $text_grids .= '<p align="center">Нет свойств</p>';
    	$objResponse->assign("grid_select_edit","innerHTML","Нет свойств");
    }

  $select_grids = '<select style="width:280px;" name="grid_id">
             <option value="">Выберите свойство...</option>';
             $res = mysql_query("select * from shop_cat_grids order by grid_name asc");
             if (mysql_num_rows($res) > 0)
             {
               while ($row = mysql_fetch_array($res))
               $select_grids .= '<option value="'.$row['grid_id'].'">'.htmlspecialchars($row['grid_name']).'</option>';
             }
  $select_grids .= '</select><button type="button" title="Добавить свойство" onclick="xajax_add_grid(this.form.grid_id.options[this.form.grid_id.selectedIndex].value,'.$id.');">Добавить</button>';

	$objResponse->assign("grid_values","innerHTML",$text_grids);
	$objResponse->assign("grid_select","innerHTML",$select_grids);
	return $objResponse;
}

function show_cards($id)
{
  $objResponse = new xajaxResponse();
  $text_cards = "";
  $result = mysql_query("select
                         shop_cat_cards.card_id,
                         shop_cat_cards.card_name
                         from
                         shop_cat_cards, shop_cat_element_cards
                         where shop_cat_element_cards.element_id = $id and
                         shop_cat_element_cards.card_id = shop_cat_cards.card_id
                         order by shop_cat_element_cards.order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1;
      $text_cards .= '<table cellspacing="0" cellpadding="1" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_cards .=  '<tr><td><span class="grey">'.htmlspecialchars($row['card_name']).'</span></td><td>';
         if ($i == 1) $text_cards .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_cards .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'card\','.$row['card_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_cards .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_cards .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'card\','.$row['card_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_cards .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'card\','.$row['card_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_cards .= '</td></tr>';
         $i++;           
       }
      $text_cards .= '</table>';
    }
   else
    {
      $text_cards .= '<p align="center">Нет карточек</p>';
    }

  $select_cards = '<select style="width:280px;" name="card_id">
             <option value="">Выберите карточку...</option>';
             $res = mysql_query("select * from shop_cat_cards order by card_name asc");
             if (mysql_num_rows($res) > 0)
             {
               while ($row = mysql_fetch_array($res))
               $select_cards .= '<option value="'.$row['card_id'].'">'.htmlspecialchars($row['card_name']).'</option>';
             }
  $select_cards .= '</select><button type="button" title="Добавить карточку" onclick="xajax_add_card(this.form.card_id.options[this.form.card_id.selectedIndex].value,'.$id.');">Добавить</button>';

	$objResponse->assign("card_values","innerHTML",$text_cards);
	$objResponse->assign("card_select","innerHTML",$select_cards);
	return $objResponse;
}

function show_actions($id)
{
  $objResponse = new xajaxResponse();
  $text_actions = "";
  $select_actions_edit = '<select style="width:280px;" name="action_id_edit">
                          <option value="">Выберите акцию...</option>';
  $result = mysql_query("select
                         shop_cat_actions.action_id,
                         shop_cat_actions.action_name
                         from
                         shop_cat_actions, shop_cat_element_actions
                         where shop_cat_element_actions.element_id = $id and
                         shop_cat_element_actions.action_id = shop_cat_actions.action_id
                         order by shop_cat_element_actions.order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1;
      $text_actions .= '<table cellspacing="0" cellpadding="1" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_actions .=  '<tr><td><span class="grey">'.htmlspecialchars($row['action_name']).'</span></td><td>';
         if ($i == 1) $text_actions .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_actions .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'action\','.$row['action_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_actions .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_actions .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'action\','.$row['action_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_actions .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'action\','.$row['action_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_actions .= '</td></tr>';
         $select_actions_edit .= '<option value="'.$row['action_id'].'">'.htmlspecialchars($row['action_name']).'</option>';
         $i++;           
       }
      $text_actions .= '</table>';
    }
   else
    {
      $text_actions .= '<p align="center">Нет акций</p>';
    }
  $select_actions_edit .= '</select><button type="button" title="Редактировать акцию" onclick="xajax_edit_action(this.form.action_id_edit.options[this.form.action_id_edit.selectedIndex].value,'.$id.');
                                                                                               xajax_edit_action_select(this.form.action_id_edit.options[this.form.action_id_edit.selectedIndex].value,'.$id.');">Редактировать</button>';
  $select_actions = '<select style="width:280px;" name="action_id">
             <option value="">Выберите акцию...</option>';
             $res = mysql_query("select * from shop_cat_actions order by action_name asc");
             if (mysql_num_rows($res) > 0)
             {
               while ($row = mysql_fetch_array($res))
               $select_actions .= '<option value="'.$row['action_id'].'">'.htmlspecialchars($row['action_name']).'</option>';
             }
  $select_actions .= '</select><button type="button" title="Добавить акцию" onclick="xajax_add_action(this.form.action_id.options[this.form.action_id.selectedIndex].value,'.$id.');">Добавить</button>';

	$objResponse->assign("action_values","innerHTML",$text_actions);
	$objResponse->assign("action_select","innerHTML",$select_actions);
	$objResponse->assign("action_select_edit","innerHTML",$select_actions_edit);
	return $objResponse;
}

function edit_action ($action_id, $id)
 {
  $objResponse = new xajaxResponse();
  $text_action_elements = '';
  $result = mysql_query("select
                         shop_cat_element_action_values.action_element_id,
                         shop_cat_elements.element_name,
                         shop_cat_elements.store_name,
                         shop_cat_elements.type
                         from
                         shop_cat_elements, shop_cat_element_action_values
                         where shop_cat_element_action_values.element_id = $id and
                         shop_cat_element_action_values.action_id = $action_id and
                         shop_cat_element_action_values.action_element_id = shop_cat_elements.element_id
                         order by shop_cat_element_action_values.order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1; 
      $text_action_elements .= '<table cellspacing="0" cellpadding="0" border="0">';
      while($row = mysql_fetch_array($result))
       {
         $text_action_elements .=  '<tr><td><span class="grey">'.htmlspecialchars($row['element_name']);
         $text_action_elements .= ' &nbsp; (id: '.$row['action_element_id'].')';
         $text_action_elements .= '</span></td><td>';
         if ($i == 1) $text_action_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else $text_action_elements .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'action_element\','.$row['action_element_id'].','.$id.','.$action_id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_action_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_action_elements .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'action_element\','.$row['action_element_id'].','.$id.','.$action_id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_action_elements .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'action_element\','.$row['action_element_id'].','.$id.','.$action_id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_action_elements .= '</td></tr>';
         $i++;           
       }
      $text_action_elements .= '</table><br />';
    }
   else $text_action_elements .= '<p align="center">Нет товаров в этой акции</p><br />';

  $objResponse->assign("action_values_edit","innerHTML",$text_action_elements);
  return $objResponse;
}

function edit_action_select ($action_id, $id)
 {
  $objResponse = new xajaxResponse();
  $text_action_elements_select = '';
  $text_action_elements_select .= '<select style="width:280px;" name="parent_id_elements_a" onchange="xajax_show_elements_in_group_a(this.form.parent_id_elements_a.options[this.form.parent_id_elements_a.selectedIndex].value, '.$action_id.', '.$id.');">
             <option value="">Выберите группу...</option>';
             global $options; $options = '';
             $text_action_elements_select .= show_select(0, '', 0, $shop_tree);
             $text_action_elements_select .= '</select><button type="button" title="Добавить группу" onclick="xajax_add_group_a(this.form.parent_id_elements_a.options[this.form.parent_id_elements_a.selectedIndex].value, '.$action_id.', '.$id.');">Добавить группу</button>
             <div id="element_info_a">&nbsp;</div>
             <div id="elements_a"></div>';
  $objResponse->assign("action_values_edit_select","innerHTML",$text_action_elements_select);
  return $objResponse;
 }

function show_grids_edit($id)
{
  $objResponse = new xajaxResponse();
  $select = '<select style="width:280px;" name="grid_id_edit">
             <option value="">Выберите свойство для редактирования...</option>';
             $res = mysql_query("select
                                 shop_cat_grids.grid_id,
                                 shop_cat_grids.grid_name
                                 from shop_cat_grids,shop_cat_element_grids
                                 where shop_cat_grids.grid_id = shop_cat_element_grids.grid_id and
                                 shop_cat_element_grids.element_id = $id order by shop_cat_grids.grid_name asc");
             if (mysql_num_rows($res) > 0)
             {
               while ($row = mysql_fetch_array($res))
               $select .= '<option value="'.$row['grid_id'].'">'.htmlspecialchars($row['grid_name']).'</option>';
             }
  $select .= '</select><button type="button" title="Редактировать свойство" onclick="xajax_edit_grid(this.form.grid_id_edit.options[this.form.grid_id_edit.selectedIndex].value,'.$id.');">Редактировать</button>';

	$objResponse->assign("grid_select_edit","innerHTML",$select);
	return $objResponse;
}

function edit_grid($grid_id,$id)
{
  $objResponse = new xajaxResponse();
  $text = "";

  $result = mysql_query("select
                         shop_cat_sizes.size_id,
                         shop_cat_sizes.size_name
                         from
                         shop_cat_sizes, shop_cat_grid_sizes
                         where shop_cat_grid_sizes.grid_id = $grid_id and
                         shop_cat_grid_sizes.size_id = shop_cat_sizes.size_id
                         order by shop_cat_grid_sizes.order_id asc");

  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellspacing="0" cellpadding="0">';
     while ($row = mysql_fetch_array($result))
      {
        $size_id = $row['size_id'];
        $text .= '<tr><td><input type="checkbox" name="size_'.$size_id.'"';

        $res = mysql_query("select * from shop_cat_sizes_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_edit_grid_size(\'delete\','.$size_id.','.$grid_id.','.$id.');"';
         }
        else $text .= ' onclick="xajax_edit_grid_size(\'add\','.$size_id.','.$grid_id.','.$id.');"';
        $text .= '></td><td> &nbsp; '.htmlspecialchars($row['size_name']).'</td></tr>';
      }
     $text .= '</table><br/>';
   }
  else $text .= '<p align="center">Нет характеристик в свойстве</p>';

	$objResponse->assign("grid_values_edit","innerHTML",$text);
	return $objResponse;
}

function edit_group_grid($grid_id,$id)
{
  $objResponse = new xajaxResponse();
  $text = "";

  $result = mysql_query("select
                         shop_cat_group_sizes.size_id,
                         shop_cat_group_sizes.size_name
                         from
                         shop_cat_group_sizes, shop_cat_group_grid_sizes
                         where shop_cat_group_grid_sizes.grid_id = $grid_id and
                         shop_cat_group_grid_sizes.size_id = shop_cat_group_sizes.size_id
                         order by shop_cat_group_grid_sizes.order_id asc");

  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellspacing="0" cellpadding="0">';
     while ($row = mysql_fetch_array($result))
      {
        $size_id = $row['size_id'];
        $text .= '<tr><td><input type="checkbox" id="gg'.$size_id.'" name="size_g_'.$size_id.'"';

        $res = mysql_query("select * from shop_cat_group_sizes_elements_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_edit_group_grid_size(\'delete\','.$size_id.','.$grid_id.','.$id.');"';
         }
        else $text .= ' onclick="xajax_edit_group_grid_size(\'add\','.$size_id.','.$grid_id.','.$id.');"';
        $text .= '></td><td> &nbsp; <label for="gg'.$size_id.'">'.htmlspecialchars($row['size_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
   }
  else $text .= '<p align="center">Нет характеристик в свойстве</p>';

	$objResponse->assign("group_grid_values_edit","innerHTML",$text);
	return $objResponse;
}

function edit_grid_size($action, $size_id, $grid_id, $id)
{
  $objResponse = new xajaxResponse();

  if ($action == 'add') mysql_query("insert into shop_cat_sizes_availability values ($id, $grid_id, $size_id)");
  if ($action == 'delete') mysql_query("delete from shop_cat_sizes_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_edit_grid($grid_id,$id);");
  return $objResponse;
}

function edit_group_grid_size($action, $size_id, $grid_id, $id)
{
  $objResponse = new xajaxResponse();

  if ($action == 'add') mysql_query("insert into shop_cat_group_sizes_elements_availability values ($id, $grid_id, $size_id)");
  if ($action == 'delete') mysql_query("delete from shop_cat_group_sizes_elements_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_edit_group_grid($grid_id,$id);");
  return $objResponse;
}

function move_up_record($record_type, $record_id, $id, $extra_value = '')
 {
  $objResponse = new xajaxResponse();
  if ($record_type == 'grid')
   {
     $last_grid_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_grids where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['grid_id'] == $record_id)
            {
              mysql_query("update shop_cat_element_grids set order_id = $last_order_id where element_id = $id and grid_id = $record_id");
              mysql_query("update shop_cat_element_grids set order_id = ".$row['order_id']." where element_id = $id and grid_id = $last_grid_id");
            }
           $last_grid_id = $row['grid_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_grids($id);");
   }
  if ($record_type == 'element')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_elements where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['similar_element_id'] == $record_id)
            {
              mysql_query("update shop_cat_element_elements set order_id = $last_order_id where element_id = $id and similar_element_id = $record_id");
              mysql_query("update shop_cat_element_elements set order_id = ".$row['order_id']." where element_id = $id and similar_element_id = $last_similar_element_id");
            }
           $last_similar_element_id = $row['similar_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_elements($id);");
   }

    if ($record_type == 'related_element') {
        $last_related_element_id = 0;
        $last_order_id = 0;
        $result = mysql_query("select * from shop_cat_related_elements where element_id = $id order by order_id asc");
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                if ($row['related_element_id'] == $record_id) {
                    mysql_query("update shop_cat_related_elements set order_id = $last_order_id where element_id = $id and related_element_id = $record_id");
                    mysql_query("update shop_cat_related_elements set order_id = ".$row['order_id']." where element_id = $id and related_element_id = $last_related_element_id");
                }
                $last_related_element_id = $row['related_element_id'];
                $last_order_id = $row['order_id'];
            }
        }
        $objResponse->script("xajax_show_related_elements($id);");
    }
  
  if ($record_type == 'card')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_cards where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['card_id'] == $record_id)
            {
              mysql_query("update shop_cat_element_cards set order_id = $last_order_id where element_id = $id and card_id = $record_id");
              mysql_query("update shop_cat_element_cards set order_id = ".$row['order_id']." where element_id = $id and card_id = $last_card_id");
            }
           $last_card_id = $row['card_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_cards($id);");
   }
  if ($record_type == 'action')
   {
     $last_action_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_actions where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['action_id'] == $record_id)
            {
              mysql_query("update shop_cat_element_actions set order_id = $last_order_id where element_id = $id and action_id = $record_id");
              mysql_query("update shop_cat_element_actions set order_id = ".$row['order_id']." where element_id = $id and action_id = $last_action_id");
            }
           $last_action_id = $row['action_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_actions($id);");
   }
  if ($record_type == 'action_element')
   {
     $last_action_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_action_values where element_id = $id and action_id = $extra_value order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['action_element_id'] == $record_id)
            {
              mysql_query("update shop_cat_element_action_values set order_id = $last_order_id where element_id = $id and action_id = $extra_value and action_element_id = $record_id");
              mysql_query("update shop_cat_element_action_values set order_id = ".$row['order_id']." where element_id = $id and action_id = $extra_value and action_element_id = $last_action_element_id");
            }
           $last_action_element_id = $row['action_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_edit_action($extra_value, $id);");
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->assign("grid_values_edit","innerHTML","");
  return $objResponse;
 }

function move_down_record($record_type, $record_id, $id, $extra_value = '')
 {
  $objResponse = new xajaxResponse();
  if ($record_type == 'grid')
   {
     $last_grid_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_grids where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_grid_id == $record_id)
            {
              mysql_query("update shop_cat_element_grids set order_id = ".$row['order_id']." where element_id = $id and grid_id = $record_id");
              mysql_query("update shop_cat_element_grids set order_id = $last_order_id where element_id = $id and grid_id = ".$row['grid_id']);
            }
           $last_grid_id = $row['grid_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_grids($id);");
   }
  if ($record_type == 'element')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_elements where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_similar_element_id == $record_id)
            {
              mysql_query("update shop_cat_element_elements set order_id = ".$row['order_id']." where element_id = $id and similar_element_id = $record_id");
              mysql_query("update shop_cat_element_elements set order_id = $last_order_id where element_id = $id and similar_element_id = ".$row['similar_element_id']);
            }
           $last_similar_element_id = $row['similar_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_elements($id);");
   }
  
    if ($record_type == 'related_element') {
        $last_related_element_id = 0;
        $last_order_id = 0;
        $result = mysql_query("select * from shop_cat_related_elements where element_id = $id order by order_id asc");
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                if ($last_related_element_id == $record_id) {
                    mysql_query("update shop_cat_related_elements set order_id = ".$row['order_id']." where element_id = $id and related_element_id = $record_id");
                    mysql_query("update shop_cat_related_elements set order_id = $last_order_id where element_id = $id and related_element_id = ".$row['related_element_id']);
                }
                $last_related_element_id = $row['related_element_id'];
                $last_order_id = $row['order_id'];
            }
        }
        $objResponse->script("xajax_show_related_elements($id);");
    }
  
  if ($record_type == 'card')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_cards where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_card_id == $record_id)
            {
              mysql_query("update shop_cat_element_cards set order_id = ".$row['order_id']." where element_id = $id and card_id = $record_id");
              mysql_query("update shop_cat_element_cards set order_id = $last_order_id where element_id = $id and card_id = ".$row['card_id']);
            }
           $last_card_id = $row['card_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_cards($id);");
   }
  if ($record_type == 'action')
   {
     $last_action_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_actions where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_action_id == $record_id)
            {
              mysql_query("update shop_cat_element_actions set order_id = ".$row['order_id']." where element_id = $id and action_id = $record_id");
              mysql_query("update shop_cat_element_actions set order_id = $last_order_id where element_id = $id and action_id = ".$row['action_id']);
            }
           $last_action_id = $row['action_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_actions($id);");
   }
  if ($record_type == 'action_element')
   {
     $last_action_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_element_action_values where element_id = $id and action_id = $extra_value order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_action_element_id == $record_id)
            {
              mysql_query("update shop_cat_element_action_values set order_id = ".$row['order_id']." where element_id = $id and action_id = $extra_value and action_element_id = $record_id");
              mysql_query("update shop_cat_element_action_values set order_id = $last_order_id where element_id = $id and action_id = $extra_value and action_element_id = ".$row['action_element_id']);
            }
           $last_action_element_id = $row['action_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_edit_action($extra_value, $id);");
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->assign("grid_values_edit","innerHTML","");
  return $objResponse;
 }

function delete_record($record_type, $record_id, $id, $extra_value = '') {
    $objResponse = new xajaxResponse();

    if ($record_type == 'site') {
        mysql_query("delete from shop_cat_element_sites where element_id = $id and site_id = $record_id");
        $objResponse->script("xajax_show_sites($id);");
    }
  
    if ($record_type == 'element') {
        mysql_query("delete from shop_cat_element_elements where element_id = $id and similar_element_id = $record_id");
        $objResponse->script("xajax_show_elements($id);");
    }

    if ($record_type == 'related_element') {
        mysql_query("delete from shop_cat_related_elements where element_id = $id and related_element_id = $record_id");
        $objResponse->script("xajax_show_related_elements($id);");
    }

    if ($record_type == 'grid') {
        mysql_query("delete from shop_cat_element_grids where element_id = $id and grid_id = $record_id");
        mysql_query("delete from shop_cat_sizes_availability where element_id = $id and grid_id = $record_id");
        $objResponse->script("xajax_show_grids($id);");
    }
  
    if ($record_type == 'card') {
        mysql_query("delete from shop_cat_element_cards where element_id = $id and card_id = $record_id");
        mysql_query("delete from shop_cat_option_values where element_id = $id and card_id = $record_id");
        $objResponse->script("xajax_show_cards($id);");
    }

    if ($record_type == 'action') {
        mysql_query("delete from shop_cat_element_actions where element_id = $id and action_id = $record_id");
        mysql_query("delete from shop_cat_element_action_values where element_id = $id and action_id = $record_id");
        $objResponse->script("xajax_show_actions($id);");
    }

    if ($record_type == 'action_element') {
        mysql_query("delete from shop_cat_element_action_values where element_id = $id and action_id = $extra_value and action_element_id = $record_id");
        $objResponse->script("xajax_edit_action($extra_value, $id);");
    }

    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();

    $_SESSION['smart_tools_refresh'] = 'enable';
    $objResponse->assign("grid_values_edit","innerHTML","");
    return $objResponse;
}

function show_prices($element_id)
{
  $objResponse = new xajaxResponse();
  $text = '';
  
  
  for ($price = 1; $price <= 2; $price++)
  {
  $text .= '<div id="price'.$price.'_div">';
  $price_value = 0;
  $price_value_old = 0;
  $res = mysql_query("select price".$price.", price".$price."_old from shop_cat_elements where element_id = $element_id");
  if (mysql_num_rows($res) > 0)
   {
     $r = mysql_fetch_array($res);
     $price_value = $r['price'.$price];
     $price_value_old = $r['price'.$price.'_old'];
   }
  
  $text .= $price.'. ';

  if ($price_value_old > 0) $text .= '<input style="width:70px" type="text" name="price'.$price.'_old" value="'.$price_value_old.'" disabled><input style="width:70px" type="text" name="price'.$price.'" value="'.$price_value.'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;" 
           onkeyup="m = ((100 - (document.form.price'.$price.'.value * 100 / document.form.price'.$price.'_old.value))/5).toFixed(0); 
           if (m >= 0 && m <= 21) this.form.price'.$price.'_perc.options[m].selected = true;
           else this.form.price'.$price.'_perc.options[0].selected = true;"><select id="price'.$price.'_perc" 
           onchange="document.form.price'.$price.'.value = 
		   (document.form.price'.$price.'_old.value - (document.form.price'.$price.'_old.value * this.form.price'.$price.'_perc.options[this.form.price'.$price.'_perc.selectedIndex].value / 100)).toFixed(0);">
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
																								<option value="50">50%</option>
																								<option value="55">55%</option>
																								<option value="60">60%</option>
																								<option value="65">65%</option>
																								<option value="70">70%</option>
																								<option value="75">75%</option>
																								<option value="80">80%</option>
																								<option value="85">85%</option>
																								<option value="90">90%</option>
																								<option value="95">95%</option>
																								<option value="100">100%</option></select><button type="button" onclick="xajax_add_new_price_value('.$price.', document.form.price'.$price.'.value, '.$element_id.', \'new\');">OK</button>';
  else $text .= '<input style="width:70px" type="text" name="price'.$price.'" value="'.$price_value.'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"><button type="button" onclick="xajax_add_new_price_value('.$price.', document.form.price'.$price.'.value, '.$element_id.',\'update\');">OK</button><button type="button" onclick="xajax_add_new_price('.$price.', '.$price_value.', '.$element_id.');">Сделать скидку</button>'; 
  $text .= '</div>';
  }

  $objResponse->assign('prices_div',"innerHTML",$text);
  return $objResponse;
}

function add_new_price($price, $price_value, $id)
{
  $objResponse = new xajaxResponse();
  $text .= $price.'. ';
  $text .= '<input style="width:70px" type="text" name="price'.$price.'_old" value="'.$price_value.'" disabled><input style="width:70px" type="text" name="price'.$price.'" value="'.$price_value.'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;" 
           onkeyup="m = ((100 - (document.form.price'.$price.'.value * 100 / document.form.price'.$price.'_old.value))/5).toFixed(0); 
           if (m >= 0 && m <= 21) this.form.price'.$price.'_perc.options[m].selected = true;
           else this.form.price'.$price.'_perc.options[0].selected = true;
		   "><select id="price'.$price.'_perc" 
           onchange="document.form.price'.$price.'.value = 
		   (document.form.price'.$price.'_old.value - (document.form.price'.$price.'_old.value * this.form.price'.$price.'_perc.options[this.form.price'.$price.'_perc.selectedIndex].value / 100)).toFixed(0);">
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
																								<option value="50">50%</option>
																								<option value="55">55%</option>
																								<option value="60">60%</option>
																								<option value="65">65%</option>
																								<option value="70">70%</option>
																								<option value="75">75%</option>
																								<option value="80">80%</option>
																								<option value="85">85%</option>
																								<option value="90">90%</option>
																								<option value="95">95%</option>
																								<option value="100">100%</option></select><button type="button" onclick="xajax_add_new_price_value('.$price.', document.form.price'.$price.'.value, '.$id.',\'new\');">OK</button>';
  $objResponse->assign('price'.$price.'_div',"innerHTML",$text);
  return $objResponse;
}

function add_new_price_value($price, $price_value, $id, $action)
{
  $objResponse = new xajaxResponse();
  $text = '';
 
  $price_value_old = 0;
  $price_value_before = 0;
  $res = mysql_query("select price".$price.", price".$price."_old from shop_cat_elements where element_id = $id");
  if (mysql_num_rows($res) > 0)
   {
     $r = mysql_fetch_array($res);
     $price_value_old = $r['price'.$price.'_old'];
     $price_value_before = $r['price'.$price];
   }
 
  if ($action == 'new' && $price_value_old == 0 && $price_value_before == $price_value) mysql_query("update shop_cat_elements set price".$price."=$price_value, price".$price."_old=0 where element_id = $id");
  elseif ($action == 'new' && $price_value_old == $price_value) mysql_query("update shop_cat_elements set price".$price."=$price_value, price".$price."_old=0 where element_id = $id");
  elseif ($action == 'new' && $price_value_old == 0) mysql_query("update shop_cat_elements set price".$price."=$price_value, price".$price."_old=$price_value_before where element_id = $id");
  elseif ($action == 'new' && $price_value_old > 0) mysql_query("update shop_cat_elements set price".$price."=$price_value where element_id = $id");
  elseif ($action == 'update') mysql_query("update shop_cat_elements set price".$price."=$price_value where element_id = $id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->assign('prices_div',"innerHTML",'<p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>');
  $objResponse->script("xajax_show_prices($id);");
  return $objResponse;
}

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

$xajax->registerFunction("show_sites");
$xajax->registerFunction("show_elements");
$xajax->registerFunction("show_related_elements");
$xajax->registerFunction("search_elements");
$xajax->registerFunction("search_related_elements");
$xajax->registerFunction("show_elements_in_group");
$xajax->registerFunction("show_related_elements_in_group");
$xajax->registerFunction("show_elements_in_group_a");
$xajax->registerFunction("show_element_info");
$xajax->registerFunction("show_element_info_a");
$xajax->registerFunction("show_grids");
$xajax->registerFunction("show_grids_edit");
$xajax->registerFunction("show_cards");
$xajax->registerFunction("show_actions");
$xajax->registerFunction("show_prices");

$xajax->registerFunction("edit_action");
$xajax->registerFunction("edit_action_select");
$xajax->registerFunction("edit_grid");
$xajax->registerFunction("edit_group_grid");
$xajax->registerFunction("edit_grid_size");
$xajax->registerFunction("edit_group_grid_size");

$xajax->registerFunction("add_site");
$xajax->registerFunction("add_element");
$xajax->registerFunction("add_related_element");
$xajax->registerFunction("add_element_a");
$xajax->registerFunction("add_group");
$xajax->registerFunction("add_related_group");
$xajax->registerFunction("add_group_a");
$xajax->registerFunction("add_grid");
$xajax->registerFunction("add_card");
$xajax->registerFunction("add_action");
$xajax->registerFunction("add_new_price");
$xajax->registerFunction("add_new_price_value");

$xajax->registerFunction("move_up_record");
$xajax->registerFunction("move_down_record");
$xajax->registerFunction("delete_record");

$xajax->registerFunction("text2url");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $element_id = (int)$_GET['id'];
 $result = mysql_query("select
                        *,
                        date_format(date, '%d.%m.%Y') as date,
                        date_format(date_begin, '%d.%m.%Y') as date_begin,
                        (select unit_id from shop_cat_elements where element_id = S.parent_id) as parent_unit_id
                        from shop_cat_elements as S
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_object($result);

 if ($row->img_path1 || $row->img_path2 || $row->img_path3) echo '<p>';
 if ($row->img_path1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path1).'" border="0"> &nbsp;';
 if ($row->img_path2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path2).'" border="0"> &nbsp;';
 if ($row->img_path3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path3).'" border="0">';
 if ($row->img_path1 || $row->img_path2 || $row->img_path3) echo '</p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat.php')) $tabs->add_tab('/admin/editors/edit_shop_cat.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_card_values.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_card_values.php?id='.$element_id, 'Карточки описаний');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_gallery.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_gallery.php?id='.$element_id, 'Фотогалерея');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_files.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_files.php?id='.$element_id, 'Файлы');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_on_map.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

  $shop_currency = 'руб.';
  $shop_currency = $user->get_cms_option('shop_currency');

   echo '<form name="form" enctype="multipart/form-data" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="element_name" value="'.htmlspecialchars($row->element_name).'" maxlength="255"/></td>
      <td><button type="button" onclick="xajax_text2url(this.form.element_name.value)">► URL</button></td>
    </tr>
    <tr>
      <td>URL</td>
      <td><input style="width:280px" type="text" name="element_url" id="element_url" value="'.htmlspecialchars($row->element_url).'" maxlength="255"/></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Заголовок страницы сайта<br /><span class="grey">TITLE</span></td>
      <td><input style="width:280px" type="text" name="element_title" value="'.htmlspecialchars($row->element_title).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Ключевые слова<br /><span class="grey">meta keyrords</span></td>
      <td><input style="width:280px" type="text" name="element_meta_keywords" value="'.htmlspecialchars($row->element_meta_keywords).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Описание<br /><span class="grey">meta description</span></td>
      <td><input style="width:280px" type="text" name="element_meta_description" value="'.htmlspecialchars($row->element_meta_description).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Тэги</td>
      <td><input style="width:280px" type="text" name="tags" value="'.htmlspecialchars($row->tags).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул</td>
      <td><input style="width:280px" type="text" name="store_name" value="'.htmlspecialchars($row->store_name).'"  maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул 1С<br/><span class="grey">Уникальный идентификатор</span></td>
      <td><input style="width:280px" type="text" name="c_store_name" value="'.htmlspecialchars($row->c_store_name).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул Яндекс.Маркет</td>
      <td><input style="width:280px" type="text" name="ym_store_name" value="'.htmlspecialchars($row->ym_store_name).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Артикул производителя</td>
      <td><input style="width:280px" type="text" name="producer_store_name" value="'.htmlspecialchars($row->producer_store_name).'" maxlength="255"></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата добавления<sup class="red">*</sup></td>
      <td>';
?>
    <script>
      LSCalendars["date"]=new LSCalendar();
      LSCalendars["date"].SetFormat("dd.mm.yyyy");
      LSCalendars["date"].SetDate("<?=$row->date?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=$row->date?>" name="date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>
<?
echo'</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Дата поступления в продажу</td>
      <td>';
?>
    <script>
      LSCalendars["date_begin"]=new LSCalendar();
      LSCalendars["date_begin"].SetFormat("dd.mm.yyyy");
      LSCalendars["date_begin"].SetDate("<?=$row->date_begin?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date_begin', event); return false;" style="width: 65px;" value="<?=$row->date_begin?>" name="date_begin"></td>
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
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="picture1"/></td><td>';
       if ($row->img_path1)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture2"/></td><td>';
       if ($row->img_path2)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=2&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr><tr><td><input style="width:280px" type="file" name="picture3"/></td><td>';
       if ($row->img_path3)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=3&id=$element_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
echo '</td></tr></table></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение товара<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0"';
            if ($row->parent_id == 0) echo ' selected';
            echo '>---Корень каталога---</option>
            '.show_select(0, '', $row->parent_id, $shop_tree).'
          </select>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Цена, '.$shop_currency.'<br><span class="grey">2 колонки цен в прайс-листе</span></td>
      <td>
      <!-- <div id="prices_div"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div> -->';      

  for ($price = 1; $price <= 5; $price++)
  {
    $price_value_old = 0;
    $price_value = 0;
    $po_key = false;
    $p_key = false;
    
    $res = mysql_query("select price".$price.", price".$price."_old from shop_cat_elements where element_id = $element_id");
    if (mysql_num_rows($res) > 0)
     {
       $r = mysql_fetch_array($res);
       $price_value = $r['price'.$price];
       $price_value_old = $r['price'.$price.'_old'];
       
       //if ($price_value == $price_value_old ||
       //    $price_value == 0) {$price_value = 0; $po_key = true;}
       //else $p_key = true;
     }
    echo '<div style="padding: 2px;">'.$price.'. ';
    echo '<span class="small">старая цена:</span> <input type="text" name="price'.$price.'_old" value="'.$price_value_old.'" style="width:70px;'.(($po_key) ? 'border: #090 2px solid;' : '').'"> &nbsp; ';
    echo '<span class="small">цена:</span> <select id="price'.$price.'_perc" 
          onchange="document.form.price'.$price.'.value = 
    	           (document.form.price'.$price.'_old.value - (document.form.price'.$price.'_old.value * this.form.price'.$price.'_perc.options[this.form.price'.$price.'_perc.selectedIndex].value / 100)).toFixed(0);">';
    for($i = 0; $i <= 100; $i+=10) echo '<option value="'.$i.'">'.$i.'%</option>';
    echo '</select>';
    echo '<input type="text" name="price'.$price.'" value="'.$price_value.'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;" 
          onkeyup="m = ((100 - (document.form.price'.$price.'.value * 100 / document.form.price'.$price.'_old.value))/5).toFixed(0); 
          if (m >= 0 && m <= 21) this.form.price'.$price.'_perc.options[m].selected = true;
          else this.form.price'.$price.'_perc.options[0].selected = true;" style="width:70px;'.(($p_key) ? 'border: #090 2px solid;' : '').'">';
    echo '</div>';
  }


    echo '</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>На заказ</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="reserve" '; if ($row->reserve == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="reserve" '; if ($row->reserve == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Спец. предложение</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="special" '; if ($row->special == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="special" '; if ($row->special == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
   </tr>
    <tr>
      <td>Новинка</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="new" '; if ($row->new == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="new" '; if ($row->new == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
   </tr>
    <tr>
      <td>Хит продаж</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="hit" '; if ($row->hit == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="hit" '; if ($row->hit == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
   </tr>
   <tr>
     <td>C этим товаром также покупают<br /><span class="grey">рекомендованные для покупки товары или группы</span></td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr>
            <td>
                <p>
                    <div id="elements_values">
                        <p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>
                    </div>
                </p>
            </td>
        </tr>
        <tr>
            <td>
            
                <p>
                <select style="width:280px;" name="parent_id_elements" onchange="xajax_show_elements_in_group(this.form.parent_id_elements.options[this.form.parent_id_elements.selectedIndex].value, '.$element_id.');">
                    <option value="">Выберите группу...</option>';

                    global $options; $options = '';
                    echo show_select(0, '', 0, $shop_tree);

                echo '</select><button type="button" title="Добавить группу" onclick="xajax_add_group(this.form.parent_id_elements.options[this.form.parent_id_elements.selectedIndex].value, '.$element_id.');">Добавить группу</button>
                </p>
                
                <p>
                    <input type="text" name="search" id="search" style="width: 280px;"><button onclick="xajax_search_elements($(\'#search\').val(), '.$element_id.');" type="button">Найти</button>
                </p>
                
                <div id="element_info">&nbsp;</div>
                <div id="elements"></div>
            </td>
        </tr>
       </table>
     </td>
      <td>&nbsp;</td>
   </tr>
   
   <tr>
     <td>Cвязанные товары<br /><span class="grey">подтовары или подгруппы у товара</span></td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr>
            <td>
                <p>
                    <div id="related_elements_values">
                        <p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p>
                    </div>
                </p>
            </td>
        </tr>
        <tr>
            <td>
            
                <p>
                <select style="width:280px;" name="parent_id_related_elements" onchange="xajax_show_related_elements_in_group(this.form.parent_id_related_elements.options[this.form.parent_id_related_elements.selectedIndex].value, '.$element_id.');">
                    <option value="">Выберите группу...</option>';

                    global $options; $options = '';
                    echo show_select(0, '', 0, $shop_tree);

                echo '</select><button type="button" title="Добавить группу" onclick="xajax_add_related_group(this.form.parent_id_related_elements.options[this.form.parent_id_related_elements.selectedIndex].value, '.$element_id.');">Добавить группу</button>
                </p>
                
                <p>
                    <input type="text" name="related_search" id="related_search" style="width: 280px;"><button onclick="xajax_search_related_elements($(\'#related_search\').val(), '.$element_id.');" type="button">Найти</button>
                </p>
                
                <div id="related_element_info">&nbsp;</div>
                <div id="related_elements"></div>
            </td>
        </tr>
       </table>
     </td>
      <td>&nbsp;</td>
   </tr>
   
   ';
   
//Производители   
     $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
     if (mysql_num_rows($res) > 0)
      {
        echo '<tr><td>Производитель</td><td>';
        echo '<select name="producer_id" style="width:280px;">
              <option value="0">---НЕТ---</option>';
        while ($r = mysql_fetch_array($res))
         {
           echo '<option value="'.$r['producer_id'].'"';
           if ($row->producer_id == $r['producer_id']) echo ' selected';
           echo '>'.htmlspecialchars($r['producer_name']);
           if ($r['producer_descr']) echo '&nbsp; ('.htmlspecialchars($r['producer_descr']).')';
           echo '</option>';
         }
	echo '</td><td>&nbsp;</td></tr>'; 
      }
     
//Единицы измерения  
     $res = mysql_query("select * from shop_units_of_measure order by unit_name asc");
     if (mysql_num_rows($res) > 0)
      {
        echo '<tr><td>Единица измерения</td><td>';
        echo '<select name="unit_id" style="width:280px;">
              <option value="0">---НЕТ---</option>';
        while ($r = mysql_fetch_array($res))
          echo '<option value="'.$r['unit_id'].'" '.(($row->unit_id == $r['unit_id']) ? ' selected' : '').'>'.htmlspecialchars($r['unit_name']).(($r['unit_descr']) ? ' &nbsp; ('.htmlspecialchars($r['unit_descr']).')' : '').'</option>';
	echo '</td><td>&nbsp;</td></tr>'; 
      }

//Количество
        echo '<tr><td>Количество, шт</td><td><input style="width:280px" type="text" name="element_quantity" value="'.htmlspecialchars($row->quantity).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td>&nbsp;</td></tr>'; 
//Вес
        echo '<tr><td>Вес, грамм</td><td><input style="width:280px" type="text" name="element_weight" value="'.htmlspecialchars($row->element_weight).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td>&nbsp;</td></tr>'; 
//Рейтиг
        echo '<tr><td>Рейтинг администратора<br /><span class="grey">Значение рейтига товара,<br />установленное администратором</span></td><td><input style="width:280px" type="text" name="element_admin_rating" value="'.htmlspecialchars($row->element_admin_rating).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td>&nbsp;</td></tr>'; 
//Сортировка
        echo '<tr><td>Порядок сортировки в текущей группе</td><td><input style="width:280px" type="text" name="order_id" value="'.htmlspecialchars($row->order_id).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td>&nbsp;</td></tr>'; 

//Сайты     
     $res = mysql_query("select * from shop_cat_sites order by site_name asc");
     if (mysql_num_rows($res) > 0)
      {
   echo '<tr>
     <td>Сайты, на которых будет<br/> отображаться товар<br /><span class="grey">Если используется<br />многосайтовый режим</span></td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="site_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="site_select"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
      }
      
//Свойства     
     $res = mysql_query("select * from shop_cat_grids order by grid_name asc");
     if (mysql_num_rows($res) > 0)
      {
	echo '<tr>
     <td>Свойства товара</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="grid_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="grid_select"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
   $res_s = mysql_query("select * from shop_cat_sizes");
   if (mysql_num_rows($res_s) > 0)
   {
    echo '<tr>
     <td>Наличие характеристик<br/>
     в свойствах данного товара</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="grid_select_edit"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="grid_values_edit"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
   }
      }
      
//Свойства товара в группе    
   $res = mysql_query("select
                       shop_cat_group_grids.grid_id,
                       shop_cat_group_grids.parent_grid_id,
                       shop_cat_group_grids.grid_name
                       from shop_cat_group_grids,shop_cat_group_element_grids
                       where shop_cat_group_grids.grid_id = shop_cat_group_element_grids.grid_id and
                       shop_cat_group_element_grids.element_id = $row->parent_id
                       order by shop_cat_group_element_grids.order_id asc");
   if (mysql_num_rows($res) > 0)
   {
     
     echo '<tr>
     <td>Наличие характеристик товара<br/>
     в свойствах данной группы </td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td>
          <select style="width:280px;" name="group_grid_id_edit">
            <option value="">Выберите свойство для редактирования...</option>';
                while ($row = mysql_fetch_array($res)) {
                    $str = array();
                    path_to_grid($row['parent_grid_id'], $str, $grid_tree);
                    $str = array_reverse($str);
                    $path = '';
                    foreach ($str as $value)
                        $path .= $value.' -&gt; ';

                    echo '<option value="'.$row['grid_id'].'">'.$path.htmlspecialchars($row['grid_name']).'</option>';
                }
      echo '</select><button type="button" title="Редактировать свойство" onclick="xajax_edit_group_grid(this.form.group_grid_id_edit.options[this.form.group_grid_id_edit.selectedIndex].value,'.$element_id.');">Редактировать</button> 
        </td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="group_grid_values_edit"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
   }

//Карточки описаний
     $res = mysql_query("select * from shop_cat_cards order by card_name asc");
     if (mysql_num_rows($res) > 0)
      {
	echo '<tr>
     <td>Карточки описаний товара</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="card_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="card_select"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
      }

//Акции
     $res = mysql_query("select * from shop_cat_actions order by action_name asc");
     if (mysql_num_rows($res) > 0)
      {
     echo '<tr>
     <td>Акции</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="action_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="action_select"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';

     echo '<tr>
     <td>Товары в акциях</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="action_select_edit"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="action_values_edit"></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="action_values_edit_select"></div></td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
      }

echo '
    <tr>
      <td>Участвуйет в рейтинге</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_rating" '; if ($row->is_rating == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Комментирование</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 1) echo ' checked'; echo ' value="1"></td>
         <td>&nbsp;Да</td>
         <td><img src="/admin/images/px.gif" alt="" width="10" height="1"></td>
         <td><input type="radio" name="is_commentation" '; if ($row->is_commentation == 0) echo ' checked'; echo ' value="0"></td>
         <td>&nbsp;Нет</td>
        </tr>
       </table>
      </td>
      <td>&nbsp;</td>
    </tr>

   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>

   <script type="text/javascript" language="JavaScript">
     begin_delay = 0;
     delay = 50;
     setTimeout("xajax_show_elements('.$element_id.')", begin_delay + delay*2);
     setTimeout("xajax_show_related_elements('.$element_id.')", begin_delay + delay*3);
     setTimeout("xajax_show_sites('.$element_id.')", begin_delay + delay*4);
     setTimeout("xajax_show_grids('.$element_id.')", begin_delay + delay*5);
     setTimeout("xajax_show_cards('.$element_id.')", begin_delay + delay*6);
     setTimeout("xajax_show_actions('.$element_id.')", begin_delay + delay*7);
   </script>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>