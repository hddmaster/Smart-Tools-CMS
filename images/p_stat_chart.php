<?
$imageSX = 610;
$imageSX_start = 30;
$imageSX_limit = 580;

$imageSY = 320;
$imageSY_start = 20;
$imageSY_limit = 280;
$step_x = 20;
$step_y = 20;

if (isset($_GET['uniques']) && isset($_GET['hits']))
 {
      $p = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
      $_p = array();
      $_sp = array();
      
      $_p = explode(',',$_GET['uniques']);
      $_sp = explode(',',$_GET['hits']);
      
      $_p_sort = $_p; 
      $_sp_sort = $_sp; 
      sort($_p_sort); 
      sort($_sp_sort);
      $max = 100;
      $min = 0;
      if ($_p_sort[0] < $_sp_sort[0]) $min = $_p_sort[0]; else $min = $_sp_sort[0];
      if ($_p_sort[count($_p_sort)-1] > $_sp_sort[count($_sp_sort)-1]) $max = $_p_sort[count($_p_sort)-1]; else $max = $_sp_sort[count($_sp_sort)-1];

      
      $step_x = round(($imageSX_limit-$imageSX_start)/count($p)); // -1 ?
      
      $max_value = 0;
      $min_value = 0;
      if ($min < 0) $min_value = floor($min/10)*10; else $min_value = ceil($min/10)*10; //!!! 
      if ($max < 0) $max_value = floor($max/10)*10; else $max_value = ceil($max/10)*10; //!!!
      $step_y = round(($imageSY_limit-$imageSY_start)/(($max_value-$min_value)/10));
      
      header("Content-type: image/png");
      $image = imagecreatetruecolor($imageSX, $imageSY);
      imageantialias($image, true);
      $white = imagecolorallocate($image, 255, 255, 255);
      $black = imagecolorallocate($image,0,0,0);
      $lightgrey = imagecolorallocate($image,230,230,230);
      $grey = imagecolorallocate($image,190,190,190);
      $darkgrey = imagecolorallocate($image,110,110,110);
      $red = imagecolorallocate($image,204,51,51);
      $blue = imagecolorallocate($image,53,53,103);
      $green = imagecolorallocate($image,0,150,0);
      
      $arial = $_SERVER['DOCUMENT_ROOT'].'/images/arial.ttf';
      
      imagefilledrectangle($image, 0, 0, $imageSX, $imageSY, $darkgrey);
      imagefilledrectangle($image, 1, 1, $imageSX-2, $imageSY-2, $white);

      //сетка
      $last_x = 0;
      for($i=$imageSX_start; $i <= $imageSX_limit; $i=$i+$step_x) $last_x = $i;
      $last_x -= $step_x;

      for($i=$imageSX_start; $i <= $last_x; $i=$i+$step_x) imageline($image, $i, $imageSY_start, $i, $imageSY_limit, $grey);
      for($i=$imageSY_start; $i <= $imageSY_limit; $i=$i+$step_y) imageline($image, $imageSX_start, $i, $last_x, $i, $grey);

      //графики
      //синий график
      $last_g_y_b = 0;
      $last_value_b = 0;
      $x = $imageSX_start;
      $k = 1;
      $d_y = -1;
      $delta_y_date = 6;
      for($i = 1; $i < count($p); $i++)
       {
         $y1 = $imageSY_start + $step_y*($max_value/10) - round(($_p[$i-1]*$step_y)/10);
         $y2 = $imageSY_start + $step_y*($max_value/10) - round(($_p[$i]*$step_y)/10);
         $y_date = $imageSY_limit + 15;
         imageline($image, $x, $y1, $x+$step_x, $y2, $blue);
         imageline($image, $x, $y1-1, $x+$step_x, $y2-1, $blue);
         
         //вывод дат
         if ($k == 1 || $i == (count($p)-1))
          {
            if ($i == (count($p)-1)) imagettftext($image, 8, 0, $last_x+8, $imageSY_limit+26, $black, $arial, date('My',$p[$i]));
            else
             {
               imageline($image, $x, $imageSY_limit, $x, $y_date - 10, $darkgrey);
               imagettftext($image, 8, 0, $x-15, $y_date, $black, $arial, date('My',$p[$i]));
             }
          }
         imagefilledellipse($image, $x, $y1-1, 2, 2, $white);
         $last_value_b = $_p[$i];
         $last_g_y_b = $y2;
         $k++;
         if ($k == 5) {$k = 1; $d_y *= -1;}
         $x=$x+$step_x;
       }

      //красный график
      $last_g_y_r = 0;
      $last_value_r = 0;
      $x = $imageSX_start;
      for($i = 1; $i < count($p); $i++)
       {
         $y1 = $imageSY_start + $step_y*($max_value/10) - round(($_sp[$i-1]*$step_y)/10);
         $y2 = $imageSY_start + $step_y*($max_value/10) - round(($_sp[$i]*$step_y)/10);
         imageline($image, $x, $y1, $x+$step_x, $y2, $red);
         imageline($image, $x, $y1-1, $x+$step_x, $y2-1, $red);
         imagefilledellipse($image, $x, $y1-1, 2, 2, $white);
         $last_value_r = $_sp[$i];
         $last_g_y_r = $y2;
         $x=$x+$step_x;
       }
      
      //вывод по названий по y
      $y_value = $max_value;
      for ($i=$imageSY_start; $i < $imageSY_limit; $i += $step_y, $y_value -=10)
       {
         if ($y_value >= $min_value && $y_value <= $max_value)
         $d = 0;
         if (strlen($y_value) < 2) $d = 6;
         if (strlen($y_value) > 2) $d = -3;
         imagettftext($image, 8, 0, $imageSX_start-25+$d, $i+3, $black, $arial, $y_value.'%');
       }
       
      //вывод последней даты
      imagefilledpolygon($image, array($last_x, $imageSY_limit,
                                       $last_x+3, $imageSY_limit+3,
                                       $last_x-3, $imageSY_limit+3), 3, $darkgrey);
      imageline($image, $last_x, $imageSY_limit, $last_x, $imageSY_limit+22, $darkgrey);
      imageline($image, $last_x, $imageSY_limit+22, $last_x+6, $imageSY_limit+22, $darkgrey);

      //вывод последнего значения синего графика
      $d = 0;
      if (strlen($last_value_b) > 4) $d = 8;
      if (strlen($last_value_b) > 5 && !preg_match('/\-/',$last_value_b)) $d = 16;
      imagefilledrectangle($image, $last_x+5, $last_g_y_b-8, $last_x+38+$d, $last_g_y_b+8, $darkgrey);
      imagefilledrectangle($image, $last_x+5, $last_g_y_b-8, $last_x+37+$d, $last_g_y_b+7, $white);
      imagefilledellipse($image, $last_x, $last_g_y_b, 2, 2, $white);
      imagettftext($image, 8, 0, $last_x+7, $last_g_y_b+4, $blue, $arial, $last_value_b.'%');
       
      //вывод последнего значения красного графика
      $d = 0;
      if (strlen($last_value_r) > 4) $d = 8;
      if (strlen($last_value_r) > 5 && !preg_match('/\-/',$last_value_r)) $d = 16;
      imagefilledrectangle($image, $last_x+5, $last_g_y_r-8, $last_x+38+$d, $last_g_y_r+8, $darkgrey);
      imagefilledrectangle($image, $last_x+5, $last_g_y_r-8, $last_x+37+$d, $last_g_y_r+7, $white);
      imagefilledellipse($image, $last_x, $last_g_y_r, 2, 2, $white);
      imagettftext($image, 8, 0, $last_x+7, $last_g_y_r+4, $red, $arial, $last_value_r.'%');
      
      //легенда
      imagefilledrectangle($image, $imageSX_start+10, $imageSY_start+10, $imageSX_start+130, $imageSY_start+60, $white);
      imagerectangle($image, $imageSX_start+10, $imageSY_start+10, $imageSX_start+130, $imageSY_start+60, $darkgrey);
      imagefilledrectangle($image, $imageSX_start+20, $imageSY_start+20, $imageSX_start+30, $imageSY_start+30, $blue);
      imagefilledrectangle($image, $imageSX_start+20, $imageSY_start+40, $imageSX_start+30, $imageSY_start+50, $red);
      imagettftext($image, 8, 0,  $imageSX_start+35, $imageSY_start+30, $black, $arial, 'PORTFOLIO');
      imagettftext($image, 8, 0, $imageSX_start+35, $imageSY_start+50, $black, $arial, $findex);
      imagepng($image);
 }
?>