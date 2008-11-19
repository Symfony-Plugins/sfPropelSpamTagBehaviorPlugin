<?php

class sfPropelSpamTagBehavior
{
  const TAGGED_SAFE      = 0;
  const NOT_CHECKED      = 1;
  const TAGGED_AUTO_SPAM = 2;
  const TAGGED_SPAM      = 3;
  
  static protected $isActivated = true;

  public function updateCriteria($class , $myCriteria, $con = null)
  {
    $columnName = sfConfig::get('propel_behavior_spam_tag_'.$class.'_column', 'moderation_status');

    if (self::$isActivated)
    {
      $myCriteria->add(
        call_user_func(array($class, 'translateFieldName'), $columnName, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_COLNAME),
        sfConfig::get('app_sfPropelSpamTagBehavior_display_treshold', self::NOT_CHECKED),
        Criteria::LESS_EQUAL
      );
    }
  }

  public function tagAsSpam($object, $type = 'manual')
  {
    self::setSpamTag(
      $object, 
      ($type == 'manual') ? self::TAGGED_SPAM : self::TAGGED_AUTO_SPAM
    );
    
    return $object->save();
  }

  public function tagAsSafe($object)
  {
    self::setSpamTag($object, self::TAGGED_SAFE);
    
    return $object->save();
  }
  
  public function getSpamTag($object)
  {
    return $object->getByName(sfConfig::get('propel_behavior_spam_tag_'.get_class($object).'_column', 'moderation_status'), BasePeer::TYPE_FIELDNAME);
  }

  public function setSpamTag($object, $value)
  {
    return $object->setByName(
      sfConfig::get('propel_behavior_spam_tag_'.get_class($object).'_column', 'moderation_status'),
      $value,
      BasePeer::TYPE_FIELDNAME
    );
  }
  
  static public function enable()
  {
    self::$isActivated = true; 
  }

  static public function disable()
  {
    self::$isActivated = false; 
  }

}
