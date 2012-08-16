<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_FILES['csv_price']['name']))
 {
   if ($user->check_user_rules('add'))
   {
     if (!is_uploaded_file($_FILES['csv_price']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
     $user_file_name = mb_strtolower($_FILES['csv_price']['name'],'UTF-8');
     $rows = file($_FILES['csv_price']['tmp_name']);
     $rows_count = sizeof($rows);
     for($i = 0; $i < $rows_count; $i++)
      {
        $tds = explode(";", $rows[$i]);
        if (is_array($tds)
	    //&& count($tds) == 4
	    )
         {
           $element_name = ''; if (isset($tds[0])) $element_name = iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[0])));
           $price = ''; if (isset($tds[1])) $price = intval(str_replace(',', '.', trim($tds[1])));
	   $producer_store_name = ''; if(isset($tds[2])) $producer_store_name = iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[2])));
           $store_name = ''; if (!isset($_POST['ignore_store_names']) && isset($tds[3])) $store_name = iconv('WINDOWS-1251', 'UTF-8', addslashes(trim($tds[3])));
           
	   if ($element_name || $price || $producer_store_name || $store_name)
	    {
	      if (isset($_POST['ignore_store_names']) && ($element_name == '' || $price == '')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype1"); exit();}
              if (!isset($_POST['ignore_store_names']) && ($store_name == '' || $element_name == '' || $price == '')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype2"); exit();}
              $result = mysql_query("insert into shop_prices_tmp values (null, '$store_name', '$producer_store_name', '$element_name', $price)");
              if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
	    }
         }
        else {Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfile3"); exit();}
      }
     $flags = ''; if (isset($_POST['ignore_store_names'])) $flags .= '?ignore_store_names=true';
     Header("Location: ".$_SERVER['PHP_SELF'].$flags); 
   } else $user->no_rules('add');
 }

