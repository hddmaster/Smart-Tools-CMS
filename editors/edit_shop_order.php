<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (    isset($_POST['order_username']) &&
        isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {		
   
        $order_id = (int)$_GET['id'];
		
		$res = mysql_query('SELECT send_to_external_system FROM shop_orders WHERE send_to_external_system != 0 and order_id = '.$order_id);
		if(mysql_num_rows($res) > 0) {			
			header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=order-error");
			exit();
		}
   
        mysql_query("update shop_order_values set grid = '".serialize(array())."' where order_id = $order_id");
        if (isset($_POST['grid'])) {
            foreach ($_POST['grid'] as $value_id => $grid)
                mysql_query("update shop_order_values set grid = '".serialize($grid)."' where value_id = $value_id");
        }   

        if (isset($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $value_id => $quantity) {
                if (intval($quantity) <= 0) mysql_query("delete from shop_order_values where value_id = $value_id");
                else mysql_query("update shop_order_values set quantity = ".intval($quantity)." where value_id = $value_id");
            }
        }

        if (isset($_POST['comment'])) {
            foreach ($_POST['comment'] as $value_id => $comment)
                mysql_query("update shop_order_values set comment = '".trim($comment)."' where value_id = $value_id");
        }

        mysql_query("update shop_order_values set payed = 0 where order_id = $order_id");
        if (isset($_POST['payed'])) {
            foreach ($_POST['payed'] as $value_id => $payed)
                mysql_query("update shop_order_values set payed = 1 where value_id = $value_id");
        }

        $order_username = trim($_POST['order_username']);
        $order_phone = trim($_POST['order_phone']);
        $order_email = trim($_POST['order_email']);
        $order_address = trim($_POST['order_address']);
        $delivery_date = substr($_POST['delivery_date'],6,4).substr($_POST['delivery_date'],3,2).substr($_POST['delivery_date'],0,2);
        $delivery_hour1 = $_POST['hour1'];
        $delivery_extra_name = trim($_POST['delivery_extra_name']);      
        $delivery_extra_price = ((double)$_POST['delivery_extra_price'] > 0 ? (double)$_POST['delivery_extra_price'] : 0);    
        $delivery_hour2 = $_POST['hour2'];
        $delivery_date2 = substr($_POST['delivery_date2'],6,4).substr($_POST['delivery_date2'],3,2).substr($_POST['delivery_date2'],0,2);
        $delivery_hour21 = $_POST['hour21'];
        $delivery_hour22 = $_POST['hour22'];
        $description_hidden = trim($_POST['description_hidden']);
        $order_comment = trim($_POST['order_comment']);
        $extended_info = trim($_POST['extended_info']);
        $delivery_id = $_POST['delivery_id'];
        $courier_id = $_POST['courier_id'];
        $user_id = $_POST['user_id'];
        $site_user_id = $_POST['site_user_id'];
        $status_id = $_POST['status_id'];
        $place_id = $_POST['place_id'];
        $payment_type_id = $_POST['payment_type_id'];
		$pickup = $_POST['pickup'];
		
		if($pickup) $delivery_id = 0;
        
        $global_id = ((isset($_POST['global_id']) && $_POST['global_id'] > 0) ? $_POST['global_id'] : 0);   

        //перерасчет стоимости заказа (по товарам)
        $price = 0;
        $result = mysql_query("select * from shop_order_values where order_id = $order_id");
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result))
                $price += $row['price'] * $row['quantity'];
        }

        $result = mysql_query(" update
                                shop_orders
                                set
                                
                                order_username = '$order_username',
                                order_phone = '$order_phone',
                                order_email = '$order_email',
                                order_address = '$order_address',
                                delivery_date = '$delivery_date',
                                delivery_hour1 = '$delivery_hour1',
                                delivery_hour2 = '$delivery_hour2',
                                delivery_date2 = '$delivery_date2',
                                delivery_hour21 = '$delivery_hour21',
                                delivery_hour22 = '$delivery_hour22',
                                delivery_extra_name = '$delivery_extra_name',      
                                delivery_extra_price = $delivery_extra_price,      
                                description_hidden = '$description_hidden',
                                order_comment = '$order_comment',
                                extended_info = '$extended_info',
                                delivery_id = $delivery_id,
                                courier_id = $courier_id,
                                user_id = $user_id,
                                site_user_id = $site_user_id,
                                price = $price,
                                global_id = $global_id,
                                status_id = $status_id,
                                place_id = $place_id,
                                payment_type_id = $payment_type_id,
								pickup = '$pickup'
                                
                                where order_id=$order_id") or die(mysql_error());
        if (!$result) {
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.$order_id.'&message=db');
            exit();
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=changed");
        exit();
    } else $user->no_rules('edit');
}

