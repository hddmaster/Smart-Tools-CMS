<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

//-----------------------------------------------------------------------------
// AJAX

function get_yandex_cy() {
    $cy = 'тест';
    $stat = new Statistic;
    $cy = $stat->get_yandex_cy();
    if ($cy == false) $cy = '&lt;не определено&gt;';

    $objResponse = new xajaxResponse();
    $objResponse->assign("yandex_cy","innerHTML",$cy);
    return $objResponse;
}

$xajax->registerFunction("get_yandex_cy");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Статистика</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/statistic.php')) $tabs->add_tab('/admin/statistic.php', 'Суммарный отчет за период');
if ($user->check_user_rules('view','/admin/stat_ips.php')) $tabs->add_tab('/admin/stat_ips.php', 'IP-адреса');
$tabs->show_tabs();

if ($user->check_user_rules('view')) {

// интерфейсная часть
?>
<form action="" method="GET">
   <table cellpadding="5" cellspacing="0" class="form_light">
    <tr>
      <td>
        
<table cellspacing="0" cellpadding="0">
 <tr>
    <td>с&nbsp;</td>
    <td>
    
        <script>
        LSCalendars["date1"]=new LSCalendar();
        LSCalendars["date1"].SetFormat("dd.mm.yyyy");
        LSCalendars["date1"].SetDate("<?=((isset($_GET['date1'])) ? $_GET['date1'] : date("d.m.Y"))?>");
        </script>
        <table cellspacing="0" cellpadding="2">
        <tr>
        <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date', event); return false;" style="width: 65px;" value="<?=((isset($_GET['date1'])) ? $_GET['date1'] : date("d.m.Y"))?>" name="date1"></td>
        <td><a style="cursor: pointer;" onclick="showCalendarForElement('date1', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
        </tr>
        </table>
        <div id="date1Ptr" style="width: 1px; height: 1px;"></div>
    
    
    </td>
    <td>&nbsp;&nbsp;по&nbsp;</td>
    <td>
    
        <script>
        LSCalendars["date2"]=new LSCalendar();
        LSCalendars["date2"].SetFormat("dd.mm.yyyy");
        LSCalendars["date2"].SetDate("<?=((isset($_GET['date2'])) ? $_GET['date2'] : date("d.m.Y"))?>");
        </script>
        <table cellspacing="0" cellpadding="2">
        <tr>
        <td><input onblur="setCalendarDateByStr(this.name, this.value);" onclick="showCalendarForElement('date2', event); return false;" style="width: 65px;" value="<?=((isset($_GET['date2'])) ? $_GET['date2'] : date("d.m.Y"))?>" name="date2"></td>
        <td><a style="cursor: pointer;" onclick="showCalendarForElement('date2', event); return false;"><img src="/admin/images/icons/calendar-month.png" alt="" border="0"></a></td>
        </tr>
        </table>
        <div id="date2Ptr" style="width: 1px; height: 1px;"></div>
        
    </td>
    
    <td style="padding-left: 10px;"><button type="submit">Сформировать</button></td>
	</tr>
	<tr>
		<td>c&nbsp;&nbsp;</td>
		<td>
			<select style="margin: 0px 0px 0px 2px;" name="hour1">
				<?
				for($i = 0; $i <= 24; $i++) {
					echo '<option'.((isset($_GET['hour1']) && $_GET['hour1'] == $i) ? ' selected="selected"' : '').' value="'.$i.'">'.$i.':00</option>';
				}
				?>
			</select>
		</td>
		<td>&nbsp;&nbsp;по&nbsp;&nbsp;</td>
		<td>
			<select style="margin: 0px 0px 0px 2px;" name="hour2">
				<?
				$flag_hour = false;
				for($i = 0; $i <= 24; $i++) {				
					if(isset($_GET['hour2']) && $_GET['hour2'] == $i) {
						$flag_hour = true;
					}
					echo '<option'.((($flag_hour && $_GET['hour2'] == $i) || (!$flag_hour && $i == 24)) ? ' selected="selected"' : '').' value="'.$i.'">'.$i.':00</option>';
				}
				?>
			</select>
		</td>
		<td>
		</td>
	</tr>
	</table>
    
    <div style="clear: both;" class="pb5"></div>  
    <div>
        <div style="float: left;"><input type="checkbox" name="orders" id="orders" value="true"<?=(isset($_GET['orders']) ? ' checked' : '')?>></div>
        <div style="float: left; padding: 2px 0px 0px 3px;"><label for="orders">только пользователи, сделавшие заказы в Магазине в указанный период</label></div>
    </div>
    
    <div style="clear: both;" class="pb5"></div>  
    <div>
        <div style="float: left;"><input type="checkbox" name="admin" id="admin" value="true"<?=(isset($_GET['admin']) ? ' checked' : '')?>></div>
        <div style="float: left; padding: 2px 0px 0px 3px;"><label for="admin">только пользователи, неавторизованные в административной части сайта</label></div>
    </div>
    
    </td>
	</tr>
	</table></form>
    <?

    //преобразуем даты
    $date1 = ((isset($_GET['date1'])) ? substr($_GET['date1'],6,4).substr($_GET['date1'],3,2).substr($_GET['date1'],0,2).'000000' : date('Ymd').'000000');
    $date2 = ((isset($_GET['date2'])) ? substr($_GET['date2'],6,4).substr($_GET['date2'],3,2).substr($_GET['date2'],0,2).'235959' : date('Ymd').'235959');
    
	$hour1 = ((isset($_GET['hour1'])) ? $_GET['hour1'] : 00);
	$hour2 = ((isset($_GET['hour2'])) ? $_GET['hour2'] : 24);
    $stat = new Statistic($date1,$date2);
    $stat->orders = (isset($_GET['orders']) ? true : false);
    if ($stat->orders) $stat->get_order_users();
    $stat->admin = (isset($_GET['admin']) ? true : false);
    if ($stat->admin) $stat->get_admin_users();
    
    
    echo '<table cellspacing="0" cellpadding="0"><tr valign="top"><td>';
    
    //распределение уникальных посетителей
    $stat->check_uniques($hour1, $hour2);
    $stat->show_uniques();
    
    echo '<td><img src="/admin/images/px.gif" width="20" height="1" alt=""></td><td>';
    
    //распределение хитов по часам
    $stat->check_hits($hour1, $hour2);
    $stat->show_hits();
    
    echo '</td></tr></table><div>&nbsp;</div>';
    
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/users.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=uniques'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">Уникальные посетители: распределение хитов, внешние ссылки</a></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/script-globe.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=links'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">Ссылающиеся страницы</a></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/tag-label.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=keywords'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">Поисковые слова</a></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/script.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=pages'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">Популярность страниц</a></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/monitor.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=ips'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">IP адреса</a></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/globe.png"></td><td><a class="h3" href="#" onclick="sw(\'/admin/stat/stat_full.php?date1='.$date1.'&date2='.$date2.'&hour1='.$hour1.'&hour2='.$hour2.'&type=useragents'.($stat->orders ? '&orders=true' : '').($stat->admin ? '&admin=true' : '').'\'); return false;">Браузеры</a></td></tr></table></p>';
    
    echo '<p>&nbsp;</p><p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/user.png"></td><td class="h3">Отчет пользователя по его номеру</td><td style="padding: 4px 4px 0px 12px;"><input type="text" id="user_id" style="width: 100px;"></td><td><button onclick="sw(\'/admin/stat/stat_user_id.php?user_id=\' + document.getElementById(\'user_id\').value); return false;">сформировать</button></td></tr></table></p>';
    echo '<p><table cellspacing="0" cellpadding="4"><tr valign="top"><td><img src="/admin/images/icons/user.png"></td><td class="h3">Заказы пользователя по его номеру</td><td><input type="text" id="global_id" style="width: 100px;"></td><td><button onclick="sw(\'/admin/stat/orders_user_id.php?user_id=\' + document.getElementById(\'global_id\').value); return false;">сформировать</button></td></tr></table></p>';
	
	
    //информация
    $stat->check_ctrs();
    $stat->check_cookies();
    $stat->check_nolinks_users();
    $stat->check_no_searchengines_users();
    $stat->show_info();

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>