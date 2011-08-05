<?php
class Iati_WEP_AccountDisplayFieldGroup
{
    protected $title = '0';
    protected $activity_date = '0';
    protected $participating_org = '0';
    protected $transaction = '0';
    protected $other_identifier = '0';
    protected $description = '0';
    protected $activity_status = '0';
    protected $contact_info = '0';
    protected $recipient_country = '0';
    protected $recipient_region = '0';
    protected $location = '0';
    protected $sector = '0';
    protected $policy_marker = '0';
    protected $collaboration_type = '0';
    protected $default_flow_type = '0';
    protected $default_finanace_type = '0';
    protected $default_aid_type = '0';
    protected $default_tied_status = '0';
    protected $budget = '0';
    protected $planned_disbursement = '0';
    protected $document_link = '0';
    protected $activity_website = '0';
    protected $related_activity = '0';
    protected $conditions = '0';
    protected $result = '0';
    
    public function getProperties(){
        return get_object_vars($this);
    }
    
    public function setProperties($property){
        $this->$property = ((property_exists($this, $property))?'1': false);
        if(!$this->$property){
            throw new Exception("Property $property not found.");
        }

    }
    
   
    
   
}