if (    isset($_GET['element_id']) &&
        isset($_GET['id'])) {
    if ($user->check_user_rules('add')) {
    
        $order_id = (int)$_GET['id'];
		
		$res = mysql_query('SELECT send_to_external_system FROM shop_orders WHERE send_to_external_system != 0 and order_id = '.$order_id);
		if(mysql_num_rows($res) > 0) {			
			header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=err");
			exit();
		}
		
        $element_id = $_GET['element_id'];
        if (trim($_GET['element_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=formvalues2");exit();}

        $result = mysql_query("select * from shop_order_values where order_id = $order_id and element_id = $element_id");
        if (mysql_num_rows($result) > 0) // товар уже есть в заказе
        {
            $row = mysql_fetch_array($result);
            $quantity = $row['quantity'] + 1;
            mysql_query("update shop_order_values set quantity = $quantity where order_id = $order_id and element_id = $element_id");
        }
        else // новый товар в заказе
        {
            $result = mysql_query("select * from shop_cat_elements where element_id = $element_id");
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $element_name = $row['element_name'];
                $store_name = $row['store_name'];
                $producer_store_name = $row['producer_store_name'];
                $price = $row['price2'];
            
                mysql_query("
                                insert into shop_order_values
                                (
                                    order_id,
                                    element_id,
                                    store_name,
                                    producer_store_name,
                                    element_name,
                                    quantity,
                                    price
                                )
                                values
                                (
                                    $order_id,
                                    $element_id,
                                    '".addslashes($store_name)."',
                                    '".addslashes($producer_store_name)."',
                                    '".addslashes($element_name)."',
                                    1,
                                    $price
                                )
                                ");
            }
        }

        //перерасчет стоимости заказа
        $price = 0;
        $result = mysql_query("select * from shop_order_values where order_id = $order_id");
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result))
                $price += $row['price'] * $row['quantity'];
        }
   
        $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id"); exit();
    } else $user->no_rules('add');
}

if (    isset($_GET['action']) && $_GET['action'] !=='' &&
        isset($_GET['value_id']) && $_GET['value_id']!=='' &&
        isset($_GET['id']) && $_GET['id']!=='') {
    $order_id = (int)$_GET['id'];
    $value_id = $_GET['value_id'];
    $action = $_GET['action'];

    if ($action == 'delete') {
        if ($user->check_user_rules('delete')) {
            mysql_query("delete from shop_order_values where order_id = $order_id and value_id = $value_id");
            $price = 0;
            $result = mysql_query("select * from shop_order_values where order_id = $order_id");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result))
                    $price += $row['price'] * $row['quantity'];
            }
   
            $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

            //перерасчет стоимости заказа
            $price = 0;
            $result = mysql_query("select * from shop_order_values where order_id = $order_id");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result))
                    $price += $row['price'] * $row['quantity'];
            }
            
            $result = mysql_query("update shop_orders set price = $price where order_id=$order_id");

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
            $_SESSION['smart_tools_refresh'] = 'enable';
        } else
            $user->no_rules('delete');
    }

}

