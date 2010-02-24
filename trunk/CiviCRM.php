<?php
/**
 * TYPOlight CiviCRM Integration
 *
 * Cron is a scheduler module for the TYPOlight CMS. It allows to automaticly
 * execute php on a time schedule similar to the unix cron/crontab scheme.
 * TYPOlight is a web content management system that specializes in accessibility
 * and generates W3C-compliant HTML code.
 *
 * If you need to contact the author of this module, please use the forum at
 * http://www.typolight.org/forum. Additional documentation can be found at the
 * 3rd party extensions WIKI http://www.typolight.org/wiki/extensions:extensions
 * For more information about TYPOlight and additional applications please visit
 * the project website http://www.typolight.org.
 *
 * NOTE: this file was edited with tabs set to 4.
 *
 * CiviCRM class implementation
 *
 * PHP version 5
 * @copyright
 * @author
 * @package    xCiviCRM
 * @license
 * @filesource
 */

class CiviCRM extends Backend
{
	/* map TL fields to CiviCRM fields */
	private $arrMapping = array('username'=>'nick_name',
							    'email'=>'email',
								'website'=>'home_URL',
								'firstname'=>'first_name',
								'lastname'=>'last_name',
								);


	public function newMemberHook(Database_Result $objUser)
	{
		$contact = $this->getContact(array('email'=>$objUser->email));

		/* Error values returned by CiviCRM are a bit inconsistent */
		if (array_key_exists('is_error', $contact)) {
			/* no contact found -> create one and put him into members group */
			$valId = $this->createContact($objUser);
			$objUser->civicrm_id = $valId;
			if ($valId != null)
			{
				$this->addToGroup($valId, $GLOBALS['TL_CONFIG']['civicrm_membergroup']);
				$this->addCiviCRMID($objUser->id, $valId);
				$this->updateNewsletter($objUser->newsletter, $objUser, true);
			}
		} elseif (count($contact) == 1) {
			/* exact one contact found, we will use it
			 * and add it to the members group */
			$objUser->civicrm_id = $contact[0]['contact_id'];
			$this->addToGroup($contact[0]['contact_id'],
							  $GLOBALS['TL_CONFIG']['civicrm_membergroup']);
			$this->addCiviCRMID($objUser->id, $contact[0]['contact_id']);
			$this->updateNewsletter($objUser->newsletter, $objUser, true);
		} else {
			/* more then one contact found ... what now?
			 *
			 * Possible Solutions:
			 * Send me a mail, use first contact, do nothing
			 *
			 */
			// XXX
		}
	}


	public function activateRecipientHook($strEmail, $arrRecipients, $arrChannels)
	{
		/* get userdata */
		$contact = $this->getContact(array('email'=>$strEmail));

		/* get group data */
		/* Only first group is used at the moment */
		$objChannels = $this->Database->prepare("SELECT id, title, civicrm_group FROM tl_newsletter_channel WHERE title=?")
									   ->execute($arrChannels[0]);
		$arrGroups = $objChannels->fetchEach('civicrm_group');
		$intGroup = $arrGroups[0];

		/* Error values returned by CiviCRM are a bit inconsistent */
		if (array_key_exists('is_error', $contact)) {
			/* no contact found -> create one and put him into members group */
			$valId = $this->createEmailonlyContact($strEmail);
			if ($valId != null)
			{
				$this->addToGroup($valId, $intGroup);
			}
		} elseif (count($contact) >= 1) {
			/* one ore more contacts found, we will use the first one
			 * and add it to the newsletter group */
			$this->addToGroup($contact[0]['contact_id'], $intGroup);
		}
	}


