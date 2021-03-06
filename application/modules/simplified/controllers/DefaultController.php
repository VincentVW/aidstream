<?php
/**
 * Handles default functionalities for simplified section.
 * @author YIPL Dev team
 *
 */
class Simplified_DefaultController extends Zend_Controller_Action
{
    public function init()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $this->_helper->layout()->setLayout('layout_wep');
        $this->view->blockManager()->enable('partial/dashboard.phtml');
        $this->view->blockManager()->enable('partial/primarymenu.phtml');
        $this->view->blockManager()->enable('partial/add-activity-menu.phtml');
        $this->view->blockManager()->enable('partial/published-list.phtml');
        $this->view->blockManager()->enable('partial/simplified-info.phtml');

        // for role user check if the user has permission to add, publish ,if not disable menu.
        if($identity->role == 'user'){
            $model = new Model_Wep();
            $userPermission = $model->getUserPermission($identity->user_id);
            $permission = $userPermission->hasPermission(Iati_WEP_PermissionConts::ADD_ACTIVITY);
            $publishPermission = $userPermission->hasPermission(Iati_WEP_PermissionConts::PUBLISH);
            if(!$permission){
                $this->view->blockManager()->disable('partial/add-activity-menu.phtml');
            }
            if(!$publishPermission){
                $this->view->blockManager()->disable('partial/published-list.phtml');
            }
        }

