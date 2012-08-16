<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['option_name']) &&
   isset($_POST['option_descr']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['option_name'])=='' || trim($_POST['option_type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $option_id = (int)$_GET['id'];
   $option_name = mysql_real_escape_string(trim($_POST['option_name']));
   $option_descr = mysql_real_escape_string(trim($_POST['option_descr']));
   $option_type = ((isset($_POST['option_type']) && (int)$_POST['option_type'] > 0) ? (int)$_POST['option_type'] : 0);
   $option_interface = ((isset($_POST['option_interface']) && trim($_POST['option_type']) !== '') ? $_POST['option_interface'] : '');
   $unit_id = ((isset($_POST['unit_id']) && (int)$_POST['unit_id'] > 0) ? (int)$_POST['unit_id'] : 0);

   $result = mysql_query("select * from shop_cat_options where option_name = '".stripslashes($option_name)."' and option_id!=$option_id");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=duplicate"); exit();}

   if ($option_type !== 6)
    {
      $result = mysql_query("update shop_cat_options set option_array = '' where option_id = $option_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=db"); exit();}  
    }

   //Обновляем содержимое...
   $result = mysql_query("update
                          shop_cat_options
                          set
                          option_name = '$option_name',
                          option_descr = '$option_descr',
                          option_type = $option_type,
                          option_interface = '$option_interface',
                          unit_id = $unit_id
                          where option_id = $option_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=db"); exit();}

   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id");
   exit();
  } else $user->no_rules('edit');
 }

if (isset($_POST['array_values']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
    $option_id = (int)$_GET['id'];
 
    //повторы
    foreach($_POST['array_values']['name'] as $id => $name)
     {
       $key = 0;
       foreach($_POST['array_values']['name'] as $id_c => $name_c)
          if ($name == $name_c && trim($name) !== '' && trim($name_c) !== '') $key++;
       if ($key == 2) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=duplicate2"); exit();}
     }

    foreach($_POST['array_values']['order'] as $id => $order)
     {
       $key = 0;
       foreach($_POST['array_values']['order'] as $id_c => $order_c)
          if ($order == $order_c && trim($order) !== '' && trim($order_c) !== '') $key++;
       if ($key == 2) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=duplicate2"); exit();}
     }

    $option_array = array();

    foreach($_POST['array_values']['order'] as $id => $order)
        $option_array[$order] = array($id);
    
    foreach($_POST['array_values']['name'] as $id => $name)
        foreach($option_array as $order => $value)
           if (in_array($id, $value)) $option_array[$order] = array($id, $name);
           
    foreach($option_array as $order => $value)
        if(!$value[1] || !trim($value[1])) unset($option_array[$order]);

    //Обновляем содержимое...
    $result = mysql_query("update shop_cat_options set option_array = '".serialize($option_array)."' where option_id = $option_id");
    if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=db2"); exit();}

    $_SESSION['smart_tools_refresh'] = 'enable';
    Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id");
    exit();
   } else $user->no_rules('edit');
  }