	public function removeRecipientHook($strEmail, $arrChannels)
	{
		/* get userdata */
		$contact = $this->getContact(array('email'=>$strEmail));

		/* get group data */
		/* Only first group is used at the moment */
		$objChannels = $this->Database->prepare("SELECT id, title, civicrm_group FROM tl_newsletter_channel WHERE id=?")
									   ->execute($arrChannels[0]);
		$arrGroups = $objChannels->fetchEach('civicrm_group');
		$intGroup = $arrGroups[0];

		/* Error values returned by CiviCRM are a bit inconsistent */
		if (array_key_exists('is_error', $contact)) {
			/* no contact found, so nothing left to do */
		} elseif (count($contact) >= 1) {
			/* one ore more contacts found, we will use the first one
			 * and add it to the newsletter group */
			$this->removeFromGroup($contact[0]['contact_id'], $intGroup);
		}
	}


	public function updateNewsletter($varValue, $objUser, $force = false)
	{
		/* this code is shamless stolen from Newsletter.php */

		// If called from the back end, the second argument is a DataContainer object
		if ($objUser instanceof DataContainer)
		{
			$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($objUser->id);

			if ($objUser->numRows < 1)
			{
				return $varValue;
			}
		}

		// Nothing has changed or member not tracked in CiviCRM
		if (($varValue == $objUser->newsletter && !$force) || !$objUser->civicrm_id)
		{
			return $varValue;
		}

		$varValue = deserialize($varValue, true);

		// Get all channel IDs
		$objChannel = $this->Database->execute("SELECT id FROM tl_newsletter_channel");
		$arrChannel = $objChannel->fetchEach('id');
		$arrOld = deserialize($objUser->newsletter, true);

		$arrDelete = array_values(array_diff($arrOld, $varValue));

		/* delete removed channels at civicrm */
		if (count($arrDelete) > 0)
		{
			foreach ($arrDelete as $intChannel)
			{
				$arrGroups = $this->Database->prepare("SELECT civicrm_group FROM tl_newsletter_channel WHERE id=? AND use_civicrm=1")
											->execute($intChannel)
											->fetchEach('civicrm_group');
				if (count($arrGroups)>0) { $this->removeFromGroup($objUser->civicrm_id, $arrGroups[0]); }
			}
		}

		/* add subscribtions add civicrm */
		if (count($varValue) > 0)
		{
			foreach ($varValue as $intChannel)
			{
				$arrGroups = $this->Database->prepare("SELECT civicrm_group FROM tl_newsletter_channel WHERE id=? AND use_civicrm=1")
											->execute($intChannel)
											->fetchEach('civicrm_group');
				if (count($arrGroups)>0) { $this->addToGroup($objUser->civicrm_id, $arrGroups[0]); }
			}
		}

		return serialize($varValue);
	}


	public function updateEmail($varValue, $objUser)
	{
		// If called from the back end, the second argument is a DataContainer object
		if ($objUser instanceof DataContainer)
		{
			$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($objUser->id);

			if ($objUser->numRows < 1)
			{
				return $varValue;
			}
		}

		if ($objUser->civicrm_id)
		{
			$arrData = array('contact_id'=>$objUser->civicrm_id,
							 'contact_type'=>'Individual',
							 'email[1][email]'=>$varValue,
							 'email[1][is_primary]'=>1,
							 'email[1][location_type_id]'=>1);
			$strUrl = $this->buildUrl('contact/add', $arrData);
			$arrResult = $this->writeToUrl($strUrl);
		}
		return $varValue;
	}


