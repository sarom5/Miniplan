<?php
/**
 * Installer.
 */
class Miniplan_Installer extends Zikula_AbstractInstaller
{

	/**
	 * @brief Provides an array containing default values for module variables (settings).
	 * @return array An array indexed by variable name containing the default values for those variables.
	 *
	 * @author Sascha Rösler
	 */
	protected function getDefaultModVars()
	{
		return array(
		'New_Inactive' => 0,
		'New_Monday' => 0,
		'New_Tuesday' => 0,
		'New_Wednesday' => 0,
		'New_Thursday' => 0,
		'New_Friday' => 0,
		'New_Saturday' => 0,
		'New_Sunday' => 0,
		'New_Churches' => array(),
		
		'Inactive' => 0,
		'Monday' => 0,
		'Tuesday' => 0,
		'Wednesday' =>0,
		'Thursday' => 0,
		'Friday' => 0,
		'Saturday' => 0,
		'Sunday' => 0,
		'Churches' => array(),
		'Default_Church' => 0
		);
	}

	public function getNew()
	{
		return array(
		'New_Inactive' => 0,
		'New_Monday' => 0,
		'New_Tuesday' => 0,
		'New_Wednesday' => 0,
		'New_Thursday' => 0,
		'New_Friday' => 0,
		'New_Saturday' => 0,
		'New_Sunday' => 0,
		'New_Churches' => array(),
		
		'Wednesday' =>0);
	}
	
