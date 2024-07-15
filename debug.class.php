<?php
//use Illuminate\Contracts\Session\Session;

//use function GuzzleHttp\json_encode;

require "debugMessage.class.php";
require_once "/var/www/html/classes/Servers/servers_class.php";

class Debug
{

    public $logs = [];
    private $debugflag;
    private $debuginfo;
    private $baselog;
    private $servers;
    private $connection;

    private $webconnection;

    private $service;
    private $path;
    private $server;
    private $constid;
    private $session;
    private $lasttaskTime;

    private $currenttaskTime;
    private $nodestructor;
    private $version = 1.5;
    private $logtypes;
    private $baselogcount = 0;
    private $maxlogtype = 0;
    private $script;
    private $localindex = 0;
    private $uid = "";

    private $setup;





    function __construct($service, $script = "",  $constid = "", $debuginfo = "", $debugflag = "deb", $baselog = false, $logtypes = [3], $localindex = 0)
    {

        ob_start();

        $this->localindex = $localindex;
        $this->script = new stdClass();
        $this->script = $script;

      //  $this->script = $obj->property1;
        $this->debugflag = $debugflag;
        $this->debuginfo = $debuginfo;
        $this->baselog = !$baselog ? (isset($_GET["baselog"]) ? true : false) : $baselog;

        $this->servers = new Servers();

        $this->connection = $this->servers->servers["bigdata"]->connection;
        $this->webconnection = $this->servers->servers["web"]->connection;

        $this->service = $service;
        $this->serviceid = $this->checkService();

        $this->server = $this->server = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : ($this->findServerIP() ? $this->findServerIP() : $_SERVER['SERVER_ADDR']);
        $this->path =  explode("?", ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $this->server . "/" . $_SERVER['REQUEST_URI'])[0];


        $this->uid = $this->server . explode("?", $_SERVER['REQUEST_URI'])[0];

        $this->constid = $constid != "" ? $constid : $this->findConstid2();
        $this->session = explode(" ", microtime(false))[1];
        $this->session = implode(".", hrtime(false));
        $this->session = microtime(true);
        $this->lasttaskTime = $this->session;
        $this->currenttaskTime = $this->session;


        $this->logtypes = $logtypes;
        $this->baselogcount = 0;

        $this->setStart();

        $this->nodestructor = false;
        file_put_contents($this->script . "_debugsetup.php", "test");



        //////////////////////////Действия по GET-запросу
        if (isset($_GET["debugaction"])) {

            $this->nodestructor = true;
            switch ($_GET["debugaction"]) {
                case "getlogs":

                    $this->getLogs(isset($_GET["debugservice"]) ? $_GET["debugservice"] : "", isset($_GET["debugsession"]) ? $_GET["debugsession"] : "",);
                    exit;
                    break;
                case "getsessions":

                    $this->getSessions(isset($_GET["debugservice"]) ? $_GET["debugservice"] : "");
                    exit;
                    break;



                case "getconst":

                    echo json_encode($this->getConstData());
                    exit;
                    break;


                case "saveconst":

                    echo json_encode($this->saveConstData($_GET['const'], $_GET['id']));
                    exit;
                    break;


                case "setsessions":

                    $this->getConstData(isset($_GET["debugids"]) ? $_GET["debugids"] : "", isset($_GET["debuguids"]) ? $_GET["debuguids"] : "");
                    exit;
                    break;
            }
        }
        /////////////////////////////////////////////////


    }



    function findServerIP()
    {

        include "/var/www/html/conf.php";

        if (isset($currentserver)) return $currentserver;

        return isset($this->servers->servers[gethostname()]) ? $this->servers->servers[gethostname()] : false;
    }