	public function syncMembers()
	{
		$objMembers = $this->Database->execute('SELECT id,email,username FROM tl_member WHERE civicrm_id IS NULL');

		$count = 0;
		$updated = array();
		if ($objMembers->numRows)
		{
			while ($objMembers->next())
			{
				$data = $objMembers->row();
				$contact = $this->getContact(array('email'=>$data['email']));

				if (!array_key_exists('is_error', $contact))
				{
					$this->Database->prepare('UPDATE tl_member SET civicrm_id=? WHERE id=?')
								   ->execute($contact[0]['contact_id'], $data['id']);
					$count++;
					$updated[] = sprintf('%-64s (%s)', $data['username'], $data['email']);
				}
			}
		}

		if ($count > 0)
		{
			$updated = implode("\n", $updated);
			$message = "<p>".sprintf($GLOBALS['TL_LANG']['tl_member']['civicrm_syncinfo'], $count)."</p>";
			$message .= "<p><textarea class=\"civicrm_list\">$updated</textarea></p>";
		} else {
			$message = "<p>".$GLOBALS['TL_LANG']['tl_member']['civicrm_nosync']."</p>";
		}

		$return = '<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_member']['civicrm_sync'].'</h2>';
		$return .= '<div class="tl_formbody_edit">';
		$return .= '<div class="tl_tbox">';
		$return .= $message;
		$return .= '<div id="tl_buttons">';
		$return .= '<a href="'.ampersand(str_replace('&key=civicrm_sync', '', $this->Environment->request)).'" class="header_back" title="';
		$return .= specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>';
		$return .= '</div></div></div>';

		return $return;
	}


	/*
	 * Private Functions
	 *
	 */


	/*
	 * Contacts
	 */

	protected function getContact($arrSearch)
	{
		$strUrl = $this->buildUrl('contact/search', $arrSearch);
		$arrReturn = $this->readFromUrl($strUrl);

		return $arrReturn;
	}


	protected function createContact($objUser)
	{
		$arrData = array('contact_type'=>'Individual');

		foreach ($this->arrMapping as $tl => $civi)
		{
			if ($objUser->$tl)
			{
				$arrData[$civi]=$objUser->$tl;
			}
		}

		/* Anlegen */
		$strUrl = $this->buildUrl('contact/add', $arrData);
		$arrResult = $this->writeToUrl($strUrl);

		$valReturn = ($arrResult['is_error']==0) ? $arrResult['contact_id'] : null;

		return $valReturn;
	}


	protected function createEmailonlyContact($strEmail)
	{
		$strUrl = $this->buildUrl('contact/add',
								  array('email'=>$strEmail,
										'contact_type'=>'Individual'));
		$arrResult = $this->writeToUrl($strUrl);

		$valReturn = ($arrResult['is_error']==0) ? $arrResult['contact_id'] : null;

		return $valReturn;
	}


	protected function addCiviCRMID($intTl, $intCivicrm)
	{
		$this->Database->prepare("UPDATE tl_member SET civicrm_id=? WHERE id=?")
					   ->execute($intCivicrm, $intTl);
	}


	/*
	 * Groups
	 */

	protected function addToGroup($intUser, $intGroup)
	{
		$strUrl = $this->buildUrl('group_contact/add',
								  array('contact_id'=>$intUser,
								        'group_id'=>$intGroup));
		return $this->writeToUrl($strUrl);
	}


	protected function removeFromGroup($intUser, $intGroup)
	{
		$strUrl = $this->buildUrl('group_contact/remove',
								  array('contact_id'=>$intUser,
								        'group_id'=>$intGroup));

		return $this->writeToUrl($strUrl);
	}

	/*
	 * curl-Wrapper
	 */


	protected function buildUrl($strCmd, $arrParams = array())
	{
		$url = $GLOBALS['TL_CONFIG']['civicrm_apiurl'].'rest.php';

		$arrParams['q'] = 'civicrm/'.$strCmd;
		$arrParams['key'] = $GLOBALS['TL_CONFIG']['civicrm_sitekey'];
		$arrParams['api_key'] = $GLOBALS['TL_CONFIG']['civicrm_apikey'];
		$arrParams['json'] = 1;

		$arrPairs = array();
		foreach ($arrParams as $name => $value)
		{
			$arrPairs[] = $name.'='.$value;
		}
		return $url.'?'.implode('&', $arrPairs);
	}


	protected function readFromUrl($strUrl)
	{
		$data = $this->_callUrl($strUrl);

		return json_decode($data, true);
	}


	protected function writeToUrl($strUrl)
	{
		$data = $this->_callUrl($strUrl);

		return json_decode($data, true);
	}


	protected function _callUrl($strUrl)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);

		curl_close($ch);

		return $data;
	}


}

?>
