<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\Reel;
use App\Models\ReelReply;
use App\Models\CommentReel;
use Illuminate\Http\Request;
use App\Models\Notifications;
use App\Models\CommentLikeReel;
use Illuminate\Support\Facades\Validator;

class CommentReelController extends Controller
{
    public function store(Request $request)
    {
        try {
            $reel = $this->validateAndGetReel($request);
            $comment = $this->createComment($request);

            $this->handleNotifications($reel, $comment);

            return $this->buildSuccessResponse($comment, $reel);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => __('general.error') . ' - ' . $e->getMessage()],
            ]);
        }
    }

    private function validator(array $data)
    {
        $messages = [
            'comment.required' => __('general.please_write_something'),
        ];

        return Validator::make($data, [
            'comment' => 'required|max:500',
        ], $messages);
    }

    private function validateAndGetReel(Request $request)
    {
        $input = $request->all();
        $validator = $this->validator($input);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $reel = Reel::findOrFail($request->reel_id);

        return $reel;
    }

    private function createComment(Request $request)
    {
        $commentData = [
            'reply' => trim(Helper::checkTextDb($request->comment)),
            'reels_id' => $request->reel_id,
            'user_id' => auth()->id()
        ];

        if ($request->isReply) {
            return $this->createReply($commentData, $request->isReply);
        }

        $comment = CommentReel::create($commentData);

        return $comment;
    }

    private function createReply(array $commentData, $parentCommentId)
    {
        return ReelReply::create(array_merge($commentData, [
            'comment_reels_id' => $parentCommentId
        ]));
    }

    private function handleNotifications(Reel $reel, $comment)
    {
        if (auth()->id() != $reel->user_id && $reel->user->notify_commented_reel) {
            Notifications::send($reel->user_id, auth()->id(), 30, $reel->id);
        }

        Helper::sendNotificationMention($comment->reply, $reel->id, 'reel');
    }

    private function buildSuccessResponse($comment, Reel $reel)
    {
        $isReply = $comment instanceof ReelReply;
        $commentId = $isReply ? $comment->comment_reels_id : $comment->id;
        $replyId = $isReply ? $comment->id : null;

        $comment->reels->increment('comments_count');

        $viewData = [
            'comment' => $comment,
            'user' => auth()->user(),
            'isReply' => $isReply,
            'commentId' => $commentId,
            'replyId' => $replyId,
            'creator' => $reel->user_id,
            'fromReplies' => false,


        ];

        return response()->json([
            'success' => true,
            'isReply' => $isReply,
            'idComment' => $commentId,
            'data' => view('reels.comment-single-reel', $viewData)->render()
        ]);
    }

    public function loadCommentsOnReel(Request $request)
    {
        $reel = Reel::with(['user:id,name,username,avatar,hide_name', 'comments.replies'])
            ->findOrFail($request->id);

        return response()->json([
            'comments' => view(
                'reels.all-comments-reel',
                [
                    'comments' => $reel?->comments()->latest('id')->get() ?? collect([]),
                    'reelId' => $reel->id,
                    'creator' => $reel->user_id,
                    'isReply' => false,
                    'fromReplies' => false,
                ]
            )->render()
        ]);
    }

    public function delete(Request $request)
    {
        $comment = CommentReel::findOrFail($request->id);

        if ($comment->user_id == auth()->id() || $comment->reels->user_id == auth()->id()) {

            $comment->reels->decrement('comments_count');

            $totalComments = $comment->reels->comments_count;

            $comment->delete();

            return response()->json([
                'success' => true,
                'total' => $totalComments
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function deleteReply(Request $request)
    {
        $reply = ReelReply::findOrFail($request->id);

        if ($reply->user_id == auth()->id() || $reply->reels->user_id == auth()->id()) {

            $reply->reels->decrement('comments_count');

            $reply->delete();

            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function like(Request $request)
    {
        $id = $request->comment_id;
        $type = $request->typeComment;

        if ($type == 'isComment') {
            $comment = CommentReel::with(['likes'])->whereId($id)->with(['user'])->firstOrFail();

            // Find Like on comments likes if exists
            $commentLike = CommentLikeReel::whereUserId(auth()->id())
                ->whereCommentReelsId($id)
                ->whereUserId(auth()->id())
                ->first();
        } else {
            $comment = ReelReply::with(['likes'])->whereId($id)->with(['user'])->firstOrFail();

            // Find Like on comments likes if exists
            $commentLike = CommentLikeReel::whereUserId(auth()->id())
                ->orWhere('reel_replies_id', $id)
                ->whereUserId(auth()->id())
                ->first();
        }


        if ($commentLike) {
            $commentLike->delete();

            return response()->json([
                'success' => true,
                'type' => 'unlike'
            ]);
        } else {
            $sql = new CommentLikeReel();
            $sql->user_id = auth()->id();

            if ($type == 'isComment') {
                $sql->comment_reels_id = $comment->id;
            } else {
                $sql->reel_replies_id = $comment->id;
            }

            $sql->save();

            if ($comment->user_id != auth()->id() && $comment->user->notify_liked_comment == 'yes') {
                Notifications::send($comment->user_id, auth()->id(), 32, $comment->reels->id);
            }

            return response()->json([
                'success' => true,
                'type' => 'like'
            ]);
        }
    }
}
