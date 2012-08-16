<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['date']) && isset($_GET['date2']))
{
  if (trim( $_GET['date'])=='' || trim($_GET['date2'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  //преобразуем даты
  $date1 = substr($_GET['date'],6,4).substr($_GET['date'],3,2).substr($_GET['date'],0,2);
  $date2 = substr($_GET['date2'],6,4).substr($_GET['date2'],3,2).substr($_GET['date2'],0,2);

  if ($date1 > $date2) {Header("Location: ".$_SERVER['PHP_SELF']."?message=date"); exit();}
}

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог');
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад', 1);
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs2->add_tab('/admin/shop_incoming.php', 'Приход товара');
if ($user->check_user_rules('view','/admin/shop_outgoing.php')) $tabs2->add_tab('/admin/shop_outgoing.php', 'Расход товара');
if ($user->check_user_rules('view','/admin/shop_places.php')) $tabs2->add_tab('/admin/shop_places.php', 'Торговые точки');
if ($user->check_user_rules('view','/admin/shop_sale.php')) $tabs2->add_tab('/admin/shop_sale.php', 'Продажи');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {


 function show_select_all()
  {
    $result = mysql_query("SELECT * FROM shop_outgoing_data group by element_name, store_name order by element_name, store_name asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['data_id'].'">'.htmlspecialchars($row['element_name']).
                      ' (арт.: '.htmlspecialchars($row['store_name']).')</option>'."\n";
        }
    }
    return $options;
  }

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->add_message('date', 'Указан неверный период для отчета');
   $message->get_message($_GET['message']);
 }

 echo '<form action="" method="GET">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Отчет по выбранным датам <sup class="red">*</sup></td>
      <td>';
?>
<TABLE cellSpacing=0 cellPadding=0 border=0>
 <TR>
  <TD class=tix>с&nbsp;</TD>
  <TD>
     <SCRIPT language=JavaScript>

     // класс LSCalendar должен присутствовать в системе, массив LSCalendars объявлен
     LSCalendars["date"]=new LSCalendar();
     LSCalendars["date"].SetFormat("dd.mm.yyyy");
     LSCalendars["date"].SetDate("<?php echo date("d.m.Y");?>");

     </SCRIPT>
    <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
     <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php if (isset($_GET['date'])) echo $_GET['date']; else echo date("d.m.Y");?>" name=date> </TD>
      <TD><button style="WIDTH: 34px; HEIGHT: 17px" onclick="showCalendarForElement('date', event); return false;"><img src="/admin/images/icons/calendar.png"></button></TD>
     </TR>
     <TR>
      <TD colSpan=2>
        <DIV id=datePtr style="WIDTH: 1px; HEIGHT: 1px">
        <SPACER height="1" width="1" type="block"/>
        </DIV>
      </TD>
     </TR>
    </TABLE>


   </TD>
   <TD class=tix>&nbsp;&nbsp;по&nbsp;</TD>
   <TD>
     <SCRIPT language=JavaScript>

     // класс LSCalendar должен присутствовать в системе, массив LSCalendars объявлен
     LSCalendars["date2"]=new LSCalendar();
     LSCalendars["date2"].SetFormat("dd.mm.yyyy");
     LSCalendars["date2"].SetDate("<?php echo date("d.m.Y");?>");

     </SCRIPT>
     <TABLE style="BORDER-RIGHT: #fff 2px inset; BORDER-TOP: #fff 2px inset; BORDER-LEFT: #fff 2px inset; BORDER-BOTTOM: #fff 2px inset" cellSpacing=0 cellPadding=0 bgColor=#ffffff border=0>
      <TR>
      <TD><INPUT class=tix onblur="setCalendarDateByStr(this.name, this.value);" style="BORDER-RIGHT: 0px; BORDER-TOP: 0px; BORDER-LEFT: 0px; WIDTH: 65px; BORDER-BOTTOM: 0px" value="<?php if (isset($_GET['date2'])) echo $_GET['date2']; else echo date("d.m.Y");?>" name=date2> </TD>
      <TD><button style="WIDTH: 34px; HEIGHT: 17px" onclick="showCalendarForElement('date2', event); return false;"><img src="/admin/images/icons/calendar.png"></button></TD>
      </TR>
      <TR>
       <TD colSpan=2>
         <DIV id=date2Ptr style="WIDTH: 1px; HEIGHT: 1px">
         <SPACER height="1" width="1" type="block"/>
         </DIV>
       </TD>
      </TR>
     </TABLE>

   </TD>
 </TR>
</TABLE>
<?php
echo'      </td>
    </tr>
    <tr>
    <td>Торговые точки</td>
    <td><select style="width:280px" name="place_id">
      <option value="">Все торговые точки</option>';

    $res = mysql_query("select * from shop_places order by place_name asc");
    if (mysql_num_rows($res) > 0)
     {
       while ($row = mysql_fetch_array($res))
        {
          echo '<option value="'.$row['place_id'].'"';
          if (isset($_GET['place_id']) && $_GET['place_id'] == $row['place_id'])
            echo ' selected';
          echo '>'.htmlspecialchars($row['place_name']).'</option>';
        }
     }

echo'</select></td></tr>
    <tr>
      <td>Выберите группу или товар</td>
      <td><select name="data_id" style="width:280px;">
            <option value="">Выберите группу или товар...</option>
            '.show_select_all().'
          </select>
      </td>
    </tr>
    </table><br>
    <button type="submit">Показать</button></form><p>&nbsp;</p>';

if (isset($_GET['date']) && $_GET['date']!='' && isset($_GET['date2']) && $_GET['date2']!='')
{
  //преобразуем даты
  $date1 = substr($_GET['date'],6,4).substr($_GET['date'],3,2).substr($_GET['date'],0,2).'000001';
  $date2 = substr($_GET['date2'],6,4).substr($_GET['date2'],3,2).substr($_GET['date2'],0,2).'235959';

  if ($date1 > $date2) {Header("Location: /admin/shop_sale.php?message=date"); exit();}


  $query_add = "";

  if (isset($_GET['place_id']) && $_GET['place_id'] !=='')
   {
     $place_id = $_GET['place_id'];
     $query_add = "and place_id = $place_id";
   }

  if (isset($_GET['data_id']))
   {
     $data_id = $_GET['data_id'];
     $result = mysql_query("select element_name,store_name from shop_outgoing_data where data_id = $data_id");
     if (mysql_num_rows($result) > 0)
      {
       $row = mysql_fetch_array($result);
       $element_name = $row['element_name'];
       $store_name = $row['store_name'];
       $query_add .= " and element_name = '$element_name' and store_name = '$store_name'";
      }

   }

    $result = mysql_query("select

                           shop_outgoing_data.*,
                           shop_outgoing.date,
                           date_format(shop_outgoing.date, '%d.%m.%Y') as date2,
                           sum(shop_outgoing_data.amount) as amount,
                           sum(shop_outgoing_data.price*amount) as price

                           from shop_outgoing_data,shop_outgoing

                           where

                           shop_outgoing.date>=$date1 and
                           shop_outgoing.date<=$date2 and
                           shop_outgoing.outgoing_id = shop_outgoing_data.outgoing_id $query_add

                           group by shop_outgoing_data.element_name,shop_outgoing_data.store_name

                           order by shop_outgoing.date desc") or die(mysql_error());

  if(mysql_num_rows($result) > 0)
   {
    

 $shop_currency = 'руб.';
 $shop_currency = $user->get_cms_option('shop_currency');
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№</td>
         <td nowrap>Дата</td>
         <td nowrap>Название</td>
         <td nowrap>Артикул</td>
         <td nowrap>Количество, шт.</td>
         <td nowrap>Всего, '.$shop_currency.'</td>
       </tr>'."\n";

 $total_price = 0;
 $total_amount = 0;
 $i = 1;

     while($row = mysql_fetch_array($result))
      {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$i.'</td>
           <td align="center">'.$row['date2'].'</td>
           <td>'.htmlspecialchars($row['element_name']).'</td>
           <td>'.htmlspecialchars($row['store_name']).'</td>
           <td align="center">'.$row['amount'].'</td>
           <td align="center">'.$row['price'].'</td>
         </tr>';

   $total_price += $row['price'];
   $total_amount += $row['amount'];
   $i++;
      }

  echo '<tr><td colspan="7">&nbsp;</td></tr>
        <tr class="header">
          <td align="right" colspan="4">Всего: </td>
          <td align="center">'.$total_amount.'</td>
          <td align="center">'.$total_price.'</td>
        </tr>';
  echo '</table>'."\n";
   }

}

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>