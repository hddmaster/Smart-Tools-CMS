<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['keyword']) &&
    isset($_GET['date1']) &&
    isset($_GET['date2']))
 {
 
   $keyword = rawurldecode($_GET['keyword']);
   $sdate1 = substr($_GET['date1'], 6, 2).'.'.substr($_GET['date1'], 4, 2).'.'.substr($_GET['date1'], 0, 4);
   $sdate2 = substr($_GET['date2'], 6, 2).'.'.substr($_GET['date2'], 4, 2).'.'.substr($_GET['date2'], 0, 4);
   $sdt = (($sdate1 == $sdate2) ? 'за '.$sdate1 : 'за период с '.$sdate1.' по '.$sdate2);
   echo '<h3>Стастистика переходов по ключевому слову <span class="h2">'.$keyword.'</span> '.$sdt.'</h3>';

   $date1 = ((isset($_GET['date1'])) ? $_GET['date1'] : date('Ymd').'000001');
   $date2 = ((isset($_GET['date2'])) ? $_GET['date2'] : date('Ymd').'235959');
   $stat = new Statistic($date1,$date2);
   $stat->check_links();
   
   $links = array();
   
   foreach ($stat->links as $link => $value)
    {
      $url = new Url_parser;
      if ($url->is_searchengine_url($link))
       {
         $params = $url->get_engine_params($link);
         if ($params['word'] !== '<no detection>')
          {
            $word = mb_strtolower(trim($params['word']),'UTF-8');
            if ($word == $keyword) $links[] = $link;
          }
       }
      unset($url);
    }
    
   $result = mysql_query("select * from stat_hits where date >= $date1 and date <= $date2 and link in ('".implode('\',\'', $links)."') group by user_id");
   if (mysql_num_rows($result) > 0)
    {
      while($row = mysql_fetch_array($result))
       {
	
    
   
   echo '<h3>№ посетителя за все время: '.$row['user_id'].'</h3>';
   $res = mysql_query("select *, date_format(date, '%d.%m.%Y %H:%i:%s') as date_f from stat_hits where user_id = ".$row['user_id']." order by date asc");
   if (mysql_num_rows($res) > 0)
    {
      echo '<table width="100%" cellspacing="1" cellpadding="1" border="0" style="background: #cccccc;">
            <tr style="padding: 2px; text-align: center;font-weight: bold;background: #EFEFEF;">
              <td width="8%">Время</td>
              <td width="8%">Время на странице, сек</td>
              <td width="28%">Заголовок страницы</td>
              <td width="28%">Адрес</td>
              <td width="28%">Внешняя ссылка</td>
            </tr>';
            
      $t = 0;
      $i = 0;
      $num_res = mysql_num_rows($res);
      
      while ($r = mysql_fetch_array($res))
       {         
         $t_ = substr($r['date'],11,9);
         $old_t = $t;
         $t = 3600*intval(substr($t_,0,2)) + 60*intval(substr($t_,3,2)) + intval(substr($t_,6,2));
         
         if ($i > 0)
          {
            echo '<script language="javascript">
                    t = '.($t-$old_t).';
                    if (t > 600 || t < 0) document.getElementById(\'td_'.$row['user_id'].'_'.($i-1).'\').innerHTML = \'<img src="/admin/images/icons/status-busy.png">\';
                    else document.getElementById(\'td_'.$row['user_id'].'_'.($i-1).'\').innerHTML = \''.($t-$old_t).'\';
                  </script>';
          }
	  
	 list($date, $time) = explode(' ', $r['date_f']); 
          
         echo '<tr style="text-align: center; background: #fff;">
                  <td nowrap>'.$date.'<br /><span class="small">'.$time.'</td>
                  <td nowrap id="td_'.$row['user_id'].'_'.$i.'">';

         if ($i == $num_res-1)
          {
            $t_now = date("His");
            $now = 3600*intval(substr($t_now,0,2)) + 60*intval(substr($t_now,2,2)) + intval(substr($t_now,4,2));
            if ($now-$old_t > 600) echo '<img src="/admin/images/icons/status-busy.png"> ';
            echo $now-$old_t;
          }

         echo '</td><td align="left">';
/*
         $page = file_get_contents($r['page']);
         if ($page)
          {
            $title = ((preg_match_all('/(?<=\<title>)[\s]*.*[\s]*(?=\<\/title>)/i', $page, $matches)) ? $matches[0][0] : 'no title');
            echo ((strlen($title) > 70) ? mb_substr($title,0,70,'UTF-8').' ...' : $title);
          }
*/

         echo '</td><td align="left"><a href="'.$r['page'].'" target="_blank">';
         if (strlen($r['page']) > 70) echo substr($r['page'],0,70);
         else echo $r['page'];
         echo '</a>';
         if (strlen($r['page']) > 70) echo ' ...';
         echo '</td><td align="left">';
         
         if ($r['link'])
          {
            $url = new Url_parser;
            if ($url->is_searchengine_url($r['link']))
             {
               $params = $url->get_engine_params($r['link']);
               if ($params['word'] == '<no detection>') echo '<a href="'.$r['link'].'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="red">-</span>';
	       else echo '<a href="'.$r['link'].'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="grey">'.htmlspecialchars($params['word']).'</span>';
             }
            else
             {
               echo '<a href="'.$r['link'].'" target="_blank">';
               if (strlen($r['link']) > 70) echo substr($r['link'],0,70);
               else echo $r['link'];
               echo '</a>';
               if (strlen($r['link']) > 70) echo ' ...';
             }
	    
	    if (preg_match('/_openstat/i', $r['page'])) echo ' <span class="small red strong">DIRECT</span>'; 
          }
         else echo '&nbsp;';
         echo '</td></tr>';

         $i++;

       }
      echo '</table><div>&nbsp;</div>';
    }


       }
    }
   
 }


  } else $user->no_rules('view');

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>