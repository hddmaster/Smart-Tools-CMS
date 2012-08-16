<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['grid_name']) &&
   isset($_POST['grid_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['grid_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $grid_id = (int)$_GET['id'];
   $grid_name = $_POST['grid_name'];
   $grid_descr = $_POST['grid_descr'];

   $result = mysql_query("select * from shop_cat_grids where grid_name = '".stripslashes($grid_name)."' and grid_id!=$grid_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_cat_grids set grid_name='$grid_name', grid_descr='$grid_descr' where grid_id=$grid_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['size_id']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('add'))
  {
    $grid_id = (int)$_GET['id'];
    $size_id = $_POST['size_id'];
    if (trim($_POST['size_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=formvalues2");exit();}

    $result = mysql_query("select * from shop_cat_grid_sizes where grid_id = $grid_id and size_id = $size_id");
    if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id&message=duplicate2");exit();}

    mysql_query("insert into shop_cat_grid_sizes values ($grid_id, $size_id, 0)");

   // перенумеровываем
   $result = mysql_query("select * from shop_cat_grid_sizes where grid_id = $grid_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['size_id'];
         mysql_query("update shop_cat_grid_sizes set order_id=$i where grid_id = $grid_id and size_id=$id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$grid_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && $_GET['action']!=='' &&
    isset($_GET['size_id']) && $_GET['size_id']!=='' &&
    isset($_GET['id']) && $_GET['id']!=='')
 {
   $grid_id = (int)$_GET['id'];
   $size_id = $_GET['size_id'];
   $action = $_GET['action'];

   if ($action == 'up')
   {
    if ($user->check_user_rules('action'))
     {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from shop_cat_grid_sizes where grid_id = $grid_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['size_id'];
           mysql_query("update shop_cat_grid_sizes set order_id = $order where grid_id = $grid_id and size_id = $vid");
           $values[$order] = $vid;
           if ($vid == $size_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_grid_sizes set order_id = '.($old_order-1).' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order];
        //для предыдущего
        $q2 = 'update shop_cat_grid_sizes set order_id = '.$old_order.' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order-1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      }
     } else $user->no_rules('action');
   }
  if ($action == 'down')
   {
    if ($user->check_user_rules('action'))
      {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from shop_cat_grid_sizes where grid_id = $grid_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['size_id'];
           mysql_query("update shop_cat_grid_sizes set order_id = $order where grid_id = $grid_id and size_id = $vid");
           $values[$order] = $vid;
           if ($vid == $size_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_grid_sizes set order_id = '.($old_order+1).' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order];
        //для следующего
        $q2 = 'update shop_cat_grid_sizes set order_id = '.$old_order.' where grid_id = '.$grid_id.' and size_id = '.$values[$old_order+1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

     }
    } else $user->no_rules('action');
   }

  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
        mysql_query("delete from shop_cat_grid_sizes where grid_id = $grid_id and size_id = $size_id");
        mysql_query("delete from shop_cat_sizes_availability where grid_id = $grid_id and size_id = $size_id");

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
        $_SESSION['smart_tools_refresh'] = 'enable';
     }
     else $user->no_rules('delete');
   }

  }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
 $grid_id = (int)$_GET['id'];
 $result = mysql_query("select * from shop_cat_grids where grid_id = $grid_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $grid_name = $row['grid_name'];
   $grid_descr = $row['grid_descr'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$grid_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="grid_name" value="'.htmlspecialchars($grid_name).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="grid_descr" value="'.htmlspecialchars($grid_descr).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Список характеристик</td>
      <td>';

      $result = mysql_query("select
                             shop_cat_sizes.size_id,
                             shop_cat_sizes.size_name
                             from
                             shop_cat_sizes, shop_cat_grid_sizes
                             where shop_cat_grid_sizes.grid_id = $grid_id and
                             shop_cat_grid_sizes.size_id = shop_cat_sizes.size_id
                             order by shop_cat_grid_sizes.order_id asc");
   $i = 1;
   if (@mysql_num_rows($result) > 0)
    {
      echo '<table cellspacing="0" cellpadding="0" border="0">';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr><td nowrap><span class="grey">'.htmlspecialchars($row['size_name']).'</span> &nbsp; </td>';

         //если элемент первый на определенном уровне, блокируем стрелку "вверх"
         echo '<td nowrap>';
         if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else echo '<a href="?id='.$grid_id.'&size_id='.$row['size_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';

         if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else echo '<a href="?id='.$grid_id.'&size_id='.$row['size_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';

         echo '<a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$grid_id&size_id=".$row['size_id']."&action=delete';}";
         echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';

         $i++;
       }
      echo '</table>';
    }
   else
     echo 'Нет характеристик';

 echo ' </td></tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
 
 
$res = mysql_query("select * from shop_cat_sizes");
if (mysql_num_rows($res) > 0)
{
  echo '<h2>Добавить характеристику</h2>';

if (isset($_GET['message']))
 {
   $message2 = new Message;
   $message2->copy_message('formvalues', 'formvalues2');
   $message2->copy_message('db', 'db2');
   $message2->copy_message('duplicate', 'duplicate2');
   $message2->get_message($_GET['error']);
 }

echo '  <form action="?id='.$grid_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>';

$result = mysql_query("select * from shop_cat_sizes order by size_name asc");
if (mysql_num_rows($result) > 0)
 {
   echo '<select style="width:280px" name="size_id">
         <option value="">Выберите характеристику...</option>';
   while($row = mysql_fetch_array($result))
    {
      echo '<option value='.$row['size_id'].'>'.htmlspecialchars($row['size_name']).' &nbsp; '.htmlspecialchars($row['size_descr']).'</option>';
    }
   echo'</select>';
 }
      
echo'</td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form>';
}

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>