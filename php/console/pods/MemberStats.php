<?php
require_once('BasePod.php');


class MemberStats extends BasePod {
	var $useButtons = true;
	var $useMemberFilters = true;
	var $useSiteFilters = true;

	function __construct($podData) {
		parent::__construct($podData);
		$this->model->db->selectdb('research');
	}

	function render_ajax($view = 'pod') {
		//ob_start();
		require('views/statistics/base.php');
		//$data = ob_end_flush();
		//return $data;
		//return "<h1>Statistics Pod: {$this->podData['body']}</h1>";
		return json_encode(array('html' => $html, 'js' => $js));
	}

	function load($view = 'full') {
		return $this->render_ajax($view);
	}

	function load_statistics($view = 'pod', $params = false) {
		$where = array();
		if (isset($_REQUEST['membersOnly'])) {
			$where[] = 'UserCollectives.isMember = 1';
		}
		if (isset($_REQUEST['teamEligibleOnly'])) {
			$where[] = "UserCollectives.optInStudy = 1 AND UserCollectives.eligibility = 'team'";
		}
		if (isset($_REQUEST['startDate'])) {
			$where[] = "t > '".$_REQUEST['startDate']." 00:00:00'";
			$startLabel = date('l jS \of F Y', strtotime($params['startDate']));
		} else {
			$where[] = "t > '".date("Y-m-d 00:00:00", time())."'";
			$startLabel = date('l jS \of F Y', time());
		}
		if (isset($_REQUEST['endDate'])) {
			$where[] = "t < '".$_REQUEST['endDate']." 23:59:59'";
			$endLabel = date('l jS \of F Y', strtotime($params['endDate']));
		} else {
			$endDate = false;
			$endLabel = 'Now';
		}
		if (isset($_REQUEST['siteid']) && $_REQUEST['siteid'] != 0) {
			$where[] = 'UserCollectives.siteid = "'.$_REQUEST['siteid'].'"';
		}
		if (count($where)) {
			$wherestr = ' WHERE '.join(' AND ', $where);
		} else {
			$wherestr = '';
		}

		$results = $this->model->db->query("SELECT LogDumps.id, action FROM LogDumps LEFT JOIN UserCollectives ON LogDumps.userid1 = UserCollectives.userid $wherestr");

		$totalMembers = 0;
		$actionTypes = array(
			'invite'			=> 'numInvites',
			'signup'		=> 'numSignups',
			'redeemed'		=> 'numPrizesRedeemed',
			'acceptedInvite'		=> 'numAcceptedInvites'
		);
		$counts = array(
			'numInvites'		=> 0,
			'numSignups'		=> 0,
			'numPrizesRedeemed'	=> 0,
			'numAcceptedInvites'	=> 0,
		);
		while (($stat = mysql_fetch_assoc($results)) !== false) {
			if (array_key_exists($stat['action'], $actionTypes)) {
				$totalMembers++;
				$counts[$actionTypes[$stat['action']]] += 1;
			}
		}

		$jsonarr = array('Stats' => array ('Totals' => array()));
		foreach ($counts as $name => $count) {
			$tmp = array();
			$tmp['name'] = $name;
			$tmp['count'] = $count;
			$tmp['label'] = $name;
			$jsonarr['Stats']['Totals'][] = $tmp;
		}
		$jsonarr['Stats']['ActionName'] = "Member Stats $startLabel -- $endLabel";
		$jsonarr['Stats']['ChartType'] = 'pie';
		//$jsonarr['Stats']['UserCounts'] = count($userData);
		$jsonarr['Stats']['TotalMembers'] = $totalMembers;
		//$jsonarr['Stats']['Counts'] = print_r($userData, true);

		return json_encode($jsonarr);
	}
			
}

?>
