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
require_once dirname(__FILE__).'/include/graphs.inc.php';
require_once dirname(__FILE__).'/include/screens.inc.php';
require_once dirname(__FILE__).'/include/blocks.inc.php';
require_once dirname(__FILE__).'/include/reports.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';
require_once dirname(__FILE__).'/include/events.inc.php';
require_once dirname(__FILE__).'/include/actions.inc.php';
require_once dirname(__FILE__).'/include/discovery.inc.php';
require_once dirname(__FILE__).'/include/html.inc.php';
require_once dirname(__FILE__).'/include/items.inc.php';
require_once dirname(__FILE__).'/include/report-ts.inc.php';

$page['title'] = _('Report TS');
$page['file'] = 'report-ts.php';
$page['hist_arg'] = array('mode', 'groupid', 'hostid','hostgroupid', 'tpl_triggerid');
$page['scripts'] = array('class.calendar.js', 'gtlc.js', 'flickerfreescreen.js');
$page['type'] = detect_page_type(PAGE_TYPE_HTML);


// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'groupid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostid' =>				array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostgroupid' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'fullscreen' =>			array(T_ZBX_INT, O_OPT, P_SYS,	IN('0,1'),	null),
	'btnSelect' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	// filter
	'tpl_triggerid' =>		array(T_ZBX_INT,	O_OPT,	P_SYS,			DB_ID,		null),
	'triggerid' =>			array(T_ZBX_INT,	O_OPT,	P_SYS|P_NZERO,	DB_ID,		null),
	'period'=>				array(T_ZBX_INT, O_OPT, null,	null,		null),
	'elementid'=>			array(T_ZBX_INT, O_OPT, null,	null,		null),
	'stime'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'action'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'file'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'orient'=>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'create_rep' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'filter_rst' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'show_triggers' =>		array(T_ZBX_INT, O_OPT, null,	null,		null),
	'show_events' =>		array(T_ZBX_INT, O_OPT, P_SYS,	null,		null),
	'ack_status' =>			array(T_ZBX_INT, O_OPT, P_SYS,	null,		null),
	'show_severity' =>		array(T_ZBX_INT, O_OPT, P_SYS,	null,		null),
	'show_details' =>		array(T_ZBX_INT, O_OPT, null,	null,		null),
	'show_maintenance' =>	array(T_ZBX_INT, O_OPT, null,	null,		null),
	'status_change_days' =>	array(T_ZBX_INT, O_OPT, null,	BETWEEN(1, DAY_IN_YEAR * 2), null),
	'status_change' =>		array(T_ZBX_INT, O_OPT, null,	null,		null),
	'txt_select' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	'application' =>		array(T_ZBX_STR, O_OPT, null,	null,		null),
	'inventory' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	'filter_groupid'=>		array(T_ZBX_INT,	O_OPT,	P_SYS,			DB_ID,		null),
	'filter_hostid' =>		array(T_ZBX_INT,	O_OPT,	P_SYS,			DB_ID,		null),
	'filter_timesince' =>	array(T_ZBX_STR,	O_OPT,	P_UNSET_EMPTY,	null,		null),
	'filter_timetill' =>	array(T_ZBX_STR,	O_OPT,	P_UNSET_EMPTY,	null,		null),
	// ajax
	'filterState' =>		array(T_ZBX_INT, O_OPT, P_ACT,	null,		null),
	// sort and sortorder
	'sort' =>				array(T_ZBX_STR, O_OPT, P_SYS, IN('"description","lastchange","priority"'),	null),
	'sortorder' =>			array(T_ZBX_STR, O_OPT, P_SYS, IN('"'.ZBX_SORT_DOWN.'","'.ZBX_SORT_UP.'"'),	null)
);

check_fields($fields);

// ----- Проверяем данные с формы и заполняем их если нет значений
if(hasRequest('action'))
{
$_REQUEST['filter_timesince'] = zbxDateToTime($_REQUEST['filter_timesince']
	? $_REQUEST['filter_timesince'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time() - SEC_PER_DAY));
}
if(!hasRequest('action'))
{
	$_REQUEST['action'] = getRequest('action',CProfile::get('web.ts_report.action', 0));
}
if(!hasRequest('file'))
{
	$_REQUEST['file'] = getRequest('file',CProfile::get('web.ts_report.file', 0));
}
if(!hasRequest('orient'))
{
	$_REQUEST['orient'] = getRequest('orient',CProfile::get('web.ts_report.orient', 0));
}

