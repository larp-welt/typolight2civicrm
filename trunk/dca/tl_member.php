<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2010
 * @author     Leo Feyer <http://www.typolight.org>
 * @package    Backend
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ';{civicrm_legend:hidden},civicrm_id';

$GLOBALS['TL_DCA']['tl_member']['fields']['civicrm_id'] = array(
	'label' => &$GLOBALS['TL_LANG']['tl_member']['civicrm_id'],
	'inputType' => 'text',
	'eval' => array('disabled'=>true),
	);

$GLOBALS['TL_DCA']['tl_member']['fields']['newsletter']['save_callback'][] = array('CiviCRM', 'updateNewsletter');
$GLOBALS['TL_DCA']['tl_member']['fields']['email']['save_callback'][] = array('CiviCRM', 'updateEmail');

$GLOBALS['TL_DCA']['tl_member']['list']['global_operations']['civicrm_sync'] =
	array(
		'label'               => &$GLOBALS['TL_LANG']['tl_member']['civicrm_sync'],
		'href'                => 'key=civicrm_sync',
		'class'               => 'header_sync',
		'attributes'          => 'onclick="Backend.getScrollOffset();"'
	);

?>
