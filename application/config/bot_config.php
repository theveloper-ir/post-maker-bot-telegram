<?php
$ci =& get_instance();
$ci->load->database();
$ci->load->model('Bot_model');

$config['bot_token_code'] = $ci->Bot_model->get_options('bot_token_code')[0]['data'];

$config['bot_id'] = $ci->Bot_model->get_options('bot_id')[0]['data'];

$config['admins_robot'] = json_decode($ci->Bot_model->get_options('admins_robot')[0]['data'],true);

$config['channel_lock_robot'] = json_decode($ci->Bot_model->get_options('channel_lock_robot')[0]['data'],true);

$config['forward_channel'] = json_decode($ci->Bot_model->get_options('forward_channel')[0]['data'],true);//main channel

$config['remove_tag_state'] = json_decode($ci->Bot_model->get_options('remove_tag_from_text')[0]['data'],true);

$config['remove_link_state'] = json_decode($ci->Bot_model->get_options('remove_link_from_text')[0]['data'],true);

$config['remove_username_state'] = json_decode($ci->Bot_model->get_options('remove_username_from_text')[0]['data'],true);


$config['admin_keyboard_message'] = "برای انجام اعمال مدیریتی از کیبورد پایین استفاده کنید!";

$config['robot_down'] = "درحال انجام بروز رسانی لطفا دقایقی دیگر مجدد تلاش کنید !!!!";

$config['available_command'] = ['/start', '/help', '/state', '/adminaction', '/sendbroadcastmessage'];

$config['available_message_type'] = ['message', 'video', 'callback_query', 'photo', 'animation', 'document', 'audio','video_note'];

$config['welcome_message'] = " برترین ربات اشتراک گذاری فایل 📂
 با سرعت و امنیت بسیار بالا 💎
برای دریافت ویدیو ها به کانال  مراجعه کنید.";

$config['unknown_command'] = "متوجه دستور شما نشدیم لطفا مجدد تلاش کنید !!!!";

$config['join_warning'] = "کاربر عزیز

برای استفاده از ربات  ابتدا باید در کانال های ما عضو بشی 👇🏻

از لینک های زیر برای عضویت استفاده کن

 بعد از عضـــویت در کانال ها « /start » را لمس کنید تا ربات برای شما فعال شود 👇🏻
 /start
 ";

$config['recipe_for_add_new_post']  = "برای ارسال پست جدید طبق الگوی زیر عمل کنید : ".PHP_EOL.PHP_EOL;
$config['recipe_for_add_new_post'] .= "ابتدا این پیام را ریپلای کرده و با توجه به تعداد و مشخصاتی که در ادامه گفته شده رسانه های خود را ارسال کنید.".PHP_EOL.PHP_EOL;
$config['recipe_for_add_new_post'] .= "`آهنگ -> حداکثر 10 فایل`".PHP_EOL;
$config['recipe_for_add_new_post'] .= "`عکس -> حداکثر 10 فایل`".PHP_EOL;
$config['recipe_for_add_new_post'] .= "`ویدیو -> حداکثر 10 فایل`".PHP_EOL;
$config['recipe_for_add_new_post'] .= "`فایل -> حداکثر 10 فایل`".PHP_EOL;
$config['recipe_for_add_new_post'] .= "`انیمیشن(گیف) ->  حداکثر 1 فایل`".PHP_EOL;
$config['recipe_for_add_new_post'] .= "`ویدیو مسیج ->  حداکثر 1 فایل`".PHP_EOL;

$config['send_board_cast_message_text'] = "برای ارسال پیام سراسری طبق الگوی زیر عمل کنید : ".PHP_EOL.PHP_EOL;
$config['send_board_cast_message_text'] .= "`/sendBroadCastMessage` ";
$config['send_board_cast_message_text'] .= "` پیام مورد نظر`".PHP_EOL;
$config['send_board_cast_message_text'] .= "متغیر های مورد استفاده".PHP_EOL;
$config['send_board_cast_message_text'] .= "نام کاربر -> {name}";


$config['update_vid_caption_message'] = "برای ویرایش کپشن ابتدا ویدیو مورد نظر ویدیو را ریپلای کرده کلمه  `updateVideoCaption` را نوشته و در خط بعدی کپشن خود را ارسال کنید طبق الگوی زیر :  ";

$config['update_vid_caption_reply_warning'] = "برای اعمال هرگونه تغییرات عنوان ویدیو مورد نظر را ریپلای کنید !";

$config['update_vid_poster_reply_warning'] = "برای اعمال هرگونه تغییرات پوستر ویدیو مورد نظر را ریپلای کنید !";


$config['not_found_file'] = "فایل مورد نظر یافت نشد !!!!";

