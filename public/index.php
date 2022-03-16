<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use App\Models\DB;
use App\Controller\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Routing\RouteCollectorProxy;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\SetCookies;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\FigResponseCookies;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);



// Create Twig
$twig = Twig::create('views', ['cache' => false]);
// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));



$app->get('/', function (Request $request, Response $response) {
//    $response->getBody()->write('Hello World!');


    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.twig', [
        'name' => "Name azad"
    ]);

});




$app->get('/login', function (Request $request, Response $response) {


    $view = Twig::fromRequest($request);
    return $view->render($response, 'login.twig', [

    ]);

});


$app->get('/register', function (Request $request, Response $response) {


    $view = Twig::fromRequest($request);
    return $view->render($response, 'register.twig', [

    ]);

});







$app->get('/users', function (Request $request, Response $response) {
    $sql = "SELECT * FROM user";

    try {
        $db = new Db();
        $conn = $db->connect();
        $stmt = $conn->query($sql);
        $users_data = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;


//        $response->getBody()->write(json_encode($users_data));
//        return $response
//            ->withHeader('content-type', 'application/json')
//            ->withStatus(200);

        $view = Twig::fromRequest($request);
        return $view->render($response, 'users.twig', [
            'users' => $users_data
        ]);

    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );

        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(500);
    }
});




$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/signup', function (Request $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $name = App::stripinput($data["name"]);
        $email = App::stripinput($data["email"]);
        $password = md5(App::stripinput($data["password"]));
        $create_date = date("Y-m-d H:i:s");

        $sql = "INSERT INTO user (name, email, password, create_date) VALUES (:name, :email, :password, :create_date)";
        try {

            if (empty($name)) {
                return false;
            }
            if (empty($email)) {
                return false;
            }
            if (empty($password)) {
                return false;
            }


            $db = new Db();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':create_date', $create_date);

            $result = $stmt->execute();

            $db = null;
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    });

    $group->post('/signin', function (Request $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $email = App::stripinput($data["email"]);
        $password = md5(App::stripinput($data["password"]));

        $sql = "SELECT id FROM user WHERE email = '". $email ."' AND password = '". $password ."'";
        try {
            if (empty($email)) {
                $result = false;
            } else  if (empty($password)) {
                $result = false;
            } else {

                $db = new Db();
                $conn = $db->connect();
                $stmt = $conn->query($sql);
                $result = $stmt->fetch();
                $db = null;

            }

//            App::debug( $result );

            if ($result['id']) {


//                $request = FigRequestCookies::set($request, Cookie::create('user_id', $result['id']));



                $cookie = FigRequestCookies::get($request, 'user_id');
                App::debug( $cookie );


                $result = true;
            }

            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        } catch (PDOException $e) {
            $error = array(
                "message" => $e->getMessage()
            );

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    });


    $group->get('/profile', function (Request $request, Response $response) {


        $cookie = FigRequestCookies::get($request, 'user_id');

        $user_id = $cookie->getValue();

        if (!$user_id) {
            $response = $response->createResponse(302);
            return $response->withHeader('Location', '/login');
        }


        $view = Twig::fromRequest($request);
        return $view->render($response, 'profile.twig', [
            'user_id' => $user_id
        ]);

    });
});




$app->run();