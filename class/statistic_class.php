<?
class Statistic {
    public $date1;
    public $date2;

    public $total_hits = 0;
    public $max_hits = 0;
    public $hits = array(   0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0);

    public $total_uniques = 0;
    public $max_uniques = 0;
    public $uniques = array(0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0);
    public $max_uniques_full = 0;
    public $uniques_full = array();
    
    public $total_ips = 0;
    public $max_ips = 0;
    public $ips = array();
    
    public $max_useragents = 0;
    public $useragents = array();
    
    public $total_useragents_full = 0;
    public $max_useragents_full = 0;
    public $useragents_full = array();
    
    public $max_pages = 0;
    public $pages = array();
    
    public $total_links = 0;
    public $max_links = 0;
    public $links = array();
    
    public $total_keywords = 0;
    public $max_keywords = 0;
    public $words = array();
    
    public $cookies = 0;
    
    public $total_ctrs = 0;
    public $ctrs = array(   0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0,
                            0,0,0,0,0,0);
                     
    public $total_no_links_users = 0;
   
    public $admin = false;
    public $admin_users = array();
    public $orders = false;
    public $order_users = array();

    function __construct($date1, $date2) {
        $this->date1 = (($date1) ? $date1 : date('Ymd000001'));
        $this->date2 = (($date2) ? $date2 : date('Ymd235959'));
    }
    
    public function get_order_users() {
        $res = mysql_query("select global_id from shop_orders where order_date >= $this->date1 and order_date <= $this->date2");
        if(mysql_num_rows($res) > 0) {
            while($r = mysql_fetch_object($res))
                $this->order_users[] = $r->global_id;
        } else return false; 
    }

    public function get_admin_users() {
        $res = mysql_query("select user_id from stat_users where admin_user_id > 0") or die(mysql_error());
        if(mysql_num_rows($res) > 0) {
            while($r = mysql_fetch_object($res))
                $this->admin_users[] = $r->user_id;
        } else return false; 
    }

    public function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function check_hits($hour1 = 00, $hour2 = 24) {
        $result = mysql_query("select count(*), date_format(date, '%H') as hour from stat_hits where 1 ".($this->orders ? ' and user_id in ('.implode(',',$this->order_users).')' : '').($this->admin ? ' and user_id not in ('.implode(',',$this->admin_users).')' : '')." and date >= $this->date1 and date <= $this->date2 group by hour asc");
        if (mysql_num_rows($result) > 0) {
            while (list($value,$hour) = mysql_fetch_array($result)) {
                $this->hits[intval($hour)] = $value;
                $this->total_hits += $value;
                if ($value > $this->max_hits) $this->max_hits = $value;
				if(intval($hour) < $hour1 || intval($hour) >= $hour2)
					$this->hits[intval($hour)] = 0;				
            }
        }
    }

    public function check_uniques($hour1 = 00, $hour2 = 24) {
        $result = mysql_query("select date_format(date, '%H') as hour, user_id from stat_hits where date >= $this->date1 and date <= $this->date2 group by user_id order by hour asc");
        if (mysql_num_rows($result) > 0) {
            while (list($hour,$user_id) = mysql_fetch_array($result)) {
                if (
                    (($this->orders && in_array($user_id, $this->order_users)) || !$this->orders)
                    &&
                    (($this->admin && !in_array($user_id, $this->admin_users)) || !$this->admin)
                ) {					
					$this->uniques[(int)$hour]++;
                    $this->total_uniques++;
                }
            }
            
            foreach($this->uniques as $hour => $value) {
                if ($value > $this->max_uniques)
                    $this->max_uniques = $value;
				if($hour < $hour1 || $hour >= $hour2)
					$this->uniques[$hour] = 0;
			}
       }
    }

    public function check_ctrs() {
        $i = 0;
        $total_ctrs = 0;
        while ($i < 24) {
            if($this->uniques[$i] !== 0)
            $this->ctrs[$i] = round($this->hits[$i]/$this->uniques[$i],2);
            $i++;
        }
        $this->total_ctrs = round($this->total_hits/$this->total_uniques,2);
    }

    public function check_uniques_full($hour1 = 00, $hour2 = 24) {
        $result = mysql_query("select count(*) as user_hits, stat_users.user_id, date_format(date, '%H') as hour from stat_hits, stat_users where stat_hits.user_id = stat_users.user_id and stat_hits.date >= $this->date1 and stat_hits.date <= $this->date2 group by stat_users.user_id order by user_hits desc");
        if (mysql_num_rows($result) > 0) {
            while (list($value,$user_id,$hour) = mysql_fetch_array($result)) {
                if (
                    (($this->orders && in_array($user_id, $this->order_users)) || !$this->orders)
                    &&
                    (($this->admin && !in_array($user_id, $this->admin_users)) || !$this->admin)) {
                    if($hour >= $hour1 && $hour < $hour2)
						$this->uniques_full[$user_id] = $value;
                    if ($value > $this->max_uniques_full) $this->max_uniques_full = $value;
                }
            }
        }
        arsort($this->uniques_full);
    }

    public function check_ips($hour1 = 00, $hour2 = 24) {
		$this->ips = Array();
        $result = mysql_query("select ip, stat_users.user_id, date_format(date, '%H') as hour from stat_hits, stat_users where stat_users.user_id = stat_hits.user_id and stat_hits.date >= $this->date1 and stat_hits.date <= $this->date2 group by stat_users.user_id");
        if (mysql_num_rows($result) > 0) {
            while (list($ip,$user_id,$hour) = mysql_fetch_array($result))
                if (
                    (($this->orders && in_array($user_id, $this->order_users)) || !$this->orders)
                    &&
                    (($this->admin && !in_array($user_id, $this->admin_users)) || !$this->admin)
					&&
					($hour >= $hour1 && $hour < $hour2)
                )
                $this->ips[$ip]++;
				
			if($hour1 == 00 && $hour2 == 24) {
				foreach($this->ips as $ip => $value)
					if ($value > $this->max_ips) $this->max_ips = $value;
	
				$this->total_ips = count($this->ips);
			}
        }
        arsort($this->ips);
    }

