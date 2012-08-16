<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

function get_shop_tree(&$shop_tree)
 {
   $result = mysql_query("select * from shop_cat_elements where type = 1 order by order_id asc");
   if(mysql_num_rows($result) > 0)
     while ($row = mysql_fetch_object($result))
       $shop_tree[$row->parent_id][$row->element_id] = $row->element_name;
 }
$shop_tree = array(); get_shop_tree($shop_tree);

function show_select($parent_id = 0, $prefix = '', $selected_element_id = 0, &$shop_tree, $group_id = 0)
 {
   global $options;
   foreach($shop_tree[$parent_id] as $element_id => $element_name)
    {
      if ($element_id !== $group_id)
       {
         $options .= '<option value="'.$element_id.'"'.($selected_element_id == $element_id ? ' selected' : '').'>'.$prefix.htmlspecialchars($element_name).'</option>';
         show_select($element_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_element_id, $shop_tree, $group_id);
       }
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


function show_grid_select($parent_grid_id = 0, $prefix = '', &$grid_tree) {
    global $options;
    foreach($grid_tree[$parent_grid_id] as $grid_id => $grid_name) {
        $options .= '   <option value="'.$grid_id.'">'.
                        $prefix.htmlspecialchars($grid_name).'</option>';
        show_grid_select($grid_id, $prefix.'&nbsp;&nbsp;&nbsp;', $grid_tree);
    }
    return $options;
}

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

//------------------------------------------------------------------------------

if (isset($_POST['element_name']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {

  $element_id = (int)$_GET['id'];
  if (trim($_POST['element_name'])=='' || trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues");exit();}

  $element_name = trim($_POST['element_name']);
  $element_url = trim($_POST['element_url']);
  $tags = trim($_POST['tags']);
  $element_title = trim( $_POST['element_title']);
  $element_meta_keywords = trim($_POST['element_meta_keywords']);
  $element_meta_description = trim($_POST['element_meta_description']);
  $store_name = trim($_POST['store_name']);
  $c_store_name = trim($_POST['c_store_name']);
  $parent_id = $_POST['parent_id'];
  $unit_id = $_POST['unit_id'];
  $order_id = 0; if (isset($_POST['order_id']) && $_POST['order_id'] > 0) $order_id = (int)$_POST['order_id'];
  $is_rating = $_POST['is_rating'];
  $is_commentation = $_POST['is_commentation'];

  if ($element_id == $parent_id) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=group_error");exit();}

  $result = mysql_query("select * from shop_cat_elements where element_id=$element_id");
  $row = mysql_fetch_array($result);
  $img_path1 = $row['img_path1'];
  $old_parent_id =$row['parent_id'];

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
   if ($parent_id !== $old_parent_id) $order_id = 0;
   $result = mysql_query("update shop_cat_elements set parent_id=$parent_id,
                                                       element_name='$element_name',
                                                       element_url='$element_url',
                                                       element_title='$element_title',
                                                       element_meta_keywords='$element_meta_keywords',
                                                       element_meta_description='$element_meta_description',
                                                       tags='$tags',
                                                       store_name = '$store_name',
                                                       c_store_name = '$c_store_name',
                                                       is_rating = $is_rating,
                                                       is_commentation = $is_commentation,
                                                       unit_id = $unit_id,
                                                       order_id = $order_id where element_id=$element_id") or die(mysql_error());
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=db"); exit();}

   if ($parent_id !== $old_parent_id)
    {
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
    }

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
     $result = mysql_query("select img_path1 from shop_cat_elements where element_id=$element_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['img_path2'],'shop_cat_elements','img_path2')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['img_path2']);
     $result = mysql_query("update shop_cat_elements set img_path2='' where element_id=$element_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
   }

  if ($delete_img == '3')
   {
     $result = mysql_query("select img_path1 from shop_cat_elements where element_id=$element_id");
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

if (isset($_POST['card_id']) &&
    isset($_POST['recursion']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {

  $element_id = (int)$_GET['id'];
  if (trim($_POST['card_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&message=formvalues2");exit();}

  $recursion = $_POST['recursion'];
  $card_id = $_POST['card_id'];
  
  if ($recursion == 0)
   {
     $result = mysql_query("select * from shop_cat_elements where parent_id = $element_id and type = 0");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           $el_id = $row['element_id'];
           $res = mysql_query("select * from shop_cat_element_cards where element_id = $el_id and card_id = $card_id");
           if (mysql_num_rows($res) == 0) mysql_query("insert into shop_cat_element_cards (element_id, card_id) values ($el_id, $card_id)");
         }
      }
   }

  if ($recursion == 1)
   {
     function update_elements($parent_id, $card_id)
      {
        $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id");
        if(mysql_num_rows($result) > 0)
         {
           while ($row = mysql_fetch_array($result))
            {
              $el_id = $row['element_id'];
              if ($row['type'] == 0)
               {
                 $res = mysql_query("select * from shop_cat_element_cards where element_id = $el_id and card_id = $card_id");
                 if (mysql_num_rows($res) == 0) mysql_query("insert into shop_cat_element_cards (element_id, card_id) values ($el_id, $card_id)");
               }
              else update_elements($el_id, $card_id);
            }
         }
      }
     
     update_elements($element_id, $card_id);
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();

  } else $user->no_rules('edit');
 }

//-----------------------------------------------------------------------------
// AJAX

function add_grid($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста свойство");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_group_element_grids where element_id = $id and grid_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такое свойство уже используется, попробуйте выбрать другое");
     return $objResponse;
   }
  else
   {
     mysql_query("insert into shop_cat_group_element_grids values ($id, $value, 0)");
     $i = 1;
     $res = mysql_query("select * from shop_cat_group_element_grids order by order_id");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
         {
           mysql_query("update shop_cat_group_element_grids set order_id = $i where element_id = ".$r['element_id']." and grid_id = ".$r['grid_id']);
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

function add_element($value, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста товар");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_elements where element_id = $id and similar_element_id = $value");
  if (mysql_num_rows($result) > 0)
   {
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
  $objResponse->script("xajax_show_elements($id);");
  return $objResponse;
}

function add_element_a($value, $action_id, $id)
{
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста товар");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_element_action_values where element_id = $id and action_id = $action_id and action_element_id = $value");
  if (mysql_num_rows($result) > 0)
   {
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

function add_group_to_groups($value, $id)
{
  
  $objResponse = new xajaxResponse();
  if (trim($value) == '')
   {
     $objResponse->alert("Выберите пожалуйста группу");
     return $objResponse;
   }

  $result = mysql_query("select * from shop_cat_group_groups where element_id = $id and similar_element_id = $value");
  if (mysql_num_rows($result) > 0)
   {
     $objResponse->alert("Такая группа уже используется, попробуйте выбрать другую");
     return $objResponse;
   }

  mysql_query("insert into shop_cat_group_groups values ($id, $value, 0)");
  $res = mysql_query("select * from shop_cat_group_groups order by order_id");
  if (mysql_num_rows($res) > 0)
   {
     while ($r = mysql_fetch_array($res))
      {
        mysql_query("update shop_cat_group_groups set order_id = $i where element_id = ".$r['element_id']." and similar_element_id = ".$r['similar_element_id']);
        $i++;
      }
   }
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_groups($id);");
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

function show_groups($id)
{
  $objResponse = new xajaxResponse();
  $text_elements = "";
  $result = mysql_query("select
                         shop_cat_group_groups.similar_element_id,
                         shop_cat_elements.element_name
                         from
                         shop_cat_elements, shop_cat_group_groups
                         where shop_cat_group_groups.element_id = $id and
                         shop_cat_group_groups.similar_element_id = shop_cat_elements.element_id
                         order by shop_cat_group_groups.order_id asc") or $objResponse->alert(mysql_error());
                             
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
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_up_record(\'group\','.$row['similar_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
         if ($i == mysql_num_rows($result)) $text_elements .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else $text_elements .= '<a style="cursor:pointer;" onclick="xajax_move_down_record(\'group\','.$row['similar_element_id'].','.$id.');"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         $text_elements .= '<a style="cursor:pointer;" onclick="if(confirm(\'Вы действительно хотите удалить?\')) {xajax_delete_record(\'group\','.$row['similar_element_id'].','.$id.');}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
         $text_elements .= '</td></tr>';
         $i++;           
       }
      $text_elements .= '</table>';
    }
   else $text_elements .= '<p align="center">Нет групп</p>';

	$objResponse->assign("groups_values","innerHTML",$text_elements);
	return $objResponse;
}

function show_elements_in_group($parent_id, $element_id)
{
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
        else $text .= ' onclick="xajax_add_element('.$similar_element_id.','.$element_id.');"';
        $text .= '></td><td> &nbsp; <label for="eig'.$similar_element_id.'">'.htmlspecialchars($row['element_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
    }
  else $text .= '<p align="center">Нет товаров</p>';

  $objResponse->assign("elements","innerHTML",$text);
  return $objResponse;
}

function show_grids($id)
{
    $objResponse = new xajaxResponse();
    $text_grids = "";
    $result = mysql_query(" select
                            shop_cat_group_grids.parent_grid_id,
                            shop_cat_group_grids.grid_id,
                            shop_cat_group_grids.grid_name
                            from
                            shop_cat_group_grids, shop_cat_group_element_grids
                            where shop_cat_group_element_grids.element_id = $id and
                            shop_cat_group_element_grids.grid_id = shop_cat_group_grids.grid_id
                            order by shop_cat_group_element_grids.order_id asc");
    if (mysql_num_rows($result) > 0) {
        $i = 1; 
        $text_grids .= '<table cellspacing="0" cellpadding="0" border="0">';
        while($row = mysql_fetch_array($result)) {

            global $grid_tree;
            $str = array();
            path_to_grid($row['parent_grid_id'], $str, $grid_tree);
            $str = array_reverse($str);
            $path = '';
            foreach ($str as $value)
                $path .= $value.' -&gt; ';
                
            $text_grids .=  '<tr><td><span class="grey">'.$path.htmlspecialchars($row['grid_name']).'</span></td><td>';
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
    } else {
        $text_grids .= '<p align="center">Нет свойств</p>';
    	$objResponse->assign("grid_select_edit","innerHTML","Нет свойств");
    }

	$objResponse->assign("grid_values","innerHTML",$text_grids);
	return $objResponse;
}

function show_grids_edit($id)
{
	$objResponse = new xajaxResponse();

  $select = '<select style="width:280px;" name="grid_id_edit">
             <option value="">Выберите свойство для редактирования...</option>';
             $res = mysql_query("select
                                 shop_cat_group_grids.grid_id,
                                 shop_cat_group_grids.parent_grid_id,
                                 shop_cat_group_grids.grid_name
                                 from shop_cat_group_grids,shop_cat_group_element_grids
                                 where shop_cat_group_grids.grid_id = shop_cat_group_element_grids.grid_id and
                                 shop_cat_group_element_grids.element_id = $id order by shop_cat_group_grids.grid_name asc");
             if (mysql_num_rows($res) > 0)
             {
                while ($row = mysql_fetch_array($res)) {
                    global $grid_tree;
                    $str = array();
                    path_to_grid($row['parent_grid_id'], $str, $grid_tree);
                    $str = array_reverse($str);
                    $path = '';
                    foreach ($str as $value)
                        $path .= $value.' -&gt; ';
                    
                    $select .= '<option value="'.$row['grid_id'].'">'.$path.htmlspecialchars($row['grid_name']).'</option>';
                }
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
        $text .= '<tr><td><input type="checkbox" id="g'.$size_id.'" name="size_'.$size_id.'"';

        $res = mysql_query("select * from shop_cat_group_sizes_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");
        if (mysql_num_rows($res) > 0)
         {
           $text .= ' checked';
           $text .= ' onclick="xajax_edit_grid_size(\'delete\','.$size_id.','.$grid_id.','.$id.');"';
         }
        else $text .= ' onclick="xajax_edit_grid_size(\'add\','.$size_id.','.$grid_id.','.$id.');"';
        $text .=  '></td><td> &nbsp; <label for="g'.$size_id.'">'.htmlspecialchars($row['size_name']).'</label></td></tr>';
      }
     $text .= '</table><br/>';
   }
  else $text .= '<p align="center">Нет характеристик в свойстве</p>';

	$objResponse->assign("grid_values_edit","innerHTML",$text);
	return $objResponse;
}

function edit_grid_size($action, $size_id, $grid_id, $id)
{
  $objResponse = new xajaxResponse();

  if ($action == 'add') mysql_query("insert into shop_cat_group_sizes_availability values ($id, $grid_id, $size_id)");
  if ($action == 'delete') mysql_query("delete from shop_cat_group_sizes_availability where element_id = $id and grid_id = $grid_id and size_id = $size_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_edit_grid($grid_id,$id);");
  return $objResponse;
}


function move_up_record($record_type, $record_id, $id)
 {
  $objResponse = new xajaxResponse();

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

  if ($record_type == 'group')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_group_groups where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['similar_element_id'] == $record_id)
            {
              mysql_query("update shop_cat_group_groups set order_id = $last_order_id where element_id = $id and similar_element_id = $record_id");
              mysql_query("update shop_cat_group_groups set order_id = ".$row['order_id']." where element_id = $id and similar_element_id = $last_similar_element_id");
            }
           $last_similar_element_id = $row['similar_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_groups($id);");
   }

  if ($record_type == 'grid')
   {
     $last_grid_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_group_element_grids where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($row['grid_id'] == $record_id)
            {
              mysql_query("update shop_cat_group_element_grids set order_id = $last_order_id where element_id = $id and grid_id = $record_id");
              mysql_query("update shop_cat_group_element_grids set order_id = ".$row['order_id']." where element_id = $id and grid_id = $last_grid_id");
            }
           $last_grid_id = $row['grid_id'];
           $last_order_id = $row['order_id'];
         }
      }
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_grids($id);");
  $objResponse->assign("grid_values_edit","innerHTML","");
  return $objResponse;
 }

function move_down_record($record_type, $record_id, $id)
 {
  $objResponse = new xajaxResponse();

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

  if ($record_type == 'group')
   {
     $last_similar_element_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_group_groups where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_similar_element_id == $record_id)
            {
              mysql_query("update shop_cat_group_groups set order_id = ".$row['order_id']." where element_id = $id and similar_element_id = $record_id");
              mysql_query("update shop_cat_group_groups set order_id = $last_order_id where element_id = $id and similar_element_id = ".$row['similar_element_id']);
            }
           $last_similar_element_id = $row['similar_element_id'];
           $last_order_id = $row['order_id'];
         }
      }
     $objResponse->script("xajax_show_groups($id);");
   }

  if ($record_type == 'grid')
   {
     $last_grid_id = 0;
     $last_order_id = 0;
     $result = mysql_query("select * from shop_cat_group_element_grids where element_id = $id order by order_id asc");
     if (mysql_num_rows($result) > 0)
      {
        while ($row = mysql_fetch_array($result))
         {
           if ($last_grid_id == $record_id)
            {
              mysql_query("update shop_cat_group_element_grids set order_id = ".$row['order_id']." where element_id = $id and grid_id = $record_id");
              mysql_query("update shop_cat_group_element_grids set order_id = $last_order_id where element_id = $id and grid_id = ".$row['grid_id']);
            }
           $last_grid_id = $row['grid_id'];
           $last_order_id = $row['order_id'];
         }
      }
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_grids($id);");
  $objResponse->assign("grid_values_edit","innerHTML","");
  return $objResponse;
 }

function delete_record($record_type, $record_id, $id)
{
  $objResponse = new xajaxResponse();

  if ($record_type == 'element')
   {
     mysql_query("delete from shop_cat_element_elements where element_id = $id and similar_element_id = $record_id");
     $objResponse->script("xajax_show_elements($id);");
   }

  if ($record_type == 'group')
   {
     mysql_query("delete from shop_cat_group_groups where element_id = $id and similar_element_id = $record_id");
     $objResponse->script("xajax_show_groups($id);");
   }

  if ($record_type == 'grid')
   {
     mysql_query("delete from shop_cat_group_element_grids where element_id = $id and grid_id = $record_id");
     mysql_query("delete from shop_cat_group_sizes_availability where element_id = $id and grid_id = $record_id");
     mysql_query("delete from shop_cat_group_sizes_elements_availability where element_id = $id and grid_id = $record_id");
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
  $objResponse->script("xajax_show_grids($id);");
  $objResponse->assign("grid_values_edit","innerHTML","");
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

$xajax->registerFunction("show_elements");
$xajax->registerFunction("show_groups");
$xajax->registerFunction("show_elements_in_group");
$xajax->registerFunction("show_grids");
$xajax->registerFunction("show_grids_edit");

$xajax->registerFunction("add_element");
$xajax->registerFunction("add_group");
$xajax->registerFunction("add_group_to_groups");
$xajax->registerFunction("edit_grid");
$xajax->registerFunction("edit_grid_size");
$xajax->registerFunction("add_grid");

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
                        *
                        from shop_cat_elements
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_object($result);

 if ($row->img_path1 || $row->img_path2 || $row->img_path3) echo '<p>';
 if ($row->img_path1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path1).'" border="0"> &nbsp;';
 if ($row->img_path2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path2).'" border="0"> &nbsp;';
 if ($row->img_path3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->img_path3).'" border="0">';
 if ($row->img_path1 || $row->img_path2 || $row->img_path3) echo '</p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_group.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_group.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_group_files.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_group_files.php?id='.$element_id, 'Файлы');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_group_on_map.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_group_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('group_error', 'Группа не может ссылаться на себя');
   $message->get_message($_GET['message']);
 }

   echo '<form name="form" enctype="multipart/form-data" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_name" value="'.htmlspecialchars($row->element_name).'" maxlength="255"/></td>
      <td><button type="button" onclick="xajax_text2url(this.form.element_name.value)">► URL</button></td>
    </tr>
    <tr>
      <td>URL <sup class="red">*</sup></td>
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
      <td>Единица измерения</td>
      <td><select name="unit_id" style="width:280px;">
            <option value="0">---НЕТ---</option>';
            
    $res = mysql_query("select * from shop_units_of_measure order by unit_name asc");
    if (mysql_num_rows($res) > 0)
     {
       while ($r = mysql_fetch_array($res))
        {
          echo '<option value="'.$r['unit_id'].'" '.(($row->unit_id == $r['unit_id']) ? ' selected' : '').'>'.htmlspecialchars($r['unit_name']).(($r['unit_descr']) ? ' &nbsp; ('.htmlspecialchars($r['unit_descr']).')' : '').'</option>';
        }
     }
          echo '</select>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Расположение группы <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0"'; if ($row->parent_id == 0) echo ' selected'; echo '>---Корень каталога---</option>
            '.show_select(0, '', $row->parent_id, $shop_tree, $element_id); global $options; $options = ''; echo '
          </select>
      </td>
      <td>&nbsp;</td>
    </tr>
   <tr>
     <td>C товарами этой группой также покупают</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="elements_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
          <td>
            <select style="width:280px;" name="parent_id_elements" onchange="xajax_show_elements_in_group(this.form.parent_id_elements.options[this.form.parent_id_elements.selectedIndex].value, '.$element_id.');">
             <option value="">Выберите группу...</option>';

             echo show_select(0, '', 0, $shop_tree);
             global $options; $options = '';
             
             echo '</select><button type="button" title="Добавить группу" onclick="xajax_add_group(this.form.parent_id_elements.options[this.form.parent_id_elements.selectedIndex].value, '.$element_id.');">Добавить группу</button>
             <div id="element_info">&nbsp;</div>
             <div id="elements"></div>
          </td>
        </tr>
       </table>
     </td>
      <td>&nbsp;</td>
   </tr>
   <tr>
     <td>Эта группа также входит в другие группы</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="groups_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
          <td>
            <select style="width:280px;" name="parent_id_groups">
             <option value="">Выберите группу...</option>';

             echo show_select(0, '', 0, $shop_tree);
             global $options; $options = '';

             echo '</select><button type="button" title="Добавить группу" onclick="xajax_add_group_to_groups(this.form.parent_id_groups.options[this.form.parent_id_groups.selectedIndex].value, '.$element_id.');">Добавить группу</button>
          </td>
        </tr>
       </table>
     </td>
      <td>&nbsp;</td>
   </tr>';

//Сортировка
        echo '<tr><td>Порядок сортировки в текущей группе</td><td><input style="width:280px" type="text" name="order_id" value="'.htmlspecialchars($row->order_id).'" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td>&nbsp;</td></tr>'; 
    
//Свойства группы    
     $res = mysql_query("select * from shop_cat_group_grids");
     if (mysql_num_rows($res) > 0)
      {
	echo '<tr>
     <td>Свойства группы</td>
     <td>
       <table cellspacing="0" cellpadding="0" width="100%" border="0">
        <tr><td>&nbsp;</td></tr>
        <tr><td><div id="grid_values"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td>
        
            <select style="width:280px;" name="grid_id">
            <option value="">Выберите свойство...</option>';
        
             global $options; $options = '';
             echo show_grid_select(0, '', $grid_tree);
        
        echo '</select><button type="button" title="Добавить свойство" onclick="xajax_add_grid(this.form.grid_id.options[this.form.grid_id.selectedIndex].value, '.$element_id.');">Добавить</button>
        
        </td></tr>
       </table>
     </td>
     <td>&nbsp;</td>
   </tr>';
   $res_s = mysql_query("select * from shop_cat_group_sizes");
   if (mysql_num_rows($res_s) > 0)
   {
     echo '<tr>
     <td>Наличие характеристик<br/>
     в свойствах данной группы</td>
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
     setTimeout("xajax_show_elements('.$element_id.')", begin_delay + delay);
     setTimeout("xajax_show_grids('.$element_id.')", begin_delay + delay*2);
     setTimeout("xajax_show_groups('.$element_id.')", begin_delay + delay*3);
   </script>';
  
  echo '<h2>Добавить карточку к товарам группы</h2>';

if (isset($_GET['message']))
 {
   $message2 = new Message;
   $message2->copy_message('formvalues', 'formvalues2');
   $message2->copy_message('db', 'db2');
   $message2->copy_message('duplicate', 'duplicate2');
   $message2->get_message($_GET['error']);
 }

 echo '
   <form name="form" action="?id='.$element_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Карточки описаний товара</td>
      <td>';
      
      $result = mysql_query("select * from shop_cat_cards order by card_name asc");
      if (mysql_num_rows($result) > 0)
       {
         echo '<select style="width:280px;" name="card_id">
                <option value="">Выберите карточку...</option>';
         while ($row = mysql_fetch_array($result))
           echo '<option value="'.$row['card_id'].'">'.htmlspecialchars($row['card_name']).'</option>';
         echo '</select>';
       }
      else echo 'Нет карточек';
  echo '</td>

    </tr>
    <tr>
      <td>Добавлять во все подгруппы</td>
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
   <button type="SUBMIT">Добавить</button>
  </form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>