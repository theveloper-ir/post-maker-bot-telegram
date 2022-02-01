<?php

class Bot extends CI_Controller
{
    public function index()
    {
        $this->config->load('bot_config');
        $this->load->database();
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->model('Bot_model');
        $this->load->helper(['text','admin','url']);

        @$this->bot_id = $this->config->item('bot_id');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();
        @$update_type = $this->telegram->getUpdateType();

        //no controll for forward and lock channel message
        if($chat_id == $this->config->item('forward_channel')[0] || !is_null(channel_lock_finder($chat_id,$this->config->item('channel_lock_robot'))))
            die();


        //check user exists in database
        @$user_info = $this->Bot_model->get_user_info($user_id);
        if (!isset($user_info[0]))
        {
            $user_data = [
                'user_id' => $data['message']['from']['id'],
                'first_name' => $data['message']['from']['first_name'],
                'last_name' => isset($data['message']['from']['last_name']) ? $data['message']['from']['last_name'] : null,
                'username' => $data['message']['from']['username'],
                'is_bot' => $data['message']['from']['is_bot'],
                'language_code' => $data['message']['from']['language_code'],
                'location_in_bot' => "10"
            ];
            $this->Bot_model->save_new_user_record($user_data);
        }

        if(in_array($update_type,$this->config->item('available_message_type')))
        {

            switch($update_type)
            {
                case "message":
                    $this->message_type();
                break;

                case "photo":
                    if(is_admin($user_id))
                        $this->photo_type();
                    else
                        $this->unknown_command_message();
                break;

                case "video":
                    if(is_admin($user_id))
                        $this->video_type();
                    else
                        $this->unknown_command_message();
                break;

                case "callback_query":
                    $this->callback_query_type();
                break;

                case "animation":
                    if(is_admin($user_id))
                        $this->animation_type();
                    else
                        $this->unknown_command_message();
                break;

                case "document":
                    if(is_admin($user_id))
                        $this->document_type();
                    else
                        $this->unknown_command_message();
                break;

                case "audio":
                    if(is_admin($user_id))
                        $this->audio_type();
                    else
                        $this->unknown_command_message();
                    break;

                case "voice":
                    if(is_admin($user_id))
                        $this->voice_type();
                    else
                        $this->unknown_command_message();
                    break;

                case "video_note":
                    if(is_admin($user_id))
                        $this->video_note_type();
                    else
                        $this->unknown_command_message();
                    break;
            }
        }
        else
            $this->unknown_command_message();
    }

    public function callback_query_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();
        @$callback_id   = $this->telegram->Callback_ID();
        @$callback_data = explode("_",$this->telegram->Callback_Data());

