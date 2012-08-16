<?
function resize($file, $type = 'jpeg')
{

//размеры конечных картинок (не более) по умолчанию
 $width = MAX_IMAGE_WIDTH; $height = MAX_IMAGE_HEIGHT;


//определяем новый размер картинки
  list($width_orig, $height_orig) = getimagesize($file);

  if ($width && ($width_orig < $height_orig)) {
     $width = ($height / $height_orig) * $width_orig;
  } else {
     $height = ($width / $width_orig) * $height_orig;
  }


     switch ($type)
      {
        case 'jpeg':
        case 'pjpeg': $image = imagecreatefromjpeg($file); break;
        case 'png':
        case 'x-png': $image = imagecreatefrompng($file); break;
        case 'gif':   $image = imagecreatefromgif($file); break;
        case 'bmp':
        case 'wbmp':  $image = imagecreatefromwbmp($file); break;
      }

//если картинка меньшего размера, чем по умолчанию, оставляем ее размер
  if (($width >= $width_orig) && ($height >= $height_orig))
   {
     //сохраняем картинку в файл JPEG
     imagejpeg($image, $file, 100);
   }
  else
   {
     $image_p = imagecreatetruecolor($width, $height);
     imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

     //сохраняем картинку в файл JPEG
     imagejpeg($image_p, $file, 100);
   }
}
?>
