<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Newsletter
 * @license    LGPL
 * @filesource
 */


/**
 * Class ModuleSubscribe
 *
 * Front end module "newsletter subscribe".
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Controller
 */
class ModuleSubscribe extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'nl_default';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### NEWSLETTER SUBSCRIBE ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'typolight/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->nl_channels = deserialize($this->nl_channels, true);

		// Return if there are no channels
		if (!is_array($this->nl_channels) || count($this->nl_channels) < 1)
		{
			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{
		// Overwrite default template
		if ($this->nl_template)
		{
			$this->Template = new FrontendTemplate($this->nl_template);
		}

		// Activate e-mail address
		if ($this->Input->get('token'))
		{
			$this->activateRecipient();
			return;
		}

		// Subscribe
		if ($this->Input->post('FORM_SUBMIT') == 'tl_subscribe')
		{
			$this->addRecipient();
		}

		// Error message
		if (strlen($_SESSION['SUBSCRIBE_ERROR']))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $_SESSION['SUBSCRIBE_ERROR'];
			$_SESSION['SUBSCRIBE_ERROR'] = '';
		}

		// Confirmation message
		if (strlen($_SESSION['SUBSCRIBE_CONFIRM']))
		{
			$this->Template->mclass = 'confirm';
			$this->Template->message = $_SESSION['SUBSCRIBE_CONFIRM'];
			$_SESSION['SUBSCRIBE_CONFIRM'] = '';
		}

		$arrChannels = array();
		$objChannel = $this->Database->execute("SELECT id, title FROM tl_newsletter_channel WHERE id IN(" . implode(',', $this->nl_channels) . ") ORDER BY title");

		// Get titles
		while ($objChannel->next())
		{
			$arrChannels[$objChannel->id] = $objChannel->title;
		}

		// Default template variables
		$this->Template->email = '';
		$this->Template->channels = $arrChannels;
		$this->Template->showChannels = !$this->nl_hideChannels;
		$this->Template->submit = specialchars($GLOBALS['TL_LANG']['MSC']['subscribe']);
		$this->Template->action = ampersand($this->Environment->request);
		$this->Template->formId = 'tl_subscribe';
		$this->Template->id = $this->id;
	}


	/**
	 * Activate a recipient
	 */
	protected function activateRecipient()
	{
		$this->Template = new FrontendTemplate('mod_newsletter');

		// Check the token
		$objRecipient = $this->Database->prepare("SELECT r.id, r.email, c.id AS cid, c.title FROM tl_newsletter_recipients r LEFT JOIN tl_newsletter_channel c ON r.pid=c.id WHERE token=?")
									   ->execute($this->Input->get('token'));

		if ($objRecipient->numRows < 1)
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidToken'];

			return;
		}

		$arrAdd = $objRecipient->fetchEach('id');
		$arrChannels = $objRecipient->fetchEach('title');

		// Update subscriptions
		$this->Database->prepare("UPDATE tl_newsletter_recipients SET active=1, token='' WHERE token=?")
					   ->execute($this->Input->get('token'));

		// Log activity
		$this->log($objRecipient->email . ' has subscribed to ' . implode(', ', $arrChannels), 'ModuleSubscribe activateRecipient()', TL_NEWSLETTER);

		// HOOK: post activation callback
		if (isset($GLOBALS['TL_HOOKS']['activateRecipient']) && is_array($GLOBALS['TL_HOOKS']['activateRecipient']))
		{
			$arrCids = $objRecipient->fetchEach('cid');

			foreach ($GLOBALS['TL_HOOKS']['activateRecipient'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($objRecipient->email, $arrAdd, $arrCids);
			}
		}

		// Confirm activation
		$this->Template->mclass = 'confirm';
		$this->Template->message = $GLOBALS['TL_LANG']['MSC']['nl_activate'];
	}


	/**
	 * Add a new recipient
	 */
	protected function addRecipient()
	{
		$arrChannels = $this->Input->post('channels');

		// Check selection
		if (!is_array($arrChannels) || count($arrChannels) < 1)
		{
			$_SESSION['SUBSCRIBE_ERROR'] = $GLOBALS['TL_LANG']['ERR']['noChannels'];
			$this->reload();
		}

		// Validate e-mail address
		if (!preg_match('/^\w+([!#\$%&\'\*\+\-\/=\?^_`\.\{\|\}~]*\w+)*@\w+([_\.-]*\w+)*\.[a-z]{2,6}$/i', $this->Input->post('email', true)))
		{
			$_SESSION['SUBSCRIBE_ERROR'] = $GLOBALS['TL_LANG']['ERR']['email'];
			$this->reload();
		}

		$arrSubscriptions = array();

		// Get active subscriptions
		$objSubscription = $this->Database->prepare("SELECT pid FROM tl_newsletter_recipients WHERE email=? AND active=?")
										  ->execute($this->Input->post('email', true), 1);

		if ($objSubscription->numRows)
		{
			$arrSubscriptions = $objSubscription->fetchEach('pid');
		}

		$arrNew = array_diff($arrChannels, $arrSubscriptions);

		// Return if there are no new subscriptions
		if (!is_array($arrNew) || count($arrNew) < 1)
		{
			$_SESSION['SUBSCRIBE_ERROR'] = $GLOBALS['TL_LANG']['ERR']['subscribed'];
			$this->reload();
		}

		$time = time();
		$strToken = md5(uniqid('', true));
		$arrCondition = array();
		$arrValues = array();

		// Prepare new subscriptions
		foreach ($arrNew as $id)
		{
			$arrValues[] = $id;
			$arrValues[] = $time;
			$arrValues[] = $this->Input->post('email', true);
			$arrValues[] = '';
			$arrValues[] = $time;
			$arrValues[] = $this->Environment->ip;
			$arrValues[] = $strToken;

			$arrCondition[] = '(?, ?, ?, ?, ?, ?, ?)';
		}

		// Remove old subscriptions that have not been activated yet
		$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE email=? AND active!=?")
					   ->execute($this->Input->post('email', true), 1);

		// Add new subscriptions
		$this->Database->prepare("INSERT INTO tl_newsletter_recipients (pid, tstamp, email, active, addedOn, ip, token) VALUES " . implode(', ', $arrCondition))
					   ->execute($arrValues);

		// Activation e-mail
		$objEmail = new Email();

		// Get channels
		$objChannel = $this->Database->execute("SELECT title FROM tl_newsletter_channel WHERE id IN(" . implode(',', $arrChannels) . ")");

		$strText = str_replace('##domain##', $this->Environment->host, $this->nl_subscribe);
		$strText = str_replace('##link##', $this->Environment->base . $this->Environment->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || strpos($this->Environment->request, '?') !== false) ? '&' : '?') . 'token=' . $strToken, $strText);
		$strText = str_replace('##token##', $strToken, $strText);
		$strText = str_replace(array('##channel##', '##channels##'), implode("\n", $objChannel->fetchEach('title')), $strText);

		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], $this->Environment->host);
		$objEmail->text = $strText;

		$objEmail->sendTo($this->Input->post('email', true));
		global $objPage;

		// Redirect to jumpTo page
		if (strlen($this->jumpTo) && $this->jumpTo != $objPage->id)
		{
			$objNextPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
										  ->limit(1)
										  ->execute($this->jumpTo);

			if ($objNextPage->numRows)
			{
				$this->redirect($this->generateFrontendUrl($objNextPage->fetchAssoc()));
			}
		}

		$_SESSION['SUBSCRIBE_CONFIRM'] = $GLOBALS['TL_LANG']['MSC']['nl_confirm'];
		$this->reload();
	}
}

?>