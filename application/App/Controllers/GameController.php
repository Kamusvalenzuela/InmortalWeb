<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Preferences;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use RedBeanPHP\R;

class GameController extends AbstractTwigController
{
    /**
     * @var Preferences
     */
    private $preferences;

    /**
     * HomeController constructor.
     *
     * @param Twig        $twig
     * @param Preferences $preferences
     */
    public function __construct(Twig $twig, Preferences $preferences)
    {
        parent::__construct($twig);

        $this->preferences = $preferences;
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    public function download(Request $request, Response $response, array $args = []): Response
    {
        $page = R::findOne( 'cms_pages', ' unique_slug LIKE ? ', [ 'download_game' ] );
        return $this->render($response, 'basic-page.twig', [
            'pageTitle' => $page->name,
            'content' => $page->content,
            'rootPath' => $this->preferences->getRootPath(),
            'assetsPath' => $this->preferences->getBaseUrl() . 'assets/' . $this->preferences->getThemeName() . "/",
        ]);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    public function leader(Request $request, Response $response, array $args = []): Response
    {
        if (!isset($this->preferences->getApiToken()['access_token']))
        {
          return $this->render($response, 'basic-page.twig', [
              'pageTitle' => "API Error",
              'content' => "Server did not receive a response...<br>check your ports, ip, settings,...Server\\resources\config\api.config.json should have the correct ip's and hosts set up.",
          ]);
        }
        else
        {
          $rankings = $this->preferences->APIcall_GET($this->preferences->getApiServer(), $this->preferences->getApiToken()['access_token'], '/api/v1/players/rank?page=0&pageSize=10&sortDirection=Descending');
          $classes = $this->preferences->APIcall_POST($this->preferences->getApiServer(), $this->preferences->getApiData(), $this->preferences->getApiToken()['access_token'], '/api/v1/gameobjects/class?page=0&count=10');
          return $this->render($response, 'leader.twig', [
              'pageTitle' => "Classement joueur",
              'players' => $rankings['Values'],
              'classes' => $classes['entries'],
          ]);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    public function page(Request $request, Response $response, array $args = []): Response
    {
        $page = R::findOne( 'cms_pages',
        'unique_slug = ? AND category = ?',
        [$args['unique_slug'], "game"]);
        if(empty($page)) {
          return $this->render($response, 'basic-page.twig', [
              'pageTitle' => "Error",
              'content' => "Not found.",
          ]);
        }else{
          return $this->render($response, 'basic-page.twig', [
              'pageTitle' => $page['name'],
              'content' => $page['content'],
          ]);
        }
    }
}
