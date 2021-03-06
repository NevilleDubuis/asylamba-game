<?php

use Asylamba\Classes\Worker\ASM;
use Asylamba\Modules\Demeter\Resource\ColorResource;

$S_CTM_T = ASM::$ctm->getCurrentSession();
ASM::$ctm->newSession();
ASM::$ctm->load(array('faction' => $faction->id), array('exportTax', 'ASC'));

echo '<div class="component player rank">';
	echo '<div class="head skin-2"></div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			echo '<h4>Taxes à la vente</h4>';
				for ($i = 0; $i < ASM::$ctm->size(); $i++) {
					$id = ASM::$ctm->get($i)->relatedFaction;

					echo '<div class="player faction color' . $id . '">';
						echo '<a href="' . APP_ROOT . 'embassy/faction-' . $id . '">';
							echo '<img src="' . MEDIA . 'faction/flag/flag-' . $id . '.png" alt="" class="picto">';
						echo '</a>';
						echo '<span class="title">produits ' . ColorResource::getInfo($id, 'demonym') . '</span>';
						echo '<strong class="name">' . ASM::$ctm->get($i)->importTax . ' %</strong>';
					echo '</div>';
				}
		echo '</div>';
	echo '</div>';
echo '</div>';

ASM::$ctm->changeSession($S_CTM_T);