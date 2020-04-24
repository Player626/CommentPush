<?php
/**
 * 评论通知推送多服务
 *
 * @package CommentPush
 * @author 高彬展,奥秘Sir
 * @version 1.1.0
 * @link https://github.com/gaobinzhan/CommentPush
 */

require 'lib/QQService.php';
require 'lib/WeChatService.php';
require 'lib/AliYunEmailService.php';

class CommentPush_Plugin implements Typecho_Plugin_Interface
{
    protected static $comment;
    protected static $active;

    /**
     * @return string|void
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->comment = [__CLASS__, 'pushServiceReady'];
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = [__CLASS__, 'pushServiceGo'];
        return _t('CommentPush插件启用成功');
    }


    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    /**
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h2>推送服务配置</h2>');
        $form->addItem($serviceTitle);

        $services = new Typecho_Widget_Helper_Form_Element_Checkbox('services', [
            "QQService" => _t('Qmsg酱'),
            "WeChatService" => _t('Server酱'),
            "AliYunEmailService" => _t('阿里云邮件')
        ], 'services', _t('推送服务 多选同时推送'), _t('插件作者：<a href="https://www.gaobinzhan.com">高彬展</a>&nbsp;<a href="https://blog.say521.cn/">奥秘Sir</a>'));
        $form->addInput($services->addRule('required', _t('必须选择一项推送服务')));

        $isPushBlogger = new Typecho_Widget_Helper_Form_Element_Radio('isPushBlogger', [
            1 => '是',
            0 => '否'
        ], 1, _t('当评论者为博主本人不推送'), _t('如果选择“是”，博主本人写的评论将不推送'));
        $form->addInput($isPushBlogger);

        $isPushCommentReply = new Typecho_Widget_Helper_Form_Element_Radio('isPushCommentReply', [
            1 => '是',
            0 => '否'
        ], 1, _t('当作者回复评论向对方发送邮件'), _t('如果选择“否”，将不推送'));
        $form->addInput($isPushCommentReply);

        $qqServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $qqServiceTitle->html('<h2>Qmsg酱配置</h2>');
        $form->addItem($qqServiceTitle);

        $qqApiUrl = new Typecho_Widget_Helper_Form_Element_Text('qqApiUrl', NULL, NULL, _t('Qmsg酱接口'), _t("当选择Qmsg酱必须填写"));
        $form->addInput($qqApiUrl);

        $receiveQq = new Typecho_Widget_Helper_Form_Element_Text('receiveQq', NULL, NULL, _t('接收消息的QQ，可以添加多个，以英文逗号分割'), _t("当选择Qmsg酱必须填写（指定的QQ必须在您的QQ号列表中）"));
        $form->addInput($receiveQq);


        $weChatServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $weChatServiceTitle->html('<h2>Server酱配置</h2>');
        $form->addItem($weChatServiceTitle);

        $weChatScKey = new Typecho_Widget_Helper_Form_Element_Text('weChatScKey', NULL, NULL, _t('Server酱 SCKEY'), _t("当选择Server酱必须填写"));
        $form->addInput($weChatScKey);

        $aliYunEmailServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $aliYunEmailServiceTitle->html('<h2>阿里云邮件配置</h2>');
        $form->addItem($aliYunEmailServiceTitle);

        $aliYunRegion = new Typecho_Widget_Helper_Form_Element_Select('regionId', [
            AliYunEmailService::HANGZHOU => _t('华东1(杭州)'),
            AliYunEmailService::SINGAPORE => _t('亚太东南1(新加坡)'),
            AliYunEmailService::SYDNEY => _t('亚太东南2(悉尼)')
        ], NULL, _t('服务地址'), _t('选择邮件推送所在服务器区域'));
        $form->addInput($aliYunRegion);

        $aliYunAccessKeyId = new Typecho_Widget_Helper_Form_Element_Text('accessKeyId', NULL, NULL, _t('AccessKey ID'), _t('请填入在阿里云生成的AccessKey ID'));
        $form->addInput($aliYunAccessKeyId);

        $aliYunAccessKeySecret = new Typecho_Widget_Helper_Form_Element_Text('accessKeySecret', NULL, NULL, _t('Access Key Secret'), _t('请填入在阿里云生成的Access Key Secret'));
        $form->addInput($aliYunAccessKeySecret);


        $aliYunFromAlias = new Typecho_Widget_Helper_Form_Element_Text('fromAlias', NULL, NULL, _t('发件人名称'), _t('邮件中显示的发信人名称，留空为博客名称'));
        $form->addInput($aliYunFromAlias);

        $aliYunAccountName = new Typecho_Widget_Helper_Form_Element_Text('accountName', NULL, NULL, _t('发件邮箱地址'), _t('邮件中显示的发信地址'));
        $form->addInput($aliYunAccountName->addRule('email', _t('请输入正确的邮箱地址')));


    }

    /**
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }


    public static function pushServiceReady($comment, $active)
    {
        self::$comment = $comment;
        self::$active = $active;

        return $comment;
    }

    public static function pushServiceGo($comment)
    {
        $options = Helper::options();
        $plugin = $options->plugin('CommentPush');

        $services = $plugin->services;

        if (!$services || $services == 'services') return false;


        self::$comment['coid'] = $comment->coid;

        /** @var QQService | WeChatService | AliYunEmailService $service */
        foreach ($services as $service) call_user_func([$service, '__handler'], self::$active, self::$comment, $plugin);
    }
}