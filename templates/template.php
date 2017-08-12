<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SEO Checker</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">

    <script>
        function securityheaders() {
            $.ajax({
                url : '<?= $securityheadersUrl; ?>',
                type : "GET",
                contentType : 'text/html',
                success : function (data, textStatus, request) {

                    var header = JSON.parse(atob(request.getResponseHeader('X-Score')));
                    console.log(header);

                    var element = $('#securityheaders');
                    element.html(header['score']);
                    element.css('background-color', header['colour']);
                },
                error : function (error) {
                    console.log(error);
                }
            });
        }

        securityheaders();
    </script>

    <style>
        #securityheaders {
            width: 70px;
            height: 70px;
            border-radius: 20%;
            text-align: center;
            line-height: 70px;
            font-size: 40px;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="row">

        <div class="col-sm-3">
            <h2>Security Headers</h2>
            <div id="securityheaders"></div>
            <a href="<?= $securityheadersUrl; ?>" target="_blank">securityheaders.io</a>
            <button class="btn btn-default btn-xs" onclick="securityheaders()">Reoad</button>
        </div>
        <div class="col-sm-3">
            <h2>Security Headers</h2>
            <a href="<?= $linkCheckerUrl; ?>" target="_blank">W3C Link Checker</a>
        </div>
        <div class="col-sm-3"></div>
        <div class="col-sm-3"></div>

    </div>

    <h2>Google PageSpeed <span class="label label-default" id="scoreElement"></span></h2>

	<?php

	function myNav($subpages = null, $googleApiKey, $index = 0)
	{

		echo '<ul>';

		foreach ($subpages AS $p) :

			echo '<li>';

			$apiUrl = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?strategy=desktop&url=' . $p['url'] . '&key=' . $googleApiKey;
			$webUrl = 'https://developers.google.com/speed/pagespeed/insights/?tab=desktop&url=' . $p['url'];

			?>

            <script>

                var googleScores = [];

                var scoreElement = $('#scoreElement');

                function updateScore() {

                    var sum = 0;
                    for (var i = 0; i < googleScores.length; i++) {
                        sum += parseInt(googleScores[i], 10); //don't forget to add the base
                    }

                    var avg = Math.round(sum / googleScores.length);

                    scoreElement.html(avg);

                    if (avg > 90)
                        scoreElement.addClass('label-success');
                    else if (avg > 60)
                        scoreElement.addClass('label-warning');
                    else
                        scoreElement.addClass('label-danger');
                }

                setTimeout(function () {

                    var element = $('#<?= $p['id']; ?>');

                    $.ajax({
                        url : '<?= $apiUrl; ?>',
                        type : "GET",
                        contentType : 'application/json',
                        success : function (data, textStatus, request) {

                            var score = data.ruleGroups.SPEED.score;

                            googleScores.push(score);
                            updateScore();

                            element.html(score);

                            if (score > 90)
                                element.addClass('label-success');
                            else if (score > 60)
                                element.addClass('label-warning');
                            else
                                element.addClass('label-danger');
                        },
                        error : function (error) {
                            element.html('!!!');
                            element.css('color', '#fff');
                        }
                    });
                }, Math.floor((Math.random() * 100) + 1) * 50);
            </script>


			<?php

			echo '<a href="' . $p['url'] . '" target="_blank">' . $p['title'] . '</a>';
			echo ' <a href="' . $webUrl . '" target="_blank"><span class="label label-default" id="' . $p['id'] . '"></span>';

			if ($p['pages'] !== null) :
				myNav($p['pages'], $googleApiKey, $index);
			endif;

			$index++;

			echo '</li>';
		endforeach;

		echo '</ul>';
	}

	myNav($sitemap['pages'], $googleApiKey);

	?>
</div>
</body>
</html>