if (    isset($_GET['action']) && $_GET['action'] !== '' &&
        isset($_GET['id']) && $_GET['id'] !== '') {
    $order_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if($action == 'synchronization') {
		mysql_query("update shop_orders set send_to_external_system = 1, date_synchronization = NOW() where order_id = $order_id");
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$order_id.'&message=changed');
		exit();
    }
    
    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();
    $_SESSION['smart_tools_refresh'] = 'enable';
}

if (isset($_POST['head']) &&
    isset($_GET['id']) &&
    isset($_FILES['file_path']['name']) &&
    is_uploaded_file($_FILES['file_path']['tmp_name'])) {

    if ($user->check_user_rules('add')) {

        $head = ''; if (isset($_POST['head'])) $head = $_POST['head'];
        $order_id = $_GET['id'];
        
        $user_file_name = mb_strtolower($_FILES['file_path']['name'],'UTF-8');
        $file = pathinfo($user_file_name);
        $ext = $file['extension'];
        $name_clear = str_replace(".$ext",'',$user_file_name);
        $name = $name_clear;
        $i = 1;
        while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/order_files/$name.$ext")) {
            $name = $name_clear." ($i)";
            $i++;
        }
        $user_file_name = $name.'.'.$ext;

        $query = "insert into shop_order_files (order_id, head, file_path) values ($order_id, '$user_file_name'";
        if (isset($_FILES['file_path']['name']) && is_uploaded_file($_FILES['file_path']['tmp_name'])) $query .= ", '"."/userfiles/order_files/$user_file_name"."'";
        else $query .= ", ''";
        $query = $query.")";

        $result = mysql_query($query) or die(mysql_error());
        if (!$result) {Header("Location: ".$_SEVRER['PHP_SELF']."?id=$element_id&message=db"); exit();}

        $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/order_files/$user_file_name";
        copy($_FILES['file_path']['tmp_name'], $filename);
        chmod($filename, 0666);

        $cache = new Cache; $cache->clear_cache_by_module();

        header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id");
        exit();
    } else $user->no_rules('add');
}

if (isset($_POST['order_files_names']) &&
    isset($_GET['id'])) {
    
    if ($user->check_user_rules('edit')) {
    
        $order_id = $_GET['id']; 
        foreach($_POST['order_files_names'] as $file_id => $head)
            mysql_query("update shop_order_files set head = '$head' where file_id = $file_id");

        Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id"); exit();

    } else $user->no_rules('add');
}


if (isset($_GET['deletefile']) &&
    isset($_GET['file_id']) &&
    isset($_GET['id'])) {
    $file_id = (int)$_GET['file_id'];
    $order_id = $_GET['id'];
    
    if ($user->check_user_rules('delete')) {
        $result = mysql_query("select * from shop_order_files where file_id = $file_id");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);

            if($row['file_path']) {
                $filename = $row['file_path'];
                if(!use_file($filename,'shop_order_files','file_path')) unlink($_SERVER['DOCUMENT_ROOT'].$filename);
            }
         
            $result = mysql_query("delete from shop_order_files where file_id=$file_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id&message=db"); exit();}

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        }
    } else $user->no_rules('delete');

    header("Location: ".$_SERVER['PHP_SELF']."?id=$order_id");
    exit();
}

//-----------------------------------------------------------------------------
// AJAX

function show_elements($parent_id) {
    $objResponse = new xajaxResponse();

    $select_elements = '<select name="element_id" style="width:280px;" size="6">';
    $result = mysql_query("select * from shop_cat_elements where type = 0 and parent_id = $parent_id order by order_id asc");
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result))
            $select_elements .= '<option value="'.$row['element_id'].'">'.htmlspecialchars($row['element_name']).' (id: '.$row['element_id'].', art:'.htmlspecialchars($row['store_name']).')</option>';
    }
    else $select_elements .= '<option value="">Нет товаров</option>';
    $select_elements .= '</select>';

    $objResponse->assign("elements","innerHTML",$select_elements);
    return $objResponse;
}

