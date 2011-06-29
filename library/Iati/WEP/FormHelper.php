<?php

class Iati_WEP_FormHelper {
    
    protected $objects = array();
    protected $globalObject;
    
    public function __construct($objects=array()) {
        $this->objects = $objects;
//        $this->globalObject = array_shift($this->objects);
        $this->globalObject = $this->objects[0];
    }
    
    public function getForm() {
        if (empty($this->objects)) {
            throw new Exception("Nothing to do with empty object list");
        }
        
        $form = '';
//        print_r($this->objects);exit;
        foreach ($this->objects as $obj) {
            $error_code = $obj->hasErrors();
            $form .= $obj->toHtml($error_code);
        }
        
        $form_string = $this->_form($this->globalObject->getObjectName(), '#');
        
        $form = sprintf($form_string, $form);
        
        
        if ($this->globalObject->hasMultiple()) {
            $form .= $this->_addMore(array('id'=>'add-more'));
        }
        
        return $this->_wrap($form, 'div');
    }
    
    private function _form($name, $action, $method="post", $attribs=null) {
        $_form = sprintf('<fieldset><legend>%s</legend><form id = "element-form" name="%s" action="%s" method="%s" %s>',
                         $name,$name, $action, $method, $this->_attr($attribs));
        $_form .= '<div id = "form-elements-wrapper">%s</div><input type="submit" id="Submit" value="Save" />';
        $_form .= '</form></fieldset>';
        return $_form;
    }
    
    protected function _wrap($formElement, $tag='p', $attribs=null) {
        return sprintf('<%s %s>%s</%s>', $tag,
                       $this->_attr($attribs), $formElement, $tag);
    }
    
    protected function _addMore($attribs=null, $tag='div', $text='Add More') {
        return sprintf('<div %s>%s</div>', $this->_attr($attribs), $text);
    }
    
    protected function _attr($attribs) {
        $_attrs = array();
        if ($attribs) {
            foreach ($attribs as $key=>$value) {
                array_push($_attrs, $key.'="'.$value.'"');
            }
        }
        return (count($_attrs) > 0 ? implode(' ', $_attrs) : '');
    }
}
?>