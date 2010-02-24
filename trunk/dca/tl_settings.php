<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');
/**
 * TYPOlight Cron Scheduler
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
 * Extends module tl_settings.
 *
 * PHP version 5
 * @copyright  
 * @author     
 * @package    CiviCRM
 * @license    
 * @filesource
 */

/**
 * Add to palette
 */

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{civicrm_legend},civicrm_apiurl,civicrm_sitekey,civicrm_apikey,civicrm_membergroup'; 

/**
 * Add field
 */

$GLOBALS['TL_DCA']['tl_settings']['fields']['civicrm_apiurl'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['civicrm_apiurl'],
	'inputType'	=> 'text',
	'default'	=> 'http://localhost/civicrm/standalone/',
	'eval'		=> array('mandatory'=>true, 'rgxp'=>'url', 'nospace'=>true, 'trailingSlash'=>true, 'tl_class'=>'long')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['civicrm_sitekey'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['civicrm_sitekey'],
	'inputType'	=> 'text',
	'default'	=> '',
	'eval'		=> array('mandatory'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'minlength'=>7, 'maxlength'=>32, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['civicrm_apikey'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['civicrm_apikey'],
	'inputType'	=> 'text',
	'default'	=> '',
	'eval'		=> array('mandatory'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['civicrm_membergroup'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['civicrm_membergroup'],
	'inputType'	=> 'text',
	'default'	=> '',
	'eval'		=> array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50')
);

?>
