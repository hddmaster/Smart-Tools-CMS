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
   $outgoing_id = (int)$_GET['id'];
   $result = mysql_query("select
                         shop_outgoing.*,
                         date_format(shop_outgoing.date, '%d.%m.%Y (%H:%i:%s)') as date2,
                         shop_places.place_name
                         from shop_outgoing, shop_places
                         where shop_outgoing.place_id = shop_places.place_id and
                         shop_outgoing.outgoing_id = $outgoing_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $date = $row['date2'];
   $place_name = $row['place_name'];
   $discount = $row['discount'];

 $shop_currency = 'руб.';
 $shop_currency = $user->get_cms_option('shop_currency');

   echo '<h2>'.htmlspecialchars($date).' &nbsp; '.htmlspecialchars($place_name).'</h2>';
   echo '<p><strong>Скидка: '.$discount.' '.$shop_currency.'</strong></p>';

   $result = mysql_query("select * from shop_outgoing_data where outgoing_id = $outgoing_id");
   if (mysql_num_rows($result) > 0)
    {
      $total_price = 0;
      $total_amount = 0;

      echo '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
      echo '<tr align="center" class="header">
         <td nowrap width="50">№</td>
         <td nowrap>Название</td>
         <td nowrap>Арт.</td>
         <td nowrap>Арт. произв.</td>
         <td nowrap>Количество, шт.</td>
         <td nowrap>Цена, '.$shop_currency.'</td>
       </tr>';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr class="underline">
                 <td align="center">'.$row['data_id'].'</td>
                 <td>'.htmlspecialchars($row['element_name']).'</td>
                 <td>'.htmlspecialchars($row['store_name']).'</td>
                 <td>'; if ($row['producer_store_name']) echo htmlspecialchars($row['producer_store_name']); else echo '&nbsp;'; echo '</td>
                 <td align="center">'.$row['amount'].'</td>
                 <td align="center">'.$row['price'].'</td>
               </tr>';
               
          $total_price += $row['price'] * $row['amount'];
          $total_amount += $row['amount'];
       }
      echo '<tr><td colspan="6">&nbsp;</td></tr>
        <tr class="header">
          <td align="right" colspan="4">Всего: </td>
          <td align="center">'.$total_amount.'</td>
          <td align="center">'.$total_price.'</td>
        </tr>';
      echo '</table>';
    }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>