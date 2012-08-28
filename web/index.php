<?php

use
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response,
	Orwell\UserProvider
;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\application();

$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array('twig.path' => __DIR__ . '/../views'));
$app->register(new Silex\Provider\MonologServiceProvider(), array(
		'monolog.logfile' => __DIR__ . '/../logs/' . ($app['debug'] === true ? 'debug.log' : 'production.log'),
		'monolog.level' => \Monolog\Logger::DEBUG
	)
);
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
		'db.options' => array(
			'driver' => 'pdo_mysql',
			'host' => 'localhost',
			'dbname' => 'orwell',
			'username' => 'orwell',
			'password' => '1984'
		)
	)
);

$app['security.firewalls'] = array(
	'login' => array(
		'pattern' => '^/login$',
	),
	'secured' => array(
		'pattern' => '^.*$',
      'form' => array('login_path' => '/login', 'check_path' => '/authenticate'),
		'logout' => array('logout_path' => '/logout'),
      'users' => $app->share(function () use ($app) {
				return new UserProvider($app['db']);
			}
		)
	)
);

$app->get('/login', function(Request $request) use ($app) {
			return $app['twig']->render('login.twig', array(
			  'error' => $app['security.last_error']($request),
			  'last_username' => $app['session']->get('_security.last_username'),
			 )
		);
	}
);

$app->get('/', function() use ($app) {
		return $app['twig']->render('home.twig');
	}
);

$app->get('/administration/users', function() use ($app) {
		return $app['twig']->render('administration/users.twig', array(
				'users' => $app['db']->fetchAll('SELECT username, lastname, firstname FROM users')
			)
		);
	}
)->bind('users');

$app->error(function(\Exception $exception, $code) use ($app) {
		return $app['debug'] === true ? null : new Response('We are sorry, but something went terribly wrong.', $code);
	}
);

$app->run();