    public function check_useragents($hour1 = 00, $hour2 = 24) {
		$this->useragents = Array();
        $result = mysql_query("select useragent, stat_users.user_id, date_format(date, '%H') as hour from stat_users, stat_hits where stat_users.user_id = stat_hits.user_id and stat_hits.date >= $this->date1 and stat_hits.date <= $this->date2 group by stat_users.user_id");
        if (mysql_num_rows($result) > 0) {
            while(list($useragent, $user_id, $hour) = mysql_fetch_array($result))
                if (
                    (($this->orders && in_array($user_id, $this->order_users)) || !$this->orders)
                    &&
                    (($this->admin && !in_array($user_id, $this->admin_users)) || !$this->admin)
					&&
					($hour >= $hour1 && $hour < $hour2)
                )
                $this->useragents[$useragent]++;
				
				if($hour1 == 00 && $hour2 == 24) {
					foreach($this->useragents as $useragent => $value)
						if ($value > $this->max_useragents)
							$this->max_useragents = $value;
				}
/*           
         foreach($this->useragents as $useragent => $value)
          {
            if(preg_match('/opera/i', $useragent))
             {
               if(preg_match('/opera\/(\d{1,2}\.\d{2})/i', $useragent, $match))
                 $this->useragents_full['opera'][$match[1]]++;
               else {$this->useragents_full['opera']['unknown']++; echo ':'.$useragent.':';}
             }
            
            if(preg_match('/firefox/i', $useragent))
             {
               if(preg_match('/firefox\/(\d{1}\.\d{1}(\.\d{1})?)/i', $useragent, $match))
                 $this->useragents_full['firefox'][$match[1]]++;
               else {$this->useragents_full['firefox']['unknown']++; echo ':'.$useragent.':';}
             }
          }
          
         arsort($this->useragents_full['opera']); 
         arsort($this->useragents_full['firefox']); 
         echo '<pre>';
         print_r($this->useragents_full);
         echo '</pre>';
*/
        }
        arsort($this->useragents);
    }

    public function check_pages($hour1 = 00, $hour2 = 24) {
		$this->pages = Array();
        $result = mysql_query("select page, date_format(date, '%H') as hour from stat_hits where 1 ".($this->orders ? ' and user_id in ('.implode(',',$this->order_users).')' : '').($this->admin ? ' and user_id not in ('.implode(',',$this->admin_users).')' : '')." and date >= $this->date1 and date <= $this->date2");
        if (mysql_num_rows($result) > 0) {
            while(list($page, $hour) = mysql_fetch_array($result)) {
				if($hour >= $hour1 && $hour < $hour2)
					$this->pages[$page]++;
			}
			
			if($hour1 == 00 && $hour2 == 24) {
				foreach($this->pages as $page => $value)
					if ($value > $this->max_pages)
						$this->max_pages = $value;
			}
        }
        arsort($this->pages);
    }

    public function check_links($hour1 = 00, $hour2 = 24) {
		$this->links = Array();
        $result = mysql_query("select count(*), link, date_format(date, '%H') as hour from stat_hits where 1 ".($this->orders ? ' and user_id in ('.implode(',',$this->order_users).')' : '').($this->admin ? ' and user_id not in ('.implode(',',$this->admin_users).')' : '')." and date >= $this->date1 and date <= $this->date2 and link != '' group by link");
        if (mysql_num_rows($result) > 0) {
            while(list($value, $link, $hour) = mysql_fetch_array($result)) {
                //считаем только внешние ссылки
                $link = trim($link);
                if ($this->is_link($link)) {
                    if($hour >= $hour1 && $hour < $hour2)
						$this->links[$link] = $value;
                    $this->total_links++;
                    if ($value > $this->max_links)
                        $this->max_links = $value;
                }
            }
        }
        arsort($this->links);
    }
    
    public function check_keywords($hour1 = 00, $hour2 = 24) {
		$this->words = Array();
        if (count($this->links) > 0) {
            foreach ($this->links as $link => $value) {
                $url = new Url_parser;
                if ($url->is_searchengine_url($link)) {
                    $params = $url->get_engine_params($link);
                    if ($params['word'] !== '<no detection>') {
                        $key = mb_strtolower(trim($params['word']),'UTF-8');
                        if (array_key_exists($key,$this->words)) $this->words[$key] += $value;
                        else $this->words[$key] = $value;
                    }
                }
                unset($url);
            }
			
			arsort($this->words);
			if($hour1 == 00 && $hour2 == 24) {            
				foreach ($this->words as $key => $value) {
					$this->total_keywords++;
					if ($value > $this->max_keywords)
						$this->max_keywords = $value;
				}
			}
        }
    }
   
