<?
function navigation($page,$per_page,$total_rows,$params='',$t = 'записей на странице') {
    $pp1 = 20;
    $pp2 = 50;
    $pp3 = 100;
    
    $page = intval($page);
    $per_page = intval($per_page);
    $total_rows = intval($total_rows);
    $num_pages = ceil($total_rows/$per_page);
    
    $add_params = '';
    if(is_array($params)) {
		foreach($params as $key => $value) {
			if(is_array($value)) {
				foreach($value as $v)
					$add_params .= '&'.$key.'[]='.rawurlencode($v);
			} else
				$add_params .= '&'.$key.'='.rawurlencode($value);
		}
    }

    echo '<div class="pages">';
    if ($num_pages > 1) {
		echo '<div class="pages1">';
			if ($page >= 1) {
	
	if ($page > 10)
	 {
           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page=1&per_page='.$per_page.$add_params.'">&lt;&lt;</a> &nbsp; ';

           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.(floor($page/10)*10-9).'&per_page='.$per_page.$add_params.'">-10</a> &nbsp; ';
	 }
	 
        echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else echo '?';
        echo 'page='.$page.'&per_page='.$per_page.$add_params.'">&lt;</a> &nbsp; ';
      }

//------------------------------------------------------------------------------
// вывод номеров страниц и номера активной страницы
     $from = ((ceil(($page+1)/10)*10)-10)+1;
     $to = $from + 9;
     if ($to > $num_pages) $to = $num_pages;

     for($i = $from; $i <= $to; $i++)
      {
        if ($i-1 == $page)
         {
           echo '<span class="npage_sel">'.$i.'</span> ';
         }
        else
         {
           echo '<a class="npage" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.$i.'&per_page='.$per_page.$add_params.'">'.$i."</a> ";
          }
       }

//------------------------------------------------------------------------------
     if ($page < ($num_pages-1))
      {
        echo ' &nbsp; <a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else echo '?';
        echo 'page='.($page+2).'&per_page='.$per_page.$add_params.'">&gt;</a> &nbsp; ';
	
	if ($page < floor($num_pages/10)*10)
	 {
           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.(floor($page/10)*10+11).'&per_page='.$per_page.$add_params.'">+10</a> &nbsp; ';

           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.$num_pages.'&per_page='.$per_page.$add_params.'">&gt;&gt;</a>';
	 }
	 
      }

  echo '</div>';}
//------------------------------------------------------------------------------
// вывод "записей на странице"
      echo '<div class="pages2">';
      if ($per_page == $pp1) echo '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp1.$add_params.'">'.$pp1.'</a> ';
        }

      if ($per_page == $pp2) echo '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp2.$add_params.'">'.$pp2.'</a> ';
       }
      if ($per_page == $pp3) echo '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp3.$add_params.'">'.$pp3.'</a> ';
       }

      echo ' '.$t.'</div></div>';
   }

/*
function navigation($page,$per_page,$total_rows,$params='',$t = 'записей на странице', $float = 'left')
   {
     $pp1 = 20;
     $pp2 = 50;
     $pp3 = 100;

     $page = intval($page);
     $per_page = intval($per_page);
     $total_rows = intval($total_rows);
     $num_pages = ceil($total_rows/$per_page);

//------------------------------------------------------------------------------
     $add_params = '';
     if (is_array($params))
      {
        foreach($params as $key => $value)
          $add_params .= '&'.$key.'='.rawurlencode($value);
      }

//------------------------------------------------------------------------------
     echo '<div style="clear:both;"></div><div class="pages">';
     if ($num_pages > 1) {
     echo '<div style="float: '.$float.'; font: 14pt arial,sans-serif; font-weight: bold;">';
     if ($page >= 1)
      {
	if ($page > 10)
	 {
           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page=1&per_page='.$per_page.$add_params.'">&lt;&lt;</a> &nbsp; ';

           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.(floor($page/10)*10-9).'&per_page='.$per_page.$add_params.'">-10</a> &nbsp; ';
	 }
	 
        echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else echo '?';
        echo 'page='.$page.'&per_page='.$per_page.$add_params.'">&lt;</a> &nbsp; ';
      }

//------------------------------------------------------------------------------
// вывод номеров страниц и номера активной страницы
     $from = ((ceil(($page+1)/10)*10)-10)+1;
     $to = $from + 9;
     if ($to > $num_pages) $to = $num_pages;

     for($i = $from; $i <= $to; $i++)
      {
        if ($i-1 == $page)
         {
           echo '<span class="npage_sel">'.$i.'</span> ';
         }
        else
         {
           echo '<a class="npage" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.$i.'&per_page='.$per_page.$add_params.'">'.$i."</a> ";
          }
       }

//------------------------------------------------------------------------------
     if ($page < ($num_pages-1))
      {
        echo ' &nbsp; <a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else echo '?';
        echo 'page='.($page+2).'&per_page='.$per_page.$add_params.'">&gt;</a> &nbsp; ';
	
	
        
	if ($page < floor($num_pages/10)*10)
	 {
           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.(floor($page/10)*10+11).'&per_page='.$per_page.$add_params.'">+10</a> &nbsp; ';

           echo '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else echo '?';
           echo 'page='.$num_pages.'&per_page='.$per_page.$add_params.'">&gt;&gt;</a>';
	 }
	 
      }

  echo '</div><div style="float: '.$float.'; width: 50px;">&nbsp;</div>';}
//------------------------------------------------------------------------------
// вывод "записей на странице"
      echo '<div style="float: '.$float.';" class="per_page">';
      if ($per_page == $pp1) echo '<span class="per_page_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="per_page" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp1.$add_params.'">'.$pp1.'</a> ';
        }

      if ($per_page == $pp2) echo '<span class="per_page_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="per_page" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp2.$add_params.'">'.$pp2.'</a> ';
       }
      if ($per_page == $pp3) echo '<span class="per_page_sel">'.$per_page.'</span> ';
      else
       {
         echo '<a class="per_page" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) echo '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else echo '?';
         echo 'per_page='.$pp3.$add_params.'">'.$pp3.'</a> ';
       }

      echo ' '.$t.'</div></div><div style="clear:both;"></div>';
   }
*/

