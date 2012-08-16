<?

class Search
 {
   public $site;
   public $links = array();
   public $links_unique = array();
   public $query_pages = array();
   public $exclusion_urls = array();

   function __construct()
    {
      $this->site = 'http://'.$_SERVER['HTTP_HOST'];
      //$this->find_links($start);
      //ksort($this->links);
    }

   public function get_links()
    {
      return $this->links;
    }

   public function is_site_url($url)
    {
      @$components = parse_url($url);
      if (is_array($components))
       {
         if (array_key_exists('host',$components))
          {
            if ($components['host'] == $_SERVER['HTTP_HOST']) return true;
            else return false;
          }
       }
      if (substr($url,0,strlen('mailto:')) == 'mailto:') return false;
      return true;
    }
    
   public function is_exclusion_url($url)
    {
      foreach ($this->exclusion_urls as $e_url) if (strpos($url, $e_url)) return true;
      return false;       
    }

   public function find_links($path)
    {
      if (trim($path) !== '')
      {

      @$page = file_get_contents($this->site.$path);
      $page_links = array();
      if ($page)
       {
         if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\'>]*)[\"\']?[^>]*>(.*?)<\/a>/i', $page, $matches, PREG_SET_ORDER))
          {
             
            foreach ($matches as $match)
             {
               $match[1] = strtolower(trim($match[1]));
               //echo $match[1].'<br />';
               
               if ($match[1] !== '' &&
                   !strpos($match[1],':') &&
                   !strpos($match[1],';') &&
                   !strpos($match[1],'(') &&
                   !strpos($match[1],')') &&
                   !strpos($match[1],'#'))
               {
               if ($this->is_site_url($match[1]))
                {
                  // преобразование ссылки
                  if (substr($match[1],0,1) == '?')
                   {
                     $pos = strpos($path,'?');
                     if ($pos) $path = substr($path,0,$pos);
                     $match[1] = $path.$match[1];
                   }

                  if (strpos($match[1],$_SERVER['HTTP_HOST']))
                   {
                    $replace1 = $this->site;
                    $replace2 = $_SERVER['HTTP_HOST'];
                    $match[1] = str_replace($replace1, '', $match[1]);
                    $match[1] = str_replace($replace2, '', $match[1]);
                   }
                  // сохраняем ссылку в массиве
                  if (!array_key_exists($match[1], $this->links))
                   {
                     $this->links[$match[1]] = 1;
                     $page_links[] = $match[1];
                   }
                  else
                    $this->links[$match[1]] += 1;
                }
               }
             }
          }

         //ссылку на текущую страницу дабавляем в список проанализированных
         if (!array_key_exists($path, $this->links_unique))
            $this->links_unique[$path] = 1;
            
// заглушка --------------------------------------------------------------------         
         if (count($this->links_unique) > 100)
          {
            echo '<p>load time: '.substr(microtime_float()-PAGE_LOAD_TIME,0,6).'</p>';
            ksort($this->links_unique);
            $i = 1;
            foreach ($this->links as $link => $q)
             {
               echo '<small>'.$i.'. '.$link.'</small><br />';
               $i++;
             }
            break; return true;
          }
// end заглушка ----------------------------------------------------------------         

         // рекурсия
         foreach ($page_links as $link)
          //ищем ссылки только на новых, непроанализированных страницах
          if (!array_key_exists($link, $this->links_unique)) $this->find_links($link);
       }
     }
    }

   public function find_pages($word)
    {
      foreach($this->links as $link => $value)
       {
         @$page = file_get_contents($this->site.$link);
         if ($page)
          {
            if (strpos(strtolower(strip_tags($page)), strtolower($word)) == true)
             {
               $title = 'no title';
               if (preg_match_all('/(?<=\<title>)[\s]*.*[\s]*(?=\<\/title>)/i', $page, $matches))
               $title = $matches[0][0];

               $this->query_pages[$link] = $title;
             }
          }
       }
      return $this->query_pages;
    }

 }
?>