    function getLogs($service = "", $session = "")
    {
        if (!$this->connection) {

            echo "[]";
            return false;
        }

        ob_clean();
        if ($service == "")  $service = $this->serviceid;

        $where = $session != "" ? " and session=$session" : "";
        $where .= $service != "" ? " and service_id=$service" : "";
        $result = [];

        $sql = "select * from globallogs.logs as l, globallogs.messages as m, globallogs.services as s where l.message_id=m.id and l.service_id=s.id " . $where . " order by session";
        $sql_result = mysqli_query($this->connection, $sql);

        if ($sql_result) {
            while ($data = mysqli_fetch_assoc($sql_result)) {


                $result[] = $data;
            }
        }
        echo \json_encode($result);
    }

    function getSessions($service = "")
    {
        ob_clean();

        if (!$this->connection) {

            echo "[]";
            return false;
        }


        if ($service == "")  $service = $this->serviceid;
        $result = [];
        $where = $service != "" ? "l.service_id=$service" : "";

        $sql = "select min(date) date, count(*) count, session from globallogs.logs as l where  $where group by session order by date";


        $sql_result = mysqli_query($this->connection, $sql);

        if ($sql_result) {
            while ($data = mysqli_fetch_assoc($sql_result)) {


                $result[] = $data;
            }
        }
        echo \json_encode($result);
    }

    function findConstid($setifabsent = false)
    {

        mysqli_query($this->webconnection, "set names utf8");
        $sql = "SELECT count(*) as qt, const.row_id  FROM nordcom.const  where const.phpscript='" . $this->path . "'and const.phpserver='" . $this->server . "'";



        $sql_result = mysqli_query($this->webconnection, $sql);

        if ($sql_result) {
            $sql_row = mysqli_fetch_array($sql_result);
            if ($sql_row["qt"] != 0) return $sql_row["row_id"];

            if ($setifabsent) {
                $sql = "INSERT INTO nordcom.const set name='" . $this->service . "', phpscript='" . $this->path . "' ,phpserver='" . $this->server . "'";
                $this->addlog($sql);

                $sql_result = mysqli_query($this->webconnection, $sql);
                return mysqli_insert_id($this->webconnection);
            }
        }
        return 0;
    }



    function setUID($id)
    {
        $this->setEnd();
        $this->localindex = $id;
        $this->constid = $this->findConstid2();
        $this->addlog("Смена идентификатора на " . $this->uid . "-" . $this->localindex . " индекс в таблице const: " . $this->constid);

        $this->setStart();
    }

    function findConstid2($setifabsent = false)
    {

        mysqli_query($this->webconnection, "set names utf8");
        $sql = "SELECT count(*) as qt, const.row_id  FROM nordcom.const where const.uid='" . $this->uid . "-" . $this->localindex . "'";



        $sql_result = mysqli_query($this->webconnection, $sql);

        if ($sql_result) {
            $sql_row = mysqli_fetch_array($sql_result);
            if ($sql_row["qt"] != 0) return $sql_row["row_id"];

            if ($setifabsent) {
                $sql = "INSERT INTO nordcom.const set name='" . $this->service . "', const.uid='" . $this->uid . "-" . $this->localindex . "'";
                $this->addlog($sql);

                $sql_result = mysqli_query($this->webconnection, $sql);
                return mysqli_insert_id($this->webconnection);
            }
        }
        return 0;
    }


    function __destruct()
    {
        if ($this->nodestructor == true) exit;
        $this->displaylog();
        $this->setEnd();
    }

    function prepareStack($stack)
    {
        $result = "";
        $stack_arr = explode("#", $stack);

        foreach ($stack_arr as $stack_item) {
            $stack_item_parts = explode(" ", $stack_item);


            $file = explode("/", $stack_item_parts[1])[count(explode("/", $stack_item_parts[1])) - 1];
            $function = $stack_item_parts[2];
            $parts = explode("(", $file);
            $file = $parts[0];
            $pos = str_replace([")", ":"], "", $parts[1]);
            if (($file != "{main}" && $pos != "")) {
                $result .= '<div class="trace"><div class="trace__position"><div class="name">строка</div><div class="data">' . $pos . '</div></div><div class="trace__file"><div class="name">файл</div><div class="data">' . $file . '</div></div><div class="trace__function"><div class="name">функция</div><div class="data">' . $function . '</div></div></div>';
            }
        }
        return $result;
    }

