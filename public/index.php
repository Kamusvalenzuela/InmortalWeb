<?php
/*
*
*  IntersectCMS - 2020
*  CMS for Intersect Engine made with ♥
*  Developped by Ramlethal
*  Many thanks to the Intersect Team for this beautiful engine
*  ----------
*  Please respect our work :)
*
*/

declare(strict_types=1);

use App\ContainerFactory;
use App\Controllers\ExceptionDemoController;
use App\Controllers\UserController;
use App\Controllers\HomeController;
use App\Controllers\GameController;
use App\Controllers\WikiController;
use App\Controllers\ShopController;
use App\Controllers\AdminController;
use App\Preferences;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use RedBeanPHP\R;

// Set the default timezone.
date_default_timezone_set('Europe/Paris');

session_start();

// Set the absolute path to the root directory.
$rootPath = realpath(__DIR__ . '/..');

// Include the composer autoloader.
include_once($rootPath . '/vendor/autoload.php');

// Create the container for dependency injection.
try {
    $container = ContainerFactory::create($rootPath);
} catch (Exception $e) {
    die($e->getMessage());
}

// Set the container to create the App with AppFactory.
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set the cache file for the routes. Note that you have to delete this file
// whenever you change the routes.
$app->getRouteCollector()->setCacheFile(
    $rootPath . '/cache/routes.cache'
);

// Add the routing middleware.
$app->addRoutingMiddleware();

$preferences = $container->get(Preferences::class);
R::setup( 'mysql:host='.$preferences->getDbIp().';dbname='.$preferences->getDbName(), $preferences->getDbUser(), $preferences->getDbPassword());
$cms_settings_data = R::getAll( 'SELECT * FROM cms_settings');
$cms_settings = array();
foreach($cms_settings_data as $setting) {
  $cms_settings[$setting['setting']] = $setting['value'];
}
if($preferences->getCmsSettings() == null || empty($preferences->getCmsSettings())){
  $preferences->setCmsSettings($cms_settings);
}
$preferences->setThemeName($cms_settings['theme']);
$preferences->setBaseURL($cms_settings['base_url']);
$preferences->setApiServer($cms_settings['api_server']);
$preferences->setApiUser($cms_settings['api_username']);
$preferences->setApiPassword($cms_settings['api_password']);

// API login
if(!isset($preferences->getApiToken()['access_token'])) {
  $loginAPI = $preferences->APIcall_POST($preferences->getApiServer(), $preferences->getApiData(), "", '/api/oauth/token');
  $preferences->setApiToken($loginAPI);
}

$server_infos = array();
if (isset($preferences->getApiToken()['access_token'])){
  $server_infos = $preferences->APIcall_GET($preferences->getApiServer(), $preferences->getApiToken()['access_token'], '/api/v1/info/stats');
}

// Add the twig middleware.
$app->addMiddleware(
    TwigMiddleware::create($app, $container->get(Twig::class))
);

// Add error handling middleware.
$displayErrorDetails = true;
$logErrors = true;
$logErrorDetails = false;
$app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

