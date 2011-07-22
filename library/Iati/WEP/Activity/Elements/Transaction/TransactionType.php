<?php 
class Iati_WEP_Activity_Elements_Transaction_TransactionType extends Iati_WEP_Activity_Elements_Transaction
{
    protected $attributes = array('text', 'code');
    protected $text;
    protected $code;
    protected $id = 0;
    protected $options = array();
    
    protected $attributes_html = array(
                'id' => array(
                    'name' => 'id',
                    'html' => '<input type= "hidden" name="%(name)s" value= "%(value)s" />' 
                ),
                'text' => array(
                    
                    'name' => 'text',
                    'label' => 'Text',
                    'html' => '<input type="text" name="%(name)s" %(attrs)s value= "%(value)s" />',
                    'attrs' => array('id' => 'id')
                ),
                'code' => array(
                    'name' => 'code',
                    'label' => 'Transaction Type Code',
                    'html' => '<select name="%(name)s" %(attrs)s>%(options)s</select>',
                    'options' => '',
                )
    );
    
    protected static $count = 0;
    protected $objectId;

    public function __construct()
    {
        $this->objectId = self::$count;
        self::$count += 1;
    
        $this->setOptions();
        
        $this->validators = array(
                                'code' => 'NotEmpty',
                            );
        $this->multiple = true;
    }
    
    public function setOptions()
    {
        $model = new Model_Wep();
        
        $this->options['code'] = array_merge(array('0' => 'Select anyone'), 
                                                $model->getCodeArray('TransactionType', null, '1'));
    }
    
    public function getClassName () {
        return 'TransactionType';
    }
    
    public function setAttributes ($data) {
        $this->code = (key_exists('@code', $data))?$data['@code']:$data['code'];
        $this->text = $data['text'];
    }
    
    public function getHtmlAttrs()
    {
        return $this->attributes_html;
    }
    
    public function getOptions($name = NULL)
    {
        return $this->options[$name];
    }
    
    public function getObjectId()
    {
        return $this->objectId;
    }
    
    public function validate()
    {
        $data['code'] = $this->code;
        $data['text'] = $this->text;
        
        parent::validate($data);
    }
}