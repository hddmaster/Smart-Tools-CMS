<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;

// Обработка формы входа в систему ---------------------------------------------
if (isset($_POST['username']) && isset($_POST['password']) ) {
    $username = mysql_real_escape_string(trim($_POST['username']));
    $password = mysql_real_escape_string(trim($_POST['password']));
    $user->cookie = (isset($_POST['cookie']) ? true : false);

    if ($user->login($username,$password)) {
        if (isset($_GET['referrer']) && trim($_GET['referrer']) !== '') {
            $referrer = 'http://'.$_SERVER['HTTP_HOST'].urldecode(trim($_GET['referrer']));
            header("Location: $referrer");
            exit();
        } else {
            header("Location: /admin/admin.php");
            exit();
        }
    } else {
        if (isset($_GET['referrer']) && trim($_GET['referrer']) !== '') {
            $referrer = urlencode(trim($_GET['referrer']));
            header("Location: /admin/?message=incorrectpassword&referrer=$referrer");
            exit();
        } else {
            header("Location: /admin/?message=incorrectpassword");
            exit();
        }
    }
}

if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized");exit();}

class Statistic_ext extends Statistic
 {
    function show_uniques() //ips
     {
       $text = '';
       $text .= '<table cellspacing="1" cellpadding="1" border="0" style="background: #cccccc;">';

       $i = 0;
       $max_width = round(170*($this->total_uniques/$this->max_uniques));

       foreach($this->uniques as $value)
        {
          $perc = round(($value*100)/$this->total_uniques,2);
          $line_width = round(($value*$max_width)/$this->total_uniques);
          $text .= '<tr style="text-align: center;background: #FFFFFF;">
                   <td nowrap width="80">'.$i.':00 - '.($i+1).':00</td>
                   <td nowrap width="50">'.$value.'</td>
                   <td nowrap align="left" width="195">';
          if ($line_width == 0) $text .= '&nbsp';
          else
            $text .= '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                      <td><div style="width:'.$line_width.'px; height:13px; background: #33CC33">&nbsp;</div></td>
                      <td width="5px">&nbsp;</td>
                      <td>'.$perc.'%</td>
                    </tr>
                   </table>';
          $text .= '</td>
                </tr>';
          $i++;
        }

       $text .= '<tr style="padding: 2px; text-align: center;font-weight: bold;background: #EFEFEF;">
               <td nowrap width="80">Всего</td>
               <td nowrap width="50">'.$this->total_uniques.'</td>
               <td>&nbsp;</td>
             </tr>
        </table>';
        
       return $text;
     }
 }

//------------------------------------------------------------------------------
// AJAX

define('USER_ID', $user->user_id);
define('USERNAME', $user->username);

function load_last_orders()
 {
   $text = '';
   $result = mysql_query("select
                          shop_orders.*,
                          date_format(shop_orders.order_date, '%d.%m.%Y (%H:%i:%s)') as order_date2,
                          date_format(shop_orders.delivery_date, '%d.%m.%Y') as delivery_date2
                          from shop_orders
                          order by order_id desc limit 10");

  $shop_currency = 'руб.';
  $system = new Auth;
  $shop_currency = $system->get_cms_option('shop_currency');

  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
     $text .= '<tr align="center" class="header">
               <td nowrap width="50">№</td>
               <td nowrap>Дата заказа</td>
               <td nowrap>Дата доставки</td>
               <td nowrap>Заказчик</td>
               <td nowrap>Всего, '.$shop_currency.'</td>
               <td nowrap>Статус</td>
               </tr>';

 while ($row = mysql_fetch_array($result))
  {
     $text .= '<tr class="underline">
               <td align="center">'.$row['order_id'].'</td>
               <td align="center">';
     if (substr($row['order_date2'],0,10) == date("d.m.Y")) $text .= '<span class="green">'.$row['order_date2'].'</span>';
     else $text .= $row['order_date2'];
     $text .= '</td>
               <td align="center">'.$row['delivery_date2'].' (с '.$row['delivery_hour1'].' ч. по '.$row['delivery_hour2'].' ч.)</td>
               <td align="center">'.($row['order_username'] ? htmlspecialchars($row['order_username']) : '&nbsp;').'</td>
               <td align="center">'.$row['price'].'</td>
               <td align="center" nowrap><table cellspacing="0" cellpadding="0"><tr>';
           
           if ($row['status_id'] > 0)
            {
              $res = mysql_query("select * from shop_order_status where status_id = ".$row['status_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 $text.= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px;background:#'.$r['status_color'].'">&nbsp;</div></td>';
               }
              else $text.= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px;">&nbsp;</div></td>';
            }
           else $text.= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px;">&nbsp;</div></td>';

           $text.= '<td style="border: 0px;">';
           if($row['status_id'] == 0)  $text.= '---НЕТ---';
           $res = mysql_query("select * from shop_order_status where status_id = ".$row['status_id']);
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              $text.= htmlspecialchars($r['status_name']);
            }

           $text.= '</select></td></tr></table>';

      $text .= '</td>
               </tr>';
  }
  $text .= '</table>';
  }

  else $text .= '<p align="center">Заказов не было</p>';

   $objResponse = new xajaxResponse();
   $objResponse->assign("last_orders","innerHTML",$text);
   return $objResponse;
 }

