<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['variant']) && isset($_GET['id']) && $_GET['id'] !== '')
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['variant'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $variant = trim($_POST['variant']);
   $poll_id = (int)$_GET['id'];
   
   // Проверка на повторы
   $result = mysql_query("select * from polls_results where poll_id = $poll_id and variant_name = '$variant'");
   if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id&message=duplicate_res");exit();}

   $result = mysql_query("insert into polls_results values (null, 0, '$variant', $poll_id, 0, 0)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id&message=db");exit();}

   // перенумеровываем
   $result = mysql_query("select * from polls_results where poll_id = $poll_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['variant_id'];
         mysql_query("update polls_results set order_id=$i where variant_id=$id");
         $i++;
       }
    }
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id");
   exit();
  } else $user->no_rules('add');
 }

if (isset($_POST['question']) &&
   isset($_POST['date1']) &&
   isset($_POST['date2']) &&
   isset($_GET['id']) && isset($_POST['variants']))
 {
 if ($user->check_user_rules('edit'))
  {
   $poll_id = (int)$_GET['id'];
   if (trim($_POST['question'])=='' ||
       trim($_POST['date1'])=='' ||
       trim($_POST['date2'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");exit();}

   $date1 = substr($_POST['date1'],6,4).substr($_POST['date1'],3,2).substr($_POST['date1'],0,2);
   $date2 = substr($_POST['date2'],6,4).substr($_POST['date2'],3,2).substr($_POST['date2'],0,2);

//проверка промежутка времени
   $date_b = intval($date1);
   $date_e = intval($date2);
   if ($date_b > $date_e) {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=date");exit();}

   $question = $_POST['question'];
   $variants = $_POST['variants'];

//проверка на наличие элементов: вариантов ответов
   if(count($variants) == 0)
    {
      Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
      exit();
    }

//проверка на пустые поля вариантов ответов
   foreach ($variants as $key)
    {
     if($key == ''){Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");exit();}
    }

//проверка на повторные записи в списке ответов
   $temp_array = $variants;
   foreach ($temp_array as $key)
    {
      $i = 0;
      foreach($variants as $current)
       {
         if($key == $current) $i++;
         if($i > 1){Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=duplicate_res");exit();}
       }
    }

   //Ищем повторы в бд названия опросов
   $result = mysql_query("select * from polls_names where question='$question' and poll_id !== $poll_id");
   if ($row = mysql_num_rows($result) > 0){Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=duplicate"); exit();}

//Обновляем содержимое...--------------------------------------------------------

  //Вопросы
  $result = mysql_query("update polls_names set question='$question',date1='$date1',date2='$date2' where poll_id=$poll_id");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id&message=db"); exit();}

  //Обновляем названия ответов по id в бд, для новых при совпадении id (переименование)
  foreach($variants as $id => $name)
   {
     mysql_query("update polls_results set variant_name='$name' where variant_id=$id");
   }

//сортируем итоговый спиок

  $i = 1;
  foreach ($variants as $id => $name)
   {
    mysql_query("update polls_results set order_id=$i where variant_id=$id");
    $i++;
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id");
  exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['action']) && $_GET['action']!=='' && isset($_GET['variant']) && $_GET['variant']!=='' && isset($_GET['id']) && $_GET['id']!=='')
 {
   $poll_id = (int)$_GET['id'];
   $variant_id = $_GET['variant'];
   $action = $_GET['action'];

   if ($action == 'up')
   {
    if ($user->check_user_rules('action'))
     {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from polls_results where poll_id = $poll_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['variant_id'];
           mysql_query("update polls_results set order_id = $order where variant_id = $vid");
           $values[$order] = $vid;
           if ($vid == $variant_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update polls_results set order_id = '.($old_order-1).' where variant_id = '.$values[$old_order];
        //для предыдущего
        $q2 = 'update polls_results set order_id = '.$old_order.' where variant_id = '.$values[$old_order-1];
        mysql_query($q1);mysql_query($q2);
      }
     } else $user->no_rules('action');
   }
  if ($action == 'down')
   {
    if ($user->check_user_rules('action'))
      {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from polls_results where poll_id = $poll_id order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $vid = $row['variant_id'];
           mysql_query("update polls_result set order_id = $order where variant_id = $vid");
           $values[$order] = $vid;
           if ($vid == $variant_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update polls_results set order_id = '.($old_order+1).' where variant_id = '.$values[$old_order];
        //для следующего
        $q2 = 'update polls_results set order_id = '.$old_order.' where variant_id = '.$values[$old_order+1];
        mysql_query($q1);mysql_query($q2);
     }
    } else $user->no_rules('action');
   }

  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
        mysql_query("delete from polls_results where variant_id = $variant_id");
        $_SESSION['smart_tools_refresh'] = 'enable';
      }
     else $user->no_rules('delete');
   }

  }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $poll_id = (int)$_GET['id'];
   $query = "select *,date_format(date1, '%d.%m.%Y') as date1,date_format(date2, '%d.%m.%Y') as date2 from polls_names where poll_id=$poll_id";
   $result = mysql_query($query);
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $question = $row['question'];
   $date1 = $row['date1'];
   $date2 = $row['date2'];

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_polls.php')) $tabs->add_tab('/admin/editors/edit_polls.php?id='.$poll_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/show_poll_res.php')) $tabs->add_tab('/admin/editors/show_poll_res.php?id='.$poll_id, 'Отчет');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('duplicate_res', 'Такие ответы уже есть в этом опросе');
   $message->add_message('date', 'Даты опроса заданы некорректно');
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$poll_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Вопрос <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="question" value="'.htmlspecialchars($question).'" maxlength="255"></td></tr>
    <tr>
      <td>Даты <sup class="red">*</sup></td>
      <td>';
?>
<table cellspacing="0" cellpadding="0">
 <tr>
  <td>с&nbsp;</td>
  <td>

    <script>
      LSCalendars["date1"]=new LSCalendar();
      LSCalendars["date1"].SetFormat("dd.mm.yyyy");
      LSCalendars["date1"].SetDate("<?=$date1?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date1', event); return false;" style="width: 65px;" value="<?=$date1?>" name="date1"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date1', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="datePtr" style="width: 1px; height: 1px;"></div>


   </td>
   <td>&nbsp;&nbsp;по&nbsp;</td>
   <td>

    <script>
      LSCalendars["date2"]=new LSCalendar();
      LSCalendars["date2"].SetFormat("dd.mm.yyyy");
      LSCalendars["date2"].SetDate("<?=$date2?>");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date2', event); return false;" style="width: 65px;" value="<?=$date2?>" name="date2"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement('date2', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="date2Ptr" style="width: 1px; height: 1px;"></div>

   </td></tr></table>
<?
echo'</td>
    </tr>
    <tr>
      <td>Ответы</td>
      <td>';

   $result = mysql_query("select * from polls_results where poll_id=$poll_id order by order_id");
   $i = 1;
   if (@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         echo '<input style="width:280px" type="text" name="variants['.$row['variant_id'].']" value="'.$row['variant_name'].'" maxlength="255">&nbsp;';

         //если элемент первый на определенном уровне, блокируем стрелку "вверх"
         if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
         else echo '<a href="?id='.$poll_id.'&variant='.$row['variant_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';

         if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
         else echo '<a href="?id='.$poll_id.'&variant='.$row['variant_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';

         echo '<a href="';
         echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?id=$poll_id&variant=".$row['variant_id']."&action=delete';}";
         echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';

         $i++;
       }
    }
   else
     echo 'Нет вариантов ответов';

echo'      </td>
    </tr>
   </table><br>
   <table cellpadding="0" cellspacing="0" border="0">
    <tr>
	  <td><button type="SUBMIT">Сохранить</button></td>
	  <td><img src="/images/px.gif" alt="" width="10" height="1"></td>
	  <td><button onclick="javascript:sw(\'/admin/editors/edit_polls_res.php?id='.$poll_id.'\');">Редактировать результаты</button></td>
	</tr>
   </table></form>';

echo '<h2>Добавить ответ</h2>';

echo '  <form action="?id='.$poll_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Ответ</td>
      <td>
       <input style="width:280px" type="text" name="variant" maxlength="255">
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form>';
  } else $user->no_rules('view');

 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>