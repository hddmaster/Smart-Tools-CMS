<?

class Graph
 {
 
   var $width = '300';
   var $height = '200';
   var $bg = 'FFFFFF';
 
   var $x_values = array();
   var $y_value = array();
   var $x_step = 1;
   var $y_step = 10;

   var $grid_color = '000000';

   var $line_width = 1;
   var $line_color = 'CCCCCC';
   
   
   function set_line_color($color)
    {
      $this->line_color = $color;
    }
    
   function set_line_width($width)
    {
      $this->line_width = $width;
    }
    
   function set_x_values ($values)
    {
      $this->x_values = $values;
    }

   function set_y_values ($values)
    {
      $this->y_values = $values;
    }

   function set_x_step($step)
    {
      $this->x_step = $step;
    }

   function set_y_step($step)
    {
      $this->y_step = $step;
    }

   function set_grid_color($color)
    {
      $this->grid_color = $color;
    }
    
   function plot()
    {
      $image = imagecreatetruecolor($this->width, $this->height);
      $bg = imagecolorallocate($image, hexdec(substr($this->bg,0,2)), hexdec(substr($this->bg,2,2)), hexdec(substr($this->bg,4,2)));
      imagefilledrectangle($image, 0, 0, imageSX($image), imageSY($image), $bg);

      //построение сетки
      //x
      imageline($image, 10, $this->height-10, $this->width-10, $this->height-10);
      //y
      imageline($image, 10, 10, 10, $this->height-10);
      
      //нанесение меток

      imagestring($image, 1, 3, 3, $stat_today->total_uniques, $darkgrey);
      imagestring($image, 1, 3, 12, $stat_today->total_hits, $darkgrey);




      header("Content-type: image/png");
      imagepng($image);
    }

 }
?>
