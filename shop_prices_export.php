<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['store_name']) ||
    isset($_GET['procuder_store_name']) ||
    isset($_GET['element_name']) ||
    isset($_GET['price1']) ||
    isset($_GET['price2']))
 {
   $columns = array();
   if (isset($_GET['store_name'])) $columns[] = 'store_name';
   if (isset($_GET['producer_store_name'])) $columns[] = 'producer_store_name';
   if (isset($_GET['element_name'])) $columns[] = 'element_name';
   if (isset($_GET['producer_name'])) $columns[] = 'producer_name';
   if (isset($_GET['price1'])) $columns[] = 'price1';
   if (isset($_GET['price2'])) $columns[] = 'price2';

   if (count($columns) == 0) {header("Location: ".$_SERVER['PHP_SELF']); exit();}

   $result = mysql_query("select shop_cat_elements.*, shop_cat_producers.producer_name from shop_cat_elements left join shop_cat_producers on shop_cat_elements.producer_id = shop_cat_producers.producer_id where type = 0 order by element_id desc");
   if (mysql_num_rows($result) > 0)
    {
      header("Cache-Control: ");
      header("Pragma: ");
      header("Content-type: application/excel");
      header("Content-Disposition: attachment; filename=price_export.csv");

      while ($row = mysql_fetch_array($result))
       {
         $i = 0;
         foreach($row as $key => $value)
          {
            echo iconv('UTF-8', 'WINDOWS-1251', $row[$columns[$i]]);
            if ($i < count($columns) - 1) echo ";";
            $i++;
          }
         echo "\r\n";
       }

      exit();
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция', 1);
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs2->add_tab('/admin/shop_ym.php', 'Экспорт -&gt; Яндекс.Маркет');
if ($user->check_user_rules('view','/admin/shop_prices_export.php')) $tabs2->add_tab('/admin/shop_prices_export.php', 'Экспорт -&gt; CSV');
if ($user->check_user_rules('view','/admin/shop_prices.php')) $tabs2->add_tab('/admin/shop_prices.php', 'Импорт &lt;- CSV');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {

 echo '<h2>Экспортировать прайс-лист</h2>';
 echo '<form action="shop_prices_export.php" method="get">
       <table cellpadding="4" cellspacing="1" border="0" class="form">
        <tr>
          <td>Колонки для экспорта</td>
          <td>
            <table cellspacing="0" cellpadding="1" border="0">
              <tr><td><input type="checkbox" name="store_name" checked style="width:16px;height:16px;"></td><td>Артикул</td></tr>
              <tr><td><input type="checkbox" name="producer_store_name" checked style="width:16px;height:16px;"></td><td>Артикул производителя</td></tr>
              <tr><td><input type="checkbox" name="element_name" checked style="width:16px;height:16px;"></td><td>Название товара</td></tr>
              <tr><td><input type="checkbox" name="producer_name" checked style="width:16px;height:16px;"></td><td>Название производителя</td></tr>
              <tr><td><input type="checkbox" name="price1" checked style="width:16px;height:16px;"></td><td>Цена 1</td></tr>
              <tr><td><input type="checkbox" name="price2" checked style="width:16px;height:16px;"></td><td>Цена 2</td></tr>
            </table>
          </td>
        </tr>
       </table><br/><button type="submit">Экспортировать</button></form>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>