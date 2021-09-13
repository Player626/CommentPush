<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 * @modify 小码农 <chengshongguo@qq.com> 增加实例化方法
 * @modify FlyRenxing <flyrenxing@qq.com> 新增任性推接口
 */

require_once 'Service.php';

class QQService extends Service
{
    public static function create(){

		static $instance ;
		if (!$instance){
		    $instance = new QQService();
		}
		return $instance;
	}
    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1) return false;

            $qqServiceId = $plugin->qqServiceId;
            $qqApiUrl = $plugin->qqApiUrl;
            $receiveQq = $plugin->receiveQq;

            if (empty($qqApiUrl) || empty($receiveQq)) throw new \Exception('缺少QQ推送服务配置参数');

            $title = $active->title;
            $author = $comment['author'];
            $link = $active->permalink;
            $context = $comment['text'];

            $template = '标题：' . $title . PHP_EOL
                . '评论人：' . $author . " [{$comment['ip']}]" . PHP_EOL
                . '评论内容：' . $context . PHP_EOL
                . '链接：' . $link . '#comment-' . $comment['coid'];

            switch ($qqServiceId)
            {
                case 1:
                    $params = [
                        'qq' => $receiveQq,
                        'msg' => $template
                    ];

                    $context = stream_context_create([
                        'http' => [
                            'method' => 'POST',
                            'header' => 'Content-type: application/x-www-form-urlencoded',
                            'content' => http_build_query($params)
                        ]
                    ]);
                    break;
                case 0:
                    $msg_meta = array('type' => 'qq', 'data' => $receiveQq);

                    $body = array('content' => $template, 'meta' => $msg_meta);

                    $context = stream_context_create([
                        'http' => [
                            'method' => 'POST',
                            'header' => 'Content-type: application/json',
                            'content' => json_encode($body)
                        ]
                    ]);
                    break;
            }



            $result = file_get_contents($qqApiUrl, false, $context);
            self::logger(__CLASS__, $receiveQq, $params, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }


}