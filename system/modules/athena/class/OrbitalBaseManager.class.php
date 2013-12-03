<?php
include_once HERMES;
include_once ZEUS;

/**
 * Orbital Base Manager
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @package Athena
 * @update 21.06.13
*/

class OrbitalBaseManager extends Manager {
	protected $managerType = '_OrbitalBase';

	public function load($where = array(), $order = array(), $limit = array()) {
		$formatWhere = Utils::arrayToWhere($where, 'ob.');
		$formatOrder = Utils::arrayToOrder($order);
		$formatLimit = Utils::arrayToLimit($limit);

		$db = DataBase::getInstance();
		$qr = $db->prepare('SELECT 
			ob.*,
			p.position AS position,
			p.rSystem AS system,
			s.xPosition AS xSystem,
			s.yPosition AS ySystem,
			s.rSector AS sector,
			se.tax AS tax,
			p.population AS planetPopulation,
			p.coefResources AS planetResources,
			p.coefHistory AS planetHistory,
			(SELECT
				SUM(bq.remainingTime) 
				FROM orbitalBaseBuildingQueue AS bq 
				WHERE bq.rOrbitalBase = ob.rPlace)
				AS remainingTimeGenerator,
			(SELECT 
				SUM(sq1.remainingTime) 
				FROM orbitalBaseShipQueue AS sq1 
				WHERE sq1.rOrbitalBase = ob.rPlace AND sq1.dockType = 1) 
				AS remainingTimeDock1,
			(SELECT 
				SUM(sq2.remainingTime) 
				FROM orbitalBaseShipQueue AS sq2 
				WHERE sq2.rOrbitalBase = ob.rPlace AND sq2.dockType = 2) 
				AS remainingTimeDock2,
			(SELECT 
				SUM(sq3.remainingTime) 
				FROM orbitalBaseShipQueue AS sq3
				WHERE sq3.rOrbitalBase = ob.rPlace AND sq3.dockType = 3) 
				AS remainingTimeDock3,
			(SELECT
				COUNT(cr.id)
				FROM commercialRoute AS cr
				WHERE (cr.rOrbitalBase = ob.rPlace OR cr.rOrbitalBaseLinked = ob.rPlace) AND cr.statement = 1)
				AS routesNumber
			FROM orbitalBase AS ob
			LEFT JOIN place AS p
				ON ob.rPlace = p.id
			LEFT JOIN system AS s
				ON p.rSystem = s.id
			LEFT JOIN sector AS se
				ON s.rSector = se.id
			' . $formatWhere . '
			' . $formatOrder . '
			' . $formatLimit
		);
		foreach($where AS $v) {
			$valuesArray[] = $v;
		}
		if(empty($valuesArray)) {
			$qr->execute();
		} else {
			$qr->execute($valuesArray);
		}
		while($aw = $qr->fetch()) {
			$b = new OrbitalBase();
			$b->setRPlace($aw['rPlace']);
			$b->setRPlayer($aw['rPlayer']);
			$b->setName($aw['name']);
			$b->setLevelGenerator($aw['levelGenerator']);
			$b->setLevelRefinery($aw['levelRefinery']);
			$b->setLevelDock1($aw['levelDock1']);
			$b->setLevelDock2($aw['levelDock2']);
			$b->setLevelDock3($aw['levelDock3']);
			$b->setLevelTechnosphere($aw['levelTechnosphere']);
			$b->setLevelCommercialPlateforme($aw['levelCommercialPlateforme']);
			$b->setLevelGravitationalModule($aw['levelGravitationalModule']);
			$b->setPoints($aw['points']);
			$b->setISchool($aw['iSchool']);
			$b->setIUniversity($aw['iUniversity']);
			$b->setPartNaturalSciences($aw['partNaturalSciences']);
			$b->setPartLifeSciences($aw['partLifeSciences']);
			$b->setPartSocialPoliticalSciences($aw['partSocialPoliticalSciences']);
			$b->setPartInformaticEngineering($aw['partInformaticEngineering']);
			$b->setIAntiSpy($aw['iAntiSpy']);
			$b->setAntiSpyAverage($aw['antiSpyAverage']);
			$b->setShipStorage(0 ,$aw['pegaseStorage']);
			$b->setShipStorage(1 ,$aw['satyreStorage']);
			$b->setShipStorage(2 ,$aw['sireneStorage']);
			$b->setShipStorage(3 ,$aw['dryadeStorage']);
			$b->setShipStorage(4 ,$aw['chimereStorage']);
			$b->setShipStorage(5 ,$aw['meduseStorage']);
			$b->setShipStorage(6 ,$aw['griffonStorage']);
			$b->setShipStorage(7 ,$aw['cyclopeStorage']);
			$b->setShipStorage(8 ,$aw['minotaureStorage']);
			$b->setShipStorage(9 ,$aw['hydreStorage']);
			$b->setShipStorage(10 ,$aw['cerbereStorage']);
			$b->setShipStorage(11 ,$aw['phenixStorage']);
			$b->setMotherShip($aw['motherShip']);
			$b->setIsCommercialBase($aw['isCommercialBase']);
			$b->setIsProductionRefinery($aw['isProductionRefinery']);
			$b->setIsProductionDock1($aw['isProductionDock1']);
			$b->setIsProductionDock2($aw['isProductionDock2']);
			$b->setResourcesStorage($aw['resourcesStorage']);
			$b->setUResources($aw['uResources']);
			$b->setUBuildingQueue($aw['uBuildingQueue']);
			$b->setUShipQueue1($aw['uShipQueue1']);
			$b->setUShipQueue2($aw['uShipQueue2']);
			$b->setUShipQueue3($aw['uShipQueue3']);
			$b->setUTechnoQueue($aw['uTechnoQueue']);
			$b->setUAntiSpy($aw['uAntiSpy']);
			$b->setDCreation($aw['dCreation']);

			$b->setPosition($aw['position']);
			$b->setSystem($aw['system']);
			$b->setXSystem($aw['xSystem']);
			$b->setYSystem($aw['ySystem']);
			$b->setSector($aw['sector']);
			$b->setTax($aw['tax']);
			$b->setPlanetPopulation($aw['planetPopulation']);
			$b->setPlanetResources($aw['planetResources']);
			$b->setPlanetHistory($aw['planetHistory']);

			$b->setRemainingTimeGenerator(round($aw['remainingTimeGenerator'], 1));
			$b->setRemainingTimeDock1(round($aw['remainingTimeDock1'], 1));
			$b->setRemainingTimeDock2(round($aw['remainingTimeDock2'], 1));
			$b->setRemainingTimeDock3(round($aw['remainingTimeDock3'], 1));
			$b->setRoutesNumber($aw['routesNumber']);

			
			// BuildingQueueManager
			$oldBQMSess = ASM::$bqm->getCurrentSession();
			ASM::$bqm->newSession(ASM_UMODE);
			ASM::$bqm->load(array('rOrbitalBase' => $aw['rPlace']), array('position', 'ASC'));
			$b->buildingManager = ASM::$bqm->getCurrentSession();
			$size = ASM::$bqm->size();

			$realGeneratorLevel = $aw['levelGenerator'];
			$realRefineryLevel = $aw['levelRefinery'];
			$realDock1Level = $aw['levelDock1'];
			$realDock2Level = $aw['levelDock2'];
			$realDock3Level = $aw['levelDock3'];
			$realTechnosphereLevel = $aw['levelTechnosphere'];
			$realCommercialPlateformeLevel = $aw['levelCommercialPlateforme'];
			$realGravitationalModuleLevel = $aw['levelGravitationalModule'];
			for ($i = 0; $i < $size; $i++) {
				switch (ASM::$bqm->get($i)->getBuildingNumber()) {
					case 0 :
						$realGeneratorLevel++;
						break;
					case 1 :
						$realRefineryLevel++;
						break;
					case 2 :
						$realDock1Level++;
						break;
					case 3 :
						$realDock2Level++;
						break;
					case 4 :
						$realDock3Level++;
						break;
					case 5 :
						$realTechnosphereLevel++;
						break;
					case 6 :
						$realCommercialPlateformeLevel++;
						break;
					case 7 :
						$realGravitationalModuleLevel++;
						break;
					default :
						CTR::$alert->add('Erreur dans la base de données');
						CTR::$alert->add('dans load() de OrbitalBaseManager', ALT_BUG_ERROR);
				}
			}
			$b->setRealGeneratorLevel($realGeneratorLevel);
			$b->setRealRefineryLevel($realRefineryLevel);
			$b->setRealDock1Level($realDock1Level);
			$b->setRealDock2Level($realDock2Level);
			$b->setRealDock3Level($realDock3Level);
			$b->setRealTechnosphereLevel($realTechnosphereLevel);
			$b->setRealCommercialPlateformeLevel($realCommercialPlateformeLevel);
			$b->setRealGravitationalModuleLevel($realGravitationalModuleLevel);
			ASM::$bqm->changeSession($oldBQMSess);

			// ShipQueueManager
			$S_SQM1 = ASM::$sqm->getCurrentSession();
			ASM::$sqm->newSession(ASM_UMODE);
			ASM::$sqm->load(array('rOrbitalBase' => $aw['rPlace'], 'dockType' => 1), array('position'));
			$b->dock1Manager = ASM::$sqm->getCurrentSession();
			ASM::$sqm->newSession(ASM_UMODE);
			ASM::$sqm->load(array('rOrbitalBase' => $aw['rPlace'], 'dockType' => 2), array('position'));
			$b->dock2Manager = ASM::$sqm->getCurrentSession();
			ASM::$sqm->newSession(ASM_UMODE);
			ASM::$sqm->load(array('rOrbitalBase' => $aw['rPlace'], 'dockType' => 3), array('position'));
			$b->dock3Manager = ASM::$sqm->getCurrentSession();
			ASM::$sqm->changeSession($S_SQM1);

			// CommercialRouteManager
			$S_CRM1 = ASM::$crm->getCurrentSession();
			ASM::$crm->newSession(ASM_UMODE);
			ASM::$crm->load(array('rOrbitalBase' => $aw['rPlace']));
			ASM::$crm->load(array('rOrbitalBaseLinked' => $aw['rPlace']));
			$b->routeManager = ASM::$crm->getCurrentSession();
			ASM::$crm->changeSession($S_CRM1);

			// TechnologyQueueManager
			include_once PROMETHEE;
			$S_TQM1 = ASM::$tqm->getCurrentSession();
			ASM::$tqm->newSession(ASM_UMODE);
			ASM::$tqm->load(array('rPlace' => $aw['rPlace']));
			$b->technoQueueManager = ASM::$tqm->getCurrentSession();
			ASM::$tqm->changeSession($S_TQM1);

			$currentB = $this->_Add($b);

			// U mechanism
			if ($this->currentSession->getUMode()) {
				// nouvelle session de PlayerManager puis restauration
				$S_PAM1 = ASM::$pam->getCurrentSession();
				ASM::$pam->newSession();
				ASM::$pam->load(array('id' => $currentB->getRPlayer()));
				$p = ASM::$pam->get();
				ASM::$pam->changeSession($S_PAM1);

				$now = Utils::now();
				$currentB->uBuildingQueue($now, $p);
				$currentB->uShipQueue1($now, $p);
				$currentB->uShipQueue2($now, $p);
				$currentB->uTechnologyQueue($now, $p);
				$currentB->uAntiSpy($now);

				$currentB->uResources($now);
			}
		}
	}

