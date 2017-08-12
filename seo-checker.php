<?php

function recursiveNav($subpages = null)
{

	$array = array();

	if ($subpages == null) :
		$subpages = site()->pages();
	endif;

	foreach ($subpages AS $p) :

		if (!check($p)) continue;

		$sub = null;

		if ($p->hasChildren()) :

			$result = recursiveNav($p->children());

			if ($result && $result['pages']) {
				$sub = $result['pages'];
			}
		endif;

		$array['pages'][] = array('url' => $p->url(), 'title' => $p->title()->value(), 'id' => str_replace('/', '', $p->id()), 'pages' => $sub);

	endforeach;

	return $array;
}

kirby()->routes(array(
	array(
		'pattern' => 'seo-checker',
		'action' => function () {

			if (site()->user()) {

				$sitemap = recursiveNav();
				//			$sitemap = '';

				$mainUrl = site()->url();

				$html = tpl::load(__DIR__ . DS . 'templates/template.php', array(
					'sitemap' => $sitemap,
					'mainUrl' => $mainUrl,
					'securityheadersUrl' => 'https://securityheaders.io/?hide=on&followRedirects=on&q=' . $mainUrl,
					'linkCheckerUrl' => 'https://validator.w3.org/checklink?hide_type=all&depth=&check=Check&uri=' . $mainUrl,
					'googleApiKey' => c::get('ka.seo-checker.google.apikey', '')
				));

				return new Response($html, 'html');

			}

			return new Response('Bitte einloggen', 'txt');
		}
	)
));