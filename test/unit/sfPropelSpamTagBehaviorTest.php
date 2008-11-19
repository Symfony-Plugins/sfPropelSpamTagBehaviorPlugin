<?php
/*
 * This file is part of the sfPropelSpamTagBehavior package.
 * 
 * (c) 2007 Francois Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit tests for the sfPropelSpamTageBehavior plugin.
 *
 * Despite running unit tests, we use the functional tests bootstrap to take advantage of propel
 * classes autoloading...
 * 
 * In order to run the tests in your context, you have to copy this file in a symfony test directory
 * and configure it appropriately (see the "configuration" section at the beginning of the file)
 *  
 * @author   Francois Zaninotto <francois.zaninotto@symfony-project.com>
 */

// configuration
// Autofind the first available app environment
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
$apps_dir = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// -- path to the symfony project where the plugin resides
$sf_path = dirname(__FILE__).'/../../../..';
 
// bootstrap
include($sf_path . '/test/bootstrap/functional.php');

// -- the model class the tests should use
$test_class = sfConfig::get('app_sfPropelSpamTagBehavior_test_class', 'Article');
$test_field = sfConfig::get('app_sfPropelSpamTagBehavior_test_field', 'moderation_status');

// create a new test browser
$browser = new sfTestBrowser();
$browser->initialize();

// initialize database manager
$databaseManager = new sfDatabaseManager();
$databaseManager->initialize();

$con = Propel::getConnection();

$test_peer_class = $test_class.'Peer';

// cleanup database
call_user_func(array($test_peer_class, 'doDeleteAll'));

// register behavior on test object
sfPropelBehavior::add($test_class, array('spam_tag' => array(
  'column' => $test_field,
)));




// Now we can start to test
$t = new lime_test(25, new lime_output_color());

$t->diag('new methods');
$methods = array(
  'tagAsSpam',
  'tagAsSafe',
  'getSpamTag',
  'setSpamTag'
);
foreach ($methods as $method)
{
  $t->ok(is_callable($test_class, $method), sprintf('Behavior adds a new %s() method to the object class', $method));
}

$t->diag('getSpamTag() and setSpamTag()');
$item1 = new $test_class();
$item1->save();
$t->is($item1->getSpamTag(), 1, 'getSpamTag() returns the current moderation status (defaults to 1 = not checked)');
$item1->setSpamTag(0);
$t->is($item1->getSpamTag(), 0, 'setSpamTag() changes the moderation status');

$t->diag('modifications of selection and count methods');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
$item1->setSpamTag(sfPropelSpamTagBehavior::NOT_CHECKED);
$item1->save();
$id1 = $item1->getId();
$item2 = new $test_class();
$item2->setSpamTag(sfPropelSpamTagBehavior::TAGGED_SPAM);
$item2->save();
$id2 = $item2->getId();
$item3 = new $test_class();
$item3->setSpamTag(sfPropelSpamTagBehavior::TAGGED_SAFE);
$item3->save();
$id3 = $item3->getId();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'retrieveByPk() finds unmarked records');
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id2), 'NULL', 'retrieveByPk() doesn\'t find records marked as spam');
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id3), $test_class, 'retrieveByPk() finds records marked as safe');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'doCount() ignores records marked as spam');
$t->is(count(call_user_func(array($test_peer_class, 'doSelect'), new Criteria())), 2, 'doSelect() ignores records marked as spam');

$t->diag('Publish/unpublish messages by default via appl.yml');
sfConfig::set('app_sfPropelSpamTagBehavior_display_treshold', sfPropelSpamTagBehavior::TAGGED_SAFE);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), 'NULL', 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 0 ignores unchecked records from normal selection');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 1, 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 0 ignores unchecked records from normal selection');
sfConfig::set('app_sfPropelSpamTagBehavior_display_treshold', sfPropelSpamTagBehavior::NOT_CHECKED);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 1 revelas unchecked records from normal selection');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 1 reveals unchecked records from normal selection');
sfConfig::set('app_sfPropelSpamTagBehavior_display_treshold', sfPropelSpamTagBehavior::TAGGED_SPAM);
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), $test_class, 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 3 lets all records through');
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 3, 'Setting `app_sfPropelSpamTagBehavior_display_treshold` to 3 lets all records through');
sfConfig::set('app_sfPropelSpamTagBehavior_display_treshold', sfPropelSpamTagBehavior::NOT_CHECKED);

$t->diag('tagAsSpam() and tagAsSafe()');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
$item1->save();
$id1 = $item1->getId();
$item1->tagAsSpam();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id1), 'NULL', 'tagAsSpam() changes the status to spam and saves the record');
$t->is($item1->getSpamTag(), sfPropelSpamTagBehavior::TAGGED_SPAM, 'tagAsSpam() changes the status to spam and saves the record');
$item1->tagAsSpam('automatic');
$t->is($item1->getSpamTag(), sfPropelSpamTagBehavior::TAGGED_AUTO_SPAM, 'tagAsSpam(\'automatic\') changes the status to auto_spam and saves the record');
$item2 = new $test_class();
$item2->save();
$id2 = $item2->getId();
$item2->tagAsSafe();
$t->isa_ok(call_user_func(array($test_peer_class, 'retrieveByPk'), $id2), $test_class, 'tagAsSafe() changes the status to safe and saves the record');
$t->is($item2->getSpamTag(), sfPropelSpamTagBehavior::TAGGED_SAFE, 'tagAsSafe() changes the status to safe and saves the record');

$t->diag('sfPropelSpamTagBehavior::enable() and disable()');
call_user_func(array($test_peer_class, 'doDeleteAll'));
$item1 = new $test_class();
$item1->setSpamTag(sfPropelSpamTagBehavior::NOT_CHECKED);
$item1->save();
$item2 = new $test_class();
$item2->setSpamTag(sfPropelSpamTagBehavior::TAGGED_SPAM);
$item2->save();
$item3 = new $test_class();
$item3->setSpamTag(sfPropelSpamTagBehavior::TAGGED_SAFE);
$item3->save();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Spam check is enabled by default in selections');
sfPropelSpamTagBehavior::disable();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 3, 'Setting sfPropelSpamTagBehavior::disable() disables the spam check in selections');
sfPropelSpamTagBehavior::enable();
$t->is(call_user_func(array($test_peer_class, 'doCount'), new Criteria()), 2, 'Setting sfPropelSpamTagBehavior::enable() enables the spam check in selections');

