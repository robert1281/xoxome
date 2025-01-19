<?php
// 设置错误日志路径
ini_set('error_log', __DIR__ . '/logs/error.log');

// 确保日志目录存在
if(!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// 自定义错误处理函数
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s]') . " Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message);
    
    // 如果是致命错误，显示友好的错误页面
    if($errno == E_ERROR || $errno == E_USER_ERROR) {
        include 'error.html';
        exit(1);
    }
    
    return false;
}

// 设置错误处理函数
set_error_handler('custom_error_handler');

// 设置未捕获的异常处理函数
set_exception_handler(function($e) {
    $error_message = date('[Y-m-d H:i:s]') . " Uncaught Exception: " . $e->getMessage() . 
                    " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    error_log($error_message);
    include 'error.html';
});

// 记录PHP错误
register_shutdown_function(function() {
    $error = error_get_last();
    if($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = date('[Y-m-d H:i:s]') . " Fatal Error: " . $error['message'] . 
                        " in " . $error['file'] . " on line " . $error['line'] . "\n";
        error_log($error_message);
        include 'error.html';
    }
});
?> 