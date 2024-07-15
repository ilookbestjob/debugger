<?
header('Access-Control-Allow-Origin: *');



if(isset($_GET["php"])) {


$path=explode("/",$_GET["php"]);

unset($path[0]);
unset($path[1]);
unset($path[2]);

$path=implode("/",$path);

$script=$_SERVER["DOCUMENT_ROOT"]."/".explode('?',$path)[0];

$scriptdata=file_get_contents($script);
echo strpos($scriptdata, "Debug")?"true":"false";

}
else{
echo "false";
}