function find_elements($order_id, $str) {
    $objResponse = new xajaxResponse();
    $query = '%'.mb_strtolower(trim($str), 'UTF-8').'%';
    $text = '';

    $q = "  select
            E.*
            from
            shop_cat_elements as E left join shop_cat_producers as P
            on E.producer_id = P.producer_id
            where
            type = 0
            and (
                    E.element_id like '$query' or
                    E.element_name like '$query' or
                    E.store_name like '$query' or
                    E.c_store_name like '$query' or
                    E.producer_store_name like '$query' or
                    E.description like '$query' or
                    E.description_full like '$query' or
                    P.producer_name like '$query' or
                    P.producer_descr like '$query' or
                    P.producer_url like '$query'
                )
             limit 10";
    $result = mysql_query($q);
    if(mysql_num_rows($result) > 0) {
        $text .= '  <table>
                        <tr>
                            <th>№</th>
                            <th>Название</th>
                            <th>Артикул</th>
                            <th>Артикул 1С</th>
                            <th>Артикул производителя</th>
                            <th>Цена 1</th>
                            <th>Цена 2</th>
                            <th>Цена 3</th>
                            <th>Цена 4</th>
                            <th>Цена 5</th>
                            <th>Добавить</th>
                        </tr>';
        while($row = mysql_fetch_object($result)) {
            $text .= '  <tr>
                            <td align="center">'.$row->element_id.'</td>
                            <td><a href="?id='.$order_id.'&element_id='.$row->element_id.'">'.htmlspecialchars($row->element_name).'</a></td>
                            <td>'.$row->store_name.'</td>
                            <td>'.$row->c_store_name.'</td>
                            <td>'.$row->producer_store_name.'</td>
                            <td>'.$row->price1.'</td>
                            <td>'.$row->price2.'</td>
                            <td>'.$row->price3.'</td>
                            <td>'.$row->price4.'</td>
                            <td>'.$row->price5.'</td>
                            <td align="center"><a href="?id='.$order_id.'&element_id='.$row->element_id.'"><img src="/admin/images/icons/plus.png" alt="Добавить"></a></td>
                        </tr>';
        }
        $text .= '</table>';        
    } else {
        $text .= '<div>поиск не дал результатов</div>';
    }

    $objResponse->assign('elements', 'innerHTML', $text);
    return $objResponse;
}

