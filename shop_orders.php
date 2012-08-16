<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['action']))
 {
   $action = $_GET['action'];

   if ($action == 'del' && isset($_GET['id']))
    {
      $order_id = (int)$_GET['id'];

      if ($user->check_user_rules('delete'))
       {
         mysql_query("delete from shop_order_links where order_id = $order_id");
         mysql_query("delete from shop_order_value where order_id = $order_id");
         mysql_query("delete from shop_orders where order_id = $order_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('delete');
    }
   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();

 }

//-----------------------------------------------------------------------------
// AJAX

function show_orders()
{
  $objResponse = new xajaxResponse();
  $text = '';

$status_id = -1; if (isset($_GET['status_id']) && $_GET['status_id'] >= 0) $status_id = (int)$_GET['status_id'];
$manager_id = -1; if (isset($_GET['manager_id']) && $_GET['manager_id'] >= 0) $manager_id = (int)$_GET['manager_id'];

$text .= '<div><form action="" method="GET">
   <table cellpadding="4" cellspacing="0" align="left">
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td>Фильтр по датам</td>
      <td>
      
       <table cellspacing="0" cellpadding="0">
        <tr>
          <td>с&nbsp;</td>
          <td><input class="datepicker" style="width: 65px;" value="'.((isset($_GET['date1'])) ? $_GET['date1'] : date('d.m.Y', mktime(0, 0, 0, (int)date('m')-2, date('d'), date('Y')))).'" name="date1"></td>
          <td>&nbsp;&nbsp;по&nbsp;</td>
          <td><input class="datepicker" style="width: 65px;" value="'.((isset($_GET['date2 '])) ? $_GET['date2'] : date("d.m.Y")).'" name="date2"></td>
        </tr>
       </table>
      
      </td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td nowrap>Фильтр по статусу</td>
      <td>';
            
$text .= '<select style="width: 280px;" name="status_id"><option value="">---ВСЕ---</option>';
           $text .= '<option value="0"';if(isset($_GET['status_id']) && $_GET['status_id'] === '0') $text .= ' selected'; $text .= '>---НЕТ---</option>';   

           $res = mysql_query("select * from shop_order_status order by status_name asc");
           if (mysql_num_rows($res) > 0)
            {
              while ($r = mysql_fetch_array($res))
               {
                 $text .= '<option value="'.$r['status_id'].'"';
                 if ($r['status_id'] == $status_id) $text .= ' selected';
                 $text .= '>'.htmlspecialchars($r['status_name']).'</option>';
               }
            }

   $text .= '</td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/funnel.png" alt=""></td>
      <td nowrap>Фильтр по менеджеру</td>
      <td>';
            
           $text .= '<select style="width: 280px;" name="manager_id"><option value="">---ВСЕ---</option>';
           $text .= '<option value="0"'; if(isset($_GET['manager_id']) && $_GET['manager_id'] === '0') $text .= ' selected'; $text .= '>---НЕТ---</option>';   

           $res = mysql_query("select * from auth where get_orders = 1 order by user_fio asc");
           if (mysql_num_rows($res) > 0)
            {
              while ($r = mysql_fetch_array($res))
               {
                 $text .= '<option value="'.$r['user_id'].'"';
                 if ($r['user_id'] == $manager_id) $text .= ' selected';
                 $text .= '>'.htmlspecialchars($r['username']).($r['user_fio'] ? ' ('.htmlspecialchars($r['user_fio']).')' : '&nbsp;').'</option>';
               }
            }

   $text .= '</td>
    </tr>
    <tr>
      <td><img src="/admin/images/icons/magnifier.png" alt=""></td>
      <td>Поиск по фразе</td>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') $text .=  htmlspecialchars($_GET['query_str']); $text .=  '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></form></div><div style="clear: both;"></div>';

// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'order_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
//даты
  $date1 = ((isset($_GET['date1'])) ? substr($_GET['date1'],6,4).substr($_GET['date1'],3,2).substr($_GET['date1'],0,2) : date('Ymd', mktime(0, 0, 0, (int)date('m')-1, date('d'), date('Y'))));
  $date2 = ((isset($_GET['date2'])) ? substr($_GET['date2'],6,4).substr($_GET['date2'],3,2).substr($_GET['date2'],0,2) : date('Ymd'));

if (isset($_GET['date1'])) $params['date1'] = $_GET['date1'];
if (isset($_GET['date2'])) $params['date2'] = $_GET['date2'];
  
if (isset($_GET['status_id']) && trim($_GET['status_id']) !== '')
 {
   $add .= " and status_id = ".$_GET['status_id'];
   $params['status_id'] = $_GET['status_id'];
 }

if (isset($_GET['manager_id']) && trim($_GET['manager_id']) !== '')
 {
   $add .= " and auth.user_id = ".$_GET['manager_id'];
   $params['manager_id'] = $_GET['manager_id'];
 }

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (
                  shop_orders.order_id like '$query_str' or
                  shop_orders.order_username like '$query_str' or	 	 	 	 
                  shop_orders.order_phone like '$query_str' or	 	 	 	 
                  shop_orders.order_email like '$query_str' or 	 	 	 
                  shop_orders.order_address like '$query_str' or	 	 
                  shop_orders.order_comment like '$query_str' or
                  shop_orders.extended_info like '$query_str' or
                  shop_orders.description_hidden like '$query_str' or
                  
                  auth.username like '$query_str' or
                  auth.user_fio like '$query_str' or
                  auth.email like '$query_str' or
                  
                  auth_site.username like '$query_str' or
                  auth_site.user_nick like '$query_str' or
                  auth_site.user_fio like '$query_str' or
                  auth_site.user_address like '$query_str' or
                  auth_site.email like '$query_str'
                  )";
 }
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select
           shop_orders.*,
           date_format(shop_orders.order_date, '%d.%m.%Y %H:%i:%s') as order_date_f,
           date_format(shop_orders.delivery_date, '%d.%m.%Y') as delivery_date_f,
           date_format(shop_orders.delivery_date2, '%d.%m.%Y') as delivery_date2_f,
           auth_site.username,
           shop_delivery.price as delivery_price
           from shop_orders left join auth on shop_orders.user_id = auth.user_id
                            left join auth_site on shop_orders.site_user_id = auth_site.user_id
                            left join shop_delivery on shop_orders.delivery_id = shop_delivery.delivery_id
           where date_format(shop_orders.order_date, '%Y%m%d') >= $date1 and date_format(shop_orders.order_date, '%Y%m%d') <= $date2 $add";
 $result = mysql_query($query) or $objResponse->alert(mysql_error()); $total_rows = mysql_num_rows($result);
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 $res = mysql_query("select * from shop_orders"); $total_orders = mysql_num_rows($res);          
 $alert = false;

 if (mysql_num_rows($result) > 0)
 {
 if (isset($_SESSION['smart_tools_orders']) && $_SESSION['smart_tools_orders'] < $total_orders) $alert = true;
 $_SESSION['smart_tools_orders'] = $total_orders;
 
 $shop_currency = 'руб.';
 $user = new Auth;
 $shop_currency = $user->get_cms_option('shop_currency');
 $text .= navigation_to_string($page, $per_page, $total_rows, $params);
 $text .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">';
 $text .= '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_id' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_id' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Дата<br />заказа&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_date&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_date' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_date&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_date' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Дата<br />доставки&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=delivery_date2&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'delivery_date2' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=delivery_date2&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'delivery_date2' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="100%">Заказчик&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_username&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_username' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_username&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_username' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Всего,<br />'.$shop_currency.'&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'price' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=price&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'price' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Статус&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'status_id' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=status_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'status_id' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Курьер&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=courier_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'courier_id' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=courier_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'courier_id' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Менеджер&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=user_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'user_id' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Примечание<br />покупателя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_comment&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_comment' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=order_comment&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'order_comment' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Примечание<br />менеджера&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=description_hidden&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'description_hidden' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=description_hidden&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'description_hidden' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap class="small">Дополнительная<br />информация&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=extended_info&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'extended_info' && $order == 'asc') $text .= 'sort_asc_sel.gif'; else $text .= 'sort_asc.gif'; $text .= '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=extended_info&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'extended_info' && $order == 'desc') $text .= 'sort_desc_sel.gif'; else $text .= 'sort_desc.gif'; $text .= '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   $text .= '<tr valign="top" onmouseover="this.style.backgroundColor='; $text .= "'#EFEFEF'"; $text .=';" onmouseout="this.style.backgroundColor='; $text .= "'white'"; $text .= ';" class="underline">
           <td align="center">'.$row['order_id'].((substr($row['order_date_f'],0,10) == date("d.m.Y")) ? '<img src="/admin/images/icons/new.png" alt="">' : '').'</td>
           <td align="center">';
           
           list($date, $time) = explode(' ', $row['order_date_f']);
           if (substr($row['order_date_f'],0,10) == date("d.m.Y")) $text .= '<strong class="green small strong">'.$date.'</strong>';
           else $text .= '<span class="small">'.$date.'</span>';
           $text .= '<br /><span class="small">'.$time.'</span>';
           $text .= '</td><td align="center">';
           if (substr($row['delivery_date2_f'],0,10) == date("d.m.Y")) $text .= '<strong class="green small strong">'.$row['delivery_date2_f'].'</strong>';
           else $text .= '<span class="small">'.$row['delivery_date2_f'].'</span>';
           $text .= '</td><td>';
           
           if ($row['order_username']) $text .= '<div>'.htmlspecialchars($row['order_username']).'</div>';
           if ($row['site_user_id']) $text .= '<div><a href="javascript:sw(\'/admin/editors/edit_auth_site_user.php?id='.$row['site_user_id'].'\');" class="strong">'.htmlspecialchars($row['username']).'</a></div>';
           
           if(!$row['order_username'] && !$row['site_user_id']) $text .= '&nbsp;';
           
           $text .= '</td>
           <td align="right" class="big" nowrap>'.number_format($row['price']+$row['delivery_price'], 2, ',', ' ').'</td>
           <td nowrap><table cellspacing="0" cellpadding="0"><tr>';
           
           if ($row['status_id'] > 0)
            {
              $res = mysql_query("select * from shop_order_status where status_id = ".$row['status_id']);
              if (mysql_num_rows($res) > 0)
               {
                 $r = mysql_fetch_array($res);
                 $text .= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px; border: #eeeeeee 1px solid; background:#'.$r['status_color'].'">&nbsp;</div></td>';
               }
              else $text .= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px; border: #eeeeeee 1px solid;">&nbsp;</div></td>';
            }
           else $text .= '<td style="border: 0px; padding-right: 4px;"><div style="width:16px; height:16px; border: #eeeeeee 1px solid;">&nbsp;</div></td>';

           $text .= '<td style="border: 0px;" class="small" nowrap>';
           $res = mysql_query("select * from shop_order_status where status_id = ".$row['status_id']);
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              $text .= htmlspecialchars($r['status_name']);
            }

           $text .= '</td></tr></table>';
           $text .= '</td>';

           //курьеры
           $text .= '<td>';
           $res = mysql_query("select * from shop_couriers where courier_id = ".$row['courier_id']);
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
               $text .= htmlspecialchars($r['courier_name']);
            } else $text .= '&nbsp;';
           $text .= '</td>';

           //менеджеры
           $text .= '<td>';
           $res = mysql_query("select * from auth where user_id = ".$row['user_id']);
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              $text .= htmlspecialchars($r['username']).(($r['user_fio']) ? '<br /><span class="small grey">'.htmlspecialchars($r['user_fio']).'</span>' : '');
            } else $text .= '&nbsp;';
           $text .= '</td>';
           
           $text .= '<td>'.(($row['order_comment']) ? htmlspecialchars($row['order_comment']) : '&nbsp;').'</td>';
           $text .= '<td>'.(($row['description_hidden']) ? htmlspecialchars($row['description_hidden']) : '&nbsp;').'</td>';
           $text .= '<td>'.(($row['extended_info']) ? htmlspecialchars($row['extended_info']) : '&nbsp;').'</td>';

           $text .= '<td nowrap align="center">
           <a href="#" onclick="sw(\'';
           //специальная форма заказа
           if (file_exists($_SERVER['DOCUMENT_ROOT'].'/admin/modules/shop_order_view/shop_order_view.php')) $text .= 'modules/shop_order_view';
           else $text .= '/admin/editors';
           $text .= '/shop_order_view.php?id='.$row['order_id'].'\'); return false;"><img align="absmiddle" src="/admin/images/icons/printer.png" border="0" alt="Просмотр заказа"></a>
           &nbsp;<a href="#" onclick="sw(\'editors/edit_shop_order.php?id='.$row['order_id'].'\'); return false;"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать заказ"></a>
           &nbsp;<a href="';
           $text .= "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['order_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params."';}";
           $text .= '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
 $text .= '</table>';
 $text .= navigation_to_string($page, $per_page, $total_rows, $params);
 }
 else $text .= '<p>За указанный период заказов не найдено</p>';

  $objResponse->assign('content','innerHTML',$text);
  $objResponse->script('$(".datepicker").datepicker($.datepicker.regional[\'ru\']);');
  if ($alert) $objResponse->alert("Внимание! Появились новые заказы!");
  return $objResponse;
}

$xajax->registerFunction("show_orders");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
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

/*
 echo '<div style="cursor: pointer;" onclick="location.href=\'/admin/shop_order_add.php\'; return false;">
        <table cellspacing="0" cellpadding="4">
	 <tr>
	   <td><img src="/admin/images/icons/plus.png" alt=""></td>
	   <td><h2 class="nomargins">Добавить заказ</h2></td>
	 </tr>
	</table>   
       </div>';
*/
?>
<script>
function process_requests()
 {
   delay = 300000; // 5 мин.
   xajax_show_orders();
   setTimeout("process_requests()", delay);
 }
setTimeout("process_requests();",1500);
</script>
<?
echo '<div id="content" align="center" style="padding-top: 10px;"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></div>';

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>