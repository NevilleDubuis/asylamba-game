<?php

use Asylamba\Classes\Worker\CTR;
use Asylamba\Classes\Worker\ASM;
use Asylamba\Classes\Container\Params;
use Asylamba\Classes\Library\Format;
use Asylamba\Modules\Ares\Model\Report;

# background paralax
echo '<div id="background-paralax" class="fleet"></div>';

# inclusion des elements
include 'fleetElement/subnav.php';
include 'defaultElement/movers.php';

# contenu spécifique
echo '<div id="content">';
	include COMPONENT . 'publicity.php';

	if (!CTR::$get->exist('view') OR CTR::$get->get('view') == 'movement' OR CTR::$get->get('view') == 'main') {
		$S_COM_UKN = ASM::$com->getCurrentSession();

		# set d'orbitale base
		$obsets = array(); $j = 0;
		for ($i = 0; $i < CTR::$data->get('playerBase')->get('ob')->size(); $i++) {
			if (Params::check(Params::LIST_ALL_FLEET) || CTR::$data->get('playerBase')->get('ob')->get($i)->get('id') == CTR::$data->get('playerParams')->get('base')) {
				$obsets[$j] = array();

				$obsets[$j]['info'] = array();
				$obsets[$j]['fleets'] = array();

				$obsets[$j]['info']['id'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('id');
				$obsets[$j]['info']['name'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('name');
				$obsets[$j]['info']['type'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('type');
				$obsets[$j]['info']['img'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('img');

				$j++;
			}
		}

		# commander manager : incoming attack
		$commandersId = array(0);
		for ($i = 0; $i < CTR::$data->get('playerEvent')->size(); $i++) {
			if (CTR::$data->get('playerEvent')->get($i)->get('eventType') == EVENT_INCOMING_ATTACK) {
				if (CTR::$data->get('playerEvent')->get($i)->get('eventInfo')->size() > 0) {
					$commandersId[] = CTR::$data->get('playerEvent')->get($i)->get('eventId');
				}
			}
		}

		$S_COM_ATK = ASM::$com->newSession();
		ASM::$com->load(array('c.id' => $commandersId));

		for ($i = 0; $i < count($obsets); $i++) {
			for ($j = 0; $j < ASM::$com->size(); $j++) {
				if (ASM::$com->get($j)->rDestinationPlace == $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = ASM::$com->get($j);
				}
			}
		}
		
		# commander manager : yours
		$S_COM_BSE = ASM::$com->newSession();
		ASM::$com->load(array('c.rPlayer' => CTR::$data->get('playerId'), 'c.statement' => array(COM_AFFECTED, COM_MOVING)), array('c.rBase', 'DESC'));

		for ($i = 0; $i < count($obsets); $i++) {
			for ($j = 0; $j < ASM::$com->size(); $j++) {
				if (ASM::$com->get($j)->rBase == $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = ASM::$com->get($j);
				}
			}
		}

		include COMPONENT . 'fleet/listFleet.php';

		# commander id
		if (CTR::$get->exist('commander')) {
			$S_COM_ID = ASM::$com->getCurrentSession();
			ASM::$com->newSession();
			ASM::$com->load(array(
				'c.rPlayer' => CTR::$data->get('playerId'),
				'c.id' => CTR::$get->get('commander'),
				'c.statement' => array(COM_AFFECTED, COM_MOVING)
			));

			if (ASM::$com->size() == 1) {
				$S_OBM_DOCK = ASM::$obm->getCurrentSession();
				ASM::$obm->newSession();
				ASM::$obm->load(array('rPlace' => ASM::$com->get()->getRBase()));

				# commanderDetail component
				$commander_commanderDetail = ASM::$com->get();
				$commander_commanderFleet = ASM::$com->get();
				$ob_commanderFleet = ASM::$obm->get();
				
				# commanderFleet component
				include COMPONENT . 'fleet/commanderFleet.php';
				include COMPONENT . 'fleet/commanderDetail.php';

				ASM::$com->changeSession($S_COM_ID);
				ASM::$obm->changeSession($S_OBM_DOCK);
			} else {
				CTR::$alert->add('Cet officier ne vous appartient pas ou n\'existe pas');
				CTR::redirect('fleet');
			}
		}

		ASM::$com->changeSession($S_COM_UKN);
	} elseif (CTR::$get->get('view') == 'overview') {
		$S_COM_UKN = ASM::$com->getCurrentSession();
		$S_OBM_UKN = ASM::$obm->getCurrentSession();

		# set d'orbitale base
		$obsets = [];
		for ($i = 0; $i < CTR::$data->get('playerBase')->get('ob')->size(); $i++) {
			$obsets[$i] = array();

			$obsets[$i]['info'] = [];
			$obsets[$i]['fleets'] = [];
			$obsets[$i]['dock'] = [];

			$obsets[$i]['info']['id'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('id');
			$obsets[$i]['info']['name'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('name');
			$obsets[$i]['info']['type'] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('type');
		}

		# commander manager : yours
		ASM::$com->newSession();
		ASM::$com->load(['c.rPlayer' => CTR::$data->get('playerId'), 'c.statement' => [COM_AFFECTED, COM_MOVING]], ['c.rBase', 'DESC']);

		for ($i = 0; $i < count($obsets); $i++) {
			for ($j = 0; $j < ASM::$com->size(); $j++) {
				if (ASM::$com->get($j)->rBase == $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = ASM::$com->get($j);
				}
			}
		}

		# ship in dock
		ASM::$obm->newSession();
		ASM::$obm->load(['rPlayer' => CTR::$data->get('playerId')]);

		for ($i = 0; $i < count($obsets); $i++) {
			for ($j = 0; $j < ASM::$obm->size(); $j++) {
				if (ASM::$obm->get($j)->rPlace == $obsets[$i]['info']['id']) {
					$obsets[$i]['dock'] = ASM::$obm->get($j)->shipStorage;
				}
			}
		}

		include COMPONENT . 'fleet/overview.php';

		ASM::$obm->changeSession($S_OBM_UKN);
		ASM::$com->changeSession($S_COM_UKN);
	} elseif (CTR::$get->get('view') == 'spyreport') {
		# loading des objets
		$S_SRM1 = ASM::$srm->getCurrentSession();
		ASM::$srm->newSession();
		ASM::$srm->load(array('rPlayer' => CTR::$data->get('playerId')), array('dSpying', 'DESC'), array(0, 40));

		# listReport component
		$spyreport_listSpy = array();
		for ($i = 0; $i < ASM::$srm->size(); $i++) { 
			$spyreport_listSpy[$i] = ASM::$srm->get($i);
		}
		include COMPONENT . 'fleet/listSpy.php';

		# report component
		ASM::$srm->newSession();

		if (CTR::$get->exist('report')) {
			ASM::$srm->load(array('id' => CTR::$get->get('report'), 'rPlayer' => CTR::$data->get('playerId')));
		} else {
			ASM::$srm->load(array('rPlayer' => CTR::$data->get('playerId')), array('dSpying', 'DESC'), array(0, 1));
		}

		if (ASM::$srm->size() == 1) {
			$spyreport = ASM::$srm->get(0);

			$S_PLM_SPY = ASM::$plm->getCurrentSession();
			ASM::$plm->newSession();
			ASM::$plm->load(array('id' => $spyreport->rPlace));
			$place_spy = ASM::$plm->get(0);

			include COMPONENT . 'fleet/spyReport.php';

			ASM::$plm->changeSession($S_PLM_SPY);
		} else {
			if (CTR::$get->exist('report')) {
				CTR::$alert->add('Ce rapport ne vous appartient pas ou n\'existe pas');
				CTR::redirect('fleet/view-spyreport');
			} else {
				include COMPONENT . 'default.php';
				include COMPONENT . 'default.php';
			}
		}

		ASM::$srm->changeSession($S_SRM1);
	} elseif (CTR::$get->get('view') == 'archive') {
		# loading des objets
		$S_LRM1 = ASM::$lrm->getCurrentSession();
		ASM::$lrm->newSession();

		if (CTR::$get->get('mode', 'archived')) {
			$archived = Report::ARCHIVED;
		} else {
			$archived = Report::STANDARD;
		}

		$rebels = Params::check(Params::SHOW_REBEL_REPORT)
			? NULL
			: 'AND p2.rColor != 0';

		if (Params::check(Params::SHOW_ATTACK_REPORT)) {
			ASM::$lrm->loadByRequest(
				'WHERE rPlayerAttacker = ? AND statementAttacker = ? ' . $rebels . ' ORDER BY dFight DESC LIMIT 0, 50',
				[CTR::$data->get('playerId'), $archived]
			);
		} else {
			ASM::$lrm->loadByRequest(
				'WHERE rPlayerDefender = ? AND statementDefender = ? ' . $rebels . ' ORDER BY dFight DESC LIMIT 0, 50',
				[CTR::$data->get('playerId'), $archived]
			);
		}

		# listReport component
		$report_listReport = array();
		for ($i = 0; $i < ASM::$lrm->size(); $i++) { 
			$report_listReport[$i] = ASM::$lrm->get($i);
		}
		$type_listReport = 1;
		include COMPONENT . 'fleet/list-report.php';

		# report component
		if (CTR::$get->exist('report')) {
			$S_RPM2 = ASM::$rpm->getCurrentSession();
			ASM::$rpm->newSession();
			ASM::$rpm->load(array('r.id' => CTR::$get->get('report')));

			if (ASM::$rpm->size() == 1 && (ASM::$rpm->get()->rPlayerAttacker == CTR::$data->get('playerId') || ASM::$rpm->get()->rPlayerDefender == CTR::$data->get('playerId'))) {
				$S_PAM1 = ASM::$pam->getCurrentSession();
				ASM::$pam->newSession();
				ASM::$pam->load(array('id' => array(ASM::$rpm->get()->rPlayerAttacker, ASM::$rpm->get()->rPlayerDefender)));

				$report_report = ASM::$rpm->get();

				$attacker_report = ASM::$pam->getById($report_report->rPlayerAttacker);
				$defender_report = ASM::$pam->getById($report_report->rPlayerDefender);

				include COMPONENT . 'fleet/report.php';
				include COMPONENT . 'fleet/manage-report.php';

				ASM::$pam->changeSession($S_PAM1);
			} else {
				CTR::$alert->add('Ce rapport ne vous appartient pas ou n\'existe pas');
				CTR::redirect('fleet/view-archive');
			}

			ASM::$rpm->changeSession($S_RPM2);
		} else {
			include COMPONENT . 'default.php';
			include COMPONENT . 'default.php';
		}

		ASM::$lrm->changeSession($S_LRM1);
	} elseif (CTR::$get->get('view') == 'memorial') {
		# loading des objets
		$S_COM1 = ASM::$com->getCurrentSession();
		ASM::$com->newSession();
		ASM::$com->load(array('c.rPlayer' => CTR::$data->get('playerId'), 'c.statement' => COM_DEAD), array('c.palmares', 'DESC'));

		# memorialTxt component
		include COMPONENT . 'fleet/memorialTxt.php';

		for ($i = 0; $i < ASM::$com->size(); $i++) {
			if ($i < 6) {
				$commander_commanderDetail = ASM::$com->get($i);
				include COMPONENT . 'fleet/commanderDetail.php';
			} else {
				$commander_shortMemorial = ASM::$com->get($i);
				include COMPONENT . 'default.php';
			}
		}

		if (isset($commander_commanderDetail) && count($commander_commanderDetail) > 0) {
		} else {
			include COMPONENT . 'default.php';
			include COMPONENT . 'default.php';
		}

		if (isset($commander_shortMemorial) && count($commander_shortMemorial) > 0) {
		}

		ASM::$com->changeSession($S_COM1);
	} else {
		CTR::redirect('404');
	}

echo '</div>';
