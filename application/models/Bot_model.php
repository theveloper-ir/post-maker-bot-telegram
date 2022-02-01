<?php
class Bot_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function save_new_post_record($data = [])
    {
        $this->db->insert('posts_tbl',$data);
        $insert_id = $this->db->insert_id();
        return $insert_id;
    }

    public function get_file_info_with_message_id_in_bot($user_id)
    {

        $creator_id =  $this->db
            ->where(['user_id'=>$user_id])
            ->get('users_tbl')
            ->result_array()[0]['id'];


        $res = $this->db
            ->where(['creator_id'=>$creator_id])
            ->get('posts_tbl')
            ->result_array();
        return $res;
    }

    public function update_post_info($data, $where)
    {
        $this->db->where($where);
        return $this->db->update('posts_tbl', $data);
    }

    public function get_last_post_create_by_user($user_id)
    {
        $creator_id =  $this->db
            ->where(['user_id'=>$user_id])
            ->get('users_tbl')
            ->result_array()[0]['id'];

        $this->db->where(['creator_id'=>$creator_id]);
        $this->db->order_by("created_time", "DESC");
        $this->db->limit(1);
        return $this->db
            ->get('posts_tbl')
            ->result_array();
    }

    public function get_post_with_code($code = '')
    {
        if(!empty($code))
        {
            $this->db
                ->where(['code'=>$code]);
        }
        $res = $this->db
            ->get('posts_tbl')
            ->result_array();
        return $res;
    }

    public function get_posts_in_channel()
    {
        $this->db
            ->where(['message_id_in_channel!='=>null]);
        $res = $this->db
            ->get('posts_tbl')
            ->result_array();
        return $res;
    }

    public function delete_post($where)//one record is post in robot
    {
        $this->db->where($where);
        return $this->db->delete('posts_tbl');
    }

    public function get_user_info($user_id = null)//if null return all user
    {
        if($user_id != null)
            $this->db->where(['user_id'=>$user_id]);
        $ret = $this->db
            ->get('users_tbl')
            ->result_array();
        return $ret;
    }

    public function save_new_user_record($data = [])
    {
        return $this->db->insert('users_tbl',$data);
    }

    public function get_options($option_name)
    {
        $ret = $this->db
            ->where(['option_name'=>$option_name])
            ->get('options_tbl')
            ->result_array();
        return $ret;
    }

    public function set_options($option_name,$data)
    {
        return
            $this->db
                ->where(['option_name'=>$option_name])
                ->update('options_tbl',$data);
    }

    public function change_options_value($option_name, $data)
    {
        return $this->db
            ->where(['option_name'=>$option_name])
            ->update('options_tbl', $data);

    }

    public function save_new_download($data)
    {
        return $this->db->insert('downloads_tbl',$data);
    }

    public function get_statics()
    {
        $post_count = $this->db->count_all_results('posts_tbl');
        $user_count = $this->db->count_all_results('users_tbl');
        $download_count = $this->db->count_all_results('downloads_tbl');
        return [
            "post_count"=>$post_count,
            "user_count"=>$user_count,
            "download_count"=>$download_count,
        ];
    }

    public function change_user_location_in_bot($user_id, $location)
    {
        return $this->db
            ->where(['user_id '=>$user_id])
            ->update('users_tbl', ['location_in_bot'=>$location]);
    }
}