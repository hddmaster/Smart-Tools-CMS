<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $order_id = (int)$_GET['id'];
   $result = mysql_query("select
                          *,
                          date_format(shop_orders.order_date, '%d.%m.%Y (%H:%i:%s)') as order_date2,
                          date_format(shop_orders.delivery_date, '%d.%m.%Y') as delivery_date2
                          from shop_orders
                          where shop_orders.order_id = $order_id");
   if (!$result) exit();
   $row = mysql_fetch_array($result);

 $shop_currency = 'руб.';
 $shop_currency_ext = 'коп.';
 $shop_currency = $user->get_cms_option('shop_currency');
 $shop_currency_ext = $user->get_cms_option('shop_currency_ext');

  echo $user->get_cms_option('shop_cheque');
  echo '<p align="right"><strong>Дата &laquo;<u>&nbsp;&nbsp;'.date("d").'&nbsp;&nbsp;</u>&raquo; &nbsp; <u>&nbsp;&nbsp;&nbsp;&nbsp;';


        switch(date("m"))
         {
           case '01' : echo 'Января'; break;
           case '02' : echo 'Февраля'; break;
           case '03' : echo 'Марта'; break;
           case '04' : echo 'Апреля'; break;
           case '05' : echo 'Мая'; break;
           case '06' : echo 'Июня'; break;
           case '07' : echo 'Июля'; break;
           case '08' : echo 'Августа'; break;
           case '09' : echo 'Сентября'; break;
           case '10' : echo 'Октября'; break;
           case '11' : echo 'Ноября'; break;
           case '12' : echo 'Декабря'; break;
         }

        echo '&nbsp;&nbsp;&nbsp;&nbsp;</u> &nbsp; <u>&nbsp;&nbsp;'.date("Y").'&nbsp;&nbsp;</u> г.</strong></p>
        <p align="center">&nbsp;</p>
        <h2 align="center">ТОВАРНЫЙ ЧЕК</h2>';

  $res = mysql_query("select * from shop_order_values where order_id = $order_id");
  if (mysql_num_rows($res) > 0)
   {
     $total = 0;
     echo '<table cellspacing="0" cellpadding="4" border="0" width="100%" style="border: #555555 1px solid;">
           <tr align="center">
            <td nowrap style="border: #555555 1px solid;"><br/><strong>Наименование товара</strong><br/><br/></td>
            <td nowrap style="border: #555555 1px solid;"><br/><strong>Свойства</strong><br/><br/></td>
            <td nowrap style="border: #555555 1px solid;"><br/><strong>Кол-во</strong><br/><br/></td>
            <td nowrap style="border: #555555 1px solid;"><br/><strong>Цена</strong><br/><br/></td>
            <td nowrap style="border: #555555 1px solid;"><br/><strong>Сумма</strong><br/><br/></td>
            <td style="border: #555555 1px solid;"><br/><strong>Примечание</strong><br/><br/></td>
       </tr>';
     while ($r = mysql_fetch_array($res))
      {
        echo '<tr>
                <td style="border: #555555 1px solid;"><span class="h3">'.htmlspecialchars($r['element_name']).'</span> (код заказа. '.htmlspecialchars($r['element_id']).($r['store_name'] ? ', артикул'.$r['store_name'] : '').')</td>
                <td align="center" style="border: #555555 1px solid;">';
                
                $grid = unserialize($r['grid']);
                if (is_array($grid) && count($grid) > 0)
                {
                foreach ($grid as $grid_id => $sizes)
                 {
                   $res_grid = mysql_query("select * from shop_cat_grids where grid_id = $grid_id");
                   if (mysql_num_rows($res_grid) > 0)
                    {
                      $r_grid = mysql_fetch_array($res_grid);
                      echo '<div><strong>'.htmlspecialchars($r_grid['grid_name']).':</strong> ';
                      $s = 1;
                      foreach ($sizes as $size_id)
                       {
                         $res_size = mysql_query("select * from shop_cat_sizes where size_id = $size_id");
                         if (mysql_num_rows($res_grid) > 0)
                          {  
                            $r_size = mysql_fetch_array($res_size);
                            echo htmlspecialchars($r_size['size_name']);
                            if($s < count($sizes)) echo ', ';
                            $s++;
                          }
                       }
                      echo '</div>'; 
                    }
                    
                 }
                } else echo '&nbsp;';
                
                echo '</td>
                <td align="center" style="border: #555555 1px solid;">'.$r['quantity'].'</td>
                <td align="center" style="border: #555555 1px solid;" nowrap>'.number_format($r['price'], 2, ',', ' ').'</td>
                <td align="center" style="border: #555555 1px solid;" nowrap>'.number_format($r['price']*$r['quantity'], 2, ',', ' ').'</td>
                <td align="left" style="border: #555555 1px solid;">'; if ($r['comment']) echo $r['comment']; else echo '&nbsp;'; echo '</td>
              </tr>';
        $total += $r['price']*$r['quantity'];
      }
     
     $res = mysql_query("select shop_delivery.* from shop_orders left join shop_delivery on shop_orders.delivery_id = shop_delivery.delivery_id where shop_orders.order_id = $order_id and shop_orders.delivery_id != 0");
     if (mysql_num_rows($res) > 0)
      {
        $r = mysql_fetch_array($res);
        echo '<tr>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
                <td style="border-top: #555555 1px solid; border-bottom :#555555 1px solid;">&nbsp;</td>
              </tr>
              <tr>
                <td style="border: #555555 1px solid;"><strong>'.$r['delivery_name'].'</strong></td>
                <td align="center" style="border: #555555 1px solid;">&nbsp;</td>
                <td align="center" style="border: #555555 1px solid;">1</td>
                <td align="center" style="border: #555555 1px solid;" nowrap>'.number_format(($r['price']+(($row['delivery_extra_price'] > 0) ? $row['delivery_extra_price'] : 0)), 2, ',', ' ').'</td>
                <td align="center" style="border: #555555 1px solid;" nowrap>'.number_format(($r['price']+(($row['delivery_extra_price'] > 0) ? $row['delivery_extra_price'] : 0)), 2, ',', ' ').'</td>
                <td align="center" style="border: #555555 1px solid;">'.(($row['delivery_extra_name']) ? $row['delivery_extra_name'] : '&nbsp;').'</td>
              </tr>';
          $total += $r['price'];
      }
     
     if($row['delivery_extra_price']) $total += $row['delivery_extra_price'];
      
     echo '</table><br/>';
     echo '<p align="right"><strong>Всего:  '.number_format($total, 2, ',', ' ').' '.$shop_currency.'</strong></p>';
     echo '<p align="right"><strong>Сумма прописью:  <u>'.print_price_as_text($total, $shop_currency, $shop_currency_ext).'</u></strong></p>';

   }
  else
   echo '<p>Заказ не содержит товаров</p>';


  echo $user->get_cms_option('shop_cheque_text');

   echo '<br/><hr size="1" noshade><br/>';

  $price = $row['price'];

   echo '<h2>Заказ №'.$row['order_id'].' от '.$row['order_date2'].'</h2>';
   echo '<table cellspacing="0" cellpadding="6" style="border: #ccc 1px dotted;">
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">Ф.И.О</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['order_username']).'</strong></td>
          <tr>
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">Контактный телефон</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['order_phone']).'</strong></td>
          <tr>
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">e-mail</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['order_email']).'</strong></td>
          <tr>
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">Адрес доставки</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['order_address']).'</strong></td>
          <tr>
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">Дата доставки</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['delivery_date2']).', &nbsp; с '.$row['delivery_hour1'].' &nbsp; по '.$row['delivery_hour2'].'</strong></td>
          <tr>
          <tr>
            <td style="border-bottom: #ccc 1px dotted; border-right: #ccc 1px dotted;">Примечание к заказу</td>
            <td style="border-bottom: #ccc 1px dotted;"><strong>'.htmlspecialchars($row['order_comment']).'</strong></td>
          <tr>
            <td style="border-right: #ccc 1px dotted;">Примечание менеджера</td>
            <td style=""><strong>'.htmlspecialchars($row['description_hidden']).'</strong></td>
          <tr>
         </table>
         ';

  echo '<script>setTimeout("window.print();", 1000);</script>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>