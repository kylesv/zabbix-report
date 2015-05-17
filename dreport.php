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

$temp_dir='/var/www/html/zabbix/pdf';

$_REQUEST['groupid'] = getRequest('groupid',CProfile::get('web.ts_report.groupid', 0));
$_REQUEST['hostgroupid'] = getRequest('hostgroupid',CProfile::get('web.ts_report.hostgroupid', 0));
$_REQUEST['hostid'] = getRequest('hostid',CProfile::get('web.ts_report.hostid', 0));
$_REQUEST['action'] = getRequest('action',CProfile::get('web.ts_report.action', 0));
$_REQUEST['elementid'] = getRequest('elementid',CProfile::get('web.ts_report.elementid', 0));
$_REQUEST['period'] = getRequest('period',CProfile::get('web.ts_report.period', 0));
$_REQUEST['file'] = getRequest('file',CProfile::get('web.ts_report.file', 0));
$_REQUEST['orient'] = getRequest('orient',CProfile::get('web.ts_report.orient', 0));
$_REQUEST['filter_timesince'] = getRequest('filter_timesince',CProfile::get('web.ts_report.timesince', 0));
$_REQUEST['tpl_triggerid'] = getRequest('tpl_triggerid',CProfile::get('web.ts_report.tpl_triggerid', 0));
$_REQUEST['filter_timetill'] = getRequest('filter_timesince') + $_REQUEST['period'];
if($_REQUEST['filter_timetill'] > time()){
	$_REQUEST['filter_timetill'] = time();
}
$_REQUEST['stime']  = date(TIMESTAMP_FORMAT, getRequest('filter_timesince'));
$stime =  getRequest('stime');