// Define the app routes.
$app->group('/', function (RouteCollectorProxy $group) {
    $group->get('', HomeController::class . ':home')->setName('home');
    $group->get('news/{id}', HomeController::class . ':news')->setName('news');
    $group->get('game/download', GameController::class . ':download')->setName('download');
    $group->get('game/p/{unique_slug}', GameController::class . ':page')->setName('gamepage');
    $group->get('wiki/p/{unique_slug}', WikiController::class . ':page')->setName('wikipage');
    $group->get('user/register', UserController::class . ':register')->setName('register');
    $group->post('user/register_complete', UserController::class . ':register_complete')->setName('register_complete');
    $group->get('user/my_account', UserController::class . ':my_account')->setName('my_account');
    $group->post('user/login', UserController::class . ':login')->setName('login');
    $group->get('user/logout', UserController::class . ':logout')->setName('logout');
    $group->get('game/leader', GameController::class . ':leader')->setName('leader');
    $group->get('shop/list', ShopController::class . ':list')->setName('shop-list');
    $group->get('shop/list/{cat}', ShopController::class . ':list')->setName('shop-list-cat');
    $group->get('shop/detail/{id}', ShopController::class . ':detail')->setName('shop-detail');
    $group->post('shop/detail/{id}', ShopController::class . ':detail');
    $group->get('user/credits', UserController::class . ':buyPoints')->setName('user-buy-credits');
    $group->post('user/credits', UserController::class . ':buyPoints');
    //Admin pages
    $group->get('admin/home', AdminController::class . ':home')->setName('admin-home');
    $group->get('admin/news/add', AdminController::class . ':news_add')->setName('admin-add-news');
    $group->post('admin/news/add', AdminController::class . ':news_add');
    $group->get('admin/news/edit/{id}', AdminController::class . ':news_edit')->setName('admin-edit-news');
    $group->post('admin/news/edit/{id}', AdminController::class . ':news_edit');
    $group->get('admin/news/delete/{id}', AdminController::class . ':news_delete')->setName('admin-delete-news');
    $group->get('admin/shop/add', AdminController::class . ':shop_add')->setName('admin-add-shop');
    $group->post('admin/shop/add', AdminController::class . ':shop_add');
    $group->get('admin/shop/edit/{id}', AdminController::class . ':shop_edit')->setName('admin-edit-shop');
    $group->post('admin/shop/edit/{id}', AdminController::class . ':shop_edit');
    $group->get('admin/shop/delete/{id}', AdminController::class . ':shop_delete')->setName('admin-delete-shop');
    $group->get('admin/pages/add', AdminController::class . ':pages_add')->setName('admin-add-pages');
    $group->post('admin/pages/add', AdminController::class . ':pages_add');
    $group->get('admin/pages/edit/{id}', AdminController::class . ':pages_edit')->setName('admin-edit-pages');
    $group->post('admin/pages/edit/{id}', AdminController::class . ':pages_edit');
    $group->get('admin/pages/delete/{id}', AdminController::class . ':pages_delete')->setName('admin-delete-pages');
    $group->get('admin/settings', AdminController::class . ':settings')->setName('admin-edit-settings');
    $group->post('admin/settings', AdminController::class . ':settings');
    $group->get('admin/translate', AdminController::class . ':translate')->setName('admin-edit-translation');
    $group->post('admin/translate', AdminController::class . ':translate');

    //Exceptions
    $group->get('exception-demo', ExceptionDemoController::class)->setName('exception-demo');
});

if($cms_settings['use_custom_game_pages']) {
$gpages = R::getAll( 'SELECT * FROM cms_pages WHERE category = :cat',
        [':cat' => 'game']
    );
}
if($cms_settings['use_wiki']) {
$wpages = R::getAll( 'SELECT * FROM cms_pages WHERE category = :cat',
        [':cat' => 'wiki']
    );
}
$lang = R::getAll( 'SELECT * FROM cms_lang WHERE language = :lang',
        [':lang' => $cms_settings['current_lang']]
    );
$lang_array = array();
foreach($lang as $translate) {
  $lang_array[$translate['str_key']] = $translate['text'];
}

$container->get(Twig::class)->getEnvironment()->addGlobal('session', $_SESSION);
$container->get(Twig::class)->getEnvironment()->addGlobal('server_infos', $server_infos);
$container->get(Twig::class)->getEnvironment()->addGlobal('rootPath', $preferences->getRootPath());
$container->get(Twig::class)->getEnvironment()->addGlobal('assetsPath', $cms_settings['base_url'] . 'assets/themes/' . $cms_settings['theme'] . "/");
$container->get(Twig::class)->getEnvironment()->addGlobal('globalAssetsPath', $cms_settings['base_url'] . "assets/general/");
$container->get(Twig::class)->getEnvironment()->addGlobal('cms_settings', $cms_settings);
$container->get(Twig::class)->getEnvironment()->addGlobal('lang', $lang_array);
if($cms_settings['use_nav_community'] == "true") {
  $container->get(Twig::class)->getEnvironment()->addGlobal('nav_community', true);
}
if($cms_settings['use_custom_game_pages'] == "true") {
  $container->get(Twig::class)->getEnvironment()->addGlobal('gamePages', $gpages);
}
if($cms_settings['use_wiki'] == "true") {
  $container->get(Twig::class)->getEnvironment()->addGlobal('wikiPages', $wpages);
}
// Run the app.
$app->run();
