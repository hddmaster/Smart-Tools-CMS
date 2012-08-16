<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['order_username']) &&
    isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
/*
   if (trim($_POST['order_username'])=='' ||
       trim($_POST['order_phone'])=='' ||
       trim($_POST['order_address'])=='' ||
       trim($_POST['delivery_date'])=='' ||
       trim($_POST['hour1'])=='' ||
       trim($_POST['hour2'])=='' ||
       trim($_POST['delivery_date2'])=='' ||
       trim($_POST['hour21'])=='' ||
       trim($_POST['hour22'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}
*/
   $order_id = (int)$_GET['id'];
   
   mysql_query("update shop_order_values set grid = '".serialize(array())."' where order_id = $order_id");
   if (isset($_POST['grid']))
    {
      foreach ($_POST['grid'] as $value_id => $grid)
        mysql_query("update shop_order_values set grid = '".serialize($grid)."' where value_id = $value_id");
    }   

   if (isset($_POST['amount']))
    {
      foreach ($_POST['amount'] as $value_id => $amount)
       {
         if (intval($amount) <= 0) mysql_query("delete from shop_order_values where value_id = $value_id");
         else mysql_query("update shop_order_values set amount = ".intval($amount)." where value_id = $value_id");
       }
    }

   if (isset($_POST['comment']))
    {
      foreach ($_POST['comment'] as $value_id => $comment)
         mysql_query("update shop_order_values set comment = '".trim($comment)."' where value_id = $value_id");
    }

   $order_username = trim($_POST['order_username']);
   $order_phone = trim($_POST['order_phone']);
   $order_email = trim($_POST['order_email']);
   $order_address = trim($_POST['order_address']);
   $delivery_date = substr($_POST['delivery_date'],6,4).substr($_POST['delivery_date'],3,2).substr($_POST['delivery_date'],0,2);
   $delivery_hour1 = $_POST['hour1'];
   
   $delivery_hour2 = $_POST['hour2'];
   $delivery_date2 = substr($_POST['delivery_date2'],6,4).substr($_POST['delivery_date2'],3,2).substr($_POST['delivery_date2'],0,2);
   $delivery_hour21 = $_POST['hour21'];
   $delivery_hour22 = $_POST['hour22'];
   $description_hidden = trim($_POST['description_hidden']);
   $order_comment = trim($_POST['order_comment']);
   $delivery_id = $_POST['delivery_id'];
   $courier_id = $_POST['courier_id'];

   //перерасчет стоимости заказа
   $price = 0;
   $result = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         $price += $row['price'] * $row['amount'];
       }
    }

   //Обновляем содержимое...
   $result = mysql_query("update shop_orders set order_username = '$order_username',
                                                 order_phone = '$order_phone',
                                                 order_email = '$order_email',
                                                 order_address = '$order_address',
                                                 delivery_date = '$delivery_date',
                                                 delivery_hour1 = '$delivery_hour1',
                                                 delivery_hour2 = '$delivery_hour2',
                                                 delivery_date2 = '$delivery_date2',
                                                 delivery_hour21 = '$delivery_hour21',
                                                 delivery_hour22 = '$delivery_hour22',
                                                 description_hidden = '$description_hidden',
                                                 order_comment = '$order_comment',
                                                 delivery_id = $delivery_id,
                                                 courier_id = $courier_id,
                                                 price = $price
                                                 where order_id=$order_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=db"); exit();}

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['element_id']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('add'))
  {
    $order_id = (int)$_GET['id'];
    $element_id = $_POST['element_id'];
    if (trim($_POST['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=formvalues2");exit();}

    $result = mysql_query("select * from shop_order_values where order_id = $order_id and element_id = $element_id");
    if (mysql_num_rows($result) > 0) // товар уже есть в заказе
     {
       $row = mysql_fetch_array($result);
       $amount = $row['amount'] + 1;
       
       mysql_query("update shop_order_values set amount = $amount where order_id = $order_id and element_id = $element_id");

     }
    else // новый товар в заказе
     {
       $result = mysql_query("select * from shop_cat_elements where element_id = $element_id");
       if (mysql_num_rows($result) > 0)
        {
          $row = mysql_fetch_array($result);
          $element_name = $row['element_name'];
          $store_name = $row['store_name'];
          $producer_store_name = $row['producer_store_name'];
          $price = $row['price2'];
          
          mysql_query("insert into shop_order_values (order_id, element_id, store_name, producer_store_name, element_name, amount, price)
                                                     values
                                                     ($order_id, $element_id, '".addslashes($store_name)."', '".addslashes($producer_store_name)."', '".addslashes($element_name)."', 1, $price)");
        }
     }

   //перерасчет стоимости заказа
   $price = 0;
   $result = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         $price += $row['price'] * $row['amount'];
       }
    }
   //Обновляем содержимое...
   $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id"); exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && $_GET['action']!=='' &&
    isset($_GET['value_id']) && $_GET['value_id']!=='' &&
    isset($_GET['id']) && $_GET['id']!=='')
 {
   $order_id = (int)$_GET['id'];
   $value_id = $_GET['value_id'];
   $action = $_GET['action'];

  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
        mysql_query("delete from shop_order_values where order_id = $order_id and value_id = $value_id");
   $price = 0;
   $result = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         $price += $row['price'] * $row['amount'];
       }
    }
   //Обновляем содержимое...
   $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

   //перерасчет стоимости заказа
   $price = 0;
   $result = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         $price += $row['price'] * $row['amount'];
       }
    }
   //Обновляем содержимое...
   $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  $_SESSION['smart_tools_refresh'] = 'enable';

      }
     else $user->no_rules('delete');
   }

  }

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id)
{
  $objResponse = new xajaxResponse();

  $select_elements = '<select name="element_id" style="width:280px;" size="6">';
  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
     while ($row = mysql_fetch_array($result))
       $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].', art:'.htmlspecialchars($row['store_name']).')</option>';
   }
  else $select_elements .= '<option value="">Нет товаров</option>';
  $select_elements .= '</select>';

  $objResponse->assign("elements","innerHTML",$select_elements);
  return $objResponse;
}

