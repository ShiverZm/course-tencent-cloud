<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Consult;

use App\Models\Consult as ConsultModel;
use App\Repos\Chapter as ChapterRepo;
use App\Repos\Course as CourseRepo;
use App\Repos\User as UserRepo;
use App\Services\Logic\ConsultTrait;
use App\Services\Logic\Service as LogicService;

class ConsultInfo extends LogicService
{

    use ConsultTrait;

    public function handle($id)
    {
        $consult = $this->checkConsult($id);

        return $this->handleConsult($consult);
    }

    protected function handleConsult(ConsultModel $consult)
    {
        $result = [
            'id' => $consult->id,
            'question' => $consult->question,
            'answer' => $consult->answer,
            'rating' => $consult->rating,
            'private' => $consult->private,
            'published' => $consult->published,
            'deleted' => $consult->deleted,
            'like_count' => $consult->like_count,
            'create_time' => $consult->create_time,
            'update_time' => $consult->update_time,
        ];

        $result['course'] = $this->handleCourseInfo($consult);
        $result['chapter'] = $this->handleChapterInfo($consult);
        $result['owner'] = $this->handleOwnerInfo($consult);
        $result['replier'] = $this->handleReplierInfo($consult);

        return $result;
    }

    protected function handleCourseInfo(ConsultModel $consult)
    {
        $courseRepo = new CourseRepo();

        $course = $courseRepo->findById($consult->course_id);

        if (!$course) return new \stdClass();

        return [
            'id' => $course->id,
            'title' => $course->title,
            'cover' => $course->cover,
        ];
    }

    protected function handleChapterInfo(ConsultModel $consult)
    {
        $chapterRepo = new ChapterRepo();

        $chapter = $chapterRepo->findById($consult->chapter_id);

        if (!$chapter) return new \stdClass();

        return [
            'id' => $chapter->id,
            'title' => $chapter->title,
        ];
    }

    protected function handleOwnerInfo(ConsultModel $consult)
    {
        $userRepo = new UserRepo();

        $owner = $userRepo->findById($consult->owner_id);

        if (!$owner) return new \stdClass();

        return [
            'id' => $owner->id,
            'name' => $owner->name,
            'avatar' => $owner->avatar,
        ];
    }

    protected function handleReplierInfo(ConsultModel $consult)
    {
        $userRepo = new UserRepo();

        $replier = $userRepo->findById($consult->replier_id);

        if (!$replier) return new \stdClass();

        return [
            'id' => $replier->id,
            'name' => $replier->name,
            'avatar' => $replier->avatar,
        ];
    }

}
