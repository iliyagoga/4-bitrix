<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule('iblock');

function addField($elem, $type,$data,$dataId,$element){
    if($elem['CODE']==$type){
        foreach($elem['LIST'] as $k=>$v){
            $k=trim($k);
            $data[$dataId]=trim($data[$dataId]);
            if( $k==$data[$dataId] ){
                $element["PROPERTY_VALUES"][$type]=$v;
            }
            else{
                if(!isset($element["PROPERTY_VALUES"][$type])){
                $element["PROPERTY_VALUES"][$type]=$data[$dataId];
                }
            }
        }
    }

    return $element;
}
$IBLOCK_ID = 1;

$arProps = [];
$rsProp = CIBlockPropertyEnum::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => $IBLOCK_ID]
);
while ($arProp = $rsProp->Fetch()) {
    $arProps[$arProp['PROPERTY_CODE']][$arProp['VALUE']] = $arProp['ID'];
}

$properties = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $IBLOCK_ID));
$list=[];

while ($arProperty = $properties->Fetch()) {
    foreach($arProps as $k=>$v)  {
        if($arProperty['CODE']==$k ){
            $arProperty['LIST']=$v;
            array_push($list,$arProperty);
        }
        }
    }
$row = 1;
$handle = fopen("vacancy.csv", "r");

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if ($row == 1) {
            $row=2;
            continue;
        }
        $data[4]=explode('•',preg_replace('/^•/','',$data[4]));
        $data[5]=explode('•',preg_replace('/^•/','',$data[5]));
        $data[6]=explode('•',preg_replace('/^•/','',$data[6]));
        $element=["MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => [],
            "NAME" => $data[3],
            "ACTIVE" => end($data) ? 'Y' : 'N',
                    ];
       
        $element["PROPERTY_VALUES"]['EMAIL']=$data[12];
        $element["PROPERTY_VALUES"]['DATE']= date('d.m.Y');
        $element["PROPERTY_VALUES"]['REQUIRE']=$data[4];
        $element["PROPERTY_VALUES"]['DUTY']=$data[5];
        $element["PROPERTY_VALUES"]['CONDITIONS']=$data[6];
        foreach($list as $elem){
            $element=addField($elem, 'ACTIVITY',$data,'9',$element);
            $element=addField($elem, 'FIELD',$data,'11',$element);
            $element=addField($elem, 'TYPE',$data,'8',$element);
               $element=addField($elem, 'SCHEDULE',$data,'10',$element);
            if($elem['CODE']=='OFFICE'){
                $weigth=[];
                foreach($elem['LIST'] as $k=>$v){
                    $weight [$v]=similar_text($k,$data['1']);
                }
                $element["PROPERTY_VALUES"]['OFFICE']=array_search(max($weight),$weight);

            }
            if($elem['CODE']=='LOCATION'){
                foreach($elem['LIST'] as $k=>$v){
                    $k=explode(', ',$k);
                    if( trim($k[0])==$data['2'] || (count($k)>1&&trim($k[1])==$data['2']) ){
                        $element["PROPERTY_VALUES"]['LOCATION']=$v;
                    }
                    else{
                        if(!isset($element["PROPERTY_VALUES"]['LOCATION'])){
                            $element["PROPERTY_VALUES"]['LOCATION']='';
                        }
                    }
                }
            }
         
            if($elem['CODE']=='SALARY_TYPE'){
                $data['7']=trim($data['7']);
                if( $data['7'] =='-' ){
                    $element["PROPERTY_VALUES"]['SALARY_TYPE']=55;
                }
                elseif(strpos($data['7'],'от') !==false ){
                    $element["PROPERTY_VALUES"]['SALARY_TYPE']=53;
                    $element["PROPERTY_VALUES"]['SALARY_VALUE']=explode('от ',$data['7'])[1];
                } 
                elseif(strpos($data['7'],'до') !==false ){
                    $element["PROPERTY_VALUES"]['SALARY_TYPE']=54;
                    $element["PROPERTY_VALUES"]['SALARY_VALUE']=explode('от ',$data['7'])[1];
                }
                else{
                    if(!isset($element["PROPERTY_VALUES"]['SALARY_TYPE'])){
                        $element["PROPERTY_VALUES"]['SALARY_TYPE']=55;
                        $element["PROPERTY_VALUES"]['SALARY_VALUE']=trim($data['7']);
                    }
                }
            }
        }
        $el = new CIBlockElement;
        if ($PRODUCT_ID = $el->Add($element)) {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $el->LAST_ERROR . '<br>';
        }
    }
    fclose($handle);



