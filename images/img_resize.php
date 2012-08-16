<?
function settransparency($new_image, $image_source) {
    $transparencyIndex = imagecolortransparent($image_source);
    $transparencyColor = array('red' => 255, 'green' => 255, 'blue' => 255);
    if ($transparencyIndex >= 0) $transparencyColor = imagecolorsforindex($image_source, $transparencyIndex);   
    $transparencyIndex = imagecolorallocate($new_image, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
    imagefill($new_image, 0, 0, $transparencyIndex);
    imagecolortransparent($new_image, $transparencyIndex);    
}

if (    isset($_GET['image']) &&
        trim($_GET['image']) !== '' &&
        file_exists($_SERVER['DOCUMENT_ROOT'].rawurldecode($_GET['image']))) {

    $headers = (function_exists('apache_request_headers') ? apache_request_headers() : false);
    $file = $_SERVER['DOCUMENT_ROOT'].rawurldecode($_GET['image']);
    list($width_orig, $height_orig) = getimagesize($file);
    $stamp_file = $_SERVER['DOCUMENT_ROOT'].'/images/stamp.jpg'; //косяк
    $filter = false;
    $filter_arg1 = 0;
    $filter_arg2 = 0;
    $filter_arg3 = 0;
    $filter_arg4 = 0;
    
    //качество
    $quality = ((isset($_GET['quality']) && trim($_GET['quality']) !== '') ? (int)$_GET['quality'] : 100);
    
    //размер максимальной стороны
    $size = 0;
    if (isset($_GET['size'])) {
        $width = intval($_GET['size']);
        $height = intval($_GET['size']);
    }
    //фиксирование ширины или высоты
    $fixed = false; if ((isset($_GET['width']) && trim($_GET['width']) !== '') || (isset($_GET['height']) && trim($_GET['height']) !== '')) $fixed = true;
    //накладываемая картинка
    $stamp = false; if (isset($_GET['stamp']) && $_GET['stamp'] == 'true') $stamp = true; if(file_exists($stamp_file)) list($stamp_file_width, $stamp_file_height) = getimagesize($stamp_file);
    //кэширование
    $cache = true; if (isset($_GET['cache']) && $_GET['cache'] == 'no') $cache = false;
    
    //тип изображения на входе
    if (function_exists('mime_content_type')) {
        $extension = basename(mime_content_type($file));
    } else {
        $f = pathinfo(basename($_GET['image']));
        $extension = $f['extension'];
    }
    switch($extension) {
        case 'jpg'  :
        case 'jpeg' :   $input_type = 'jpeg'; break;
        case 'gif'  :   $input_type = 'gif'; break;
        case 'png'  :   $input_type = 'png'; break;
        case 'bmp'  :   $input_type = 'bmp'; break;
        default     :   $input_type = 'jpeg'; break;
    }
    
    //тип изображения на выходе
    $output_type = ((isset($_GET['output_type'])) ? $_GET['output_type'] : $input_type);
    
    //фильтр
    if (isset($_GET['filter']) && trim($_GET['filter']) !== '') {
        switch(trim($_GET['filter'])) {
            case 'IMG_FILTER_NEGATE'         : $filter = 0; break; //Reverses all colors of the image.
            case 'IMG_FILTER_GRAYSCALE'      : $filter = 1; break; //Converts the image into grayscale.
            case 'IMG_FILTER_BRIGHTNESS'     : $filter = 2; break; //Changes the brightness of the image. Use arg1 to set the level of brightness.
            case 'IMG_FILTER_CONTRAST'       : $filter = 3; break; //Changes the contrast of the image. Use arg1 to set the level of contrast.
            case 'IMG_FILTER_COLORIZE'       : $filter = 4; break; //Like IMG_FILTER_GRAYSCALE, except you can specify the color. Use arg1 , arg2 and arg3 in the form of red , blue , green and arg4 for the alpha channel. The range for each color is 0 to 255.
            case 'IMG_FILTER_EDGEDETECT'     : $filter = 5; break; //Uses edge detection to highlight the edges in the image.
            case 'IMG_FILTER_EMBOSS'         : $filter = 6; break; //Embosses the image.
            case 'IMG_FILTER_GAUSSIAN_BLUR'  : $filter = 7; break; //Blurs the image using the Gaussian method.
            case 'IMG_FILTER_SELECTIVE_BLUR' : $filter = 8; break; //Blurs the image.
            case 'IMG_FILTER_MEAN_REMOVAL'   : $filter = 9; break; //Uses mean removal to achieve a "sketchy" effect.
            case 'IMG_FILTER_SMOOTH'         : $filter = 10; break; //Makes the image smoother. Use arg1 to set the level of smoothness.
            case 'IMG_FILTER_PIXELATE'       : $filter = 11; break; //Applies pixelation effect to the image, use arg1 to set the block size and arg2 to set the pixelation effect mode.
            default: $filter = false; break;
        }
    }
    //if (isset($_GET['filter_arg1']) && trim($_GET['filter_arg1']) !== '') $filter_arg1 = $_GET['filter_arg1'];
    //if (isset($_GET['filter_arg2']) && trim($_GET['filter_arg2']) !== '') $filter_arg2 = $_GET['filter_arg2'];
    //if (isset($_GET['filter_arg3']) && trim($_GET['filter_arg3']) !== '') $filter_arg3 = $_GET['filter_arg3'];
    //if (isset($_GET['filter_arg4']) && trim($_GET['filter_arg4']) !== '') $filter_arg4 = $_GET['filter_arg4'];
    
    //sharpening
    $sharpening = (isset($_GET['sharpening']) ? true : false);
    $sharpening_level = ((isset($_GET['sharpening_level']) && (int)$_GET['sharpening_level'] > 0) ? (int)$_GET['sharpening_level'] : 6);
    $matrix = array(
                        array(-1, -1, -1),
                        array(-1, $sharpening_level, -1),
                        array(-1, -1, -1)
                    );
    //подробнее тут http://loriweb.pair.com/8udf-sharpen.html
    
    //формирование кэш-ключа
    $cache_key = '';
    $cache_key .= $file.'?';
    if (isset($_GET['size'])) $cache_key .= '&size='.$_GET['size'];
    if (isset($_GET['width'])) $cache_key .= '&width='.$_GET['width'];
    if (isset($_GET['height'])) $cache_key .= '&height='.$_GET['height'];
    if (isset($_GET['placement'])) $cache_key .= '&placement='.$_GET['placement'];
    if (isset($_GET['action'])) $cache_key .= '&action='.$_GET['action'];
    if (isset($_GET['quality'])) $cache_key .= '&quality='.$_GET['quality'];
    if (isset($_GET['stamp'])) $cache_key .= '&stamp='.$_GET['stamp'];
    if (isset($_GET['filter'])) $cache_key .= '&filter='.$_GET['filter'];
    if (isset($_GET['output_type'])) $cache_key .= '&output_type='.$_GET['output_type'];
    //if (isset($_GET['filter_arg1'])) $cache_key .= '&filter_arg1='.$_GET['filter_arg1'];
    //if (isset($_GET['filter_arg2'])) $cache_key .= '&filter_arg2='.$_GET['filter_arg2'];
    //if (isset($_GET['filter_arg3'])) $cache_key .= '&filter_arg3='.$_GET['filter_arg3'];
    //if (isset($_GET['filter_arg4'])) $cache_key .= '&filter_arg4='.$_GET['filter_arg4'];
    $cache_file = $_SERVER['DOCUMENT_ROOT'].'/cache_image/'.md5($cache_key);
    
    //определение размеров
    if (!$fixed) {
    if ($width_orig < $height_orig) $width = ($height / $height_orig) * $width_orig;
    else $height = ($width / $width_orig) * $height_orig;
    } else {
        if (    isset($_GET['width']) && trim($_GET['width']) !== '' &&
                isset($_GET['height']) && trim($_GET['height']) !== '') {
            $width = intval($_GET['width']);
            $height = intval($_GET['height']);
        
            if (isset($_GET['placement']) && $_GET['placement'] == 'part') {
                if (($width / $height) > ($width_orig / $height_orig)) $height = ($width / $width_orig) * $height_orig;
                else $width = ($height / $height_orig) * $width_orig;
            } else {
                if (($width / $height) > ($width_orig / $height_orig)) $width = ($height / $height_orig) * $width_orig;
                else $height = ($width / $width_orig) * $height_orig;
            }
        } elseif (isset($_GET['width']) && trim($_GET['width']) !== '') {
            $width = intval($_GET['width']);
            $height = ($width / $width_orig) * $height_orig;
        } elseif (isset($_GET['height']) && trim($_GET['height']) !== '') {
            $height = intval($_GET['height']);
            $width = ($height / $height_orig) * $width_orig;
        }
    }
    
    //если картинка меньшего размера, чем по умолчанию, оставляем ее размер
    if (($width > $width_orig) && ($height > $height_orig)) {$width = $width_orig; $height=$height_orig;}
    
    //вывод заголовков и ресайз
    if (    $headers &&
            isset($headers['If-Modified-Since']) &&
            $cache &&
            (gmdate("D, d M Y H:i:s",filemtime($file))." GMT" == $headers['If-Modified-Since']))
            header("HTTP/1.1 304 Not Modified");
    else {
        header("Last-Modified: " . gmdate("D, d M Y H:i:s",filemtime($file)) . " GMT");
        header("Content-type: image/$output_type");
        header("Content-Disposition: inline; filename=".basename($file));
    
        if ($cache) { 
            if (!file_exists($cache_file)) {
                if (isset($_GET['placement']) && $_GET['placement'] == 'part') $image_p = imagecreatetruecolor($_GET['width'], $_GET['height']);
                else $image_p = imagecreatetruecolor($width, $height);
                switch($input_type) {
                    case 'jpeg': $image = imagecreatefromjpeg($file); break;
                    case 'gif': $image = imagecreatefromgif($file); break;
                    case 'png': $image = imagecreatefrompng($file); break;
                    case 'bmp': $image = imagecreatefromwbmp($file); break;
                    default: $image = imagecreatefromjpeg($file); break;
                }
            
                settransparency($image_p, $image); 
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                if ((   $size > 200 ||
                        $height > 200 ||
                        $width > 200) &&
                        file_exists($stamp_file) &&
                        $stamp)
                    imagecopy($image_p, $stamp_file, 4, $height-$stamp_file_height-4, 0, 0, $stamp_file_width, $stamp_file_height);
                if ($filter) imagefilter($image_p, $filter);
                if ($sharpening) imageconvolution($image_p, $matrix, 8, 0);
                switch($output_type) {
                    case 'jpeg': imagejpeg($image_p, $cache_file, $quality); break;
                    case 'gif': imagegif($image_p, $cache_file); break;
                    case 'png': imagepng($image_p, $cache_file); break;
                    case 'bmp': imagewbmp($image_p, $cache_file); break;
                    default: imagejpeg($image_p, $cache_file, $quality); break;
                }
            }
            
            readfile($cache_file);
        } else {
            if (isset($_GET['placement']) && $_GET['placement'] == 'part') $image_p = imagecreatetruecolor($_GET['width'], $_GET['height']);
            else $image_p = imagecreatetruecolor($width, $height);
            switch($input_type) {
                case 'jpeg': $image = imagecreatefromjpeg($file); break;
                case 'gif': $image = imagecreatefromgif($file); break;
                case 'png': $image = imagecreatefrompng($file); break;
                case 'bmp': $image = imagecreatefromwbmp($file); break;
                default: $image = imagecreatefromjpeg($file); break;
            }
        
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
            if ((   $size > 200 ||
                    $height > 200 ||
                    $width > 200) &&
                    file_exists($stamp_file) &&
                    $stamp)
                imagecopy($image_p, $stamp_file, 4, $height-$stamp_file_height-4, 0, 0, $stamp_file_width, $stamp_file_height);
            if ($filter) imagefilter($image_p, $filter);
            if ($sharpening) imageconvolution($image_p, $matrix, 8, 0);
            switch($output_type) {
                case 'jpeg': imagejpeg($image_p, false, $quality); break;
                case 'gif': imagegif($image_p, false); break;
                case 'png': imagepng($image_p, false); break;
                case 'bmp': imagewbmp($image_p, false); break;
                default: imagejpeg($image_p, false, $quality); break;
            }
        }
    }
}
?>