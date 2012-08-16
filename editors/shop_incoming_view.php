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
   $incoming_id = (int)$_GET['id'];
   $result = mysql_query("select
                         shop_incoming.*,
                         date_format(shop_incoming.date, '%d.%m.%Y (%H:%i:%s)') as date2
                         from shop_incoming
                         where shop_incoming.incoming_id = $incoming_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $date = $row['date2'];

 
  $shop_currency = 'руб.';
  $shop_currency = $user->get_cms_option('shop_currency');

  echo '<h2>Приходная накладная от '.htmlspecialchars($date).'</h2>';

   $result = mysql_query("select * from shop_incoming_data where incoming_id = $incoming_id");
   if (mysql_num_rows($result) > 0)
    {
      $total_price1 = 0;
      $total_price2 = 0;
      $total_amount = 0;

      echo '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
      echo '<tr align="center" class="header">
         <td nowrap width="50">№</td>
         <td nowrap>Название</td>
         <td nowrap>Арт.</td>
         <td nowrap>Арт. произв.</td>
         <td nowrap>Количество, шт.</td>
         <td nowrap>Цена 1, '.$shop_currency.'</td>
         <td nowrap>Цена 2, '.$shop_currency.'</td>
       </tr>';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr class="underline">
                 <td align="center">'.$row['data_id'].'</td>
                 <td>'.htmlspecialchars($row['element_name']).'</td>
                 <td>'.htmlspecialchars($row['store_name']).'</td>
                 <td>'; if ($row['producer_store_name']) echo htmlspecialchars($row['producer_store_name']); else echo '&nbsp;'; echo '</td>
                 <td align="center">'.$row['amount'].'</td>
                 <td align="center">'.$row['price1'].'</td>
                 <td align="center">'.$row['price2'].'</td>
               </tr>';
               
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
        </tr>';
      echo '</table>';
    }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>