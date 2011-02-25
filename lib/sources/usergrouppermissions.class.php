<?php

/***************************************************************************
 *            usergrouppermissions.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
include_once('lib/userpermissions.class.php');
 
class _userGroupPermissions extends userPermissions {
	var $sqlTable = 'usergrouppermissions';
	var $sqlRow = 'GroupID';
	var $sqlOwnerTable = 'usergroups';
	var $sqlOwnerField = 'GroupName';
	var $adminPath = 'admin/members/usergroups/usergrouppermissions';
	
	function setupAdmin() {
		parent::setupAdmin();
		
		favoriteLinks::add(
			__('User Groups'), 
			'?path=admin/members/usergroups');
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Group Permissions'),
			$ownertitle);
	}
}

?>