    public function check_cookies() {
        $result = mysql_query("select count(*) from stat_users where date >= $this->date1 and date <= $this->date2 and cookies = 0");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $this->cookies = $row[0];
        }
    }
    
    public function is_link($link) {
        $url = parse_url(strtolower($link));
        if (is_array($url) &&
            array_key_exists('host',$url) &&
            !preg_match('/'.str_replace(array('.', '-'), array('\.', '\-'), DOMAIN).'/i', $url['host'])) return true;
        else return false;
    }
    
    public function get_yandex_cy() {
        $handle = fopen("http://search.yaca.yandex.ru/yca/cy/ch/".DOMAIN."/", "rb");
        if ($handle) {
            $page = '';
            while (!feof($handle))
                $page .= fread($handle, 8192);

            fclose($handle);
            if ($page !== '') {
                if (preg_match('/<a href=".*">([\d]{1,5})<\/a>/',$page, $match))
                return $match[1];
                else return false;
            } else return false;
        } else return false;
    }

    public function check_nolinks_users() {
        $result = mysql_query("select count(*) from stat_hits where date >= $this->date1 and date <= $this->date2 and link != '' group by user_id");
        if (mysql_num_rows($result) > 0)
            $this->total_no_links_users = $this->total_uniques - mysql_num_rows($result);
    }

    public function check_no_searchengines_users() {
        $u = new Url_parser;        
        $searchengines = '\''.implode('\'', $u->searchengines).'\'';
        
        $result = mysql_query("select count(*) from stat_hits where date >= $this->date1 and date <= $this->date2 and link != '' group by user_id");
        if (mysql_num_rows($result) > 0) {
            //$this->total_no_links_users = $this->total_uniques - mysql_num_rows($result);
        }
    }

    public function show_uniques()
     {
       echo '<h3>Уникальные посетители</h3>
             <table cellspacing="1" cellpadding="1" border="0" style="background: #cccccc;">';

       $i = 0;
	   $total_uniques = 0;
       $max_width = round(170*($this->total_uniques/$this->max_uniques));

       foreach($this->uniques as $value)
        {
          $perc = round(($value*100)/$this->total_uniques,2);
          $line_width = round(($value*$max_width)/$this->total_uniques);
          echo '<tr style="text-align: center;background: #FFFFFF;">
                  <td nowrap width="80">'.$i.':00 - '.($i+1).':00</td>
                  <td nowrap width="50">'.$value.'</td>
                  <td nowrap align="left" width="195">';
          if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                      <td><div style="width:'.$line_width.'px; height:13px; background: #33CC33">&nbsp;</div></td>
                      <td width="5px">&nbsp;</td>
                      <td>'.$perc.'%</td>
                    </tr>
                   </table>';
          echo '</td>
                </tr>';
          $i++;
		  $total_uniques += $value;
        }

       echo '<tr style="padding: 2px; text-align: center;font-weight: bold;background: #EFEFEF;">
               <td nowrap width="80">Всего</td>
               <td nowrap width="50">'.$total_uniques.'</td>
               <td>&nbsp;</td>
             </tr>
        </table>';
     }
     
    public function show_hits()
     {
        echo '<h3>Просмотры страниц</h3>
              <table cellspacing="1" cellpadding="1" border="0" style="background: #cccccc;">';
        $i = 0;
		$total_hits = 0;
        $max_width = round(170*($this->total_hits/$this->max_hits));

        foreach($this->hits as $value)
         {
           $perc = round(($value*100)/$this->total_hits,2);
           $line_width = round(($value*$max_width)/$this->total_hits);

           echo '<tr style="text-align: center;background: #FFFFFF;">
                   <td nowrap width="80">'.$i.':00 - '.($i+1).':00</td>
                   <td nowrap width="50">'.$value.'</td>
                   <td nowrap align="left" width="195">';
           if ($line_width == 0) echo '&nbsp';
           else
             echo '<table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                       <td><div style="width:'.$line_width.'px; height:13px; background: #FFCC33">&nbsp;</div></td>
                       <td width="5px">&nbsp;</td>
                       <td>'.$perc.'%</td>
                     </tr>
                    </table>';
			echo '</td>
                 </tr>';
			$i++;
			$total_hits += $value;
         }
        echo '<tr style="padding: 2px; text-align: center;font-weight: bold;background: #EFEFEF;">
                <td nowrap width="80">Всего</td>
                <td nowrap width="50">'.$total_hits.'</td>
                <td>&nbsp;</td>
              </tr>
             </table>';
     }

    public function show_pages($page, $per_page, $prms)
     {
       echo '<h2>Популярность страниц</h2>';
       navigation($page, $per_page, count($this->pages), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td width="100%">URL</td>
                <td width="100">Хитов</td>
                <td width="195">% хитов</td>
              </tr>';

       $max_width = round(170*($this->total_hits/$this->max_pages));

       $pages = array_slice($this->pages, abs($page*$per_page), $per_page, true);
       foreach ($pages as $page => $value)
        {
          $perc = round(($value*100)/$this->total_hits,2);
          $line_width = round(($value*$max_width)/$this->total_hits);

          echo '<tr style="text-align: center;background: #FFFFFF;">
                  <td align="left">';

          echo '<a href="'.$page.'" target="_blank">';
          if (strlen($page) > 70) echo substr($page,0,70);
          else echo $page;
          echo '</a>';
          if (strlen($page) > 70) echo ' ...';

          echo '</td>
                <td width="80">'.$value.'</td>
                <td align="left" width="195">';
          if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                     <td><div style="width:'.$line_width.'px; height:13px; background: #99CCFF">&nbsp;</div></td>
                     <td width="5px">&nbsp;</td>
                     <td>'.$perc.'%</td>
                   </tr>
                  </table>';
          echo '</td>
                </tr>';
        }
       echo '</table>';
       navigation($page, $per_page, count($this->pages), $prms);
     }
	 
	public function show_csv_pages() {
		echo '<tr class="header small" align="center">
					<td width="100%">URL</td>
					<td width="100">Хитов</td>
					<td width="195">% хитов</td>
				</tr>';
	
		$max_width = round(170*($this->total_hits/$this->max_pages));
	
		$pages = $this->pages;
		foreach ($pages as $page => $value)	{
			$perc = round(($value*100)/$this->total_hits,2);
			$line_width = round(($value*$max_width)/$this->total_hits);
	
			echo '<tr style="text-align: center;background: #FFFFFF;">
					<td align="left">';
	
			echo '<a href="'.$page.'" target="_blank">';
			if (strlen($page) > 70) echo substr($page,0,70);
			else echo $page;
			echo '</a>';
			if (strlen($page) > 70) echo ' ...';
	
			echo '</td>
					<td width="80">'.$value.'</td>
					<td align="left" width="195">';
			if ($line_width == 0) echo '&nbsp';
			else
				echo '<table cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td><div style="width:'.$line_width.'px; height:13px; background: #99CCFF">&nbsp;</div></td>
						<td width="5px">&nbsp;</td>
						<td>'.$perc.'%</td>
					</tr>
					</table>';
			echo '</td>
					</tr>';
		}
    }

    public function show_uniques_full($page, $per_page, $prms)
     {
       echo '<h2>Уникальные посетители: распределение хитов, внешние ссылки</h2>';
       navigation($page, $per_page, count($this->uniques_full), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td nowrap>№ пользователя<br />за все время</td>
                <td>Внешние ссылки</td>
                '.($this->orders ? '<td width="195">Состав заказа</td>' : '<td width="100" nowrap>Заказы за<br />отчетный период</td>').'
                <td width="100">Хитов</td>
              </tr>';

       $max_width = round(170*($this->total_hits/$this->max_uniques_full));

       $uniques_full = array_slice($this->uniques_full, abs($page*$per_page), $per_page, true);
       foreach ($uniques_full as $user_id => $value)
        {
          $perc = round(($value*100)/$this->total_hits,2);
          $line_width = round(($value*$max_width)/$this->total_hits);
          
          $bg = '#fff';
          if (in_array($user_id, $this->admin_users)) $bg = '#ffb';

          echo '<tr valign="top" style="background: '.$bg.';"><td align="center" nowrap>';

          if (isset($_COOKIE['stcmsid']) && $user_id == $_COOKIE['stcmsid']) echo '<a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$user_id.'\');" class="green">'.$user_id.'</span></a>';
          else echo '<a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$user_id.'\');">'.$user_id.'</a>';

          echo '</td><td>';
          
          $result = mysql_query("select * from stat_hits where user_id = $user_id and date >= $this->date1 and date <= $this->date2 and link != ''");
          if (mysql_num_rows($result) > 0)
           {
             $links = array();
             while ($row = mysql_fetch_array($result))
              {
                if($this->is_link($row['link']))
                 {
                   if (array_key_exists($row['link'], $links) &&
                       array_key_exists($row['page'], $links[$row['link']])) $links[$row['link']][$row['page']]++;
                   else $links[$row['link']][$row['page']] = 1;
                 }
              }
              
             foreach($links as $link => $val)
              {
                $host = '';
                $word = '';
                
                $url = new Url_parser;
                if ($url->is_searchengine_url($link))
                 {
                   $params = $url->get_engine_params($link);
                   $host = '<span class="h3">'.$params['host'].'</span>';
                   $word = ((mb_strlen($params['word'], 'UTF-8') > 70) ? htmlspecialchars(mb_substr($params['word'], 0, 70, 'UTF-8')).'...' : htmlspecialchars($params['word']));
                 }
                else $word = ((mb_strlen($link, 'UTF-8') > 70) ? mb_substr($link, 0, 70, 'UTF-8').'...' : $link);

                foreach($val as $cpage => $count) {
                  $bgl = '#eee';
                  if (in_array($user_id, $this->admin_users)) $bgl = '#ffe';
                  echo '<div style="background: '.$bgl.'; padding: 6px; margin: 6px;">'.(($host) ? $host.' &nbsp; ' : '').'<a class="grey" href="'.$link.'" target="_blanck">'.$word.'</a>';
                  
                  if ($count > 1) echo '<sup class="grey">'.$count.'</sup>';

                  //DIRECT
                  if (preg_match('/_openstat=/i', $cpage)) {
                    $d = explode(';', base64_decode(substr($cpage, strpos($cpage, '_openstat=') + 10)));
                    //if ($d[0] !== 'market.yandex.ru') {
                      echo '<div style="margin: 4px;"><span class="small red strong">DIRECT</span>:<br />';
                      foreach($d as $v)
                        if($v) echo '<div style="padding: 0px 0px 0px 20px;">'.$v.'</div>';
                      echo '</div>';
                    //}
                  }
                  
                  //ADWORDS
                  if (preg_match('/gclid=/i', $cpage)) {
                    echo '<div style="margin: 4px;"><span class="small red strong">ADWORDS:</span><br />';
                    $d = explode(';', substr($cpage, strpos($cpage, 'gclid=') + 6));
                    foreach($d as $v)
                      if($v) echo '<div style="padding: 0px 0px 0px 20px;">'.$v.'</div>';
                    echo '</div>';
                  }

                  echo '<div>точка входа: <a href="'.$cpage.'" target="_blank">'.((mb_strlen($cpage, 'UTF-8') > 70) ? mb_substr($cpage, 0, 70, 'UTF-8').'...' : $cpage).'</a></div>';
                  
                  echo '</div>';
                }

              }
           }


          echo '</td>';
          
          if ($this->orders)
           {
             echo '<td>';
             $result = mysql_query("select * from shop_orders where global_id = $user_id and order_date >= $this->date1 and order_date <= $this->date2");
             if (mysql_num_rows($result) > 0)
              {
                $i = 1;
                while ($row = mysql_fetch_object($result))
                 {
                   echo '<div><strong><a href="#" onclick="sw(\'';
                   //специальная форма заказа
                   if (file_exists($_SERVER['DOCUMENT_ROOT'].'/admin/modules/shop_order_view/shop_order_view.php')) $text .= 'modules/shop_order_view';
                   else echo '/admin/editors';
                   echo '/shop_order_view.php?id='.$row->order_id.'\'); return false;">Заказ №'.$row->order_id.'</a></strong></div>';
                   
                   $res = mysql_query("select * from shop_order_values where order_id = ".$row->order_id);
                   if (mysql_num_rows($res) > 0)
                    {
                      $v = 1;
                      while ($r = mysql_fetch_object($res))
                       {
                         echo '<div class="small">'.htmlspecialchars($r->element_name).'</div>';
                         echo '<div class="small grey">id: '.$r->element_id.'</div>';
                         echo '<div class="small grey">количество: '.$r->quantity.'</div>';
                         echo '<div class="small grey">цена: '.number_format($r->price, 2, ',', ' ').'</div>';
                         echo '<div class="small grey" style="'.(($v < mysql_num_rows($res)) ? 'border-bottom: #ccc 1px dotted;' : '').'">сумма: '.number_format($r->price*$r->quantity, 2, ',', ' ').'</div>';                        
                         $v++;
                       }
                    }
                   
                   echo '<div style="border-top: #ccc 1px solid;">Сумма заказа: '.number_format($row->price+$row->delivery_price, 2, ',', ' ').'</div>';
                   if ($i < mysql_num_rows($result)) echo '<div>&nbsp;</div>';
                   $i++;
                 }
              }
             echo '</td>';            
           }
          else
           {
             $period_order = ((mysql_num_rows(mysql_query("select * from shop_orders where global_id = $user_id and order_date >= $this->date1 and order_date <= $this->date2"))) ? true : false);
             echo '<td align="center">'.(($period_order) ? '<img src="/admin/images/icons/tick-small.png">' : '').'</td>';                         
           }
           
          echo '<td align="center">'.$value.'</td>';
/*
                <td align="left">';
        if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                     <td><div style="width:'.$line_width.'px; height:13px; background: #33CC33">&nbsp;</div></td>
                     <td width="5px">&nbsp;</td>
                     <td>'.$perc.'%</td>
                   </tr>
                  </table>';
          echo '</td>';
*/
          echo '</tr>';
        }
       echo '</table>'; 
       navigation($page, $per_page, count($this->uniques_full), $prms);
       if ($this->date == date("YmdHis")) echo '<br/><span class="red">*</span> <span class="grey"> - внешние ссылки</span>';
     }
	 
	public function show_csv_uniques_full() {
		echo '<tr class="header small" align="center">
                <td nowrap>№ пользователя<br />за все время</td>
                <td>Внешние ссылки</td>
				<td>Точка входа</td>
				<td>Ключевое слово</td>
                '.($this->orders ? '<td width="195">Состав заказа</td>' : '<td width="100" nowrap>Заказы за<br />отчетный период</td>').'
                <td width="100">Хитов</td>
              </tr>';

		$max_width = round(170*($this->total_hits/$this->max_uniques_full));

		$uniques_full = $this->uniques_full;
		foreach ($uniques_full as $user_id => $value) {
			$perc = round(($value*100)/$this->total_hits,2);
			$line_width = round(($value*$max_width)/$this->total_hits);
          
			$bg = '#fff';
			if (in_array($user_id, $this->admin_users)) $bg = '#ffb';

			echo '<tr valign="top" style="background: '.$bg.';"><td align="center" nowrap>';

			if (isset($_COOKIE['stcmsid']) && $user_id == $_COOKIE['stcmsid']) echo '<a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$user_id.'\');" class="green">'.$user_id.'</span></a>';
			else echo '<a href="javascript:sw(\'/admin/stat/stat_user_id.php?user_id='.$user_id.'\');">'.$user_id.'</a>';

			echo '</td><td>';
          
			$result = mysql_query("select * from stat_hits where user_id = $user_id and date >= $this->date1 and date <= $this->date2 and link != ''");
			if (mysql_num_rows($result) > 0) {
				$links = array();
				while ($row = mysql_fetch_array($result)) {
					if($this->is_link($row['link'])) {
						if (array_key_exists($row['link'], $links) &&
							array_key_exists($row['page'], $links[$row['link']])) $links[$row['link']][$row['page']]++;
						else $links[$row['link']][$row['page']] = 1;
					}
				}
				
				$points = array();
				$words = array();
				foreach($links as $link => $val) {
					$host = '';
					$word = '';
                
					$url = new Url_parser;
					if ($url->is_searchengine_url($link)) {
						$params = $url->get_engine_params($link);
						$host = '<span class="h3">'.$params['host'].'</span>';
						$word = ((mb_strlen($params['word'], 'UTF-8') > 70) ? htmlspecialchars(mb_substr($params['word'], 0, 70, 'UTF-8')).'...' : htmlspecialchars($params['word']));
					}
					else $word = ((mb_strlen($link, 'UTF-8') > 70) ? mb_substr($link, 0, 70, 'UTF-8').'...' : $link);
					
					foreach($val as $cpage => $count) {
						$bgl = '#eee';
						if (in_array($user_id, $this->admin_users)) $bgl = '#ffe';
						echo '<div style="background: '.$bgl.'; padding: 6px; margin: 6px;">'.(($host) ? $host.' &nbsp; ' : '').'<a class="grey" href="'.$link.'" target="_blanck">'.$word.'</a>';
						
						$words[] = $word;
						
						if ($count > 1) echo '<sup class="grey">'.$count.'</sup>';
	
						//DIRECT
						if (preg_match('/_openstat=/i', $cpage)) {
							$d = explode(';', base64_decode(substr($cpage, strpos($cpage, '_openstat=') + 10)));
							//if ($d[0] !== 'market.yandex.ru') {
							echo '<div style="margin: 4px;"><span class="small red strong">DIRECT</span>:<br />';
							foreach($d as $v)
								if($v) echo '<div style="padding: 0px 0px 0px 20px;">'.$v.'</div>';
							echo '</div>';
							//}
						}
					
						//ADWORDS
						if (preg_match('/gclid=/i', $cpage)) {
							echo '<div style="margin: 4px;"><span class="small red strong">ADWORDS:</span><br />';
							$d = explode(';', substr($cpage, strpos($cpage, 'gclid=') + 6));
							foreach($d as $v)
							if($v) echo '<div style="padding: 0px 0px 0px 20px;">'.$v.'</div>';
							echo '</div>';
						}
	
						$points[] =  '<div><a href="'.$cpage.'" target="_blank">'.((mb_strlen($cpage, 'UTF-8') > 70) ? mb_substr($cpage, 0, 70, 'UTF-8').'...' : $cpage).'</a></div>';
					
						echo '</div>';
					}
					
				}
				
				echo '</td><td>';
					
				foreach ($points as $point)
					echo $point;
					
				echo '</td><td>';
					
				foreach ($words as $w)
					echo '<div>'.$w.'</div>';
			}else{
				echo '</td><td></td><td>';
			}


			echo '</td>';
			
			if ($this->orders) {
				echo '<td>';
				$result = mysql_query("select * from shop_orders where global_id = $user_id and order_date >= $this->date1 and order_date <= $this->date2");
				if (mysql_num_rows($result) > 0) {
					$i = 1;
					while ($row = mysql_fetch_object($result)) {
						echo '<div><strong><a href="#" onclick="sw(\'';
						//специальная форма заказа
						if (file_exists($_SERVER['DOCUMENT_ROOT'].'/admin/modules/shop_order_view/shop_order_view.php')) $text .= 'modules/shop_order_view';
						else echo '/admin/editors';
						echo '/shop_order_view.php?id='.$row->order_id.'\'); return false;">Заказ №'.$row->order_id.'</a></strong></div>';
						
						$res = mysql_query("select * from shop_order_values where order_id = ".$row->order_id);
						if (mysql_num_rows($res) > 0) {
							$v = 1;
							while ($r = mysql_fetch_object($res)) {
								echo '<div class="small">'.htmlspecialchars($r->element_name).'</div>';
								echo '<div class="small grey">id: '.$r->element_id.'</div>';
								echo '<div class="small grey">количество: '.$r->quantity.'</div>';
								echo '<div class="small grey">цена: '.number_format($r->price, 2, ',', ' ').'</div>';
								echo '<div class="small grey" style="'.(($v < mysql_num_rows($res)) ? 'border-bottom: #ccc 1px dotted;' : '').'">сумма: '.number_format($r->price*$r->quantity, 2, ',', ' ').'</div>';                        
								$v++;
							}
						}
					
						echo '<div style="border-top: #ccc 1px solid;">Сумма заказа: '.number_format($row->price+$row->delivery_price, 2, ',', ' ').'</div>';
						if ($i < mysql_num_rows($result)) echo '<div>&nbsp;</div>';
						$i++;
					}
				}
				echo '</td>';            
			}else{
				$period_order = ((mysql_num_rows(mysql_query("select * from shop_orders where global_id = $user_id and order_date >= $this->date1 and order_date <= $this->date2"))) ? true : false);
				echo '<td align="center">'.(($period_order) ? 'Были' : '').'</td>';                         
			}
			
			echo '<td align="center">'.$value.'</td>';
	
			echo '</tr>';
		}
    }

    public function show_ips($page, $per_page, $prms)
     {
       echo '<h2>IP адреса</h2>';
       navigation($page, $per_page, count($this->ips), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td width="100%">IP адрес</td>
                <td width="100">Количество</td>
                <td width="195">% от общего количества</td>
              </tr>';

       $max_width = round(170*($this->total_ips/$this->max_ips));

       $ips = array_slice($this->ips, abs($page*$per_page), $per_page, true);
       foreach ($ips as $ip => $value)
        {
          $perc = round(($value*100)/$this->total_ips,2);
          $line_width = round(($value*$max_width)/$this->total_ips);

          echo '<tr style="text-align: center;background: #FFFFFF;">
                  <td align="left">';

          if ($ip == $_SERVER['REMOTE_ADDR']) echo '<span class="green">'.$ip.'</span>';
          else echo $ip;

          echo ' &nbsp; <a style="color:#666" href="javascript:sw(\'/admin/stat/whois.php?ip='.$ip.'\');">whois</a>';

          $result = mysql_query("select * from stat_ips_detect where ip = '$ip'");
          if (mysql_num_rows($result) > 0)
           {
             $row = mysql_fetch_array($result);
             echo ' &nbsp; <span class="grey">('.htmlspecialchars($row['description']).')</span>';
           }

          echo '</td>
                <td width="80">'.$value.'</td>
                <td align="left" width="195">';
        if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                     <td><div style="width:'.$line_width.'px; height:13px; background: #00CC99">&nbsp;</div></td>
                     <td width="5px">&nbsp;</td>
                     <td>'.$perc.'%</td>
                   </tr>
                  </table>';
          echo '</td>
                </tr>';
        }
       echo '</table>';
       navigation($page, $per_page, count($this->ips), $prms);
     }
	 
	public function show_csv_ips() {
		echo '<tr class="header small" align="center">
					<td width="100%">IP адрес</td>
					<td width="100">Количество</td>
					<td width="195">% от общего количества</td>
				</tr>';
	
		$max_width = round(170*($this->total_ips/$this->max_ips));
	
		$ips = $this->ips;
		foreach ($ips as $ip => $value) {
			$perc = round(($value*100)/$this->total_ips,2);
			$line_width = round(($value*$max_width)/$this->total_ips);
	
			echo '<tr style="text-align: center;background: #FFFFFF;">
					<td align="left">';
	
			if ($ip == $_SERVER['REMOTE_ADDR']) echo '<span class="green">'.$ip.'</span>';
			else echo $ip;
	
			echo ' &nbsp; <a style="color:#666" href="javascript:sw(\'/admin/stat/whois.php?ip='.$ip.'\');">whois</a>';
	
			$result = mysql_query("select * from stat_ips_detect where ip = '$ip'");
			if (mysql_num_rows($result) > 0)
			{
				$row = mysql_fetch_array($result);
				echo ' &nbsp; <span class="grey">('.htmlspecialchars($row['description']).')</span>';
			}
	
			echo '</td>
					<td width="80">'.$value.'</td>
					<td align="left" width="195">';
			if ($line_width == 0) echo '&nbsp';
			else
				echo '<table cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td><div style="width:'.$line_width.'px; height:13px; background: #00CC99">&nbsp;</div></td>
						<td width="5px">&nbsp;</td>
						<td>'.$perc.'%</td>
					</tr>
					</table>';
			echo '</td>
					</tr>';
		}
    }

    public function show_useragents($page, $per_page, $prms)
     {
       echo '<h2>Браузеры</h2>';
       navigation($page, $per_page, count($this->useragents), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td width="100%">User agent</td>
                <td width="100">Количество</td>
                <td width="195">% от общего количества</td>
              </tr>';

       $max_width = round(170*($this->total_uniques/$this->max_useragents));

       $useragents = array_slice($this->useragents, abs($page*$per_page), $per_page, true);
       foreach ($useragents as $useragent => $value)
        {
          if ($parametr == 'brief') {if ($i > 9) break;}
          $perc = round(($value*100)/$this->total_uniques,2);
          $line_width = round(($value*$max_width)/$this->total_uniques);

          echo '<tr style="text-align: center;background: #FFFFFF;">
                  <td align="left">';

          echo $useragent;

          echo '</td>
                <td width="80">'.$value.'</td>
                <td align="left" width="195">';
          if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                     <td><div style="width:'.$line_width.'px; height:13px; background: #CC33FF">&nbsp;</div></td>
                     <td width="5px">&nbsp;</td>
                     <td>'.$perc.'%</td>
                   </tr>
                  </table>';
          echo '</td>
               </tr>';
        }
       echo '</table>';
       navigation($page, $per_page, count($this->useragents), $prms);
     }
	 
	public function show_csv_useragents() {
		echo '<tr class="header small" align="center">
					<td width="100%">User agent</td>
					<td width="100">Количество</td>
					<td width="195">% от общего количества</td>
				</tr>';
	
		$max_width = round(170*($this->total_uniques/$this->max_useragents));
	
		$useragents = $this->useragents;
		foreach ($useragents as $useragent => $value) {
			if ($parametr == 'brief') {if ($i > 9) break;}
			$perc = round(($value*100)/$this->total_uniques,2);
			$line_width = round(($value*$max_width)/$this->total_uniques);
	
			echo '<tr style="text-align: center;background: #FFFFFF;">
					<td align="left">';
	
			echo $useragent;
	
			echo '</td>
					<td width="80">'.$value.'</td>
					<td align="left" width="195">';
			if ($line_width == 0) echo '&nbsp';
			else
				echo '<table cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td><div style="width:'.$line_width.'px; height:13px; background: #CC33FF">&nbsp;</div></td>
						<td width="5px">&nbsp;</td>
						<td>'.$perc.'%</td>
					</tr>
					</table>';
			echo '</td>
				</tr>';
		}
    }

    public function show_links($page, $per_page, $prms)
     {
       echo '<h2>Ссылающиеся страницы</h2>';
       navigation($page, $per_page, count($this->links), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td width="100%">URL</td>
                <td width="100">Количество</td>
                <td width="195">% от общего количества</td>
              </tr>';

       $max_width = round(170*($this->total_links/$this->max_links));

       $links = array_slice($this->links, abs($page*$per_page), $per_page, true);
       $favicons = array();
       foreach ($links as $link => $value)
        {
          if ($parametr == 'brief') {if ($i > 9) break;}
          $perc = round(($value*100)/$this->total_links,2);
          $line_width = round(($value*$max_width)/$this->total_links);

          echo '<tr style="text-align: center;background: #FFFFFF;">
                  <td align="left"><table cellspacing="0" cellpadding="0"><tr>';

          $url = new Url_parser;
          $params = $url->get_engine_params($link);
          if (array_key_exists($params['host'], $favicons))
            $favicons[$params['host']][1]++;
          else
           {
             if (file_get_contents('http://'.$params['host'].'/favicon.ico')) $favicons[$params['host']][0] = 'true';
             else $favicons[$params['host']][0] = 'false';
           }
           
          if ($favicons[$params['host']][0] == 'true') echo '<td nowrap><img src="http://'.$params['host'].'/favicon.ico" alt="" width="16" height="16">&nbsp;&nbsp;</td>';

          echo '<td>';
          if ($url->is_searchengine_url($link))
           {
             if ($params['word'] == '<no detection>') echo '<a href="'.$link.'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="red">-</span>';
	     else echo '<a href="'.$link.'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="grey">'.htmlspecialchars($params['word']).'</span>';
           }
          else
           {
             echo '<a href="'.$link.'" target="_blank">';
             if (strlen($link) > 70) echo substr($link,0,70);
             else echo $link;
             echo '</a>';
             if (strlen($link) > 70) echo ' ...';
           }
          echo '</td>';

          echo '</tr></table></td>
                <td width="80">'.$value.'</td>
                 <td align="left" width="195">';
          if ($line_width == 0) echo '&nbsp';
          else
            echo '<table cellspacing="0" cellpadding="0" border="0">
                   <tr>
                     <td><div style="width:'.$line_width.'px; height:13px; background: #CC3333">&nbsp;</div></td>
                     <td width="5px">&nbsp;</td>
                     <td>'.$perc.'%</td>
                   </tr>
                  </table>';

          echo '</td>
               </tr>';
        }
       echo '</table>';
       navigation($page, $per_page, count($this->links), $prms);
     }
	 
	public function show_csv_links() {
		echo '<tr class="header small" align="center">
                <td width="100%">URL</td>
				<td>Запросы</td>
                <td width="100">Количество</td>
                <td width="195">% от общего количества</td>
              </tr>';

		$max_width = round(170*($this->total_links/$this->max_links));

		$links = $this->links;
		$favicons = array();
		foreach ($links as $link => $value) {
			if ($parametr == 'brief') {if ($i > 9) break;}
			$perc = round(($value*100)/$this->total_links,2);
			$line_width = round(($value*$max_width)/$this->total_links);
	
			echo '<tr style="text-align: center;background: #FFFFFF;">
					<td align="left">';
	
			$url = new Url_parser;
			$params = $url->get_engine_params($link);
			if (array_key_exists($params['host'], $favicons))
				$favicons[$params['host']][1]++;
			else {
				if (file_get_contents('http://'.$params['host'].'/favicon.ico')) $favicons[$params['host']][0] = 'true';
				else $favicons[$params['host']][0] = 'false';
			}
			
			if ($favicons[$params['host']][0] == 'true') echo '<img src="http://'.$params['host'].'/favicon.ico" alt="" width="16" height="16">&nbsp;&nbsp;';
	
			if ($url->is_searchengine_url($link)) {
				if ($params['word'] == '<no detection>') echo '<a href="'.$link.'" target="_blank">'.$params['host'].'</a> &nbsp; <span class="red">-</span>';
				else echo '<a href="'.$link.'" target="_blank">'.$params['host'].'</a> &nbsp;';
				echo '</td><td><span class="grey">'.htmlspecialchars($params['word']).'</span>';
            }else{
				echo '<a href="'.$link.'" target="_blank">';
				if (strlen($link) > 70) echo substr($link,0,70);
				else echo $link;
				echo '</a>';
				if (strlen($link) > 70) echo ' ...';
				echo '</td><td>';
			}

			echo '</td>
                <td width="80">'.$value.'</td>
                 <td align="left" width="195">';
			if ($line_width == 0) echo '&nbsp';
			else
				echo $perc;
	
			echo '</td>
               </tr>';
        }
    }
     
    public function show_keywords($page, $per_page, $prms)
     {
       echo $this->total_words;
       echo '<h2>Поисковые слова</h2>';
       navigation($page, $per_page, count($this->words), $prms);
       echo '<table width="100%" cellspacing="1" cellpadding="1" style="background: #cccccc;">
              <tr class="header small" align="center">
                <td width="100%">Слово</td>
                <td width="100">Количество</td>
                <td width="100">&nbsp;</td>
                <td width="100">&nbsp;</td>
                <td width="100">&nbsp;</td>
                <td width="195">% от общего количества</td>
              </tr>';

          $max_width = round(170*($this->total_keywords/$this->max_keywords));

       $words = array_slice($this->words, abs($page*$per_page), $per_page, true);
          foreach ($words as $key => $value)
           {
             if ($param == 'brief') {if ($i > 9) break;}
             $perc = round(($value*100)/$this->total_keywords,2);
             $line_width = round(($value*$max_width)/$this->total_keywords);
             echo '<tr style="text-align: center;background: #FFFFFF;">
                     <td align="left">'.htmlspecialchars($key).' &nbsp; <a href="javascript:sw(\'/admin/stat/stat_users_by_keyword.php?keyword='.rawurlencode($key).'&date1='.$this->date1.'&date2='.$this->date2.'\');" class="small">пути по сайту</a></td>
                     <td width="80" nowrap>'.$value.'</td>
                     <td><a href="http://yandex.ru/yandsearch?text='.rawurlencode($key).'" target="_blank">Yandex</a></td>
                     <td><a href="http://www.google.ru/search?&q='.rawurlencode($key).'" target="_blank">Google</a></td>
                     <td><a href="http://nova.rambler.ru/search?query='.rawurlencode($key).'" target="_blank">Rambler</a></td>
                     <td align="left" width="195">';
             if ($line_width == 0) echo '&nbsp';
             else
             echo '<table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                      <td><div style="width:'.$line_width.'px; height:13px; background: #CCCCCC">&nbsp;</div></td>
                      <td width="5px">&nbsp;</td>
                      <td>'.$perc.'%</td>
                    </tr>
                   </table>';
             echo '</td>
                   </tr>';
           }
          echo '</table>';
       navigation($page, $per_page, count($this->words), $prms);
    }
	
	public function show_csv_keywords() {
		echo $this->total_words;
		echo '<tr class="header small" align="center">
					<td width="100%">Слово</td>
					<td width="100">Количество</td>
					<td width="100">&nbsp;</td>
					<td width="100">&nbsp;</td>
					<td width="100">&nbsp;</td>
					<td width="195">% от общего количества</td>
				</tr>';
	
		$max_width = round(170*($this->total_keywords/$this->max_keywords));
	
		$words = $this->words;
		foreach ($words as $key => $value) {
			if ($param == 'brief') {if ($i > 9) break;}
			$perc = round(($value*100)/$this->total_keywords,2);
			$line_width = round(($value*$max_width)/$this->total_keywords);
			echo '<tr style="text-align: center;background: #FFFFFF;">
					<td align="left">'.htmlspecialchars($key).' &nbsp; <a href="javascript:sw(\'/admin/stat/stat_users_by_keyword.php?keyword='.rawurlencode($key).'&date1='.$this->date1.'&date2='.$this->date2.'\');" class="small">пути по сайту</a></td>
					<td width="80" nowrap>'.$value.'</td>
					<td><a href="http://yandex.ru/yandsearch?text='.rawurlencode($key).'" target="_blank">Yandex</a></td>
					<td><a href="http://www.google.ru/search?&q='.rawurlencode($key).'" target="_blank">Google</a></td>
					<td><a href="http://nova.rambler.ru/search?query='.rawurlencode($key).'" target="_blank">Rambler</a></td>
					<td align="left" width="195">';
			if ($line_width == 0) echo '&nbsp';
			else
			echo '<table cellspacing="0" cellpadding="0" border="0">
					<tr>
					<td><div style="width:'.$line_width.'px; height:13px; background: #CCCCCC">&nbsp;</div></td>
					<td width="5px">&nbsp;</td>
					<td>'.$perc.'%</td>
					</tr>
				</table>';
			echo '</td>
				</tr>';
		}
    }

    public function show_info() {
        echo '  <fieldset><legend>Дополнительная информация</legend>';
        echo '  <table cellspacing="0" cellpadding="0"><tr valign="top"><td>
             
                <table cellspacing="0" cellpadding="3">
                    <tr>
                        <td>Среднее количество просмотренных страниц:</td>
                        <td><strong>'.$this->total_ctrs.'</strong></td>
                    </tr>';
              
        if ($this->cookies > 0)
        echo '      <tr>
                        <td>Пользователей без поддержки cookies:</td>
                        <td><strong>'.$this->cookies.'</strong></td>
                    </tr>';
              
        if ($this->total_no_links_users > 0)
        echo '      <tr>
                        <td>Пользователей без внешних ссылок:</td>
                        <td><strong>'.$this->total_no_links_users.'</strong></td>
                    </tr>';
              
        echo '  </table></td></tr></table>';
        echo '  </fieldset>';
    }

    public function show_yandex_cy() {
        if ($this->get_yandex_cy())
        echo '  <fieldset>
                    <legend>Поисковые системы</legend>
                    Индекс цитирования (тИЦ) ресурса Яndex: <strong>'.$this->get_yandex_cy().'</strong>
                </fieldset>';
    }


}
?>
