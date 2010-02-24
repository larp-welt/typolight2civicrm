<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
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
 * @copyright  Tom Hegermann 2009
 * @author     Tom Hegermann (TomH)
 * @license    LGPL
 */

/**
 * Palettes
 */

$GLOBALS['TL_DCA']['tl_newsletter_channel']['palettes']['__selector__'][] = 'use_civicrm';
$GLOBALS['TL_DCA']['tl_newsletter_channel']['palettes']['default'] .= ';{civicrm_legend:hide},use_civicrm';
$GLOBALS['TL_DCA']['tl_newsletter_channel']['subpalettes']['use_civicrm'] = 'civicrm_group';

$GLOBALS['TL_DCA']['tl_newsletter']['list']['sorting']['headerFields'][] = 'use_civicrm';

/**
 * Fields
 */

$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['use_civicrm'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_newsletter_channel']['use_civicrm'],
	'inputType'		=> 'checkbox',
	'eval'			=> array('submitOnChange'=>true),
);

//$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['civicrm_group'] = array(
//	'label'		=> &$GLOBALS['TL_LANG']['tl_newsletter_channel']['civicrm_group'],
//	'inputType'	=> 'text',
//	'default'	=> '',
//	'eval'		=> array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50')
//);

$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['civicrm_group'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_newsletter_channel']['civicrm_group'],
	'inputType'	=> 'select',
	'options_callback' => array('CiviCRM', 'getAllGroups'),
	'default'	=> '',
	'eval'		=> array('mandatory'=>true, 'tl_class'=>'w50')
);

?>
