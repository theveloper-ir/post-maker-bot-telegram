<?php

if (!function_exists('is_admin')) {

    function is_admin($user_id)
    {
        $is_admin = false;
        $ci =& get_instance();
        $admins = $ci->config->item('admins_robot');
        if(in_array($user_id, $admins))
            $is_admin = true;
        return $is_admin;
    }
}

if (!function_exists('is_unique_media')) {

    function is_unique_media($file_unique_id)
    {
        $is_admin = false;
        $ci =& get_instance();

    }
}