if(!hasRequest('period'))
{
	$_REQUEST['period'] = getRequest('period',CProfile::get('web.ts_report.period', 0));
}

if(!hasRequest('elementid'))
{
	$_REQUEST['elementid'] = getRequest('elementid',CProfile::get('web.ts_report.elementid', 0));
}
if(!hasRequest('tpl_triggerid'))
{
	$_REQUEST['tpl_triggerid'] = getRequest('tpl_triggerid',CProfile::get('web.ts_report.tpl_triggerid', 0));
}
if(!hasRequest('filter_groupid'))
{
	$_REQUEST['filter_groupid'] = getRequest('filter_groupid',CProfile::get('web.ts_report.filter_groupid', 0));
}
if(!hasRequest('filter_hostid'))
{
	$_REQUEST['filter_hostid'] = getRequest('filter_hostid',CProfile::get('web.ts_report.filter_hostid', 0));
}

// если нажата кнопка сброса параметров
if (hasRequest('filter_rst'))
{
	$_REQUEST['filter_groupid'] = 0;
	$_REQUEST['filter_hostid'] = 0;
	$_REQUEST['filter_timesince'] = zbxDateToTime($_REQUEST['filter_timesince']
	? $_REQUEST['filter_timesince'] : date(TIMESTAMP_FORMAT_ZERO_TIME, time() - SEC_PER_DAY));
	$_REQUEST['tpl_triggerid'] = 0;
	$_REQUEST['groupid'] = 0;
	$_REQUEST['action'] = 'events';
	$_REQUEST['file'] = 'pdf';
	$_REQUEST['orient'] = 'Portrait';
	$_REQUEST['hostid'] = 0;
	$_REQUEST['groupid'] = 0;
	$_REQUEST['elementid'] = 0;
	$_REQUEST['period'] = 86400;
}
if (!hasRequest('filter_rst')) {
	$_REQUEST['filter_groupid'] = getRequest('filter_groupid',CProfile::get('web.ts_report.filter_groupid', 0));
	$_REQUEST['filter_hostid'] = getRequest('filter_hostid',CProfile::get('web.ts_report.filter_hostid', 0));
	$_REQUEST['hostgroupid'] = getRequest('groupid',CProfile::get('web.ts_report.groupid', 0));
	$_REQUEST['filter_timesince'] = getRequest('filter_timesince',CProfile::get('web.ts_report.filter_timesince', 0));
}