function load_last_messages()
 {
   $text = '';
   $result = mysql_query("select *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2 from guestbook where type = 0 order by text_id desc limit 10");

  if (mysql_num_rows($result) > 0)
   {
     $text .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
     $text .= '<tr align="center" class="header">
               <td nowrap width="50">№</td>
               <td nowrap>Дата</td>
               <td nowrap>Имя</td>
               <td nowrap>e-mail</td>
               <td nowrap>Сообщение</td>
               <td nowrap>Ответ</td>
               </tr>';

 while ($row = mysql_fetch_array($result))
  {
     $text .= '<tr class="underline">
               <td align="center">'.$row['text_id'].'</td>
               <td align="center" nowrap>';
     if (substr($row['date2'],0,10) == date("d.m.Y")) $text .= '<span class="green">'.$row['date2'].'</span>';
     else $text .= $row['date2'];
     $text .= '</td>
               <td align="center" nowrap>'.htmlspecialchars($row['name']).'</td>
               <td align="center">'; if($row['email']) $text .= $row['email']; else $text .= '&nbsp;'; $text .= '</td>
               <td><div class="text"><span class="small">'.$row['text'].'</span></div></td>
               <td>'; if ($row['text_answ'] !== '') $text .= '<div class="text"><span class="small">'.$row['text_answ'].'<span></div>'; else $text .= '&nbsp;'; $text .= '</td>';
     $text .= '</td>
               </tr>';
  }
  $text .= '</table>';
  }

  else $text .= '<p align="center">Сообщений не было</p>';

   $objResponse = new xajaxResponse();
   $objResponse->assign("last_messages","innerHTML",$text);
   return $objResponse;
 }

function load_statistic()
 {
   $text = '';
   $stat = new Statistic_ext;
   
   $stat->check_uniques();
   if($stat->total_uniques) $text .= $stat->show_uniques();
   else $text .= '<p align="center">Сегодня посетителей не было</p>';

   $objResponse = new xajaxResponse();
   $objResponse->assign("statistic","innerHTML",$text);
   return $objResponse;
 }

function load_online()
 {
   $text = '';

   $result = mysql_query("select count(1) as c,username from auth,auth_online where auth_online.user_id = auth.user_id group by username order by username asc");
   $i = 1;
   $num_res = mysql_num_rows($result);
   if($num_res > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         if (USERNAME == $row['username']) $text .= '<span class="green">'.htmlspecialchars($row['username']).'</span>';
         else $text .= htmlspecialchars($row['username']);
         if ($row['c'] > 1) $text .= '('.$row['c'].')';
         if ($i < $num_res) $text .= ', ';
         $i++;
       }
    }

   $objResponse = new xajaxResponse();
   $objResponse->assign("online","innerHTML",$text);
   $objResponse->assign("count_online","innerHTML",$num_res);
   return $objResponse;
 }

function load_site_online()
 {
   $text = '';

   $result = mysql_query("select count(1) as c,username from auth_site,auth_site_online where auth_site_online.user_id = auth_site.user_id group by username order by username asc");
   $i = 1;
   $num_res = mysql_num_rows($result);
   if($num_res > 0)
    {
      while ($row = mysql_fetch_array($result))
       {
         if (USERNAME == $row['username']) $text .= '<span class="green">'.htmlspecialchars($row['username']).'</span>';
         else $text .= htmlspecialchars($row['username']);
         if ($row['c'] > 1) $text .= '('.$row['c'].')';
         if ($i < $num_res) $text .= ', ';
         $i++;
       }
    }

   $objResponse = new xajaxResponse();
   $objResponse->assign("site_online","innerHTML",$text);
   $objResponse->assign("count_site_online","innerHTML",$num_res);
   return $objResponse;
 }