$xajax->registerFunction("show_elements");
$xajax->registerFunction("find_elements");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {

        function get_shop_tree(&$shop_tree) {
            $result = mysql_query("select * from shop_cat_elements where type = 1 order by order_id asc");
            if(mysql_num_rows($result) > 0)
                while ($row = mysql_fetch_object($result))
                    $shop_tree[$row->parent_id][$row->element_id] = $row->element_name;
        }
        $shop_tree = array(); get_shop_tree($shop_tree);

        function show_select($parent_id = 0, $prefix = '', $selected_element_id = 0, &$shop_tree) {
            global $options;
            foreach($shop_tree[$parent_id] as $element_id => $element_name) {
                $options .= '<option value="'.$element_id.'"'.($selected_element_id == $element_id ? ' selected' : '').'>'.$prefix.htmlspecialchars($element_name).'</option>';
                show_select($element_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_element_id, $shop_tree);
            }
            return $options;
        }

        $order_id = (int)$_GET['id'];
        $result = mysql_query(" select
                                shop_orders.*,
                                date_format(shop_orders.order_date, '%d.%m.%Y (%H:%i:%s)') as order_date_f,
                                date_format(shop_orders.delivery_date, '%d.%m.%Y') as delivery_date_f,
                                date_format(shop_orders.delivery_date2, '%d.%m.%Y') as delivery_date2_f,
                                auth_site.username,
                                shop_delivery.price as delivery_price
                                from shop_orders left join auth_site on shop_orders.user_id = auth_site.user_id
                                left join shop_delivery on shop_orders.delivery_id = shop_delivery.delivery_id
                                where shop_orders.order_id = $order_id");

        if (!$result) exit();
        $row = mysql_fetch_object($result);

        echo '<div><span class="h2">Заказ №'.$row->order_id.'</span> <span class="h3">от '.$row->order_date_f.'</span></div><div>&nbsp;</div>';

        if($row->send_to_external_system) {
            echo '<h1 style="color: #EE0000;">ЗАКАЗ НЕЛЬЗЯ РЕДАКТИРОВАТЬ!</h1>';
        }
	
        if (isset($_GET['message'])) {
            $message = new Message;
            $message->add_message('order-error', 'exclamation', 'ЗАКАЗ НЕЛЬЗЯ РЕДАКТИРОВАТЬ!');
			$message->get_message($_GET['message']);
        }
 
    ?>
        <style>
            #query {
                border: #ccc 1px solid;
                background: none;
                width: 100%;
            }
            
            #elements {
                margin: 10px 0px;
                width: 100%;
            }

            #elements table {
                border-collapse: collapse;
                border: none;
                empty-cells: show;
                width: 100%;
            }

            #elements table tr {
            }
            
            #elements table tr th {
                padding: 4px;
                border: #eee 1px solid;
                background: #efefef;
            }

            #elements table tr td {
                padding: 4px;
                border: #eee 1px solid;
            }


        </style>
        
        <script type="text/javascript">
            var default_value = 'добавить товар в заказ: поиск по ключевому слову';
			$(document).ready(function() {
				$('input[name="pickup"]').click(function() {
					if(this.checked) {
						$('select[name=delivery_id]').attr('disabled','disabled');
						$('input[name=delivery_extra_name]').attr('disabled','disabled');
						$('input[name=delivery_extra_price]').attr('disabled','disabled');
					}else{
						$('select[name=delivery_id]').removeAttr('disabled');
						$('input[name=delivery_extra_price]').removeAttr('disabled');
					}
				});
			});
        </script>
    
        <div style="border-top: #ccc 1px dashed; clear: both;">&nbsp;</div>
        <input type="text" id="query" name="query" value="добавить товар в заказ: поиск по ключевому слову" onclick="if(this.value == default_value) this.value = '';" onfocus="if(this.value == default_value) this.value = '';" onkeyup="if(this.value.length > 2) xajax_find_elements(<?=$order_id?>, this.value);" style="width: 100%;">
        <div id="elements" style="width: 100%;"></div>
        <div style="border-bottom: #ccc 1px dashed; clear: both;">&nbsp;</div><div>&nbsp;</div>
    <?


 echo '<form action="?id='.$order_id.'" method="post">';
 
 if(!$row->send_to_external_system) {
	echo '<div><button type="SUBMIT">Сохранить</button></div><div>&nbsp;</div>';
 }
 echo '<table cellpadding="4" cellspacing="1" border="0" class="form" width="100%">
    <tr>
      <td>Статус</td>
      <td>
        <table cellspacing="0" cellpadding="0"><tr>';
           
           if ($row->status_id)
            {
              $res = mysql_query("select * from shop_order_status where status_id = ".$row->status_id);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 echo '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px; border: #eeeeeee 1px solid; background:#'.$r['status_color'].'">&nbsp;</div></td>';
               }
              else echo '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px; border: #eeeeeee 1px solid;">&nbsp;</div></td>';
            }

           echo '<td style="border: 0px;"><select style="width: '.(($row->status_id) ? '260' : '280').'px;" name="status_id">';
           echo '<option value="0"'; if($row->status_id == 0) echo ' selected'; echo '>---НЕТ---</option>';
           $res = mysql_query("select * from shop_order_status order by status_name asc");
           if (mysql_num_rows($res) > 0)
            {
              while ($r = mysql_fetch_array($res))
               {
                 echo '<option value="'.$r['status_id'].'"';
                 if ($row->status_id == $r['status_id']) echo ' selected';
                 echo '>'.htmlspecialchars($r['status_name']).'</option>';
               }
            }

           echo '</select></td></tr></table>
      </td>
    </tr>
    <tr>
      <td>Магазин сети</td>
      <td>
        <select name="place_id" style="width: 280px;"><option value="0">---НЕТ---</option>';
        $res = mysql_query("select * from shop_places where status = 1 order by place_name asc");
        if (mysql_num_rows($res) > 0) {
            while ($r = mysql_fetch_object($res))
                echo '<option value="'.$r->place_id.'"'.(($row->place_id == $r->place_id) ? ' selected' : '').'>'.htmlspecialchars($r->place_name).'</option>';
        }
        echo '</select>
      </td>
    </tr>
    
    
    <tr>
      <td>Ф.И.О. <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="order_username" value="'.htmlspecialchars($row->order_username).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Контактный телефон <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="order_phone" value="'.htmlspecialchars($row->order_phone).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>e-mail</td>
      <td><input style="width:280px" type="text" name="order_email" value="'.htmlspecialchars($row->order_email).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Адрес доставки <sup class="red">*</sup></td>
      <td><textarea style="width: 100%;" rows="2" name="order_address">'.htmlspecialchars($row->order_address).'</textarea></td>
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
        <table cellspacing="0" cellpadding="0"><tr>
        <td>
        <select name="delivery_id" style="width: 280px;" '.(($row->pickup && !$row->delivery_id) ? 'disabled="disabled"' : '').'><option value="0">---НЕТ---</option>';
        $res = mysql_query("select * from shop_delivery order by delivery_name asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->delivery_id.'"'.(($row->delivery_id == $r->delivery_id) ? ' selected' : '').'>'.htmlspecialchars($r->delivery_name).'</option>';
         }
        echo '</select>
        </td>
        <td class="small" style="padding: 0px 5px 0px 20px;">Город</td>
        <td><input type="text" name="delivery_extra_name" value="'.$row->delivery_extra_name.'" style="width: 100px;"></td>        
        <td class="small" style="padding: 0px 5px 0px 20px;">Цена</td>
        <td><input type="text" name="delivery_extra_price" value="'.$row->delivery_extra_price.'" style="width: 100px;" '.(($row->pickup && !$row->delivery_id) ? 'disabled="disabled"' : '').'></td>
        </tr>
		<tr>
			<td colspan="5" style="padding: 10px 0px 0px;"><label><input type="checkbox" name="pickup" value="1" '.(($row->pickup && !$row->delivery_id) ? 'checked="checked"' : '').'/> Cамовывоз</label></td>
		</tr>
		</table>
      </td>
    </tr>
    <tr>
      <td>Товары</td>
      <td width="100%">';

   $result_values = mysql_query("select * from shop_order_values where order_id = $order_id");
   if (mysql_num_rows($result_values) > 0)
    {
      echo '<table cellspacing="0" cellpadding="4" border="0" width="100%">
             <tr align="center" class="header">
               <td width="50%">Товар</td>
               <td width="50%">Свойства</td>
               <td>Примечание</td>
               <td>Кол-во</td>
               <td>Оплачен</td>
               <td nowrap>Цена</td>
               <td nowrap>Сумма</td>
               <td>&nbsp;</td>
             </tr>';
      while ($row_values = mysql_fetch_array($result_values))
       {
         echo '<tr>
               <td style="border-bottom: #ccc 1px dotted;">
                   <h3 class="nomargins">'.htmlspecialchars($row_values['element_name']).'</h3>
                   <div class="small">ID: '.$row_values['element_id'].($row_values['store_name'] ? ', ART: '.htmlspecialchars($row_values['store_name']) : '').'</div></td>
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
          } else echo '&nbsp;';
         echo '</td>
               <td style="border-bottom: #ccc 1px dotted;"><input type="text" style="width: 150px" name="comment['.$row_values['value_id'].']" value="'.$row_values['comment'].'"></td>
               <td style="border-bottom: #ccc 1px dotted;" align="center"><input type="text" style="width: 30px" name="quantity['.$row_values['value_id'].']" value="'.$row_values['quantity'].'"></td>
               <td style="border-bottom: #ccc 1px dotted;" align="center"><input type="checkbox" name="payed['.$row_values['value_id'].']" value="'.$row_values['payed'].'" '.(($row_values['payed'] == 1) ? 'checked' : '').'></td>
               <td style="border-bottom: #ccc 1px dotted;" align="center" nowrap>'.number_format($row_values['price'], 2, ',', ' ').'</td>
               <td style="border-bottom: #ccc 1px dotted;" align="center" nowrap>'.number_format($row_values['price']*$row_values['quantity'], 2, ',', ' ').'</td>';
         echo '<td nowrap style="border-bottom: #ccc 1px dotted;" align="center"><a href="';
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
      <td>Итого</td>
      <td class="h2">'.number_format($row->price + $row->delivery_price, 2, ',', ' ').' '.$shop_currency.'</td>
    </tr>
    <tr>
      <td>Форма оплаты</td>
      <td>
        <select name="payment_type_id" style="width: 280px;"><option value="0">---НЕТ---</option>';
        $res = mysql_query("select * from shop_order_payment_types order by type_name asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->type_id.'"'.(($row->payment_type_id == $r->type_id) ? ' selected' : '').'>'.htmlspecialchars($r->type_name).'</option>';
         }
        echo '</select>
      </td>
    </tr>
<!--
    <tr>
      <td>Итого со скидкой</td>
      <td class="h2">'.($row->price_discount + $row->delivery_price).' '.$shop_currency.'</td>
    </tr>
-->
    <tr>
      <td>Менеджер</td>
      <td>
        <select name="user_id" style="width: 280px;"><option value="0">---НЕТ---</option>';
        $res = mysql_query("select * from auth where get_orders = 1 order by username, user_fio asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->user_id.'"'.(($row->user_id == $r->user_id) ? ' selected' : '').'>'.htmlspecialchars($r->username).(($r->user_fio) ? ' ('.htmlspecialchars($r->user_fio).')' : '').'</option>';
         }
        echo '</select>
      </td>
    </tr>
    <tr>
      <td>Пользователь сайта</td>
      <td>
        <select name="site_user_id" style="width: 280px;"><option value="0">---НЕТ---</option>';
        $res = mysql_query("select * from auth_site order by username, user_fio asc");
        if (mysql_num_rows($res) > 0)
         {
           while ($r = mysql_fetch_object($res))
             echo '<option value="'.$r->user_id.'"'.(($row->site_user_id == $r->user_id) ? ' selected' : '').'>'.htmlspecialchars($r->username).(($r->user_fio) ? ' ('.htmlspecialchars($r->user_fio).')' : '').'</option>';
         }
        echo '</select>
      </td>
    </tr>
    <tr>
      <td>Курьер</td>
      <td>
        <select name="courier_id" style="width: 280px;"><option value="0">---НЕТ---</option>';
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
      <td>Примечание покупателя</td>
      <td><textarea style="width: 100%;" rows="2" name="order_comment">'.htmlspecialchars($row->order_comment).'</textarea></td>
    </tr>
    <tr>
      <td>Примечание менеджера</td>
      <td><textarea style="width: 100%;" rows="2" name="description_hidden">'.htmlspecialchars($row->description_hidden).'</textarea></td>
    </tr>
    <tr>
      <td>Дополнитетельная информация</td>
      <td><textarea style="width: 100%;" rows="2" name="extended_info">'.htmlspecialchars($row->extended_info).'</textarea></td>
    </tr>
    <tr>
      <td>Номер пользователя за все время</td>
      <td>
       <table cellspacing="0" cellpadding="0">
        <tr>
          <td><input type="text" name="global_id" class="h2" style="width: 100px;" value="'.(($row->global_id) ? $row->global_id : '').'"></td>';
          if($row->global_id)
          echo '<td style="padding: 0px 6px 0px 30px"><a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$row->global_id.'&date1='.(((int)date('Y')-10).date('md').'235959').'&date2='.date('Ymd235959').'\');"><img src="/admin/images/icons/chart.png" alt="" border="0"></a></td>
                <td><a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$row->global_id.'&date1='.(((int)date('Y')-10).date('md').'235959').'&date2='.date('Ymd235959').'\');">статистика пользователя за все время</a></td>';
        echo '</tr>
       </table> 
      </td>
    </tr>
   </table>
   <div>&nbsp;</div>';

	if($row->send_to_external_system) {
        echo '<p>Заказ синхронизован с базой данных магазина. Редактирование заблокировано.</p>';
    }else{
		echo '<div><button type="SUBMIT">Сохранить</button></div>';
	}
	echo '</form>';



    echo '<h2>Файлы</h2>';
    echo '<div>';
    echo '</div>';

    if (isset($_GET['message-files'])) {
        $message = new Message;
        $message->get_message($_GET['message-files']);
    }

    echo '  <div class="dhtmlgoodies_question">
            <table cellspacing="0" cellpadding="4">
                <tr>
                    <td><img src="/admin/images/icons/plus.png" alt=""></td>
                    <td><h4 class="nomargins">Добавить новый файл</h4></td>
                </tr>
            </table>   
            </div>
            <div class="dhtmlgoodies_answer"><div>';

    echo '  <form enctype="multipart/form-data" action="?id='.$order_id.'" method="post">
            <table cellpadding="4" cellspacing="1" class="form">
                <tr>
                    <td>Название</td>
                    <td><input style="width:280px" type="text" name="head" maxlength="255"></td>
                </tr>
                <tr>
                    <td>Файл</td>
                    <td><input style="width:280px" type="file" name="file_path"/></td>
                </tr>
            </table><br />
            <button type="SUBMIT">Добавить</button>
            </form><br /></div></div>';

    $query = "  select
                *
                from shop_order_files
                where
                order_id = $order_id";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        echo '  <form action="?id='.$order_id.'" method="post">
                <input type="hidden" name="files" value="true">
                <table cellpadding="4" cellspacing="0" border="0" width="100%">
                    <tr align="center" class="header">
                        <td nowrap width="50">№</td>
                        <td nowrap width="50%">Название</td>
                        <td width="50%">Файл</td>
                        <td width="120">&nbsp;</td>
                    </tr>';

        while ($row = mysql_fetch_array($result)) {
            echo '  <tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
                        <td align="center">'.$row['file_id'].'</td>
                        <td><input type="text" name="order_files_names['.$row['file_id'].']" value="'.htmlspecialchars($row['head']).'" style="width: 100%"></td>
                        <td><a href="'.$row['file_path'].'" target="_blank">'.basename($row['file_path']).'</td>
                        <td nowrap align="center"><a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?deletefile=true&id='.$order_id.'&file_id='.$row['file_id'].'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
                    </tr>';
        }
        echo '  </table>
                <p align="right"><button type="submit">Сохранить</button></p>
                </form>';
    }

  
	if($row->send_to_external_system < 1) {
        ?>
            <div style="margin: 40px 0px 0px 0px; padding: 20px 0px; border-top: 2px #e00 solid;">
                <form method="get" action="?id=<?=$order_id?>">
					<input type="hidden" name="action" value="synchronization">
					<input type="hidden" name="id" value="<?=$order_id?>">
                    <button type="submit">Выгрузить во внешнюю систему</button>
                </form>
                <p class="small grey">Внимение! После синхронизации заказ будет заблокирован. Редактирование будет невозможным.</p>
            </div>
        <?
    }

    
    } else $user->no_rules('view');
}
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>