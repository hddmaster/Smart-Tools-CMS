<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['user_id']) && trim($_GET['user_id']) !== '')
 {
 
   $user_id = $_GET['user_id'];
   echo '<table align="right">
				<tr>
					<td>
						<img src="/admin/images/icons/user.png">
					</td>
					<td>
						<a onclick="sw(\'/admin/stat/orders_user_id.php?user_id='.$user_id.'\'); return false;" href="#" class="h3">Покупки пользователя</a>
					</td>
				</tr>
			</table>';
   echo '<h3>№ посетителя за все время: '.$user_id.'</h3>';

   $result = mysql_query("select U.*, (select count(*) from stat_hits where user_id = U.user_id) as hits from stat_users as U where U.user_id = $user_id") or die(mysql_error());
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result); 
      echo '<fieldset>
            <legend>Статистика</legend>
            User agent: <strong>'.$row['useragent'].'</strong><br />
            Cookies: <strong>'.(($row['cookies']) ? 'Да' : 'Нет').'</strong><br />
            Количество просмотренных страниц: <strong>'.$row['hits'].'</strong><br />
            IP-адрес: <strong>'.$row['ip'].'</strong>
            </fieldset>';
    }

   $result = mysql_query("select
			  *,
			  date_format(date, '%d.%m.%Y %H:%i:%s') as date_f,
			  date_format(date, '%Y%m%d') as date_c
			  from
			  stat_hits
			  where
			  user_id=$user_id
			  order by date asc");
   if (mysql_num_rows($result) > 0)
    {
      $date_c = '';
      $i = 0;
      $num_res = mysql_num_rows($result);      
      while ($row = mysql_fetch_array($result))
       {
	 if ($row['date_c'] !== $date_c)
	  {
	    if ($row['date_c'] !== $date_c && $i > 0) echo '</table><p><img src="/admin/images/icons/status-busy.png"> - потенциальная точка выхода (10 мин. неактивности)</p>';
            echo '<table width="100%" cellspacing="1" cellpadding="2" border="0" style="background: #ccc;">
                   <tr style="padding: 2px; text-align: center;font-weight: bold; background: #efefef;" class="small">
                     <td>Дата</td>
                     <td width="8%">Время на странице, сек</td>
                     <td width="28%">Заголовок страницы</td>
                     <td width="28%">Адрес</td>
                     <td width="28%">Внешняя ссылка</td>
                   </tr>';
            $t = 0;
	  }
	  
         $t_ = substr($row['date'],11,9);
         $old_t = $t;
         $t = 3600*intval(substr($t_,0,2)) + 60*intval(substr($t_,3,2)) + intval(substr($t_,6,2));
         
         if ($i > 0)
          {
            echo '<script language="javascript">
                    t = '.($t-$old_t).';
                    if (t > 600 || t < 0) document.getElementById(\'td_'.($i-1).'\').innerHTML = \'<img src="/admin/images/icons/status-busy.png">\';
                    else document.getElementById(\'td_'.($i-1).'\').innerHTML = \''.($t-$old_t).'\';
                  </script>';
          }
	  
         echo '<tr style="text-align: center; background: #fff;" class="small">
                  <td nowrap>'.$row['date_f'].'</td>
                  <td nowrap id="td_'.$i.'">';

         if ($i == $num_res-1)
          {
            $t_now = date("His");
            $now = 3600*intval(substr($t_now,0,2)) + 60*intval(substr($t_now,2,2)) + intval(substr($t_now,4,2));
            if ($now-$old_t > 60) echo '<img src="/admin/images/icons/status-busy.png"> ';
            echo $now-$old_t;
          }

         echo '</td><td align="left">';
/*
         $page = file_get_contents($row['page']);
         if ($page)
          {
            $title = ((preg_match_all('/(?<=\<title>)[\s]*.*[\s]*(?=\<\/title>)/i', $page, $matches)) ? $matches[0][0] : 'no title');
            echo ((strlen($title) > 70) ? mb_substr($title,0,70,'UTF-8').' ...' : $title);
          }
*/
         echo '</td><td align="left"><a href="'.$row['page'].'" target="_blank">';
         if (strlen($row['page']) > 70) echo substr($row['page'],0,70);
         else echo $row['page'];
         echo '</a>';
         if (strlen($row['page']) > 70) echo ' ...';
         echo '</td><td align="left">';
         
         if ($row['link'])
          {
            $url = new Url_parser;
            if ($url->is_searchengine_url($row['link']))
             {
               $params = $url->get_engine_params($row['link']);
               if ($params['word'] == '<no detection>') echo '<a href="'.$row['link'].'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="red">-</span>';
	       else echo '<a href="'.$row['link'].'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="grey">'.htmlspecialchars($params['word']).'</span>';
             }
            else
             {
               echo '<a href="'.$row['link'].'" target="_blank">';
               if (strlen($row['link']) > 70) echo substr($row['link'],0,70);
               else echo $row['link'];
               echo '</a>';
               if (strlen($row['link']) > 70) echo ' ...';
             }
	    
	    if (preg_match('/_openstat/i', $row['page'])) echo ' <span class="small red strong">DIRECT</span>'; 
          }
         else echo '&nbsp;';
         echo '</td>';
         echo '</tr>';
         $i++;
         
	 $date_c = $row['date_c'];
       }
      echo '</table><p><img src="/admin/images/icons/status-busy.png"> - потенциальная точка выхода (1 мин. неактивности)</p>';
    }
 }


  } else $user->no_rules('view');

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>