function navigation_to_string($page,$per_page,$total_rows,$params='',$t = 'записей на странице')
   {
     $pp1 = 20;
     $pp2 = 50;
     $pp3 = 100;

     $page = intval($page);
     $per_page = intval($per_page);
     $total_rows = intval($total_rows);
     $num_pages = ceil($total_rows/$per_page);

     $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

     $text .= '<div class="pages">';
     if ($num_pages > 1) {
     $text .= '<div class="pages1">';
     if ($page >= 1)
      {
	if ($page > 10)
	 {
           $text .= '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else $text .= '?';
           $text .= 'page=1&per_page='.$per_page.$add_params.'">&lt;&lt;</a> &nbsp; ';

           $text .= '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else $text .= '?';
           $text .= 'page='.(floor($page/10)*10-9).'&per_page='.$per_page.$add_params.'">-10</a> &nbsp; ';
	 }
	 
        $text .= '<a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else $text .= '?';
        $text .= 'page='.$page.'&per_page='.$per_page.$add_params.'">&lt;</a> &nbsp; ';
      }

//------------------------------------------------------------------------------
// вывод номеров страниц и номера активной страницы
     $from = ((ceil(($page+1)/10)*10)-10)+1;
     $to = $from + 9;
     if ($to > $num_pages) $to = $num_pages;

     for($i = $from; $i <= $to; $i++)
      {
        if ($i-1 == $page)
         {
           $text .= '<span class="npage_sel">'.$i.'</span> ';
         }
        else
         {
           $text .= '<a class="npage" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else $text .= '?';
           $text .= 'page='.$i.'&per_page='.$per_page.$add_params.'">'.$i."</a> ";
          }
       }

//------------------------------------------------------------------------------
     if ($page < ($num_pages-1))
      {
        $text .= ' &nbsp; <a class="arr" href="'.$_SERVER['PHP_SELF'];
        if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
        else $text .= '?';
        $text .= 'page='.($page+2).'&per_page='.$per_page.$add_params.'">&gt;</a> &nbsp; ';
	
	
        
	if ($page < floor($num_pages/10)*10)
	 {
           $text .= '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else $text .= '?';
           $text .= 'page='.(floor($page/10)*10+11).'&per_page='.$per_page.$add_params.'">+10</a> &nbsp; ';

           $text .= '<a class="arr" href="'.$_SERVER['PHP_SELF'];
           if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
           else $text .= '?';
           $text .= 'page='.$num_pages.'&per_page='.$per_page.$add_params.'">&gt;&gt;</a>';
	 }
	 
      }

  $text .= '</div>';}
//------------------------------------------------------------------------------
// вывод "записей на странице"
      $text .= '<div class="pages2">';
      if ($per_page == $pp1) $text .= '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         $text .= '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else $text .= '?';
         $text .= 'per_page='.$pp1.$add_params.'">'.$pp1.'</a> ';
        }

      if ($per_page == $pp2) $text .= '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         $text .= '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else $text .= '?';
         $text .= 'per_page='.$pp2.$add_params.'">'.$pp2.'</a> ';
       }
      if ($per_page == $pp3) $text .= '<span class="npage_sel">'.$per_page.'</span> ';
      else
       {
         $text .= '<a class="npage" href="'.$_SERVER['PHP_SELF'];
         if (isset($_GET['sort_by']) && isset($_GET['order'])) $text .= '?sort_by='.$_GET['sort_by'].'&order='.$_GET['order'].'&';
         else $text .= '?';
         $text .= 'per_page='.$pp3.$add_params.'">'.$pp3.'</a> ';
       }

      $text .= ' '.$t.'</div></div>';
      return $text;
   }
?>