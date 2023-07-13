<?

    $repOpt=array();
    $repOpt['']['width']=18;
    $repOpt['']['type']="img";
    //$repOpt['Тип']['width']=20;
    //$repOpt['ЦОД']['width']=30;
    $repOpt['Дата']['width']=50;
    $repOpt['Дата']['type']='date';
    $repOpt['Документ']['width']=160;
    $repOpt['Сумма']['width']=60;    
    $repOpt['Сумма']['type']="price";    
    $repOpt['Автор']['width']=50;    
    


    repEchoTableHeader();    

    //,case when zodCompany.zod=".intval($zodLocal)." then 1 else 0 end as Приоритет
    $contr=array();    
    $sql="select zodCompany.inn as ИНН, zodCompany.name as Наименование    
    from zodCompany, zodCompanyPhone, zod
    where zodCompanyPhone.zod=zodCompany.zod 
    and zod.zod=zodCompany.zod 
    and zodCompany.id=zodCompanyPhone.zodCompany
    and zodCompanyPhone.phone='".GetCifr($zodPhone)."'
    group by zodCompany.inn
    order by 1
    ";
    $deb=0;
    $sql=zodSQL($sql);
    while ($sqlg=mysqli_fetch_array($sql,MYSQLI_ASSOC)){
        //repEchoTitle($sqlg['Наименование']);
        $styline=array();
        $styline['font-bold']=1;
        $styline['font-size']=12;
        $styline['padding']=2;
        repEchoTableLine($sqlg['Наименование']." (".$sqlg['ИНН'].")",4,$styline);



        $wh=" and zodCompany.inn=".$sqlg['ИНН']." and zod.zod=".$zodLocal;
        $sql2="select zod.zod as zod, zod.namelit as ЦОД
        ,zodDoc.id as zodDocId
        ,zodDoc.sum as Сумма
        ,zodDocType.row_id as zodDocTypeId,zodDocType.name2 as Документ
        ,zodDoc.no as Номер, zodDoc.data as Дата, zodDoc.prov, zodDoc.del
        ,(select zodAuthor.name from zodAuthor where zodAuthor.inn=zodDoc.zodAuthor limit 1) as Автор
        ,(select zodDocStatus.name from zodDocStatus where zodDocStatus.row_id=zodDoc.zodDocStatus_id limit 1) as Статус
        from zodDoc, zod, zodDocType, zodCompany        
        where zodDoc.zod=zod.zod and zodDoc.zodDocType_ID=zodDocType.row_id
        and zodCompany.zod=zod.zod and zodCompany.id=zodDoc.zodCompany
        ".$wh."
        group by zodDoc.zod, zodDoc.id
        order by zodDoc.data desc
        limit 10"; 
        $deb=0;       
        $sql2=zodSQL($sql2);
        $oldzod=0;
        while ($sql2g=mysqli_fetch_array($sql2,MYSQLI_ASSOC)){
            if ($oldzod==0){
                $styline=array();
                $styline['background']='fffbf0';
                //$styline['padding']=2;                
                repEchoTableLine("ЦОД: ".$sql2g['ЦОД'],4,$styline);  
                $oldzod=-1;
            }

            $docimg="doc";
            if ($sql2g['del']==1) $docimg.="del";
            if ($sql2g['prov']==1) $docimg.="prov";
            $sql2g['']=$docimg.".png";

            $sql2g['Документ'].=" №".$sql2g['Номер'];

            repEchoTableLine($sql2g);            
        }

        $wh=" and zodCompany.inn=".$sqlg['ИНН']." and zod.zod<>".$zodLocal;
        $sql2="select zod.zod as zod, zod.namelit as ЦОД
        ,zodDoc.id as zodDocId
        ,zodDoc.sum as Сумма
        ,zodDocType.row_id as zodDocTypeId,zodDocType.name2 as Документ
        ,zodDoc.no as Номер, zodDoc.data as Дата, zodDoc.prov, zodDoc.del
        ,(select zodAuthor.name from zodAuthor where zodAuthor.inn=zodDoc.zodAuthor limit 1) as Автор
        ,(select zodDocStatus.name from zodDocStatus where zodDocStatus.row_id=zodDoc.zodDocStatus_id limit 1) as Статус
        from zodDoc, zod, zodDocType, zodCompany        
        where zodDoc.zod=zod.zod and zodDoc.zodDocType_ID=zodDocType.row_id
        and zodCompany.zod=zod.zod and zodCompany.id=zodDoc.zodCompany
        ".$wh."
        group by zodDoc.zod, zodDoc.id
        order by zodDoc.data desc
        limit 10"; 
        $deb=0;       
        $sql2=zodSQL($sql2);
        $oldzod=0;
        while ($sql2g=mysqli_fetch_array($sql2,MYSQLI_ASSOC)){
            if ($oldzod!=$sql2g['zod']){
                $styline=array();
                $styline['border']='0';
                //$styline['padding']=2;                
                repEchoTableLine("&nbsp;",array(0),$styline);
                
                $styline=array();
                $styline['background']='fffbf0';
                //$styline['padding']=2;                
                repEchoTableLine("ЦОД: ".$sql2g['ЦОД'],4,$styline);            

                $oldzod=$sql2g['zod'];
            }
            $docimg="doc";
            if ($sql2g['del']==1) $docimg.="del";
            if ($sql2g['prov']==1) $docimg.="prov";
            $sql2g['']=$docimg.".png";

            $sql2g['Документ'].=" №".$sql2g['Номер'];

            repEchoTableLine($sql2g);            
        }        



    }

    repEchoTableFooter();

?>