if (isset($_POST['array_element']) &&
   isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   if (trim($_POST['array_element']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues3"); exit();}

   $option_id = (int)$_GET['id'];
   $array_element = mysql_real_escape_string(trim($_POST['array_element']));

   $option_array = array();
   $max_order = 0;
   $max_id = 0;
   
   $result = mysql_query("select * from shop_cat_options where option_id = $option_id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $option_array = unserialize($row['option_array']);
      foreach($option_array as  $e_order => $value)
       {
         list($e_id, $e_name) = $value;
         if ($e_name == $array_element) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=duplicate3"); exit();}
         if ($e_id > $max_id) $max_id = $e_id; 
         if ($e_order > $max_order) $max_order = $e_order; 
       }
    }

   $option_array[$max_order+1] = array($max_id+1, $array_element);
   ksort($option_array);
   
   //Обновляем содержимое...
   $result = mysql_query("update shop_cat_options set option_array = '".serialize($option_array)."' where option_id = $option_id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id&message=db3"); exit();}

    $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$option_id");
   exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $result = mysql_query("select * from shop_cat_options where option_id = ".(int)$_GET['id']);
   $row = mysql_fetch_object($result);
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.(int)$_GET['id'].'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="option_name" value="'.htmlspecialchars($row->option_name).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="option_descr" value="'.htmlspecialchars($row->option_descr).'" maxlength="255"></td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <select style="width:280px;" name="option_type">
          <option value="">Выберите тип параметра...</option>
          <option value="1"'; if ($row->option_type == 1) echo ' selected'; echo '>INT (целое число)</option>
          <option value="2"'; if ($row->option_type == 2) echo ' selected'; echo '>DOUBLE (число с плавающей точкой)</option>
          <option value="3"'; if ($row->option_type == 3) echo ' selected'; echo '>BOOLEAN (да/нет)</option>
          <option value="4"'; if ($row->option_type == 4) echo ' selected'; echo '>CHAR (строка)</option>
          <option value="5"'; if ($row->option_type == 5) echo ' selected'; echo '>TEXT (текст)</option>
          <option value="6"'; if ($row->option_type == 6) echo ' selected'; echo '>ARRAY (справочник)</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Интерфейс <sup class="red">*</sup></td>
      <td>
        <select style="width:280px;" name="option_interface">
          <option value="">Выберите интерфейс...</option>
          <option value="select"'; if ($row->option_interface == 1) echo ' selected'; echo '>SELECT (выпадающий список)</option>
          <option value="radio"'; if ($row->option_interface == 3) echo ' selected'; echo '>RADIO (радиокнопка)</option>
          <option value="checkbox"'; if ($row->option_interface == 4) echo ' selected'; echo '>CHECKBOX (галочка)</option>
          <option value="date"'; if ($row->option_interface == 5) echo ' selected'; echo '>DATE (дата)</option>
          <option value="interval"'; if ($row->option_interface == 5) echo ' selected'; echo '>INTERVAL (интервальное значение)</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Единица измерения</td>
      <td><select name="unit_id" style="width:280px;">
              <option value="0">---НЕТ---</option>';
      $res = mysql_query("select * from shop_units_of_measure order by unit_name asc");
      if (mysql_num_rows($res) > 0)
        while ($r = mysql_fetch_array($res))
          echo '<option value="'.$r['unit_id'].'" '.(($row->unit_id == $r['unit_id']) ? ' selected' : '').'>'.htmlspecialchars($r['unit_name']).(($r['unit_descr']) ? ' &nbsp; ('.htmlspecialchars($r['unit_descr']).')' : '').'</option>';
    echo '</td>
    </tr> 
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  
  if ($row->option_type == 6)
   {
     $values = ''; if($row->option_array !== '' && is_array(unserialize($row->option_array))) $values = unserialize($row->option_array);
     echo '<h2>Редактировать справочник</h2>';
     
     if (is_array($values) && count($values) > 0)
      {
        if (isset($_GET['messsage']))
         {
           $message2 = new Message;
           $message2->copy_message('formvalues', 'formvalues2');
           $message2->copy_message('db', 'db2');
           $message2->copy_message('duplicate', 'duplicate2');
           $message2->get_message($_GET['message']);
         }
        
        echo '<form action="?id='.(int)$_GET['id'].'" method="post">
              <table cellpadding="2" cellspacing="1" class="form">
               <tr class="header">
                 <td>Название</td>
                 <td>Порядок</td>
                 <td>&nbsp;</td>               
               </tr>';
                 
        $i = 1;
        ksort($values);
        foreach($values as $order => $value)
         {
           list($id, $name) = $value;
           echo '<tr>
                   <td><input type="text" style="width: 280px;" name="array_values[name]['.$id.']" value="'.htmlspecialchars($name).'"></td>
                   <td><input type="text" name="array_values[order]['.$id.']" value="'.$order.'" style="width: 30px;"></td>';
           
           echo '<td>';
           if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
           else echo '<a href="?id='.(int)$_GET['id'].'&eid='.$id.'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';
           if ($i == count($values)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
           else echo '<a href="?id='.(int)$_GET['id'].'&eid='.$id.'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';
           echo '<a href="#" onclick="if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?id='.(int)$_GET['id'].'&eid='.$id.'&action=delete\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a>';
           echo '</td></tr>';
           $i++;
         }
        
        echo '</table><br><button type="SUBMIT">Сохранить</button></form>';
      }

     if (isset($_GET['message']))
      {
        $message3 = new Message;
        $message3->copy_message('formvalues', 'formvalues3');
        $message3->copy_message('db', 'db3');
        $message3->copy_message('duplicate', 'duplicate3');
        $message3->get_message($_GET['message']);
      }
     
     echo '<h3>Дабавить элемент в справочник</h3>
           <form action="?id='.(int)$_GET['id'].'" method="post">
           <table cellpadding="4" cellspacing="1" border="0" class="form_light">
            <tr>
              <td><input type="text" name="array_element" style="width: 280px;"></td>
              <td><button type="submit">OK</button></td>
            </tr>
           </table>
           </form>';  
   }
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>