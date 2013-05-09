<?php
/**
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

 The JSpace component for Joomla! is free software: you can redistribute it
 and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation, either version 3 of the License,
 or (at your option) any later version.

 The JSpace component for Joomla! is distributed in the hope that it will be
 useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with the JSpace component for Joomla!.  If not, see
 <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */

defined('JPATH_PLATFORM') or die;

jimport('jspace.factory');
jimport('joomla.application.component.view');

class JSpaceMessenger{
	const TYPE_ITEM_ARCHIVED = 'itemArchived';
	
	/**
	 * 
	 * @var JMail
	 */
	protected static $mailer;
	
	/**
	 * Array of email adresses
	 * @var array
	 */
	protected $receipients = array();
	
	/**
	 * Array of user ids to send email to
	 * @var array
	 */
	protected $userIds = array();
	
	/**
	 * A db query to get the receipients
	 * @var unknown_type
	 */
	protected $dbQuery = null;
	protected $db = null;
	
	protected $subject = "";
	/**
	 * 
	 * @var JView
	 */
	protected $body = null;
	
	/**
	 * @return JMail
	 */
	public static function getMailer() {
		if( is_null(self::$mailer) ) {
			self::$mailer = JFactory::getMailer();
		}
		return self::$mailer;
	}
	
	public function __construct() {
		$this->body = new JView();
		$this->body->addTemplatePath( JPATH_LIBRARIES . "/jspace/messenger/views/mail/tmpl/");
		$app = JFactory::getApplication();
		$this->body->addTemplatePath( JPATH_THEMES . "/" . $app->getTemplate() . "/html/jspace/mail/");
		
		$this->body->setLayout('default');
	}
	
	/**
	 * Gets a db query to retrieve receipients.
	 */
	protected function getDbQuery() {
		if( is_null($this->dbQuery) ) {
			$this->db = JFactory::getDbo();
			$this->dbQuery = $this->db->getQuery(true);
			$this->dbQuery->select('email');
			$this->dbQuery->from('#__users');
		}
		return $this->dbQuery;
	}
	
	/**
	 * Set receipients by group.
	 * @param <string|array> $receipients
	 */
	public function addReceipientsGroups($groups, $recursive=true) {
		if( !is_array($groups) ) {
			$groups = array($groups);
		}
		
		$acl = JFactory::getACL();
		$to = array();
		foreach( $groups as $grp) {
			$to = array_merge($to,$acl->getUsersByGroup($grp, $recursive));
		}
		
		$this->userIds = array_merge($this->userIds, $to);
		
		return $this;
	}
	
	/**
	 * This is actually loading the emails from db if nesessary.
	 * @return JSpaceMessenger
	 */
	protected function loadFromDb() {
		
		if( count($this->userIds)>0 ) {
			$this->getDbQuery()->where('id IN (' . implode(',', $this->userIds) . ')');
		}
		if( !is_null($this->dbQuery) ) {
			$this->db->setQuery($this->getDbQuery());
			$rows = $this->db->loadColumn();
			
			$this->receipients = array_merge($this->receipients, $rows);
		}
		return $this;
	} 
	
	public function setMessageType($type, $params = array()) {
		switch( $type ) {
			case self::TYPE_ITEM_ARCHIVED:
				if( !isset($params['item']) ) {
					throw new JException( JText::_('COM_JSPACE_MAIL_MESSAGE_TYPE_MISSING_ITEM') );
				}
				$item = $params['item'];
				$profile = JUserHelper::getProfile($item->user_id);
				$this->subject = JText::sprintf('COM_JSPACE_MAIL_SUBJECT_' . strtoupper($type), $profile->jspace['firstName'] . ' ' . $profile->jspace['lastName']);
				break;
			default: 
				$this->subject = JText::_('COM_JSPACE_MAIL_SUBJECT_' . strtoupper($type));
				break;
		}
		$this->body->assign('params', $params);
		$this->body->assign('mail', $type);
		
		return $this;
	}
	
// 	public function setMessage($subject, $body) {
		
// 	}
	
	/**
	 * Attempt to send email according to saved setings.
	 * 
	 * @return boolean
	 */
	public function send() {
		$app	= JFactory::getApplication();
		$this->loadFromDb();
		
		if( count($this->receipients) == 0 ) {
			return true;
		}
		$mailer = self::getMailer();
		$mailer->setSender(array($app->getCfg('mailfrom'), $app->getCfg('fromname')));
		$mailer->setSubject($this->subject);
		$mailer->setBody($this->body->loadTemplate());
		$mailer->IsHTML(true);
		
		$mailer->addRecipient($this->receipients);
		// Send the Mail
		$rs	= $mailer->Send();
		
		// Check for an error
		if ($rs instanceof Exception) {
			throw new JException( $rs->getError() );
			return false;
		} elseif (empty($rs)) {
			throw new JException( JText::_('COM_JSPACE_THE_MAIL_COULD_NOT_BE_SENT'));
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Add array of emails to receipients list.
	 * 
	 * @param <array|string> $r
	 * @return JSpaceMessenger
	 */
	public function addReceipientsEmails( $r ) {
		if( !is_array($r) ) {
			$r = array( $r );
		}
		$this->receipients = array_merge($this->receipients, $r);
		return $this;
	}
}







