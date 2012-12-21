<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Ministrants entity class.
 *
 * Annotations define the entity mappings to database.
 *
 * @ORM\Entity
 * @ORM\Table(name="MiniPlan_Worships")
 */
class MiniPlan_Entity_Worships extends Zikula_EntityAccess
{

    /**
     * The following are annotations which define the id field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $wid;

    /**
     * The following are annotations which define the cid field.
     *
     * @ORM\Column(type="integer")
     */
    private $cid;
    
    /**
     * The following are annotations which define the birthday field.
     *
     * @ORM\Column(type="datetime")
     */
    private $date;
    
    /**
     * The following are annotations which define the ministrantsRequired field.
     *
     * @ORM\Column(type="integer")
     */
    private $ministrantsRequired;


    public function getWid()
    {
        return $this->wid;
    }
    
    public function getDate()
    {
        return $this->date;
    }
    
    public function getDateFormatted()
    {
        return $this->date->format('d.m.Y G:i');
    }
    
    public function getCid()
    {
    	return $this->cid;
    }
    
    public function getCname()
    {
    	return ModUtil::apiFunc('MiniPlan', 'Admin', 'getChurchNameById', array('id' => $this->cid));
    }

    public function getMinistrantsRequired()
    {
        return $this->ministrantsRequired;
    }

    public function setCid($cid)
    {
        $this->cid = $cid;
    }
    
    public function setDate($date)
    {
        $this->date = new \DateTime($date);
    }
    
    public function setMinistrantsRequired($ministrantsRequired)
    {
    	$this->ministrantsRequired = $ministrantsRequired;
    }
}
