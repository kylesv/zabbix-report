<?php
/*
** Zabbix
** Copyright (C) 2015 Technoserv
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
**/

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/report-ts.inc.php';
require_once dirname(__FILE__).'/include/func.inc.php';

$fields = array(
	'groupid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostid' =>				array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostgroupid' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'tpl_triggerid' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'fullscreen' =>			array(T_ZBX_INT, O_OPT, P_SYS,	IN('0,1'),	null),
	'btnSelect' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	'file'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'orient'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	// filter
	'period'=>				array(T_ZBX_INT, O_OPT, null,	null,		null),
	'elementid'=>				array(T_ZBX_INT, O_OPT, null,	null,		null),
	'stime'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'filter_timesince'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'filter_timetill'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'action'=>				array(T_ZBX_STR, O_OPT, null,	null,		null)
	// sort and sortorder
);
check_fields($fields);

$config = select_config();





?>