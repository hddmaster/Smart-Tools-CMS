<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['groups']))
 {
 if ($user->check_user_rules('edit'))
  {
     function add_parents($element_id, &$groups)
     {
        $result = mysql_query("select parent_id from shop_cat_elements where element_id = $element_id and parent_id != 0");
        if (mysql_num_rows($result) > 0)
         {
           $row = mysql_fetch_array($result);

           $key = true;
           foreach ($groups as $value)
            {
              if ($value == $row['parent_id']) $key = false;
            }
           if ($key) $groups[] = $row['parent_id'];

           add_parents($row['parent_id'], $groups);          
         }
      }

    $groups = $_POST['groups'];
    foreach ($groups as $value) add_parents($value, $groups);

    //удаляем выколотые элементы из отсутствующих групп
    $yml_shop_na_elements = unserialize($user->get_cms_option('yml_shop_na_elements'));
    foreach ($yml_shop_na_elements as $id => $element)
     {
       $res = mysql_query("select parent_id from shop_cat_elements where element_id = $element");
       if (mysql_num_rows($res) > 0)
        {
          $r = mysql_fetch_array($res);
          $parent_id = $r['parent_id'];

          $key = true;
          foreach ($groups as $group) if ($parent_id == $group) $key = false;
          if ($key) unset($yml_shop_na_elements[$id]);
        }
     }
    mysql_query("update cms_options set setting_text_value = '".serialize($yml_shop_na_elements)."' where option_sname = 'yml_shop_na_elements'");

    $result = mysql_query("select * from cms_options where option_sname = 'yml_shop_categories'");
    if (mysql_num_rows($result) > 0) mysql_query("update cms_options set setting_text_value = '".serialize($groups)."' where option_sname = 'yml_shop_categories'");
    Header("Location: ".$_SERVER['PHP_SELF']);
    exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['elements']) && isset($_GET['group_id']))
 {
 if ($user->check_user_rules('edit'))
  {
    $elements = $_POST['elements'];
    $na_elements = array();
    $a_elements = array();
    $group_id = $_GET['group_id'];
    
    $result = mysql_query("select * from shop_cat_elements where parent_id = $group_id and type = 0 order by order_id asc");
    if (mysql_num_rows($result) > 0)
     {
       $i = 0;
       while ($row = mysql_fetch_array($result))
        {
          if ($row['element_id'] !== $elements[$i]) $na_elements[] = $row['element_id'];
          else {$a_elements[] = $row['element_id']; $i++;}
        }
     }

    if (is_array(unserialize($user->get_cms_option('yml_shop_na_elements')))) $yml_shop_na_elements = unserialize($user->get_cms_option('yml_shop_na_elements'));
    else $yml_shop_na_elements = array();

    //добавляем в "выколотые элементы"
    foreach($na_elements as $post_na_element)
     {
       //проверка на наличие в конфигурации
       $key = true;
       if (count($yml_shop_na_elements) > 0)
        {
          foreach($yml_shop_na_elements as $na_element)
           if ($na_element == $post_na_element) $key = false;
        }

       if ($key) { $yml_shop_na_elements[] = $post_na_element;}
     }

    //убираем из "выколотых элементов"
    foreach($a_elements as $post_a_element)
     {
       if (count($yml_shop_na_elements) > 0)
        {
          foreach($yml_shop_na_elements as $id => $na_element)
           if ($na_element == $post_a_element) unset($yml_shop_na_elements[$id]);
        }
     }

    mysql_query("update cms_options set setting_text_value = '".serialize($yml_shop_na_elements)."' where option_sname = 'yml_shop_na_elements'");
    Header("Location: ".$_SERVER['PHP_SELF']);
    exit();

  } else $user->no_rules('edit');
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

function show_tree($parent_id = 0, &$groups)
  {
    $result = mysql_query("SELECT * FROM shop_cat_elements where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          if (is_begin($row['element_id'], $row['parent_id'])) echo '<dl>'."\n";

          echo '<dd><input type="checkbox" style="width:16px;height:16px;" name="groups[]" value="'.$row['element_id'].'"';
          foreach ($groups as $value) if ($value == $row['element_id']) echo ' checked';
          echo '> '.htmlspecialchars($row['element_name']).'</dd>'."\n";

          show_tree($row['element_id'], $groups);

          if (is_end($row['element_id'], $row['parent_id'])) echo '</dl>'."\n";
        }
    }
  }

function show_select($parent_id = 0, &$groups, $prefix = '')
  {
    $result = mysql_query("SELECT * FROM shop_cat_elements where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          foreach ($groups as $value) if ($value == $row['element_id'])
           {
             echo '<option value="'.$row['element_id'].'"';
             if (isset($_GET['group_id']) && $_GET['group_id'] == $row['element_id']) echo ' selected';
             echo '>'.$prefix.htmlspecialchars($row['element_name']).'</option>'."\n";
           }

          show_select($row['element_id'], $groups, $prefix.'&nbsp;&nbsp;&nbsp;');
        }
    }
  }

