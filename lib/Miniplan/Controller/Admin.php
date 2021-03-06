<?php

/**
 * This is the admin controller class providing navigation and interaction functionality.
 */
class Miniplan_Controller_Admin extends Zikula_AbstractController
{
	/**
	 * @brief Main function.
	 * @throws Different views according to the access
	 * @return template Admin/Main.tpl
	 * 
	 * @author Sascha Rösler
	 */
	 
	 /*
	 *Security: access ADmin: add and remove sites
	 *			access Moderate: confirm pages
	 *			access Edit: edit pages
	 */
	public function main()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$descriptions = ModUtil::apiFunc('Miniplan', 'Create', 'getRoutinedescription');
		
		$minidb = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy();
		$thereiswhattdo = 0;
		foreach($minidb as $mini){
			if($mini->getEdited() == 0)
				$thereiswhattdo = 1;
		}
		
	 	return $this->view
	 	->assign('descriptions',$descriptions)
	 	->assign('thereiswhattdo',$thereiswhattdo)
		->fetch('Admin/Main.tpl');
	
	}
	
	/***
	*This function returns an array of all ministrants
	***/
	public function getMinistrants()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$users = UserUtil::getUsers();
		$ministrants = array();
		foreach($users as $user)
		{
			if($user['__ATTRIBUTES__'][ministrant_prop]
				&&$user['__ATTRIBUTES__'][ministrant_state_prop] == "1")
				$ministrants[] = $user;
		}
		return $ministrants;
	}
	
	/**
	*show the group of ministrants
	*/
	public function group()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$users = UserUtil::getUsers();
		$ministrants = array();
		foreach($users as $user)
		{
			if($user['__ATTRIBUTES__'][ministrant_prop]
				&&$user['__ATTRIBUTES__'][ministrant_state_prop] == "1")
				{
					$minidb = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array('uid'=>$user['uid']));
					$minidb = $minidb[0];
					$ministrants[] = array("user" =>$user, "db"=>$minidb);
				}
		}
		return $this->view
		->assign('ministrants', $ministrants)
		->fetch('Admin/Group.tpl');
	
	}
	
	/**
	*show requests of ministrants
	*/
	public function Requests()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$users = UserUtil::getUsers();
		$pendings = array();
		foreach($users as $user)
		{
			if($user['__ATTRIBUTES__'][ministrant_prop]
				&& (
					$user['__ATTRIBUTES__'][ministrant_state_prop] == "" 
					||$user['__ATTRIBUTES__'][ministrant_state_prop] == "0"))
				$pendings[] = $user;
		}
		return $this->view
		->assign('pendings', $pendings)
		->fetch('Admin/Requests.tpl');
	
	}
	
	/***
	*The following functions manage the Ministrants
	***/
	
	/***
	* Add an ministrant
	***/
	public function Add_ministrant()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$actionid = FormUtil::getPassedValue('id',null,'GET');
		$user = UserUtil::getPNUser($actionid);
		
		//write down to db:
		$userdb = new Miniplan_Entity_User();
		$userdb->setUid($actionid);
		$userdb->setMy_calendar('');
		$userdb->setChurches($this->getVar('New_Churches'));
		//set all days to not locked
		$userdb->setDays(array(
		'Mo'	=> $this->getVar('New_Monday'),
		'Tue'	=> $this->getVar('New_Tuesday'),
		'Wed'	=> $this->getVar('New_Wednesday'),
		'Tuers'	=> $this->getVar('New_Thursday'),
		'Fri'	=> $this->getVar('New_Friday'),
		'Sat'	=> $this->getVar('New_Saturday'),
		'Sun'	=> $this->getVar('New_Sunday')));
		$userdb->setInactive($this->getVar('New_Inactive'));
		$userdb->setInfo('');
		$userdb->setNicname('');
		$userdb->setpid(0);
		$userdb->setEdited(0);
		$userdb->setPpriority(0);
		$this->entityManager->persist($userdb);
		$this->entityManager->flush();
		
		//write down state
		UserUtil::setVar(ministrant_state_prop,"1",$actionid);
		//group:
		//get groupid
		$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => ministrant_group) );
		//add user to group
		ModUtil::apiFunc('Groups', 'admin', 'adduser',array('gid'=>$gid,'uid'=>$actionid));
		
		LogUtil::RegisterStatus($this->__("Ministrant has been added successfully."));
		
		
		$this->redirect(ModUtil::url($this->name, 'admin', 'Requests'));
	}
	
	/***
	* Delete an ministrant
	***/
	public function Delete_ministrant()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$actionid = FormUtil::getPassedValue('id',null,'GET');
		$path = FormUtil::getPassedValue('path',null,'GET');
		$mini = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array('uid'=>$actionid));
		$mini = $mini[0];
		echo $path;
		echo $actionid;
		$mid = $mini->getMid();
		
		$user = UserUtil::getPNUser($actionid);
		//write down state
		UserUtil::setVar(ministrant_state_prop,"2",$actionid);
		//group:
		//get groupid
		$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => ministrant_group) );
		//add user
		ModUtil::apiFunc('Groups', 'admin', 'removeuser',array('gid'=>$gid,'uid'=>$actionid));
		//delete user from db
		$this->entityManager->remove($mini);
		$this->entityManager->flush();
		//reset the pids
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array('pid'=>$mid));
		if($minis)
		{
			foreach ($minis as $mini)
			{
				$mid = $mini->getMid();
				$mymini = $this->entityManager->find('Miniplan_Entity_User', $mid);
				$mymini->setPid(0);
				$mymini->setPpriority(0);
				$this->entityManager->persist($mymini);
				$this->entityManager->flush();
			}
		}
		LogUtil::RegisterStatus($this->__("Minisrtant has been removed successfully."));
	
	
		$this->redirect(ModUtil::url($this->name, 'admin', $path));
	}
	
	/**
	*show churches
	*/
	public function church()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array(),array('cid'=>'ASC'));
		return $this->view
			->assign('churches', $churches)
			->fetch('Admin/Church.tpl');
	}
	
	/**
	 * @brief Churche add function.
	 * @throws Zikula_Forbidden If not ACCESS_MODERATE
	 * @return redirect self::Church()
	 */
	public function ChurchAdd()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$action = FormUtil::getPassedValue('action', null, 'POST');
		switch($action)
		{
		case 'add':
			$name = FormUtil::getPassedValue('inname', null, 'POST');
			$shortname = FormUtil::getPassedValue('inshortname', null, 'POST');
	  		$adress = FormUtil::getPassedValue('inadress', null, 'POST'); 	
		
			if($name == "")
				return LogUtil::RegisterError($this->__("The added church has no name."), null, ModUtil::url($this->name, 'admin', 'Church'));
			if($adress == "")
				LogUtil::RegisterStatus($this->__("The church has no adress."));
		
			$church = new Miniplan_Entity_Church();
			$church->setName($name);
			$church->setShortName($shortname);
			$church->setorg_cid(0);
			$church->setAdress($adress);
			$this->entityManager->persist($church);
			$this->entityManager->flush();
			$cid = $church->getCid();
			
			//create for every ministrant an church input
			$users = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
			foreach($users as $user)
			{
				$my_churches=$user->getChurches();
				$my_churches[$cid]=0;
				$uid=$user->getUid();
				
				$user->setChurches($my_churches);
				$this->entityManager->persist($user);
				$this->entityManager->flush();
			}
			
			//create setting var
			$setting_churches = $this->getVar('Churches');
			$setting_churches[$cid] = $this->getVar('Default_Church');
			$this->setVar('Churches', $setting_churches);
			
			//create setting var
			$setting_churches = $this->getVar('New_Churches');
			$setting_churches[$cid] = $this->getVar('Default_Church');
			$this->setVar('New_Churches', $setting_churches);
			
			LogUtil::RegisterStatus($this->__("Church has been added successfully."));
			break;
		
			case 'del':
			$actionid = FormUtil::getPassedValue('id',null,'POST');
			if( $actionid=="")
				return LogUtil::RegisterError($this->__("ID is missing."), null, ModUtil::url($this->name, 'admin','Church'));
			$church = $this->entityManager->find('Miniplan_Entity_Church', $actionid);
		
		}
		$this->redirect(ModUtil::url($this->name, 'admin', 'Church'));
	}
	
	public function ChurchEdit()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$actionid = FormUtil::getPassedValue('id',null,'GET');
		if( $actionid=="")
			return LogUtil::RegisterError($this->__("ID is missing."), null, ModUtil::url($this->name, 'admin','Church'));
		$form = FormUtil::newForm('Miniplan', $this);
		return $form->execute('Admin/ChurchEdit.tpl', new Miniplan_Handler_Edit());
		break;
	}
	
	public function ChurchDel()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$actionid = FormUtil::getPassedValue('id',null,'GET');
		if( $actionid=="")
			return LogUtil::RegisterError($this->__("ID is missing."), null, ModUtil::url($this->name, 'admin','Church'));
		$church = $this->entityManager->find('Miniplan_Entity_Church', $actionid);
		//del church
		$this->entityManager->remove($church);
		$this->entityManager->flush();
		LogUtil::RegisterStatus($this->__("Church has been removed successfully."));
	
		//create setting var
		$setting_churches = $this->getVar('Churches');
		unset( $setting_churches[$actionid] );
		$this->setVar('Churches', $setting_churches);
		
		//create setting var
		$setting_churches = $this->getVar('New_Churches');
		unset( $setting_churches[$actionid] );
		$this->setVar('New_Churches', $setting_churches);
		
		$users = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
		foreach($users as $user)
		{
			$my_churches=$user->getChurches();
			unset( $my_churches[$actionid] );
			$uid=$user->getUid();
		
			$user->setChurches($my_churches);
			$this->entityManager->persist($user);
			$this->entityManager->flush();
		}
	
		$this->redirect(ModUtil::url($this->name, 'admin', 'Church'));
	}
	
	/**
	 * This function returns an array of all ministrants
	 */
	public function settings()
	{
		if (!SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN)) {
		    return LogUtil::registerPermissionError();
		}
	
		$form = FormUtil::newForm('Miniplan', $this);
		return $form->execute('Admin/settings.tpl', new Miniplan_Handler_Settings());
	}
	
	/**
	*show the date of Miniplans
	*/
	public function calendar()
	{
		if (SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN))
		{
			$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC', 'time' =>'ASC'));
			return $this->view
			->assign('worships', $worships)
			->fetch('Admin/Calendar.tpl');
		}
	}
	
	public function form()
	{
		if (SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN))
		{
			$forms = $this->entityManager->getRepository('Miniplan_Entity_WorshipForm')->findBy(array(),array('wfid'=>'ASC'));
			return $this->view
			->assign('forms', $forms)
			->fetch('Admin/Form.tpl');
		}
	}
	
	public function Delete_Form()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$actionid = FormUtil::getPassedValue('id',null,'GET');
		if( $actionid=="")
			return LogUtil::RegisterError($this->__("ID is missing."), null, ModUtil::url($this->name, 'admin','Calendar'));
		$form = $this->entityManager->find('Miniplan_Entity_WorshipForm', $actionid);
		$this->entityManager->remove($form);
		$this->entityManager->flush();
					
		LogUtil::RegisterStatus($this->__("Form has been removed successfully."));
	
		$this->redirect(ModUtil::url($this->name, 'admin', 'form'));
	}
	
	public function Edit_Form()
	{
		if (!SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN)) {
		    return LogUtil::registerPermissionError();
		}
		$form = FormUtil::newForm('Miniplan', $this);
		return $form->execute('Admin/FormEdit.tpl', new Miniplan_Handler_FormEdit());
	}
	
	public function SaveForm()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		//get inputs
		$action = FormUtil::getPassedValue('action',null,'POST');
		$cid = FormUtil::getPassedValue('inChurch',null,'POST');
		$time = FormUtil::getPassedValue('time',null,'POST');
		$minis = FormUtil::getPassedValue('minis',null,'POST');
		$info = FormUtil::getPassedValue('info',null,'POST');
		$date_num = FormUtil::getPassedValue('indate_num',null,'POST');
		
		if($action == "add")
		{
			//for every date
			for($i = 0;$i<=$date_num;$i++)
			{
				$date = FormUtil::getPassedValue('indate'.$i,null,'POST');
				if($date!="")
				{
					$worship = new Miniplan_Entity_Worship();
					$worship->setCid($cid);
					$worship->setTime($time);
					$worship->setMinis_requested($minis);
					$worship->setInfo($info);
					$worship->setDate($date);
					$this->entityManager->persist($worship);
					$this->entityManager->flush();
					$Wid = $worship->getWid();
					
					$users = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
					foreach($users as $user)
					{
						$userstate = 0;
						if($user->getInactive() == 2)
							$userstate = 1;
						
						$churches = $user->getChurches();
						if(($churches[$cid] == 2)||($churches[$cid] == 3))
							$userstate = $churches[$cid]-1;
						
						$userdate=$date;
						$userdate = date_format($date, 'l');
						$days = $user->getDays();
						if((($days[$userdate] == 2)||($days[$userdate] == 3))&&($churches[$cid] != 2))
							$userstate = $days[$userdate]-1;
						$my_calendar = $user->getMy_calendar();
						$my_calendar[$Wid] = $userstate;
						$user->setMy_calendar($my_calendar);
						$this->entityManager->persist($user);
						$this->entityManager->flush();
					}
					LogUtil::RegisterStatus($this->__("Worship has been added successfully."));
				}
			}
		}
		$this->redirect(ModUtil::url($this->name, 'admin', 'calendar'));
	}
	
	public function Create_Worship()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$worship = FormUtil::newForm('Miniplan', $this);
		return $worship->execute('Admin/New_Worship.tpl', new Miniplan_Handler_NewWorship());
	}
	
	public function Delete_All_Worship(){
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC', 'time' =>'ASC'));
		foreach($worships as $worship)
		{
			$this->Delete_Worship($worship->getWid());
		}
		$this->redirect(ModUtil::url($this->name, 'admin', 'calendar'));
	}
	
	public function Delete_Worship($id)
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		if($id){
			$actionid = $id;
			$returnstate = false;
		}
		else{
			$actionid = FormUtil::getPassedValue('id',null,'GET');
			$returnstate = true;
		}
		if( $actionid=="")
			return LogUtil::RegisterError($this->__("ID is missing."), null, ModUtil::url($this->name, 'admin','Calendar'));
		$worship = $this->entityManager->find('Miniplan_Entity_Worship', $actionid);
		$this->entityManager->remove($worship);
		$this->entityManager->flush();
		
		$users = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
		foreach($users as $user)
		{
			$my_calendar = $user->getMy_calendar();
			unset($my_calendar[$actionid]);
			$user->setMy_calendar($my_calendar);
			$this->entityManager->persist($user);
			$this->entityManager->flush();
		}
		
		$old_plan = $this->entityManager->getRepository('Miniplan_Entity_Plan')->findBy(array("wid"=>$actionid));
		
		foreach($old_plan as $old_einteilung)
		{
			$oldtemp = $this->entityManager->find('Miniplan_Entity_Plan', $old_einteilung->getId());
			$this->entityManager->remove($oldtemp);
			$this->entityManager->flush();
		}
		
		LogUtil::RegisterStatus($this->__("Worship has been removed successfully."));
		if($returnstate)
			$this->redirect(ModUtil::url($this->name, 'admin', 'calendar'));
	}
	
	public function Edit_Worship()
	{
		if (!SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN)) {
		    return LogUtil::registerPermissionError();
		}
		$form = FormUtil::newForm('Miniplan', $this);
		return $form->execute('Admin/worshipEdit.tpl', new Miniplan_Handler_WorshipEdit());
	}
	/**
	*show the template to input my dates
	*/
	public function my_calendar()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_COMMENT));
		$myurl = FormUtil::getPassedValue('url','my_calendar','GET');
		if(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_COMMENT))
		{
			$uid = FormUtil::getPassedValue('id',null,'GET');
			if(! $uid)
				$uid = SessionUtil::getVar('uid');
		}
		else
			$uid = SessionUtil::getVar('uid');
			
		$em = $this->getService('doctrine.entitymanager');
		$qb = $em->createQueryBuilder();
		$qb->select('p')
		   ->from('Miniplan_Entity_User', 'p')
		   ->where('p.uid = :uid')
		   ->setParameter('uid', $uid);
		$minis = $qb->getQuery()->getArrayResult();
		$mini = $minis[0];
		$user = UserUtil::getPNUser($uid);
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC', 'time' => 'ASC'));
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array(),array('cid'=>'ASC'));
		//get aministrantarray order by uid
		return $this->view
		->assign('worships', $worships)
		->assign('churches', $churches)
		->assign('user',$user)
		->assign('mini',$mini)
		->assign('url',$myurl)
		->fetch('Admin/myData/My_Calendar.tpl');
	}
	
	function save_myCalendar()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_COMMENT));
		$myurl = FormUtil::getPassedValue('url','my_calendar','POST');
		$url = ModUtil::url($this->name, 'admin', $myurl);
		if($myurl=='my_calendar')
		{
			$mid=FormUtil::getPassedValue('mid',null,'POST');
			$mini = $this->entityManager->find('Miniplan_Entity_User', $mid);
			$uid = $mini->getUid();
			$url = ModUtil::url($this->name, 'admin', $myurl,array("id"=>$uid));
		}
		echo $myurl;
		
		echo $url;
		$action = FormUtil::getPassedValue('action',null,'POST');
		if($action == 'add')
		{
			$days_admin=array();
			$churches_admin=array();
			if(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN))
			{
				//admin
				$adminInactive = FormUtil::getPassedValue('adminInactive',null,'POST');
				if($adminInactive)
					$adminInactive=1;
				else
					$adminInactive=0;
		
				//get admin weekdays
				$adminMonday = FormUtil::getPassedValue('adminMonday',null,'POST');
				if($adminMonday)
					$days_admin['Mo']=1;
				else
					$days_admin['Mo']=0;
				$adminTuesday = FormUtil::getPassedValue('adminTuesday',null,'POST');
				if($adminTuesday)
					$days_admin['Tue']=1;
				else
					$days_admin['Tue']=0;
				$adminWednesday = FormUtil::getPassedValue('adminWednesday',null,'POST');
				if($adminWednesday)
					$days_admin['Wed']=1;
				else
					$days_admin['Wed']=0;
				$adminTuersday = FormUtil::getPassedValue('adminThursday',null,'POST');
				if($adminThursday)
					$days_admin['Thur']=1;
				else
					$days_admin['Thur']=0;
				$adminFriday = FormUtil::getPassedValue('adminFriday',null,'POST');
				if($adminFriday)
					$days_admin['Fir']=1;
				else
					$days_admin['Fri']=0;
				$adminSaturday = FormUtil::getPassedValue('adminSaturday',null,'POST');
				if($adminSaturday)
					$days_admin['Sat']=1;
				else
					$days_admin['Sat']=0;
				$adminSunday = FormUtil::getPassedValue('adminSunday',null,'POST');
				if($adminSunday)
					$days_admin['Sun']=1;
				else
					$days_admin['Sun']=0;
		
				//get admin churches
				$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array());
				foreach($churches as $church)
				{
					$cid=$church->getCid();
					$inputchurch = FormUtil::getPassedValue('adminchurch_'.$cid,null,'POST');
					if($inputchurch)
						$churches_admin[$cid]=1;
					else
						$churches_admin[$cid]=0;
				}
			}
			
			//user
				$Inactive = FormUtil::getPassedValue('Inactive',null,'POST');
				if($Inactive)
					$adminInactive=2;
				else if($adminInactive)
					$adminInactive=1;
				else
					$adminInactive=0;
		
				//get  weekdays
				$days_state=array();
				$day = FormUtil::getPassedValue('Mon_state',null,'POST');
				if($day)
					$days_admin['Mo'] = $day;
				$day = FormUtil::getPassedValue('Tue_state',null,'POST');
				if($day)
					$days_admin['Tue'] = $day; 
				$day = FormUtil::getPassedValue('Wed_state',null,'POST');
				if($day)
					$days_admin['Wed'] = $day;
				$day = FormUtil::getPassedValue('Thur_state',null,'POST');
				if($day)
					$days_admin['Thur'] = $day;
				$day = FormUtil::getPassedValue('Fri_state',null,'POST');
				if($day)
					$days_admin['Fri'] = $day;
				$day = FormUtil::getPassedValue('Sat_state',null,'POST');
				if($day)
					$days_admin['Sat'] = $day;
				$day = FormUtil::getPassedValue('Sun_state',null,'POST');
				if($day)
					$days_admin['Sun'] = $day;
		
				//get  churches
				$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array());
				$churches_=array();
				foreach($churches as $church)
				{
					$cid=$church->getCid();
					 $church = FormUtil::getPassedValue('church_state'.$cid,null,'POST');
					 if($church)
					 	$churches_admin[$cid] = $church;
				}
				
				$my_calendar = array();
				$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array());
				foreach($worships as $worship)
				{
					$wid = $worship->getWid();
					$my_calendar[$wid] = FormUtil::getPassedValue('worship_state_'.$wid,0,'POST');
					echo "a".$wid."#".$my_calendar[$wid]."<br />>";
					/*if($my_calendar[$wid]=""){
						$my_calendar[$wid]=0;echo "b";}*/
				}
				print_r($my_calendar);
				//get Partner data
				$pid=FormUtil::getPassedValue('pid',null,'POST');
				$ppriority=FormUtil::getPassedValue('ppriority',null,'POST');
				//get minidata
				$mid=FormUtil::getPassedValue('mid',null,'POST');
				$mini = $this->entityManager->find('Miniplan_Entity_User', $mid);
				$nicname=FormUtil::getPassedValue('nicname',null,'POST');
				$mini->setMy_calendar($my_calendar);
				$mini->setNicname($nicname);
				$mini->setChurches($churches_admin);
				$mini->setDays($days_admin);
				$mini->setInactive($adminInactive);
				$mini->setPid($pid);
				$mini->setPpriority($ppriority);
				$this->entityManager->persist($mini);
        		$this->entityManager->flush();
        		LogUtil::registerStatus($this->__('Save changes!'));
		}
		$this->redirect($url);
	}
	
	public function my_address()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_COMMENT));
		
		if(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_COMMENT))
		{
			$uid = FormUtil::getPassedValue('id',null,'GET');
			if(! $uid)
				$uid = SessionUtil::getVar('uid');
		}
		else
			$uid = SessionUtil::getVar('uid');
		
		$form = FormUtil::newForm('Miniplan', $this);
		return $form->execute('Admin/myData/My_Address.tpl', new Miniplan_Handler_Address());
	}
	
	private function cmp($a, $b)
	{
		if (strcmp( $a['__ATTRIBUTES__'][realname], $b['__ATTRIBUTES__'][realname]) == 0){
			if (strcmp( $a['__ATTRIBUTES__'][first_name], $b['__ATTRIBUTES__'][first_name]) == 0)
				return 0;
			return (strcmp( $a['__ATTRIBUTES__'][first_name], $b['__ATTRIBUTES__'][first_name]) < 0) ? -1 : 1;
		}
		else
			return (strcmp( $a['__ATTRIBUTES__'][realname], $b['__ATTRIBUTES__'][realname]) < 0)?-1:1;
	}
	
	public function address()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));

		$users = UserUtil::getUsers();
		$ministrants = array();
		foreach($users as $user)
		{
			if($user['__ATTRIBUTES__'][ministrant_prop]
				&&$user['__ATTRIBUTES__'][ministrant_state_prop] == "1")
				{
					if(!isset($user['__ATTRIBUTES__'][realname]))
						$user['__ATTRIBUTES__'][realname] = "";
					if(!isset($user['__ATTRIBUTES__'][first_name]))
						$user['__ATTRIBUTES__'][first_name] = "";
					$ministrants[] = $user;
				}
		}
		
		@usort($ministrants, array($this, "cmp"));
		
		return $this->view
		->assign('ministrants', $ministrants)
		->fetch('Admin/Address.tpl');
	}
	
	public function printAddress(){
	
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));

		$users = UserUtil::getUsers();
		$ministrants = array();
		foreach($users as $user)
		{
			if($user['__ATTRIBUTES__'][ministrant_prop]
				&&$user['__ATTRIBUTES__'][ministrant_state_prop] == "1")
				{
					$ministrants[] = $user;
				}
		}
		
		@usort($ministrants, array($this, "cmp"));
		
		Miniplan_Controller_Print::printAddressXLS($ministrants);
		return 0;
	}
	
	public function passed_Dates()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array(),array('cid'=>'ASC'));
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array(),array('mid'=>'ASC'));
		$uids = array();
		$partnername = array();
		foreach($minis as $mini)
		{
			$uids[$mini->getMid()] = $mini->getUid();
			$partnername[$mini->getMid()] = $mini->getNicname();
		}
		$users = UserUtil::getUsers();
		return $this->view
		->assign('worships', $worships)
		->assign('churches', $churches)
		->assign('minis',$minis)
		->assign('uids',$uids)
		->assign('partnername',$partnername)
		->assign('users',$users)
		->fetch('Admin/passedDates.tpl');
	}
	
	public function printData(){
	
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array(),array('cid'=>'ASC'));
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array(),array('mid'=>'ASC'));
		$uids = array();
		foreach($minis as $mini)
		{
			$uids[$mini->getMid()] = $mini->getUid();
		}
		$users = UserUtil::getUsers();
		$data = array('worships' => $worships, 
				'churches'=> $churches,
				'minis' => $minis,
				'uids' => $uids,
				'users' => $users,
				'type' => 'xls',
				);
		Miniplan_Controller_Print::printDataXLS($data);
		return 0;
	}
	
	public function quick_input()
	{
		if (SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN))
		{
			$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
			$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array(),array('cid'=>'ASC'));
			$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array(),array('mid'=>'ASC'));
			$users = UserUtil::getUsers();
			return $this->view
			->assign('worships', $worships)
			->assign('churches', $churches)
			->assign('minis',$minis)
			->assign('users',$users)
			->fetch('Admin/quick_input.tpl');
		}
	}
	
	public function quickinput_save()
	{
		die;
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array(),array('mid'=>'ASC'));
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		
		$action = FormUtil::getPassedValue('action',null,'POST');
		echo $action;die;
		if($action == 'add')
		{
			die;//load input
			foreach($minis as $index=> $mini)
			{
				//save partner properties
				$mid = $mini -> getMid();
				$mymini = $this->entityManager->find('Miniplan_Entity_User', $mid);
				$pid = FormUtil::getPassedValue('pid'.$mid,null,'POST');
				$mymini->setPid($pid);
				$ppriority = FormUtil::getPassedValue('ppriority'.$mid,null,'POST');
				$mymini->setPpriority($ppriority);
				
				//save worship properties
				$calendar = $mini->getMy_calendar();
				foreach($worships as $worship)
				{
					$wid = $worship->getWid();
					$worship_state = FormUtil::getPassedValue('state_'.$wid.'_'.$mid,null,'POST');
					echo $worship_state;
					$calendar[$wid] = $worship_state;
				}
				print_r($calendar);
				$mymini->setMy_calendar($calendar);
				die;
				$this->entityManager->persist($mymini);
        		$this->entityManager->flush();
			}
			
        	LogUtil::registerStatus($this->__('Save changes!'));
		}
		$url = ModUtil::url($this->name, 'admin', 'quick_input');
		echo "hallo";
		die;
		$this->redirect($url);
	}
	
	public function createmanager()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$routine = FormUtil::getPassedValue('Routineselector',null,'POST');
		$returns = array();
		switch($routine)
		{
			case 1:
				$returns = ModUtil::apiFunc('Miniplan', 'Create', 'Vuong');
				break;
			case 2:
				$this->redirect( ModUtil::url($this->name, 'admin', 'createManuell'));
				break;
			case 3:
				$this->redirect( ModUtil::url($this->name, 'admin', 'printOdt'));
				break;
			case 4:
				$this->redirect( ModUtil::url($this->name, 'admin', 'printData'));
				break;
			default:
				LogUtil::RegisterError($this->__("Please enter a vaild routine."));
				$this->redirect(ModUtil::url($this->name, 'admin', 'main'));
		}
		
		if(!$returns["success"]){
			echo "Fehler!!!";
			$returns["error_warnings"] = nl2br($returns["error_warnings"]);
			return $this->view
			->assign("message", $returns["error_warnings"])
			->fetch('Admin/Plan_CreatedError.tpl');
		}
		
		//berechnen der Statistik
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array());
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		$minis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array(),array('mid'=>'ASC'));
		
		foreach ($minis as $mini)
		{
			$minis_array[$mini->getMid()] = new Miniplan_Creator_Mini($mini, 0, $churches);
		}		
		$statistik = new Miniplan_Creator_Statistik($worships, $minis, $churches);
		$statistik->setMinisObj($minis_array);
		$statistik->calculateStatistik();
		
		//print_r($MiniplanData);
		return $this->view
			->assign("statistik", $statistik)
			->fetch('Admin/Plan_Created.tpl');
	}
	
	/*the function printWord() is not very beautiful. Because of the bad idea, sending a doc via HTML
	* I comment the function out.*/
	/*public function printWord()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$MiniplanData = SessionUtil::getVar("Generated_Miniplan");
		$plan = $MiniplanData['plan'];
		
		ob_end_clean();
		header("Content-type: application/vnd.ms-word");
		header("Content-Disposition: attachment;Filename=Miniplan.doc");

		echo "<html>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=Windows-1252\">";
		echo "<body>";
		
		$output = "<table>";
		$counter = 0;
		foreach($plan as $item)
		{
			$output.="<tr>";
			if($counter && ($plan[$counter-1]["date"]==$item["date"]))
				;//$output.="<td></td>";
			else
				{
					$mycounter = $counter;
					$row = 0;
					$info = "";
					while($plan[$counter]["date"] == $plan[$mycounter]["date"])
					{
						$row++;
						$mycounter++;
						$info.= $plan[$mycounter]["info"];
					}
					$output.="<td rowspan=\"".$row."\" style=\" vertical-align:top;\">".$item["date"]."<br/>".$info."</td>";
				}
			$output.="<td style=\"text-align:right;\">".$item["time"]."</td>";
			$output.="<td>".$item["cname"]."</td>";
			
			$output.="<td>";
			foreach($item["minis"] as $mini)
			{
				$minidata = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array('mid'=>$mini));
				$thismini = UserUtil::getPNUser($minidata[0]->getUid());
				$output.=$thismini["uname"].", ";
			}
			$output = substr($output, 0, -2);
			$output.="</td>";
			$output.="</tr>";
			$counter++;
		}
		$output .= "</table>";
		echo $output;
		echo "</body>";
		echo "</html>";
		system::shutdown();
	}*/
	
	public function printOdt()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		
		include 'phpodt-0.3/phpodt.php';
		
		//zwischenspeicher für Gottesdienst infos
		$info = "";
		//zählt zeilennummer
		$counter = 0;
		//zählt zeilen ohne gottesdienste
		$leerzeilen = 0;
		//zeilennummer des letzten Tages
		$dateId = 0;
		
		//Ausgabe
		$output = array();
		
		//pro Gottesdienst
		foreach($worships as $item)
		{
			$temp = array();
			/**********************
			* Der folgende Abschnitt sorgt dafür, dass Informationen zu einem Gottesdienst
			* immer in der 2. Zeile des Tages gespeichert werden. Hierzu werden die Infos
			* der ersten Zeile in $info zwischengespeichert. 
			* ist die Infozeile schon vorbei, werden die Daten nachträglich eingetragen. Hierbei
			* hilft die Variable $dateId
			**************************/
			
			//kein Neuer Tag -> das Datum wurde schon mal gedruckt
			if($counter && ($worships[$counter-1]->getDate()==$item->getDate()))
			{
				//ist gerade Infozeile?
				if($counter == ($dateId+1))
				{
					//schreibe Information
					$temp[] = $item->getInfo();
					//neue Informationen für diesen Gottesdienst oder alte vom ersten Gottesdienst des Tages?
					if($item->getInfo()!="" || $info != "")
						$temp[0] .= $info;
				}
				else
				{
					$temp[] = "";
					
					//zuspät für info. schreibe wenn es was gibt, die info in die Infozeile vorher
					if($item->getInfo() != "")
						$output[$dateId+1][0] = $item->getInfo();
				}
				
				$info = "";
			}
			//neuer Tag / bzw. erster Gottesdienst des Tages
			else
			{
				//infozeile ohne 2. Gottesdienst?
				//erstelle zwischenzeile
				if($info != "")
				{
					$temp[] = $info;
					$info = "";
					$temp[] = "";
					$temp[] = "";
					$temp[] = "";
					$temp[] = "";
					$output[] = $temp;
					unset($temp);
					//neue Zeile
					$temp = array();
					//$counter++;
				}
				//neuer Gottesdienstag
				$temp[] = $item->getDateFormattedout();
				
				$dateId = $counter + $leerzeilen;
				//zwischenspeicher für das Infofeld
				if($item->getInfo() != "")
					$info = $item->getInfo();
				
			}
			//$pos = strpos($item->getTime(), ":");
			/*if($pos == 1)
				$item->getTime() = "".$item->getTime();*/
			$temp[] = $item->getCnic();
			$temp[] = "";
			$temp[]= $item->getTimeFormatted();
			
			//Ausgabe der Ministranten pro Gottesdienst
			$temp_minis = "";
			
			$minis = $this->entityManager->getRepository('Miniplan_Entity_Plan')->findBy(array("wid"=>$item->getWid()));
			print_r($minis);
			foreach($minis as $mini)
			{
				echo $mini;
				$temp_minis.= $mini->getNicname().", ";//$->getUsername().", ";
			}
			//verhinder ", " nach dem letzten Namen
			$temp_minis = substr($temp_minis, 0, -2);
			$temp[] = $temp_minis;
			$output[] = $temp;
			$counter++;
		}
		
		$odt = ODT::getInstance();
		
		$textStyle = new TextStyle('textstyle1');   //You can the name, but it's better to specify one
		$textStyle->setColor('#000000');            //Change the color of the text to red. The color must be in hexa  notation
		$textStyle->setBold();                      //Make the text bold
		$textStyle->setFontSize(15);                //Change the font size
		
		$pStyle = new ParagraphStyle('PStyle');
		$pStyle->setTextAlign(StyleConstants::CENTER);
		
		$styledParagraph = new Paragraph($pStyle);
		$styledParagraph->addText('Ministrantenplan am '.date("d.m.y")." um ".date("H:i")." Uhr", $textStyle);
		
		$emptyParagraph = new Paragraph($pStyle);
		$emptyParagraph->addText('', $textStyle);
		
		$odt = ODT::getInstance();
		
		$table = new Table('table1');
		$table->createColumns(5);
		
		$tableStyle = new TableStyle($table->getTableName());
		$tableStyle->setAlignment(StyleConstants::LEFT);
		$tableStyle->setWidth('17cm');
		$tableStyle->setHorizontalMargin('0.1cm', '0.1cm');
		$tableStyle->setVerticalMargin('0.1cm', '0.1cm');

		$columnStyle0 = $table->getColumnStyle(0);
		$columnStyle0->setWidth('3cm');
		$columnStyle1 = $table->getColumnStyle(1);
		$columnStyle1->setWidth('1.3cm');
		$columnStyle3 = $table->getColumnStyle(2);
		$columnStyle3->setWidth('0.1cm');
		$columnStyle4 = $table->getColumnStyle(3);
		$columnStyle4->setWidth('1.2cm');
		
		$table->setStyle($tableStyle);
		
		$rows = $output;
		
		$table->addRows($rows);
		
		$file = tempnam("tmp", "zip");
		$odt->output($file);
		
		ob_end_clean();
		header('Content-Type: application/vnd.oasis.opendocument.text');
		header('Content-Length: ' . filesize($file));
		header('Content-Disposition: attachment; filename="Miniplan'.date("Ymd_Hi").'.odt"');
		readfile($file);
		unlink($file);
		system::shutdown();
	}
	
	public function createdPlan(){
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		
		
		//zwischenspeicher für Gottesdienst infos
		$info = "";
		//zählt zeilennummer
		$counter = 0;
		//zählt zeilen ohne gottesdienste
		$leerzeilen = 0;
		//zeilennummer des letzten Tages
		$dateId = 0;
		
		//Ausgabe
		$output = array();
		
		//pro Gottesdienst
		foreach($worships as $item)
		{
			$temp = array();
			/**********************
			* Der folgende Abschnitt sorgt dafür, dass Informationen zu einem Gottesdienst
			* immer in der 2. Zeile des Tages gespeichert werden. Hierzu werden die Infos
			* der ersten Zeile in $info zwischengespeichert. 
			* ist die Infozeile schon vorbei, werden die Daten nachträglich eingetragen. Hierbei
			* hilft die Variable $dateId
			**************************/
			
			//kein Neuer Tag -> das Datum wurde schon mal gedruckt
			if($counter && ($worships[$counter-1]->getDate()==$item->getDate()))
			{
				//ist gerade Infozeile?
				if($counter == ($dateId+1))
				{
					//schreibe Information
					$temp[] = $item->getInfo();
					//neue Informationen für diesen Gottesdienst oder alte vom ersten Gottesdienst des Tages?
					if($item->getInfo()!="" || $info != "")
						$temp[0] .= $info;
				}
				else
				{
					$temp[] = "";
					
					//zuspät für info. schreibe wenn es was gibt, die info in die Infozeile vorher
					if($item->getInfo() != "")
						$output[$dateId+1][0] = $item->getInfo();
				}
				
				$info = "";
			}
			//neuer Tag / bzw. erster Gottesdienst des Tages
			else
			{
				//infozeile ohne 2. Gottesdienst?
				//erstelle zwischenzeile
				if($info != "")
				{
					$temp[] = $info;
					$info = "";
					$temp[] = "";
					$temp[] = "";
					$temp[] = "";
					$temp[] = "";
					$output[] = $temp;
					unset($temp);
					//neue Zeile
					$temp = array();
					//$counter++;
				}
				//neuer Gottesdienstag
				$temp[] = $item->getDateFormattedout();
				
				$dateId = $counter + $leerzeilen;
				//zwischenspeicher für das Infofeld
				if($item->getInfo() != "")
					$info = $item->getInfo();
				
			}
			//$pos = strpos($item->getTime(), ":");
			/*if($pos == 1)
				$item->getTime() = "".$item->getTime();*/
			$temp[] = $item->getCnic();
			$temp[] = "";
			$temp[]= $item->getTimeFormatted();
			
			//Ausgabe der Ministranten pro Gottesdienst
			$temp_minis = "";
			
			$minis = $this->entityManager->getRepository('Miniplan_Entity_Plan')->findBy(array("wid"=>$item->getWid()));
			foreach($minis as $mini)
			{
				$temp_minis.= $mini->getNicname().", ";//$->getUsername().", ";
			}
			//verhinder ", " nach dem letzten Namen
			$temp_minis = substr($temp_minis, 0, -2);
			$temp[] = $temp_minis;
			$output[] = $temp;
			$counter++;
		}
		
		return $this->view
			->assign("data", $output)
			->fetch('Admin/ViewPlan.tpl');
	}
	
	public function createManuell(){
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		//LogUtil::registerError($this->__('This function is not implementated!'));
		
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		
		$worships = $this->entityManager->getRepository('Miniplan_Entity_Worship')->findBy(array(),array('date'=>'ASC','time' =>'ASC'));
		
		
		//zwischenspeicher für Gottesdienst infos
		$info = "";
		//zählt zeilennummer
		$counter = 0;
		//zählt zeilen ohne gottesdienste
		$leerzeilen = 0;
		//zeilennummer des letzten Tages
		$dateId = 0;
		
		//Ausgabe
		$output = array();
		
		//pro Gottesdienst
		$partnerWarning = array();
		foreach($worships as $item)
		{
			$temp = array();
			$data = array();
			/**********************
			* Der folgende Abschnitt sorgt dafür, dass Informationen zu einem Gottesdienst
			* immer in der 2. Zeile des Tages gespeichert werden. Hierzu werden die Infos
			* der ersten Zeile in $info zwischengespeichert. 
			* ist die Infozeile schon vorbei, werden die Daten nachträglich eingetragen. Hierbei
			* hilft die Variable $dateId
			**************************/
			
			//kein Neuer Tag -> das Datum wurde schon mal gedruckt
			if($counter && ($worships[$counter-1]->getDate()==$item->getDate()))
			{
				//ist gerade Infozeile?
				if($counter == ($dateId+1))
				{
					//schreibe Information
					$data[] = $item->getInfo();
					//neue Informationen für diesen Gottesdienst oder alte vom ersten Gottesdienst des Tages?
					if($item->getInfo()!="" || $info != "")
						$data[0] .= $info;
				}
				else
				{
					$data[] = "";
					
					//zuspät für info. schreibe wenn es was gibt, die info in die Infozeile vorher
					if($item->getInfo() != "")
						$output[$dateId+1]['data'][0] = $item->getInfo();
				}
				
				$info = "";
			}
			//neuer Tag / bzw. erster Gottesdienst des Tages
			else
			{
				//infozeile ohne 2. Gottesdienst?
				//erstelle zwischenzeile
				if($info != "")
				{
					$data[] = $info;
					$info = "";
					$data[] = "";
					$data[] = "";
					$data[] = "";
					$data[] = "";
					$output[] = array('data' => $data);
					unset($data);
					//neue Zeile
					$data = array();
					//$counter++;
				}
				//neuer Gottesdienstag
				$data[] = $item->getDateFormattedout();
				
				$dateId = $counter + $leerzeilen;
				//zwischenspeicher für das Infofeld
				if($item->getInfo() != "")
					$info = $item->getInfo();
				
			}
			//$pos = strpos($item->getTime(), ":");
			/*if($pos == 1)
				$item->getTime() = "".$item->getTime();*/
			$data[] = $item->getCnic();
			$data[] = "";
			$data[]= $item->getTimeFormatted();
			
			
			
			//Ausgabe der eingeteilten Ministranten für diesen Gottesdienst
			$minis = $this->entityManager->getRepository('Miniplan_Entity_Plan')->findBy(array("wid"=>$item->getWid()));
			
			$partnerWarning[$item->getWid()] = array();
			
			//durchsuche dem Gottesdienst nach Partner
			foreach($minis as $thismini) {
				if($thismini->getPid()){
					//hat partner
					$partners = $this->entityManager->getRepository('Miniplan_Entity_Plan')->findBy(array("wid"=>$item->getWid(), "mid" => $thismini->getPid()));
					//partner fehlt
					if(!$partners){
						if($thismini->getPpriority())
							//muss, gib fehler aus
							$partnerWarning[$item->getWid()][$thismini->getMid()] = 1;
						else
							//gerne, gibt warnung aus
							$partnerWarning[$item->getWid()][$thismini->getMid()] = 2;
						}
				}
			}
			
			//füllt leere zellen auf
			if(count($minis) < $item->getMinis_requested()){
				$div = $item->getMinis_requested() - count($minis);
				for($i = 0; $i < $div ; $i ++ )
					$minis[] = "";
			}
			
			//suche alle Ministranten, die an diesem Gottesdienst eingeteilt werden können
			$allMinis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
			
			//alle Minis, die können
			//freiwillige minis
			$minisCan = array();
			$minisVoluntary = array();
			foreach($allMinis as $thisMini){
				$myCalendar = $thisMini->getMy_calendar();
				if($myCalendar[$item->getWid()] == 0)
					$minisCan[] = $thisMini->getMid();
				if($myCalendar[$item->getWid()] == 2)
					$minisVoluntary[] = $thisMini->getMid();
			}
			$temp['dividedMinis'] = $minis;
			$temp['data'] = $data;
			$temp['worship'] = $item;
			$temp['allMinis'] = $minisCan;
			$temp['voluntaryMinis'] = $minisVoluntary;
			$output[] = $temp;
			$counter++;
		}
		
		$allMinis = $this->entityManager->getRepository('Miniplan_Entity_User')->findBy(array());
		$orderdAllMinis = array();
		foreach($allMinis as $item){
			$orderdAllMinis[$item->getMid()] = $item;
		}
		$churches = $this->entityManager->getRepository('Miniplan_Entity_Church')->findBy(array());
		
		//berechnen der Statistik
		
		foreach ($allMinis as $mini)
		{
			$minis_array[$mini->getMid()] = new Miniplan_Creator_Mini($mini, 0, $churches);
		}
		$statistik = new Miniplan_Creator_Statistik($worships, $allMinis, $churches);
		$statistik->setMinisObj($minis_array);
		$statistik->calculateStatistik();
		
		$statistic = array();
		foreach($statistik->getAusgabe() as $item){
			if($item['mid']){
				$statistic[$item['mid']]['all'] = $item['gesamt'];
				foreach($churches as $church){
					$statistic[$item['mid']][$church->getCid()] = $item[$church->getCid()];
				}
			}
		}
		$durchschnitt = $statistik->getDurchschnitt();
		return $this->view
			->assign("data", $output)
			->assign("minis", $orderdAllMinis)
			->assign("partnerWarning", $partnerWarning)
			->assign("churches", $churches)
			->assign("statistic", $statistic)
			->assign("durchschnitt", $durchschnitt )
			->fetch('Admin/CreatePlanManuely.tpl');
	}
	
	public function printLog()
	{
		$this->throwForbiddenUnless(SecurityUtil::checkPermission('Miniplan::', '::', ACCESS_ADMIN));
		$MiniplanData = SessionUtil::getVar("Generated_Miniplan");
		/*
			"curches" => $churches,				//list of churches
			"worships" => $worships,			//list of worships
			"minis_array" => $minis,			//list of minis
			"mixed_minis" => $minis_rand_array,	//list of mixed minis with data of Amount of divisions
			"plan" => $plan,					//the created plan
			"error_warnings" => $error_warnings,//all error and warnings
			"allocation" => $allocation,		//Häufigkeit der Einteilung
			"varianz" => $varianz,
			"log" => $log						//logfile
		*/
		ob_end_clean();
		header('Content-Type: application/txt');
		header('Content-Disposition: attachment; filename="Miniplan'.date("Ymd_Hi").'.log"');
		
		echo "Logdatei für den Miniplan\n";
		echo "\n";
		
		echo "#Inhaltsverzeichnis\n";
		echo "Bitte navigieren Sie durch diese Logdatei mit Hilfe von Hashtags.\nHierzu öffnen Sie mit \"Strg+F\" die Suche und gaben dort das gesuchte Kapitel mit einem \"#\"davor ein.\n";
		echo "\n		#Fehler und Warnungen
		#Prozess Log
		#Kirchen
		#Gottesdienste
		#Ministranten
		#Gemischte Ministranten";
		
		echo "\n\n#Fehler und Warnungen:\n";
		echo $MiniplanData["error_warnings"];
		echo "\n\n";
		
		echo "#Prozess Log:\n";
		foreach($MiniplanData["log"]->getData() as $log)
		{
			echo $log."\n";
		}
		echo "\n\n";
		
		echo "\n\n";
		
		echo "Anhang\n";
		
		echo "#Kirchen:\n";
		print_r($MiniplanData["churches"]);
		echo "\n\n";
		
		echo "#Gottesdienste:\n";
		print_r($MiniplanData["worships"]);
		echo "\n\n";
		
		echo "#Ministranten:\n";
		print_r($MiniplanData["minis_array"]);
		echo "\n\n";
		
		echo "#gemischte Ministranten:\n";
		print_r($MiniplanData["mixed_minis"]);
		echo "\n\n";
		system::shutdown();
	}
}