	public function addProfileProperties(){
		ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Vorname",
					'attribute_name' => first_name,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Dein Vorname",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Name",
					'attribute_name' => name,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Dein Nachname",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Straße",
					'attribute_name' => street,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Deine Adresse",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Ort",
					'attribute_name' => place,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Dein Ort",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "PLZ",
					'attribute_name' => plz,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Dein PLZ",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Geburtstag",
					'attribute_name' => birthday,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 5,
					'listoptions'    => "",
					'note'           => "Dein Geburtstag",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Telefon",
					'attribute_name' => tel,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Deine Telefonnummer",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Handy",
					'attribute_name' => mobile,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Deine Handynummer",
				));
				ModUtil::apiFunc('Profile', 'admin', 'create', array(
					'label'          => "Eltern Handy",
					'attribute_name' => parent_mobile,
					'required'       => 0,
					'viewby'         => 2,
					'dtype'          => 1,
					'displaytype'    => 0,
					'listoptions'    => "",
					'note'           => "Die Handynummer deiner Eltern (Nur für Kinder)",
				));
	}
	/**
	 * Install the Miniplan module.
	 *
	 * This function is only ever called once during the lifetime of a particular
	 * module instance.
	 *
	 * @return boolean True on success, false otherwise.
	 */
	public function install()
	{
		$this->setVars($this->getDefaultModVars());

		// Create database tables.
		try {
			DoctrineHelper::createSchema($this->entityManager, array(
				'Miniplan_Entity_Church'
			));
		} catch (Exception $e) {
			return LogUtil::registerError($e);
		}
		try {
			DoctrineHelper::createSchema($this->entityManager, array(
				'Miniplan_Entity_User'
			));
		} catch (Exception $e) {
			return LogUtil::registerError($e);
		}
		
		try {
			DoctrineHelper::createSchema($this->entityManager, array(
				'Miniplan_Entity_Plan'
			));
		} catch (Exception $e) {
			return LogUtil::registerError($e);
		}
		
		try {
			DoctrineHelper::createSchema($this->entityManager, array(
				'Miniplan_Entity_Worship'
			));
		} catch (Exception $e) {
			return LogUtil::registerError($e);
		}
		
		try {
			DoctrineHelper::createSchema($this->entityManager, array(
				'Miniplan_Entity_WorshipForm'
			));
		} catch (Exception $e) {
			return LogUtil::registerError($e);
		}
		
		
		
		ModUtil::apiFunc('Groups', 'admin', 'create',array('name'=>ministrant_group));
		ModUtil::apiFunc('Groups', 'admin', 'create',array('name'=>oberministrant_group));
		
		//create permission for ministrant_group
    	$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => ministrant_group) );
		ModUtil::apiFunc('Permissions', 'admin', 'create',array('realm'=>0,'id'=>$gid,'component'=>'Miniplan::','instance'=>'::','level'=>ACCESS_MODERATE,'insseq'=>10));
		
		//create permission for oberministrant_group
		$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => oberministrant_group) );
		ModUtil::apiFunc('Permissions', 'admin', 'create',array('realm'=>0,'id'=>$gid,'component'=>'Miniplan::','instance'=>'::','level'=>ACCESS_ADMIN,'insseq'=>10));
		
		//configure profile
		//create two 
		$dudid = ModUtil::apiFunc('Profile', 'admin', 'create', array(
            'label'          => ministrant_prop,
            'attribute_name' => ministrant_prop,
            'required'       => 0,
            'viewby'         => 2,
            'dtype'          => 1,
            'displaytype'    => 2,
            'listoptions'    => "",
            'note'           => "Ministrant?",
        ));
        $dudid = ModUtil::apiFunc('Profile', 'admin', 'create', array(
            'label'          => ministrant_state_prop,
            'attribute_name' => ministrant_state_prop,
            'required'       => 0,
            'viewby'         => 2,
            'dtype'          => 1,
            'displaytype'    => 0,
            'listoptions'    => "",
            'note'           => "Ministrantenstatus",
        ));
        $this->addProfileProperties();
		return true;
	}


	/**
	 * Upgrade the Miniplan module from an old version
	 *
	 * This function must consider all the released versions of the module!
	 * If the upgrade fails at some point, it returns the last upgraded version.
	 *
	 * @param  string $oldVersion   version number string to upgrade from
	 *
	 * @return mixed  true on success, last valid version string or false if fails
	 */
	public function upgrade($oldversion)
	{
		switch($oldversion)
		{
			case '0.0.3':
			case '0.0.4':
			//sar
			try {
				DoctrineHelper::updateSchema($this->entityManager, array('Miniplan_Entity_User'));
				DoctrineHelper::updateSchema($this->entityManager, array('Miniplan_Entity_Church'));
				} catch (Exception $e) {
				return LogUtil::registerError($e);
			}
			case "0.0.5":
			try {
				DoctrineHelper::updateSchema($this->entityManager, array('Miniplan_Entity_User'));
				} catch (Exception $e) {
				return LogUtil::registerError($e);
			}
			case "0.0.6":
				try {
					DoctrineHelper::createSchema($this->entityManager, array(
						'Miniplan_Entity_Plan'
					));
				} catch (Exception $e) {
					return LogUtil::registerError($e);
				}
			case "0.0.7":
				$this->addProfileProperties();
			case "0.0.8":

			default:
				break;
		}
		$this->setVars($this->getNew());
		return true;
	}


	/**
	 * Uninstall the module.
	 *
	 * This function is only ever called once during the lifetime of a particular
	 * module instance.
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function uninstall()
	{
		//group:
    	//get groupid
    	$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => ministrant_group) );
    	//delete group
    	ModUtil::apiFunc('Groups', 'admin', 'delete',array('gid'=>$gid));
    	
    	//get groupid
    	$gid = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname', array('name' => oberministrant_group) );
    	//delete group
    	ModUtil::apiFunc('Groups', 'admin', 'delete',array('gid'=>$gid));
		
		//userproperties:
		//ministrant
		$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>ministrant_prop));
    	echo $prop['prop_id'];
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	
    	//set ministrant for userregistry
    	$allduds = ModUtil::getVar('Profile','dudregshow');
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>ministrant_prop));
    	if(!(in_array($prop['prop_id'],$allduds)))
    	{
    		$allduds[] = $prop['prop_id'];
    		ModUtil::setVar('Profile','dudregshow',$allduds);
    	}
    	
    	//ministrant_state
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>ministrant_state_prop));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	
    	//Vorname
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>first_name));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Nachname
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>name));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Adresse
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>street));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Ort
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>place));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//PLZ
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>plz));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Geburtstag
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>birthday));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Handynummer
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>mobile));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	//Eltern Handy
    	$prop = ModUtil::apiFunc('Profile','user','get',array('proplabel'=>parent_mobile));
    	ModUtil::apiFunc('Profile','admin','delete',array('dudid'=>$prop['prop_id']));
    	
		// Drop database tables
		try {
            DoctrineHelper::dropSchema($this->entityManager, array(
                'Miniplan_Entity_Church'
            ));
        } catch (Exception $e) {
            echo $e;
            System::shutdown();
            return true;
        } 
		DoctrineHelper::dropSchema($this->entityManager, array(
			'Miniplan_Entity_User'
		));
		
		DoctrineHelper::dropSchema($this->entityManager, array(
			'Miniplan_Entity_Plan'
		));
		
		DoctrineHelper::dropSchema($this->entityManager, array(
			'Miniplan_Entity_Worship'
		));
		$this->delVars();
		
		DoctrineHelper::dropSchema($this->entityManager, array(
			'Miniplan_Entity_WorshipForm'
		));
		
		return true;
	}

}