switch (getRequest('action'))
{
	case "screens":
		$html=$prog_html.getRequest('action').".php?stime=$stime&period=".getRequest('period')."&elementid=".getRequest('elementid')."&fullscreen=1";
		$temp_pdf=$temp_dir."/screens-".getRequest('period')."-$stime.pdf";
		break;
	case "events":
		$html=$prog_html.getRequest('action').".php?stime=$stime&period=".getRequest('period')."&groupid=".getRequest('groupid')."&hostid=".getRequest('hostid')."&fullscreen=1";
		$temp_pdf=$temp_dir."/events-".getRequest('period')."-$stime.pdf";
		break;
	case "report2":
		$html=$prog_html.getRequest('action').".php?mode=1&filter_timesince=".getRequest('filter_timesince')."&filter_timetill=".getRequest('filter_timetill')."&hostgroupid=".getRequest('hostgroupid')."&fullscreen=1&tpl_triggerid=".getRequest('tpl_triggerid');
		$temp_pdf=$temp_dir."/avail-".getRequest('period')."-$stime-".getRequest('tpl_triggerid').".pdf";
		break;
	default:
		$html=$prog_html;
}
if(getRequest('file')=='csv') 
{
	$csvExport = true;
	$csvRows = array();
	
	$page['type'] = detect_page_type(PAGE_TYPE_CSV);
	$page['file'] = getRequest('action')."-".getRequest('period')."-$stime.csv";
	
	require_once dirname(__FILE__).'/include/page_header.php';
	
	if (getRequest('action')=='events')
	{
		$header = array(
		_('Time'),
		(getRequest('hostid', 0) == 0) ? _('Host') : null,
		_('Description'),
		_('Status'),
		_('Severity'),
		_('Duration'),
		$config['event_ack_enable'] ? _('Ack') : null,
		_('Actions'));
		$csvRows[] = $header;
		
		$knownTriggerIds = array();
		$validTriggerIds = array();

		$triggerOptions = array(
			'output' => array('triggerid'),
			'preservekeys' => true,
			'monitored' => true
		);

		$allEventsSliceLimit = 999999;//$config['search_limit'];
		
		$from = $_REQUEST['filter_timesince'];
		$till = $_REQUEST['filter_timetill'];
		
		$eventOptions = array(
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'time_from' => $from,
			'time_till' => $till,
			'output' => array('eventid', 'objectid'),
			'sortfield' => array('clock', 'eventid'),
			'sortorder' => ZBX_SORT_DOWN,
			'limit' => $allEventsSliceLimit + 1
		);

		if ($triggerId) 
		{
			$knownTriggerIds = array($triggerId => $triggerId);
			$validTriggerIds = $knownTriggerIds;

			$eventOptions['objectids'] = array($triggerId);;
		}
		elseif (getRequest('hostid') > 0) 
		{
			$hostTriggers = API::Trigger()->get(array(
				'output' => array('triggerid'),
				'hostids' => getRequest('hostid'),
				'monitored' => true,
				'preservekeys' => true
			));
			$filterTriggerIds = array_map('strval', array_keys($hostTriggers));
			$knownTriggerIds = array_combine($filterTriggerIds, $filterTriggerIds);
			$validTriggerIds = $knownTriggerIds;

			$eventOptions['hostids'] = getRequest('hostid');
			$eventOptions['objectids'] = $validTriggerIds;
		}
		elseif (getRequest('groupid') > 0) 
		{
			$eventOptions['groupids'] = getRequest('groupid');

			$triggerOptions['groupids'] = getRequest('groupid');
		}

		$events = array();

		while (true) 
		{
			$allEventsSlice = API::Event()->get($eventOptions);

			$triggerIdsFromSlice = array_keys(array_flip(zbx_objectValues($allEventsSlice, 'objectid')));

			$unknownTriggerIds = array_diff($triggerIdsFromSlice, $knownTriggerIds);

			if ($unknownTriggerIds) 
			{
				$triggerOptions['triggerids'] = $unknownTriggerIds;
				$validTriggersFromSlice = API::Trigger()->get($triggerOptions);

				foreach ($validTriggersFromSlice as $trigger) {
					$validTriggerIds[$trigger['triggerid']] = $trigger['triggerid'];
				}

				foreach ($unknownTriggerIds as $id) {
					$id = strval($id);
					$knownTriggerIds[$id] = $id;
				}
			}

			foreach ($allEventsSlice as $event) 
			{
				if (isset($validTriggerIds[$event['objectid']])) {
					$events[] = array('eventid' => $event['eventid']);
				}
			}

			// break loop when either enough events have been retrieved, or last slice was not full
			if (count($events) >= $config['search_limit'] || count($allEventsSlice) <= $allEventsSliceLimit) 
			{
				break;
			}

			/*
			 * Because events in slices are sorted descending by eventid (i.e. bigger eventid),
			 * first event in next slice must have eventid that is previous to last eventid in current slice.
			 */
			$lastEvent = end($allEventsSlice);
			$eventOptions['eventid_till'] = $lastEvent['eventid'] - 1;
		}

		/*
		 * At this point it is possible that more than $config['search_limit'] events are selected,
		 * therefore at most only first $config['search_limit'] + 1 events will be used for pagination.
		 */
		$events = array_slice($events, 0, $allEventsSliceLimit);


		// query event with extend data
		$events = API::Event()->get(array(
			'source' => EVENT_SOURCE_TRIGGERS,
			'object' => EVENT_OBJECT_TRIGGER,
			'eventids' => zbx_objectValues($events, 'eventid'),
			'output' => API_OUTPUT_EXTEND,
			'select_acknowledges' => API_OUTPUT_COUNT,
			'sortfield' => array('clock', 'eventid'),
			'sortorder' => ZBX_SORT_DOWN,
			'nopermissions' => true
		));


		$triggers = API::Trigger()->get(array(
			'triggerids' => zbx_objectValues($events, 'objectid'),
			'selectHosts' => array('hostid', 'status'),
			'selectItems' => array('itemid', 'hostid', 'name', 'key_', 'value_type'),
			'output' => array('description', 'expression', 'priority', 'flags', 'url')
		));
		$triggers = zbx_toHash($triggers, 'triggerid');

		// fetch hosts
		$hosts = array();
		foreach ($triggers as $trigger) 
		{
			$hosts[] = reset($trigger['hosts']);
		}
		$hostids = zbx_objectValues($hosts, 'hostid');

		$hosts = API::Host()->get(array(
			'output' => array('name', 'hostid', 'status'),
			'hostids' => $hostids,
			'selectGraphs' => API_OUTPUT_COUNT,
			'selectScreens' => API_OUTPUT_COUNT,
			'preservekeys' => true
		));

		// actions
		$actions = getEventActionsStatus(zbx_objectValues($events, 'eventid'));

		// events
		foreach ($events as $event)
		{
			$trigger = $triggers[$event['objectid']];

			$host = reset($trigger['hosts']);
			$host = $hosts[$host['hostid']];

			$triggerItems = array();

			$trigger['items'] = CMacrosResolverHelper::resolveItemNames($trigger['items']);

			foreach ($trigger['items'] as $item) {
				$triggerItems[] = array(
					'name' => $item['name_expanded'],
					'params' => array(
						'itemid' => $item['itemid'],
						'action' => in_array($item['value_type'], array(ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64))
							? HISTORY_GRAPH : HISTORY_VALUES
					)
				);
			}

			$description = CMacrosResolverHelper::resolveEventDescription(zbx_array_merge($trigger, array(
				'clock' => $event['clock'],
				'ns' => $event['ns']
			)));

			// duration
			$event['duration'] = ($nextEvent = get_next_event($event, $events))
				? zbx_date2age($event['clock'], $nextEvent['clock'])
				: zbx_date2age($event['clock']);

			// action
			$action = isset($actions[$event['eventid']]) ? $actions[$event['eventid']] : ' - ';

			$csvRows[] = array(
				zbx_date2str(DATE_TIME_FORMAT_SECONDS, $event['clock']),
				(getRequest('hostid', 0) == 0) ? $host['name'] : null,
				$description,
				trigger_value2str($event['value']),
				getSeverityCaption($trigger['priority']),
				$event['duration'],
				$config['event_ack_enable'] ? ($event['acknowledges'] ? _('Yes') : _('No')) : null,
				strip_tags((string) $action)
				);
		}
	}
	elseif(getRequest('action')=='report2')
	{
		$triggerOptions = array(
			'output' => array('triggerid', 'description', 'expression', 'value'),
			'expandDescription' => true,
			'monitored' => true,
			'selectHosts' => array('name'),
			'filter' => array(),
			'hostids' => null,
			'limit' => $config['search_limit'] + 1
		);
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
		$triggerTable = new CTableInfo(_('No triggers found.'));
		$header = array(
			_('Узел сети'),
			_('Имя'),
			_('Проблема %'),
			_('ОК %'),
			_('Проблема '),
			_('ОК '),
			_('Всего')
		);
		$csvRows[] = $header;
		$triggers = API::Trigger()->get($triggerOptions);

		CArrayHelper::sort($triggers, array('host', 'description'));

		//$paging = getPagingLine($triggers);

		foreach ($triggers as $trigger) {
			$availability = calculateAvailability($trigger['triggerid'], getRequest('filter_timesince'),
				getRequest('filter_timetill')
			);

			$csvRows[]=array(
				$trigger['hosts'][0]['name'],
				$trigger['description'],
				sprintf('%.4f%%', $availability['true']),
				sprintf('%.4f%%', $availability['false']),
				zbx_date2age(0,$availability['true_time']),
				zbx_date2age(0,$availability['false_time']),
				zbx_date2age(0,$availability['total_time'])
			);
		}
	}
	$text_utf8 = zbx_toCSV($csvRows);
	$text_cp1251 = iconv("UTF-8","CP1251",$text_utf8);
	echo $text_cp1251;
	
}
elseif(getRequest('file')=='pdf')
{
	$prog_page_or="-O ".$_REQUEST['orient'];
	exec("$prog_binpath $prog_page_or '".$html."' '".$temp_pdf."'",$stdout);
	file_force_download_default($temp_pdf);
}
elseif(getRequest('file')=='http')
{
	header("Location: $html");
}
?>
