<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

if (1/*||isTesting()*/) {
    require __DIR__  . "/cache.new.php";
} else {
    require __DIR__  . "/cache.legacy.php";
}