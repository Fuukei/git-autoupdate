<?php
require_once __DIR__ . '/fs.php';
class GITAUCronLog
{
    /**
     * 存储的时间
     */
    public string $datetime;
    /**
     * 是否为错误日志
     */
    public bool $isError;
    /**
     * 日志内容
     */
    public string $msg;
    /**
     * 主题名称
     */
    public string $theme_name;
    /**
     * 更新前的主题版本号
     */
    public string $theme_version_pre;
    /**
     * 更新后的主题版本号
     */
    public string $theme_version_post;
    public function __construct(bool $isError = false, string $datetime, string $msg, string $theme_name, string $theme_version_pre, string $theme_version_post)
    {
        $this->datetime = $datetime;
        $this->msg = $msg;
        $this->theme_name = $theme_name;
        $this->theme_version_pre = $theme_version_pre;
        $this->theme_version_post = $theme_version_post;
        $this->isError = $isError;
    }

    public static function serialize(GITAUCronLog $logCls)
    {
        $array = array(
            "datetime" => $logCls->datetime,
            "msg" => $logCls->msg,
            "theme_name" => $logCls->theme_name,
            "theme_version_pre" => $logCls->theme_version_pre,
            "theme_version_post" => $logCls->theme_version_post,
            "isError" => $logCls->isError
        );
        return json_encode($array);
    }
    public static function deserialize($json)
    {
        $array = json_decode($json);
        return new GITAUCronLog($array->isError, $array->datetime, $array->msg, $array->theme_name, $array->theme_version_pre, $array->theme_version_post,);
    }
}
function gitau_cron_log_err(string $msg)
{
    $theme = wp_get_theme();
    $log = new GITAUCronLog(true, date("Y-m-d H:i:s"), GITAU_MSG_PREFIX . ": " . $msg, $theme->get('Name'), $theme->get('Version'), $theme->get('Version'));
    $logJson = GITAUCronLog::serialize($log);
    gitau_write_log($logJson);
}
function gitau_cron_log_success(string $theme_version_pre, string $theme_version_post)
{
    $theme = wp_get_theme();
    $log = new GITAUCronLog(false, date("Y-m-d H:i:s"), "主题更新成功", $theme->get('Name'), $theme_version_pre, $theme_version_post);
    $logJson = GITAUCronLog::serialize($log);
    gitau_write_log($logJson);
}
