<?php
/**
 * Copyright Zikula Foundation 2010 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license MIT
 * @package ZikulaExamples_ExampleDoctrine
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Form handler for create and edit.
 */
class Miniplan_Handler_Settings extends Zikula_Form_AbstractHandler
{

    private $churches;

    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     */
    public $old_settings;
    
    public function initialize(Zikula_Form_View $view)
    {

        // load user with id
        $settings = $this->getVars();
        $old_settings = $settings;
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array());
        $view->assign('settings',$settings)
        	 ->assign('churches',$churches);
        // assign current values to form fields
        return true;
    }

    /**
     * Handle form submission.
     *
     * @param Zikula_Form_View $view  Current Zikula_Form_View instance.
     * @param array            &$args Args.
     *
     * @return boolean
     */
    public function handleCommand(Zikula_Form_View $view, &$args)
    {
        if ($args['commandName'] == 'cancel') {
            return $view->redirect(ModUtil::url('Miniplan', 'admin', 'main' ));
        }
        
        // check for valid form
        if (!$view->isValid()) {
            return false;
        }
        // load form values
        $data = $view->getValues();
        
        //set Weekdays
		$this->setVar('Monday', $data["Monday"]);
		$this->setVar('Tuesday', $data["Tuesday"]);
		$this->setVar('Wednesday', $data["Wednesday"]);
		$this->setVar('Thursday', $data["Thursday"]);
		$this->setVar('Friday', $data["Friday"]);
		$this->setVar('Saturday', $data["Saturday"]);
		$this->setVar('Sunday', $data["Sunday"]);
		
		//set inactive
		$this->setVar('Inactive', $data["Inactive"]);
		
		//set default_church
		$this->setVar('Default_Church', $data["Default_Church"]);
		
		//save churches
		$churches_settings = $this->getVar('Churches');
		foreach($churches_settings as $cid => $church_setting)
		{
			$churches_settings[$cid] = ($data["church_".$cid] == true);
		}
		$this->setVar('Churches', $churches_settings);
		
		//set minis
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
		foreach($minis as $mini)
		{
			//if set no, but not set in past
			$state = $mini->getInactive();
			if($data["Inactive"]&&(!$old_settings["Inactive"])&&(!$state))
			{
				$mini->setInactive(1);
			}
			if($data["ResetInactive"])
				$mini->setInactive(0);
			
			$state = $mini->getDays();
			if($data["Monday"]&&(!$old_settings["Monday"])&&(!$state["Mo"]))
			{
				$state["Mo"] = 1;
			}
			if($data["ResetMonday"])
				$state["Mo"] = 0;
			if($data["Tuesday"]&&(!$old_settings["Tuesday"])&&(!$state["Tue"]))
			{
				$state["Tue"] = 1;
			}
			if($data["ResetTuesday"])
				$state["Tue"] = 0;
			if($data["Wendesday"]&&(!$old_settings["Wednesday"])&&(!$state["Wed"]))
			{
				$state["Wed"] = 1;
			}
			if($data["ResetWednesday"])
				$state["Wed"] = 0;
			if($data["Thursday"]&&(!$old_settings["Thursday"])&&(!$state["Thur"]))
			{
				$state["Thur"] = 1;
			}
			if($data["ResetThursday"])
				$state["Thur"] = 0;
			if($data["Friday"]&&(!$old_settings["Friday"])&&(!$state["Fri"]))
			{
				$state["Fri"] = 1;
			}
			if($data["ResetFriday"])
				$state["Fri"] = 0;
			if($data["Saturday"]&&(!$old_settings["Saturday"])&&(!$state["Sat"]))
			{
				$state["Sat"] = 1;
			}
			if($data["ResetSaturday"])
				$state["Sat"] = 0;
			if($data["Sunday"]&&(!$old_settings["Sunday"])&&(!$state["Sun"]))
			{
				$state["Sun"] = 1;
			}
			if($data["ResetSunday"])
				$state["Sun"] = 0;
			
			$mini->setDays($state);
			
			$churches = $mini->getChurches();
			foreach($churches as $cid => $church)
			{
				if($data["church_".$cid]&&(!$old_settings["church_".$cid])&&(!$church))
					$church = 1;
				if($data["Resetchurch_".$cid])
					$church = 0;
				$churches[$cid] = $church;
			}
			$mini->setChurches($churches);
		}
		$this->entityManager->persist($mini);
        $this->entityManager->flush();
        		
		//set New_Weekdays
		$this->setVar('New_Monday', $data["New_Monday"]);
		$this->setVar('New_Tuesday', $data["New_Tuesday"]);
		$this->setVar('New_Wednesday', $data["New_Wednesday"]);
		$this->setVar('New_Thursday', $data["New_Thursday"]);
		$this->setVar('New_Friday', $data["New_Friday"]);
		$this->setVar('New_Saturday', $data["New_Saturday"]);
		$this->setVar('New_Sunday', $data["New_Sunday"]);
		
		//set New_inactive
		$this->setVar('New_Inactive', $data["New_Inactive"]);
		
		//save churches
		$New_churches_settings = $this->getVar('New_Churches');
		foreach($New_churches_settings as $New_cid => $New_church_setting)
		{
			$New_churches_settings[$New_cid] = ($data["New_church_".$New_cid] == true);
		}
		$this->setVar('New_Churches', $New_churches_settings);

        return $view->redirect(ModUtil::url('Miniplan', 'admin', 'settings' ));
    }
}
