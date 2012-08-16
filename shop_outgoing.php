<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['amount']))
{
  if ($user->check_user_rules('add'))
   {
  if (trim($_POST['amount'])=='' ||
      trim($_POST['price'])=='' ||
      trim($_POST['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $element_id = $_POST['element_id'];
  $amount = intval($_POST['amount']);

  //проверка на допустимое число
  if ($amount <= 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=not_null2"); exit();}
  $result = mysql_query("select amount from shop_cat_elements where element_id = $element_id");
  if (mysql_num_rows($result) > 0)
   {
     $row = mysql_fetch_array($result);
     $old_amount = $row['amount'];
     if ($amount > $old_amount) {Header("Location: ".$_SERVER['PHP_SELF']."?message=invalid_amount"); exit();}
   }

  //цена по которой продали
  $price = intval($_POST['price']);

  //проверка на наличие этого элемента на временном складе
  $result = mysql_query("select * from shop_outgoing_tmp where element_id = $element_id");
  if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}
  else
   {
     //Добавляем новый товар на временный склад...
     $result = mysql_query("insert into shop_outgoing_tmp values (null , $element_id, $amount, $price)");
     if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
   }

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
}


if (isset($_GET['action']))
 {
   $action = $_GET['action'];

   if ($action == 'del' && isset($_GET['id']))
    {
      $id = (int)$_GET['id'];

      if ($user->check_user_rules('delete')) mysql_query("delete from shop_outgoing_tmp where id = $id");
      else $user->no_rules('delete');
    }

   if ($action == 'register')
    {
      if ($user->check_user_rules('action'))
       {
         if (trim($_POST['place_id']) == '' || trim($_POST['discount']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues2"); exit();}

         $result = mysql_query("select * from shop_outgoing_tmp");
         if (mysql_num_rows($result) > 0)
          {
            //сохранение приходной накладной
            $date = date("YmdHis");
            $place_id = $_POST['place_id'];
            $discount = $_POST['discount'];
            mysql_query("insert into shop_outgoing values (null, '$date', $place_id, $discount)");
            $outgoing_id = mysql_insert_id();

            while ($row = mysql_fetch_array($result))
             {
               $element_id = $row['element_id'];
               $amount = $row['amount'];
               $price = $row['price'];

               $res = mysql_query("select * from shop_cat_elements where element_id = $element_id");
               $r = mysql_fetch_array($res);
               $old_amount = $r['amount'];
               $new_amount =  $old_amount - $amount;
               mysql_query("update shop_cat_elements set amount = $new_amount where element_id = $element_id");

               //вносим данные в архив приходных накладных
               $res = mysql_query("select
                                   *
                                   from shop_cat_elements
                                   where
                                   element_id = $element_id");
               $r = mysql_fetch_array($res);
               $element_name = $r['element_name'];
               $store_name = $r['store_name'];
               $producer_store_name = $r['producer_store_name'];

               mysql_query("insert into shop_outgoing_data values (null, $outgoing_id, '$element_name', '$store_name', '$producer_store_name', $amount, $price) ");
             }

           //очистка временной таблицы
           mysql_query("truncate shop_outgoing_tmp");
          }
       }
      else $user->no_rules('action');
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();

 }


//-----------------------------------------------------------------------------
// AJAX

function show_prices($element_id)
{
  $text = "";
  $text2 = "";
  if ($element_id)
   {
     $result = mysql_query("select * from shop_cat_elements where element_id = $element_id and type = 0");
     if (@mysql_num_rows($result) > 0)
      {
        $row = mysql_fetch_object($result);

        $text .= '1. <input style="width:16px;height:16px;" type="radio" name="price" value="'.$row->price1.'" /> '.$row->price1.'<br/>';
        $text .= '2. <input style="width:16px;height:16px;" type="radio" name="price" value="'.$row->price2.'" /> '.$row->price2;

        $text2 = 'Всего на складе: '.$row->amount;
      }
   }

	$objResponse = new xajaxResponse();
	$objResponse->assign("prices","innerHTML",$text);
	$objResponse->assign("total_amount","innerHTML",$text2);

	return $objResponse;
}

function show_elements($parent_id)
{
	$objResponse = new xajaxResponse();
  $select_elements = '<select name="element_id" style="width:280px;" size="6"onchange=" xajax_show_prices(this.form.element_id.options[this.form.element_id.selectedIndex].value);">';

  $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
  if (mysql_num_rows($result) > 0)
   {
      while ($row = mysql_fetch_array($result))
       {
         $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].', art:'.htmlspecialchars($row['store_name']).')</option>';
       }
   }
  else $select_elements .= '<option value="">Нет товаров</option>';

  $select_elements .= '</select>';

	$objResponse->assign("elements","innerHTML",$select_elements);
	return $objResponse;
}

$xajax->registerFunction("show_prices");
$xajax->registerFunction("show_elements");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад', 1);
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs2->add_tab('/admin/shop_incoming.php', 'Приход товара');
if ($user->check_user_rules('view','/admin/shop_outgoing.php')) $tabs2->add_tab('/admin/shop_outgoing.php', 'Расход товара', 1);
if ($user->check_user_rules('view','/admin/shop_places.php')) $tabs2->add_tab('/admin/shop_places.php', 'Торговые точки');
if ($user->check_user_rules('view','/admin/shop_sale.php')) $tabs2->add_tab('/admin/shop_sale.php', 'Продажи');
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
if ($user->check_user_rules('view','/admin/shop_outgoing.php')) $tabs3->add_tab('/admin/shop_outgoing.php', 'Новая накладная');
if ($user->check_user_rules('view','/admin/shop_outgoing_all.php')) $tabs3->add_tab('/admin/shop_outgoing_all.php', 'Все накладные');
$tabs3->show_tabs();

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
          $options .= '<option value="'.$row['element_id'].'">'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
          show_select($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options;
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
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
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
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
   $message->add_message('invalid_amount', 'Количество не должно превышать остаток на складе');
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить имеющийся на складе товар</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 $shop_currency = 'руб.';
 $shop_currency = $user->get_cms_option('shop_currency');

 echo '<form action="" name="selector" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Товар <sup class="red">*</sup></td>
      <td>
         <select name="parent_id" style="width:280px;" onchange="xajax_show_elements(this.form.parent_id.options[this.form.parent_id.selectedIndex].value);">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень галереи---</option>'.
         show_select()
         .'</select>
         <div id="elements"></div>
      </td></tr>
    <tr>
      <td>Цена, '.$shop_currency.'<br><span class="grey">2 колонки цен в прайс-листе</span></td>
      <td><div name="prices" id="prices"></div></td>
    </tr>
    <tr>
      <td>Количество, шт.<sup class="red">*</sup></td>
      <td><table cellspacing="0" cellpadding="0" border="0"><tr><td><input style="width:70px" type="text" name="amount" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td><td style="padding-left: 10px;"><div class="grey" name="total_amount" id="total_amount"></div></td></tr></table></td></tr>

  </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

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
    $sort_by = 'id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select
           shop_outgoing_tmp.*,
           shop_cat_elements.element_name,
           shop_cat_elements.store_name,
           shop_cat_elements.producer_store_name
           from shop_outgoing_tmp, shop_cat_elements where shop_outgoing_tmp.element_id = shop_cat_elements.element_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=element_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'element_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Арт.&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=store_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'store_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=store_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'store_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Арт. произв.&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_store_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'producer_store_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=producer_store_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'producer_store_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Количество, шт.&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=amount&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'amount' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=amount&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'amount' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Цена, '.$shop_currency.'&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Всего, '.$shop_currency.'&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price_discount&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price_discount' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price_discount&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price_discount' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
          <td width="120">&nbsp;</td>
       </tr>'."\n";

 $total_price = 0;
 $total_amount = 0;

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['id'].'</td>
           <td>'.htmlspecialchars($row['element_name']).'</td>
           <td>'.htmlspecialchars($row['store_name']).'</td>
           <td>'; if ($row['producer_store_name'])  echo htmlspecialchars($row['producer_store_name']); else echo '&nbsp;'; echo '</td>
           <td align="center">'.$row['amount'].'</td>
           <td align="center">'.$row['price'].'</td>
           <td align="center">'.($row['price'] * $row['amount']).'</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_outgoing_tmp.php?id='.$row['id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   $total_price += $row['price'] * $row['amount'];
   $total_amount += $row['amount'];
   }

  echo '<tr><td colspan="7">&nbsp;</td></tr>
        <tr class="header">
          <td align="right" colspan="4">Всего: </td>
          <td align="center">'.$total_amount.'</td>
          <td align="center">&nbsp;</td>
          <td align="center">'.$total_price.'</td>
          <td>&nbsp;</td>
        </tr>';
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);

 $res= mysql_query("select * from shop_places order by place_name asc");
 if (mysql_num_rows($res) > 0)
  {

if (isset($_GET['message']))
 {
   $message2 = new Message;
   $message2->copy_message('formvalues', 'formvalues2');
   $message2->copy_message('db', 'db2');
   $message2->get_message($_GET['error']);
 }

    echo '<p>
          <form method="POST" action="?action=register" onsubmit="if(confirm(\'Вы действительно провести накладную?\')) return true;">';

    echo '<select name="place_id">
          <option value="">Выберите торговую точку...</option>';
    while($r = mysql_fetch_array($res))
      echo '<option value="'.$r['place_id'].'">'.htmlspecialchars($r['place_name']).'</option>';

    echo '</select> &nbsp;
          Скидка, '.$shop_currency.': <input type="text" name="discount"  style="width:70px" value="0" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"> &nbsp;
          <button type="submit">Провести накладную</button></form>
          </p>';
  }

 echo '<hr size="1">';
}

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>