if (isset($_GET['action']) && $_GET['action']!=='')
 {
  $action = $_GET['action'];
  if ($action == 'del' && isset($_GET['id']))
   {
     if ($user->check_user_rules('delete'))
      {
        $price_id = (int)$_GET['id'];
        mysql_query("delete from shop_prices_tmp where price_id = $price_id");
      } else $user->no_rules('delete');
   }
  if ($action == 'update')
   {
     if ($user->check_user_rules('action'))
      {
        $element_name_key = false;
        $price_key = false;
        if (isset($_POST['element_name'])) $element_name_key = true; else $element_name_key = false;
        if (isset($_POST['producer_store_name'])) $producer_store_name_key = true; else $producer_store_name_key = false;
        if (isset($_POST['price'])) $price_key = $_POST['price'];
        $parent_id = 0; if(isset($_POST['parent_id'])) $parent_id = $_POST['parent_id'];
        $producer_id = 0;  if (isset($_POST['producer_id'])) $producer_id = $_POST['producer_id'];
        $site = ''; if (isset($_POST['site'])) $site = $_POST['site'];
        $card_id = 0;  if (isset($_POST['card_id'])) $card_id = $_POST['card_id'];

        // Распределение данных
        $result = mysql_query("select * from shop_prices_tmp order by price_id asc");
        if (mysql_num_rows($result) > 0)
         {
           while ($row = mysql_fetch_array($result))
            {
              $store_name = $row['store_name'];
              $producer_store_name = $row['producer_store_name'];
              $element_name = $row['element_name'];
              $price = $row['price'];

              $new = !use_field($store_name, 'shop_cat_elements', 'store_name');

              if (!$new)
               {
                 if ($element_name_key) mysql_query("update shop_cat_elements set element_name = '$element_name' where store_name = '$store_name'");
                 if ($price_key) mysql_query("update shop_cat_elements set price$price_key = $price where store_name = '$store_name'");
                 if ($producer_store_name_key) mysql_query("update shop_cat_elements set producer_store_name = '$producer_store_name' where store_name = '$store_name'");
               }
              else
               {
                 mysql_query("insert into shop_cat_elements values (null,
			                                            $parent_id,
								    0,
								    0,
								    '$store_name',
								    '$producer_store_name',
								    '$element_name',
								    '',
								    '',
								    '',
								    '',
								    '',
								    0,
								    0,
								    0,
								    $price,
								    0,
								    0,
								    $producer_id,
								    0,
								    0)") or die (mysql_error());
                 $element_id = mysql_insert_id();

                 if (is_array($site))
                  {
                    foreach ($site as $site_id)
                      mysql_query("insert into shop_cat_element_sites values ($element_id, $site_id)");
                  }
                 if ($card_id !== 0)
                  {
                    mysql_query("insert into shop_cat_element_cards values ($element_id, $card_id)");
                  }
               }
            }
           mysql_query("truncate table shop_prices_tmp");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

           Header("Location: ".$_SERVER['PHP_SELF']."?message=added"); exit();
         }
      } else $user->no_rules('action');
   }
  if ($action == 'clear')
   {
     if ($user->check_user_rules('action'))
      {
        mysql_query("truncate table shop_prices_tmp");
        Header("Location: ".$_SERVER['PHP_SELF']); exit();
      } else $user->no_rules('action');
   }
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
 //собираем парные записи, если они есть
 $result = mysql_query("select * from shop_prices_tmp where store_name != '' order by price_id asc");
 $store_names = array();
 $alert = 0;
 if (mysql_num_rows($result) > 0)
  {
    while ($row = mysql_fetch_array($result))
     {
       if (array_key_exists($row['store_name'], $store_names))
         {
           $store_names[$row['store_name']]++;
           $alert = 1;
         }
       else $store_names[$row['store_name']] = 1;
     }
  } 

 $result = mysql_query("select * from shop_prices_tmp order by price_id asc");
 if (@mysql_num_rows($result) > 0)
 {

if (isset($_GET['status']))
 {
   $message = new Message;
   $message->add_message('codes', 'В csv-файле совпадают коды');
   $error->get_error_message($_GET['status']);
 }

if ($alert == 1)
 {
   $err = new Errors;
   echo $err->show_error_message('В csv-файле совпадают артикулы');
 }

 echo '<form action="" method="post"><p align="right"><button type="button" class="red" onclick="if(confirm(\'Вы действительно хотите очистить список?\')) location.href=\'?action=clear\';">Очистить список</button></p></form>';
 echo '<h2>Позиции для импорта</h2>
       <table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№</td>
         <td nowrap>Название</td>
         <td nowrap>Цена</td>
         <td nowrap>Арт. произв.</td>
         <td nowrap>Арт.</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 $new_positions = 0;
 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">';
   if (isset($_GET['ignore_store_names'])) $new = true;
   else $new = !use_field($row['store_name'], 'shop_cat_elements', 'store_name');
   if ($new) $new_positions++;

   if ($store_names[$row['store_name']] > 1)
    {
       echo '<td align="center"><strong class="red">'.$row['price_id'].'</strong></td>
             <td><strong class="red">'; if ($new) echo '<span class="green">новая позиция!</span> &nbsp; '; echo htmlspecialchars($row['element_name']).'</strong></td>
             <td align="center"><strong class="red">'.$row['price'].'</strong></td>
             <td>'; if ($row['producer_store_name']) echo '<strong class="red">'.htmlspecialchars($row['producer_store_name']).'</strong>'; else echo '&nbsp;'; echo '</td>
             <td>'; if ($row['store_name']) echo '<strong class="red">'.htmlspecialchars($row['store_name']).'</strong>'; else echo '&nbsp;'; echo '</td>';
    }
   else
    {
      echo '<td align="center">'.$row['price_id'].'</td>
            <td>'; if ($new) echo '<span class="green">новая позиция!</span> &nbsp; '; echo htmlspecialchars($row['element_name']).'</td>
            <td align="center">'.$row['price'].'</td>
            <td>'; if ($row['producer_store_name']) echo htmlspecialchars($row['producer_store_name']); else echo '&nbsp;'; echo '</td>
            <td>'; if ($row['store_name']) echo htmlspecialchars($row['store_name']); else echo '&nbsp;'; echo '</td>';
    }

   echo '  <td nowrap align="center">';
      echo '<a href="javascript:sw(\'/admin/editors/edit_prices_tmp.php?id='.$row['price_id'].'\');">
            <img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать запись"></a>
            &nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['price_id'].'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a>';

   echo '</td>
         </tr>'."\n";
  }
 echo '</table>'."\n";
 echo '<form action="" method="post"><p align="right"><button type="button" class="red" onclick="if(confirm(\'Вы действительно хотите очистить список?\')) location.href=\'?action=clear\';">Очистить список</button></p></form>';

echo '<form action="?action=update'; if (isset($_GET['ignore_store_names'])) echo '&ignore_store_names=true';  echo '" method="POST">';

//if (!isset($_GET))
echo '<h2>Обновить каталог</h2>';
echo '<table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Колонки для обновления / добавления<sup class="red">*</sup></td>
      <td>
        <table cellspacing="2" cellpadding="0" border="0">
          <tr><td><input type="radio" name="price" value="1" style="width: 16px; height: 16px;"></td><td>Цены 1</td></tr>
          <tr><td><input type="radio" name="price" value="2" style="width: 16px; height: 16px;" checked></td><td>Цены 2</td></tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr><td><input type="checkbox" name="element_name" style="width: 16px; height: 16px;"></td><td>Названия товаров</td></tr>
          <tr><td><input type="checkbox" name="producer_store_name" style="width: 16px; height: 16px;"></td><td>Артикулы производителей</td></tr>
        </table>
      </td>
    </tr>
   </table><br/>';

if ($new_positions > 0 || count($store_names) == 0)
{
echo '<h2>Добавить новые позиции в каталог</h2>
     <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Группа для новых позиций <sup class="red">*</sup><br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'').'
          </select>
      </td>
    </tr>
   <tr>
     <td>Производитель</td>
     <td>';
     $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
     if (mysql_num_rows($res) > 0)
      {
        echo '<select name="producer_id" style="width:280px;">
              <option value="0">---НЕТ---</option>';
        while ($r = mysql_fetch_array($res))
           echo '<option value="'.$r['producer_id'].'">'.htmlspecialchars($r['producer_name']).' &nbsp; '.htmlspecialchars($r['producer_descr']).'</option>';
      }
     else echo 'Нет производителей';
   echo '</td>
   </tr>
    <tr>
      <td>Сайты, на которых будет отображаться новый товар</td>
      <td>';

      $result = mysql_query("select * from shop_cat_sites order by site_name asc");
      if (mysql_num_rows($result) > 0)
       {
         echo '<table cellspacing="0" cellpadding="2" border="0">';
         while ($row = mysql_fetch_array($result))
          {
            echo '<tr>
                    <td><input type="checkbox" name="site[]" value="'.$row['site_id'].'" style="width: 16px; height: 16px;"></td>
                    <td>'.htmlspecialchars($row['site_name']).'</td>
                  </tr>';
          }
         echo '</table>';
       }
      else echo 'Нет сайтов';

 echo '</td>
    </tr>
    <tr>
      <td>Карточки описаний товара</td>
      <td>';
      $result = mysql_query("select * from shop_cat_cards order by card_name asc");
      if (mysql_num_rows($result) > 0)
       {
         echo '<select style="width:280px;" name="card_id">
                <option value="">Выберите карточку...</option>';
         while ($row = mysql_fetch_array($result))
           echo '<option value="'.$row['card_id'].'">'.htmlspecialchars($row['card_name']).'</option>';
         echo '</select>';
       }
      else echo 'Нет карточек';
  echo '</td>
    </tr>
  </table><br/>';
}
echo '<button type="submit"'; if ($alert == 1) echo ' disabled'; echo '>Импортировать в каталог</button></form>';

  }

else
 {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('added', 'Позиции каталога обновлены');
   $message->get_message($_GET['message']);
 }

 echo '<h2>Импортировать прайс-лист / загрузить новые товары</h2>';
 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Файл <span class="red">*</span></td>
      <td><input style="width:280px" type="file" name="csv_price"></td>
    </tr>
  </table>
  <div>&nbsp;</div>
  <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td><input type="checkbox" name="ignore_store_names" checked></td>
      <td style="padding-left: 4px;">Игнорировать артикулы</td>
    </tr>
  </table>
  <div>&nbsp;</div><button type="submit">Импортировать</button></form>';

 echo '<fieldset>
        <legend>Внимание</legend>
        Требования к файлу:
          <ul>
            <li>Поля: "Название товара", "Цена", "Артикул производителя", "Артикул".</li>
            <li>Разделитель между столбцами: `;`</li>
            <li>При установленной галочке "игорировать артикулы" он будет назначаться автоматически при конечном импорте.</li>
            <li>Артикул производителя может быть пустым полем</li>
          </ul>
        После импорта файла у вас будет возможность отредактировать полученный список. Выбрать ценовую колонку.
       </fieldset>';

 }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>