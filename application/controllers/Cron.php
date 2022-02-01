<?php

class Cron extends CI_Controller
{
    public function send_broadcast_message()
    {
        $this->config->load('bot_config');
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->database();
        $this->load->model('Bot_model');
        $this->load->helper(['text','admin','url']);

        $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
        $send_broadcast_message_data = json_decode($get_send_broadcast_message_status['data'],true);

        $is_started = true;//set true for limit execute for more count

        if(!$send_broadcast_message_data['is_start'])
        {
            $is_started = false;
            $send_broadcast_message_data['is_start'] = true;
            $json_data['data']=json_encode($send_broadcast_message_data);
            $this->Bot_model->set_options('send_broadcast_message',$json_data);
        }


        if($get_send_broadcast_message_status['option_value'] && !$is_started && !$send_broadcast_message_data['is_complete'] && $send_broadcast_message_data['type'] != 0)
        {
            if($send_broadcast_message_data['type'] == 1)
            {
                $all_user_info = $this->Bot_model->get_user_info(null);

                $block_count = 0;
                $count_all_user = count($all_user_info);
                $all_send_time = 0;

                for($i = 0; $i < $count_all_user; $i++)
                {
                    $this->benchmark->mark('start_send_time');

                    if($i % 15 == 0)
                    {
                        sleep(1);
                    }

                    $content = ['chat_id'=>$all_user_info[$i]['user_id'],'from_chat_id'=>$send_broadcast_message_data['from_chat_id'],'message_id'=>$send_broadcast_message_data['message_id']];
                    $send_message_result = $this->telegram->forwardMessage($content);

                    if(!$send_message_result['ok'] && $send_message_result['error_code'] == 429)
                        sleep($send_message_result['parameters']['retry_after']);
                    else if(!$send_message_result['ok'])
                        $block_count++;


                    $this->benchmark->mark('end_send_time');

                    $all_send_time += $this->benchmark->elapsed_time('start_send_time', 'end_send_time');
                }

                //set is complete for send broadcast message and set false option value
                $json_data ['data']=
                    [
                        'type'=>0,
                        'block_count'    => $block_count,
                        'count_all_user' => $count_all_user,
                        'is_start'       => true,
                        'is_complete'    => true,
                        'all_send_time'=>$all_send_time,
                        'average_send_time'=>($all_send_time/$count_all_user)
                    ];
                $option_data['option_value'] = 0;
                $option_data['data'] = json_encode($json_data['data']);

                $complete_send_broadcast_message_action = $this->Bot_model->set_options('send_broadcast_message',$option_data);

                if($complete_send_broadcast_message_action && $json_data['data']['is_complete'])
                {
                    $report_txt = "";
                    $report_txt .= "تعداد کل کاربران جهت ارسال : ".'`'.$json_data['data']['count_all_user'].'`'.PHP_EOL;
                    $report_txt .= "تعداد ارسالی کل : ".'`'.($count_all_user - $block_count).'`'.PHP_EOL;
                    $report_txt .= "تعداد بلاکی : ".'`'.$json_data['data']['block_count'].'`'.PHP_EOL;
                    $report_txt .= "زمان کلی ارسال : ".'`'.$all_send_time.'`'.PHP_EOL;
                    $report_txt .= "میانگین زمان ارسال : ".'`'.($all_send_time/$count_all_user).'`'.PHP_EOL;



                    $all_admin = $this->config->item('admins_robot');
                    foreach ($all_admin as $item)
                    {
                        $content = ['chat_id'=>$item,'text'=>$report_txt,'parse_mode'=>'markdown'];
                        $this->telegram->sendMessage($content);
                    }
                }
            }
            else if($send_broadcast_message_data['type'] == 2)
            {
                $all_user_info = $this->Bot_model->get_user_info(null);

                $block_count = 0;
                $count_all_user = count($all_user_info);
                $all_send_time = 0;

                for($i = 0; $i < $count_all_user; $i++)
                {
                    $this->benchmark->mark('start_send_time');

                    if($i % 15 == 0)
                    {
                        sleep(1);
                    }

                    $keyboard_message = [];
                    if($send_broadcast_message_data['keyboard'] != null)
                        $keyboard_message = $send_broadcast_message_data['keyboard'];

                    $keyboard_message = array_filter($keyboard_message);
                    $keyb= $this->telegram->buildInlineKeyBoard($keyboard_message);
                    $content = ['chat_id'=>$all_user_info[$i]['user_id'],'from_chat_id'=>$send_broadcast_message_data['from_chat_id'],'message_id'=>$send_broadcast_message_data['message_id'], 'reply_markup'=>$keyb];
                    $send_message_result = $this->telegram->copyMessage($content);

                    if(!$send_message_result['ok'] && $send_message_result['error_code'] == 429)
                        sleep($send_message_result['parameters']['retry_after']);
                    else if(!$send_message_result['ok'])
                        $block_count++;


                    $this->benchmark->mark('end_send_time');

                    $all_send_time += $this->benchmark->elapsed_time('start_send_time', 'end_send_time');
                }

                //set is complete for send broadcast message and set false option value
                $json_data ['data']=
                    [
                        'type'=>0,
                        'block_count'    => $block_count,
                        'count_all_user' => $count_all_user,
                        'is_start'       => true,
                        'is_complete'    => true,
                        'all_send_time'=>$all_send_time,
                        'average_send_time'=>($all_send_time/$count_all_user)
                    ];
                $option_data['option_value'] = 0;
                $option_data['data'] = json_encode($json_data['data']);

                $complete_send_broadcast_message_action = $this->Bot_model->set_options('send_broadcast_message',$option_data);

                if($complete_send_broadcast_message_action && $json_data['data']['is_complete'])
                {
                    $report_txt = "";
                    $report_txt .= "تعداد کل کاربران جهت ارسال : ".'`'.$json_data['data']['count_all_user'].'`'.PHP_EOL;
                    $report_txt .= "تعداد ارسالی کل : ".'`'.($count_all_user - $block_count).'`'.PHP_EOL;
                    $report_txt .= "تعداد بلاکی : ".'`'.$json_data['data']['block_count'].'`'.PHP_EOL;
                    $report_txt .= "زمان کلی ارسال : ".'`'.$all_send_time.'`'.PHP_EOL;
                    $report_txt .= "میانگین زمان ارسال : ".'`'.($all_send_time/$count_all_user).'`'.PHP_EOL;



                    $all_admin = $this->config->item('admins_robot');
                    foreach ($all_admin as $item)
                    {
                        $content = ['chat_id'=>$item,'text'=>$report_txt,'parse_mode'=>'markdown'];
                        $this->telegram->sendMessage($content);
                    }
                }
            }
            else if($send_broadcast_message_data['type'] == 3)
            {
                $all_user_info = $this->Bot_model->get_user_info(null);

                $block_count = 0;
                $count_all_user = count($all_user_info);
                $all_send_time = 0;

                for($i = 0; $i < $count_all_user; $i++)
                {
                    $this->benchmark->mark('start_send_time');

                    if($i % 15 == 0)
                    {
                        sleep(1);
                    }

                    $content = ['chat_id'=>$all_user_info[$i]['user_id'],'text'=>str_replace("{name}",$all_user_info[$i]['first_name'],$send_broadcast_message_data['message'])];
                    $send_message_result = $this->telegram->sendMessage($content);

                    if(!$send_message_result['ok'] && $send_message_result['error_code'] == 429)
                        sleep($send_message_result['parameters']['retry_after']);
                    else if(!$send_message_result['ok'])
                        $block_count++;


                    $this->benchmark->mark('end_send_time');

                    $all_send_time += $this->benchmark->elapsed_time('start_send_time', 'end_send_time');
                }
                //set is complete for send broadcast message and set false option value

                $json_data ['data']=
                    [
                        'type'           => 0,
                        'message'        => null,
                        'block_count'    => $block_count,
                        'count_all_user' => $count_all_user,
                        'is_start'       => true,
                        'is_complete'    => true,
                        'last_message'   => $send_broadcast_message_data['message']
                    ];
                $option_data['option_value'] = 0;
                $option_data['data'] = json_encode($json_data['data']);
                $complete_send_broadcast_message_action = $this->Bot_model->set_options('send_broadcast_message',$option_data);
                if($complete_send_broadcast_message_action && $json_data['data']['is_complete'])
                {
                    $report_txt = "";
                    $report_txt .= "تعداد کل کاربران جهت ارسال : ".'`'.$json_data['data']['count_all_user'].'`'.PHP_EOL;
                    $report_txt .= "تعداد ارسالی کل : ".'`'.($count_all_user - $block_count).'`'.PHP_EOL;
                    $report_txt .= "تعداد بلاکی : ".'`'.$json_data['data']['block_count'].'`'.PHP_EOL;
                    $report_txt .= "زمان کلی ارسال : ".'`'.$all_send_time.'`'.PHP_EOL;
                    $report_txt .= "میانگین زمان ارسال : ".'`'.($all_send_time/$count_all_user).'`'.PHP_EOL;

                    $all_admin = $this->config->item('admins_robot');
                    foreach ($all_admin as $item)
                    {
                        $content = ['chat_id'=>$item,'text'=>$report_txt,'parse_mode'=>'markdown'];
                        $this->telegram->sendMessage($content);
                    }
                }
            }
        }
        else
        {
            echo "dare kar mikone ya faal nist bekesh biron".PHP_EOL;
        }

    }

}