$xajax->registerFunction("load_last_orders");
$xajax->registerFunction("load_last_messages");
$xajax->registerFunction("load_statistic");
$xajax->registerFunction("load_online");
$xajax->registerFunction("load_site_online");

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

 if ($user->check_user_rules('view'))
  {

echo '<table cellspacing="0" cellpadding="0" width="100%">
       <tr valign="top"><td>';

if ($user->check_user_rules('view','/admin/statistic.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/statistic.php">Статистика</a></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box" id="statistic"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></td></tr></table>';
echo '</div>
      </p>';
}

echo '</td><td><img src="/admin/images/px.gif" alt="" width="20" height="1"></td><td width="100%">';

if ($user->check_user_rules('view','/admin/auth.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/auth.php">Пользователи CMS on-line</a>: <span id="count_online"></span></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box" id="online"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></td></tr></table>';
echo '</div>
      </p>';
}

if ($user->check_user_rules('view','/admin/auth_site.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/auth_site.php">Пользователи сайта on-line</a>: <span id="count_site_online"></span></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box" id="site_online"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></td></tr></table>';
echo '</div>
      </p>';
}

if ($user->check_user_rules('view','/admin/auth.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><span class="hmenu">Техническая информация</span></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box">';
echo 'Дата и время на сервере: <strong>'.date('d.m.Y (H:i:s)').'</strong><br />
      Корневая папка: <strong>'.$_SERVER['DOCUMENT_ROOT'].'</strong><br/>
      Параметры сервера: <strong>'.$_SERVER['SERVER_SOFTWARE'].'</strong><br/>
      Версия PHP: <strong>'.PHP_VERSION.'</strong><br/>
      Версия MySQL: <strong>'.mysql_get_client_info().'</strong><br/>
      IP сервера: <strong>'.$_SERVER['SERVER_ADDR'].'</strong><br/><br/>
      Ваша дата и время: <strong><span id="user_datetime"></span></strong>
      
      <script>
      function show_user_date()
       {
         var now = new Date();
         var day = now.getDate();
         var month = now.getMonth() + 1;
         var year = now.getFullYear();
         var hour = now.getHours(); 
         var minute = now.getMinutes(); 
         var second = now.getSeconds();
      
         if (day.toString().length < 2) day = "0" + day.toString();
         if (month.toString().length < 2) month = "0" + month.toString();
         if (hour.toString().length < 2) hour = "0" + hour.toString();
         if (minute.toString().length < 2) minute = "0" + minute.toString();
         if (second.toString().length < 2) second = "0" + second.toString();
      
         document.getElementById(\'user_datetime\').innerHTML = day + "." + month + "." + year + " (" + hour + ":" + minute + ":" + second + ")";
       }

      delay = 1000; // 1 cек.
      function update_user_date()
       {
        show_user_date();
        setTimeout("update_user_date()", delay);
       }   
      update_user_date();
      </script>
      
      <br />
      Информация о браузере: <strong>'.$_SERVER['HTTP_USER_AGENT'].'</strong><br/>
      IP пользователя: <strong>'.$_SERVER['REMOTE_ADDR'].'</strong><br /><br />';
      
$php_values = ini_get_all();
echo '<div style="overflow: auto; height: 100px; width: 100%; border: #cccccc 1px solid; padding: 4px;">';
foreach ($php_values as $value_name => $values) if (trim ($values['local_value']) !== '') echo '<strong>'.$value_name.'</strong>: '.$values['local_value'].'<br />';
echo '</div>';
echo '</td></tr></table>
      </p>';
}

if(function_exists('get_exchange')) {
$file = $_SERVER['DOCUMENT_ROOT'].'/admin/modules/exchange/database';
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/currencies.php">Курсы валют</a>'; if (file_exists($file))  echo '<span class="transparent"> | Обновлено '.date("d.m.Y (H:i:s)", filemtime($file)).'</span>'; echo '</td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box">';
$exchange = get_exchange();
echo '<table cellspacing="0" cellpadding="2">';
foreach ($exchange as $code => $value)
 {
   if ($code == 'USD' ||
       $code == 'EUR') 
   echo '<tr align="center">
           <td><strong>'.$code.'</strong></td>
           <td><span class="green">'.$value.'</span></td>
         </tr>';
 }
echo '</table>';
echo '</td></tr></table>
      </p>';
}
 echo '</td></tr></table>';


if ($user->check_user_rules('view','/admin/shop_orders.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/shop_orders.php">Заказы</a></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box" id="last_orders"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></td></tr></table>
      </p>';
}

if ($user->check_user_rules('view','/admin/guestbook.php'))
{
echo '<p>
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
       <tr>
         <td class="h_menu_sep">&nbsp;</td>
         <td nowrap class="h_menu_td"><a class="hmenu" href="/admin/guestbook.php">Гостевая книга</a></td>
         <td class="h_menu_sep" width="100%">&nbsp;</td>
       </tr>
      </table>
      <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="100%" class="hmenu_box" id="last_messages"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></td></tr></table>
      </p>';
}

 //xajax
 echo '<script language="javascript">
         function process_requests()
          {
            delay = 60000; // 1 мин.
            '; if ($user->check_user_rules('view','/admin/statistic.php')) echo 'xajax_load_statistic();'; echo '
            '; if ($user->check_user_rules('view','/admin/auth.php')) echo 'xajax_load_online();'; echo '
            '; if ($user->check_user_rules('view','/admin/auth_site.php')) echo 'xajax_load_site_online();'; echo '
            '; if ($user->check_user_rules('view','/admin/guestbook.php')) echo 'xajax_load_last_messages();'; echo '
            '; if ($user->check_user_rules('view','/admin/shop_orders.php')) echo 'xajax_load_last_orders();'; echo '
            setTimeout("process_requests()", delay);
          }
         setTimeout("process_requests();",1000);;
       </script>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>