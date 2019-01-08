<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/VK_helper.php';

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    die('Rename config.sample.php to config.php and edit.');
}

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use voku\helper\HtmlDomParser;

$cookieJar = new CookieJar();
$client    = new Client([
    'cookies'         => $cookieJar,
    'verify'          => false,
    'allow_redirects' => true,
    'headers'         => [
        'Accept'       => 'text/html',
        'Content-Type' => 'text/html',
    ],
]);

$tracks = [];
$offset = (isset($_GET['offset']) ? (int) $_GET['offset'] : 0);

// Get login URL
try {

    $loginPageResponse = $client->request('GET', 'https://m.vk.com');

    if ($loginPageResponse->getStatusCode() == 200) {

        $__loginPageHTML = $loginPageResponse->getBody()->getContents();

        // Get form URL
        $dom               = HtmlDomParser::str_get_html($__loginPageHTML);
        $loginForm         = $dom->find('form', 0);
        $__loginFormAction = $loginForm->action;

        // Login & Save cookies
        try {

            $response = $client->request('POST', $__loginFormAction, [
                'form_params' => [
                    'email' => $__USERNAME,
                    'pass'  => $__PASSWORD,
                ],
            ]);

            // Get my music
            try {
                $userMusicPageResponse = $client->request('POST', 'https://m.vk.com/audios' . $__USER_ID, [
                    'headers'     => [
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                    'form_params' => [
                        '_ajax'  => 1,
                        'offset' => $offset,
                    ],
                ]);

                if ($userMusicPageResponse->getStatusCode() == 200) {

                    $__myMusicPageJSON = $userMusicPageResponse->getBody()->getContents();
                    $__arrayFromJSON   = json_decode($__myMusicPageJSON, true);

                    foreach ($__arrayFromJSON as $key => $value) {
                        if ($key == '3') {
                            foreach ($value as $key => $value) {
                                if ($key == '0') {
                                    foreach ($value as $key => $value) {
                                        $tracks[] = [
                                            'title'       => isset($value[0]) ? $value[0] : null,
                                            'artist'      => isset($value[3]) ? $value[3] : null,
                                            'song'        => isset($value[4]) ? $value[4] : null,
                                            'extra_url'   => isset($value[2]) ? $value[2] : null,
                                            'decoded_url' => isset($value[2]) ? decode($value[2]) : null,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

            } catch (\Exception $e) {
                die('An error occured: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            die('An error occured: ' . $e->getMessage());
        }

    }

} catch (\Exception $e) {
    die('An error occured: ' . $e->getMessage());
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>VK.Music &mdash; Direct Download Mp3</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>

<body class="bg-light">
    <div class="container">
        <div class="py-3 text-center">
            <h2>Playlist</h2>
            <p class="lead">Get playlist from user ID: <strong><?=$__USER_ID;?></strong></p>
        </div>

        <div class="row py-3">
            <div class="col-sm">
                <?php if ($offset > 0) {?>
                    <a href="?offset=<?=($offset - 50);?>" class="btn btn-sm btn-primary">&laquo; Previous 50 records</a>
                <?php }?>
            </div>
            <div class="col-sm text-right">
                <?php if (count($tracks) >= 50) {?>
                <a href="?offset=<?=($offset + 50);?>" class="btn btn-sm btn-primary">Next 50 records &raquo;</a>
                <?php }?>
            </div>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Song</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tracks as $__t) {?>
            <tr>
                <td><?=$__t['title'];?></td>
                <td><?=$__t['artist'];?></td>
                <td><?=$__t['song'];?></td>
                <td><a href="<?=$__t['decoded_url'];?>" class="btn btn-sm btn-block btn-success" target="_blank">Download</a></td>
            </tr>
            <?}?>
            </tbody>
        </table>

    </div>
</body>
</html>