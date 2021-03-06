<?php

/**
 * Place Manager
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @package Gaia
 * @update 20.05.13
*/
namespace Asylamba\Modules\Gaia\Manager;

use Asylamba\Classes\Worker\CTR;
use Asylamba\Classes\Worker\ASM;
use Asylamba\Classes\Worker\Manager;
use Asylamba\Classes\Library\Utils;
use Asylamba\Classes\Database\Database;

use Asylamba\Modules\Gaia\Model\Place;

class PlaceManager extends Manager {
	protected $managerType = '_Place';
	
	public function load($where = array(), $order = array(), $limit = array()) {
		$formatWhere = Utils::arrayToWhere($where, 'p.');
		$formatOrder = Utils::arrayToOrder($order, 'p.');
		$formatLimit = Utils::arrayToLimit($limit);

		$db = Database::getInstance();
		$qr = $db->prepare('SELECT p.*,
			s.rSector AS rSector,
			s.xPosition AS xPosition,
			s.yPosition AS yPosition,
			s.typeOfSystem AS typeOfSystem,
			se.tax AS tax,
			se.rColor AS sectorColor,
			pl.rColor AS playerColor,
			pl.name AS playerName,
			pl.avatar AS playerAvatar,
			pl.status AS playerStatus,
			pl.level AS playerLevel,
			ob.rPlace AS obId,
			ob.name AS obName,
			ob.points AS points,
			ob.levelCommercialPlateforme AS levelCommercialPlateforme,
			ob.levelSpatioport AS levelSpatioport,
			ob.resourcesStorage AS obResources,
			ob.antiSpyAverage AS antiSpyAverage,
			ob.typeOfBase AS obTypeOfBase
			FROM place AS p
			LEFT JOIN system AS s
				ON p.rSystem = s.id
				LEFT JOIN sector AS se
					ON s.rSector = se.id
					LEFT JOIN player AS pl
						ON p.rPlayer = pl.id
						LEFT JOIN orbitalBase AS ob
							ON p.id = ob.rPlace
		' . $formatWhere . '
		' . $formatOrder . '
		' . $formatLimit);

		foreach($where AS $v) {
			if (is_array($v)) {
				foreach ($v as $p) {
					$valuesArray[] = $p;
				}
			} else {
				$valuesArray[] = $v;
			}
		}
		
		if (empty($valuesArray)) {
			$qr->execute();
		} else {
			$qr->execute($valuesArray);
		}

		$this->fill($qr);
	}

	public function search($search, $order = array(), $limit = array()) {
		$search = '%' . $search . '%';
		
		$formatOrder = Utils::arrayToOrder($order);
		$formatLimit = Utils::arrayToLimit($limit);

		$db = Database::getInstance();
		$qr = $db->prepare('SELECT p.*,
			s.rSector AS rSector,
			s.xPosition AS xPosition,
			s.yPosition AS yPosition,
			s.typeOfSystem AS typeOfSystem,
			se.tax AS tax,
			se.rColor AS sectorColor,
			pl.rColor AS playerColor,
			pl.name AS playerName,
			pl.avatar AS playerAvatar,
			pl.status AS playerStatus,
			pl.level AS playerLevel,
			ob.rPlace AS obId,
			ob.name AS obName,
			ob.points AS points,
			ob.levelCommercialPlateforme AS levelCommercialPlateforme,
			ob.levelSpatioport AS levelSpatioport,
			ob.resourcesStorage AS obResources,
			ob.antiSpyAverage AS antiSpyAverage,
			ob.typeOfBase AS obTypeOfBase
			FROM place AS p
			LEFT JOIN system AS s
				ON p.rSystem = s.id
				LEFT JOIN sector AS se
					ON s.rSector = se.id
					LEFT JOIN player AS pl
						ON p.rPlayer = pl.id
						LEFT JOIN orbitalBase AS ob
							ON p.id = ob.rPlace
			WHERE (pl.statement = 1 OR pl.statement = 2 OR pl.statement = 3)
			AND (LOWER(pl.name) LIKE LOWER(?)
			OR   LOWER(ob.name) LIKE LOWER(?))			
			' . $formatOrder . '
			' . $formatLimit
		);

		$qr->execute(array($search, $search));

		$this->fill($qr);
	}

	protected function fill($qr) {
		while ($aw = $qr->fetch()) {
			$p = new Place();

			$p->setId($aw['id']);
			$p->setRSystem($aw['rSystem']);
			$p->setTypeOfPlace($aw['typeOfPlace']);
			$p->setPosition($aw['position']);
			$p->setPopulation($aw['population']);
			$p->setCoefResources($aw['coefResources']);
			$p->setCoefHistory($aw['coefHistory']);
			$p->setResources($aw['resources']);
			$p->danger = $aw['danger'];
			$p->maxDanger = $aw['maxDanger'];
			$p->uPlace = $aw['uPlace'];

			$p->setRSector($aw['rSector']);
			$p->setXSystem($aw['xPosition']);
			$p->setYSystem($aw['yPosition']);
			$p->setTypeOfSystem($aw['typeOfSystem']);
			$p->setTax($aw['tax']);
			$p->setSectorColor($aw['sectorColor']);

			if ($aw['rPlayer'] != 0) {
				$p->setRPlayer($aw['rPlayer']);
				$p->setPlayerColor($aw['playerColor']);
				$p->setPlayerName($aw['playerName']);
				$p->setPlayerAvatar($aw['playerAvatar']);
				$p->setPlayerStatus($aw['playerStatus']);
				$p->playerLevel = $aw['playerLevel'];
				if (isset($aw['msId'])) {
					$p->setTypeOfBase($aw['msType']);
					$p->setBaseName($aw['msName']);
					$p->setResources($aw['msResources']);
				} elseif (isset($aw['obId'])) {
					$p->setTypeOfBase(Place::TYP_ORBITALBASE);
					$p->typeOfOrbitalBase = $aw['obTypeOfBase'];
					$p->setBaseName($aw['obName']);
					$p->setLevelCommercialPlateforme($aw['levelCommercialPlateforme']);
					$p->setLevelSpatioport($aw['levelSpatioport']);
					$p->setResources($aw['obResources']);
					$p->setAntiSpyInvest($aw['antiSpyAverage']);
					$p->setPoints($aw['points']);
				} else {
					CTR::$alert->add('Problèmes d\'appartenance du lieu !');
				}
			} else {
				$p->setTypeOfBase(Place::TYP_EMPTY);
				$p->setBaseName('Planète rebelle');
				$p->setPoints(0);
			}

			$S_COM3 = ASM::$com->getCurrentSession();
			ASM::$com->newSession();
			ASM::$com->load(array('c.rBase' => $aw['id'], 'c.statement' => array(1, 2)));

			for ($i = 0; $i < ASM::$com->size(); $i++) { 
				$p->commanders[] = ASM::$com->get($i);
			}
			
			ASM::$com->changeSession($S_COM3);

			$currentP = $this->_Add($p);

			if ($this->currentSession->getUMode()) {
				$currentP->uMethod();
			}
		}
	}

	public static function add(Place $p) {
		$db = Database::getInstance();
		$qr = $db->prepare('INSERT INTO
			place(rPlayer, rSystem, typeOfPlace, position, population, coefResources, coefHistory, resources, danger, maxDanger, uPlace)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$qr->execute(array(
			$p->getRPlayer(),
			$p->getRSystem(),
			$p->getTypeOfPlace(),
			$p->getPosition(),
			$p->getPopulation(),
			$p->getCoefResources(),
			$p->getCoefHistory(),
			$p->getResources(),
			$p->danger,
			$p->maxDanger,
			$p->uPlace
		));

		$p->setId($db->lastInsertId());

		$this->_Add($p);
	}

	public function save() {
		$places = $this->_Save();

		foreach ($places AS $p) {
			$db = Database::getInstance();

			$qr = $db->prepare('UPDATE place
				SET	id = ?,
					rPlayer = ?,
					rSystem = ?,
					typeOfPlace = ?,
					position = ?,
					population = ?,
					coefResources = ?,
					coefHistory = ?,
					resources = ?,
					danger = ?,
					maxDanger = ?,
					uPlace = ?
				WHERE id = ?');
			$qr->execute(array(
				$p->getId(),
				$p->getRPlayer(),
				$p->getRSystem(),
				$p->getTypeOfPlace(),
				$p->getPosition(),
				$p->getPopulation(),
				$p->getCoefResources(),
				$p->getCoefHistory(),
				$p->getResources(),
				$p->danger,
				$p->maxDanger,
				$p->uPlace,
				$p->getId()
			));
		}
	}

	public static function deleteById($id) {
		$db = Database::getInstance();
		$qr = $db->prepare('DELETE FROM place WHERE id = ?');
		$qr->execute(array($id));
		$this->_Remove($id);

		return TRUE;
	}	
}