    function addlog($info, $critical = false, $logtype = 0, $loglevel = 0, $script = "", $bind = "")
    {
        $this->maxlogtype = $logtype > $this->maxlogtype ? $logtype : $this->maxlogtype;

        if (isset($_GET[$this->debugflag])) {

            $this->currenttaskTime = microtime(true);
            //  $this->logs[] = new DebugMessage(debug_backtrace()[0]["line"], $info, $critical, "Функция:  <span class=\"highlight\">" . debug_backtrace()[1]["function"] . "()</span><br>Файл: " . debug_backtrace()[0]["file"] . "  <br>", ["startTime" => count($this->logs) > 0 ? $this->session : 0, "lastTime" => (count($this->logs) > 0 ? $this->lasttaskTime : 0), "currentTime" => (count($this->logs) > 0 ? $this->currenttaskTime : 0)]);

            $e = new Exception();

            $this->logs[] = new DebugMessage(debug_backtrace()[0]["line"], $info, $critical, " Трассировка: " . $this->prepareStack($e->getTraceAsString()), ["startTime" => count($this->logs) > 0 ? $this->session : 0, "lastTime" => (count($this->logs) > 0 ? $this->lasttaskTime : 0), "currentTime" => (count($this->logs) > 0 ? $this->currenttaskTime : 0)]);



            $this->lasttaskTime = $this->currenttaskTime;
        }

        file_put_contents($this->script . "_debuglog.txt", json_encode($this->logs));

        /*
        if ($this->baselog) {
            if (in_array($logtype, $this->logtypes)) {
                $this->baselogcount++;
                $this->addBase($logtype, $loglevel, $info, $script, $bind);
            }
        }*/
    }