        $this->view->blockManager()->enable('partial/usermgmtmenu.phtml');

    }

    public function indexAction()
    {
        $this->_redirect('simplified/default/dashboard');
    }

    public function dashboardAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        $account_id = $identity->account_id;
        $model = new Model_Wep();
        $activityModel = new Model_Activity();

        $activities_id = $model->listAll('iati_activities', 'account_id', $account_id);
        if (empty($activities_id)) {
            $data['@version'] = '01';
            $data['@generated_datetime'] = date('Y-m-d H:i:s');
            $data['user_id'] = $identity->user_id;
            $data['account_id'] = $identity->account_id;
            $data['unqid'] = uniqid();
            $activities_id = $model->insertRowsToTable('iati_activities', $data);
        } else {
            $activities_id = $activities_id[0]['id'];
        }
        $activityCollModel = new Model_ActivityCollection();
        $activities = $activityCollModel->getActivitiesByAccount($account_id);
        $activitiesAttribs = $activityCollModel->getActivityAttribs($activities);


        $regInfoModel = new Model_RegistryInfo();
        $regInfo = $regInfoModel->getOrgRegistryInfo($account_id);

        $regPublishModel = new Model_RegistryPublishedData();
        $publishedFiles = $regPublishModel->getPublishedInfoByOrg($account_id);

        $this->view->published_data = $published_data;
        $this->view->activity_count = sizeof($activities);
        $this->view->state_count = $activityModel->getCountByState($activities);
        $this->view->last_updated_datetime = $activityModel->getLastUpdatedDatetime($activities);
        $this->view->published_activity_count = $regPublishModel->getActivityCount($publishedFiles);
        $this->view->activity_elements_info = $activitiesAttribs;
        $this->view->registry_url = "/publisher/".$regInfo->publisher_id;
        $this->view->activities_id = $activities_id;
    }

    public function settingsAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        $model = new Model_Wep();
        $modelRegistryInfo = new Model_RegistryInfo();

        $defaultFieldsValues = $model->getDefaults(
                                        'default_field_values',
                                        'account_id',
                                        $identity->account_id
                                    );
        $default['field_values'] = $defaultFieldsValues->getDefaultFields();
        $defaultFieldGroup = $model->getDefaults(
                                        'default_field_groups',
                                        'account_id',
                                        $identity->account_id
                                    );
        $default['fields'] = $defaultFieldGroup->getProperties();
        $form = new Simplified_Form_Default_EditDefaults();
        $form->edit($default);

        $registryInfoData = $modelRegistryInfo->getOrgRegistryInfo($identity->account_id);
        if($registryInfoData){
            $form->populate($registryInfoData->toArray());
        }
        if ($_POST) {
            try {
                $data = $this->getRequest()->getPost();
                if (!$form->isValid($data)) {
                    $form->populate($data);
                } else {
                    
                    //Update Publishing Info
                    $modelRegistryInfo->updateRegistryInfoFromData($data);

                    //Update Default Values
                    $defaults = new Model_Defaults();
                    $defaults->updateDefaults($data);

                    $this->_helper->FlashMessenger
                        ->addMessage(array('message' => "Settings successfully updated."));
                    if ($identity->role == 'superadmin') {
                        $this->_redirect('admin/dashboard');
                    } else if ($identity->role == 'admin') {
                        $this->_redirect('wep/dashboard');
                    }
                }
            } catch (Exception $e) {
                print $e;
            }
        }
        $this->view->blockManager()->enable('partial/dashboard.phtml');
        $this->view->form = $form;
    }

    public function addActivityAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
    
        $model = new Model_Viewcode();
        $rowSet = $model->getRowsByFields('default_field_values' , 'account_id' , $identity->account_id);

        $defaultValues = unserialize($rowSet[0]['object']);
        $default = $defaultValues->getDefaultFields();
        $wepModel = new Model_Wep();

        $reporting_org_info['@reporting_org_name'] = $default['reporting_org'];
        $reporting_org_info['@reporting_org_ref'] = $default['reporting_org_ref'];
        $reporting_org_info['@reporting_org_type'] =$default['reporting_org_type'];
        $reporting_org_info['@reporting_org_lang'] = $default['reporting_org_lang'];
        $incomplete = false;
        foreach($reporting_org_info as $key => $reportingOrgValue){
            if(!$reportingOrgValue && $key != '@reporting_org_lang'){
                $incomplete = true;
                break;
            }
        }
        if($incomplete){
            //For admin user redirect to defaults page.
             //For admin user redirect to defaults page.
            if($identity->role_id == 1){
                $this->_helper->FlashMessenger
                    ->addMessage(array(
                                    'message' => "Before you start entering activity data
                                    you need to add some default values that will
                                    automatically be filled in for
                                    each activity you report."
                                    ));
                $this->_redirect('simplified/default/settings');
            } else { // For other user redirect to dashboard.
                $this->_helper->FlashMessenger
                    ->addMessage(array(
                                    'message' => "All information for Reporting Organisation
                                       is not provided .Please contact you organisation admin"
                                    )
                              );
                $this->_redirect('simplified/default/dashborad');
            }

        }
        $form = new Simplified_Form_Activity_Default();

        $data = $this->_request->getPost();
        if($data){
            $form = new Simplified_Form_Activity_Default(array('data' => $data));
            if($form->validate($data)){
                $fundingOrgData = $data['funding_org'];
                $sector = $data['sector'];
                $data = $form->getValues($data);
                $data['funding_org'] = $fundingOrgData;
                $data['sector'] = $sector;
                
                $modelSimplified = new Simplified_Model_Simplified();
                $activityId = $modelSimplified->addActivity($data , $default);
                
                //Create Activity Hash
                $activityHashModel = new Model_ActivityHash();
                $updated = $activityHashModel->updateActivityHash($activityId);
                
                $this->_helper->FlashMessenger
                    ->addMessage(array('message' => 'Congratulations! You have
                                       successfully created an activity.'));
                $this->_redirect('/simplified/default/view-activity/'.$activityId);
                
            } else {
                $form->populate($data);
                $this->_helper->FlashMessenger
                    ->addMessage(array('error' => 'You have some error in your data.
                                       Please check the fields marked in red to proceed.'));
            }
            
        }        

        $this->view->activities_id = $activities_id;
        $this->view->activity_info = $activity_info;
        $this->view->reporting_org_info = $reporting_org_info;
        $this->view->form = $form;
        
    }

    public function getInitialValues($activity_id, $class)
    {
        $refArray = array(
            'ReportingOrg', 'ParticipatingOrg', 'Transaction'

        );
        $identity = Zend_Auth::getInstance()->getIdentity();
        $model = new Model_Wep();
        $defaultFieldValues = $model->getDefaults(
                                        'default_field_values',
                                        'account_id',
                                        $identity->account_id);
        $defaults = $defaultFieldValues->getDefaultFields();
        $initial['@currency'] = $defaults['currency'];
        //$initial['@xml_lang'] = $defaults['language'];
        $initial['text'] = '';
        if ($class == 'ReportingOrg') {
            $initial['text'] = $defaults['reporting_org'];
        }
        if ($class == 'ReportingOrg') {
            $initial['@ref'] = $defaults['reporting_org_ref'];
        }
        if ($class == 'OtherActivityIdentifier') {
            $initial['@owner_ref'] = $defaults['reporting_org_ref'];
        }
        if ($class == 'ReportingOrg') {
            $initial['@ref'] = $defaults['reporting_org_ref'];
        }
        if ($class == 'Transaction') {
            $initial['@ref'] = $defaults['reporting_org_ref'];
        }
        return $initial;
    }
    
    public function viewActivitiesAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        $wepModel = new Model_Wep();

        if ($_GET) {
            $activities_id = $this->getRequest()->getParam('activities_id');
            $wepModel = new Model_Wep();
            $exists = $wepModel->getRowById('iati_activities', 'id', $_GET['activities_id']);
            if(!$exists){
                $this->_helper->FlashMessenger->addMessage(array('error' => "Invalid Id."));

                $this->_redirect('/user/user/login');
            }
        }
        else{
            $activities = $wepModel->listAll('iati_activities', 'account_id', $identity->account_id);
            $activities_id = $activities[0]['id'];
        }

        $this->view->activities_id = $activities_id;
        $activityArray = $wepModel->listAll('iati_activity', 'activities_id', $activities_id);

        //foreach activity get activity title
        $titleArray = array();
        if ($activityArray) {
            $i = 0;
            foreach($activityArray as $key=>$activity){

                $title = $wepModel->listAll('iati_title', 'activity_id', $activity['id']);
                $identifier = $wepModel->listAll('iati_identifier', 'activity_id', $activity['id']);
                //                    print_r($title[0]['text']);exit;
                $activity_array[$i]['title'] = ($title[0]['text'])?$title[0]['text']:'No title';
                $activity_array[$i]['identifier'] = ($identifier[0]['activity_identifier'])?
                                                        $identifier[0]['activity_identifier']:
                                                        'No Activity Identifier';
                $activity_array[$i]['last_updated_datetime'] = $activity['@last_updated_datetime'];
                $activity_array[$i]['id'] = $activity['id'];
                $activity_array[$i]['status_id']  = $activity['status_id'];
                $i++;
            }
        }
        if(Iati_WEP_ActivityState::hasPermissionForState(Iati_WEP_ActivityState::STATUS_PUBLISHED)){
            $modelRegistryInfo = new Model_RegistryInfo();
	        $registryInfo = $modelRegistryInfo->getOrgRegistryInfo($identity->account_id);

	        $status_form = new Form_Wep_ActivityStatus();
            if($registryInfo->update_registry){
                $status_form->change->setLabel('Publish and Register');
            } else {
                $status_form->change->setLabel('Publish');
            }
            $status_form->change->setAttrib('id','publish');
            $status_form->status->setValue(Iati_WEP_ActivityState::STATUS_PUBLISHED);
            $status_form->setAction($this->view->baseUrl()."/wep/update-status");
        }

        $this->view->activity_array = $activity_array;
        $this->view->status_form = $status_form;
    }
    
    public function viewActivityAction()
    {
        if(!$activityId = $this->getRequest()->getParam('activity_id'))
        {
            $this->_redirect('/wep/view-activities');
        }

        $identity = Zend_Auth::getInstance()->getIdentity();
        $model = new Model_Wep();
        
        $activity_info = $model->listAll('iati_activity', 'id', $activityId);
        $activity = $activity_info[0];
        $state = $activity['status_id'];
        $activity['@xml_lang'] = $model->fetchValueById(
                                            'Language',
                                            $activity_info[0]['@xml_lang'],
                                            'Code');
        $activity['@default_currency'] = $model->fetchValueById(
                                                    'Currency',
                                                    $activity_info[0]['@default_currency'],
                                                    'Code');

        $iati_identifier_row = $model->getRowById('iati_identifier', 'activity_id', $activityId);
        $activity['activity_identifier'] = $iati_identifier_row['activity_identifier'];

        // Get form for status change
        $next_state = Iati_WEP_ActivityState::getNextStatus($state);
        if($next_state && Iati_WEP_ActivityState::hasPermissionForState($next_state)){
            $status_form = new Form_Wep_ActivityChangeState();
            $status_form->setAction($this->view->baseUrl()."/wep/update-status?redirect="
                                    .urlencode($this->getRequest()->getRequestUri()));
            $status_form->ids->setValue($activityId);
            $status_form->status->setValue($next_state);
            $status_form->change_state->setLabel(Iati_WEP_ActivityState::getStatus($next_state));
        } else {
            $status_form = null;
        }

        $model = new Simplified_Model_Simplified();
        $data = $model->getDataForForm($activityId);
        
        $this->view->activityData = $data;
        $this->view->status_form = $status_form;
        $this->view->state = $state;
        $this->view->activityInfo = $activity;
        $this->view->activity_id = $activityId;
    }
    
    public function editActivityAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        $wepModel = new Model_Wep();
        $identity = Zend_Auth::getInstance()->getIdentity();
    
        $modelVc = new Model_Viewcode();
        $rowSet = $modelVc->getRowsByFields(
                                'default_field_values' ,
                                'account_id' ,
                                $identity->account_id);
        $defaultValues = unserialize($rowSet[0]['object']);
        $default = $defaultValues->getDefaultFields();

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $form = new Simplified_Form_Activity_Default(array('data' => $formData));
            if (!$form->validate($formData)) {
                $form->populate($formData);
                $this->_helper->FlashMessenger
                    ->addMessage(array('error' => 'You have some error in form data'));
            } else {
                $fundingOrgData = $formData['funding_org'];
                $sector = $formData['sector'];
                $location = $formData['location'];
                $formData = $form->getValues($formData);
                $formData['funding_org'] = $fundingOrgData;
                $formData['sector'] = $sector;
                $formData['location'] = $location;

                $activityId = $formData['activity_id'];
                $model = new Simplified_Model_Simplified();
                $model->updateActivity($formData , $default);
                
                $activityHashModel = new Model_ActivityHash();
                $updated = $activityHashModel->updateActivityHash($activityId);
                
                if(!$updated){
                    $type = 'message';
                    $message = 'No Changes Made';
                } else {
                    //change state to editing
                    Model_Activity::updateActivityUpdatedInfo($activityId);   
                    $type = 'message';
                    $message = "Information updated sucessfully";
                }
                
                $this->_helper->FlashMessenger->addMessage(array($type => $message));
                $this->_redirect('simplified/default/view-activity/' . $activityId);
            }//end of inner if
        } else {
            if($activityId = $this->getRequest()->getParam('activity_id')){
                $activity = $wepModel->getRowById('iati_activity', 'id', $activityId);
                if(!$activity){
                    $this->_helper->FlashMessenger
                        ->addMessage(array('warning' => "Activity does not exist."));
    
                    $this->_redirect('/simplified/default/dashboard');
                }
            

                $model = new Simplified_Model_Simplified();
                $data = $model->getDataForForm($activityId);
                $data['activity_id'] = $activity['id'];
                // Get identifier
                $identifier = $wepModel->getRowById('iati_identifier', 'activity_id', $activityId);
                $data['identifier_id'] = $identifier['id'];
                $data['identifier'] = $identifier['activity_identifier'];
                
                
                $form = new Simplified_Form_Activity_Default(array('data' => $data));
                $this->view->activityInfo = $activityInfo;
            }
        }

        $this->view->form = $form;
        $this->view->activity_id = $activity_id;

    }    

    public function getHelpMessageAction()
    {
        $element_name = $this->getRequest()->getParam('element');
        $model = new Model_Help();
        $message = $model->getHelpMessage($element_name);
        if(!$message['message'])
        {
            $message['message'] = 'No help is provided for this item';
        }
        $this->_helper->json($message['message']);
    }
    
    public function getFormAction()
    {
        $class = $this->_getParam('class');
        $refEle = $this->_getParam('refEle');
        $count = 0;
        if($refEle){
            $temp = explode('-' , $refEle);
            $count = $temp[1] + 1;
        }
        $formName = 'Simplified_Form_Activity_'.$class;
        $form = new $formName(array('count' => $count));
        $form->removeDecorator('form');
        $partialPath = Zend_Registry::get('config')->resources->layout->layoutpath;
        $myView = new Zend_View;
        $myView->setScriptPath($partialPath.'/partial');
        $myView->assign('form' , $form);
        $form = $myView->render('form.phtml');
        print $form;
        exit;
    }
    
    
    public function removeElementAction()
    {
        $this->_helper->layout->disableLayout();
        if($this->_request->isGet()){
            try{
                if($_GET['classname'])
                {
                    $class = $_GET['classname'];
                }

                if($_GET['id']){
                    $id = $_GET['id'];
                }

                $fieldName = 'id';
                $value = $id;
                $dbLayer = new Iati_WEP_DbLayer();
                $del = $dbLayer->deleteRows($class, $fieldName, $value);
                print 'success';
                exit();

            } catch(Exception $e){
                print $e; exit();
            }
        }
    }
}
