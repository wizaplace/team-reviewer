<?php

use Symfony\Component\HttpFoundation\Cookie;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Templating
$app['templating'] = new class() {
    public function render($path, array $data = [])
    {
        extract($data);
        ob_start();
        eval('; ?>'.file_get_contents(__DIR__.'/../views/'.$path).'<?php ;');

        return ob_get_clean();
    }
};

require_once __DIR__.'/../functions.php';

$app->get('/', function () use ($app) {
    if (!empty($_GET['id']) && !empty($_GET['redir'])) {

    }

    $repos = [];

    if (is_file(__DIR__.'/../repos.dat')) {
        $repos = unserialize(file_get_contents(__DIR__.'/../repos.dat'));
    }

    return $app['templating']->render('index.php', [
        'repos' => $repos,
    ]);
});

$app->get('/{id}/{redir}', function ($id, $redir) use ($app) {
    $response = $app->redirect(base64_decode($redir));
    $response->headers->setCookie(new Cookie('lastClick['.$id.']', (string) time(), time() + 2678400));

    return $response;
});

$app->run();
