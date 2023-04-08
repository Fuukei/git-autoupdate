<?php

// 以JSON格式存储信息
function gitau_write_log($info)
{
    $file = null;
    $file_path = plugin_dir_path(__FILE__) . 'cron.log';
    try {
        // 将文件内容读取为一个数组，每个元素是一行
        $lines = file($file_path);
        // 获取数组的长度，即文件的行数
        $line_count = count($lines);
        // 如果行数大于等于五
        if ($line_count >= 4) {
            // 删除数组的第一个元素，即最前的一行
            array_shift($lines);
        }
        // 将要写入的信息追加到数组末尾
        $lines[] = $info;
        // 将数组重新拼接为一个字符串，每个元素之间用换行符分隔
        $content = implode("\n", $lines);
        // 以写入模式打开文件，覆盖原有内容
        $file = fopen($file_path, 'w');
        // 如果文件打开成功
        if ($file) {
            // 将拼接后的字符串写入文件
            fwrite($file, $content);
        } else {
            error_log(GITAU_MSG_PREFIX . ' cannot open file');
        }
    } catch (\Throwable $th) {
        error_log(GITAU_MSG_PREFIX . ' ' . $th->getMessage());
    } finally {
        // 关闭文件
        fclose($file);
        $file = null;
    }
}

function gitau_read_log()
{
    $file_path = plugin_dir_path(__FILE__) . 'cron.log';
    foreach (file($file_path) as $key => $line) {
        yield  GITAUCronLog::deserialize($line);
    }
}
