<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $result = collect($users)->filter(
        fn($user) => empty($term) ?: s($user)->ignoreCase()->startsWith($term)
    );
    $params = [
        'users' => $result,
        'term'  => $term,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = [
        'id'       => $args['id'],
        'nickname' => "user-{$args['id']}",
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    return $response->write("Course id: {$args['id']}");
});

$app->run();
