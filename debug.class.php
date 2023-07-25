<?php
require "debugMessage.class.php";


class Debug
{

    public $logs = [];
    private $debugflag;
    private $debuginfo;
    function __construct($debuginfo, $debugflag = "deb")
    {

        $this->debugflag = $debugflag;
        $this->debuginfo = $debuginfo;
    }


    function __destruct()
    {

        $this->displaylog();
    }


    function addlog($info, $critical = false)
    {


        if (isset($_GET[$this->debugflag])) {

            $this->logs[] = new DebugMessage(debug_backtrace()[0]["line"], $info, $critical, debug_backtrace()[0]["file"]);
        }
    }


    function displaylog()
    {
        if (isset($_GET[$this->debugflag ])){
        ob_clean();

        echo '<html>';
        echo '<style type="text/css">';
        //////////////////стили вывода начало

        echo 'body{ margin:70px;}';
        echo '.row:nth-child(2n){ background:#fbfbfb;}';
        echo '.row,.debuginfo{overflow-wrap: break-word;background:#fafafa;cursor:pointer;display:grid;grid-template-columns:70px 300px 1fr;width:100%;padding:10px;border-bottom:solid 1px #ededed;font-family:tahoma;}';
        echo '.debuginfo{display:block;margin-bottom:40px;}';
        echo '.header{font-weight:600}';

        echo '.red,.red:nth-child(2n){background:#ffab90}';
        echo '.info{padding:0 20px;width:100%;overflow-wrap: break-word;}';
        echo '.subheader{padding:0 20px;width:100%;overflow-wrap: break-word;}';


        echo '.tableheader{display:grid;grid-template-columns:50px 300px 300px;padding:5px 20px;width:100%;overflow-wrap: break-word;}';
        echo '.tablerow{display:grid;grid-template-columns:50px 300px 300px;padding:5px 20px;width:100%;overflow-wrap: break-word;}';
        



        //////////////////стили вывода конец        
        echo '</style><body>';

        echo '<div class="debuginfo"> <div class="header">Отладочная информация</div>';
        
        echo '<div class="subheader">'.$this->debuginfo.'</div>';
        echo '<div style="height:200px;overflow:hidden;overflow-y:scroll;padding:20px;">';

        $included_files = get_included_files();

        foreach ($included_files as $filename) {
            echo "$filename<br>";
        }

        if (isset($this->debuginfo["header"])) echo '<div>' . $this->debuginfo["header"] . '</div>';

        echo '</div></div>';



        echo '<div style="cursor:pointer;display:grid;grid-template-columns:70px 300px 1fr;width:100%;background: #dadada;padding:10px;border:solid 1px #ededed;font-family:tahoma;"><div>Строка</div><div>Файл</div><div>Сообщение</div></div>';

        foreach ($this->logs as $key => $log) {
            $this->info($log->info, $log->line, $log->file, $log->critical);
        }

        echo "</body></html>";}
    }



    function info($info, $line, $file, $critical = false, $deb = "deb")
    {




        if (isset($_GET[$deb])) {
            echo '<div  class="row'.( $critical?" red":"").'"><div>' . $line . "</div><div>" . $file . '</div><div class="info">' . $info . "</div></div>";

        }
    }


    

    public function formatResponse($newAPI, $level = 0, $gkey = "")
    {

        $array = json_decode(json_encode($newAPI), true);

      

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

 
}
