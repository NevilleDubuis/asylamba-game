<?php
# accept a commercial route action

# int base 			id (rPlace) de la base orbitale qui accepte la route
# int route 		id de la route commerciale

use Asylamba\Classes\Worker\CTR;
use Asylamba\Classes\Worker\ASM;
use Asylamba\Classes\Library\Utils;
use Asylamba\Classes\Library\DataAnalysis;
use Asylamba\Classes\Library\Format;
use Asylamba\Classes\Database\Database;
use Asylamba\Modules\Athena\Resource\OrbitalBaseResource;
use Asylamba\Modules\Demeter\Resource\ColorResource;
use Asylamba\Modules\Hermes\Model\Notification;
use Asylamba\Modules\Demeter\Model\Color;

for ($i=0; $i < CTR::$data->get('playerBase')->get('ob')->size(); $i++) { 
	$verif[] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('id');
}

$base 	= Utils::getHTTPData('base');
$route 	= Utils::getHTTPData('route');


if ($base !== FALSE AND $route !== FALSE AND in_array($base, $verif)) {
	$S_CRM1 = ASM::$crm->getCurrentSession();
	ASM::$crm->newSession();
	ASM::$crm->load(array('id'=>$route, 'rOrbitalBaseLinked' => $base, 'statement' => CRM_PROPOSED));

	if (ASM::$crm->get() && ASM::$crm->size() == 1) {
		$cr = ASM::$crm->get();

		$S_CLM1 = ASM::$clm->getCurrentSession();
		ASM::$clm->newSession();
		ASM::$clm->load(array('id' => array($cr->playerColor1, $cr->playerColor2)));

		if (ASM::$clm->size() == 2) {
			if (ASM::$clm->get(0)->id == $cr->playerColor1) {
				$color1 = ASM::$clm->get(0);
				$color2 = ASM::$clm->get(1);
			} else {
				$color1 = ASM::$clm->get(1);
				$color2 = ASM::$clm->get(0);
			}
		} else {
			$color1 = ASM::$clm->get();
			$color2 = ASM::$clm->get();
		}

		if ($color1->colorLink[$cr->playerColor2] != Color::ENEMY && $color2->colorLink[$cr->playerColor1] != Color::ENEMY) {
			$S_OBM1 = ASM::$obm->getCurrentSession();
			ASM::$obm->newSession();
			ASM::$obm->load(array('rPlace' => $cr->getROrbitalBase()));
			$proposerBase = ASM::$obm->get();

			ASM::$obm->load(array('rPlace' => $cr->getROrbitalBaseLinked()));
			$acceptorBase = ASM::$obm->get(1);

			ASM::$crm->load(array('rOrbitalBase' => $acceptorBase->getId()));
			ASM::$crm->load(array('rOrbitalBaseLinked' => $acceptorBase->getId(), 'statement' => CRM_ACTIVE));
			ASM::$crm->load(array('rOrbitalBaseLinked' => $acceptorBase->getId(), 'statement' => CRM_STANDBY));

			$nbrCommercialRoute = ASM::$crm->size() - 1;
			$nbrMaxCommercialRoute = OrbitalBaseResource::getBuildingInfo(OrbitalBaseResource::SPATIOPORT, 'level', $acceptorBase->getLevelSpatioport(), 'nbRoutesMax'); 
			
			if ($nbrCommercialRoute < $nbrMaxCommercialRoute) {
				# compute bonus if the player is from Negore
				if (CTR::$data->get('playerInfo')->get('color') == ColorResource::NEGORA) {
					$price = round($cr->getPrice() - ($cr->getPrice() * ColorResource::BONUS_NEGORA_ROUTE / 100));
				} else {
					$price = $cr->getPrice();
				}

				if (CTR::$data->get('playerInfo')->get('credit') >= $price) {
					# débit des crédits au joueur
					$S_PAM1 = ASM::$pam->getCurrentSession();
					ASM::$pam->newSession(ASM_UMODE);
					ASM::$pam->load(array('id' => CTR::$data->get('playerId')));
					ASM::$pam->get()->decreaseCredit($price);

					# augmentation de l'expérience des deux joueurs
					$exp = round($cr->getIncome() * CRM_COEFEXPERIENCE);
					ASM::$pam->load(array('id' => $proposerBase->getRPlayer()));
					ASM::$pam->get()->increaseExperience($exp);
					ASM::$pam->get(1)->increaseExperience($exp);
					
					ASM::$pam->changeSession($S_PAM1);
					
					# activation de la route
					$cr->setStatement(CRM_ACTIVE);
					$cr->setDCreation(Utils::now());

					$n = new Notification();
					$n->setRPlayer($proposerBase->getRPlayer());
					$n->setTitle('Route commerciale acceptée');
					$n->addBeg();
					$n->addLnk('embassy/player-' . CTR::$data->get('playerId'), CTR::$data->get('playerInfo')->get('name'))->addTxt(' a accepté la route commerciale proposée entre ');
					$n->addLnk('map/place-' . $acceptorBase->getRPlace(), $acceptorBase->getName())->addTxt(' et ');
					$n->addLnk('map/base-' . $proposerBase->getRPlace(), $proposerBase->getName());
					$n->addSep()->addTxt('Cette route vous rapporte ' . Format::numberFormat($cr->getIncome()) . ' crédits par relève.');
					$n->addBrk()->addBoxResource('xp', $exp, 'expérience gagnée');
					$n->addSep()->addLnk('action/a-switchbase/base-' . $proposerBase->getRPlace() . '/page-spatioport', 'En savoir plus ?');
					$n->addEnd();
					ASM::$ntm->add($n);

					if (DATA_ANALYSIS) {
						$db = Database::getInstance();
						$qr = $db->prepare('INSERT INTO 
							DA_CommercialRelation(`from`, `to`, type, weight, dAction)
							VALUES(?, ?, ?, ?, ?)'
						);
						$qr->execute([$cr->playerId1, $cr->playerId2, 6, DataAnalysis::creditToStdUnit($cr->price), Utils::now()]);
					}

					CTR::$alert->add('Route commerciale acceptée, vous gagnez ' . $exp . ' points d\'expérience', ALERT_STD_SUCCESS);
				} else {
					CTR::$alert->add('impossible d\'accepter une route commerciale', ALERT_STD_ERROR);
				}
			} else {
				CTR::$alert->add('impossible d\'accepter une route commerciale', ALERT_STD_ERROR);
			}
			ASM::$obm->changeSession($S_OBM1);
		} else {
			CTR::$alert->add('Vous ne pouvez pas accepter les routes de ce joueur, vos deux factions sont en guerre', ALERT_STD_ERROR);
		}
		ASM::$clm->changeSession($S_CLM1);
	} else {
		CTR::$alert->add('impossible d\'accepter une route commerciale', ALERT_STD_ERROR);
	}
	ASM::$crm->changeSession($S_CRM1);
} else {
	CTR::$alert->add('pas assez d\'informations pour accepter une route commerciale', ALERT_STD_FILLFORM);
}