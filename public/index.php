<?php

use App\Validator;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

use function Symfony\Component\String\s;

require __DIR__ . '/../vendor/autoload.php';

const USER_STORAGE_PATH = __DIR__ . '/../storage/users.json';

function getUsers(): array
{
    if (!file_exists(USER_STORAGE_PATH)) {
        return [];
    }

    return json_decode(file_get_contents(USER_STORAGE_PATH), true);
}

function saveUsers(array $users): void
{
    $jsonUsers = json_encode($users, JSON_PRETTY_PRINT);
    file_put_contents(USER_STORAGE_PATH, $jsonUsers);
}

function addUser(array $user): void
{
    $users = getUsers();
    $maxId = collect($users)->max('id');
    $users[] = array_merge($user, ['id' => $maxId + 1]);
    saveUsers($users);
}

session_start();

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn() => new Messages());
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
})->setName('home');

$app->get('/users', function ($request, $response) {
    $users = getUsers();
    $term = $request->getQueryParam('term');
    $result = collect($users)->filter(
        fn($user) => empty($term) ?: s($user['nickname'])->ignoreCase()->startsWith($term)
    );
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users'    => $result,
        'term'     => $term,
        'messages' => $messages,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user'   => [
            'email'    => '',
            'nickname' => '',
        ],
        'errors' => [],
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user_new');

$app->get('/users/{id}', function ($request, $response, $args) {
    $users = getUsers();
    $user = collect($users)->firstWhere('id', $args['id']);

    if (!$user) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        addUser($user);

        $this->get('flash')->addMessage('success', 'Пользователь успешно добавлен');

        return $response->withRedirect($router->urlFor('users'), 302);
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user_add');

$app->run();
