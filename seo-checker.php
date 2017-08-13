<?php

namespace ka\kirby;

include_once __DIR__ . '/helper/http.php';
include_once __DIR__ . '/helper/message.php';
include_once __DIR__ . '/helper/statuscodes.php';

use C;
use ka\HTTP;
use Tpl;
use Response;

class SEOChecker
{

	public static function recursiveNav($subpages = null, $googleApiKey, &$counter, &$scoreSumDesktop, &$scoreSumMobile)
	{

		$array = array();

		if ($subpages == null) :
			$subpages = site()->pages();
		endif;

		foreach ($subpages as $p) :

			if (!check($p)) continue;

			$sub = null;

			if ($p->hasChildren()) :

				$result = self::recursiveNav($p->children(), $googleApiKey, $counter, $scoreSumDesktop, $scoreSumMobile);

				if ($result && $result['pages']) {
					$sub = $result['pages'];
				}
			endif;

			$counter++;

			// Google PageSpeed Desktop
			$apiUrlDesktop = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=desktop&url=' . $p->url() . '&key=' . $googleApiKey;
			$responseDesktop = HTTP::get($apiUrlDesktop);

			$scoreDesktop = $responseDesktop->data['data']['ruleGroups']['SPEED']['score'];

			$scoreSumDesktop += $scoreDesktop;

			// Google PageSpeed Mobile
			$apiUrlMobile = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=mobile&url=' . $p->url() . '&key=' . $googleApiKey;
			$responseMobile = HTTP::get($apiUrlMobile);

			$scoreMobile = $responseMobile->data['data']['ruleGroups']['SPEED']['score'];

			$scoreSumMobile += $scoreMobile;

			// Response
			$array['pages'][] = [
				'url' => $p->url(),
				'title' => $p->title()->value(),
				'id' => str_replace('/', '', $p->id()),
				'pages' => $sub,
				'pagespeed' => [
					'scoreDesktop' => $scoreDesktop,
					'scoreMobile' => $scoreMobile
				]
			];

		endforeach;

		return $array;
	}
}

kirby()->routes(array(
	[
		'pattern' => 'seo-checker',
		'action' => function () {

			if (!site()->user()) {
				return response::error($message = 'Bitte einloggen', $code = 401);
			}

			$mainUrl = site()->url();

			$url = parse_url($mainUrl);

			$html = Tpl::load(__DIR__ . DS . 'templates/template.php', array(
				'mainUrl' => $mainUrl,
				'securityheadersUrl' => 'https://securityheaders.io/?hide=on&followRedirects=on&q=' . $mainUrl,
				'faviconCheckerUrl' => 'https://realfavicongenerator.net/favicon_checker?protocol=' . $url['scheme'] . '&site=' . $url['host'],
				'linkCheckerUrl' => 'https://validator.w3.org/checklink?hide_type=all&depth=&check=Check&uri=' . $mainUrl,
				'cspBuilder' => 'https://report-uri.io/home/generate',
				'getGoogleApiCredentials' => 'https://console.cloud.google.com/apis/credentials'
			));

			return new Response($html, 'html');
		}
	],
	[
		'method' => 'GET',
		'pattern' => 'seo-checker/favicon-checker',
		'action' => function () {

			if (!site()->user()) {
				return response::error($message = 'Bitte einloggen', $code = 401);
			}

			$mainUrl = site()->url();
			$response = HTTP::get('https://realfavicongenerator.net/analyze_favicon?ignore_root_issues=on&site=' . $mainUrl);

			$return = [];

			$infos = [];
			$errors = [];

			foreach ($response->data['data'] as $device) {

				if (isset($device['messages']) && isset($device['messages']['info'])) {
					foreach ($device['messages']['info'] as $info) {
						$infos[] = $info[0];
					}
				}

				if (isset($device['messages']) && isset($device['messages']['error'])) {
					foreach ($device['messages']['error'] as $error) {
						$errors[] = $error[0];
					}
				}

				$return['browser'][] = [
					'name' => $device['name'],
					'image' => 'https://realfavicongenerator.net/' . $device['presentation'],
				];
			}

			$return['infos'] = $infos;
			$return['errors'] = $errors;

			// for debug
			$return['$response'] = $response;

			return response::json($return);
		}
	],
	[
		'method' => 'GET',
		'pattern' => 'seo-checker/security-headers',
		'action' => function () {

			if (!site()->user()) {
				return response::error($message = 'Bitte einloggen', $code = 401);
			}

			$mainUrl = site()->url();
			$response = HTTP::get('https://securityheaders.io/?hide=on&followRedirects=on&q=' . $mainUrl, [], true);

			return new Response($response->data['header'], 'json');
		}
	],
	[
		'method' => 'GET',
		'pattern' => 'seo-checker/page-speed',
		'action' => function () {

			if (!site()->user()) {
				return response::error($message = 'Bitte einloggen', $code = 401);
			}

			$googleApiKey = c::get('ka.seo-checker.google.apikey', '');

			$counter = 0;
			$scoreSumDesktop = 0;
			$scoreSumMobile = 0;

			$sitemap = SEOChecker::recursiveNav(null, $googleApiKey, $counter, $scoreSumDesktop, $scoreSumMobile);

			return response::json([
				'sitemap' => $sitemap['pages'],
				'counter' => $counter,
				'avgDesktop' => round($scoreSumDesktop / $counter),
				'avgMobile' => round($scoreSumMobile / $counter)
			]);
		}
	]
));