<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['option_values']) && isset($_GET['id'])) {
 if ($user->check_user_rules('edit')) {
    $element_id = (int)$_GET['id'];
    
    foreach ($_POST['option_values'] as $card_id => $options)
     {
       $card_id = (int)$card_id;
       foreach ($options as $option_id => $option_value)
        {
          $query = '';
          $option_id = (int)$option_id;
          if ($option_value !== '')
           {
             $result = mysql_query("select option_type from shop_cat_options where option_id = ".$option_id);
             if (mysql_num_rows($result)) {
                $row = mysql_fetch_object($result);
                switch ($row->option_type) {
                   case 1: $field = 'option_int_value'; $option_value = (int)$option_value; break;
                   case 2: $field = 'option_double_value'; $option_value = (double)$option_value; break;
                   case 3: $field = 'option_boolean_value'; $option_value = (int)$option_value; break;
                   case 4: $field = 'option_char_value'; $option_value = mysql_real_escape_string(trim($option_value)); break;
                   case 5: $field = 'option_text_value'; $option_value = mysql_real_escape_string(trim($option_value)); break;
                   case 6: $field = 'option_text_value'; $option_value = serialize($option_value); break;
                 }
                 
                $res = mysql_query("select * from shop_cat_option_values where element_id = $element_id and card_id = $card_id and option_id = $option_id");
                if (mysql_num_rows($res) > 0) $query = "update shop_cat_option_values set $field = '$option_value' where element_id = $element_id and card_id = $card_id and option_id = $option_id";
                else $query = "insert into shop_cat_option_values (element_id, card_id, option_id, $field) values ($element_id, $card_id, $option_id, '$option_value')";
              }
           }
          else $query = "delete from shop_cat_option_values where element_id = $element_id and card_id = $card_id and option_id = $option_id";
          //echo $query.'<br />';
          if ($query) mysql_query($query);
        }
     }

    //exit();
    //Обновление кэша связанных модулей на сайте
    $cache = new Cache; $cache->clear_cache_by_module();
    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id"); exit();
  } else $user->no_rules('edit');
 }

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

 if ($file1 || $file2 || $file3) echo '<p>';
 if ($file1) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file1).'" border="0"> &nbsp;';
 if ($file2) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file2).'" border="0"> &nbsp;';
 if ($file3) echo '<img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($file3).'" border="0">';
 if ($file1 || $file2 || $file3) echo '</p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat.php')) $tabs->add_tab('/admin/editors/edit_shop_cat.php?id='.$element_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_card_values.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_card_values.php?id='.$element_id, 'Карточки описаний');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_gallery.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_gallery.php?id='.$element_id, 'Фотогалерея');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_files.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_files.php?id='.$element_id, 'Файлы');
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_on_map.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_on_map.php?id='.$element_id, 'Расположение на карте');
$tabs->show_tabs();

 echo '<form action="?id='.$element_id.'" method="post">';
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
       echo '<h2>'.htmlspecialchars($row['card_name']).'</h2>';
       echo '<table cellpadding="4" cellspacing="1" width="100%" class="form">';

       $card_id = $row['card_id'];
       $res = mysql_query("SELECT
                           O.*,
                           U.unit_name,
                           U.unit_descr
                           FROM shop_cat_options as O LEFT JOIN shop_cat_card_options as CO ON O.option_id = CO.option_id
                                                      LEFT JOIN shop_units_of_measure as U ON O.unit_id = U.unit_id
                           WHERE CO.card_id = $card_id
                           ORDER by CO.order_id asc") or die(mysql_error());
       if (mysql_num_rows($res) > 0)
        {
          while ($r = mysql_fetch_array($res))
           {
             $option_id = (int)$r['option_id'];
             $option_type = (int)$r['option_type'];
             $option_array = unserialize($r['option_array']);
             echo '<tr><td width="20%"><strong>'.htmlspecialchars($r['option_name']).'</strong>'.($r['unit_name'] ? ', '.htmlspecialchars($r['unit_name']) : '').($r['unit_descr'] ? ' <span class="small grey">('.htmlspecialchars($r['unit_descr']).')</span>' : '').'</td><td width="80%">';
             
             $value = '';
             $res_value = mysql_query("select * from shop_cat_option_values where element_id = $element_id and card_id = $card_id and option_id = $option_id");
             if (mysql_num_rows($res_value) > 0) {
             $res_row = mysql_fetch_array($res_value);
             switch ($option_type) {
                case 1: $value = $res_row['option_int_value']; break;
                case 2: $value = $res_row['option_double_value']; break;
                case 3: $value = $res_row['option_boolean_value']; break;
                case 4: $value = $res_row['option_char_value']; break;
                case 5: $value = $res_row['option_text_value']; break;
                case 6: $value = unserialize($res_row['option_text_value']); break;
             }
             }
              
            if ($option_type < 6)
             {
               if ($option_type !== 5)
                {
                  if ($option_type == 1 || $option_type == 2)
                  echo '<input type="text" style="width:100px;" name="option_values['.$card_id.']['.$option_id.']" value="'.htmlspecialchars($value).'">';

                  if ($option_type == 4)
                  echo '<input type="text" style="width:100%;" name="option_values['.$card_id.']['.$option_id.']" value="'.htmlspecialchars($value).'">';

                  if ($option_type == 3)
                   {
                     echo '<table cellspacing="0" cellpadding="0">
                            <tr>
                              <td><input type="radio" name="option_values['.$card_id.']['.$option_id.']" value="1"'; if ($value == 1) echo ' checked'; echo '></td>
                              <td>Да &nbsp;</td>
                              <td><input type="radio" name="option_values['.$card_id.']['.$option_id.']" value="0"'; if ($value == 0) echo ' checked'; echo '></td>
                              <td>Нет &nbsp;</td>
                            </tr>
                           </table>';
                   }
                }
              
               if ($option_type == 5)
                {
                  $oFCKeditor = new FCKeditor('option_values['.$card_id.']['.$option_id.']') ;
                  $oFCKeditor->BasePath = '/admin/fckeditor/';
                  $oFCKeditor->ToolbarSet = 'Minimal' ;
                  $oFCKeditor->Value = $value;
                  $oFCKeditor->Width  = '100%' ;
                  $oFCKeditor->Height = '150' ;
                  $oFCKeditor->Create() ;
                }
             }
           
            if ($option_type == 6)
             {
               if (count($option_array))
                {
                  echo '<select style="width: 50%;" name="option_values['.$card_id.']['.$option_id.'][]" multiple size="3"><option value="0">---НЕТ---</option>';
                  foreach($option_array as $order => $val)
                   {
                     list($e_id, $e_name) = $val;
                     echo '<option value="'.$e_id.'"'.(in_array($e_id, $value) ? ' selected' : '').'>'.htmlspecialchars($e_name).'</option>';
                   }
                  echo '</select>';
                }
             }
            echo '</td><td class="small" nowrap>';
            
            switch($r['option_type'])
             {
               case 1: echo 'целое число'; break;
               case 2: echo 'число с плавающей точкой'; break;
               case 3: echo 'да/нет'; break;
               case 4: echo 'строка'; break;
               case 5: echo 'текст'; break;
               case 6: echo 'выбор нескольких значений'; break;
             }
            
            echo '</td></tr>';
          }
        }
       else echo '<tr><td>Нет характеристик в карточке описания</td></tr>';
      
       echo '</table>
             <div>&nbsp;</div>
             <button type="SUBMIT">Сохранить</button>
             <div>&nbsp;</div><div>&nbsp;</div>';
     }
  }
 else echo '<p align="center">Нет карточек</p>';
 echo '</form>';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>