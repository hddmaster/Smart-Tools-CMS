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

   echo '<h2>'.htmlspecialchars($question).'</h2>';

   $total = 0;
   $result = mysql_query("select * from polls_results where poll_id=$poll_id order by order_id");
   if (mysql_num_rows($result) > 0)
      while ($row = mysql_fetch_array($result))
         $total += $row['value'];

   $result = mysql_query("select * from polls_results where poll_id=$poll_id order by order_id");
   if (mysql_num_rows($result) > 0)
    {
      echo '<table cellspacing="1" cellpadding="8">';
      echo '<tr align="center">
              <td style="border-bottom: #CCCCCC 2px solid;"><strong>Вопрос</strong></td>
              <td style="border-bottom: #CCCCCC 2px solid;"><strong>Ответов</strong></td>
              <td style="border-bottom: #CCCCCC 2px solid;"><strong>%</strong></td>
            </tr>';
      while ($row = mysql_fetch_array($result))
       {
         echo '<tr>
                 <td style="border-bottom: #CCCCCC 1px solid;">'.$row['variant_name'].'</td>
                 <td style="border-bottom: #CCCCCC 1px solid;" align="center">'.$row['value'].'</td>
                 <td style="border-bottom: #CCCCCC 1px solid;" align="center">'.round(($row['value']*100)/$total,2).'%</td>
               </tr>';
       }
      echo '<tr>
              <td style="border-bottom: #CCCCCC 2px solid;" align="right"><strong>Всего:</strong></td>
              <td style="border-bottom: #CCCCCC 2px solid;" align="center"><strong>'.$total.'</strong></td>
              <td style="border-bottom: #CCCCCC 2px solid;">&nbsp;</td>
            </tr>';
      echo '</table>'; 
    }
   else
     echo 'Нет вариантов ответов';

  } else $user->no_rules('view');

 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>