$xajax->registerFunction("show_elements");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы', 1);
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/shop_delivery.php')) $tabs2->add_tab('/admin/shop_delivery.php', 'Виды доставки');
if ($user->check_user_rules('view','/admin/shop_order_status.php')) $tabs2->add_tab('/admin/shop_order_status.php', 'Статусы заказов');
if ($user->check_user_rules('view','/admin/shop_couriers.php')) $tabs2->add_tab('/admin/shop_couriers.php', 'Курьеры');
if ($user->check_user_rules('view','/admin/shop_order_statistic.php')) $tabs2->add_tab('/admin/shop_order_statistic.php', 'Статистика');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '')
  {
    global $options;
    $result = mysql_query("SELECT * FROM shop_cat_elements where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['element_id'].'"';
          if ($parent_id_added == $row['element_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div>
        <table cellspacing="0" cellpadding="4">
	 <tr>
	   <td><img src="/admin/images/icons/plus.png" alt=""></td>
	   <td><h2 class="nomargins">Добавить заказ</h2></td>
	 </tr>
	</table>   
       </div><div>&nbsp;</div>';

  echo ' <form action="" method="post">
         <table cellpadding="0" cellspacing="0"><tr><td><h3 class="nomargins">Добавить товар</h3></td><td style="padding-left: 10px;">
         <select name="parent_id" style="width:280px;" onchange="xajax_show_elements(this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень галереи---</option>'.
         show_select()
         .'</select>
         <div id="elements"></div>
         </td>
   
       <td style="padding-left: 10px;"><button type="SUBMIT">Добавить</button></td>
   
      </tr></table>
      </form><div>&nbsp;</div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form" width="100%">
    <tr>
      <td>Ф.И.О. <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="order_username" value="" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Контактный телефон <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="order_phone" value="'.htmlspecialchars($row->order_phone).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>e-mail</td>
      <td>
       <input style="width:280px" type="text" name="order_email" value="'.htmlspecialchars($row->order_email).'" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Адрес доставки <sup class="red">*</sup></td>
      <td>
       <textarea style="width: 100%;" rows="3" name="order_address">'.htmlspecialchars($row->order_address).'</textarea>
      </td>
    </tr>
    <tr>
      <td>Дата доставки клиентская</td>
      <td>
      
    <table cellspacing="0" cellpadding="0" border="0">
    <tr><td>';
?>
    <script>
      LSCalendars["delivery_date"]=new LSCalendar();
      LSCalendars["delivery_date"].SetFormat("dd.mm.yyyy");
      LSCalendars["delivery_date"].SetDate("<?=$row->delivery_date_f?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('delivery_date', event); return false;" style="width: 65px;" value="<?=$row->delivery_date_f?>" name="delivery_date"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('delivery_date', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="delivery_datePtr" style="width: 1px; height: 1px;"></div>
<?
echo '</td>
      <td>';
       echo '&nbsp;&nbsp;&nbsp;с
       <select name="hour1">';
        $i = 00.00;
        while ($i < 24.00)
         {
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour1) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.30;
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour1) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
        $i += 0.70;
         }
       echo '</select>
      </td>
      <td>
        &nbsp;&nbsp;&nbsp;по
       <select name="hour2">';
        $i = 00.00;
        while ($i < 24.00)
         {
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour2) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.30;
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour2) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.70;
         }
       echo '</select>
      </td></tr></table>
     </td>
    </tr>
    <tr>
      <td>Дата доставки внутреняя</td>
      <td>
      
    <table cellspacing="0" cellpadding="0" border="0">
    <tr><td>';
?>
    <script>
      LSCalendars["delivery_date2"]=new LSCalendar();
      LSCalendars["delivery_date2"].SetFormat("dd.mm.yyyy");
      LSCalendars["delivery_date2"].SetDate("<?=$row->delivery_date2_f?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('delivery_date2', event); return false;" style="width: 65px;" value="<?=$row->delivery_date2_f?>" name="delivery_date2"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('delivery_date2', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="delivery_date2Ptr" style="width: 1px; height: 1px;"></div>
<?
echo '</td>
      <td>
       &nbsp;&nbsp;&nbsp;с
       <select name="hour21">';
        $i = 00.00;
        while ($i < 24.00)
         {
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour21) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.30;
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour21) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
        $i += 0.70;
         }
       echo '</select>
      </td>
      <td>
        &nbsp;&nbsp;&nbsp;по
       <select name="hour22">';
        $i = 00.00;
        while ($i < 24.00)
         {
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour22) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.30;
           echo '<option value="'.sprintf("%05.2f", $i).'"';
           if ($i == $row->delivery_hour22) echo ' selected';
           echo '>'.sprintf("%05.2f", $i).'</option>';
           $i += 0.70;
         }
       echo '</select>
      </td></tr></table>
     </td>
    </tr>
    <tr>
      <td>Доставка</td>
      <td>
        <select name="delivery_id" style="width: 280px;"><option value="0">Выберите тип доставки...</option>';
        $res = mysql_query("select * from shop_delivery order by delivery_name asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->delivery_id.'"'.(($row->delivery_id == $r->delivery_id) ? ' selected' : '').'>'.htmlspecialchars($r->delivery_name).'</option>';
         }
        echo '</select>
      </td>
    </tr>
    <tr>
      <td>Курьер</td>
      <td>
        <select name="courier_id" style="width: 280px;"><option value="0">Выберите курьера...</option>';
        $res = mysql_query("select * from shop_couriers order by courier_name asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->courier_id.'"'.(($row->courier_id == $r->courier_id) ? ' selected' : '').'>'.htmlspecialchars($r->courier_name).'</option>';
         }
        echo '</select>
      </td>
    </tr>
    <tr>
      <td>Товары</td>
      <td width="100%">';

   $result_values = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (@mysql_num_rows($result_values) > 0)
    {
      echo '<table cellspacing="0" cellpadding="4" border="0" width="100%">
             <tr align="center" class="header">
               <td width="50%">Товар</td>
               <td width="50%">Свойства</td>
               <td>Примечание</td>
               <td>Количество</td>
               <td>&nbsp;</td>
             </tr>';
      while ($row_values = mysql_fetch_array($result_values))
       {
         echo '<tr>
               <td style="border-bottom: #ccc 1px dotted;"><h3 class="nomargins">'.htmlspecialchars($row_values['element_name']).'</h3><div class="small">ID: '.$row_values['element_id'].', ART: '.htmlspecialchars($row_values['store_name']).'</div></td>
               <td style="border-bottom: #ccc 1px dotted;">';

         $grid = unserialize($row_values['grid']);
         $res = mysql_query("select
                             shop_cat_grids.grid_id,
                             shop_cat_grids.grid_name
                             from
                             shop_cat_grids, shop_cat_element_grids
                             where shop_cat_element_grids.element_id = {$row_values['element_id']} and
                             shop_cat_element_grids.grid_id = shop_cat_grids.grid_id
                             order by shop_cat_element_grids.order_id asc");
         if (mysql_num_rows($res) > 0)
          {
            while ($r = mysql_fetch_array($res))
             {
               echo '<div style="clear: both;"><strong>'.$r['grid_name'].'</strong></div>';
               $resg = mysql_query("select
                                    shop_cat_sizes.size_id,
                                    shop_cat_sizes.size_name
                                    from
                                    shop_cat_sizes, shop_cat_grid_sizes
                                    where shop_cat_grid_sizes.grid_id = {$r['grid_id']} and
                                    shop_cat_grid_sizes.size_id = shop_cat_sizes.size_id
                                    order by shop_cat_grid_sizes.order_id asc");
               if (mysql_num_rows($resg) > 0)
                {
                  while ($rg = mysql_fetch_array($resg))
                   { 
                     $resa = mysql_query("select * from shop_cat_sizes_availability where element_id = {$row_values['element_id']} and grid_id = {$r['grid_id']} and size_id = {$rg['size_id']}");
                     if (mysql_num_rows($resa) > 0)
                      {
                        echo '<div style="float: left; padding: 2px 8px 2px 0px;"><table cellspacing="0" cellpadding="0">';
                        echo '<tr><td><input type="checkbox" name="grid['.$row_values['value_id'].']['.$r['grid_id'].'][]" value="'.$rg['size_id'].'"';
                        if (array_key_exists($r['grid_id'], $grid) && in_array($rg['size_id'], $grid[$r['grid_id']])) echo ' checked';
                        echo '></td><td>'.htmlspecialchars($rg['size_name']).'</td></tr>';
                        echo '</table></div>';
                      }
                   }
                }

             }
          }
         echo '</td>
               <td style="border-bottom: #ccc 1px dotted;"><input type="text" style="width: 150px" name="comment['.$row_values['value_id'].']" value="'.$row_values['comment'].'"></td>
               <td style="border-bottom: #ccc 1px dotted;"><input type="text" style="width: 30px" name="amount['.$row_values['value_id'].']" value="'.$row_values['amount'].'"></td>';
         echo '<td nowrap style="border-bottom: #ccc 1px dotted;"><a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$order_id&value_id=".$row_values['value_id']."&action=delete';}";
         echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td></tr>';
       }
      echo '</table>';
    }
   else
     echo 'Нет товаров в заказе';

  $shop_currency = 'руб.';
  $shop_currency = $user->get_cms_option('shop_currency');

 echo ' </td></tr>
    <tr>
      <td>Итого, '.$shop_currency.'</td>
      <td>
       <input style="width:280px" type="text" name="price" value="'.$row->price.'" maxlength="255" disabled>
      </td>
    </tr>
    <tr>
      <td>Примечание покупателя</td>
      <td>
       <textarea style="width: 100%;" rows="3" name="order_comment"></textarea>
      </td>
    </tr>
    <tr>
      <td>Примечание менеджера</td>
      <td>
       <textarea style="width: 100%;" rows="3" name="description_hidden"></textarea>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form>';
 
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>