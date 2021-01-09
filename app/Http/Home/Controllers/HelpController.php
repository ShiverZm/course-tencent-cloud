<?php

namespace App\Http\Home\Controllers;

use App\Http\Home\Services\Index as IndexService;
use App\Services\Logic\Help\HelpInfo as HelpInfoService;
use App\Services\Logic\Help\HelpList as HelpListService;

/**
 * @RoutePrefix("/help")
 */
class HelpController extends Controller
{

    /**
     * @Get("/", name="home.help.index")
     */
    public function indexAction()
    {
        $service = new HelpListService();

        $items = $service->handle();

        $this->seo->prependTitle('帮助');

        $this->view->setVar('items', $items);
    }

    /**
     * @Get("/{id:[0-9]+}", name="home.help.show")
     */
    public function showAction($id)
    {
        $service = new HelpInfoService();

        $help = $service->handle($id);

        $featuredCourses = $this->getFeaturedCourses();

        $this->seo->prependTitle(['帮助', $help['title']]);

        $this->view->setVar('help', $help);
        $this->view->setVar('featured_courses', $featuredCourses);
    }

    protected function getFeaturedCourses()
    {
        $service = new IndexService();

        return $service->getSimpleFeaturedCourses();
    }

}
