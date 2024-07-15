<?php
class DebugMessage{
public $line;
public $info;
public $critical;
public $extrainfo;
public $file;

function __construct($line,$info,$critical,$file,$extrainfo=[]){

    $this->line=$line;
    $this->info=$info;
    $this->critical=$critical;
    $this->extrainfo=$extrainfo;
    $this->file=$file;
    


}




}