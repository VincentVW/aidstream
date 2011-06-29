<?php
class Iati_Codelist_Collection_ActivityDateType extends Iati_Codelist_Collection_ICodelistCollection
{
    public function __construct($lang = '1') {

        $this->tableName = Iati_Codelist_Constants::$ActivityDateType;

        $this->lang = $lang;

        $this->fetchResultSet();

        $this->Process();

    }


     
    public function Process()

    {
        foreach($this->resultSet['0'] as $eachData){

            $this->data[] = array('code' => $eachData['Code'], 'name'=> $eachData['Description']);

        }

    }


}