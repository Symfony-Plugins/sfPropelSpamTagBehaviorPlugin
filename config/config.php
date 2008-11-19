<?php

sfPropelBehavior::registerHooks('spam_tag', 
  array(
    'Peer:doSelectRS' => array( 
      'sfPropelSpamTagBehavior', 'updateCriteria'
    ),
    'Peer:doSelectJoin' => array(
      'sfPropelSpamTagBehavior', 'updateCriteria'
    ),
    'Peer:doSelectJoinAll' => array( 
      'sfPropelSpamTagBehavior', 'updateCriteria'
    ),    
    'Peer:doSelectJoinAllExcept' => array( 
      'sfPropelSpamTagBehavior', 'updateCriteria'
    )
  )
);

sfPropelBehavior::registerMethods('spam_tag',  array(
  array ('sfPropelSpamTagBehavior', 'tagAsSpam'),
  array ('sfPropelSpamTagBehavior', 'tagAsSafe'),
  array ('sfPropelSpamTagBehavior', 'getSpamTag'),
  array ('sfPropelSpamTagBehavior', 'setSpamTag')
));

