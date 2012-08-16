<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['card_name']) &&
   isset($_POST['card_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['card_name'])=='')
     {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $card_id = (int)$_GET['id'];
   $card_name = mysql_real_escape_string(trim($_POST['card_name']));
   $card_descr = mysql_real_escape_string(trim($_POST['card_descr']));

   $result = mysql_query("select * from shop_cat_cards where card_name = '".stripslashes($card_name)."' and card_id!=$card_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id&message=duplicate"); exit();}

   //Обновляем содержимое...
   $result = mysql_query("update shop_cat_cards set card_name='$card_name', card_descr='$card_descr' where card_id=$card_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id&message=db"); exit();}
   
   foreach($_POST['order'] as $option_id => $order)
     mysql_query("update shop_cat_card_options set order_id = $order where card_id = $card_id and option_id = $option_id");

   mysql_query("update shop_cat_card_options set filter = 0 where card_id = $card_id");
   foreach($_POST['filter'] as $option_id => $filter)
     mysql_query("update shop_cat_card_options set filter = $filter where card_id = $card_id and option_id = $option_id");
    
   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['option_id']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('add'))
  {
    $card_id = (int)$_GET['id'];
    $option_id = $_POST['option_id'];
    if (trim($_POST['option_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id&message=formvalues2");exit();}

    $result = mysql_query("select * from shop_cat_card_options where card_id = $card_id and option_id = $option_id");
    if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id&message=duplicate2");exit();}

    $result = mysql_query("insert into shop_cat_card_options (card_id, option_id) values ($card_id, $option_id)");
    if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id&message=db2");exit();}
 
   // перенумеровываем
   $result = mysql_query("select * from shop_cat_card_options where card_id = $card_id order by order_id asc");
   if (mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['option_id'];
         mysql_query("update shop_cat_card_options set order_id=$i where card_id = $card_id and option_id=$option_id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['option_id']) &&
   isset($_GET['id']) &&
   isset($_GET['action']) && $_GET['action'] == 'delete')
 {
 if ($user->check_user_rules('delete'))
  {
    $card_id = (int)$_GET['id'];
    $option_id = $_GET['option_id'];
    mysql_query("delete from shop_cat_card_options where card_id = $card_id and option_id = $option_id");
    mysql_query("delete from shop_cat_option_values where card_id = $card_id and option_id = $option_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$card_id"); exit();
  } else $user->no_rules('delete');
 }

if (isset($_GET['action']) && $_GET['action']!=='' &&
    isset($_GET['option_id']) && $_GET['option_id']!=='' &&
    isset($_GET['id']) && $_GET['id']!=='')
 {
   $card_id = (int)$_GET['id'];
   $option_id = $_GET['option_id'];
   $action = $_GET['action'];

   if ($action == 'up')
   {
    if ($user->check_user_rules('action'))
     {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from shop_cat_card_options where card_id = $card_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['option_id'];
           mysql_query("update shop_cat_card_options set order_id = $order where card_id = $card_id and option_id = $vid");
           $values[$order] = $vid;
           if ($vid == $option_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_card_options set order_id = '.($old_order-1).' where card_id = '.$card_id.' and option_id = '.$values[$old_order];
        //для предыдущего
        $q2 = 'update shop_cat_card_options set order_id = '.$old_order.' where card_id = '.$card_id.' and option_id = '.$values[$old_order-1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  $_SESSION['smart_tools_refresh'] = 'enable';

      }
     } else $user->no_rules('action');
   }
  if ($action == 'down')
   {
    if ($user->check_user_rules('action'))
      {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     $result = mysql_query("select * from shop_cat_card_options where card_id = $card_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['option_id'];
           mysql_query("update shop_cat_card_options set order_id = $order where card_id = $card_id and option_id = $vid");
           $values[$order] = $vid;
           if ($vid == $option_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update shop_cat_card_options set order_id = '.($old_order+1).' where card_id = '.$card_id.' and option_id = '.$values[$old_order];
        //для следующего
        $q2 = 'update shop_cat_card_options set order_id = '.$old_order.' where card_id = '.$card_id.' and option_id = '.$values[$old_order+1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  $_SESSION['smart_tools_refresh'] = 'enable';

     }
    } else $user->no_rules('action');
   }

  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
        mysql_query("delete from shop_cat_card_options where card_id = $card_id and option_id = $option_id");
        mysql_query("delete from shop_cat_option_values where card_id = $card_id and option_id = $option_id");
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
 $card_id = (int)$_GET['id'];
 $result = mysql_query("select * from shop_cat_cards where card_id = $card_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $card_name = $row['card_name'];
   $card_descr = $row['card_descr'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$card_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="card_name" value="'.htmlspecialchars($card_name).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="card_descr" value="'.htmlspecialchars($card_descr).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Список характеристик</td>
      <td>';
      
      $options = array();
      $result = mysql_query("select
                             shop_cat_options.*,
                             shop_cat_card_options.order_id,
                             shop_cat_card_options.filter
                             from
                             shop_cat_options, shop_cat_card_options
                             where shop_cat_card_options.card_id = $card_id and
                             shop_cat_card_options.option_id = shop_cat_options.option_id
                             order by shop_cat_card_options.order_id asc");
   $i = 1;
   if (mysql_num_rows($result) > 0)
    {
      echo '<table cellspacing="0" cellpadding="2" border="0">
             <tr class="header">
               <td>Навание</td>
               <td>Является фильтром<br />в группе товара</td>
               <td>Порядок</td>
               <td>Удалить</td>
             </tr>';
      while ($row = mysql_fetch_array($result))
       {
         $options[] = $row['option_id'];
         echo '<tr>
                 <td nowrap><span class="grey">'.htmlspecialchars($row['option_name']).'</span></td>
                 <td align="center"><input type="checkbox" name="filter['.$row['option_id'].']" value="1"'.((int)$row['filter'] == 1 ? ' checked' : '').'></td>
                 <td nowrap><input type="text" name="order['.$row['option_id'].']" value="'.$row['order_id'].'" style="width: 30px;">';

         //если элемент первый на определенном уровне, блокируем стрелку "вверх"
         if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else echo '<a href="?id='.$card_id.'&option_id='.$row['option_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';

         if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else echo '<a href="?id='.$card_id.'&option_id='.$row['option_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
         
         echo '</td><td align="center">';

         echo '<a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$card_id&option_id=".$row['option_id']."&action=delete';}";
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
  
$result = mysql_query("select * from shop_cat_options ".(count($options) ? "where option_id not in (".implode(',',$options).")" : '')." order by option_name asc");
if (mysql_num_rows($result) > 0)
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

echo '<form action="?id='.$card_id.'" method="post">
   <table cellpadding="0" cellspacing="0" border="0"><tr><td>
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Характеристика</td>
      <td>';

   echo '<select style="width:280px" name="option_id">
         <option value="">Выберите характеристику...</option>';
   while($row = mysql_fetch_array($result))
    {
      echo '<option value='.$row['option_id'].'>'.htmlspecialchars($row['option_name']).' &nbsp; '.htmlspecialchars($row['option_descr']).'</option>';
    }
   echo'</select>';
   echo'</td>
    </tr>
   </table></td><td> &nbsp; <button type="SUBMIT">Добавить</button></td></tr></table>
  </form>';
 }
      
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>