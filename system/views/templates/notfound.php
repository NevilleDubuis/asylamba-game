<?php
echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
	echo '<title>404 — Expansion</title>';

	echo '<meta charset="utf-8" />';
	echo '<meta name="description" content="' . APP_DESCRIPTION . '" />';
	echo '<link rel="stylesheet" media="screen" type="text/css" href="' . CSS . 'notfound/main.css" />';
echo '</head>';

echo '<body>';
	echo '<div class="container">';
		echo '<h1>404. Not Found.</h1>';
		echo '<h2>Une page se perd dans l\'infini de l\'espace et c\'est le drame.<br/>';
		echo 'Après on fait avec.</h2>';
	echo '</div>';

	echo '<div class="boxlink">';
		echo '<a href="' . APP_ROOT . 'profil">revenir vers la page principale</a>';
		echo '<a href="' . GETOUT_ROOT . '">où allez jeter un oeil au site</a>';
		echo '<a href="' . GETOUT_ROOT . 'blog">où peut-être au blog</a>';

		echo '<a href="' . FACEBOOK_LINK . '">sinon vous pouvez "liker" notre page facebook</a>';
		echo '<a href="' . TWEETER_LINK . '">ou nous suivre vers tweeter</a>';
	echo '</div>';
echo '</body>';
?>