        $callback_data_cmd   = $callback_data[0];
        $callback_data_value = $callback_data[1];

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($callback_data_cmd)
        {
            case "wasDo":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'اعمال شده !'];
                    $this->telegram->answerCallbackQuery($content);
                }
                break;

            case "backToMainMenu":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'بازگشت به منو اصلی'];
                    $this->telegram->answerCallbackQuery($content);

                    $option =     [
                        [
                            $this->telegram->buildInlineKeyBoardButton('ارسال پیام سراسری 🔔🧾', $url = '', $callback_data = 'sendBroadCastMessage'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('آمار ربات 📊', $url = '', $callback_data = 'getRobotStatics'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('افزودن پست جدید 📝➕', $url = '', $callback_data = 'addNewPost'),
                        ],

                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف کلیه پیام های کانال ❌🔔📨', $url = '', $callback_data = 'confirmAction_A001'),
                        ],
                    ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup'=>$keyb,'reply_to_message_id'=>$this->telegram->MessageID(), 'text'=>$this->config->item('admin_keyboard_message')];
                    $this->telegram->editMessageText($content);
                }
                break;

            case "getRobotStatics":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'دریافت آمار کلی ربات'];
                    $this->telegram->answerCallbackQuery($content);

                    $option = $this->telegram->InlineKeyboard();

                    $keyb = $this->telegram->buildInlineKeyBoard($option);

                    $statics_data = $this->Bot_model->get_statics();

                    $reply_message_text = "";
                    $reply_message_text .= "آمار ربات ".PHP_EOL;
                    $reply_message_text .= "```";
                    $reply_message_text .= "تعداد کاربران : ".$statics_data['user_count'].PHP_EOL;
                    $reply_message_text .= "تعداد پست ها :  ".$statics_data['post_count'].PHP_EOL;
                    $reply_message_text .= "تعداد دانلودها :  ".$statics_data['download_count'].PHP_EOL;
                    $reply_message_text .= "```";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
                break;

            case "botSetting":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'تنظیمات ربات'];
                    $this->telegram->answerCallbackQuery($content);


                    if($this->config->item('remove_tag_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_link_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_username_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                            ];


                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
                break;

            case "tagRemoveOptionActive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'فعال کردن آپشن حذف تگ'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = true;
                    $this->Bot_model->set_options('remove_tag_from_text',$json_data);

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                        ];

                    if($this->config->item('remove_link_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_username_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
            break;

            case "tagRemoveOptionDeactive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'غیرفعال کردن آپشن حذف تگ'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = false;
                    $this->Bot_model->set_options('remove_tag_from_text',$json_data);

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                        ];

                    if($this->config->item('remove_link_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_username_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
            break;

            case "linkRemoveOptionActive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'فعال کردن آپشن حذف لینک'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = true;
                    $this->Bot_model->set_options('remove_link_from_text',$json_data);

                    if($this->config->item('remove_tag_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                        ];

                    if($this->config->item('remove_username_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
            break;

            case "linkRemoveOptionDeactive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'غیرفعال کردن آپشن حذف لینک'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = false;
                    $this->Bot_model->set_options('remove_link_from_text',$json_data);

                    if($this->config->item('remove_tag_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                        ];

                    if($this->config->item('remove_username_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                            ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
            break;

            case "usernameRemoveOptionActive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'فعال کردن آپشن حذف یوزرنیم'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = true;
                    $this->Bot_model->set_options('remove_username_from_text',$json_data);

                    if($this->config->item('remove_tag_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_link_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                            ];


                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ✅', $url = '', $callback_data = 'usernameRemoveOptionDeactive'),
                        ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
                break;

            case "usernameRemoveOptionDeactive":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'غیرفعال کردن آپشن حذف یوزرنیم'];
                    $this->telegram->answerCallbackQuery($content);
                    $json_data['data'] = false;
                    $this->Bot_model->set_options('remove_username_from_text',$json_data);


                    if($this->config->item('remove_tag_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ✅', $url = '', $callback_data = 'tagRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف تگ ❌', $url = '', $callback_data = 'tagRemoveOptionActive'),
                            ];

                    if($this->config->item('remove_link_state'))
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ✅', $url = '', $callback_data = 'linkRemoveOptionDeactive'),
                            ];
                    else
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف لینک ❌', $url = '', $callback_data = 'linkRemoveOptionActive'),
                            ];


                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف یوزرنیم ❌', $url = '', $callback_data = 'usernameRemoveOptionActive'),
                        ];

                    $option [] =
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text = "";
                    $reply_message_text .= "فعال : ✅ / غیر فعال : ❌";
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= "### با کلیک روی هر آیتم میتوانید وضعیت آنرا تغییر دهید ###".PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                }
                break;

            case "sendBroadCastMessage":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'تنظیم ارسال پیام سراسری'];
                    $this->telegram->answerCallbackQuery($content);

                    $option []=
                        [
                            $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                        ];
                    $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                    $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);
                    if(!is_null($send_broadcast_option_data['message']) || !empty($send_broadcast_option_data['message']))
                    {
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('ریست تنظیمات ارسال پیام سراسری 🔧❌', $url = '', $callback_data = 'cancelSendBroadCast'),
                            ];
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ];
                    }
                    else
                    {
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ];
                    }

                    $keyb = $this->telegram->buildInlineKeyBoard($option);

                    $reply_message_text = "";
                    $reply_message_text .= $this->config->item('send_board_cast_message_text').PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                    $this->Bot_model->change_user_location_in_bot($chat_id, "30");

                    $json_data['data'] =json_encode(
                        [
                            'type'=>0,
                            'message' => null,
                            'block_count'    => 0,
                            'count_all_user' => 0,
                            'is_start'       => false,
                            'is_complete'    => false,
                            'last_message'   => $send_broadcast_option_data['last_message']
                        ]);
                    $this->Bot_model->set_options('send_broadcast_message',$json_data);
                }
                break;

            case "startSendBroadCast":
                $user_info = $this->Bot_model->get_user_info($user_id)[0];
                if (is_admin($user_id))
                {
                    $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                    $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);
                    $reply_message_text = "";

                    if($send_broadcast_option_data['type']==0 || $user_info['location_in_bot']<30 || $user_info['location_in_bot']>40)
                    {
                        $content = ['callback_query_id' => $callback_id, 'text' => 'پیامی جهت ارسال سراسری تنظیم نشده است یا در حالت ارسال قرار ندارید!'];
                        $this->telegram->answerCallbackQuery($content);

                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                                ],
                            ];

                        $reply_message_text .= '`پیامی جهت ارسال سراسری تنظیم نشده است لطفا مجدد تلاش کنید`'.PHP_EOL;
                        $reply_message_text .= $this->config->item('send_board_cast_message_text').PHP_EOL;
                        $this->Bot_model->change_user_location_in_bot($chat_id, "30");

                    }
                    else
                    {
                        $content = ['callback_query_id' => $callback_id, 'text' => 'شروع عملیات ارسال پیام سراسری'];
                        $this->telegram->answerCallbackQuery($content);

                        $option =     [
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ],
                        ];
                        $reply_message_text .= "`ربات در حالت ارسال پیام سراسری قرار گرفت بزودی نتیجه برای شما ارسال خواهد شد`".PHP_EOL;
                    }


                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $keyb = $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$reply_message_text, 'parse_mode'=>'markdown', 'reply_markup' => $keyb];
                    $this->telegram->editMessageText($content);


                    $json_data['option_value'] = 1;
                    $this->Bot_model->set_options('send_broadcast_message',$json_data);
                }
                break;

            case "cancelSendBroadCast":
                if (is_admin($user_id))
                {
                    $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                    $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);

                    $json_data ['data']=
                        [
                            'type'           => 0,
                            'block_count'    => $send_broadcast_option_data['block_count'],
                            'count_all_user' => $send_broadcast_option_data['count_all_user'],
                            'is_start'       => $send_broadcast_option_data['is_start'],
                            'is_complete'    => $send_broadcast_option_data['is_complete'],

                        ];
                    $option_data['option_value'] = 0;
                    $option_data['data'] = json_encode($json_data['data']);
                    $this->Bot_model->set_options('send_broadcast_message',$option_data);

                    $content = ['callback_query_id' => $callback_id, 'text' => 'ریست تنظیمات ارسال پیام سراسری'];
                    $this->telegram->answerCallbackQuery($content);

                    $option []=
                        [
                            $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                        ];
                    $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                    $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);
                    if(!is_null($send_broadcast_option_data['message']) || !empty($send_broadcast_option_data['message']))
                    {
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('ریست تنظیمات ارسال پیام سراسری 🔧❌', $url = '', $callback_data = 'cancelSendBroadCast'),
                            ];
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ];
                    }
                    else
                    {
                        $option [] =
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ];
                    }

                    $keyb = $this->telegram->buildInlineKeyBoard($option);

                    $reply_message_text = "";
                    $reply_message_text .= $this->config->item('send_board_cast_message_text').PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                    $this->Bot_model->change_user_location_in_bot($chat_id, "30");

                }
                break;

            case "sendBroadCastWithSenderName":
                $content = ['callback_query_id' => $callback_id, 'text' => 'ارسال پیام سراسری بهمراه نام ارسال کننده'];
                $this->telegram->answerCallbackQuery($content);

                $json_data['data'] =json_encode(
                    [
                        'type' => '1',//forward message with sender name
                        'message_id' => $callback_data_value,
                        'from_chat_id' => $chat_id,
                        'block_count'    => 0,
                        'count_all_user' => 0,
                        'is_start'       => false,
                        'is_complete'    => false,

                    ]);
                $this->Bot_model->set_options('send_broadcast_message',$json_data);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('ریست تنظیمات ارسال پیام سراسری 🔧❌', $url = '', $callback_data = 'cancelSendBroadCast'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ],
                    ];
                $keyb = $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb];
                $this->telegram->editMessageReplyMarkup($content);

                $this->Bot_model->change_user_location_in_bot($chat_id, "31");

                break;

            case "sendBroadCastWithOutSenderName":
                $content = ['callback_query_id' => $callback_id, 'text' => 'ارسال پیام سراسری بدون نام ارسال کننده'];
                $this->telegram->answerCallbackQuery($content);


                $content = ['chat_id' => $chat_id,'from_chat_id'=>$chat_id, 'message_id' => $callback_data_value];
                $res = $this->telegram->copyMessage($content);


                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('ریست تنظیمات ارسال پیام سراسری 🔧❌', $url = '', $callback_data = 'cancelSendBroadCast'),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                        ],
                    ];
                $keyb = $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id' => $chat_id, 'text' => $this->config->item('add_link_guide_message'), 'reply_to_message_id' => $res['result']['message_id'], 'reply_markup'=>$keyb, 'parse_mode'=>'markdown'];
                $this->telegram->sendMessage($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$callback_data_value];
                $this->telegram->deleteMessage($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                $json_data['data'] =json_encode(
                    [
                        'type' => '2',//forward message withOut sender name
                        'message_id' => $res['result']['message_id'],
                        'from_chat_id' => $chat_id,
                        'keyboard'=>null,
                        'block_count'    => 0,
                        'count_all_user' => 0,
                        'is_start'       => false,
                        'is_complete'    => false,

                    ]);
                $this->Bot_model->set_options('send_broadcast_message',$json_data);


                $this->Bot_model->change_user_location_in_bot($chat_id, "32");
                break;

            case "addNewPost":
                if (is_admin($user_id))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'افزودن پست جدید'];
                    $this->telegram->answerCallbackQuery($content);

                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ],
                        ];

                    $keyb = $this->telegram->buildInlineKeyBoard($option);


                    $reply_message_text  = "";
                    $reply_message_text .= $this->config->item('recipe_for_add_new_post').PHP_EOL;
                    $reply_message_text .= PHP_EOL.PHP_EOL;
                    $reply_message_text .= $this->config->item('admin_keyboard_message');

                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb, 'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                    $this->telegram->editMessageText($content);
                    $this->Bot_model->change_user_location_in_bot($chat_id, "50");
                }
                break;

            case "sendPostToChannel":

                if (is_admin($user_id))
                {
                    $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                    $post_cover_info = json_decode($post_info['post_cover_info'], true);
                    $post_info['caption'] .= PHP_EOL . PHP_EOL . $this->config->item('forward_channel')[1];

                    if(is_null($post_info['post_cover_info']))
                    {
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id'=>$this->config->item('forward_channel')[0],'text'=>$post_info['caption'],'reply_markup'=>$keyb, 'disable_web_page_preview'=>true];
                        $res = $this->telegram->sendMessage($content);

                    }
                    else
                    {
                        switch ($post_cover_info['post_type_id'])
                        {
                            case "1":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'video'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendVideo($content);

                                break;

                            case "2":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'photo'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendPhoto($content);

                                break;

                            case "3":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'animation'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendAnimation($content);

                                break;

                            case "4":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'title'=>$post_cover_info['caption'], 'audio'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendAudio($content);

                                break;

                            case "5":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'document'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendDocument($content);

                                break;

                            case "6":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'voice'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendVoice($content);

                                break;

                            case "7":

                                $option =
                                    [
                                        [
                                            $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                        ],
                                    ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                                $content = ['chat_id'=>$this->config->item('forward_channel')[0],'caption'=>$post_info['caption'], 'video_note'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                                $res = $this->telegram->sendVideoNote($content);

                                break;
                        }
                    }
                    if($res['ok'])
                    {
                        $content = ['callback_query_id' => $callback_id, 'text' => 'ارسال پست به کانال'];
                        $this->telegram->answerCallbackQuery($content);
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست از کانال ❌📢', $url = '', $callback_data = 'removePostFromChannel_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('پست به کانال ارسال شد ✅📤', $url = '', $callback_data = 'wasDo'),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);
                        $post_info_update = [
                            'message_id_in_channel'=>$res['result']['message_id']
                        ];

                        $this->Bot_model->update_post_info($post_info_update,['code'=>$post_info['code']]);
                    }
                    else
                    {
                        $content = ['callback_query_id' => $callback_id, 'text' => 'ارسال به کانال با مشکل مواجه شده است 🚫'];
                        $this->telegram->answerCallbackQuery($content);
                    }
                }

                break;

            case "nextStepForPost":

                $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                $post_step = intval($post_info['complete_step_post_id'])+1;

                switch($post_step)
                {
                    case "2":

                        $content = ['callback_query_id' => $callback_id, 'text' => 'لطفا کپشن مورد نظر خود را طبق الگو برای این پست ارسال کنید'];
                        $this->telegram->answerCallbackQuery($content);
                        $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_caption_guide_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);

                    break;

                    case "3":

                        $content = ['callback_query_id' => $callback_id, 'text' => 'لطفا پوستر مورد نظر خود را برای این پست ارسال کنید'];
                        $this->telegram->answerCallbackQuery($content);
                        $this->Bot_model->change_user_location_in_bot($chat_id, "82");

                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('مرحله قبلی ⬅️', $url = '', $callback_data = 'previousStepForPost_'.$post_info['code']),
                                    $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);

                    break;

                    case "4":
                        if(is_null($post_info['caption']) && is_null($post_info['post_cover_info']))
                        {
                            $content = ['callback_query_id' => $callback_id, 'text' => 'برای ارسال پست به کانال حتما باید کاور یا عنوان(کپشن) قرار دهید 🚫'];
                            $this->telegram->answerCallbackQuery($content);

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('مرحله قبلی ⬅️', $url = '', $callback_data = 'previousStepForPost_'.$post_info['code']),
                                        $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                    ],
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                            $this->telegram->editMessageText($content);
                            $post_step = 3;
                        }
                       else
                       {
                           $content = ['callback_query_id' => $callback_id, 'text' => 'مرحله پایانی پست ایجاد شده'];
                           $this->telegram->answerCallbackQuery($content);
                           $option =
                               [
                                   [
                                       $this->telegram->buildInlineKeyBoardButton('ارسال پست به کانال 📤', $url = '', $callback_data = 'sendPostToChannel_'.$post_info['code']),
                                   ],
                                   [
                                       $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$post_info['code']),
                                   ],
                                   [
                                       $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                   ],
                               ];
                           $keyb= $this->telegram->buildInlineKeyBoard($option);
                           $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                           $this->telegram->editMessageText($content);
                       }

                    break;

                }

                $post_info_update = [
                    'complete_step_post_id'=>$post_step
                ];

                $this->Bot_model->update_post_info($post_info_update,['code'=>$post_info['code']]);

                break;

            case "previousStepForPost":

                $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                $post_step = intval($post_info['complete_step_post_id'])-1;

                switch($post_step)
                {
                    case "1":

                        $post_step = 2;

                    break;

                    case "2":

                        $content = ['callback_query_id' => $callback_id, 'text' => 'لطفا کپشن مورد نظر خود را طبق الگو برای این پست ارسال کنید'];
                        $this->telegram->answerCallbackQuery($content);
                        $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>'عنوان پست : '.$post_info['caption'].PHP_EOL.PHP_EOL.$this->config->item('add_caption_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);
                    break;

                    case "3":

                        $content = ['callback_query_id' => $callback_id, 'text' => 'لطفا پوستر مورد نظر خود را برای این پست ارسال کنید'];
                        $this->telegram->answerCallbackQuery($content);
                        $this->Bot_model->change_user_location_in_bot($chat_id, "82");

                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('مرحله قبلی ⬅️', $url = '', $callback_data = 'previousStepForPost_'.$post_info['code']),
                                    $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);

                    break;

                    case "4":

                        $content = ['callback_query_id' => $callback_id, 'text' => 'مرحله پایانی پست ایجاد شده'];
                        $this->telegram->answerCallbackQuery($content);
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('ارسال پست به کانال 📤', $url = '', $callback_data = 'sendPostToChannel_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'parse_mode'=>'markdown', 'reply_markup'=>$keyb];
                        $this->telegram->editMessageText($content);

                        break;
                }

                $post_info_update = [
                    'complete_step_post_id'=>$post_step
                ];

                $this->Bot_model->update_post_info($post_info_update,['code'=>$post_info['code']]);


                break;

            case "deletePost":

               $post_info_with_code = $this->Bot_model->get_post_with_code($callback_data_value)[0];

               if(!isset($post_info_with_code['id']))
               {
                   $content = ['callback_query_id' => $callback_id, 'text' => 'پست پیدا نشد !'];
                   $this->telegram->answerCallbackQuery($content);
                   die();
               }

               $content = ['callback_query_id' => $callback_id, 'text' => 'حذف پست مورد نظر'];
               $this->telegram->answerCallbackQuery($content);
               if(!is_null($post_info_with_code['message_id_in_channel']))
               {
                   $content = ['chat_id' => $this->config->item('forward_channel')[0], 'message_id' => $post_info_with_code['message_id_in_channel']];
                   $removeFromChannel = $this->telegram->deleteMessage($content);
               }

                $db_res = $this->Bot_model->delete_post(['id'=>$post_info_with_code['id']]);
                $this->Bot_model->change_user_location_in_bot($chat_id, "20");

                if($db_res)
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'پست با موفقیت از بانک اطلاعاتی حذف گردید'];
                    $this->telegram->answerCallbackQuery($content);

                    $message_ids_in_bot = json_decode($post_info_with_code['message_id_in_bot'],true );

                    foreach ($message_ids_in_bot as $item) {
                        $content = ['chat_id'=>$chat_id, 'message_id'=>$item];
                        $this->telegram->deleteMessage($content);
                    }
                    $this->Bot_model->change_user_location_in_bot($chat_id, "50");
                }

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "removePostFromChannel":
                if(is_admin($user_id))
                {

                    $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                    $post_info_update = [
                        'message_id_in_channel'=>null
                    ];

                    $set_null_for_channel_message_id =  $this->Bot_model->update_post_info($post_info_update,['code'=>$post_info['code']]);
                    if ($set_null_for_channel_message_id) {
                        $content = ['chat_id' => $this->config->item('forward_channel')[0], 'message_id' => $post_info['message_id_in_channel']];
                        $removeFromChannel = $this->telegram->deleteMessage($content);
                    }

                    $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];

                    if ($removeFromChannel['ok'])
                    {
                        if (!$post_info['message_id_in_channel'])
                        {
                            $content = ['callback_query_id' => $callback_id, 'text' => 'پست از کانال حذف شد !'];
                            $this->telegram->answerCallbackQuery($content);
                        }
                        $option = [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال به کانال 📢', $url = '', $callback_data = 'sendPostToChannel_' . $post_info['code']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('پست از کانال حذف شد 🚫', $url = '', $callback_data = 'wasDo'),
                            ],
                        ];

                    }
                    else if(!$removeFromChannel['ok'] && is_null($post_info['message_id_in_channel']))
                    {
                        $content = ['callback_query_id' => $callback_id, 'text' => 'پست قبلا از کانال حذف گردیده است!'];
                        $this->telegram->answerCallbackQuery($content);
                        $option = [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال به کانال 📢', $url = '', $callback_data = 'sendPostToChannel_' . $post_info['code']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                            ],
                        ];
                    }


                    $keyb = $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb];
                    $this->telegram->editMessageReplyMarkup($content);
                }
                break;

            case "showPostPreview":
                $content = ['callback_query_id' => $callback_id, 'text' => 'پیش نمایش پست در کانال'];
                $this->telegram->answerCallbackQuery($content);

                $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                $post_cover_info = json_decode($post_info['post_cover_info'], true);
                $post_info['caption'] .= PHP_EOL . PHP_EOL . $this->config->item('forward_channel')[1];


                if(is_null($post_info['post_cover_info']))
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                            ],
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>$post_info['caption'],'reply_markup'=>$keyb, 'disable_web_page_preview'=>true];
                    $res = $this->telegram->sendMessage($content);

                }
                else
                {
                    switch ($post_cover_info['post_type_id'])
                    {
                        case "1":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'video'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendVideo($content);

                            break;

                        case "2":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'photo'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendPhoto($content);

                            break;

                        case "3":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'animation'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendAnimation($content);

                            break;

                        case "4":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'title'=>$post_cover_info['caption'], 'audio'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendAudio($content);

                            break;

                        case "5":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'document'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendDocument($content);

                            break;

                        case "6":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'voice'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendVoice($content);

                            break;

                        case "7":

                            $option =
                                [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('دانلود ️ 📥', $url = "https://t.me/" . $this->bot_id . "?start=" . $post_info['code']),
                                    ],
                                ];
                            $keyb= $this->telegram->buildInlineKeyBoard($option);
                            $content = ['chat_id'=>$chat_id,'caption'=>$post_info['caption'], 'video_note'=>$post_cover_info['file_id'],'reply_markup'=>$keyb];
                            $res = $this->telegram->sendVideoNote($content);

                            break;
                    }

                }
                litespeed_finish_request();
                sleep(15);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
                $this->telegram->deleteMessage($content);


                break;

            case "sendVideoAfterJoin":

                $content = ['callback_query_id' => $callback_id, 'text' => 'در حال بررسی ارسال فایل به شما'];
                $this->telegram->answerCallbackQuery($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);
                if(!is_admin($user_id))
                {
                    //check user in channel lock
                    foreach ($this->config->item('channel_lock_robot') as $item)
                    {
                        $content = ['chat_id'=>$item[0],'user_id'=>$user_id];
                        $status = $this->telegram->getChatMember($content)['result']['status'];

                        //some problem with kicked status
                        if($status == "left")
                        {
                            $counter = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣'];
                            $i = 0;
                            $option = [];
                            foreach ($this->config->item('channel_lock_robot') as $item)
                            {
                                if(strpos($item[1],"https://")===0)
                                    array_push($option,[$this->telegram->buildInlineKeyBoardButton('کانال '.$counter[$i] , $url = $item[1])]);
                                else
                                    array_push($option,[$this->telegram->buildInlineKeyBoardButton('کانال '.$counter[$i] , $url = "https://t.me/".$item[1])]);
                                $i++;
                            }
                            array_push($option, [$this->telegram->buildInlineKeyBoardButton('تایید عضویت ✅ | دانلود', $url = '', $callback_data = 'sendVideoAfterJoin_'.$callback_data_value)]);


                            $keyb= $this->telegram->buildInlineKeyBoard($option);

                            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('join_warning'),'reply_markup'=>$keyb];
                            $this->telegram->sendMessage($content);

                            die();
                        }
                    }
                }

                $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];

                //check file info and if exists send to user
                if (isset($post_info['id']))
                {
                    //save download data
                    $download_data = [
                        'users_tbl_id'=>$this->Bot_model->get_user_info($user_id)[0]['id'],
                        'files_tbl_id'=>$post_info['id'],
                    ];
                    $this->Bot_model->save_new_download($download_data);

                    //send file to user
                    $post_media_info = json_decode($post_info['post_media_info'], true);
                    $post_info['caption'] .= PHP_EOL.PHP_EOL. $this->config->item('forward_channel')[1];
                    $keyb = "";
                    if(is_admin($user_id))
                    {
                        $option =     [
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف پست از کانال ❌📢', $url = '', $callback_data = 'removePostFromChannel_'.$post_info['code']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                            ],
                        ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                    }

                    switch ($post_info['post_type_id'])
                    {
                        case "1":
                            if(count($post_media_info) == 1)
                            {
                                $content = ['chat_id'=>$chat_id,'video'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                $res_smg = $this->telegram->sendVideo($content);
                            }
                            else
                            {
                                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                $res_smg = $this->telegram->sendMediaGroup($content);

                                if(is_admin($user_id))
                                {
                                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                    $this->telegram->sendMessage($content);
                                }
                            }

                            break;

                        case "2":

                            if(count($post_media_info) == 1)
                            {
                                $content = ['chat_id'=>$chat_id,'photo'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                $res_smg = $this->telegram->sendPhoto($content);
                            }
                            else
                            {
                                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                $res_smg = $this->telegram->sendMediaGroup($content);

                                if(is_admin($user_id))
                                {
                                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                    $this->telegram->sendMessage($content);
                                }
                            }

                            break;

                        case "3":

                            $content = ['chat_id'=>$chat_id, 'animation'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                            $this->telegram->sendAnimation($content);

                            break;

                        case "4":

                            if(count($post_media_info) == 1)
                            {
                                $content = ['chat_id'=>$chat_id,'audio'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                $res_smg = $this->telegram->sendAudio($content);
                            }
                            else
                            {
                                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                $res_smg = $this->telegram->sendMediaGroup($content);

                                if(is_admin($user_id))
                                {
                                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                    $this->telegram->sendMessage($content);
                                }
                            }

                        break;

                        case "5":

                            if(count($post_media_info) == 1)
                            {
                                $content = ['chat_id'=>$chat_id,'document'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                $res_smg = $this->telegram->sendDocument($content);
                            }
                            else
                            {
                                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                $res_smg = $this->telegram->sendMediaGroup($content);

                                if(is_admin($user_id))
                                {
                                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                    $this->telegram->sendMessage($content);
                                }
                            }

                        break;

                        case "6":

                            $content = ['chat_id'=>$chat_id, 'voice'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                            $this->telegram->sendVoice($content);

                        break;

                        case "7":

                            $content = ['chat_id'=>$chat_id, 'video_note'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                            $this->telegram->sendVideoNote($content);

                            break;
                    }
                }
                else
                {
                    $content = ['chat_id' => $chat_id, 'text' => $this->config->item('not_found_file'), 'reply_to_message_id' => $this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }

            break;

            case "removeAllBotMessageInChannel":

                $post_info = $this->Bot_model->get_posts_in_channel();

                if(count($post_info))
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'در حال اجرای حذف تمامی پیام های ارسال شده توسط ربات به کانال'];
                    $this->telegram->answerCallbackQuery($content);


                    foreach ($post_info as $item) {
                        $post_info = $this->Bot_model->get_post_with_code($callback_data_value)[0];
                        $post_info_update = [
                            'message_id_in_channel'=>null
                        ];

                        $set_null_for_channel_message_id =  $this->Bot_model->update_post_info($post_info_update,['code'=>$item['code']]);
                        if ($set_null_for_channel_message_id) {
                            $content = ['chat_id' => $this->config->item('forward_channel')[0], 'message_id' => $item['message_id_in_channel']];
                            $removeFromChannel = $this->telegram->deleteMessage($content);
                        }
                    }

                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('تایید دستور و حذف کلیه پیام های ارسال به کانال توسط ربات ✅', $url = '', $callback_data = 'wasDo'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ],
                        ];
                }
                else
                {
                    $content = ['callback_query_id' => $callback_id, 'text' => 'پستی جهت حذف از کانال پیدا نشد'];
                    $this->telegram->answerCallbackQuery($content);

                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('پستی جهت حذف از کانال پیدا نشد ❌', $url = '', $callback_data = 'wasDo'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ],
                        ];
                }

                $keyb= $this->telegram->buildInlineKeyBoard($option);

                $content = ['chat_id'=>$chat_id, 'message_id' => $this->telegram->MessageID(), 'text'=>$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);


                break;

            case "confirmAction":

                switch ($callback_data_value)
                {
                    case "A001":
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('تایید و حذف کلیه پیام های ارسال به کانال توسط ربات', $url = '', $callback_data = 'removeAllBotMessageInChannel'),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);

                        $reply_message_text = "";
                        $reply_message_text .= $this->config->item('admin_keyboard_message');

                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID(), 'reply_markup' => $keyb,'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                        $this->telegram->editMessageText($content);
                    break;
                }

                break;
        }
    }

    public function message_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();
        $text_exp = explode(" ",$text);

        $cmd = $text_exp[0];
        $input = $text_exp[1];

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        $cmd = strtolower($cmd);
        if(strpos($data['message']['text'],'/') == 0 && in_array($cmd,$this->config->item('available_command')))
        {
            switch ($cmd)
            {
                case "/start":
                    $this->Bot_model->change_user_location_in_bot($chat_id, "10");
                    if (isset($input))
                    {
                        #send post to user

                        if(!is_admin($user_id))
                        {
                            //check user in channel lock
                            foreach ($this->config->item('channel_lock_robot') as $item)
                            {
                                $content = ['chat_id'=>$item[0],'user_id'=>$user_id];
                                $status = $this->telegram->getChatMember($content)['result']['status'];

                                //some problem with kicked status
                                if($status == "left")
                                {
                                    $counter = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣'];
                                    $i = 0;
                                    $option = [];
                                    foreach ($this->config->item('channel_lock_robot') as $item)
                                    {
                                        if(strpos($item[1],"https://")===0)
                                            array_push($option,[$this->telegram->buildInlineKeyBoardButton('کانال '.$counter[$i] , $url = $item[1])]);
                                        else
                                            array_push($option,[$this->telegram->buildInlineKeyBoardButton('کانال '.$counter[$i] , $url = "https://t.me/".$item[1])]);
                                        $i++;
                                    }
                                    array_push($option, [$this->telegram->buildInlineKeyBoardButton('تایید عضویت ✅ | دانلود', $url = '', $callback_data = 'sendVideoAfterJoin_'.$input)]);


                                    $keyb= $this->telegram->buildInlineKeyBoard($option);

                                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('join_warning'),'reply_markup'=>$keyb];
                                    $this->telegram->sendMessage($content);

                                    die();
                                }
                            }
                        }

                        $post_info = $this->Bot_model->get_post_with_code($input)[0];

                        //check file info and if exists send to user
                        if (isset($post_info['id']))
                        {
                           //save download data

                            //send file to user
                            $post_media_info = json_decode($post_info['post_media_info'], true);
                            $post_info['caption'] .= PHP_EOL.PHP_EOL. $this->config->item('forward_channel')[1];
                            $keyb = "";
                            if(is_admin($user_id))
                            {
                                $option =     [
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('حذف پست از کانال ❌📢', $url = '', $callback_data = 'removePostFromChannel_'.$post_info['code']),
                                    ],
                                    [
                                        $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                    ],
                                ];
                                $keyb= $this->telegram->buildInlineKeyBoard($option);
                            }
                            switch ($post_info['post_type_id'])
                            {
                                case "1":

                                    if(count($post_media_info) == 1)
                                    {
                                        $content = ['chat_id'=>$chat_id,'video'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                        $res_smg = $this->telegram->sendVideo($content);
                                    }
                                    else
                                    {
                                        $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                        $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                        $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                        $res_smg = $this->telegram->sendMediaGroup($content);

                                        if(is_admin($user_id))
                                        {
                                            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                            $this->telegram->sendMessage($content);
                                        }
                                    }

                                break;

                                case "2":

                                    if(count($post_media_info) == 1)
                                    {
                                        $content = ['chat_id'=>$chat_id,'photo'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                        $res_smg = $this->telegram->sendPhoto($content);
                                    }
                                    else
                                    {
                                        $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                        $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                        $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                        $res_smg = $this->telegram->sendMediaGroup($content);

                                        if(is_admin($user_id))
                                        {
                                            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                            $this->telegram->sendMessage($content);
                                        }
                                    }
                                    break;

                                case "3":

                                    $content = ['chat_id'=>$chat_id, 'animation'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                    $this->telegram->sendAnimation($content);

                                    break;

                                case "4":

                                    if(count($post_media_info) == 1)
                                    {
                                        $content = ['chat_id'=>$chat_id,'audio'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                        $res_smg = $this->telegram->sendAudio($content);
                                    }
                                    else
                                    {
                                        $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                        $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                        $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                        $res_smg = $this->telegram->sendMediaGroup($content);

                                        if(is_admin($user_id))
                                        {
                                            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                            $this->telegram->sendMessage($content);
                                        }
                                    }
                                    break;

                                case "5":

                                    if(count($post_media_info) == 1)
                                    {
                                        $content = ['chat_id'=>$chat_id,'document'=>$post_media_info[0]['media'],'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                        $res_smg = $this->telegram->sendDocument($content);
                                    }
                                    else
                                    {
                                        $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                                        $post_media_info[count($post_media_info)-1]['caption'] = $post_info['caption'];
                                        $content = ['chat_id'=>$chat_id, 'media'=>json_encode($post_media_info)];
                                        $res_smg = $this->telegram->sendMediaGroup($content);

                                        if(is_admin($user_id))
                                        {
                                            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$res_smg['result']['message_id'],'reply_markup'=>$keyb];
                                            $this->telegram->sendMessage($content);
                                        }
                                    }

                                break;

                                case "6":

                                    $content = ['chat_id'=>$chat_id, 'voice'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                    $this->telegram->sendVoice($content);

                                break;

                                case "7":

                                    $content = ['chat_id'=>$chat_id, 'video_note'=>$post_media_info[0]['media'], 'caption'=>$post_info['caption'],'reply_markup'=>$keyb];
                                    $this->telegram->sendVideoNote($content);

                                    break;
                            }


                        }
                        else
                        {
                            $content = ['chat_id' => $chat_id, 'text' => $this->config->item('not_found_file'), 'reply_to_message_id' => $this->telegram->MessageID()];
                            $this->telegram->sendMessage($content);
                        }
                    }
                    else
                    {
                        #send welcome message
                        $content = ['chat_id'=>$chat_id,'text'=>str_replace("{channel}",$this->config->item('forward_channel')[1],$this->config->item('welcome_message')),'reply_to_message_id'=>$this->telegram->MessageID()];
                        $this->telegram->sendMessage($content);
                    }
                    $this->Bot_model->change_user_location_in_bot($chat_id, "10");
                break;

                case "/adminaction":
                    if(is_admin($user_id))
                    {
                        $option =     [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال پیام سراسری 🔔🧾', $url = '', $callback_data = 'sendBroadCastMessage'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('آمار ربات 📊', $url = '', $callback_data = 'getRobotStatics'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('افزودن پست جدید ➕📝', $url = '', $callback_data = 'addNewPost'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('حذف کلیه پیام های کانال ❌🔔📨', $url = '', $callback_data = 'confirmAction_A001'),
                            ],
                        ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$this->telegram->MessageID(),'reply_markup'=>$keyb];
                        $this->telegram->sendMessage($content);
                        $this->Bot_model->change_user_location_in_bot($chat_id, "20");
                    }
                break;

                case "/help":
                    $this->Bot_model->change_user_location_in_bot($chat_id, "11");
                    if (is_admin($user_id))
                    {
                        $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_help_message'),'reply_to_message_id'=>$this->telegram->MessageID(),'parse_mode'=>'markdown'];
                        $this->telegram->sendMessage($content);
                    }
                    else
                    {
                        $content = ['chat_id'=>$chat_id,'text'=>str_replace("{channel}",$this->config->item('forward_channel')[1],$this->config->item('user_help_message')),'reply_to_message_id'=>$this->telegram->MessageID(),'parse_mode'=>'markdown', 'disable_web_page_preview'=>true];
                        $this->telegram->sendMessage($content);
                    }
                    break;

                case "/sendbroadcastmessage":
                    if (is_admin($user_id) && $user_info['location_in_bot'] == "30")
                    {
                        $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                        $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);

                        $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('شروع ارسال پیام سراسری 🔔', $url = '', $callback_data = 'startSendBroadCast'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ریست تنظیمات ارسال پیام سراسری 🔧❌', $url = '', $callback_data = 'cancelSendBroadCast'),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ],
                        ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);

                        $reply_message_text = "";
                        $reply_message_text .= "متن پیام سراسری شما : ".PHP_EOL;
                        $reply_message_text .= "```".trim(str_replace(["/sendBroadCastMessage"],"",$text))."```".PHP_EOL;
                        $reply_message_text .= PHP_EOL.PHP_EOL;
                        $reply_message_text .= $this->config->item('admin_keyboard_message');

                        $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->ReplyToMessageID(), 'reply_markup' => $keyb,'text'=>$reply_message_text, 'parse_mode'=>'markdown'];
                        $this->telegram->editMessageText($content);

                        $json_data['data'] =json_encode(
                            [
                                'type'=>3,
                                'message' => trim(str_replace(["/sendBroadCastMessage"],"",$text)),
                                'block_count'    => 0,
                                'count_all_user' => 0,
                                'is_start'       => false,
                                'is_complete'    => false,
                                'last_message'   => $send_broadcast_option_data['last_message']
                            ]);
                        $this->Bot_model->set_options('send_broadcast_message',$json_data);

                    }
                break;
            }
        }
        else if(strpos($text,"پنل") === 0 && is_admin($user_id))
        {
            $option =     [
                [
                    $this->telegram->buildInlineKeyBoardButton('ارسال پیام سراسری 🔔🧾', $url = '', $callback_data = 'sendBroadCastMessage'),
                ],
                [
                    $this->telegram->buildInlineKeyBoardButton('آمار ربات 📊', $url = '', $callback_data = 'getRobotStatics'),
                ],
                [
                    $this->telegram->buildInlineKeyBoardButton('افزودن پست جدید ➕📝', $url = '', $callback_data = 'addNewPost'),
                ],
                [
                    $this->telegram->buildInlineKeyBoardButton('حذف کلیه پیام های کانال ❌🔔📨', $url = '', $callback_data = 'confirmAction_A001'),
                ],
            ];
            $keyb= $this->telegram->buildInlineKeyBoard($option);
            $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_keyboard_message'),'reply_to_message_id'=>$this->telegram->MessageID(),'reply_markup'=>$keyb];
            $this->telegram->sendMessage($content);
            $this->Bot_model->change_user_location_in_bot($chat_id, "20");
        }
        else if(strpos($text,"لینک") === 0)
        {
            switch ($user_info['location_in_bot'])
            {
                case "32":

                    $get_send_broadcast_message_status = $this->Bot_model->get_options('send_broadcast_message')[0];
                    $send_broadcast_option_data = json_decode($get_send_broadcast_message_status['data'],true);

                    if($send_broadcast_option_data['message_id'] == $this->telegram->ReplyToMessageID())
                    {
                        $keyboard_message = [];
                        if($send_broadcast_option_data['keyboard'] != null)
                            $keyboard_message = $send_broadcast_option_data['keyboard'];

                        $keyboard_message = array_filter($keyboard_message);


                        $link_data = explode("***",trim(str_replace_limit("لینک","",$text,1)));
                        array_push($keyboard_message,[ $this->telegram->buildInlineKeyBoardButton(trim($link_data[0]), $url = trim($link_data[1]), $callback_data = '')]);

                        $keyb= $this->telegram->buildInlineKeyBoard($keyboard_message);

                        $json_data['data'] =json_encode(
                            [
                                'type' => '2',//forward message withOut sender name
                                'message_id'   => $send_broadcast_option_data['message_id'],
                                'from_chat_id' => $send_broadcast_option_data['from_chat_id'],
                                'keyboard'=>$keyboard_message,
                                'block_count'    => 0,
                                'count_all_user' => 0,
                                'is_start'       => false,
                                'is_complete'    => false,
                            ]);

                        $this->Bot_model->change_options_value('send_broadcast_message',$json_data);

                        $content = ['chat_id'=>$chat_id, 'message_id' => $send_broadcast_option_data['message_id'], 'reply_markup'=>$keyb];
                        $this->telegram->editMessageReplyMarkup($content);
                    }
                    else
                    {
                        $content = ['chat_id'=>$chat_id,'text'=>'لطفا برای افزودن لینک به پیام مورد نظر پیام را ریپلای کنید !'];
                        $res = $this->telegram->sendMessage($content);
                        litespeed_finish_request();
                        sleep(60);
                        $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                        $this->telegram->deleteMessage($content);
                    }
                    break;

            }
        }
        else if(strpos($text,"کپشن") === 0)
        {
            $post_info = $this->Bot_model->get_last_post_create_by_user($chat_id)[0];

            switch ($user_info['location_in_bot'])
            {
                case "81":
                    if($post_info['complete_step_post_id'] < 3)
                    {
                        $caption_data = trim(str_replace_limit("کپشن","",$text,1));
                        $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                        $post_media_info = json_decode($last_post_insert_by_user['post_media_info'], true);
                        $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'], true);
                        $post_media_info[count($post_media_info)-1]['caption'] = $caption_data;
                        $post_media_info[count($post_media_info)-1]['caption'] .= PHP_EOL.PHP_EOL. $this->config->item('forward_channel')[1];

                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('مرحله قبلی ⬅️', $url = '', $callback_data = 'previousStepForPost_'.$post_info['code']),
                                    $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$post_info['code']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$post_info['code']),
                                ],
                            ];
                        $keyb= $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>'عنوان مورد نظر شما : '.$caption_data.PHP_EOL.PHP_EOL."در صورت نیاز به تغییر مجدد عنوان از دستور زیر استفاده کنید".PHP_EOL."`کپشن عنوان پست مورد نظر`".PHP_EOL.PHP_EOL.$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb, 'parse_mode'=>'markdown'];
                        $this->telegram->editMessageText($content);

                        $post_info_update = [
                            'post_media_info'=>json_encode($post_media_info),
                            'caption'=>$caption_data,
                            'complete_step_post_id'=>2
                        ];

                        litespeed_finish_request();

                        $this->Bot_model->update_post_info($post_info_update,['code'=>$post_info['code']]);
                    }

                    $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);

                    break;
            }
        }
        else if(strpos($text,"افزودن مدیر") === 0 && is_admin($user_id))
        {
            $admin_change = trim(str_replace_limit("افزودن مدیر","",$text,1));
            if(!empty($admin_change) && is_numeric($admin_change))
            {
                $admins = $this->config->item('admins_robot');
                if(array_search($admin_change,$admins) !== false)
                {
                    $content = ['chat_id'=>$chat_id,'text'=>'کاربر مورد نظر در لیست مدیران قرار دارد !'];
                    $res = $this->telegram->sendMessage($content);
                }
                else
                {
                    array_push($admins,(int)$admin_change);
                    $admins = json_encode($admins);
                    $json_data['data'] = $admins;
                    $this->Bot_model->set_options('admins_robot',$json_data);
                    $content = ['chat_id'=>$chat_id,'text'=>'کاربر مورد نظر با موفقیت در لیست مدیران افزوده شد !'];
                    $res = $this->telegram->sendMessage($content);
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "83");
                $content = ['chat_id'=>$chat_id,'text'=>'لطفا یک پیام متنی از ادمین مورد نظر جهت افزوده شدن به لیست مدیران فووراد کنید'];
                $res = $this->telegram->sendMessage($content);
            }
            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"حذف مدیر") === 0 && is_admin($user_id))
        {
            $admin_change = trim(str_replace_limit("حذف مدیر","",$text,1));
            if(!empty($admin_change) && is_numeric($admin_change))
            {
                $admins = $this->config->item('admins_robot');
                if(array_search($admin_change,$admins) === false)
                {
                    $content = ['chat_id'=>$chat_id,'text'=>'کاربر مورد نظر در لیست مدیران یافت نشد !'];
                    $res = $this->telegram->sendMessage($content);
                }
                else
                {
                    $pos = array_search($admin_change, $admins);
                    unset($admins[$pos]);
                    $admins = array_values($admins);
                    $admins = json_encode($admins);
                    $json_data['data'] = $admins;
                    $this->Bot_model->set_options('admins_robot',$json_data);
                    $content = ['chat_id'=>$chat_id,'text'=>'کاربر مورد نظر با موفقیت از لیست مدیران حذف شد !'];
                    $res = $this->telegram->sendMessage($content);
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "84");

                $content = ['chat_id'=>$chat_id,'text'=>'لطفا یک پیام متنی از ادمین مورد نظر جهت حذف شدن از لیست مدیران فووراد کنید'];
                $res = $this->telegram->sendMessage($content);
            }

            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"افزودن فوروارد") === 0 && is_admin($user_id))
        {
            $forward_changes = trim(str_replace_limit("افزودن فوروارد","",$text,1));
            if(!empty($forward_changes) && is_numeric($forward_changes))
            {
                $forward_info = $this->config->item('forward_channel');
                if(is_null($forward_info))
                {
                    $content = ['chat_id'=>$forward_changes];
                    $res = $this->telegram->createChatInviteLink($content);
                    $get_chat_data = $this->telegram->getChat($content);

                    if($res['ok'] && $get_chat_data['result']['type']="channel")
                    {
                        $admins = json_encode([(int)$forward_changes,$res['result']['invite_link']]);
                        $json_data['data'] = $admins;
                        $this->Bot_model->set_options('forward_channel',$json_data);
                        $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_seted_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }
                    else
                    {
                        $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_admin_required_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }
                }
                else
                {
                    $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_is_set_warning')];
                    $res = $this->telegram->sendMessage($content);
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "85");

                $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_send_forward_from_channel')];
                $res = $this->telegram->sendMessage($content);
            }


            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"تغییر فوروارد") === 0 && is_admin($user_id))
        {
            $forward_changes = trim(str_replace_limit("تغییر فوروارد","",$text,1));
            if(!empty($forward_changes) && is_numeric($forward_changes))
            {
                $content = ['chat_id'=>$forward_changes];
                $res = $this->telegram->createChatInviteLink($content);
                if($res['ok'])//is admin
                {
                    $admins = json_encode([(int)$forward_changes,$res['result']['invite_link']]);
                    $json_data['data'] = $admins;
                    $this->Bot_model->set_options('forward_channel',$json_data);
                    $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_changed_warning')];
                    $res = $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_admin_required_warning')];
                    $res = $this->telegram->sendMessage($content);
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "86");

                $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('forward_channel_change_send_forward_from_channel')];
                $res = $this->telegram->sendMessage($content);
            }


            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"افزودن قفل") === 0 && is_admin($user_id))
        {
            $lock_changes = trim(str_replace_limit("افزودن قفل","",$text,1));
            $channel_lock_robot = $this->config->item('channel_lock_robot')==null?[]:$this->config->item('channel_lock_robot');

            if(count($channel_lock_robot) == 5)
            {
                $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('locked_channel_count_warning')];
                $res = $this->telegram->sendMessage($content);
            }
            else if(!empty($lock_changes) && is_numeric($lock_changes))
            {
                if (count($channel_lock_robot) == 5)
                {
                    $content = ['chat_id' => $chat_id, 'text' => $this->config->item('locked_channel_count_warning')];
                    $res = $this->telegram->sendMessage($content);
                }
                else
                {
                    if(is_null(channel_lock_finder($lock_changes,$this->config->item('channel_lock_robot'))))
                    {
                        $content = ['chat_id' => $lock_changes];
                        $res = $this->telegram->createChatInviteLink($content);
                        $get_chat_data = $this->telegram->getChat($content);

                        if ($res['ok'] && $get_chat_data['result']['type'] = "channel")
                        {
                            array_push($channel_lock_robot, [(int)$lock_changes, $res['result']['invite_link']]);
                            $json_data['data'] = json_encode($channel_lock_robot);
                            $this->Bot_model->set_options('channel_lock_robot', $json_data);
                            $content = ['chat_id' => $chat_id, 'text' => $this->config->item('locked_channel_added_warning')];
                            $res = $this->telegram->sendMessage($content);
                        }
                        else
                        {
                            $content = ['chat_id' => $chat_id, 'text' => $this->config->item('bot_channel_access_permission')];
                            $res = $this->telegram->sendMessage($content);
                        }
                    }
                    else
                    {
                        $content = ['chat_id' => $chat_id, 'text' => 'کانال قفل با از قفل بوده !'];
                        $res = $this->telegram->sendMessage($content);
                    }
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "87");

                $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('locked_channel_add_send_forward_from_channel')];
                $res = $this->telegram->sendMessage($content);
            }

            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"حذف قفل") === 0 && is_admin($user_id))
        {
            $lock_changes = trim(str_replace_limit("حذف قفل","",$text,1));
            $channel_lock_robot = $this->config->item('channel_lock_robot')==null?[]:$this->config->item('channel_lock_robot');

            if(!empty($lock_changes) && is_numeric($lock_changes))
            {
                $channel_location_in_arr  = channel_lock_finder($lock_changes,$this->config->item('channel_lock_robot'));
                if(!is_null($channel_location_in_arr))
                {
                    unset($channel_lock_robot[$channel_location_in_arr]);
                    $channel_lock_robot = array_values($channel_lock_robot);
                    if(count($channel_lock_robot))
                        $json_data['data'] = json_encode($channel_lock_robot);
                    else
                        $json_data['data'] = null;

                    $this->Bot_model->set_options('channel_lock_robot',$json_data);
                    $content = ['chat_id'=>$chat_id,'text'=>'کانال قفل با موفقیت حذف شد !'];
                    $res = $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>'کانال قفل جهت حذف پیدا نشد !'];
                    $res = $this->telegram->sendMessage($content);
                }
            }
            else
            {
                $this->Bot_model->change_user_location_in_bot($chat_id, "88");

                $content = ['chat_id'=>"$chat_id",'text'=>$this->config->item('locked_channel_change_send_forward_from_channel')];
                $res = $this->telegram->sendMessage($content);
            }

            litespeed_finish_request();
            sleep(60);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
            $this->telegram->deleteMessage($content);
            $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
            $this->telegram->deleteMessage($content);

        }
        else if(strpos($text,"راهنما") === 0)
        {
            $this->Bot_model->change_user_location_in_bot($chat_id, "11");
            if (is_admin($user_id))
            {
                $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('admin_help_message'),'reply_to_message_id'=>$this->telegram->MessageID(),'parse_mode'=>'markdown'];
                $this->telegram->sendMessage($content);
            }
            else
            {
                $content = ['chat_id'=>$chat_id,'text'=>str_replace("{channel}",$this->config->item('forward_channel')[1],$this->config->item('user_help_message')),'reply_to_message_id'=>$this->telegram->MessageID(),'parse_mode'=>'markdown', 'disable_web_page_preview'=>true];
                $this->telegram->sendMessage($content);
            }
        }
        else if (isset($forwardFromData) || isset($forwardFromChatData))
        {
            switch ($user_info['location_in_bot']) {
                case "30":
                    $content = ['chat_id' => $chat_id, 'from_chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                    $res = $this->telegram->forwardMessage($content);
                    if ($res['ok']) {
                        $option =
                            [
                                [
                                    $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_' . $res['result']['message_id']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_' . $res['result']['message_id']),
                                ],
                                [
                                    $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                                ]
                            ];
                        $keyb = $this->telegram->buildInlineKeyBoard($option);
                        $content = ['chat_id' => $chat_id, 'text' => 'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم', 'reply_to_message_id' => $res['result']['message_id'], 'reply_markup' => $keyb];
                        $this->telegram->sendMessage($content);
                    } else {
                        $content = ['chat_id' => $chat_id, 'text' => $this->config->item('robot_cannot_send_message'), 'reply_to_message_id' => $this->telegram->MessageID()];
                        $this->telegram->sendMessage($content);
                    }
                    break;

                case "83":
                    $admin_change = $this->telegram->FromID();
                    $admins = $this->config->item('admins_robot');

                    if (array_search($admin_change, $admins) !== false)
                    {
                        $content = ['chat_id' => $chat_id, 'text' => 'کاربر مورد نظر در لیست قرار دارد !'];
                        $res = $this->telegram->sendMessage($content);
                    }
                    else
                    {

                        array_push($admins, $admin_change);
                        $admins = json_encode($admins);
                        $json_data['data'] = $admins;
                        $this->Bot_model->set_options('admins_robot', $json_data);
                        $content = ['chat_id' => $chat_id, 'text' => 'کاربر مورد نظر با موفقیت به لیست مدیران افزوده شد !'];
                        $res = $this->telegram->sendMessage($content);
                    }

                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id' => $chat_id, 'message_id' => $res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                    break;

                case "84":

                    $admin_change = $this->telegram->FromID();
                    $admins = $this->config->item('admins_robot');

                    if (array_search($admin_change, $admins) === false) {
                        $content = ['chat_id' => $chat_id, 'text' => 'کاربر مورد نظر در لیست مدیران یافت نشد !'];
                        $res = $this->telegram->sendMessage($content);
                    } else {
                        $pos = array_search($admin_change, $admins);
                        unset($admins[$pos]);
                        $admins = array_values($admins);
                        $admins = json_encode($admins);
                        $json_data['data'] = $admins;
                        $this->Bot_model->set_options('admins_robot', $json_data);
                        $content = ['chat_id' => $chat_id, 'text' => 'کاربر مورد نظر با موفقیت از لیست مدیران حذف شد !'];
                        $res = $this->telegram->sendMessage($content);
                    }
                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id' => $chat_id, 'message_id' => $res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                    break;

                case "85":

                    $forward_changes = $this->telegram->FromChatID();
                    $forward_info = $this->config->item('forward_channel');
                    if (is_null($forward_info)) {
                        $content = ['chat_id' => $forward_changes];
                        $res = $this->telegram->createChatInviteLink($content);
                        if ($res['ok'])//is admin
                        {
                            $admins = json_encode([$forward_changes, $res['result']['invite_link']]);
                            $json_data['data'] = $admins;
                            $this->Bot_model->set_options('forward_channel', $json_data);
                            $content = ['chat_id' => "$chat_id", 'text' => $this->config->item('forward_channel_seted_warning')];
                            $res = $this->telegram->sendMessage($content);
                        } else {
                            $content = ['chat_id' => "$chat_id", 'text' => $this->config->item('forward_channel_admin_required_warning')];
                            $res = $this->telegram->sendMessage($content);
                        }
                    } else {
                        $content = ['chat_id' => "$chat_id", 'text' => $this->config->item('forward_channel_is_set_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }

                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id' => $chat_id, 'message_id' => $res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                    break;

                case "86":

                    $forward_changes = $this->telegram->FromChatID();
                    $content = ['chat_id' => $forward_changes];
                    $res = $this->telegram->createChatInviteLink($content);
                    if ($res['ok'])//is admin
                    {
                        $admins = json_encode([$forward_changes, $res['result']['invite_link']]);
                        $json_data['data'] = $admins;
                        $this->Bot_model->set_options('forward_channel', $json_data);
                        $content = ['chat_id' => "$chat_id", 'text' => $this->config->item('forward_channel_changed_warning')];
                        $res = $this->telegram->sendMessage($content);
                    } else {
                        $content = ['chat_id' => "$chat_id", 'text' => $this->config->item('forward_channel_admin_required_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }

                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id' => $chat_id, 'message_id' => $res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                    break;

                case "87":
                    $lock_changes = $this->telegram->FromChatID();
                    $channel_lock_robot = $this->config->item('channel_lock_robot') == null ? [] : $this->config->item('channel_lock_robot');

                    if (count($channel_lock_robot) == 5)
                    {
                        $content = ['chat_id' => $chat_id, 'text' => $this->config->item('locked_channel_count_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }
                    else
                    {
                        if(is_null(channel_lock_finder($lock_changes,$this->config->item('channel_lock_robot'))))
                        {
                            $content = ['chat_id' => $lock_changes];
                            $res = $this->telegram->createChatInviteLink($content);

                            if ($res['ok'])
                            {
                                array_push($channel_lock_robot, [$lock_changes, $res['result']['invite_link']]);
                                $json_data['data'] = json_encode($channel_lock_robot);
                                $this->Bot_model->set_options('channel_lock_robot', $json_data);
                                $content = ['chat_id' => $chat_id, 'text' => $this->config->item('locked_channel_added_warning')];
                                $res = $this->telegram->sendMessage($content);
                            }
                            else
                                {
                                $content = ['chat_id' => $chat_id, 'text' => $this->config->item('bot_channel_access_permission')];
                                $res = $this->telegram->sendMessage($content);
                            }
                        }
                        else
                        {
                            $content = ['chat_id' => $chat_id, 'text' => 'کانال قفل با از قفل بوده !'];
                            $res = $this->telegram->sendMessage($content);
                        }
                    }


                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                    break;

                case "88":
                    $lock_changes = $this->telegram->FromChatID();
                    $channel_lock_robot = $this->config->item('channel_lock_robot') == null ? [] : $this->config->item('channel_lock_robot');


                    $channel_location_in_arr  = channel_lock_finder($lock_changes,$this->config->item('channel_lock_robot'));
                    if(!is_null($channel_location_in_arr))
                    {
                        unset($channel_lock_robot[$channel_location_in_arr]);
                        $channel_lock_robot = array_values($channel_lock_robot);

                        if(count($channel_lock_robot))
                            $json_data['data'] = json_encode($channel_lock_robot);
                        else
                            $json_data['data'] = null;

                        $this->Bot_model->set_options('channel_lock_robot',$json_data);
                        $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('locked_channel_deleted_warning')];
                        $res = $this->telegram->sendMessage($content);
                    }
                    else
                    {
                        $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('locked_channel_not_found_in_list')];
                        $res = $this->telegram->sendMessage($content);
                    }

                    litespeed_finish_request();
                    sleep(60);
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                    $this->telegram->deleteMessage($content);
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
                    $this->telegram->deleteMessage($content);
                break;

                default:
                    $this->unknown_command_message();
                break;
            }
        }
        else
            $this->unknown_command_message();
    }

    public function video_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot']) {
            case "30":
                $content = ['chat_id' => $chat_id, 'from_chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok']) {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_' . $res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_' . $res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb = $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id' => $chat_id, 'text' => 'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم', 'reply_to_message_id' => $res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                } else {
                    $content = ['chat_id' => $chat_id, 'text' => $this->config->item('robot_cannot_send_message'), 'reply_to_message_id' => $this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id = $data['message']['video']['file_id'];
                $file_unique_id = $data['message']['video']['file_unique_id'];
                $thumb = $data['message']['video']['thumb']['file_id'];

                $content = ['chat_id' => $chat_id, 'caption' =>$this->config->item('add_post_guide_first_step_message'), 'video' => $file_id, 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendVideo($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>'تعداد فایل ارسالی برای پست : 1'.$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id']];
                $res_sm = $this->telegram->sendMessage($content);
                $file_data = [
                    'post_media_info' => json_encode(
                        [
                            [
                                'type' => 'video',
                                'media' => $file_id,
                                'thumb' => (isset($thumb)?$thumb:''),
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id' => 1,
                    'code' => $file_code,
                    'message_id_in_bot' => json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id'],
                        ]
                    ),
                    'creator_id' => $this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "51");

                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "51":

                $file_id = $data['message']['video']['file_id'];
                $file_unique_id = $data['message']['video']['file_unique_id'];
                $thumb = $data['message']['video']['thumb']['file_id'];

                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $post_media_info = json_decode($last_post_insert_by_user['post_media_info'], true);

                $message_id_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'], true);
                foreach ($message_id_in_bot as $item) {
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$item];
                    $this->telegram->deleteMessage($content);
                }

                $post_media_info = array_merge($post_media_info,
                    [
                        [
                            'type'=>'video',
                            'media'=>$file_id,
                            'thumb' => (isset($thumb)?$thumb:''),
                            'file_unique_id'=>$file_unique_id,
                        ]
                    ]
                );


                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                $caption_extra = "";
                $post_media_count = count($post_media_info);
                $media_count_txt = 'تعداد فایل ارسالی برای پست : '.$post_media_count.PHP_EOL.PHP_EOL;
                if($post_media_count == 10)
                {
                    $media_count_txt = "";
                    $caption_extra .= $this->config->item('file_limit_count_message');
                    $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                }
                $post_media_info = json_encode($post_media_info);

                $content = ['chat_id'=>$chat_id, 'media'=>$post_media_info];
                $res_smg = $this->telegram->sendMediaGroup($content);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$caption_extra.$media_count_txt.$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$last_post_insert_by_user['message_id_in_bot '], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $message_ids_in_bot = [];
                array_push($message_ids_in_bot,$res_sm ['result']['message_id']);
                foreach ($res_smg['result'] as $item) {
                    array_push($message_ids_in_bot,$item['message_id']);
                }

                $post_info_update = [
                    'post_media_info'=>$post_media_info,
                    'message_id_in_bot' => json_encode(
                        $message_ids_in_bot
                    )
                ];
                $this->Bot_model->update_post_info($post_info_update,['code'=>$last_post_insert_by_user['code']]);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$res_smg['result']['message_id']];
                $this->telegram->deleteMessage($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['video']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot'],
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>1,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update, ['code'=>$last_post_insert_by_user['code']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('ارسال پست به کانال 📤', $url = '', $callback_data = 'sendPostToChannel_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function photo_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();


        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id        = $data['message']['photo'][count($data['message']['photo'])-1]['file_id'];
                $file_unique_id = $data['message']['photo'][count($data['message']['photo'])-1]['file_unique_id'];

                $content = ['chat_id' => $chat_id, 'caption' => $this->config->item('add_post_guide_first_step_message'), 'photo' => $file_id, 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendPhoto($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>'تعداد فایل ارسالی برای پست : 1'.$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id']];
                $res_sm = $this->telegram->sendMessage($content);
                $file_data = [
                    'post_media_info' => json_encode(
                        [
                            [
                                'type' => 'photo',
                                'media' => $file_id,
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id' => 2,
                    'code' => $file_code,
                    'message_id_in_bot' => json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id'],
                        ]
                    ),
                    'creator_id' => $this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "56");

                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "56":

                $file_id        = $data['message']['photo'][count($data['message']['photo'])-1]['file_id'];
                $file_unique_id = $data['message']['photo'][count($data['message']['photo'])-1]['file_unique_id'];

                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $post_media_info = json_decode($last_post_insert_by_user['post_media_info'], true);

                $message_id_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'], true);
                foreach ($message_id_in_bot as $item) {
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$item];
                    $this->telegram->deleteMessage($content);
                }

                $post_media_info = array_merge($post_media_info,
                    [
                        [
                            'type'=>'photo',
                            'media'=>$file_id,
                            'file_unique_id'=>$file_unique_id,
                        ]
                    ]
                );


                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                $caption_extra = "";
                $post_media_count = count($post_media_info);
                $media_count_txt = 'تعداد فایل ارسالی برای پست : '.$post_media_count.PHP_EOL.PHP_EOL;
                if($post_media_count == 10)
                {
                    $media_count_txt = "";
                    $caption_extra .= $this->config->item('file_limit_count_message');
                    $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                }
                $post_media_info = json_encode($post_media_info);

                $content = ['chat_id'=>$chat_id, 'media'=>$post_media_info];
                $res_smg = $this->telegram->sendMediaGroup($content);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$caption_extra.$media_count_txt.$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$last_post_insert_by_user['message_id_in_bot '], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $message_ids_in_bot = [];
                array_push($message_ids_in_bot,$res_sm ['result']['message_id']);
                foreach ($res_smg['result'] as $item) {
                    array_push($message_ids_in_bot,$item['message_id']);
                }

                $post_info_update = [
                    'post_media_info'=>$post_media_info,
                    'message_id_in_bot' => json_encode(
                        $message_ids_in_bot
                    )
                ];
                $this->Bot_model->update_post_info($post_info_update,['code'=>$last_post_insert_by_user['code']]);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$res_smg['result']['message_id']];
                $this->telegram->deleteMessage($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['photo'][count($data['message']['photo'])-1]['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot'],
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>2,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [

                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله قبلی ⬅️', $url = '', $callback_data = 'previousStepForPost_'.$last_post_insert_by_user['code']),
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_' . $last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function animation_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id           = $data['message']['animation']['file_id'];
                $file_unique_id    = $data['message']['animation']['file_unique_id'];
                $thumb             = $data['message']['animation']['thumb']['file_id'];

                $content = ['chat_id'=>$chat_id, 'animation'=>$file_id, 'caption' => $this->config->item('add_post_guide_first_step_message'), 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendAnimation($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('file_limit_count_message').$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id'], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $file_data = [
                    'post_media_info'=>json_encode(
                        [
                            [
                                'type' => 'animation',
                                'thumb' => (isset($thumb)?$thumb:''),
                                'media' => $file_id,
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id'=>3,
                    'code'=>$file_code,
                    'caption'=>$this->telegram->Caption(),
                    'message_id_in_bot'=>json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id']
                        ]
                    ),
                    'creator_id'=>$this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "81");


                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['animation']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>3,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('ارسال پست به کانال 📤', $url = '', $callback_data = 'sendPostToChannel_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function audio_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id        = $data['message']['audio']['file_id'];
                $file_unique_id = $data['message']['audio']['file_unique_id'];
                $thumb          = $data['message']['audio']['thumb']['file_id'];

                $content = ['chat_id' => $chat_id, 'caption' =>$this->config->item('add_post_guide_first_step_message'), 'audio' => $file_id, 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendAudio($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>'تعداد فایل ارسالی برای پست : 1'.PHP_EOL.PHP_EOL.$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id']];
                $res_sm = $this->telegram->sendMessage($content);
                $file_data = [
                    'post_media_info' => json_encode(
                        [
                            [
                                'type' => 'audio',
                                'media' => $file_id,
                                'thumb' => (isset($thumb)?$thumb:''),
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id' => 4,
                    'code' => $file_code,
                    'message_id_in_bot' => json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id'],
                        ]
                    ),
                    'creator_id' => $this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "67");

                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "67":

                $file_id        = $data['message']['audio']['file_id'];
                $file_unique_id = $data['message']['audio']['file_unique_id'];
                $thumb          = $data['message']['audio']['thumb']['file_id'];

                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $post_media_info = json_decode($last_post_insert_by_user['post_media_info'], true);

                $message_id_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'], true);
                foreach ($message_id_in_bot as $item) {
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$item];
                    $this->telegram->deleteMessage($content);
                }

                $post_media_info = array_merge($post_media_info,
                    [
                        [
                            'type'=>'audio',
                            'media'=>$file_id,
                            'thumb' => (isset($thumb)?$thumb:''),
                            'file_unique_id'=>$file_unique_id,
                        ]
                    ]
                );


                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                $caption_extra = "";
                $post_media_count = count($post_media_info);
                $media_count_txt = 'تعداد فایل ارسالی برای پست : '.$post_media_count.PHP_EOL.PHP_EOL;
                if($post_media_count == 10)
                {
                    $media_count_txt = "";
                    $caption_extra .= $this->config->item('file_limit_count_message');
                    $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                }
                $post_media_info = json_encode($post_media_info);

                $content = ['chat_id'=>$chat_id, 'media'=>$post_media_info];
                $res_smg = $this->telegram->sendMediaGroup($content);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$caption_extra.$media_count_txt.$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$last_post_insert_by_user['message_id_in_bot '], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $message_ids_in_bot = [];
                array_push($message_ids_in_bot,$res_sm ['result']['message_id']);
                foreach ($res_smg['result'] as $item) {
                    array_push($message_ids_in_bot,$item['message_id']);
                }

                $post_info_update = [
                    'post_media_info'=>$post_media_info,
                    'message_id_in_bot' => json_encode(
                        $message_ids_in_bot
                    )
                ];
                $this->Bot_model->update_post_info($post_info_update,['code'=>$last_post_insert_by_user['code']]);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$res_smg['result']['message_id']];
                $this->telegram->deleteMessage($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['audio']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot'],
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>4,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>4
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_' . $last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function document_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id        = $data['message']['document']['file_id'];
                $file_unique_id = $data['message']['document']['file_unique_id'];
                $thumb          = $data['message']['document']['thumb']['file_id'];


                $content = ['chat_id' => $chat_id, 'caption' =>$this->config->item('add_post_guide_first_step_message'), 'document' => $file_id, 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendDocument($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>'تعداد فایل ارسالی برای پست : 1'.PHP_EOL.PHP_EOL.$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id']];
                $res_sm = $this->telegram->sendMessage($content);
                $file_data = [
                    'post_media_info' => json_encode(
                        [
                            [
                                'type' => 'document',
                                'media' => $file_id,
                                'thumb' => (isset($thumb)?$thumb:''),
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id' => 5,
                    'code' => $file_code,
                    'message_id_in_bot' => json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id'],
                        ]
                    ),
                    'creator_id' => $this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "72");

                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "72":

                $file_id        = $data['message']['document']['file_id'];
                $file_unique_id = $data['message']['document']['file_unique_id'];
                $thumb          = $data['message']['document']['thumb']['file_id'];


                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $post_media_info = json_decode($last_post_insert_by_user['post_media_info'], true);

                $message_id_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'], true);
                foreach ($message_id_in_bot as $item) {
                    $content = ['chat_id'=>$chat_id, 'message_id'=>$item];
                    $this->telegram->deleteMessage($content);
                }

                $post_media_info = array_merge($post_media_info,
                    [
                        [
                            'type'=>'document',
                            'media'=>$file_id,
                            'thumb' => (isset($thumb)?$thumb:''),
                            'file_unique_id'=>$file_unique_id,
                        ]
                    ]
                );


                $post_media_info = array_diff_key($post_media_info,['file_unique_id'=>'xxx']);
                $caption_extra = "";
                $post_media_count = count($post_media_info);
                $media_count_txt = 'تعداد فایل ارسالی برای پست : '.$post_media_count.PHP_EOL.PHP_EOL;
                if($post_media_count == 10)
                {
                    $media_count_txt = "";
                    $caption_extra .= $this->config->item('file_limit_count_message');
                    $this->Bot_model->change_user_location_in_bot($chat_id, "81");
                }
                $post_media_info = json_encode($post_media_info);

                $content = ['chat_id'=>$chat_id, 'media'=>$post_media_info];
                $res_smg = $this->telegram->sendMediaGroup($content);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$caption_extra.$media_count_txt.$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$last_post_insert_by_user['message_id_in_bot '], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $message_ids_in_bot = [];
                array_push($message_ids_in_bot,$res_sm ['result']['message_id']);
                foreach ($res_smg['result'] as $item) {
                    array_push($message_ids_in_bot,$item['message_id']);
                }

                $post_info_update = [
                    'post_media_info'=>$post_media_info,
                    'message_id_in_bot' => json_encode(
                        $message_ids_in_bot
                    )
                ];
                $this->Bot_model->update_post_info($post_info_update,['code'=>$last_post_insert_by_user['code']]);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$res_smg['result']['message_id']];
                $this->telegram->deleteMessage($content);
                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['document']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot'],
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>5,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_' . $last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function voice_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id        = $data['message']['voice']['file_id'];
                $file_unique_id = $data['message']['voice']['file_unique_id'];


                $content = ['chat_id' => $chat_id, 'caption' =>$this->config->item('add_post_guide_first_step_message'), 'voice' => $file_id, 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendVoice($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('file_limit_count_message').$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id'], 'parse_mode'=>'markdown'];

                $res_sm = $this->telegram->sendMessage($content);
                $file_data = [
                    'post_media_info' => json_encode(
                        [
                            [
                                'type' => 'voice',
                                'media' => $file_id,
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id' => 6,
                    'code' => $file_code,
                    'message_id_in_bot' => json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id'],
                        ]
                    ),
                    'creator_id' => $this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "81");

                $content = ['chat_id' => $chat_id, 'message_id' => $this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['voice']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot'],
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>6,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_' . $last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function video_note_type()
    {
        $this->load->model('Bot_model');
        @$chat_id     = $this->telegram->ChatID();
        @$user_id     = $this->telegram->UserID();
        @$text        = $this->telegram->Text();
        @$data        = $this->telegram->getData();

        $forwardFromData = $this->telegram->forwardFromData();
        $forwardFromChatData = $this->telegram->forwardFromChatData();

        $user_info = $this->Bot_model->get_user_info($user_id)[0];

        switch ($user_info['location_in_bot'])
        {
            case "30":
                $content = ['chat_id'=>$chat_id,'from_chat_id'=>$chat_id,'message_id'=>$this->telegram->MessageID()];
                $res = $this->telegram->forwardMessage($content);
                if ($res['ok'])
                {
                    $option =
                        [
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال با نام ارسال کننده 🏷', $url = '', $callback_data = 'sendBroadCastWithSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('ارسال بدون نام ❌🏷', $url = '', $callback_data = 'sendBroadCastWithOutSenderName_'.$res['result']['message_id']),
                            ],
                            [
                                $this->telegram->buildInlineKeyBoardButton('بازگشت به منو اصلی 🔙', $url = '', $callback_data = 'backToMainMenu'),
                            ]
                        ];
                    $keyb= $this->telegram->buildInlineKeyBoard($option);
                    $content = ['chat_id'=>$chat_id,'text'=>'پیام فووراد شده را به چه صورت برای ارسال همگانی آماده کنیم','reply_to_message_id'=>$res['result']['message_id'], 'reply_markup' => $keyb];
                    $this->telegram->sendMessage($content);
                }
                else
                {
                    $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('robot_cannot_send_message'),'reply_to_message_id'=>$this->telegram->MessageID()];
                    $this->telegram->sendMessage($content);
                }
                break;

            case "50":

                $file_id           = $data['message']['video_note']['file_id'];
                $file_unique_id    = $data['message']['video_note']['file_unique_id'];
                $thumb             = $data['message']['video_note']['thumb']['file_id'];

                $content = ['chat_id'=>$chat_id, 'video_note'=>$file_id, 'caption' => $this->config->item('add_post_guide_first_step_message'), 'parse_mode' => 'markdown'];
                $res = $this->telegram->sendVideoNote($content);

                $file_code = file_code_generator($file_unique_id);
                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('مرحله بعدی ➡️', $url = '', $callback_data = 'nextStepForPost_'.$file_code),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$file_code),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id,'text'=>$this->config->item('file_limit_count_message').$this->config->item('add_post_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup'=>$keyb , 'reply_to_message_id'=>$res['result']['message_id'], 'parse_mode'=>'markdown'];
                $res_sm = $this->telegram->sendMessage($content);

                $file_data = [
                    'post_media_info'=>json_encode(
                        [
                            [
                                'type' => 'video_note',
                                'thumb' => (isset($thumb)?$thumb:''),
                                'media' => $file_id,
                                'file_unique_id'=>$file_unique_id,
                            ]
                        ]
                    ),
                    'post_type_id'=>7,
                    'code'=>$file_code,
                    'caption'=>$this->telegram->Caption(),
                    'message_id_in_bot'=>json_encode(
                        [
                            $res_sm['result']['message_id'],
                            $res['result']['message_id']
                        ]
                    ),
                    'creator_id'=>$this->Bot_model->get_user_info($user_id)[0]['id'],
                ];

                $this->Bot_model->save_new_post_record($file_data);
                $this->Bot_model->change_user_location_in_bot($chat_id, "81");


                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            case "82":
                $file_id = $data['message']['video_note']['file_id'];
                $last_post_insert_by_user = $this->Bot_model->get_last_post_create_by_user($user_info['user_id'])[0];
                $message_ids_in_bot = json_decode($last_post_insert_by_user['message_id_in_bot'],true );

                $post_info_update = [
                    'post_cover_info'=>json_encode([
                        'post_type_id'=>7,
                        'file_id'=>$file_id
                    ]),
                    'complete_step_post_id'=>3
                ];
                $this->Bot_model->update_post_info($post_info_update,['message_id_in_bot'=>$last_post_insert_by_user['message_id_in_bot']]);

                $option =
                    [
                        [
                            $this->telegram->buildInlineKeyBoardButton('ارسال پست به کانال 📤', $url = '', $callback_data = 'sendPostToChannel_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('پیش نمایش پست 🧾', $url = '', $callback_data = 'showPostPreview_'.$last_post_insert_by_user['code']),
                        ],
                        [
                            $this->telegram->buildInlineKeyBoardButton('حذف پست ❌📝', $url = '', $callback_data = 'deletePost_'.$last_post_insert_by_user['code']),
                        ],
                    ];
                $keyb= $this->telegram->buildInlineKeyBoard($option);
                $content = ['chat_id'=>$chat_id, 'message_id' => $message_ids_in_bot[0], 'text'=>$this->config->item('add_poster_guide_message').$this->config->item('admin_keyboard_message'), 'reply_markup' => $keyb];
                $this->telegram->editMessageText($content);

                $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
                $this->telegram->deleteMessage($content);

                break;

            default:
                $this->unknown_command_message();
                break;
        }
    }

    public function unknown_command_message()
    {
        $this->config->load('bot_config');
        $this->load->database();
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram', ["bot_token" => $bot_token]);
        @$chat_id = $this->telegram->ChatID();

        $txt = "";
        if (is_admin($chat_id))
        {
            $txt.="`در ارسال پیام دقت کنید ! احتمالا پیام اشتباهی در مرحله اشتباهی فرستاده اید در صورت نیاز از گزینه بازگشت به منو اصلی استفاده نمایید.`".PHP_EOL.PHP_EOL;
            $txt.="یا از دستورات زیر استفاده کنید : ".PHP_EOL;
            $txt.="`باز شدن پنل مدیر` /adminaction".PHP_EOL;
        }
        $txt .= $this->config->item('unknown_command');
        $content = ['chat_id'=>$chat_id,'text'=>$txt, 'parse_mode'=>'markdown'];
        $res = $this->telegram->sendMessage($content);
        $content = ['chat_id'=>$chat_id, 'message_id'=>$this->telegram->MessageID()];
        $this->telegram->deleteMessage($content);
        litespeed_finish_request();
        sleep(15);
        $content = ['chat_id'=>$chat_id, 'message_id'=>$res['result']['message_id']];
        $this->telegram->deleteMessage($content);

    }

    public function setWebhook()
    {
        $this->config->load('bot_config');
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->model('Bot_model');
        $this->load->helper(['text','url']);
        header('Content-Type: application/json');
        echo json_encode($this->telegram->setWebhook(base_url()));
    }

    public function setFakeWebhook()
    {
        $this->config->load('bot_config');
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->model('Bot_model');
        $this->load->helper(['text','url']);
        header('Content-Type: application/json');
        echo json_encode($this->telegram->setWebhook(base_url().'../index.php'));
    }

    public function getwebhookinfo()
    {
        $this->config->load('bot_config');
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->model('Bot_model');
        $this->load->helper(['text','url']);
        header('Content-Type: application/json');
        echo json_encode($this->telegram->getwebhookinfo());
    }

    public function deleteWebhook()
    {
        $this->config->load('bot_config');
        $bot_token = $this->config->item('bot_token_code');
        $this->load->library('telegram',["bot_token"=>$bot_token]);
        $this->load->model('Bot_model');
        $this->load->helper(['text','url']);
        header('Content-Type: application/json');
        echo json_encode($this->telegram->deleteWebhook());
    }
}