    function addBase($logtype, $loglevel, $message,  $bind = "")
    {

        if (!$this->connection) {
            return false;
        }


        $sql = "INSERT INTO `globallogs`.`logs` set date='" . date("Y-m-d H:i:s") . "', service_id=" . $this->serviceid . ", logtype_id='" . $logtype . "', loglevel_id=" . $loglevel . ",  message_id=" . $this->checkmessage($message) . ",  script='" . (((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . gethostbyname(trim(exec("hostname"))) . "/" . $_SERVER['REQUEST_URI']) . "', bind_id='" . $bind . "' , session='" . $this->session . "'";

        /*
$sql = "INSERT INTO `globallogs`.`logs` set date='" . date("Y-m-d H:i:s") . "', service_id=" . $this->serviceid . ", logtype_id='" . $logtype . "', loglevel_id=" . $loglevel . ",  message_id=1,  script='" . (((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . gethostbyname(trim(exec("hostname"))) . "/" . $_SERVER['REQUEST_URI']) . "', bind_id='" . $bind . "' , session='" . $this->session . "'";

$this->checkmessage($message) ;*/
        mysqli_query($this->connection, $sql);
    }

    function checkService()
    {
        if (!$this->connection) {
            return 0;
        }


        $sql = "SELECT count(*) as qt, id FROM `globallogs`.`services` where name='$this->service'";

        $sql_result = mysqli_query($this->connection, $sql);

        if ($sql_result) {
            $sql_row = mysqli_fetch_array($sql_result);
            if ($sql_row["qt"] != 0) return $sql_row["id"];

            $sql = "INSERT INTO `globallogs`.`services` set name='$this->service', path='$this->path' ,server='$this->server',const_id='$this->constid'";
            $sql_result = mysqli_query($this->connection, $sql);
            return mysqli_insert_id($this->connection);
        }
        return 0;
    }


    function checkmessage($message)
    {
        if (!$this->connection) {

            return 0;
        }

        $sql = "SELECT count(*) as qt, id FROM `globallogs`.`messages` where message='$message'";

        $sql_result = mysqli_query($this->connection, $sql);

        if ($sql_result) {
            $sql_row = mysqli_fetch_array($sql_result);
            if ($sql_row["qt"] != 0) return $sql_row["id"];

            $sql = "INSERT INTO `globallogs`.`messages` set message='$message'";
            $sql_result = mysqli_query($this->connection, $sql);
            return mysqli_insert_id($this->connection);
        }
        return 0;
    }


    function setStart()
    {
        if (!isset($_GET["starttype"])) return false;
        if ($_GET["starttype"] == "cron") {
            if ($this->constid != "") {

                $sql = "UPDATE nordcom.const  SET startDate='" . date("Y-m-d H:i:s") . "', phpscript='" . $this->path . "' ,phpserver='" . $this->server . "' WHERE row_id = $this->constid ";
                $sql_result = mysqli_query($this->webconnection, $sql);
                $this->addlog("Начало сессии " . $this->const . ". Данные " . ($sql_result ? "занесены" : "не занесены") . " в const");
            }
        } else {
            $this->addlog("Начало сессии " . $this->const . ". Данные не занесены в const. Отсутвует флаг starttype=cron");
        }
    }


    function setEnd()
    {
        if (!isset($_GET["starttype"])) return false;
        if ($_GET["starttype"] == "cron") {
            if ($this->constid != "") {

                $sql = "UPDATE nordcom.const  SET data='" . date("Y-m-d H:i:s") . "', phpscript='" . $this->path . "' ,phpserver='" . $this->server . "' WHERE row_id = $this->constid ";
                $sql_result = mysqli_query($this->webconnection, $sql);
                $this->addlog("Конец сессии " . $this->const . ". Данные " . ($sql_result ? "занесены" : "не занесены") . " в const");
            }
        } else {
            $this->addlog("Конец сессии " . $this->const . ". Данные не занесены в const. Отсутвует флаг starttype=cron");
        }
    }


    function makecontainer($name, $data, $expanded = false)
    {

        return '<div class="infocontainer">   <div class="header">' . $name . '<div class="arrow">&#9660;</div></div><div class="data" ' . ($expanded ? "expanded" : "style=\"display:none\"") . '>' . $data . '</div> </div>';
    }


    function displaylog()
    {

        if (isset($_GET["isdebug"])) {
            ob_clean();
            echo "true";
            return true;
        }


        if (isset($_GET[$this->debugflag])) {
            ob_clean();

            echo '<html>';

            //////////////////стили вывода начало
            echo '<style type="text/css">';

            echo file_get_contents(__DIR__ . '/css/style.css');

            echo '</style>';


            //////////////////стили вывода конец        

            echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>';

            echo '<script>';
            echo 'let service=' . $this->serviceid . "\nlet dev=" . (isset($_GET["dev"]) ? "true" : "false") . "\nuid='" . $this->uid . "'";


            echo (file_get_contents(__DIR__ . '/js/debug.js')) . '</script>';

            echo '<body>';


            echo '<div class="tabs"> <div class="tab selected" id="debuginfo">Отладочная информация</div><div class="tab" id="jurnal">Журнал</div><div class="tab right" id="setup">Настройки</div></div>';
            echo '<div class="journalinfocontainer"><div class="sessions"></div><div class="sessionsdata"></div></div>';
            echo '<div class="debuginfocontainer">';


            echo '<div class="debuginfo"> <div class="header ">Отладочная информация ' . $this->script . ' ver ' . $this->version . " (" . (date("F d Y H:i:s.", filemtime(__FILE__))) .
                ')</div>';





            $data = "";
            foreach ($_GET as $key => $value) {
                $data .= "<div class=\"fileinfo\"><div>$key</div> <div class=\"blured\">" . $value . "</div></div>";
            }


            echo  $this->makecontainer("Переменные GET", $data);


            $data = "";
            foreach ($_SERVER as $key => $value) {
                $data .= "<div class=\"fileinfo\"><div>$key</div> <div class=\"blured\">" . $value . "</div></div>";
            }


            echo  $this->makecontainer("Переменные среды", $data);

            $included_files = get_included_files();

            $included = "";
            foreach ($included_files as $filename) {
                $included .= "<div class=\"fileinfo\"><div>$filename</div> <div class=\"blured\">" . (date("F d Y H:i:s.", filemtime($filename))) . "</div></div>";
            }


            echo  $this->makecontainer("Присоединненный файлы", $included);

            echo '</div>';



            echo '<div class="tableheader"><div>Строка</div><div>Время</div><div>Расположение</div><div>Сообщение</div></div>';

            foreach ($this->logs as $key => $log) {

                if ($this->baselog) {

                    if (in_array($this->maxlogtype, $this->logtypes)) {
                        $this->baselogcount++;
                        $this->addBase($log->logtype, $log->loglevel, $log->info, $this->constid);
                    }
                }
                $this->info($log->info, $log->line, $log->file, $log->critical, $this->debugflag, $log->extrainfo);
            }
            echo `<script>
$(function(){
$("#basecount").html($this->baselogcount)
            })
</script>
`;


            echo '</body></html>';
        }
    }




    function info($info, $line, $file, $critical = false, $deb = "deb", $extrainfo)
    {




        if (isset($_GET[$deb])) {
            $totalTime = (round($extrainfo["currentTime"] - $extrainfo["startTime"], 2));
            $operationTime = (round($extrainfo["currentTime"] - $extrainfo["lastTime"], 2));
            echo '<div  class="row' . ($critical ? " red" : "") . '"><div>' . $line . "</div><div> Общее: <span class=\"" . ($totalTime > 20 ? "alert" : "") . "\"> " . $totalTime . " сек. </span><br>Операция: <span class=\"" . ($operationTime > 2 ? "alert" : "") . "\"> " . $operationTime . " сек.</span></div><div>" . $file . '</div><div class="info">' . $info . "</div></div>";
        }
    }




    function formatResponse($newAPI, $level = 0, $gkey = "")
    {

        $array = \json_decode(json_encode($newAPI), true);



        if ($level == 0) {
            //$array = array_multisort($array);
        }
        $llevel = $level;
        $style = "style=\"margin-left:" . (40 + $level * 20) . "px;position:relative;word-break: break-all;display:block;width:100%;margin-top:20px;\"";
        $innerstyle = "style=\"margin-left:" . (40 + $level * 20 + 20) . "px;word-break: break-all;position:relative;display:block;width:100%;border-left:solid 1px navy;padding-left:20px;margin-top:20px;\"";

        $result = array();


        $result['html'] = $level == 0 ? "<div style=\"background-color:#CCCCCC50;font-family:tahoma;color:navy;width:auto;border:solid 1px #cccccc;margin:10px;margin-top:50px;pading:50px;max-height:600px;overflow:scroll; \">" : "";


        $result['text'] = ($level == 0 ? "Корневой объект содержит следующие поля: " : "Объект $gkey содержит следующие поля: ");


        $result['html'] .= ($level == 0 ? "<div $innerstyle>" : (is_integer($gkey) ? "<div $style><strong>Массив [0..n]</strong></div><div $innerstyle>" : "<div $style><strong>$gkey</strong></div><div $innerstyle>"));



        foreach ($array as $key => $arrayItem) {

            if (is_array($arrayItem)) {
                // $array = ksort($arrayItem);
                $array = $arrayItem;
                $result['html'] .= "</div>";
                $level++;
                $result['text'] .= $this->formatResponse($arrayItem, $level, $key)['text'];
                $result['html'] .= $this->formatResponse($arrayItem, $level, $key)['html'];
                $result['html'] .= "<div $innerstyle>";
            } else {
                //$result = array_merge($result, array($key => ''));

                $result['text'] .= $key . ",";

                $result['html'] .= (is_integer($key)) ? ($key > 0 ? "Массив [0..n]" : "") : "<div><strong>$key</strong>&nbsp;&nbsp;&nbsp; $arrayItem</div>";
            }
        }

        $result['html'] .= "</div>";
        $result['html'] .= $llevel == 0 ? "</div>" : "";
        return $result;
    }

    function formatResponse2($JSON)
    {
        $JSON = rawurldecode($JSON);


        // замена ["]  перед [ слово": { ]
        $re = '/\"(?=(\w*)(\")(\s*)(:)(\s*)({))/u';
        $JSON = preg_replace($re, '<div class="key" ><!--nodename-titlte Начало--><div class="nodetittle" onclick="event.stopPropagation();tdt(this);">"', $JSON);



        // замена  любого { 
        $JSON = str_replace(":{", ':</div><!--nodename-titlte Конец-->{', $JSON);

        // замена ["]  перед [ слово": [ ]
        $re = '/\"(?=(\w*)(\")(\s*)(:)(\s*)(\[))/u';
        $JSON = preg_replace($re, '<div class="array"><!--nodename-titlte Начало--><div class="nodetittle" onclick="event.stopPropagation();tdt(this);">', $JSON);

        // замена  любого { 
        $JSON = str_replace(":[", ':</div><!--nodename-titlte Конец-->[', $JSON);

        // замена  любого { 
        $JSON = str_replace("{", '<!--fugure Начало--><div class="open_figure">{</div><div class="node">', $JSON);

        // замена [}]  перед [ , " ]
        $re = '/}(?=(,\s*\"))/u';
        $JSON = preg_replace($re, '} <!--figure Конец--></div>', $JSON);


        // замена [ " ]  перед [ слово":"  ]
        $re = '/\"(?=(\w*)(\")(\s*)(:)(\s*)(\"))/u';
        $JSON = preg_replace($re, '<div class="item">"', $JSON);

        // замена [ ", ]  где нет [ \", ]
        $re = '/(?<!\\\)\",/u';
        $JSON = preg_replace($re, '",</div >', $JSON);


        $re = '/\"(\s*)(:)(\s*)(?=(<))\",/u';
        $JSON = preg_replace($re, '', $JSON);

        $JSON = str_replace("}", '</div></div><div class="close_figure">}</div>', $JSON);
        $JSON = str_replace("[", '<div class="open_square">[</div><div class="node">', $JSON);
        $JSON = str_replace("]", '</div><div class="close_square">]</div></div>', $JSON);


        //замена переносов
        $JSON = str_replace("\n", '', $JSON);
        $JSON = str_replace("\r", '', $JSON);

        $JSON = str_replace('":<', '<', $JSON);
        $JSON = str_replace('"', '', $JSON);
        $JSON = str_replace(':', '&nbsp;:&nbsp;', $JSON);


        return '<div class="jsonformat">' . $JSON . '</div>';
    }


    //////////////////////////////работа с const/////////

    function getConstData()
    {
        $result = [];
        $sql = "select * from nordcom.const";
        $sql_result = mysqli_query($this->webconnection, $sql);

        while ($row = mysqli_fetch_assoc($sql_result)) {
            $result[] = $row;
        }
        return $result;
    }




    function saveConstData($constid, $id)
    {
        $result = [];
        $sql = "update nordcom.const set uid='" . (explode("-", $id)[1] == "" ? "null" : $id) . "' where row_id='$constid'";
        $sql_result = mysqli_query($this->webconnection, $sql);
    }

    function setConstData($id, $uid)
    {

        foreach ($id as $index => $iditem) {
        }

        $sql = "update nordcom.const set uid='" . $uid[$index] . "' where id=$iditem";
        $sql_result = mysqli_query($this->webconnection, $sql);

        while ($row = mysqli_fetch_assoc($sql_result)) {
            $result[] = $row;
        }
        return $result;
    }






    ////////////////////////////////////////////////////////
}
