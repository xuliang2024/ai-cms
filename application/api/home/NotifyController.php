<?php
require_once "sby".DIRECTORY_SEPARATOR."HttpUtlis.php";

/**
 * 签约回调示例
 */

$data = '{"funCode":"6011","merId":"1676619392976687","resCode":"0000","resData":"JzlgqZvY7c78YGTpcKmqh+i5bLRZhsxhjcEnFU29AikjJgjbm6pLdij356tUZBSw8WoUNZp8uFIDN4lf25ViR1AXuTDEQxUVdB5x3a/e4UcmMt2w1yBbocJpuR8y85S5/RViP2R0noV49jFLPORSs//ywIcCS1+3EgOBQBO93UC22Vh/7kKxyOKolaBGuntJ8lx4USMsJ/HPrmAwYl94Nw==","resMsg":"成功","sign":"esVA63pycI+tRIdVkAeyROqgAUc6U+lWz8ZNgr/TTdLs21j/PDDr67+4xFlPCl6DaS2H05LR2v2mnHhS7Ma3VcmS/bB90TwcWDAIa600GMothYp7WkNK5uK+u/GsdALajlVvA8Bj4kx4qSSvJ4g6aX/A4oWlKIk/DJcMDrNOWjE=","version":"V1.0"}';

$httpUtlis = new HttpUtlis();

$res = $httpUtlis->handleResponse($data);
echo "\n";
echo "\n";
echo  $res;