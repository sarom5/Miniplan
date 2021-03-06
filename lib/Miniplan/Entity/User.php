<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Ministrants entity class.
 *
 * Annotations define the entity mappings to database.
 *
 * @ORM\Entity
 * @ORM\Table(name="Miniplan_User")
 */
class Miniplan_Entity_User extends Zikula_EntityAccess
{

    /**
     * The following are annotations which define the id field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $mid;

    /**
     * The following are annotations which define the user id field.
     *
     * @ORM\Column(type="integer")
     */
    private $uid;
    
    /**
     * The following are annotations which define the field for additional informations.
     *
     * @ORM\Column(type="string",  length="100")
     */
    private $nicname;
    
     /**
     * The following are annotations which define the my_calendar field.
     *
     * @ORM\Column(type="array")
     */
    private $my_calendar;
    
    /**
     * The following are annotations which define the churches field.
     *
     * @ORM\Column(type="array")
     */
    private $churches;
    
     /**
     * The following are annotations which define the days field.
     *
     * @ORM\Column(type="array")
     */
    private $days;
    
    /**
     * The following are annotations which define the inactive field.
     *
     * @ORM\Column(type="integer")
     */
    private $inactive;
    
    /**
     * The following are annotations which define the inactive field.
     *
     * @ORM\Column(type="integer")
     */
    private $edited;
    
     /**
     * The following are annotations which define the partnerid field.
     *
     * @ORM\Column(type="integer")
     */
    private $pid;
    
    /**
     * The following are annotations which define the partnerpriority field.
     *
     * @ORM\Column(type="integer")
     */
    private $ppriority;
    
     /**
     * The following are annotations which define the field for additional informations.
     *
     * @ORM\Column(type="array")
     */
    private $info;
    
    public function getMid()
    {
        return $this->mid;
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    public function setUid($uid)
    {
        $this->uid = $uid;
    }
    
    public function getMy_calendar()
    {
        return $this->my_calendar;
    }

    public function setMy_calendar($my_calendar)
    {
        $this->my_calendar = $my_calendar;
    }
    
    public function getEinteilungsIndex(){
    	//liefert die Anzahl an Gottesdiensten zurück, an denen der Mini kann
    	$temp = array_keys($this->my_calendar, 1);
    	return count($this->my_calendar) - count($temp);
    }
    
    
    public function getChurches()
    {
    	return $this->churches;
    }
    
    public function setChurches($churches)
    {
    	$this->churches = $churches;
    }
    
    public function getDays()
    {
    	return $this->days;
    }
    
    public function setDays($days)
    {
    	$this->days = $days;
    }
        
    public function getInfo()
    {
    	return $this->info;
    }
    
    public function setInfo($info)
    {
    	$this->info = $info;
    }
         
    public function getInactive()
    {
    	return $this->inactive;
    }
    
    public function setInactive($inactive)
    {
    	$this->inactive = $inactive;
    }
    
    public function getEdited()
    {
    	return $this->edited;
    }
    
    public function setEdited($edited)
    {
    	$this->edited = $edited;
    }
    
    public function getPid()
    {
    	return $this->pid;
    }
    
    public function getPnic()
    {
    	return ModUtil::apiFunc('Miniplan', 'Admin', 'getNicById', array('id' => $this->pid));
    }
    
    public function setPid($pid)
    {
    	$this->pid = $pid;
    }
    
    public function getPpriority()
    {
    	return $this->ppriority;
    }
    
    public function setPpriority($ppriority)
    {
    	$this->ppriority = $ppriority;
    }
    
    public function getNicname()
    {
    	return $this->nicname;
    }
    
    public function setNicname($nicname)
    {
    	$this->nicname = $nicname;
    }
}
