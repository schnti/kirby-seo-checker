<?php

namespace ka\kirby;

include_once __DIR__ . '/helper/http.php';
include_once __DIR__ . '/helper/message.php';
include_once __DIR__ . '/helper/statuscodes.php';

use C;
use ka\HTTP;
use ka\StatusCodes;
use Tpl;
use Response;

class SEOChecker
{

	public static function recursiveNav($subpages = null, $googleApiKey, $desktop, $mobile, &$counter, &$errors)
	{

		$array = array();

		if ($subpages == null) :
			$subpages = site()->pages();
		endif;

		foreach ($subpages as $p) :

			if (!check($p)) continue;

			$sub = null;

			if ($p->hasChildren()) :

				$result = self::recursiveNav($p->children(), $googleApiKey, $desktop, $mobile, $counter, $errors);

				if ($result && $result['pages']) {
					$sub = $result['pages'];
				}
			endif;

			$counter['count']++;

			// Google PageSpeed Desktop
			if ($desktop) {
				$apiUrlDesktop = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=desktop&url=' . $p->url() . '&key=' . $googleApiKey;
				$responseDesktop = HTTP::get($apiUrlDesktop);

				if ($responseDesktop->code != StatusCodes::HTTP_OK) {
					$errors[] = $responseDesktop->data;
				}

				$scoreDesktop = $responseDesktop->data['data']['ruleGroups']['SPEED']['score'];

				$counter['sumDesktop'] += $scoreDesktop;
			} else {
				$scoreDesktop = null;
			}

			// Google PageSpeed Mobile
			if ($mobile) {
				$apiUrlMobile = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=mobile&url=' . $p->url() . '&key=' . $googleApiKey;
				$responseMobile = HTTP::get($apiUrlMobile);

				if ($responseMobile->code != StatusCodes::HTTP_OK) {
					$errors[] = $responseMobile->data;
				}

				$scoreMobile = $responseMobile->data['data']['ruleGroups']['SPEED']['score'];

				$counter['sumMobile'] += $scoreMobile;
			} else {
				$scoreMobile = null;
			}

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

			$counter['count'] = 0;
			$counter['sumDesktop'] = 0;
			$counter['sumMobile'] = 0;

			$errors = [];

			$desktop = filter_var(get('desktop', 0), FILTER_VALIDATE_BOOLEAN);
			$mobile = filter_var(get('mobile', 0), FILTER_VALIDATE_BOOLEAN);

			$sitemap = SEOChecker::recursiveNav(null, $googleApiKey, $desktop, $mobile, $counter, $errors);

			return response::json([
				'sitemap' => $sitemap['pages'],
				'counter' => $counter['count'],
				'avgDesktop' => ($counter['count'] > 0) ? round($counter['sumDesktop'] / $counter['count']) : 0,
				'avgMobile' => ($counter['count'] > 0) ? round($counter['sumMobile'] / $counter['count']) : 0,
				'errors' => $errors,
				'desktop' => $desktop,
				'mobile' => $mobile
			]);
		}
	],
	[
		'method' => 'GET',
		'pattern' => 'seo-checker/page-speed/single',
		'action' => function () {

			if (!site()->user()) {
				return response::error($message = 'Bitte einloggen', $code = 401);
			}

			$googleApiKey = c::get('ka.seo-checker.google.apikey', '');

			$url = get('url');
			$strategy = get('strategy', 'desktop');

			$apiUrlDesktop = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=' . $strategy . '&url=' . $url . '&key=' . $googleApiKey;
			$responseDesktop = HTTP::get($apiUrlDesktop);

			if ($responseDesktop->code != StatusCodes::HTTP_OK) {
				return response::error($message = 'ERROR', $code = $responseDesktop->code);
			}

			$score = $responseDesktop->data['data']['ruleGroups']['SPEED']['score'];

			return response::json([
				'score' => $score,
				'strategy' => $strategy,
				'url' => $url
			]);
		}
	]
));