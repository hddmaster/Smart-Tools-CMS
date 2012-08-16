<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы', 1);
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/shop_delivery.php')) $tabs2->add_tab('/admin/shop_delivery.php', 'Виды доставки');
if ($user->check_user_rules('view','/admin/shop_order_status.php')) $tabs2->add_tab('/admin/shop_order_status.php', 'Статусы заказов');
if ($user->check_user_rules('view','/admin/shop_couriers.php')) $tabs2->add_tab('/admin/shop_couriers.php', 'Курьеры');
if ($user->check_user_rules('view','/admin/shop_order_statistic.php')) $tabs2->add_tab('/admin/shop_order_statistic.php', 'Статистика');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {
   $date1 = ((isset($_GET['date1'])) ? $_GET['date1'] : date('d.m.Y', mktime(0, 0, 0, (int)date('m')-1, date('d'), date('Y'))));
   $date2 = ((isset($_GET['date2'])) ? $_GET['date2'] : date('d.m.Y'));

   $date1db = ((isset($_GET['date1'])) ? substr($_GET['date1'],6,4).substr($_GET['date1'],3,2).substr($_GET['date1'],0,2) : date('Ymd', mktime(0, 0, 0, (int)date('m')-1, date('d'), date('Y'))));
   $date2db = ((isset($_GET['date2'])) ? substr($_GET['date2'],6,4).substr($_GET['date2'],3,2).substr($_GET['date2'],0,2) : date('Ymd'));
  

 echo '<p><form action="" method="get">  
 <table cellspacing="0" cellpadding="0">
 <tr>
  <td>с&nbsp;</td>
  <td>

    <script>
      LSCalendars["date1"]=new LSCalendar();
      LSCalendars["date1"].SetFormat("dd.mm.yyyy");
      LSCalendars["date1"].SetDate("'.$date1.'");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement(\'date1\', event); return false;" style="width: 65px;" value="'.$date1.'" name="date1"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement(\'date1\', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="date1Ptr" style="width: 1px; height: 1px;"></div>


   </td>
   <td>&nbsp;&nbsp;по&nbsp;</td>
   <td>

    <script>
      LSCalendars["date2"]=new LSCalendar();
      LSCalendars["date2"].SetFormat("dd.mm.yyyy");
      LSCalendars["date2"].SetDate("'.$date2.'");
    </script>
    <table cellspacing="0" cellpadding="0">
     <tr>
       <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement(\'date2\', event); return false;" style="width: 65px;" value="'.$date2.'" name="date2"></td>
       <td><a style="cursor: pointer;" onclick="showCalendarForElement(\'date2\', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
     </tr>
    </table>
    <div id="date2Ptr" style="width: 1px; height: 1px;"></div>

   </td>
   
   <td style="padding-left: 10px;"><button type="sunbmit">Сформировать отчет</button></td>
   </tr></table></form></p>';

   echo '<h2>Стаститика по заказам с '.$date1.' по '.$date2.'</h2>
         <table cellspacing="0" cellspacing="0"><tr valign="top"><td width="50%">';
    
   echo '<fieldset><legend>Сводная информация по товарным группам, ТОП 50</legend>
          <table cellspacing="0" cellpadding="4">';
   $res = mysql_query("select
		       V.element_id,
		       E.parent_id,
		       (select element_name from shop_cat_elements where element_id = E.parent_id) as element_name,
		       sum(V.amount) as s
		       from shop_order_values as V left join shop_cat_elements as E
		       on V.element_id = E.element_id
		       left join shop_orders as O
		       on V.order_id = O.order_id
		       where O.order_date >= $date1db and O.order_date <= $date2db
		       group by E.parent_id
		       order by s desc
		       limit 50");
   if (mysql_num_rows($res) > 0)
    {
      while ($r = mysql_fetch_object($res))
       {
	 echo '<tr>
	        <td>'.htmlspecialchars($r->element_name).'</td>
		<td class="strong">'.$r->s.'</td>
	       </tr>';
       }
    }
   echo '</table></fieldset>';

   echo '<fieldset><legend>Сводная информация по товарам, ТОП 50</legend>
          <table cellspacing="0" cellpadding="4">';
   $res = mysql_query("select
		       V.element_name,
		       sum(V.amount) as s
		       from shop_order_values as V left join shop_orders as O on V.order_id = O.order_id
		       where O.order_date >= $date1db and O.order_date <= $date2db
		       group by V.element_id
		       order by s desc
		       limit 50");
   if (mysql_num_rows($res) > 0)
    {
      while ($r = mysql_fetch_object($res))
       {
	 echo '<tr>
	        <td>'.htmlspecialchars($r->element_name).'</td>
		<td class="strong">'.$r->s.'</td>
	       </tr>';
       }
    }
   echo '</table></fieldset>';

   echo '</td><td><img src="/admin/imgaes/px.gif" alt="" width="20" height="1"></td><td width="50%">';

   echo '<fieldset><legend>Сводная информация по статусу</legend>
          <table cellspacing="0" cellpadding="4">';
   $res = mysql_query("select
		       S.status_name,
		       S.status_id,
		       count(*) as c
		       from shop_orders as O left join shop_order_status as S
		       on O.status_id = S.status_id
		       where O.order_date >= $date1db and O.order_date <= $date2db
		       group by O.status_id
		       order by c desc");
   if (mysql_num_rows($res) > 0)
    {
      while ($r = mysql_fetch_object($res))
       {
	 echo '<tr>
	        <td><a class="h4" href="/admin/shop_orders.php?status_id='.(($r->status_id) ? $r->status_id : 0).'">'.(($r->status_name) ? htmlspecialchars($r->status_name) : '---НЕТ---').'</a></td>
		<td class="strong">'.$r->c.'</td>
	       </tr>';
       }
    }
   echo '</table></fieldset>';

   echo '<fieldset><legend>Оценка конверсии по доменам (заказов / переходов)</legend>
   
   <table cellspacing="0" cellpadding="4">';
      
   $domains = array();
   $res = mysql_query("select link from shop_order_links as L, shop_orders as O where L.order_id = O.order_id and O.order_date >= $date1db and O.order_date <= $date2db order by link asc") or die(mysql_error());
   if (mysql_num_rows($res) > 0)
    {
      while ($r = mysql_fetch_object($res))
       {
         $components = parse_url($r->link);
	 $host = str_replace('www.', '', $components['host']);
	 if (array_key_exists($host, $domains)) $domains[$host]++;
	 else $domains[$host] = 1;
       }
    }
    
   arsort($domains);
   
   foreach($domains as $domain => $c)
    {
      $go = 0;
      $res = mysql_query("select * from stat_global_referrers where visits_date >= $date1db and visits_date <= $date2db");
      if (mysql_num_rows($res) > 0)
       {
	 while ($r = mysql_fetch_object($res))
	  {
	    $data = unserialize($r->data_array);
	    foreach($data as $link => $count)
	     {
	       $components = parse_url($link);
	       if ($domain == str_replace('www.', '', $components['host'])) $go += $count;
	     }
	  }
       }
      echo '<tr>
	      <td class="h3">'.$domain.'</td>
	      <td class="strong">'.$c.' / '.$go.' = '.round(($c/$go)*100, 2).'%</td>
	    </tr>';
    }
   echo '</table></fieldset>';

   echo '</td></tr></table>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>