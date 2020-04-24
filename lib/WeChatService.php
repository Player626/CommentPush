<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

require_once 'Contract/ServiceInterface.php';

class WeChatService implements ServiceInterface
{
    public function __handler($active, $comment, $plugin)
    {
        $isPushBlogger = $plugin->isPushBlogger;
        if ($comment['authorId'] == 1 && $isPushBlogger == 1 ) return false;


        $weChatScKey = $plugin->weChatScKey;
        if (empty($weChatScKey)) return false;

        $title = $active->title;
        $author = $comment['author'];
        $link = $active->permalink;
        $context = $comment['text'];

        $template = '标题：' . $title . PHP_EOL
            . '评论人：' . $author . PHP_EOL
            . '评论内容：' . $context . PHP_EOL
            . '链接：' . $link . '#comment-' . $comment['coid'];

        $params = http_build_query([
            'text' => '有人给你评论啦！！',
            'desp' => $template
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $params
            ]
        ]);

        return file_get_contents('https://sc.ftqq.com/' . $weChatScKey . '.send', false, $context);
    }
}