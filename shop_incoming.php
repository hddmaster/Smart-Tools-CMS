<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['element_name']) &&
    isset($_POST['store_name']) &&
    isset($_POST['price1']) &&
    isset($_POST['price2']) &&
    isset($_POST['amount']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['element_name'])=='' ||
      trim($_POST['store_name'])=='' ||
      trim($_POST['price1'])=='' ||
      trim($_POST['price2'])=='' ||
      trim($_POST['amount'])=='' ||
      trim($_POST['parent_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $parent_id = $_POST['parent_id'];
  $element_name = $_POST['element_name'];
  $store_name = $_POST['store_name'];
  $producer_store_name = $_POST['producer_store_name'];
  $price1 = intval($_POST['price1']);
  $price2 = intval($_POST['price2']);
  $amount = intval($_POST['amount']);

  if ($amount <= 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=notnull"); exit();}

  //проверка на повторы
  $result = mysql_query("select * from shop_cat_elements where store_name = '".stripslashes($store_name)."'");
  if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}
  $result = mysql_query("select * from shop_incoming_tmp where store_name = '".stripslashes($store_name)."'");
  if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}

  //уникальная запись! Добавляем на временный склад...
  $result = mysql_query("insert into shop_incoming_tmp values (null , 0, $parent_id, '$store_name', '$producer_store_name', '$element_name', $amount, $price1, $price2)");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }

elseif (isset($_POST['amount']))
{
  if ($user->check_user_rules('add'))
   {
  if (trim($_POST['amount'])=='' ||
      trim($_POST['element_id'])== '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues2"); exit();}

  $element_id = $_POST['element_id'];
  $amount = intval($_POST['amount']);

  if ($amount <= 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=notnull2"); exit();}

  $result = mysql_query("select * from shop_incoming_tmp where element_id = $element_id");
  if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate2"); exit();}

  $result = mysql_query("select * from shop_cat_elements where element_id = $element_id and type = 0");
  if (mysql_num_rows($result) > 0)
   {
     $row = mysql_fetch_array($result);
     $element_name = $row['element_name'];
     $store_name = $row['store_name'];
     $producer_store_name = $row['producer_store_name'];
     $parent_id = $row['parent_id'];
     $price1 = $row['price1'];
     $price2 = $row['price2'];

     //Добавляем на временный склад...
     $result = mysql_query("insert into shop_incoming_tmp values (null, $element_id, $parent_id, '$store_name', '$producer_store_name', '$element_name', $amount, $price1, $price2)");
     if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
   }
  else {Header("Location: ".$_SERVER['PHP_SELF']."?message=type2"); exit();}

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

      if ($user->check_user_rules('delete')) mysql_query("delete from shop_incoming_tmp where id = $id");
      else $user->no_rules('delete');
    }

   if ($action == 'register')
    {
      if ($user->check_user_rules('action'))
       {
         $result = mysql_query("select * from shop_incoming_tmp");
         if (mysql_num_rows($result) > 0)
          {
            //сохранение приходной накладной
            $date = date("YmdHis");
            mysql_query("insert into shop_incoming values (null, '$date')");
            $incoming_id = mysql_insert_id();

            while ($row = mysql_fetch_array($result))
             {
               $parent_id = $row['parent_id'];
               $element_id = $row['element_id'];
               $element_name = $row['element_name'];
               $store_name = $row['store_name'];
               $producer_store_name = $row['producer_store_name'];
               $amount = $row['amount'];
               $price1 = $row['price1'];
               $price2 = $row['price2'];

               //новый элемент
               if ($element_id == 0)
                {
                  mysql_query("insert into shop_cat_elements values (null, $parent_id, 0, 0, '$store_name', '$producer_store_name', '$element_name', '', '', '', '', '', 0, $amount, $price1, $price2, 0, 0, 0, 0, 0)");
                  $element_id = mysql_insert_id();

                  // перенумеровываем порядок элементов
                  $res = mysql_query("select * from shop_cat_elements where parent_id = $parent_id order by order_id asc");
                  if (@mysql_num_rows($res) > 0)
                   {
                     $i = 1;
                     while ($r = mysql_fetch_array($res))
                      {
                        $id = $r['element_id'];
                        mysql_query("update shop_cat_elements set order_id=$i where element_id = $id");
                        $i++;
                      }
                   }
                }

               //уже есть на складе
               else
                {
                  $res = mysql_query("select * from shop_cat_elements where element_id = $element_id");
                  $r = mysql_fetch_array($res);
                  $old_amount = $r['amount'];
                  $new_amount =  $old_amount + $amount;
                  mysql_query("update shop_cat_elements set amount = $new_amount where element_id = $element_id");
                }

               mysql_query("insert into shop_incoming_data values(null, $incoming_id, '$element_name', '$store_name', '$producer_store_name', $amount, $price1, $price2)");
             }

           //очистка временной таблицы
           mysql_query("truncate shop_incoming_tmp");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
 
          }
       }
      else $user->no_rules('action');
    }

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
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
       {
         $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].', art:'.htmlspecialchars($row['store_name']).')</option>';
       }
   }
  else $select_elements .= '<option value="">Нет товаров</option>';

  $select_elements .= '</select>';

	$objResponse->assign("elements","innerHTML",$select_elements);
	return $objResponse;
}



