<?php
if ( ! function_exists('remove_link_from_text'))
{
    function remove_link_from_text($str)
    {
        $str = preg_replace("/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i","", $str);
        return $str;
    }
}

if ( ! function_exists('remove_hashtag_from_text'))
{
    function remove_hashtag_from_text($str)
    {
        $str = preg_replace('/\#[A-Za-z-0-9]+/m',"", $str);
        return $str;
    }
}

if ( ! function_exists('remove_username_from_text'))
{
    function remove_username_from_text($str)
    {
        $str = preg_replace("/.*[\W](@(?=.{5,64}(?:\s|$))(?![_])(?!.*[_]{2})[a-zA-Z0-9_]+(?<![_.])).*/","", $str);
        return $str;
    }
}

if ( ! function_exists('str_replace_limit'))
{
    function str_replace_limit($find, $replacement, $subject, $limit = 0){
        if ($limit == 0)
            return str_replace($find, $replacement, $subject);
        $ptn = '/' . preg_quote($find,'/') . '/';
        return preg_replace($ptn, $replacement, $subject, $limit);
    }
}

if ( ! function_exists('file_code_generator'))
{
    function file_code_generator($file_unique_id)
    {
        $file_code = substr(md5(time().$file_unique_id),0,10);
        $ci =& get_instance();
        $file_info_result = $ci->db->where(['code'=>$file_code])
            ->get('posts_tbl')
            ->result_array();
        if(isset($file_info_result[0]))
            file_code_generator($file_unique_id);
        else
            return $file_code;
    }
}


if ( ! function_exists('channel_lock_finder'))
{
    function channel_lock_finder($channel_id, $channels)
    {
        foreach ($channels as $key => $val) {
            if ($val[0] === $channel_id) {
                return $key;
            }
        }
        return null;
    }
}