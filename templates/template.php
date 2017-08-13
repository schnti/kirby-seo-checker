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

        function faviconChecker() {

            var element = $('#faviconChecker');
            element.empty();
            element.html('loading...');

            var elementErrors = $('#faviconCheckerErrors');
            var elementWarnings = $('#faviconCheckerWarnings');

            elementErrors.empty();
            elementWarnings.empty();

            $.ajax({
                url : '/seo-checker/favicon-checker',
                type : "GET",
                contentType : 'application/json',
                success : function (data) {

                    element.empty();

                    data.browser.forEach(function (value) {
                        element.append('<div class="col-xs-2">' +
                            '<span>' + value.name + '</span>' +
                            '<img src="' + value.image + '" class="img-responsive">' +
                            '</div>'
                        );
                    });

                    if (data.infos.length > 0) {
                        data.infos.forEach(function (value) {
                            elementWarnings.append('<div class="alert alert-info">' + value + '</div>');
                        });
                    }

                    if (data.errors.length > 0) {
                        data.errors.forEach(function (value) {
                            elementErrors.append('<div class="alert alert-danger">' + value + '</div>');
                        });
                    }

                },
                error : function (error) {
                    console.error(error);
                }
            });
        }

        function securityheaders() {

            var element = $('#securityheaders');

            element.empty();
            element.css('background-color', '#fff');
            element.html('loading...');

            $.ajax({
                url : '/seo-checker/security-headers',
                type : 'GET',
                contentType : 'application/json',
                success : function (data) {

                    var header = JSON.parse(atob(data['X-Score']));
                    console.log(header);

                    element.empty();
                    element.html(header['score']);
                    element.css('background-color', header['colour']);
                },
                error : function (error) {
                    console.error(error);
                }
            });
        }

        function addClass(element, score) {
            if (score > 84)
                element.addClass('label-success');
            else if (score > 64)
                element.addClass('label-warning');
            else
                element.addClass('label-danger');
        }

        function generateSitemap(element, data) {
            data.forEach(function (value) {

                var li = $('<li/>');

                li.append('<a href="' + value.url + '" target="_blank">' + value.title + '</a>');

                if (value.pagespeed.scoreDesktop !== null) {
                    li.append('&nbsp;');

                    var scoreLinkDesktop = $('<a href="https://developers.google.com/speed/pagespeed/insights/?tab=desktop&url=' + value.url + '" target="_blank"></a>');
                    li.append(scoreLinkDesktop);

                    var scoreDesktop = $('<span class="label">' + value.pagespeed.scoreDesktop + '</span>');
                    addClass(scoreDesktop, value.pagespeed.scoreDesktop);

                    scoreLinkDesktop.append(scoreDesktop);
                }

                if (value.pagespeed.scoreMobile !== null) {
                    li.append('&nbsp;');

                    var scoreLinkMobile = $('<a href="https://developers.google.com/speed/pagespeed/insights/?tab=mobile&url=' + value.url + '" target="_blank"></a>');
                    li.append(scoreLinkMobile);

                    var scoreMobile = $('<span class="label"><span class="glyphicon glyphicon-phone"></span> ' + value.pagespeed.scoreMobile + '</span>');
                    addClass(scoreMobile, value.pagespeed.scoreMobile);

                    scoreLinkMobile.append(scoreMobile);
                }

                if (value.pages) {

                    var ul = $('<ul/>');

                    generateSitemap(ul, value.pages);

                    li.append(ul);
                }

                element.append(li);
            });
        }

        function pageSpeed() {

            var scoreElementDesktop = $('#scoreElementDesktop');
            var scoreElementMobile = $('#scoreElementMobile');

            var element = $('#pageSpeed');
            element.empty();
            element.html('loading...');

            var checkDesktop = $('#checkDesktop').is(":checked");
            var checkMobile = $('#checkMobile').is(":checked");

            $.ajax({
                url : '/seo-checker/page-speed?desktop=' + checkDesktop + '&mobile=' + checkMobile,
                type : 'GET',
                contentType : 'application/json',
                success : function (data) {

                    console.log(data);

                    scoreElementDesktop.html(data['avgDesktop']);
                    addClass(scoreElementDesktop, data['avgDesktop']);

                    scoreElementMobile.html(data['avgMobile']);
                    addClass(scoreElementMobile, data['avgMobile']);

                    element.empty();

                    generateSitemap(element, data.sitemap);
                },
                error : function (error) {
                    console.error(error);
                }
            });
        }

        function loadAll() {
            securityheaders();
            faviconChecker();
            pageSpeed();
        }

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

    <div class="pull-right"><a href="<?= $mainUrl; ?>" target="_blank"><span class="glyphicon glyphicon-chevron-left"></span>Go to the website</a></div>

    <h1>SEO-Checker
        <button class="btn btn-default btn-xs" onclick="loadAll()">Load all</button>
    </h1>


    <div class="row">

        <div class="col-sm-3">
            <h2>Security Headers
                <button class="btn btn-default btn-xs" onclick="securityheaders()">Reoad</button>
            </h2>
            <div id="securityheaders"></div>
            <a href="<?= $securityheadersUrl; ?>" target="_blank">securityheaders.io</a>
        </div>
        <div class="col-sm-3">
            <h2>Other tools</h2>
            <ul>
                <li><a href="<?= $linkCheckerUrl; ?>" target="_blank">W3C Link Checker</a></li>
            </ul>
        </div>
        <div class="col-sm-3">
            <h2>Helpful links</h2>
            <ul>
                <li><a href="<?= $cspBuilder; ?>" target="_blank">CSP Builder</a></li>
                <li><a href="<?= $getGoogleApiCredentials; ?>" target="_blank">Get Google API Credentials for PageSpeed</a></li>
            </ul>
        </div>
        <div class="col-sm-3"></div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <h2>Favicon Checker
                <button class="btn btn-default btn-xs" onclick="faviconChecker()">Reoad</button>
            </h2>
            <div id="faviconChecker" class="row"></div>

            <br/>

            <div id="faviconCheckerErrors"></div>
            <div id="faviconCheckerWarnings"></div>

            <div><a href="<?= $faviconCheckerUrl; ?>" target="_blank">Favicon Checker</a></div>
        </div>
    </div>

    <h2>Google PageSpeed <span class="label" id="scoreElementDesktop"></span> <span class="label" id="scoreElementMobile"></span>
        <button class="btn btn-default btn-xs" onclick="pageSpeed()">Reoad</button>
    </h2>

    <label class="checkbox-inline">
        <input type="checkbox" name="desktop" checked="checked" id="checkDesktop"> Desktop
    </label>
    <label class="checkbox-inline">
        <input type="checkbox" name="mobile" id="checkMobile"> Mobile
    </label>


    <ul id="pageSpeed"></ul>
</div>
</body>
</html>