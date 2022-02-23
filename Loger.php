<?php


class Loger {
    public static function filePath()
    {
        return ( plugin_dir_path( __FILE__ ) . 'import.log' );
    }

    public static function error($message)
    {
        static::toFile('error: '.$message);
        return $message;
    }

    public static function info($message)
    {
        static::toFile('info: '.$message);
        return $message;
    }

    public static function toFile($message)
    {
        $message = date('Y-m-d H:i:s ').$message."\n";
        $handle = fopen(static::filePath(), 'a');
        fputs($handle, $message);
        fclose($handle);
    }

}
