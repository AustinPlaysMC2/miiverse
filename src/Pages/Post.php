<?php
/**
 * Holds the post handler.
 */

namespace Miiverse\Pages;

use Miiverse\Community\Community;
use Miiverse\CurrentSession;
use Miiverse\DB;
use Miiverse\User;

/**
 * Post handler.
 *
 * @author Repflez
 */
class Post extends Page
{
    /**
     * Creates or replies to a post.
     */
    public function submit()
    {
        $kind = $_POST['kind'] ?? null;

        if ($kind == 'post') {
            $title_id = $_POST['olive_title_id'];
            $id = $_POST['olive_community_id'];
            $feeling = $_POST['feeling_id'];
            $spoiler = $_POST['is_spoiler'] ?? 0;
            $type = $_POST['_post_type'];

            switch ($type) {
                case 'body':
                    $body = $_POST['body'];

                    $postId = DB::table('posts')->insertGetId([
                        'community' => $id,
                        'content'   => $body,
                        'feeling'   => $feeling,
                        'user_id'   => CurrentSession::$user->id,
                        'spoiler'   => intval($spoiler),
                    ]);
                    break;
                case 'painting':
                    $painting = base64_decode($_POST['painting']);
                    $painting_name = CurrentSession::$user->id.'-'.time().'.png';

                    file_put_contents(path('public/img/drawings/'.$painting_name), $painting);

                    $postId = DB::table('posts')->insertGetId([
                        'community' => $id,
                        'image'     => $painting_name,
                        'feeling'   => $feeling,
                        'user_id'   => CurrentSession::$user->id,
                        'spoiler'   => intval($spoiler),
                    ]);
                    break;
                default:
                    break;
            }

            if (!CurrentSession::$user->posted) {
                DB::table('users')->where('user_id', '=', CurrentSession::$user->id)->update(['posted' => 1]);
            }

            DB::table('users')->where('user_id', '=', CurrentSession::$user->id)->increment('posts');

            redirect(route('title.community', ['tid' => hashid($title_id), 'id' => hashid($id)]));
        } elseif ($kind = 'reply') {
            $post_id = $_POST['olive_post_id'];
            $feeling = $_POST['feeling_id'];
            $spoiler = $_POST['is_spoiler'] ?? 0;
            $type = $_POST['_post_type'];

            switch ($type) {
                case 'body':
                    $body = $_POST['body'];

                    DB::table('comments')->insert([
                        'post'    => $post_id,
                        'content' => $body,
                        'feeling' => $feeling,
                        'user'    => CurrentSession::$user->id,
                        'spoiler' => intval($spoiler),
                    ]);
                    break;
                case 'painting':
                    $painting = base64_decode($_POST['painting']);
                    $painting_name = CurrentSession::$user->id.'-'.time().'.png';

                    file_put_contents(path('public/img/drawings/'.$painting_name), $painting);

                    DB::table('comments')->insert([
                        'post'    => $post_id,
                        'image'   => $painting_name,
                        'feeling' => $feeling,
                        'user'    => CurrentSession::$user->id,
                        'spoiler' => intval($spoiler),
                    ]);
                    break;
            }

            if (!CurrentSession::$user->posted) {
                DB::table('users')->where('user_id', '=', CurrentSession::$user->id)->update(['posted' => 1]);
            }

            DB::table('posts')->where('id', '=', $post_id)->increment('comments');

            redirect(route('post.show', ['id' => hashid($post_id)]));
        }
        exit;
    }

    /**
     * Shows an individual post.
     */
    public function show(string $id) : string
    {
        $post_id = dehashid($id);
        $comments = [];

        $post = DB::table('posts')
                        ->where('id', $post_id)
                        ->first();

        $post->community = new Community($post->community);
        $post->user = User::construct($post->user_id);

        $comments_temp = DB::table('comments')
                    ->where('post', $post->id)
                    ->orderBy('created', 'asc')
                    ->limit(20)
                    ->get(['id', 'created', 'edited', 'deleted', 'user', 'content', 'type', 'image', 'feeling', 'spoiler']);

        $feeling = ['normal', 'happy', 'like', 'surprised', 'frustrated', 'puzzled'];

        if ($comments_temp) {
            foreach ($comments_temp as $comment) {
                $comment->user = User::construct($comment->user);
                $comments[] = $comment;
            }
        }

        return view('posts/view', compact('post', 'comments', 'feeling'));
    }

    /**
     * Reply form for posts.
     *
     * @return string
     */
    public function reply($id) : string
    {
        $post = dehashid($id);

        if (!is_array($post)) {
            return view('errors/404');
        }

        $meta = DB::table('posts')
                    ->where('id', $post)
                    ->first();

        if (!$meta) {
            return view('errors/404');
        }

        $community = DB::table('communities')
                    ->where('id', $meta->community)
                    ->first();

        if (!$community) {
            return view('errors/404');
        }

        return view('posts/reply', compact('meta', 'community'));
    }

    /**
     * Create a Yeah for this post.
     *
     * @var string
     *
     * @return string
     */
    public function yeahs(string $post_id) : string
    {
        $post_id = dehashid($post_id);

        $post = DB::table('posts')
                    ->where('id', $post_id)
                    ->first();

        if ($post) {
            DB::table('likes')->insert([
                    'type' => 0,
                    'id'   => $post->id,
                    'user' => CurrentSession::$user->id,
                ]);

            DB::table('posts')->where('id', $post_id)->increment('likes');
        } else {
            header('HTTP/1.1 403 Forbidden');
        }

        return '';
    }

    /**
     * Remove a Yeah for this post.
     *
     * @var string
     *
     * @return string
     */
    public function removeYeahs(string $post_id) : string
    {
        $post_id = dehashid($post_id);

        $post = DB::table('posts')
                    ->where('id', $post_id)
                    ->first();

        if ($post) {
            DB::table('likes')
                ->where([
                    'type' => 0,
                    'id'   => $post->id,
                    'user' => CurrentSession::$user->id,
                ])
                ->delete();

            DB::table('posts')->where('id', $post_id)->decrement('likes');
        } else {
            header('HTTP/1.1 403 Forbidden');
        }

        return '';
    }
}