function is_begin($element_id, $parent_id)
 {
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id and type = 1 order by order_id asc");
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
   $result = mysql_query("select * from shop_cat_elements where parent_id = $parent_id and type = 1 order by order_id asc");
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

 $groups =  array(); if ($user->get_cms_option('yml_shop_categories')) $groups = unserialize($user->get_cms_option('yml_shop_categories'));
 $na_elements = array(); if ($user->get_cms_option('yml_shop_na_elements')) $na_elements = unserialize($user->get_cms_option('yml_shop_na_elements'));

 $add = '';
 if ($user->get_cms_option('yml_hidden_goods') == 0) $add = 'and status = 1';
 $error_elements = 0;
 foreach ($groups as $value)
  {
    $result = mysql_query("select count(*) from shop_cat_elements where type = 0 and parent_id = $value and producer_id = 0 $add order by order_id asc");
    if (mysql_num_rows($result) > 0)
     {
       $row = mysql_fetch_array($result);
       $error_elements += $row[0];
     }
  }

 if ($error_elements > 0)
  {
    echo '<span class="red"><strong>Внимание!</strong> Найдены товары, отмеченные для экспорта, без производителей. По условиям Яндекс.Маркета такие товары не могут представлены в каталоге.</span><br/>';
/*
    $res = mysql_query("select element_id from shop_cat_elements where producer_id=0 and type=0");
    if (mysql_num_rows($res) > 0)
     {
       $i = 1;
       echo '<div class="small"><strong>Идентификаторы всех товаров без производителя:</strong> ';
       while ($r = mysql_fetch_array($res))
        {
          echo '<a href="javascript:sw(\'/admin/editors/edit_shop_cat.php?id='.$r['element_id'].'\');">'.$r['element_id'].'</a>';
          if ($i < mysql_num_rows($res)) echo ', ';
          $i++;
        }
       echo '</div><div class="small" style="margin-top: 6px;">* Для редактирования товаров щелкните мышкой но нужным номерам.</div>'; 
     }
*/
  }
   
 echo '<table cellspacing="0" cellpadding="0" width="100%"><tr valign="top"><td width="50%">
       <h2>Группы для экспорта</h2>
       <div style="padding:10px; border: #CCCCCC 1px solid;">
       <form action="" method="POST">
       <button type="submit">Сохранить</button><br/>';
       show_tree(0, $groups);
 echo '<br /><button type="submit">Сохранить</button></form></div></td>
       <td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td>
       <td width="50%">
       <h2>Редактирование выбранных групп</h2>
       <div style="padding:10px; border: #CCCCCC 1px solid;">
       <form action="" method="GET">
       <select name="group_id" style="width:280px;">
         <option value="">Выберите группу...</option>
         <option value="0">---Корень каталога---</option>';
       show_select(0, $groups, '');
 echo '</select><button type="submit">OK</button></form>';

 if (isset($_GET['group_id']))
  {
    $group_id = $_GET['group_id'];
    $result = mysql_query("select * from shop_cat_elements where parent_id = $group_id and type = 0 order by order_id asc");
    if (mysql_num_rows($result) > 0)
     {
       echo '<br /><form action="?group_id='.$group_id.'" method="POST">
             <button type="submit">Сохранить</button><br/><br />';
       while($row = mysql_fetch_array($result))
        {
          echo '<dd><input type="checkbox" style="width:16px;height:16px;" name="elements[]" value="'.$row['element_id'].'"';

          $key = true;
          foreach ($na_elements as $value) if ($value == $row['element_id']) $key = false;
          if ($key) echo ' checked';

          echo '> '.htmlspecialchars($row['element_name']).' <span class="grey">(id: '.$row['element_id'].'; art: '.htmlspecialchars($row['store_name']).')</span></dd>'."\n";
        }
       echo '<br /><br /><button type="submit">Сохранить</button></form>';
    }
   else
    echo '<p>Товаров не найдено</p>';
  }

 echo '</div></td></table>';

 $add = '';
 if ($user->get_cms_option('yml_hidden_goods') && $user->get_cms_option('yml_hidden_goods') == 0) $add = 'and status = 1';
 $result = mysql_query("select count(*) from shop_cat_elements where type = 0 $add");
 $row = mysql_fetch_array($result);
 echo '<p>Товаров для экспорта: <strong>'.$row[0].'</strong>';

 $checked_elements = 0;
 foreach ($groups as $value)
  {
    $result = mysql_query("select count(*) from shop_cat_elements where type = 0 and parent_id = $value $add order by order_id asc");
    if (mysql_num_rows($result) > 0)
     {
       $row = mysql_fetch_array($result);
       $checked_elements += $row[0];
     }
  }

 $checked_elements -= count($na_elements);

 echo '<br/>Отмечено для экспорта: <strong>'.$checked_elements.'</strong></p>';

 echo '<fieldset>
         <legend>Внимание</legend>
         Для экспорта товаров необходимо отметить нужные группы, затем можно отметить экспортируемые товары в группах. В редактируемой группе должен быть хотя бы один отмеченный товар.
       </fieldset>';



 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>