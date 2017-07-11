<?php

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
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
$config = include __DIR__.'/../config.php';

$guzzleOptions = [];

$stack = HandlerStack::create();
$stack->push(
    new CacheMiddleware(
        new PrivateCacheStrategy(
            new DoctrineCacheStorage(
                new FilesystemCache('/tmp/team-reviewer')
            )
        )
    ),
    'cache'
);

$guzzleOptions['handler'] = $stack;

if (isset($config['auth']['token'])) {
    $guzzleOptions['headers']['Authorization'] = 'token '.$config['auth']['token'];
} elseif (isset($config['auth']['basic'])) {
    $guzzleOptions['auth'] = [$config['auth']['basic']['username'], $config['auth']['basic']['password']];
}
$app['http.client'] = new \GuzzleHttp\Client($guzzleOptions);

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

$app->get('/update', function () use ($app, $config) {
    $repos = [];

    foreach ($config['repositories'] as $repository) {
        $response = $app['http.client']->get('https://api.github.com/repos/' . $repository . '/pulls?state=open&per_page=100');

        $response = json_decode($response->getBody()->getContents(), true);

        foreach ($response as &$pr) {
            echo "  #{$pr['number']} {$pr['title']}\n";

            $pr['issue'] = json_decode(
                $app['http.client']->get($pr['issue_url'])->getBody()->getContents(),
                true
            );

            $pr['status'] = json_decode(
                $app['http.client']->get('https://api.github.com/repos/'.$repository.'/commits/'.$pr['head']['sha'].'/status')->getBody()->getContents(),
                true
            );
        }

        $repos[$repository] = $response;
    }

    file_put_contents(__DIR__.'/../repos.dat', serialize($repos));

    return 'ok';
});

$app->run();
