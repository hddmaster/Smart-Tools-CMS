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

 $element_id = (int)$_GET['id'];
 $result = mysql_query("select
                        *
                        from shop_cat_elements
                        where element_id=$element_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $element_name = $row['element_name'];
   $file1 = $row['img_path1'];
   $file2 = $row['img_path2'];
   $file3 = $row['img_path3'];

 if ($row['element_name']) echo '<h1>'.htmlspecialchars($row['element_name']).'</h1>';
 if ($row['producer_store_name']) echo '<div class="grey">Артикул производителя: '.htmlspecialchars($row['producer_store_name']).'</div>';
 if ($row['producer_id'])
  {
    $res = mysql_query("select * from shop_cat_producers where producer_id = ".$row['procuder_id']);
    if (mysql_num_rows($res) > 0)
     {
       $r = mysql_fetch_array($res);
       echo '<div class="grey">Поизводитель: '.htmlspecialchars($row['producer_name']).'</div>';
     }
  }
 echo '<div>&nbsp;</div>'; 

 $result = mysql_query("select
                        shop_cat_cards.card_name,
                        shop_cat_cards.card_id
                        from shop_cat_cards, shop_cat_element_cards
                        where shop_cat_element_cards.element_id = $element_id and
                        shop_cat_cards.card_id = shop_cat_element_cards.card_id");

 if(mysql_num_rows($result) > 0)
  {
    while($row = mysql_fetch_array($result))
     {
       echo '<table cellpadding="4" cellspacing="0" border="0" style="border: #555555 1px solid;">';
       $card_id = $row['card_id'];
       $res = mysql_query("select
                           shop_cat_options.option_id,
                           shop_cat_options.option_name
                           from shop_cat_options, shop_cat_card_options
                           where shop_cat_card_options.card_id = $card_id and
                           shop_cat_options.option_id = shop_cat_card_options.option_id order by shop_cat_card_options.order_id asc");
       if (mysql_num_rows($res) > 0)
        {
          while ($r = mysql_fetch_array($res))
           {
             $option_id = $r['option_id'];
             $re = mysql_query("select * from shop_cat_option_values where element_id = $element_id and card_id = $card_id and option_id = $option_id");
             if (mysql_num_rows($re) > 0)
              {
                $re_row = mysql_fetch_array($re);
                echo '<tr><td style="border: #555555 1px solid;"><strong>'.htmlspecialchars($r['option_name']).'</strong></td>
                      <td style="border: #555555 1px solid;">'.htmlspecialchars($re_row['option_value']).'</td>  </tr>';
              }
           }
        }
       echo '</table><div>&nbsp;<div>';
     }
  }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>