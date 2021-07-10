<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Page;

use App\Models\Page as PageModel;
use App\Models\User as UserModel;
use App\Services\Logic\PageTrait;
use App\Services\Logic\Service as LogicService;

class PageInfo extends LogicService
{

    use PageTrait;

    public function handle($id)
    {
        $user = $this->getCurrentUser(true);

        $page = $this->checkPage($id);

        return $this->handlePage($page, $user);
    }

    protected function handlePage(PageModel $page, UserModel $user)
    {
        $page->content = kg_parse_markdown($page->content);

        $me = $this->handleMeInfo($page, $user);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'content' => $page->content,
            'published' => $page->published,
            'deleted' => $page->deleted,
            'create_time' => $page->create_time,
            'update_time' => $page->update_time,
            'me' => $me,
        ];
    }

    protected function handleMeInfo(PageModel $page, UserModel $user)
    {
        $me = ['owned' => 0];

        if ($page->published == 1) {
            $me['owned'] = 1;
        }

        return $me;
    }

}