CProfile::update('web.ts_report.tpl_triggerid', getRequest('tpl_triggerid', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.filter_hostid', getRequest('filter_hostid', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.file', getRequest('file', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.filter_groupid', getRequest('filter_groupid', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.orient', getRequest('orient', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.action', getRequest('action', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.period', getRequest('period', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.filter_timesince', getRequest('filter_timesince', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.elementid', getRequest('elementid', 0),PROFILE_TYPE_ID);
CProfile::update('web.ts_report.timesince', getRequest('filter_timesince', 0),PROFILE_TYPE_STR);
CProfile::update('web.ts_report.hostgroupid', getRequest('hostgroupid', 0),PROFILE_TYPE_ID);



/*
 * Permissions
 */
if (getRequest('groupid') && !API::HostGroup()->isReadable(array(getRequest('groupid')))) {
	access_deny();
}
if (getRequest('hostid') && !API::Host()->isReadable(array(getRequest('hostid')))) {
	access_deny();
}

/*
 * Ajax
 */
if (hasRequest('filterState')) {
	CProfile::update('web.ts_report.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
}
if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}


$config = select_config();

$pageFilter = new CPageFilter(array(
	'groups' => array(
		'monitored_hosts' => true,
		'with_monitored_triggers' => true
	),
	'hosts' => array(
		'monitored_hosts' => true,
		'with_monitored_triggers' => true
	),
	'hostid' => getRequest('hostid'),
	'groupid' => getRequest('groupid')
));
$_REQUEST['groupid'] = $pageFilter->groupid;
$_REQUEST['hostid'] = $pageFilter->hostid;

/*
 * Display
 */
 

$cmbPeriod = new CComboBox('period', $_REQUEST['period'], 'submit()');
$cmbPeriod->addItem('86400', _('Day'));
$cmbPeriod->addItem('604800', _('Week'));
$cmbPeriod->addItem('2592000', _('Month'));
$cmbPeriod->addItem('31104000', _('Year'));
$cmbAction = new CComboBox('action', $_REQUEST['action'], 'submit()');
$cmbAction->addItem('events', _('События'));
$cmbAction->addItem('screens', _('Комплексный экран'));
$cmbAction->addItem('report2', _('Доступность'));
$cmbFile = new CComboBox('file', $_REQUEST['file'], 'submit()');
$cmbFile->addItem('pdf', _('PDF'));
if(getRequest('action')=='events' or getRequest('action')=='report2' )
{
	$cmbFile->addItem('csv', _('CSV'));
}
$cmbFile->addItem('http', _('Web page'));
if(getRequest('action')=='screens')
{
	$screens = API::Screen()->get(array(
			'screenids' => array($_REQUEST['elementid']),
			'output' => array('screenid')
		));
	$data['screens'] = API::Screen()->get(array(
		'output' => array('screenid', 'name')
	));
}

// trigger options

if(getRequest('action')=='report2')
{
	if (!empty($_REQUEST['filter_hostid']) || !$config['dropdown_first_entry']) {
		$hosts = API::Host()->get(array(
			'output' => array('hostid'),
			'templateids' => $_REQUEST['filter_hostid']
		));

		$triggerOptions['hostids'] = zbx_objectValues($hosts, 'hostid');
	}
	if (isset($_REQUEST['tpl_triggerid']) && !empty($_REQUEST['tpl_triggerid'])) {
		$triggerOptions['filter']['templateid'] = $_REQUEST['tpl_triggerid'];
	}
	if (isset($_REQUEST['hostgroupid']) && !empty($_REQUEST['hostgroupid'])) {
		$triggerOptions['groupids'] = $_REQUEST['hostgroupid'];
	}

	// filter template group
	$groupsComboBox = new CComboBox('filter_groupid', $_REQUEST['filter_groupid'], 'javascript: submit();');
	$groupsComboBox->addItem(0, _('all'));

	$groups = API::HostGroup()->get(array(
		'output' => array('groupid', 'name'),
		'templated_hosts' => true,
		'with_triggers' => true
	));
	order_result($groups, 'name');

	foreach ($groups as $group) {
		$groupsComboBox->addItem($group['groupid'], $group['name']);
	}

	// filter template
	$templateComboBox = new CComboBox('filter_hostid', $_REQUEST['filter_hostid'], 'javascript: submit();');
	$templateComboBox->addItem(0, _('all'));

	$templates = API::Template()->get(array(
		'output' => array('templateid', 'name'),
		'groupids' => empty($_REQUEST['filter_groupid']) ? null : $_REQUEST['filter_groupid'],
		'with_triggers' => true
	));
	order_result($templates, 'name');

	$templateIds = array();
	foreach ($templates as $template) {
		$templateIds[$template['templateid']] = $template['templateid'];

		$templateComboBox->addItem($template['templateid'], $template['name']);
	}

	// filter trigger
	$triggerComboBox = new CComboBox('tpl_triggerid', getRequest('tpl_triggerid', 0), 'javascript: submit()');
	$triggerComboBox->addItem(0, _('all'));

	$sqlCondition = empty($_REQUEST['filter_hostid'])
		? ' AND '.dbConditionInt('h.hostid', $templateIds)
		: ' AND h.hostid='.zbx_dbstr($_REQUEST['filter_hostid']);

	$sql =
		'SELECT DISTINCT t.triggerid,t.description,h.name'.
		' FROM triggers t,hosts h,items i,functions f'.
		' WHERE f.itemid=i.itemid'.
			' AND h.hostid=i.hostid'.
			' AND t.status='.TRIGGER_STATUS_ENABLED.
			' AND t.triggerid=f.triggerid'.
			' AND h.status='.HOST_STATUS_TEMPLATE.
			' AND i.status='.ITEM_STATUS_ACTIVE.
				$sqlCondition.
		' ORDER BY t.description';
	$triggers = DBfetchArrayAssoc(DBselect($sql), 'triggerid');

	foreach ($triggers as $trigger) {
		$templateName = empty($_REQUEST['filter_hostid']) ? $trigger['name'].NAME_DELIMITER : '';

		$triggerComboBox->addItem($trigger['triggerid'], $templateName.$trigger['description']);
	}

	/*if (isset($_REQUEST['tpl_triggerid']) && !isset($triggers[$_REQUEST['tpl_triggerid']])) {
		unset($triggerOptions['filter']['templateid']);
	}*/
}

/*
 * Form
 */

$reportWidget = new CWidget();
$reportWidget->addPageHeader(
	_('Generate Report\'s').SPACE.'['.zbx_date2str(DATE_TIME_FORMAT_SECONDS).']');
$reportWidget->addHeader(_('Generate Report\'s'));
$reportWidget->addHeaderRowNumber();

$timeSinceRow = createDateSelector('filter_timesince', $_REQUEST['filter_timesince']);

$filterPeriodTable = new CTable(null, 'calendar');
$filterPeriodTable->addRow($timeSinceRow);


$filterForm = new CFormTable();
$filterForm->setTableClass('formtable old-filter');
$filterForm->setAttribute('name', 'zbx_filter');
$filterForm->setAttribute('id', 'zbx_filter');
$filterForm->addVar('filter_timesince', date(TIMESTAMP_FORMAT, $_REQUEST['filter_timesince']));
$filterForm->addRow(_('Тип отчета'),$cmbAction);
$filterForm->addRow(_('Тип файла'),$cmbFile);
if(getRequest('file')=='pdf')
{
	$cmbOrient = new CComboBox('orient', $_REQUEST['orient'], 'submit()');
	$cmbOrient->addItem('Portrait', _('Книжная'));
	$cmbOrient->addItem('Landscape', _('Альбомная'));
	$filterForm->addRow(_('Ориентация листа'),$cmbOrient);
}
$filterForm->addRow(_('Период отчета'),$cmbPeriod);
$filterForm->addRow(_('Начало'), $filterPeriodTable);

if(getRequest('action')=='events')
{
	$filterForm->addRow(_('Группа'),$pageFilter->getGroupsCB());
	$filterForm->addRow(_('Узел'),$pageFilter->getHostsCB());
}
if(getRequest('action')=='report2')
{
	$filterForm->addRow(_('Группа'),$pageFilter->getGroupsCB());
	$filterForm->addRow(_('Template group'), $groupsComboBox);
	$filterForm->addRow(_('Template'), $templateComboBox);
	$filterForm->addRow(_('Template trigger'), $triggerComboBox);
}
if(getRequest('action')=='screens')
{
	$cmbScreen = new CComboBox('elementid', getRequest('elementid'), 'submit()');
	foreach ($data['screens'] as $screen) 
	{
		$cmbScreen->addItem($screen['screenid'], $screen['name']);
	}
	$filterForm->addRow(_('Комплексный экран'), $cmbScreen);
}
$filterForm->addItemToBottomRow(new CSubmit('create_rep',_('Создать отчет')));
$filterForm->addItemToBottomRow(new CSubmit('filter_rst', _('Reset')));

if(getRequest('create_rep'))
{
	$html_address="?action=".getRequest('action')."&period=".getRequest('period')."&groupid=".getRequest('groupid')."&hostid=".getRequest('hostid')."&fullscreen=1&tpl_triggerid=".getRequest('tpl_triggerid')."&hostgroupid=".getRequest('hostgroupid')."&filter_timesince=".getRequest('filter_timesince');
	//echo "<a href=dreport.php$html_address>Скачать отчет</a>";
	header("Location:dreport.php$html_address");
}
$reportWidget->addFlicker($filterForm, CProfile::get('web.ts_report.filter.state', 0));
$reportWidget->addItem(BR());

// output
require_once dirname(__FILE__).'/include/page_header.php';
$reportWidget->show();
require_once dirname(__FILE__).'/include/page_footer.php';

?>