$config['admin_help_message'] = "مدیر عزیز سلام 😊".PHP_EOL;
$config['admin_help_message'] .= "برای استفاده از امکانات ربات از دستورات زیر استفاده کنید".PHP_EOL;
$config['admin_help_message'] .= "لیست دستورات : ".PHP_EOL;
$config['admin_help_message'] .= "نمایش پنل مدیریتی ربات : دستور `پنل` یا /adminaction ".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "نمایش پنل مدیریتی ربات : دستور `راهنما` یا /help ".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "افزودن مدیر به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `افزودن مدیر یوزرآیدی`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `افزودن مدیر` سپس فوروارد *پیام متنی* از کاربر مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "حذف مدیر به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `حذف مدیر یوزرآیدی`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `حذف مدیر` سپس فوروارد *پیام متنی* از کاربر مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "### دقت شود در صورت فوروارد پیام از کاربر تنظیمات حریم خصوصی کاربر مورد نظر باز بوده تا یوزر آی دی کاربر ثبت گردد ###".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "افزودن فوروارد به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `افزودن فوروارد یوزرآیدی کانال`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `افزودن فوروارد` سپس فوروارد *پیام متنی* از کانال مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "تغییر فوروارد به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `تغییر فوروارد یوزرآیدی کانال`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `تغییر فوروارد` سپس فوروارد *پیام متنی* از کانال مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "افزودن قفل به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `افزودن قفل یوزرآیدی کانال`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `افزودن قفل` سپس فوروارد *پیام متنی* از کانال مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "حذف قفل به 2 صورت زیر صورت میپذیرد : ".PHP_EOL;
$config['admin_help_message'] .= "1 - دستور `حذف قفل یوزرآیدی کانال`".PHP_EOL;
$config['admin_help_message'] .= "2 - دستور `حذف قفل` سپس فوروارد *پیام متنی* از کانال مورد نظر".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "به جهت تغییر کپشن (عنوان) پست مورد نظر در زمانی که از شما خواسته شد از دستور زیر استفاده نمایید : ".PHP_EOL;
$config['admin_help_message'] .= "`کپشن عنوان پست مورد نظر`".PHP_EOL.PHP_EOL;
$config['admin_help_message'] .= "در صورتی که درخواست اضافه کردن لینک به پیامی جهت ارسال سراسری دارید از دستور زیر استفاده کنید: ".PHP_EOL;
$config['admin_help_message'] .= "`لینک عنوان لینک *** آدرس لینک`".PHP_EOL.PHP_EOL;



$config['user_help_message'] = "کاربر عزیز سلام 😊".PHP_EOL;
$config['user_help_message'] .= "برای دسترسی به محتوای کانال از لینک زیر استفاده کنید : ".PHP_EOL.PHP_EOL;
$config['user_help_message'] .= "{channel}";

$config['robot_cannot_send_message'] = "مشکلی در ارسال پیام رخ داده است لطفا مجدد تلاش کنید !!!";

$config['forward_channel_seted_warning'] = "کانال فوروراد با موفقیت افزوده شد";
$config['forward_channel_changed_warning'] = "کانال فوروراد با موفقیت تغییر یافت";
$config['forward_channel_is_set_warning'] = "کانال فوروراد قبلا افزوده شده است برای تغییر آن از دستور /help کمک بگیرید.";
$config['forward_channel_send_forward_from_channel'] = "جهت افزودن کانال برای فووراد پیامی متنی از کانال مورد نظر را فووراد کنید";
$config['forward_channel_change_send_forward_from_channel'] = "جهت تغییر کانال برای فووراد پیامی متنی از کانال مورد نظر را فووراد کنید";
$config['forward_channel_admin_required_warning'] = "ابتدا ربات را در کانال مورد نظر مدیر کرده و سپس مجدد دستورات خود را ارسال نمایید !";

$config['locked_channel_added_warning'] = "کانال قفل با موفقیت افزوده شد !";
$config['locked_channel_deleted_warning'] = "کانال قفل با موفقیت حذف شد !";
$config['locked_channel_count_warning'] = "امکان افزودن بیشتر از 5 قفل برای ربات مهیا نشده است !";
$config['locked_channel_not_found_in_list'] = "'کانال قفل جهت حذف پیدا نشد !' !";
$config['locked_channel_add_send_forward_from_channel'] = "جهت افزودن کانال قفل پیامی متنی از کانال مورد نظر را فووراد کنید";
$config['locked_channel_change_send_forward_from_channel'] = "جهت حذف کانال قفل پیامی متنی از کانال مورد نظر را فووراد کنید";


$config['bot_channel_access_permission'] = "پیام ارسالی حتما باید از کانال باشد یا ربات دسترسی های لازم به کانال مورد نظر شما را ندارد لطفا مجدد دسترسی هایی ربات در کانال مورد نظر را بررسی و مجدد اقدام نمایید !!!!";

$config['add_caption_guide_message'] = "لطفا در این مرحله عنوان مورد نظر خود را طبق الگو زیر ارسال کنید".PHP_EOL;
$config['add_caption_guide_message'] .= "`کپشن عنوان پست مورد نظر`".PHP_EOL.PHP_EOL;

$config['add_poster_guide_message'] = "لطفا در این مرحله  پوستر نظر خود را ارسال کنید".PHP_EOL.PHP_EOL;
$config['add_poster_guide_message'] .= "### در نظر داشته باشید در صورتی که در این مرحله رسانه ای ارسال نکنید عنوان به عنوان پوستر در نظر گرفته خواهد شد ###".PHP_EOL.PHP_EOL;




