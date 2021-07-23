<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Services;

use App\Builders\CommentList as CommentListBuilder;
use App\Builders\ReportList as ReportListBuilder;
use App\Library\Paginator\Query as PagerQuery;
use App\Models\Comment as CommentModel;
use App\Models\Reason as ReasonModel;
use App\Models\Report as ReportModel;
use App\Repos\Comment as CommentRepo;
use App\Repos\Report as ReportRepo;
use App\Repos\User as UserRepo;
use App\Services\Logic\Comment\AfterCreateTrait;
use App\Services\Logic\Comment\CommentInfo as CommentInfoService;
use App\Services\Logic\Comment\CountTrait;
use App\Validators\Comment as CommentValidator;

class Comment extends Service
{

    use AfterCreateTrait;
    use CountTrait;

    public function getReasons()
    {
        return ReasonModel::commentRejectOptions();
    }

    public function getComments()
    {
        $pagerQuery = new PagerQuery();

        $params = $pagerQuery->getParams();

        $params['deleted'] = $params['deleted'] ?? 0;

        $sort = $pagerQuery->getSort();
        $page = $pagerQuery->getPage();
        $limit = $pagerQuery->getLimit();

        $commentRepo = new CommentRepo();

        $pager = $commentRepo->paginate($params, $sort, $page, $limit);

        return $this->handleComments($pager);
    }

    public function getComment($id)
    {
        return $this->findOrFail($id);
    }

    public function getCommentInfo($id)
    {
        $service = new CommentInfoService();

        return $service->handle($id);
    }

    public function getReports($id)
    {
        $reportRepo = new ReportRepo();

        $where = [
            'item_id' => $id,
            'item_type' => ReportModel::ITEM_COMMENT,
            'reviewed' => 0,
        ];

        $pager = $reportRepo->paginate($where);

        $pager = $this->handleReports($pager);

        return $pager->items;
    }

    public function updateComment($id)
    {
        $comment = $this->findOrFail($id);

        $post = $this->request->getPost();

        $validator = new CommentValidator();

        $data = [];

        if (isset($post['content'])) {
            $data['content'] = $validator->checkContent($post['content']);
        }

        if (isset($post['published'])) {
            $data['published'] = $validator->checkPublishStatus($post['published']);
        }

        $comment->update($data);

        return $comment;
    }

    public function deleteComment($id)
    {
        $comment = $this->findOrFail($id);

        $comment->deleted = 1;

        $comment->update();

        $validator = new CommentValidator();

        if ($comment->parent_id > 0) {
            $parent = $validator->checkParent($comment->parent_id);
            $this->decrCommentReplyCount($parent);
        }

        $item = $validator->checkItem($comment->item_id, $comment->item_type);

        $this->decrItemCommentCount($item);

        $owner = $this->findUser($comment->owner_id);

        $this->decrUserCommentCount($owner);

        return $comment;
    }

    public function restoreComment($id)
    {
        $comment = $this->findOrFail($id);

        $comment->deleted = 0;

        $comment->update();

        $validator = new CommentValidator();

        if ($comment->parent_id > 0) {
            $parent = $validator->checkParent($comment->parent_id);
            $this->incrCommentReplyCount($parent);
        }

        $item = $validator->checkItem($comment->item_id, $comment->item_type);

        $this->incrItemCommentCount($item);

        $owner = $this->findUser($comment->owner_id);

        $this->incrUserCommentCount($owner);

        return $comment;
    }

    public function moderate($id)
    {
        $type = $this->request->getPost('type', ['trim', 'string']);
        $reason = $this->request->getPost('reason', ['trim', 'string']);

        $comment = $this->findOrFail($id);

        $validator = new CommentValidator();

        if ($type == 'approve') {

            $comment->published = CommentModel::PUBLISH_APPROVED;

        } elseif ($type == 'reject') {

            $validator->checkRejectReason($reason);

            $comment->published = CommentModel::PUBLISH_REJECTED;
        }

        $comment->update();

        if ($type == 'approve') {

            $owner = $this->findUser($comment->owner_id);

            $item = $validator->checkItem($comment->item_id, $comment->item_type);

            $this->incrItemCommentCount($item);
            $this->incrUserCommentCount($owner);

            if ($comment->parent_id == 0) {
                $this->handleItemCommentedNotice($item, $comment);
            }

            if ($comment->parent_id > 0) {
                $parent = $validator->checkParent($comment->parent_id);
                $this->incrCommentReplyCount($parent);
                $this->handleCommentRepliedNotice($comment);
            }

            $this->handleCommentPostPoint($comment);

            $this->eventsManager->fire('Comment:afterApprove', $this, $comment);

        } elseif ($type == 'reject') {

            $this->eventsManager->fire('Comment:afterReject', $this, $comment);
        }

        return $comment;
    }

    public function report($id)
    {
        $accepted = $this->request->getPost('accepted', 'int', 0);
        $deleted = $this->request->getPost('deleted', 'int', 0);

        $comment = $this->findOrFail($id);

        $reportRepo = new ReportRepo();

        $reports = $reportRepo->findItemPendingReports($comment->id, ReportModel::ITEM_COMMENT);

        if ($reports->count() > 0) {
            foreach ($reports as $report) {
                $report->accepted = $accepted;
                $report->reviewed = 1;
                $report->update();
            }
        }

        $comment->report_count = 0;

        if ($deleted == 1) {
            $comment->deleted = 1;
        }

        $comment->update();
    }

    protected function findOrFail($id)
    {
        $validator = new CommentValidator();

        return $validator->checkComment($id);
    }

    protected function findUser($id)
    {
        $userRepo = new UserRepo();

        return $userRepo->findById($id);
    }

    protected function handleComments($pager)
    {
        if ($pager->total_items > 0) {

            $builder = new CommentListBuilder();

            $pipeA = $pager->items->toArray();
            $pipeB = $builder->handleUsers($pipeA);
            $pipeC = $builder->objects($pipeB);

            $pager->items = $pipeC;
        }

        return $pager;
    }

    protected function handleReports($pager)
    {
        if ($pager->total_items > 0) {

            $builder = new ReportListBuilder();

            $items = $pager->items->toArray();

            $pipeA = $builder->handleUsers($items);
            $pipeB = $builder->objects($pipeA);

            $pager->items = $pipeB;
        }

        return $pager;
    }

}