$xajax->bOutputEntities = true;
$xajax->setLogFile("/logs/xajax_errors.log");



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
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs2->add_tab('/admin/shop_incoming.php', 'Приход товара', 1);
if ($user->check_user_rules('view','/admin/shop_outgoing.php')) $tabs2->add_tab('/admin/shop_outgoing.php', 'Расход товара');
if ($user->check_user_rules('view','/admin/shop_places.php')) $tabs2->add_tab('/admin/shop_places.php', 'Торговые точки');
if ($user->check_user_rules('view','/admin/shop_sale.php')) $tabs2->add_tab('/admin/shop_sale.php', 'Продажи');
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs3->add_tab('/admin/shop_incoming.php', 'Новая накладная');
if ($user->check_user_rules('view','/admin/shop_incoming_all.php')) $tabs3->add_tab('/admin/shop_incoming_all.php', 'Все накладные');
$tabs3->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select_all($parent_id = 0, $prefix = '')
  {
    global $options_all;
    $result = mysql_query("SELECT * FROM shop_cat_elements where parent_id = $parent_id order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options_all .= '<option value="'.$row['element_id'].'"';
          if ($row['type'] == 1) $options_all .= ' style="background: #CCCCCC;"';
          $options_all .= '>'.$prefix.htmlspecialchars($row['element_name']);
          if ($row['store_name']) $options_all .= ' (арт.: '.htmlspecialchars($row['store_name']).')';
          $options_all .= '</option>'."\n";
          show_select_all($row['element_id'],$prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
    return $options_all;
  }

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

 function show_tree($parent_id = 0)
  {
    $result = mysql_query("SELECT * FROM shop_cat_elements where parent_id = $parent_id order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if (is_begin($row['element_id'], $row['parent_id'])) echo '<ul>'."\n";
          echo '<li id="node'.$row['element_id'].'"';
          if ($row['type'] == 0) echo ' noChildren="true"';
          echo '>';
          echo '<a href="#">';
          echo htmlspecialchars($row['element_name']);
          echo '</a>';
          if ($row['type'] == 1) echo ' <span class="grey">&lt;группа&gt;</span>';
          if ($row['store_name']) echo ' <span class="grey">('.htmlspecialchars($row['store_name']).')</span>';
          echo '</li>'."\n";
          show_tree($row['element_id']);
          if (is_end($row['element_id'], $row['parent_id'])) echo '</ul>'."\n";
        }
    }
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
   $message->add_message('type', 'Можно добавить только товар');
   $message->get_message($_GET['message']);
 }

 $shop_currency = 'руб.';
 $shop_currency = $user->get_cms_option('shop_currency');

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить новый товар</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="element_name" maxlength="255"></td></tr>
    <tr>
      <td>Артикул <sup class="red">*</sup><br/><span class="grey">Уникальный идентификатор</span></td>
      <td><input style="width:280px" type="text" name="store_name" maxlength="255"></td></tr>
    <tr>
      <td>Артикул производителя</td>
      <td><input style="width:280px" type="text" name="producer_store_name" maxlength="255"></td></tr>
    <tr>
      <td>Расположение товара <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">Выберите группу...</option>
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'').'
          </select>
      </td>
    </tr>
    <tr>
      <td>Цена, '.$shop_currency.'<br><span class="grey">2 колонки цен в прайс-листе</span></td>
      <td>
          1. <input style="width:70px" type="text" name="price1" value="0" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"><br/>
          2. <input style="width:70px" type="text" name="price2" value="0" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;">
      </td></tr>
    <tr>
      <td>Количество, шт.<sup class="red">*</sup></td>
      <td><input style="width:70px" type="text" name="amount" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td></tr>

  </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

if (isset($_GET['message']))
 {
   $message2 = new Message;
   $message2->copy_message('formvalues', 'formvalues2');
   $message2->copy_message('db', 'db2');
   $message2->copy_message('notnull', 'notnull2');
   $message2->copy_message('duplicate', 'duplicate2');
   $error2->add_error_message('type2', 'Можно добавить только товар');
   $message2->get_message($_GET['error']);
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

 echo '<form action="" method="post">
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
      <td>Количество, шт.<sup class="red">*</sup></td>
      <td><input style="width:70px" type="text" name="amount" maxlength="255" onKeyPress = "if (event.keyCode < 48 || event.keyCode > 57) event.returnValue = false;"></td></tr>

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
           *
           from shop_incoming_tmp $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 echo '<p align="right"><form action="javascript:if(confirm(\'Вы действительно провести накладную?\')){location.href=\'?action=register\';}"><button type="submit">Провести накладную</button></form></p>';
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
         <td nowrap>Цена 1, '.$shop_currency.'&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price1&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price1' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price1&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price1' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Цена 2, '.$shop_currency.'&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price2&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price2&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'price2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 $total_price1 = 0;
 $total_price2 = 0;
 $total_amount = 0;

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['id'].'</td>
           <td>'.htmlspecialchars($row['element_name']).'</td>
           <td>'.htmlspecialchars($row['store_name']).'</td>
           <td>'; if ($row['producer_store_name'])  echo htmlspecialchars($row['producer_store_name']); else echo '&nbsp;'; echo '</td>
           <td align="center">'.$row['amount'].'</td>
           <td align="center">'.$row['price1'].'</td>
           <td align="center">'.$row['price2'].'</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_shop_incoming_tmp.php?id='.$row['id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать элемент"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>

         </tr>'."\n";
   $total_price1 += $row['price1'] * $row['amount'];
   $total_price2 += $row['price2'] * $row['amount'];
   $total_amount += $row['amount'];

   }

  echo '<tr><td colspan="6">&nbsp;</td></tr>
        <tr class="header">
          <td align="right" colspan="4">Всего: </td>
          <td align="center">'.$total_amount.'</td>
          <td align="center">'.$total_price1.'</td>
          <td align="center">'.$total_price2.'</td>
          <td>&nbsp;</td>
        </tr>';
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
 echo '<p align="right"><form action="javascript:if(confirm(\'Вы действительно провести накладную?\')){location.href=\'?action=register\';}"><button type="submit">Провести накладную</button></form></p>';
}


 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>