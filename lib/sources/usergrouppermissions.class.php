<?php

/***************************************************************************
 *            usergrouppermissions.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 *  For licensing, see LICENSE or http://jcore.net/license
 ****************************************************************************/
 
include_once('lib/userpermissions.class.php');
 
class _userGroupPermissions extends userPermissions {
	var $sqlTable = 'usergrouppermissions';
	var $sqlRow = 'GroupID';
	var $sqlOwnerTable = 'usergroups';
	var $sqlOwnerField = 'GroupName';
	var $adminPath = 'admin/members/usergroups/usergrouppermissions';
	
	function setupAdmin() {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'userGroupPermissions::setupAdmin', $this);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'userGroupPermissions::setupAdmin', $this, $handled);
			
			return $handled;
		}
		
		parent::setupAdmin();
		
		favoriteLinks::add(
			__('User Groups'), 
			'?path=admin/members/usergroups');
		
		api::callHooks(API_HOOK_AFTER,
			'userGroupPermissions::setupAdmin', $this);
	}
	
	function displayAdminTitle($ownertitle = null) {
		$handled = api::callHooks(API_HOOK_BEFORE,
			'userGroupPermissions::displayAdminTitle', $this, $ownertitle);
		
		if (isset($handled)) {
			api::callHooks(API_HOOK_AFTER,
				'userGroupPermissions::displayAdminTitle', $this, $ownertitle, $handled);
			
			return $handled;
		}
		
		admin::displayTitle(
			__('Group Permissions'),
			$ownertitle);
		
		api::callHooks(API_HOOK_AFTER,
			'userGroupPermissions::displayAdminTitle', $this, $ownertitle);
	}
}

?>