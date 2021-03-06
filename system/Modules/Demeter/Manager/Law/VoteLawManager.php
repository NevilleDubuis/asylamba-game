<?php

/**
 * VoteLawLaw Manager
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @package Demeter
 * @update 29.09.14
*/
namespace Asylamba\Modules\Demeter\Manager\Law;

use Asylamba\Classes\Worker\Manager;
use Asylamba\Classes\Library\Utils;
use Asylamba\Classes\Database\Database;
use Asylamba\Modules\Demeter\Model\Law\VoteLaw;

class VoteLawManager extends Manager {
	protected $managerType ='_VoteLaw';

	public function load($where = array(), $order = array(), $limit = array()) {
		$formatWhere = Utils::arrayToWhere($where, 'v.');
		$formatOrder = Utils::arrayToOrder($order);
		$formatLimit = Utils::arrayToLimit($limit);

		$db = Database::getInstance();
		$qr = $db->prepare('SELECT v.*
			FROM voteLaw AS v
			' . $formatWhere .'
			' . $formatOrder .'
			' . $formatLimit
		);

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

		$aw = $qr->fetchAll();
		$qr->closeCursor();

		foreach($aw AS $awVoteLaw) {
			$voteLaw = new VoteLaw();

			$voteLaw->id = $awVoteLaw['id'];
			$voteLaw->rLaw = $awVoteLaw['rLaw'];
			$voteLaw->rPlayer = $awVoteLaw['rPlayer'];
			$voteLaw->vote = $awVoteLaw['vote'];
			$voteLaw->dVotation = $awVoteLaw['dVotation'];

			$this->_Add($voteLaw);
		}
	}

	public function save() {
		$db = Database::getInstance();

		$voteLaws = $this->_Save();

	foreach ($voteLaws AS $voteLaw) {

		$qr = $db->prepare('UPDATE voteLaw
			SET
				rLaw = ?,
				rPlayer = ?,
				vote = ?,
				dVotation = ?
			WHERE id = ?');
		$aw = $qr->execute(array(
				$voteLaw->rLaw,
				$voteLaw->rPlayer,
				$voteLaw->vote,
				$voteLaw->dVotation,
				$voteLaw->id

			));
		}
	}

	public function add($newVoteLaw) {
		$db = Database::getInstance();
		$qr = $db->prepare('INSERT INTO voteLaw
			SET
				rLaw = ?,
				rPlayer = ?,
				vote = ?,
				dVotation = ?');

		$aw = $qr->execute(array(
			$newVoteLaw->rLaw,
			$newVoteLaw->rPlayer,
			$newVoteLaw->vote,
			$newVoteLaw->dVotation
		));

		$newVoteLaw->id = $db->lastInsertId();

		$this->_Add($newVoteLaw);

		return $newVoteLaw->id;
	}

	public function deleteById($id) {
		$db = Database::getInstance();
		$qr = $db->prepare('DELETE FROM voteLaw WHERE id = ?');
		$qr->execute(array($id));

		$this->_Remove($id);
		return TRUE;
	}
}
