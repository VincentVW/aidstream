<?php

class Iati_Organisation_Element_Activity_Transaction_AidType extends Iati_Organisation_BaseElement
{
    protected $className = 'AidType';
    protected $displayName = 'Aid Type';
    protected $attribs = array('id' , '@code' , 'text' , '@xml_lang');
    protected $iatiAttribs = array('@code' , 'text' , '@xml_lang');
    protected $tableName = 'iati_transaction/aid_type';
}