	public function add(OrbitalBase $b) {
		$db = DataBase::getInstance();
		$qr = $db->prepare('INSERT INTO
			orbitalBase(rPlace, rPlayer, name, levelGenerator, levelRefinery, levelDock1, levelDock2, levelDock3, levelTechnosphere, levelCommercialPlateforme, levelGravitationalModule, points,
				iSchool, iUniversity, partNaturalSciences, partLifeSciences, partSocialPoliticalSciences, partInformaticEngineering, iAntiSpy, antiSpyAverage, 
				pegaseStorage, satyreStorage, sireneStorage, dryadeStorage, chimereStorage, meduseStorage, griffonStorage, cyclopeStorage, minotaureStorage, hydreStorage, cerbereStorage, phenixStorage,
				motherShip, isCommercialBase, isProductionRefinery, isProductionDock1, isProductionDock2, resourcesStorage, uResources, uBuildingQueue, uShipQueue1, uShipQueue2, uShipQueue3, uTechnoQueue, uAntiSpy, dCreation)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  
				?, ?, ?, ?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$qr->execute(array(
			$b->getRPlace(),
			$b->getRPlayer(),
			$b->getName(),
			$b->getLevelGenerator(),
			$b->getLevelRefinery(),
			$b->getLevelDock1(),
			$b->getLevelDock2(),
			$b->getLevelDock3(),
			$b->getLevelTechnosphere(),
			$b->getLevelCommercialPlateforme(),
			$b->getLevelGravitationalModule(),
			$b->getPoints(),

			$b->getISchool(),
			$b->getIUniversity(),
			$b->getPartNaturalSciences(),
			$b->getPartLifeSciences(),
			$b->getPartSocialPoliticalSciences(),
			$b->getPartInformaticEngineering(),
			$b->getIAntiSpy(),
			$b->getAntiSpyAverage(),

			$b->getShipStorage(0),
			$b->getShipStorage(1),
			$b->getShipStorage(2),
			$b->getShipStorage(3),
			$b->getShipStorage(4),
			$b->getShipStorage(5),
			$b->getShipStorage(6),
			$b->getShipStorage(7),
			$b->getShipStorage(8),
			$b->getShipStorage(9),
			$b->getShipStorage(10),
			$b->getShipStorage(11),
			
			$b->getMotherShip(),
			$b->getIsCommercialBase(),
			$b->getIsProductionRefinery(),
			$b->getIsProductionDock1(),
			$b->getIsProductionDock2(),
			$b->getResourcesStorage(),
			$b->getUResources(),
			$b->getUBuildingQueue(),
			$b->getUShipQueue1(),
			$b->getUShipQueue2(),
			$b->getUShipQueue3(),
			$b->getUTechnoQueue(),
			$b->getUAntiSpy(),
			$b->getDCreation()
		));
		$b->setId($db->lastInsertId());
		$this->_Add($b);
	}

	public function save() {
		$bases = $this->_Save();
		foreach ($bases AS $k => $b) {
			$db = DataBase::getInstance();
			$qr = $db->prepare('UPDATE orbitalBase
				SET	rPlace = ?, rPlayer = ?, name = ?, levelGenerator = ?, levelRefinery = ?, levelDock1 = ?, levelDock2 = ?, levelDock3 = ?, levelTechnosphere = ?, levelCommercialPlateforme = ?, levelGravitationalModule = ?, points = ?,
			iSchool = ?, iUniversity = ?, partNaturalSciences = ?, partLifeSciences = ?, partSocialPoliticalSciences = ?, partInformaticEngineering = ?, iAntiSpy = ?, antiSpyAverage = ?,
			pegaseStorage = ?, satyreStorage = ?, sireneStorage = ?, dryadeStorage = ?, chimereStorage = ?, meduseStorage = ?, griffonStorage = ?, cyclopeStorage = ?, minotaureStorage = ?, hydreStorage = ?, cerbereStorage = ?, phenixStorage = ?,
			motherShip = ?, isCommercialBase = ?, isProductionRefinery = ?, isProductionDock1 = ?, isProductionDock2 = ?, resourcesStorage = ?, uResources = ?, uBuildingQueue = ?, uShipQueue1 = ?, uShipQueue2 = ?, uShipQueue3 = ?, uTechnoQueue = ?, uAntiSpy = ?, dCreation = ?
				WHERE rPlace = ?');
			$qr->execute(array(
				$b->getRPlace(),
				$b->getRPlayer(),
				$b->getName(),
				$b->getLevelGenerator(),
				$b->getLevelRefinery(),
				$b->getLevelDock1(),
				$b->getLevelDock2(),
				$b->getLevelDock3(),
				$b->getLevelTechnosphere(),
				$b->getLevelCommercialPlateforme(),
				$b->getLevelGravitationalModule(),
				$b->getPoints(),
				$b->getISchool(),
				$b->getIUniversity(),
				$b->getPartNaturalSciences(),
				$b->getPartLifeSciences(),
				$b->getPartSocialPoliticalSciences(),
				$b->getPartInformaticEngineering(),
				$b->getIAntiSpy(),
				$b->getAntiSpyAverage(),
				$b->getShipStorage(0),
				$b->getShipStorage(1),
				$b->getShipStorage(2),
				$b->getShipStorage(3),
				$b->getShipStorage(4),
				$b->getShipStorage(5),
				$b->getShipStorage(6),
				$b->getShipStorage(7),
				$b->getShipStorage(8),
				$b->getShipStorage(9),
				$b->getShipStorage(10),
				$b->getShipStorage(11),
				$b->getMotherShip(),
				$b->getIsCommercialBase(),
				$b->getIsProductionRefinery(),
				$b->getIsProductionDock1(),
				$b->getIsProductionDock2(),
				$b->getResourcesStorage(),
				$b->getUResources(),
				$b->getUBuildingQueue(),
				$b->getUShipQueue1(),
				$b->getUShipQueue2(),
				$b->getUShipQueue3(),
				$b->getUTechnoQueue(),
				$b->getUAntiSpy(),
				$b->getDCreation(),
				$b->getRPlace()
			));
		}
	}

	public function changeOwnerById($id, $newOwner) {
		$S_OBM1 = ASM::$obm->getCurrentSession();
		ASM::$obm->newSession(ASM_UMODE);
		ASM::$obm->load(array('rPlace' => $id));
		$base = ASM::$obm->get();

		if (isset($base)) {
			# attribuer le rPlayer à la Place
			$S_PLM1 = ASM::$plm->getCurrentSession();
			ASM::$plm->newSession(ASM_UMODE);
			ASM::$plm->load(array('id' => $id));
			ASM::$plm->get()->setRPlayer($newOwner);
			ASM::$plm->changeSession($S_PLM1);

			# attribuer le rPlayer à la Base
			$base->setRPlayer($newOwner);

			# suppression des routes commerciales
			$S_CRM1 = ASM::$crm->getCurrentSession();
			ASM::$crm->newSession(ASM_UMODE);
			ASM::$crm->load(array('rOrbitalBase' => $base->getRPlace()));
			ASM::$crm->load(array('rOrbitalBaseLinked' => $base->getRPlace()));
			for ($i = 0; $i < ASM::$crm->size(); $i++) { 
				ASM::$crm->deleteById(ASM::$crm->get($i)->getId());
				# envoyer une notif
			}
			ASM::$crm->changeSession($S_CRM1);

			# ajoutet/enlever la base dans le controller
			if (CTR::$data->get('playerId') == $newOwner) {
				CTRHelper::addBase('ob', $base->getId(), $base->getName(), $base->getSector(), $base->getSystem());
			} else {
				CTRHelper::removeBase('ob', $base->getId());
			}

			# changer l'allégeance des commandants dans l'école
			$S_COM1 = ASM::$com->getCurrentSession();
			ASM::$com->newSession(ASM_UMODE);
			ASM::$com->load(array('rBase' => $id, 'statement' => COM_INSCHOOL));
			for ($i = 0; $i < ASM::$com->size(); $i++) { 
				ASM::$com->get($i)->setRPlayer($newOwner);
				bug::pre(ASM::$com->get($i));
			}
			ASM::$com->changeSession($S_COM1);

			# rendre déserteuses les flottes en voyage
			$S_COM2 = ASM::$com->getCurrentSession();
			ASM::$com->newSession(ASM_UMODE);
			ASM::$com->load(array('rBase' => $id, 'statement' => COM_MOVING));
			for ($i = 0; $i < ASM::$com->size(); $i++) { 
				ASM::$com->get($i)->setStatement(COM_DESERT);
			}
			ASM::$com->changeSession($S_COM2);

			# applique en cascade le changement de couleur des sytèmes
			GalaxyColorManager::apply();

		} else {
			CTR::$alert->add('Cette base orbitale n\'exite pas !', ALERT_BUG_INFO);
			CTR::$alert->add('dans changeOwnerById de OrbitalBaseManager', ALERT_BUG_ERROR);

		}
		ASM::$obm->changeSession($S_OBM1);
	}
}
?>