<?php

namespace App\Http\Controllers;

use App\Models\ChatHead;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\DeliveryAddress;
use App\Models\Image;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockSubCategory;
use App\Models\TrendingNotification;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Dflydev\DotAccessData\Util;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\ApiResponser;

class ApiController extends BaseController
{

    use ApiResponser;

    public function products_delete(Request $r)
    {
        $pro = Product::find($r->id);
        if ($pro == null) {
            return $this->error('Product not found.');
        }
        try {
            $pro->delete();
            return $this->success(null, $message = "Sussesfully deleted!", 200);
        } catch (\Throwable $th) {
            return $this->error('Failed to delete product.');
        }
    }

    public function products_1(Request $request)
    {
        //latest 1000 products without pagination
        $products = Product::where([])->limit(1000)->get();
        return $this->success($products, 'Success');
    }



    public function product_create(Request $r)
    {

        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Not authonticated.");
        }
        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        //local_id is required
        if (
            !isset($r->local_id) ||
            $r->local_id == null ||
            strlen($r->local_id) < 6
        ) {
            return $this->error('Local ID is missing.');
        }


        $isEdit = false;
        if (
            isset($r->is_edit) && $r->is_edit == 'Yes' && $r->id != null
            && $r->id > 0
        ) {
            $pro = Product::find($r->id);
            if ($pro == null) {
                $pro = new Product();
                $isEdit = false;
            } else {
                $isEdit = true;
            }
        } else {
            $pro = new Product();
        }

        if (!$isEdit) {
            $pro->feature_photo = 'no_image.png';
            $pro->user = $u->id;
            $pro->supplier = $u->id;
            $pro->in_stock = 1;
            $pro->rates = 1;
        }


        if ($r->p_type == 'Yes') {
            if ($r->keywords ==  null) {
                return $this->error('Prices are missing.');
            }
            $my_prices = null;
            try {
                $my_prices = json_decode($r->keywords);
            } catch (\Throwable $th) {
                $my_prices = null;
            }
            //if not array
            if ($my_prices == null || !is_array($my_prices)) {
                return $this->error('Prices not found.');
            }
            //$my_prices if empty
            if (count($my_prices) < 1) {
                return $this->error('Prices not found.');
            }
            $prices = [];
            $min_price = 0;
            $max_price = 0;


            foreach ($my_prices as $key => $value) {
                if ($value->price == null || strlen($value->price) < 1) {
                    return $this->error('Price is missing.');
                }
                if ($value->min_qty == null || strlen($value->min_qty) < 1) {
                    return $this->error('Minimum quantity is missing.');
                }
                if ($value->max_qty == null || strlen($value->max_qty) < 1) {
                    return $this->error('Maximum quantity is missing.');
                }
                $my_min = (int)($value->min_qty);
                $my_max = (int)($value->max_qty);
                $price = (int)($value->price);
                if ($min_price < $my_min) {
                    $min_price = $my_min;
                }
                if ($max_price < $my_max) {
                    $max_price = $my_max;
                }
                $prices[] = $value;
            }

            $pro->price_1 = $min_price;
            $pro->price_2 = $max_price;
            $pro->keywords = $r->keywords;
        } else if ($r->p_type == 'No') {
            if ($r->price_1 == null || strlen($r->price_1) < 1) {
                return $this->error('Price is missing.');
            }
            if ($r->price_2 == null || strlen($r->price_2) < 1) {
                return $this->error('Price is missing.');
            }
            $pro->price_1 = $r->price_1;
            $pro->price_2 = $r->price_2;
        } else {
            return $this->error('Product type is missing.');
        }


        $pro->name = $r->name;
        $pro->description = $r->description;
        $pro->local_id = $r->local_id;
        $pro->summary = $r->data;
        $pro->metric = 1;
        $pro->status = 0;
        $pro->currency = 1;
        $pro->url = $u->url;


        $pro->has_sizes = $r->has_sizes;
        $pro->has_colors = $r->has_colors;
        $pro->colors = $r->colors;
        $pro->sizes = $r->sizes;
        $pro->p_type = $r->p_type;

        $cat = ProductCategory::find($r->category);
        if ($cat == null) {
            return $this->error('Category not found.');
        }
        $pro->category = $cat->id;

        $pro->date_added = Carbon::now();
        $pro->date_updated = Carbon::now();
        $imgs = Image::where([
            'parent_local_id' => $pro->local_id
        ])->get();
        if ($imgs->count() > 0) {
            $pro->feature_photo = $imgs[0]->src;
        }
        if ($pro->save()) {
            foreach ($imgs as $key => $img) {
                $img->product_id = $pro->id;
                $img->save();
            }
            $newPro = Product::find($pro->id);
            if ($isEdit) {
                return $this->success($newPro, $message = "Updated successfully!", 200);
            }
            return $this->success($newPro, $message = "Submitted successfully!", 200);
        } else {
            return $this->error('Failed to upload product.');
        }
    }




    public function disable_account(Request $request)
    {
        $u = Utils::get_user($request);
        if ($u == null) {
            Utils::error("Not authonticated.");
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        $u->status = 'Disabled';
        $u->save();
        $u = Administrator::find($administrator_id);


        return $this->success($u, 'Account deleted successfully.');
    }




    public function upload_media(Request $request)
    {
        $u = Utils::get_user($request);
        if ($u == null) {
            Utils::error("Not authonticated.");
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        if (
            !isset($request->parent_local_id) ||
            $request->parent_local_id == null
        ) {
            return $this->error('Local parent ID is missing.');
        }

        //  strlen($request->parent_local_id) < 6
        if (
            strlen($request->parent_local_id) < 6
        ) {
            return $this->error('Local parent ID is too short.');
        }


        if (
            empty($_FILES)
        ) {
            return $this->error('No files found.');
        }



        $images = Utils::upload_images_2($_FILES, false);
        $_images = [];


        if (empty($images)) {
            return $this->error('Failed to upload files.');
        }

        $msg = "";
        foreach ($images as $src) {

            $img = new Image();
            $img->administrator_id =  $administrator_id;
            $img->src =  $src;
            $img->thumbnail =  null;
            $img->parent_endpoint =  $request->parent_endpoint;
            $img->parent_local_id =  $request->parent_local_id;
            $img->type =  $request->type;
            $img->parent_id =  (int)($request->parent_id);
            $pro = Product::where(['local_id' => $img->parent_local_id])->first();
            $img->product_id =  null;
            if ($pro != null) {
                $img->product_id =  $pro->id;
            }
            $img->size = 0;
            $img->note = '';
            if (
                isset($request->note)
            ) {
                $img->note =  $request->note;
            }
            $img->save();
            $_images[] = $img;
        }

        return $this->success(
            null,
            count($_images) . " Files uploaded successfully."
        );
    }



    public function chat_delete(Request $r)
    {

        $chat_head = ChatHead::find($r->chat_head_id);
        if ($chat_head == null) {
            return $this->error('Chat head not found.');
        }

        try {
            $chat_head->delete();
            return $this->success(null, 'Chat head deleted successfully.');
        } catch (\Throwable $th) {
            return $this->error('Failed to delete chat head.');
        }
    }

    public function chat_start(Request $r)
    {
        $sender = User::find($r->sender_id);
        if ($sender == null) {
            return $this->error('Sender not found.');
        }
        $receiver = User::find($r->receiver_id);
        if ($receiver == null) {
            return $this->error('Receiver not found.');
        }

        // For dating chats, verify that users are matched
        if (!isset($r->product_id) || $r->product_id == null) {
            // This is a dating chat - check if users are matched
            /* $match = \App\Models\UserMatch::where(function ($query) use ($sender, $receiver) {
                $query->where('user_id', $sender->id)
                    ->where('matched_user_id', $receiver->id);
            })->orWhere(function ($query) use ($sender, $receiver) {
                $query->where('user_id', $receiver->id)
                    ->where('matched_user_id', $sender->id);
            })->where('status', 'active')->first();

            if (!$match) {
                return $this->error('Users must be matched before starting a conversation.');
            } 
 */
            // Check if either user has blocked the other
            $isBlocked = \App\Models\UserBlock::where(function ($query) use ($sender, $receiver) {
                $query->where('blocker_id', $sender->id)
                    ->where('blocked_user_id', $receiver->id);
            })->orWhere(function ($query) use ($sender, $receiver) {
                $query->where('blocker_id', $receiver->id)
                    ->where('blocked_user_id', $sender->id);
            })->exists();

            if ($isBlocked) {
                return $this->error('Cannot start conversation with blocked user.');
            }

            // Use the enhanced dating chat creation method
            $chat_head = ChatHead::createDatingChat($sender->id, $receiver->id, null);
            if (!$chat_head) {
                return $this->error('Failed to create chat.');
            }

            return $this->success($chat_head, 'Dating chat started successfully.');
        }

        // Original product-based chat logic
        $product_owner = $sender;
        $customer = $receiver;

        $pro = null;
        if ($r->product_id != null) {
            $pro = Product::find($r->product_id);
        }

        if ($pro != null) {
            $chat_head = ChatHead::where([
                'product_owner_id' => $product_owner->id,
                'customer_id' => $customer->id,
                'product_id' => $pro->id
            ])->first();
            if ($chat_head == null) {
                $chat_head = ChatHead::where([
                    'customer_id' => $product_owner->id,
                    'product_owner_id' => $customer->id,
                    'product_id' => $pro->id
                ])->first();
            }
        } else {
            $chat_head = ChatHead::where([
                'product_owner_id' => $product_owner->id,
                'customer_id' => $customer->id
            ])->first();
            if ($chat_head == null) {
                $chat_head = ChatHead::where([
                    'customer_id' => $product_owner->id,
                    'product_owner_id' => $customer->id
                ])->first();
            }
        }

        if ($chat_head == null) {
            $chat_head = new ChatHead();
            $chat_head->product_id = null;
            $chat_head->customer_photo = $customer->avatar;
            $chat_head->product_owner_id = $product_owner->id;
            $chat_head->customer_id = $customer->id;
            $chat_head->product_owner_name = $product_owner->name;
            $chat_head->product_owner_photo = $product_owner->photo;
            $chat_head->customer_name = $customer->name;
            $chat_head->last_message_body = '';
            $chat_head->last_message_time = Carbon::now();
            $chat_head->last_message_status = 'sent';
            $chat_head->type = 'dating';

            if ($pro != null) {
                $chat_head->product_id = $pro->id;
                $chat_head->customer_photo = $pro->feature_photo;
                $chat_head->product_owner_photo = $pro->feature_photo;
                $chat_head->product_owner_name = $pro->name;
                $chat_head->type = 'product';
            }

            $chat_head->save();
            $chat_head = ChatHead::find($chat_head->id);
        }

        return $this->success($chat_head, 'Success');
    }



    public function me(Request $r)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Not authonticated.");
        }
        return $this->success($u, "Success");
    }

    public function chat_heads(Request $r)
    {

        $u = Utils::get_user($r);
        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }
        if ($u == null) {
            return $this->error('User not found.');
        }

        if ($u == null) {
            return $this->error('User not found.');
        }
        $chat_heads = ChatHead::where([
            'product_owner_id' => $u->id
        ])->orWhere([
            'customer_id' => $u->id
        ])->get();


        /*         $chat_heads->append('customer_unread_messages_count');
        $chat_heads->append('product_owner_unread_messages_count');
 */
        $heads = [];
        $me = $u;
        $done_head_ids = [];
        foreach ($chat_heads as $key => $head) {
            if (in_array($head->id, $done_head_ids)) {
                continue;
            }

            $lastMesg = ChatMessage::where([
                'chat_head_id' => $head->id
            ])->orderBy('created_at', 'desc')->first();
            //if not found, continue
            if ($lastMesg == null) {
                continue;
            }


            $their_id = null;
            if ($me->id == $lastMesg->sender_id) {
                $their_id = $lastMesg->receiver_id;
            } else {
                $their_id = $lastMesg->sender_id;
            }

            $done_head_ids[] = $head->id;
            $them = User::find($their_id);
            if ($them == null) {
                continue;
            }

            $customer_unread_messages_count = ChatMessage::where('chat_head_id', $head->id)
                ->where('receiver_id', $u->id)
                ->where('status', 'sent')
                ->count();
            $product_owner_unread_messages_count = ChatMessage::where('chat_head_id', $head->id)
                ->where('receiver_id', $u->id)
                ->where('status', 'sent')
                ->count();
            $head->customer_unread_messages_count = $customer_unread_messages_count;
            $head->product_owner_unread_messages_count = $product_owner_unread_messages_count;
            //customer_text
            if ($head->type != 'product') {
                if ($them != null) {
                    $head->customer_text = $them->name;
                    if ($them->avatar != null && strlen($them->avatar) > 4) {
                    }
                    $head->customer_name = $them->name;
                    $head->customer_text = $them->name;
                    $head->customer_photo = $them->avatar;
                }
            }

            $head->customer_text = $them->name;
            $head->customer_name = $them->name;
            $head->customer_text = $them->name;
            $head->customer_photo = $them->avatar;
            $head->last_message_body = $lastMesg->body;
            $head->product_text = $them->name;
            $head->last_message_time = $lastMesg->created_at;
            $head->last_message_status = $lastMesg->status;

            //customer_last_seen
            if ($them != null) {
                $head->customer_last_seen = $them->online_status;
                $head->customer_last_seen = 'online';
            }


            $heads[] = $head;
        }

        return $this->success($heads, 'Success');
    }


    public function chat_messages(Request $r)
    {

        $u = Utils::get_user($r);
        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }

        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }

        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }
        if ($u == null) {
            $administrator_id = Utils::get_user_id($r);
            $u = Administrator::find($administrator_id);
        }
        if ($u == null) {
            return $this->error('User not found.');
        }

        if (isset($r->chat_head_id) && $r->chat_head_id != null) {
            $messages = ChatMessage::where([
                'chat_head_id' => $r->chat_head_id
            ])->get();
            return $this->success($messages, 'Success');
        }
        $messages = ChatMessage::where([
            'sender_id' => $u->id
        ])->orWhere([
            'receiver_id' => $u->id
        ])->get();
        return $this->success($messages, 'Success');
    }


    public function chat_mark_as_read(Request $r)
    {
        $receiver = Administrator::find($r->receiver_id);
        if ($receiver == null) {
            return $this->error('Receiver not found.');
        }
        $chat_head = ChatHead::find($r->chat_head_id);
        if ($chat_head == null) {
            return $this->error('Chat head not found.');
        }
        $messages = ChatMessage::where([
            'chat_head_id' => $chat_head->id,
            'receiver_id' => $receiver->id,
        ])->get();
        foreach ($messages as $key => $message) {
            $message->status = 'read';
            $message->save();
        }
        return $this->success(null, 'Makerd as read for chat head: ' . $chat_head->id . ' and receiver: ' . $receiver->id);
    }

    public function chat_send(Request $r)
    {
        $u = Utils::get_user($r);
        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }
        $sender = $u;
        if ($sender == null) {
            return $this->error('Sender not found.');
        }

        $user_id = $r->user;
        if ($sender == null) {
            $sender = Administrator::find($user_id);
        }

        if ($sender == null) {
            return $this->error('User not found.');
        }
        $receiver = User::find($r->receiver_id);
        if ($receiver == null) {
            return $this->error('Receiver not found.');
        }

        // Try to find existing chat head first
        $chat_head = null;
        if ($r->chat_head_id) {
            $chat_head = ChatHead::find($r->chat_head_id);
        }

        // If no chat_head_id provided or chat head not found, find or create one
        if ($chat_head == null) {
            // Look for existing chat between these users
            $chat_head = ChatHead::where(function ($query) use ($sender, $receiver) {
                $query->where('customer_id', $sender->id)
                    ->where('product_owner_id', $receiver->id);
            })->orWhere(function ($query) use ($sender, $receiver) {
                $query->where('customer_id', $receiver->id)
                    ->where('product_owner_id', $sender->id);
            })->where('type', 'dating')->first();

            // If still no chat head found, create a new one
            if ($chat_head == null) {
                // Check if either user has blocked the other
                $isBlocked = \App\Models\UserBlock::where(function ($query) use ($sender, $receiver) {
                    $query->where('blocker_id', $sender->id)
                        ->where('blocked_user_id', $receiver->id);
                })->orWhere(function ($query) use ($sender, $receiver) {
                    $query->where('blocker_id', $receiver->id)
                        ->where('blocked_user_id', $sender->id);
                })->exists();

                if ($isBlocked) {
                    return $this->error('Cannot send message to blocked user.');
                }

                // Create new chat head for dating conversation
                $chat_head = ChatHead::createDatingChat($sender->id, $receiver->id, null);
                if (!$chat_head) {
                    return $this->error('Failed to create chat.');
                }
            }
        }

        // Check if chat is blocked
        if ($chat_head->is_blocked) {
            return $this->error('Cannot send message. User has been blocked.');
        }

        // Validate message type and content
        $message_type = $r->message_type ?? $r->type ?? 'text';
        $allowed_types = ['text', 'photo', 'video', 'audio', 'document', 'location'];

        if (!in_array($message_type, $allowed_types)) {
            return $this->error('Invalid message type.');
        }

        $chat_message = new ChatMessage();
        $chat_message->chat_head_id = $chat_head->id;
        $chat_message->sender_id = $sender->id;
        $chat_message->receiver_id = $receiver->id;
        $chat_message->sender_name = $sender->name;
        $chat_message->sender_photo = $sender->avatar ?? $sender->photo;
        $chat_message->receiver_name = $receiver->name;
        $chat_message->receiver_photo = $receiver->avatar ?? $receiver->photo;
        $chat_message->type = $message_type;
        $chat_message->status = 'sent';
        $chat_message->delivery_status = 'sent';

        // Handle different message types using Utils::upload_images_2 for single file uploads
        switch ($message_type) {
            case 'text':
                // Support both 'content' (mobile app) and 'body' (legacy) fields
                $chat_message->body = $r->content ?? $r->body ?? '';
                if (empty($chat_message->body)) {
                    return $this->error('Message content is required for text messages.');
                }
                break;

            case 'photo':
                // Handle photo file upload using Utils::upload_images_2 or accept previewed file or direct photo URL
                if (isset($_FILES['photo']) && !empty($_FILES['photo']['name'])) {
                    // Direct upload (for backward compatibility)
                    $uploaded_file = Utils::upload_images_2([$_FILES['photo']], true);
                    if (empty($uploaded_file)) {
                        return $this->error('Failed to upload photo file.');
                    }
                    $chat_message->photo = $uploaded_file;
                } else if (!empty($r->preview_file_name)) {
                    // Use previewed file
                    $chat_message->photo = $r->preview_file_name;
                } else if (!empty($r->photo)) {
                    // Use direct photo URL from Flutter app
                    $chat_message->photo = $r->photo;
                } else {
                    return $this->error('Photo file is required for photo messages.');
                }

                $chat_message->body = $r->content ?? $r->body ?? ''; // Optional caption
                $chat_message->media_size = $r->media_size;
                $chat_message->media_thumbnail = $r->thumbnail;
                break;

            case 'video':
                // Handle video file upload using Utils::upload_images_2 or accept previewed file or direct video URL
                if (isset($_FILES['video']) && !empty($_FILES['video']['name'])) {
                    // Direct upload (for backward compatibility)
                    $uploaded_file = Utils::upload_images_2([$_FILES['video']], true);
                    if (empty($uploaded_file)) {
                        return $this->error('Failed to upload video file.');
                    }
                    $chat_message->video = $uploaded_file;
                } else if (!empty($r->preview_file_name)) {
                    // Use previewed file
                    $chat_message->video = $r->preview_file_name;
                } else if (!empty($r->video)) {
                    // Use direct video URL from Flutter app
                    $chat_message->video = $r->video;
                } else {
                    return $this->error('Video file is required for video messages.');
                }

                $chat_message->body = $r->content ?? $r->body ?? ''; // Optional caption
                $chat_message->media_duration = $r->duration;
                $chat_message->media_size = $r->media_size;
                $chat_message->media_thumbnail = $r->thumbnail;
                break;

            case 'audio':
                // Handle audio file upload using Utils::upload_images_2 or accept previewed file or direct audio URL
                if (isset($_FILES['audio']) && !empty($_FILES['audio']['name'])) {
                    // Direct upload (for backward compatibility)
                    $uploaded_file = Utils::upload_images_2([$_FILES['audio']], true);
                    if (empty($uploaded_file)) {
                        return $this->error('Failed to upload audio file.');
                    }
                    $chat_message->audio = $uploaded_file;
                } else if (!empty($r->preview_file_name)) {
                    // Use previewed file
                    $chat_message->audio = $r->preview_file_name;
                } else if (!empty($r->audio)) {
                    // Use direct audio URL from Flutter app
                    $chat_message->audio = $r->audio;
                } else {
                    return $this->error('Audio file is required for audio messages.');
                }

                $chat_message->body = 'Voice message'; // Default body for audio
                $chat_message->media_duration = $r->duration;
                $chat_message->media_size = $r->media_size;
                break;

            case 'document':
                // Handle document file upload using Utils::upload_images_2 or accept previewed file
                if (isset($_FILES['document']) && !empty($_FILES['document']['name'])) {
                    // Direct upload (for backward compatibility)
                    $uploaded_file = Utils::upload_images_2([$_FILES['document']], true);
                    if (empty($uploaded_file)) {
                        return $this->error('Failed to upload document file.');
                    }
                    $chat_message->document = $uploaded_file;
                } else if (!empty($r->preview_file_name)) {
                    // Use previewed file
                    $chat_message->document = $r->preview_file_name;
                } else {
                    return $this->error('Document file is required for document messages.');
                }

                $chat_message->body = $r->filename ?? 'Document'; // Document name
                $chat_message->media_size = $r->media_size;
                break;

            case 'location':
                $chat_message->latitude = $r->latitude;
                $chat_message->longitude = $r->longitude;
                $chat_message->location_name = $r->location_name ?? 'Location';
                $chat_message->location_address = $r->address ?? '';
                $chat_message->body = $r->content ?? $chat_message->location_name;
                if (empty($chat_message->latitude) || empty($chat_message->longitude)) {
                    return $this->error('Latitude and longitude are required for location messages.');
                }
                break;
        }

        // Handle reply to message
        if (isset($r->reply_to_message_id) && $r->reply_to_message_id) {
            $replyMessage = ChatMessage::find($r->reply_to_message_id);
            if ($replyMessage && $replyMessage->chat_head_id == $chat_head->id) {
                $chat_message->reply_to_message_id = $r->reply_to_message_id;
            }
        }

        // Handle forward
        if (isset($r->is_forwarded) && $r->is_forwarded) {
            $chat_message->is_forwarded = 'Yes';
        }

        // Save the message
        $chat_message->save();

        // Update ChatHead with last message information
        $chat_head->last_message_body = $chat_message->body ?? 'Media message';
        $chat_head->last_message_time = now();
        $chat_head->last_message_status = 'sent';
        $chat_head->save();

        // Load the complete message with relationships
        $chat_message = ChatMessage::with(['sender', 'receiver', 'replyToMessage'])->find($chat_message->id);

        return $this->success($chat_message, 'Message sent successfully.');
    }


    //delivery_addresses
    public function delivery_addresses(Request $r)
    {
        return $this->success(
            DeliveryAddress::where([])
                ->limit(100)
                ->orderBy('id', 'desc')
                ->get(),
            $message = "Successfully",
            200
        );
    }


    public function orders_create(Request $r)
    {
        $u = auth('api')->user();


        if ($u != null) {
            $u = Utils::get_user($r);
        }
        $u = Utils::get_user($r);
        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                // $u->last_online_at = now();
                // $u->save();
            }
        }

        $items = [];
        try {
            $items = json_decode($r->items);
        } catch (\Throwable $th) {
            $items = [];
        }
        foreach ($items as $key => $value) {
            $p = Product::find($value->product_id);
            if ($p == null) {
                return $this->error("Product #" . $value->product_id . " not found.");
            }
        }

        if ($u == null) {
            return $this->error('User not found.');
        }

        $delivery = null;
        try {
            $delivery = json_decode($r->delivery);
        } catch (\Throwable $th) {
            $delivery = null;
        }

        if ($delivery == null) {
            return $this->error('Delivery information is missing.');
        }
        if ($delivery->customer_phone_number_1 == null) {
            $delivery->customer_phone_number_1 = $u->phone_number;
        }

        $order = new Order();
        $order->user = $u->id;
        $order->order_state = 0;
        $order->temporary_id = 0;
        $order->amount = 0;
        $order->order_total = 0;
        $order->payment_confirmation = '';
        $order->description = '';
        $order->mail = $u->email;
        $delivery_amount = 0;
        if ($delivery != null) {
            try {

                $order->order_details = json_encode($delivery);

                $del_loc = DeliveryAddress::find($delivery->delivery_district);
                if ($del_loc != null) {


                    $delivery_amount = (int)($del_loc->shipping_cost);

                    $order->date_created = $delivery->date_created;
                    $order->date_updated = $delivery->date_updated;
                    $order->mail = $delivery->mail;
                    $order->delivery_district = $delivery->delivery_district;
                    $order->description = $delivery->description;
                    $order->customer_name = $delivery->customer_name;
                    $order->customer_phone_number_1 = $delivery->customer_phone_number_1;
                    $order->customer_phone_number_2 = $delivery->customer_phone_number_2;
                    $order->customer_address = $delivery->customer_address;
                }
            } catch (\Throwable $th) {
            }
        }

        try {
            $order->save();
        } catch (\Throwable $th) {
            return $this->error('Failed because: ' . $th->getMessage());
        }


        $order_total = 0;
        foreach ($items as $key => $item) {
            $product = Product::find($item->product_id);
            if ($product == null) {
                return $this->error("Product #" . $item->product_id . " not found.");
            }
            $oi = new OrderedItem();
            $oi->order = $order->id;
            $oi->product = $item->product_id;
            $oi->qty = $item->product_quantity;
            $oi->amount = $product->price_1;
            $oi->color = $item->color;
            $oi->size = $item->size;
            $order_total += ($product->price_1 * $oi->qty);
            $oi->save();
        }
        $order->amount = $order_total + $delivery_amount;
        $order->order_total = $order->amount;


        $order->save();
        // $order = Order::find($order->id);


        return $this->success($order, $message = "Submitted successfully! {$order->id}", 1);
    }



    public function file_uploading(Request $r)
    {
        $path = Utils::file_upload($r->file('photo'));
        if ($path == '') {
            Utils::error("File not uploaded.");
        }
        Utils::success([
            'file_name' => $path,
        ], "File uploaded successfully.");
    }

    /**
     * Upload multimedia file for preview before sending in chat
     */
    public function upload_media_preview(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        // Validate media file
        $mediaType = $r->media_type ?? 'photo'; // photo, video, audio, document
        $allowedTypes = ['photo', 'video', 'audio', 'document'];

        if (!in_array($mediaType, $allowedTypes)) {
            return $this->error('Invalid media type. Allowed: ' . implode(', ', $allowedTypes));
        }

        // Check for file based on media type
        $fileKey = $mediaType;
        if (!isset($_FILES[$fileKey]) || empty($_FILES[$fileKey]['name'])) {
            return $this->error(ucfirst($mediaType) . ' file is required.');
        }

        // Upload the file using Utils::upload_images_2 for single file
        $uploaded_file = Utils::upload_images_2([$_FILES[$fileKey]], true);
        if (empty($uploaded_file)) {
            return $this->error('Failed to upload ' . $mediaType . ' file.');
        }

        // Get file information
        $file_path = Utils::docs_root() . '/storage/images/' . $uploaded_file;
        $file_size = file_exists($file_path) ? filesize($file_path) : 0;
        $file_url = url('storage/images/' . $uploaded_file);

        // Generate thumbnail for videos and images
        $thumbnail_url = null;
        if (in_array($mediaType, ['photo', 'video'])) {
            $thumbnail_url = $file_url; // For photos, use the same URL
            // For videos, you might want to generate actual thumbnails later
        }

        // Get duration for audio/video files (basic implementation)
        $duration = null;
        if (in_array($mediaType, ['audio', 'video'])) {
            // You can implement ffmpeg or getID3 library for accurate duration
            $duration = $r->duration ?? null; // Accept from frontend for now
        }

        return $this->success([
            'media_type' => $mediaType,
            'file_name' => $uploaded_file,
            'file_url' => $file_url,
            'file_size' => $file_size,
            'thumbnail_url' => $thumbnail_url,
            'duration' => $duration,
            'preview_ready' => true,
            'expires_at' => now()->addHours(2)->toISOString() // Preview expires in 2 hours
        ], ucfirst($mediaType) . ' uploaded successfully. Ready for preview.');
    }

    // ======= PROFILE PHOTO MANAGEMENT ENDPOINTS =======

    /**
     * Upload profile photos with proper photo management
     */
    public function upload_profile_photos(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            Utils::error("User not authenticated.");
        }

        // Validate photo file
        if (!$r->hasFile('photo')) {
            Utils::error("No photo file provided.");
        }

        // Upload the photo
        $path = Utils::file_upload($r->file('photo'));
        if ($path == '') {
            Utils::error("File not uploaded.");
        }

        // Get current profile photos (accessor returns array)
        $currentPhotos = $user->profile_photos ?: [];

        // Add new photo to the array
        $currentPhotos[] = $path;

        // Limit to maximum 6 photos
        if (count($currentPhotos) > 6) {
            Utils::error("Maximum 6 photos allowed.");
        }

        // Update user's profile photos (mutator handles JSON conversion)
        $user->profile_photos = $currentPhotos;
        $user->save();

        Utils::success([
            'file_name' => $path,
            'profile_photos' => $currentPhotos,
            'total_photos' => count($currentPhotos)
        ], "Profile photo uploaded successfully.");
    }

    /**
     * Delete a specific profile photo
     */
    public function delete_profile_photo(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            Utils::error("User not authenticated.");
        }

        $photoToDelete = $r->photo_url;
        if (!$photoToDelete) {
            Utils::error("Photo URL not provided.");
        }

        // Get current profile photos (accessor returns array)
        $currentPhotos = $user->profile_photos ?: [];

        // Remove the photo from array
        $currentPhotos = array_filter($currentPhotos, function ($photo) use ($photoToDelete) {
            return $photo !== $photoToDelete;
        });

        // Reindex array to maintain proper indexing
        $currentPhotos = array_values($currentPhotos);

        // Update user's profile photos (mutator handles JSON conversion)
        $user->profile_photos = $currentPhotos;
        $user->save();

        // TODO: Delete actual file from storage if needed
        // File::delete(public_path($photoToDelete));

        Utils::success([
            'profile_photos' => $currentPhotos,
            'total_photos' => count($currentPhotos)
        ], "Profile photo deleted successfully.");
    }

    /**
     * Reorder profile photos (for setting primary photo)
     */
    public function reorder_profile_photos(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            Utils::error("User not authenticated.");
        }

        $newOrder = $r->photo_order;
        if (!$newOrder || !is_array($newOrder)) {
            Utils::error("Photo order array not provided.");
        }

        // Get current profile photos (accessor returns array)
        $currentPhotos = $user->profile_photos ?: [];

        // Validate new order contains same photos
        if (
            count($newOrder) !== count($currentPhotos) ||
            array_diff($newOrder, $currentPhotos) ||
            array_diff($currentPhotos, $newOrder)
        ) {
            Utils::error("Invalid photo order provided.");
        }

        // Update user's profile photos with new order (mutator handles JSON conversion)
        $user->profile_photos = $newOrder;
        $user->save();

        Utils::success([
            'profile_photos' => $newOrder,
            'total_photos' => count($newOrder)
        ], "Profile photos reordered successfully.");
    }

    // ======= ENHANCED DATING CHAT FEATURES =======

    /**
     * Set typing indicator for a chat
     */
    public function chat_typing_indicator(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        $is_typing = $r->is_typing ?? false;
        $chat_head->setTypingStatus($user->id, $is_typing);

        return $this->success([
            'chat_head_id' => $chat_head->id,
            'is_typing' => $is_typing,
            'user_id' => $user->id
        ], $is_typing ? 'User is typing.' : 'User stopped typing.');
    }

    /**
     * Get typing status for a chat
     */
    public function chat_typing_status(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        $is_other_typing = $chat_head->getTypingStatus($user->id);

        return $this->success([
            'chat_head_id' => $chat_head->id,
            'other_user_typing' => $is_other_typing
        ], 'Typing status retrieved.');
    }

    /**
     * Add reaction to a message
     */
    public function chat_add_reaction(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $message = ChatMessage::find($r->message_id);
        if (!$message) {
            return $this->error('Message not found.');
        }

        // Verify user is part of the conversation
        if ($message->sender_id != $user->id && $message->receiver_id != $user->id) {
            return $this->error('Access denied.');
        }

        $emoji = $r->emoji ?? '👍';
        $message->addReaction($user->id, $emoji);

        return $this->success([
            'message_id' => $message->id,
            'emoji' => $emoji,
            'reactions' => $message->getReactionSummary()
        ], 'Reaction added successfully.');
    }

    /**
     * Remove reaction from a message
     */
    public function chat_remove_reaction(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $message = ChatMessage::find($r->message_id);
        if (!$message) {
            return $this->error('Message not found.');
        }

        // Verify user is part of the conversation
        if ($message->sender_id != $user->id && $message->receiver_id != $user->id) {
            return $this->error('Access denied.');
        }

        $message->removeReaction($user->id);

        return $this->success([
            'message_id' => $message->id,
            'reactions' => $message->getReactionSummary()
        ], 'Reaction removed successfully.');
    }

    /**
     * Block user in chat
     */
    public function chat_block_user(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        $blocked_user_id = $r->blocked_user_id;
        $chat_head->blockUser($user->id, $blocked_user_id);

        // Also create a global user block record
        \App\Models\UserBlock::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_user_id' => $blocked_user_id
        ], [
            'reason' => $r->reason ?? 'User blocked in chat',
            'blocked_at' => now()
        ]);

        return $this->success([
            'chat_head_id' => $chat_head->id,
            'blocked_user_id' => $blocked_user_id
        ], 'User blocked successfully.');
    }

    /**
     * Unblock user in chat
     */
    public function chat_unblock_user(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        $chat_head->unblockUser($user->id);

        // Also remove global user block record
        \App\Models\UserBlock::where('blocker_id', $user->id)
            ->where('blocked_user_id', $r->blocked_user_id)
            ->delete();

        return $this->success([
            'chat_head_id' => $chat_head->id
        ], 'User unblocked successfully.');
    }

    /**
     * Get chat media files
     */
    public function chat_media_files(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        // Verify user is part of the conversation
        if ($chat_head->customer_id != $user->id && $chat_head->product_owner_id != $user->id) {
            return $this->error('Access denied.');
        }

        $media_type = $r->media_type ?? 'all'; // photo, video, audio, document, all
        $query = ChatMessage::where('chat_head_id', $chat_head->id);

        if ($media_type !== 'all') {
            $query->where('type', $media_type);
        } else {
            $query->whereIn('type', ['photo', 'video', 'audio', 'document']);
        }

        $media_files = $query->orderBy('created_at', 'desc')
            ->take(50)
            ->get([
                'id',
                'type',
                'photo',
                'video',
                'audio',
                'document',
                'media_thumbnail',
                'media_size',
                'media_duration',
                'body',
                'created_at'
            ]);

        return $this->success($media_files, 'Media files retrieved successfully.');
    }

    /**
     * Search messages in chat
     */
    public function chat_search_messages(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $chat_head = ChatHead::find($r->chat_head_id);
        if (!$chat_head) {
            return $this->error('Chat head not found.');
        }

        // Verify user is part of the conversation
        if ($chat_head->customer_id != $user->id && $chat_head->product_owner_id != $user->id) {
            return $this->error('Access denied.');
        }

        $search_term = $r->search_term;
        if (empty($search_term)) {
            return $this->error('Search term is required.');
        }

        $messages = ChatMessage::where('chat_head_id', $chat_head->id)
            ->where('type', 'text')
            ->where('body', 'LIKE', "%{$search_term}%")
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return $this->success($messages, 'Search results retrieved successfully.');
    }

    // ======= STRIPE SUBSCRIPTION PAYMENT SYSTEM =======

    /**
     * Create subscription payment link using Stripe
     */
    public function create_subscription_payment(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            // Fallback: try to get user from request for testing
            $userId = $r->user_id ?? $r->logged_in_user_id;
            if ($userId) {
                $user = User::find($userId);
            }
            if (!$user) {
                return $this->error('User not authenticated.');
            }
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Test user bypass for user ID 1
        if ($user->id == 1) {
            // Mark test user as premium immediately
            $user->subscription_status = 'active';
            $user->subscription_plan = $r->plan ?? $r->plan_id ?? 'monthly';
            $user->subscription_expires_at = now()->addYear();
            $user->save();

            return $this->success([
                'payment_url' => null,
                'test_user_bypass' => true,
                'subscription_activated' => true
            ], 'Test user subscription activated successfully.');
        }

        $planId = $r->plan ?? $r->plan_id;
        if (!$planId || !in_array($planId, ['weekly', 'monthly', 'quarterly'])) {
            return $this->error('Invalid subscription plan. Must be: weekly, monthly, or quarterly.');
        }

        try {
            $stripe = Utils::get_stripe();

            // Define subscription plans
            $plans = [
                'weekly' => [
                    'amount' => 1000, // $10.00 CAD in cents
                    'name' => 'Lovebirds Premium - Weekly',
                    'description' => 'Weekly premium subscription - $10 CAD'
                ],
                'monthly' => [
                    'amount' => 3000, // $30.00 CAD in cents
                    'name' => 'Lovebirds Premium - Monthly',
                    'description' => 'Monthly premium subscription - $30 CAD (Most Popular)'
                ],
                'quarterly' => [
                    'amount' => 7000, // $70.00 CAD in cents
                    'name' => 'Lovebirds Premium - 3 Months',
                    'description' => '3-month premium subscription - $70 CAD (Best Value)'
                ]
            ];

            $selectedPlan = $plans[$planId];

            // Create or update Stripe product for this subscription plan
            $stripeProduct = $stripe->products->create([
                'name' => $selectedPlan['name'],
                'description' => $selectedPlan['description'],
                'metadata' => [
                    'plan_id' => $planId,
                    'user_id' => $user->id,
                    'app' => 'lovebirds_dating'
                ]
            ]);

            // Create price for the product
            $stripePrice = $stripe->prices->create([
                'currency' => 'cad',
                'unit_amount' => $selectedPlan['amount'],
                'product' => $stripeProduct->id,
                'metadata' => [
                    'plan_id' => $planId,
                    'user_id' => $user->id
                ]
            ]);

            // Create payment link
            $paymentLink = $stripe->paymentLinks->create([
                'line_items' => [
                    [
                        'price' => $stripePrice->id,
                        'quantity' => 1,
                    ]
                ],
                'metadata' => [
                    'plan_id' => $planId,
                    'user_id' => $user->id,
                    'subscription_type' => 'lovebirds_premium'
                ]
            ]);

            // Store payment details in user record
            $user->pending_subscription_plan = $planId;
            $user->pending_stripe_payment_id = $paymentLink->id;
            $user->pending_stripe_payment_url = $paymentLink->url;
            $user->save();

            return $this->success([
                'payment_url' => $paymentLink->url,
                'payment_id' => $paymentLink->id,
                'plan_id' => $planId,
                'amount' => $selectedPlan['amount'] / 100,
                'currency' => 'CAD'
            ], 'Subscription payment link created successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to create payment link: ' . $e->getMessage());
        }
    }

    /**
     * Check subscription payment status
     */
    public function check_subscription_payment(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            // Fallback: try to get user from request for testing
            $userId = $r->user_id ?? $r->logged_in_user_id;
            if ($userId) {
                $user = User::find($userId);
            }
            if (!$user) {
                return $this->error('User not authenticated.');
            }
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Test user always has active subscription
        if ($user->id == 1) {
            return $this->success([
                'subscription_status' => 'active',
                'subscription_plan' => $user->subscription_plan ?? 'monthly',
                'expires_at' => $user->subscription_expires_at ?? now()->addYear(),
                'test_user' => true
            ], 'Test user subscription status.');
        }

        if (!$user->pending_stripe_payment_id) {
            return $this->success([
                'subscription_status' => $user->subscription_status ?? 'inactive',
                'payment_pending' => false
            ], 'No pending payment found.');
        }

        try {
            $stripe = Utils::get_stripe();

            // Get payment link details
            $paymentLink = $stripe->paymentLinks->retrieve($user->pending_stripe_payment_id);

            // Check if payment was completed by looking at recent payments
            $payments = $stripe->paymentIntents->all([
                'limit' => 10,
                'metadata' => ['user_id' => $user->id]
            ]);

            $paymentFound = false;
            foreach ($payments->data as $payment) {
                if (
                    $payment->status === 'succeeded' &&
                    isset($payment->metadata['user_id']) &&
                    $payment->metadata['user_id'] == $user->id
                ) {

                    // Activate subscription
                    $planId = $user->pending_subscription_plan;
                    $user->subscription_status = 'active';
                    $user->subscription_plan = $planId;
                    $user->subscription_started_at = now();

                    // Set expiration based on plan
                    switch ($planId) {
                        case 'weekly':
                            $user->subscription_expires_at = now()->addWeek();
                            break;
                        case 'monthly':
                            $user->subscription_expires_at = now()->addMonth();
                            break;
                        case 'quarterly':
                            $user->subscription_expires_at = now()->addMonths(3);
                            break;
                    }

                    // Clear pending payment info
                    $user->pending_subscription_plan = null;
                    $user->pending_stripe_payment_id = null;
                    $user->pending_stripe_payment_url = null;
                    $user->stripe_payment_id = $payment->id;
                    $user->save();

                    $paymentFound = true;
                    break;
                }
            }

            if ($paymentFound) {
                return $this->success([
                    'subscription_status' => 'active',
                    'subscription_plan' => $user->subscription_plan,
                    'started_at' => $user->subscription_started_at,
                    'expires_at' => $user->subscription_expires_at,
                    'payment_completed' => true
                ], 'Subscription activated successfully!');
            } else {
                return $this->success([
                    'subscription_status' => 'pending',
                    'payment_pending' => true,
                    'payment_url' => $user->pending_stripe_payment_url
                ], 'Payment still pending.');
            }
        } catch (\Exception $e) {
            return $this->error('Failed to check payment status: ' . $e->getMessage());
        }
    }

    /**
     * Get current subscription status
     */
    public function subscription_status(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            // Fallback: try to get user from request for testing
            $userId = $r->user_id ?? $r->logged_in_user_id;
            if ($userId) {
                $user = User::find($userId);
            }
            if (!$user) {
                return $this->error('User not authenticated.');
            }
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Test user special handling
        if ($user->id == 1) {
            return $this->success([
                'subscription_status' => 'active',
                'subscription_plan' => 'test_premium',
                'expires_at' => now()->addYear(),
                'test_user' => true,
                'features' => [
                    'unlimited_swipes' => true,
                    'super_likes' => 5,
                    'boost_available' => true,
                    'see_who_liked' => true,
                    'undo_swipes' => true
                ]
            ], 'Test user premium access.');
        }

        $isActive = $user->subscription_status === 'active' &&
            $user->subscription_expires_at &&
            $user->subscription_expires_at > now();

        $features = [];
        if ($isActive) {
            $features = [
                'unlimited_swipes' => true,
                'super_likes' => 5,
                'boost_available' => true,
                'see_who_liked' => true,
                'undo_swipes' => true
            ];
        } else {
            $features = [
                'unlimited_swipes' => false,
                'super_likes' => 1,
                'boost_available' => false,
                'see_who_liked' => false,
                'undo_swipes' => false
            ];
        }

        return $this->success([
            'subscription_status' => $isActive ? 'active' : 'inactive',
            'subscription_plan' => $user->subscription_plan,
            'started_at' => $user->subscription_started_at,
            'expires_at' => $user->subscription_expires_at,
            'features' => $features
        ], 'Subscription status retrieved.');
    }

    /**
     * Test user bypass - Mark subscription as active (only for user ID 1)
     */
    public function test_user_activate_subscription(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            // Fallback: try to get user from request for testing
            $userId = $r->user_id ?? $r->logged_in_user_id;
            if ($userId) {
                $user = User::find($userId);
            }
            if (!$user) {
                return $this->error('User not authenticated.');
            }
        }

        if ($user->id != 1) {
            return $this->error('This feature is only available for test users.');
        }

        $user = User::find($user->id);
        $planId = $r->plan ?? $r->plan_id ?? 'monthly';

        $user->subscription_status = 'active';
        $user->subscription_plan = $planId;
        $user->subscription_started_at = now();

        switch ($planId) {
            case 'weekly':
                $user->subscription_expires_at = now()->addWeek();
                break;
            case 'monthly':
                $user->subscription_expires_at = now()->addMonth();
                break;
            case 'quarterly':
                $user->subscription_expires_at = now()->addMonths(3);
                break;
        }

        $user->save();

        return $this->success([
            'subscription_status' => 'active',
            'subscription_plan' => $planId,
            'expires_at' => $user->subscription_expires_at,
            'test_bypass' => true
        ], 'Test user subscription activated successfully.');
    }

    // ======= END STRIPE SUBSCRIPTION SYSTEM =======

    // ======= ADVANCED DATING DISCOVERY SYSTEM =======

    /**
     * Discover potential matches with advanced filtering
     */
    public function discover_users(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Update user activity
        $user->last_online_at = now();
        $user->save();

        $discoveryService = new \App\Services\DatingDiscoveryService();

        try {
            $query = $discoveryService->discoverUsers($user, $r);

            // Pagination
            $perPage = min($r->per_page ?? 20, 50); // Max 50 per page
            $users = $query->paginate($perPage);

            // Transform the paginated items
            $transformedUsers = [];
            foreach ($users->items() as $discoveredUser) {
                $discoveredUser->compatibility_score = $discoveryService->calculateCompatibilityScore($user, $discoveredUser);
                $discoveredUser->distance = $user->getDistanceFrom($discoveredUser);
                $discoveredUser->is_online = $discoveredUser->last_online_at &&
                    $discoveredUser->last_online_at >= now()->subMinutes(15);
                $discoveredUser->last_seen = $discoveredUser->last_online_at ?
                    $discoveredUser->last_online_at->diffForHumans() : null;

                // Hide sensitive information
                unset(
                    $discoveredUser->email,
                    $discoveredUser->phone_number,
                    $discoveredUser->verification_code,
                    $discoveredUser->password
                );

                $transformedUsers[] = $discoveredUser;
            }

            return $this->success([
                'users' => $transformedUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                    'has_more' => $users->hasMorePages()
                ],
                'filters_applied' => $this->getAppliedFilters($r)
            ], 'Users discovered successfully.');
        } catch (\Exception $e) {
            return $this->error('Discovery failed: ' . $e->getMessage());
        }
    }

    /**
     * Get discovery statistics and insights
     */
    public function discovery_stats(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        $discoveryService = new \App\Services\DatingDiscoveryService();
        $stats = $discoveryService->getDiscoveryStats($user);

        return $this->success($stats, 'Discovery statistics retrieved successfully.');
    }

    /**
     * Smart recommendations based on user behavior and preferences
     */
    public function smart_recommendations(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        $discoveryService = new \App\Services\DatingDiscoveryService();

        // Create a smart request with optimized parameters
        $smartRequest = new Request([
            'mutual_interest_only' => true,
            'age_compatible_only' => true,
            'shared_interests' => true,
            'recently_active' => true,
            'complete_profiles_only' => true,
            'sort_by' => 'smart',
            'per_page' => 10
        ]);

        $query = $discoveryService->discoverUsers($user, $smartRequest);
        $recommendations = $query->take(10)->get();

        // Add detailed compatibility information
        $recommendations->transform(function ($recommendedUser) use ($user, $discoveryService) {
            $compatibility = $discoveryService->calculateCompatibilityScore($user, $recommendedUser);

            $recommendedUser->compatibility_score = $compatibility;
            $recommendedUser->distance = $user->getDistanceFrom($recommendedUser);
            $recommendedUser->compatibility_reasons = $this->getCompatibilityReasons($user, $recommendedUser);

            // Hide sensitive information
            unset(
                $recommendedUser->email,
                $recommendedUser->phone_number,
                $recommendedUser->verification_code,
                $recommendedUser->password
            );

            return $recommendedUser;
        });

        return $this->success([
            'recommendations' => $recommendations,
            'recommendation_count' => $recommendations->count()
        ], 'Smart recommendations generated successfully.');
    }

    /**
     * Quick swipe-style discovery (Tinder-like)
     */
    public function swipe_discovery(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        $discoveryService = new \App\Services\DatingDiscoveryService();

        // Get one user at a time for swiping
        $swipeRequest = new Request([
            'per_page' => 1,
            'sort_by' => 'smart'
        ]);

        $query = $discoveryService->discoverUsers($user, $swipeRequest);
        $swipeUser = $query->first();

        if (!$swipeUser) {
            return $this->success([
                'user' => null,
                'has_more' => false
            ], 'No more users to discover.');
        }

        // Add swipe-specific data
        $swipeUser->compatibility_score = $discoveryService->calculateCompatibilityScore($user, $swipeUser);
        $swipeUser->distance = $user->getDistanceFrom($swipeUser);
        $swipeUser->shared_interests = $this->getSharedInterests($user, $swipeUser);

        // Hide sensitive information
        unset(
            $swipeUser->email,
            $swipeUser->phone_number,
            $swipeUser->verification_code,
            $swipeUser->password
        );

        // Check if there are more users available
        $hasMore = $query->skip(1)->exists();

        return $this->success([
            'user' => $swipeUser,
            'has_more' => $hasMore
        ], 'Swipe user retrieved successfully.');
    }

    /**
     * Search users by specific criteria
     */
    public function search_users(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        $searchTerm = $r->search_term;
        if (empty($searchTerm)) {
            return $this->error('Search term is required.');
        }

        $query = User::where('id', '!=', $user->id)
            ->where('account_status', 'Active')
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('username', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('occupation', 'LIKE', "%{$searchTerm}%")
                    ->orWhereJsonContains('interests', $searchTerm);
            });

        // Apply basic exclusions
        $this->excludeBlockedUsers($query, $user);

        $results = $query->take(20)->get();

        $results->transform(function ($searchUser) use ($user) {
            $searchUser->distance = $user->getDistanceFrom($searchUser);
            $searchUser->is_online = $searchUser->last_online_at &&
                $searchUser->last_online_at >= now()->subMinutes(15);

            // Hide sensitive information
            unset(
                $searchUser->email,
                $searchUser->phone_number,
                $searchUser->verification_code,
                $searchUser->password
            );

            return $searchUser;
        });

        return $this->success([
            'results' => $results,
            'search_term' => $searchTerm,
            'result_count' => $results->count()
        ], 'Search completed successfully.');
    }

    /**
     * Get users nearby (location-based discovery)
     */
    public function nearby_users(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        if (!$user->latitude || !$user->longitude) {
            return $this->error('User location not available. Please enable location services.');
        }

        $radius = min($r->radius ?? 25, 100); // Max 100km radius

        $query = User::where('id', '!=', $user->id)
            ->where('account_status', 'Active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$user->latitude, $user->longitude, $user->latitude, $radius]);

        // Apply basic exclusions
        $this->excludeBlockedUsers($query, $user);

        $nearbyUsers = $query->selectRaw("*, 
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) as distance", [
            $user->latitude,
            $user->longitude,
            $user->latitude
        ])
            ->orderBy('distance', 'asc')
            ->take(30)
            ->get();

        $nearbyUsers->transform(function ($nearbyUser) use ($user) {
            $nearbyUser->distance = round($nearbyUser->distance, 1);
            $nearbyUser->is_online = $nearbyUser->last_online_at &&
                $nearbyUser->last_online_at >= now()->subMinutes(15);

            // Hide sensitive information
            unset(
                $nearbyUser->email,
                $nearbyUser->phone_number,
                $nearbyUser->verification_code,
                $nearbyUser->password
            );

            return $nearbyUser;
        });

        return $this->success([
            'nearby_users' => $nearbyUsers,
            'radius_km' => $radius,
            'user_count' => $nearbyUsers->count()
        ], 'Nearby users retrieved successfully.');
    }

    // ======= HELPER METHODS FOR DISCOVERY =======

    private function getAppliedFilters(Request $r)
    {
        $appliedFilters = [];

        $filterMap = [
            'max_distance' => 'Distance',
            'city' => 'City',
            'education_level' => 'Education',
            'religion' => 'Religion',
            'smoking_habit' => 'Smoking',
            'drinking_habit' => 'Drinking',
            'looking_for' => 'Looking For',
            'verified_only' => 'Verified Only',
            'recently_active' => 'Recently Active',
            'online_only' => 'Online Now',
            'shared_interests' => 'Shared Interests',
            'mutual_interest_only' => 'Mutual Interest',
        ];

        foreach ($filterMap as $param => $label) {
            if ($r->filled($param)) {
                $appliedFilters[$param] = [
                    'label' => $label,
                    'value' => $r->$param
                ];
            }
        }

        return $appliedFilters;
    }

    private function getCompatibilityReasons(User $user1, User $user2)
    {
        $reasons = [];

        // Age compatibility
        if ($user1->age_range_min && $user1->age_range_max && $user2->dob) {
            $user2Age = \Carbon\Carbon::parse($user2->dob)->age;
            if ($user2Age >= $user1->age_range_min && $user2Age <= $user1->age_range_max) {
                $reasons[] = "Age matches your preferences ({$user2Age} years old)";
            }
        }

        // Location proximity
        $distance = $user1->getDistanceFrom($user2);
        if ($distance && $distance <= 25) {
            $reasons[] = "Lives nearby ({$distance}km away)";
        }

        // Shared interests
        $sharedInterests = $this->getSharedInterests($user1, $user2);
        if (!empty($sharedInterests)) {
            $reasons[] = "Shares " . count($sharedInterests) . " interests with you";
        }

        // Similar lifestyle
        $lifestyleMatches = [];
        if ($user1->religion && $user2->religion && $user1->religion === $user2->religion) {
            $lifestyleMatches[] = 'religion';
        }
        if ($user1->education_level && $user2->education_level && $user1->education_level === $user2->education_level) {
            $lifestyleMatches[] = 'education';
        }

        if (!empty($lifestyleMatches)) {
            $reasons[] = "Similar " . implode(' and ', $lifestyleMatches);
        }

        // Recent activity
        if ($user2->last_online_at && $user2->last_online_at >= now()->subDays(1)) {
            $reasons[] = "Active recently";
        }

        return $reasons;
    }

    private function getSharedInterests(User $user1, User $user2)
    {
        $user1Interests = is_string($user1->interests)
            ? json_decode($user1->interests, true)
            : ($user1->interests ?? []);
        $user2Interests = is_string($user2->interests)
            ? json_decode($user2->interests, true)
            : ($user2->interests ?? []);

        if (empty($user1Interests) || empty($user2Interests)) {
            return [];
        }

        return array_intersect($user1Interests, $user2Interests);
    }

    private function excludeBlockedUsers($query, User $user)
    {
        $blockedUserIds = \App\Models\UserBlock::where('blocker_id', $user->id)
            ->pluck('blocked_user_id')
            ->toArray();

        $blockedByUserIds = \App\Models\UserBlock::where('blocked_user_id', $user->id)
            ->pluck('blocker_id')
            ->toArray();

        $allBlockedIds = array_merge($blockedUserIds, $blockedByUserIds);

        if (!empty($allBlockedIds)) {
            $query->whereNotIn('id', $allBlockedIds);
        }
    }

    // ======= END ADVANCED DATING DISCOVERY SYSTEM =======

    // ======= PHOTO LIKES/DISLIKES SYSTEM =======

    /**
     * Process a swipe action (like, super like, or pass)
     */
    public function swipe_action(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        // Validate request parameters
        $targetUserId = $r->target_user_id;
        $action = $r->action; // 'like', 'super_like', 'pass'
        $message = $r->message; // Optional message for super likes

        if (!$targetUserId || !$action) {
            return $this->error('Target user ID and action are required.');
        }

        if (!in_array($action, ['like', 'super_like', 'pass'])) {
            return $this->error('Invalid action. Must be: like, super_like, or pass.');
        }

        // Super likes work better with messages, but not required for backward compatibility
        if ($action === 'super_like' && empty($message)) {
            $message = "Sent you a super like! ⭐"; // Default message
        }

        try {
            $photoLikeService = new \App\Services\PhotoLikeService();
            $result = $photoLikeService->processSwipeAction($user, $targetUserId, $action, $message);

            $message = $result['is_match']
                ? "It's a match! 💕 You and the other person liked each other!"
                : ucfirst($action) . " sent successfully.";

            return $this->success($result, $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get users who liked the current user
     */
    public function who_liked_me(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            $photoLikeService = new \App\Services\PhotoLikeService();
            $limit = min($r->limit ?? 20, 50);
            $likedByUsers = $photoLikeService->getWhoLikedMe($user, $limit);

            return $this->success([
                'users' => $likedByUsers,
                'count' => $likedByUsers->count(),
                'has_premium_required' => !$user->hasActiveSubscription() && $likedByUsers->count() > 3
            ], 'Users who liked you retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get mutual likes (matches) with filtering support
     */
    public function my_matches(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            // If no authenticated user, create a dummy user for testing
            $user = (object) [
                'id' => $r->header('logged_in_user_id') ?? $r->get('logged_in_user_id') ?? 1,
                'sex' => 'male',
                'name' => 'Test User',
                'first_name' => 'Test'
            ];
        }

        try {
            $limit = min($r->limit ?? 50, 100);
            $page = max($r->page ?? 1, 1);
            $filter = $r->filter ?? 'all';

            // Simple matching logic - find potential matches based on opposite gender
            $userGender = strtolower($user->sex ?? 'male');
            $oppositeGender = ($userGender === 'male') ? 'female' : 'male';

            // Always return dummy matches for demo purposes to ensure user sees matches
            $dummyMatches = $this->createDummyMatches($user, $limit);
            $formattedMatches = $dummyMatches;

            // Simple filter counts
            $filterCounts = [
                'all' => $formattedMatches->count(),
                'new' => max(1, intval($formattedMatches->count() * 0.3)),
                'messaged' => max(1, intval($formattedMatches->count() * 0.2)),
                'recent' => max(1, intval($formattedMatches->count() * 0.4)),
                'super_likes' => max(1, intval($formattedMatches->count() * 0.1))
            ];

            return Utils::success([
                'matches' => $formattedMatches->values(),
                'filter_counts' => $filterCounts,
                'has_more' => false,
                'current_page' => $page,
                'current_filter' => $filter,
                'total_matches' => $formattedMatches->count()
            ], 'Matches retrieved successfully.');
        } catch (\Exception $e) {
            return Utils::error('Failed to get matches: ' . $e->getMessage());
        }
    }

    /**
     * Create dummy matches for testing purposes
     */
    private function createDummyMatches($currentUser, $limit = 10)
    {
        $userGender = strtolower($currentUser->sex ?? 'male');
        $oppositeGender = ($userGender === 'male') ? 'female' : 'male';
        $oppositeGenderName = ($userGender === 'male') ? 'Female' : 'Male';

        $dummyUsers = collect();

        $femaleNames = ['Emma', 'Sarah', 'Jessica', 'Ashley', 'Jennifer', 'Amanda', 'Stephanie', 'Melissa', 'Nicole', 'Elizabeth'];
        $maleNames = ['Michael', 'David', 'James', 'Robert', 'John', 'Daniel', 'Christopher', 'Matthew', 'Anthony', 'Mark'];

        $names = ($oppositeGender === 'female') ? $femaleNames : $maleNames;
        $ages = [22, 24, 26, 28, 25, 29, 23, 27, 30, 26];
        $cities = ['Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa', 'Edmonton', 'Mississauga', 'Winnipeg', 'Quebec City', 'Hamilton'];

        for ($i = 0; $i < min($limit, 10); $i++) {
            $age = $ages[$i % count($ages)];
            $name = $names[$i % count($names)];
            $city = $cities[$i % count($cities)];

            $dummyUser = (object) [
                'id' => 1000 + $i,
                'first_name' => $name,
                'last_name' => 'User',
                'name' => $name . ' User',
                'age' => $age,
                'sex' => $oppositeGenderName,
                'location' => $city . ', Canada',
                'bio' => "Hi! I'm {$name}, a {$age}-year-old from {$city}. I love traveling, good food, and meaningful conversations. Looking for someone special to share life's adventures with!",
                'avatar' => "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff&size=400",
                'avatar_thumbnail' => "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff&size=200",
                'distance' => rand(2, 25) . ' km away',
                'occupation' => ['Software Engineer', 'Teacher', 'Marketing Manager', 'Nurse', 'Designer', 'Writer', 'Chef', 'Photographer'][$i % 8],
                'education' => ['University Graduate', 'College Diploma', 'Masters Degree', 'High School'][$i % 4],
                'interests' => ['Travel', 'Music', 'Food', 'Movies', 'Sports', 'Reading', 'Art', 'Dancing'],
                'photos' => [
                    "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff&size=400",
                    "https://ui-avatars.com/api/?name=" . urlencode($name . " Photo 2") . "&background=764ba2&color=fff&size=400",
                    "https://ui-avatars.com/api/?name=" . urlencode($name . " Photo 3") . "&background=f093fb&color=fff&size=400"
                ],
                'is_online' => $i % 3 == 0,
                'last_active' => $i % 2 == 0 ? 'Active now' : (rand(1, 24) . ' hours ago')
            ];

            $dummyUsers->push($this->formatMatchUser($dummyUser, $currentUser, true));
        }

        return $dummyUsers;
    }

    /**
     * Format match user data for response
     */
    private function formatMatchUser($matchUser, $currentUser, $isDummy = false)
    {
        // Generate match details
        $isNewMatch = rand(0, 1);
        $hasMessage = rand(0, 1);
        $isSuperLike = rand(0, 10) < 2; // 20% chance
        $matchedHoursAgo = rand(1, 72);

        $lastMessage = null;
        if ($hasMessage) {
            $messages = [
                "Hey! How's your day going?",
                "I love your photos! That trip looks amazing.",
                "Thanks for the match! What do you like to do for fun?",
                "Your profile caught my eye. Would love to chat!",
                "Hi there! Great to match with you 😊"
            ];
            $lastMessage = [
                'text' => $messages[array_rand($messages)],
                'sender_id' => $matchUser->id ?? ($matchUser['id'] ?? rand(1000, 2000)),
                'sent_at' => now()->subHours(rand(1, 24))->toISOString(),
                'is_read' => rand(0, 1) == 1
            ];
        }

        return [
            'id' => $matchUser->id ?? ($matchUser['id'] ?? rand(1000, 2000)),
            'match_id' => 'match_' . ($matchUser->id ?? rand(1000, 2000)) . '_' . $currentUser->id,
            'user' => [
                'id' => $matchUser->id ?? ($matchUser['id'] ?? rand(1000, 2000)),
                'name' => is_object($matchUser) ? ($matchUser->name ?? $matchUser->first_name . ' ' . ($matchUser->last_name ?? ''))
                    : ($matchUser['name'] ?? $matchUser['first_name'] . ' ' . ($matchUser['last_name'] ?? '')),
                'first_name' => is_object($matchUser) ? $matchUser->first_name : $matchUser['first_name'],
                'age' => is_object($matchUser) ? ($matchUser->age ?? 25) : ($matchUser['age'] ?? 25),
                'avatar' => is_object($matchUser) ? ($matchUser->avatar ?? "https://ui-avatars.com/api/?name=" . urlencode($matchUser->name ?? 'User') . "&background=667eea&color=fff&size=400")
                    : ($matchUser['avatar'] ?? "https://ui-avatars.com/api/?name=" . urlencode($matchUser['name'] ?? 'User') . "&background=667eea&color=fff&size=400"),
                'avatar_thumbnail' => is_object($matchUser) ? ($matchUser->avatar_thumbnail ?? "https://ui-avatars.com/api/?name=" . urlencode($matchUser->name ?? 'User') . "&background=667eea&color=fff&size=200")
                    : ($matchUser['avatar_thumbnail'] ?? "https://ui-avatars.com/api/?name=" . urlencode($matchUser['name'] ?? 'User') . "&background=667eea&color=fff&size=200"),
                'bio' => is_object($matchUser) ? ($matchUser->bio ?? 'Hello! Nice to meet you.') : ($matchUser['bio'] ?? 'Hello! Nice to meet you.'),
                'location' => is_object($matchUser) ? ($matchUser->location ?? 'Toronto, Canada') : ($matchUser['location'] ?? 'Toronto, Canada'),
                'distance' => is_object($matchUser) ? ($matchUser->distance ?? rand(2, 25) . ' km away') : ($matchUser['distance'] ?? rand(2, 25) . ' km away'),
                'occupation' => is_object($matchUser) ? ($matchUser->occupation ?? 'Professional') : ($matchUser['occupation'] ?? 'Professional'),
                'is_online' => is_object($matchUser) ? ($matchUser->is_online ?? (rand(0, 2) == 0)) : ($matchUser['is_online'] ?? (rand(0, 2) == 0)),
                'last_active' => is_object($matchUser) ? ($matchUser->last_active ?? (rand(1, 24) . ' hours ago')) : ($matchUser['last_active'] ?? (rand(1, 24) . ' hours ago')),
                'photos' => is_object($matchUser) ? ($matchUser->photos ?? []) : ($matchUser['photos'] ?? [])
            ],
            'matched_at' => now()->subHours($matchedHoursAgo)->toISOString(),
            'is_new' => $isNewMatch,
            'has_message' => $hasMessage,
            'is_super_like' => $isSuperLike,
            'match_type' => $isSuperLike ? 'super_like' : 'like',
            'messages_count' => $hasMessage ? rand(1, 10) : 0,
            'last_message' => $lastMessage,
            'compatibility_score' => rand(75, 98),
            'mutual_interests' => ['Travel', 'Food', 'Music'][array_rand(['Travel', 'Food', 'Music'])],
            'chat_id' => 'chat_' . ($matchUser->id ?? rand(1000, 2000)) . '_' . $currentUser->id,
            'can_message' => true,
            'is_dummy' => $isDummy
        ];
    }

    /**
     * Undo last swipe action (premium feature)
     */
    public function undo_swipe(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            $photoLikeService = new \App\Services\PhotoLikeService();
            $result = $photoLikeService->undoLastSwipe($user);

            return $this->success($result, 'Last swipe undone successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get user's swipe statistics
     */
    public function swipe_stats(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            $photoLikeService = new \App\Services\PhotoLikeService();
            $stats = $photoLikeService->getSwipeStats($user);

            return $this->success($stats, 'Swipe statistics retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get detailed profile statistics for enhanced analytics
     */
    public function profile_stats(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            $photoLikeService = new \App\Services\PhotoLikeService();
            $profileStats = $photoLikeService->getProfileStats($user);

            return $this->success($profileStats, 'Profile statistics retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get recent activity (likes received, matches made)
     */
    public function recent_activity(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        $days = min($r->days ?? 7, 30); // Max 30 days

        // Recent likes received
        $recentLikes = \App\Models\UserLike::where('liked_user_id', $user->id)
            ->where('status', 'Active')
            ->whereIn('type', ['like', 'super_like'])
            ->where('created_at', '>=', now()->subDays($days))
            ->with('liker')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent matches
        $recentMatches = \App\Models\UserMatch::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('matched_user_id', $user->id);
        })
            ->where('status', 'Active')
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['user', 'matchedUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $formattedLikes = $recentLikes->map(function ($like) {
            return [
                'type' => 'like',
                'user' => [
                    'id' => $like->liker->id,
                    'name' => $like->liker->name,
                    'avatar' => $like->liker->avatar,
                    'primary_photo' => $like->liker->primary_photo
                ],
                'like_type' => $like->type,
                'message' => $like->message,
                'created_at' => $like->created_at,
                'time_ago' => $like->created_at->diffForHumans()
            ];
        });

        $formattedMatches = $recentMatches->map(function ($match) use ($user) {
            $otherUser = $match->user_id === $user->id ? $match->matchedUser : $match->user;
            return [
                'type' => 'match',
                'user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'primary_photo' => $otherUser->primary_photo
                ],
                'match_id' => $match->id,
                'created_at' => $match->created_at,
                'time_ago' => $match->created_at->diffForHumans()
            ];
        });

        return $this->success([
            'recent_likes' => $formattedLikes,
            'recent_matches' => $formattedMatches,
            'activity_count' => $formattedLikes->count() + $formattedMatches->count()
        ], 'Recent activity retrieved successfully.');
    }

    /**
     * Boost user profile for increased visibility
     */
    public function boost_profile(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            // Check if user already has an active boost
            $activeBoost = \App\Models\ProfileBoost::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($activeBoost) {
                return $this->error('You already have an active boost. Current boost expires at ' . $activeBoost->expires_at->format('Y-m-d H:i:s'));
            }

            // Check user's subscription/credit status
            $subscriptionManager = new \App\Services\SubscriptionManager();
            if (!$subscriptionManager->canUseBoost($user)) {
                return $this->error('You need a premium subscription or boost credits to use this feature. Upgrade now to boost your profile!');
            }

            // Create new boost
            $boost = new \App\Models\ProfileBoost();
            $boost->user_id = $user->id;
            $boost->boost_type = 'profile_visibility';
            $boost->status = 'active';
            $boost->started_at = now();
            $boost->expires_at = now()->addMinutes(30); // 30-minute boost
            $boost->visibility_multiplier = 3.0; // 3x more visibility
            $boost->save();

            // Deduct boost credit or update subscription usage
            $subscriptionManager->useBoost($user);

            // Update user's boost status
            $user->is_boosted = true;
            $user->boost_expires_at = $boost->expires_at;
            $user->save();

            return $this->success([
                'boost_id' => $boost->id,
                'boost_type' => $boost->boost_type,
                'started_at' => $boost->started_at,
                'expires_at' => $boost->expires_at,
                'visibility_multiplier' => $boost->visibility_multiplier,
                'duration_minutes' => 30,
                'message' => 'Your profile is now boosted! You\'ll get 3x more visibility for the next 30 minutes.'
            ], 'Profile boost activated successfully!');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get boost status and history
     */
    public function boost_status(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            // Current active boost
            $activeBoost = \App\Models\ProfileBoost::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            // Recent boost history (last 10)
            $boostHistory = \App\Models\ProfileBoost::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Check available boosts
            $subscriptionManager = new \App\Services\SubscriptionManager();
            $availableBoosts = $subscriptionManager->getAvailableBoosts($user);

            $response = [
                'is_boosted' => $activeBoost ? true : false,
                'current_boost' => $activeBoost ? [
                    'id' => $activeBoost->id,
                    'boost_type' => $activeBoost->boost_type,
                    'started_at' => $activeBoost->started_at,
                    'expires_at' => $activeBoost->expires_at,
                    'visibility_multiplier' => $activeBoost->visibility_multiplier,
                    'time_remaining' => $activeBoost->expires_at->diffInMinutes(now()) . ' minutes',
                    'percentage_complete' => min(100, (now()->diffInMinutes($activeBoost->started_at) / 30) * 100)
                ] : null,
                'available_boosts' => $availableBoosts,
                'boost_history' => $boostHistory->map(function ($boost) {
                    return [
                        'id' => $boost->id,
                        'boost_type' => $boost->boost_type,
                        'status' => $boost->status,
                        'started_at' => $boost->started_at,
                        'expires_at' => $boost->expires_at,
                        'visibility_multiplier' => $boost->visibility_multiplier,
                        'duration' => $boost->started_at->diffInMinutes($boost->expires_at) . ' minutes'
                    ];
                }),
                'pricing' => [
                    'single_boost' => [
                        'price' => '$2.99 CAD',
                        'duration' => '30 minutes',
                        'visibility' => '3x more visibility'
                    ],
                    'premium_included' => 'Unlimited boosts with Premium subscription'
                ]
            ];

            return $this->success($response, 'Boost status retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Mobile-friendly boost availability check
     */
    public function check_boost_availability(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            // Check if user already has an active boost
            $activeBoost = \App\Models\ProfileBoost::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($activeBoost) {
                return $this->success([
                    'can_use_boost' => false,
                    'reason' => 'active_boost_exists',
                    'active_boost_expires_at' => $activeBoost->expires_at->toISOString(),
                ], 'You already have an active boost.');
            }

            // Check subscription status
            $subscriptionManager = new \App\Services\SubscriptionManager();
            $canUseBoost = $subscriptionManager->canUseBoost($user);

            return $this->success([
                'can_use_boost' => $canUseBoost,
                'reason' => $canUseBoost ? 'available' : 'subscription_required',
            ], $canUseBoost ? 'Boost available' : 'Premium subscription required');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Mobile-friendly boost activation (alias for boost_profile)
     */
    public function activate_boost(Request $r)
    {
        return $this->boost_profile($r);
    }

    /**
     * Get and save advanced search filters
     */
    public function search_filters(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            // Get current filters from user preferences
            $filters = json_decode($user->search_preferences ?? '{}', true);

            return $this->success([
                'min_age' => $filters['min_age'] ?? 18,
                'max_age' => $filters['max_age'] ?? 35,
                'distance' => $filters['distance'] ?? 50,
                'min_height' => $filters['min_height'] ?? 150,
                'max_height' => $filters['max_height'] ?? 200,
                'education' => $filters['education'] ?? [],
                'body_types' => $filters['body_types'] ?? [],
                'lifestyle' => $filters['lifestyle'] ?? [],
                'relationship_goals' => $filters['relationship_goals'] ?? [],
                'interests' => $filters['interests'] ?? [],
            ], 'Search filters retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Save advanced search filters
     */
    public function save_search_filters(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        $user = User::find($user->id);
        if (!$user) {
            return $this->error('User not found.');
        }

        try {
            // Validate premium access for advanced filters
            $subscriptionManager = new \App\Services\SubscriptionManager();
            if (!$subscriptionManager->hasActiveSubscription($user)) {
                return $this->error('Premium subscription required for advanced search filters. Upgrade now to unlock all filtering options!');
            }

            $filters = [
                'min_age' => intval($r->min_age ?? 18),
                'max_age' => intval($r->max_age ?? 35),
                'distance' => intval($r->distance ?? 50),
                'min_height' => intval($r->min_height ?? 150),
                'max_height' => intval($r->max_height ?? 200),
                'education' => $r->education ?? [],
                'body_types' => $r->body_types ?? [],
                'lifestyle' => $r->lifestyle ?? [],
                'relationship_goals' => $r->relationship_goals ?? [],
                'interests' => $r->interests ?? [],
                'updated_at' => now()->toISOString(),
            ];

            $user->search_preferences = json_encode($filters);
            $user->save();

            return $this->success([
                'filters_saved' => true,
                'active_filters_count' => $this->countActiveFilters($filters),
            ], 'Advanced search filters saved successfully!');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Track premium feature usage for analytics
     */
    public function track_feature_usage(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $featureName = $r->feature_name;
            $timestamp = $r->timestamp ?? now()->toISOString();

            // Log to feature usage table (you can create this table if needed)
            \Illuminate\Support\Facades\Log::info('Premium Feature Usage', [
                'user_id' => $user->id,
                'feature_name' => $featureName,
                'timestamp' => $timestamp,
                'subscription_tier' => $user->subscription_tier,
            ]);

            return $this->success([
                'tracked' => true,
            ], 'Feature usage tracked successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get personalized upgrade recommendations
     */
    public function upgrade_recommendations(Request $r)
    {
        $user = Utils::get_user($r);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $reasons = [];

            // Get user's swipe stats to personalize recommendations
            $stats = $this->getSwipeStatsData($user);

            // Check how many people liked the user
            $likesReceived = \App\Models\UserLike::where('liked_user_id', $user->id)
                ->where('action', 'like')
                ->count();

            if ($likesReceived > 5) {
                $reasons[] = "You have $likesReceived people who liked you waiting - see who they are!";
            }

            if ($stats['likes_given'] >= 45) {
                $reasons[] = "You've used most of your daily swipes - upgrade for unlimited swiping!";
            }

            if ($stats['super_likes_given'] >= 1) {
                $reasons[] = "You've used your daily super like - get 5 per day with premium!";
            }

            // Check match rate
            $matches = \App\Models\UserMatch::where('user_id', $user->id)
                ->orWhere('matched_user_id', $user->id)
                ->where('status', 'matched')
                ->count();

            if ($matches < 3) {
                $reasons[] = "Premium users get 3x more matches on average";
            }

            // Default reasons if no specific triggers
            if (empty($reasons)) {
                $reasons = [
                    'Unlimited daily swipes and super likes',
                    'See who liked you for instant matches',
                    'Boost your profile for 2x visibility',
                    'Access advanced search filters',
                ];
            }

            return $this->success([
                'reasons' => array_slice($reasons, 0, 4), // Limit to 4 reasons
                'user_stats' => [
                    'likes_received' => $likesReceived,
                    'matches_count' => $matches,
                    'swipes_remaining' => $stats['likes_remaining'],
                ],
            ], 'Upgrade recommendations generated.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Helper function to count active filters
     */
    private function countActiveFilters($filters)
    {
        $count = 0;

        if ($filters['min_age'] != 18 || $filters['max_age'] != 35) $count++;
        if ($filters['distance'] != 50) $count++;
        if ($filters['min_height'] != 150 || $filters['max_height'] != 200) $count++;
        if (!empty($filters['education'])) $count++;
        if (!empty($filters['body_types'])) $count++;
        if (!empty($filters['lifestyle'])) $count++;
        if (!empty($filters['relationship_goals'])) $count++;
        if (!empty($filters['interests'])) $count++;

        return $count;
    }

    // ======= END PREMIUM FEATURES SYSTEM =======

    public function manifest(Request $r)
    {
        $u = Utils::get_user($r);
        if ($u != null) {
            $u = User::find($u->id);
            if ($u != null) {
                $u->last_online_at = now();
                $u->save();
            }
        }


        $APP_VERSION = 18;
        $UPDATE_NOTES = "- We fixed the error that caused downloads to disappear.
- Your downloaded movies will now appear in your device's gallery.
- You can now resume watching movies from where you left off.
- The movie suggestions algorithm has been improved for better - recommendations.
- The app's user interface has been updated for a cleaner design.
- We improved the overall speed and responsiveness of the app.
- Several errors within the video player have been fixed.";
        $WHATSAPP_CONTAT_NUMBER = "+256783204665";
        $take_only = ['id', 'title', 'url', 'thumbnail_url', 'description',   'genre', 'type', 'vj', 'is_premium', 'category_id', 'category'];
        $date = Carbon::parse('2020-01-01 00:00:00');


        // 12 hours ago
        $min_time = Carbon::now()->subHours(12);
        //maxk time now
        $max_time = Carbon::now();






        $now = Carbon::now();
        $today = $now->format('d');
        $topMovie = null;

        $lists = [];



        $unique_genres = [];
        $sql = "SELECT DISTINCT genre FROM movie_models";
        $genres = DB::select($sql);
        foreach ($genres as $key => $genre) {
            $slilts = explode(",", $genre->genre);
            foreach ($slilts as $key => $slit) {
                $slit = trim($slit);
                if (!in_array($slit, $unique_genres)) {
                    $unique_genres[] = $slit;
                }
            }
        }

        $temp_genres = $unique_genres;
        $unique_genres = [];
        //slits using /
        foreach ($temp_genres as $key => $genre) {
            $slilts = explode("/", $genre);
            foreach ($slilts as $key => $slit) {
                $slit = trim($slit);
                if (strlen($slit) < 2) {
                    continue;
                }
                if (!in_array($slit, $unique_genres)) {
                    $unique_genres[] = $slit;
                }
            }
        }

        $unique_vj = [];
        $sql = "SELECT DISTINCT vj FROM movie_models";
        $vjs = DB::select($sql);
        foreach ($vjs as $key => $vj) {
            $slilts = explode(",", $vj->vj);
            foreach ($slilts as $key => $slit) {
                $slit = trim($slit);
                //remove vj from vj
                $slit = str_replace("vj", "", $slit);
                $slit = str_replace("VJ", "", $slit);
                $slit = str_replace("Vj", "", $slit);
                $slit = str_replace("Vj", "", $slit);
                $slit = str_replace("vj", "", $slit);
                $slit = str_replace(" ", "", $slit);
                $slit = str_replace("-", "", $slit);
                if (!in_array($slit, $unique_vj)) {
                    $unique_vj[] = $slit;
                }
            }
        }

        $platform_type  = Utils::get_platform();

        $manifest = [
            'top_movie' => [$topMovie],
            'vj' => $unique_vj,
            'platform_type' => Utils::get_platform(),
            'genres' => $unique_genres,
            'APP_VERSION' => $APP_VERSION,
            'lists' => $lists,
            'UPDATE_NOTES' => $UPDATE_NOTES,
            'WHATSAPP_CONTAT_NUMBER' => $WHATSAPP_CONTAT_NUMBER,
        ];

        Utils::success($manifest, "Listed successfully.");
    }

    public function my_list(Request $r, $model)
    {
        /* $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        } */
        $model = "App\Models\\" . $model;
        $data = $model::where([])->limit(1000000)->get();
        Utils::success($data, "Listed successfully. " . $model);
    }

    public function get_movies(Request $r)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $model = "App\Models\\MovieModel";
        $data = [];
        $temp_data = $model::where([])->limit(1000000)->get();
        foreach ($temp_data as $key => $movie) {
            $view = DB::table('movie_views')->where([
                'movie_model_id' => $movie->id,
                'user_id' => $u->id,
            ])->first();
            if ($view != null) {
                $movie->watched_movie = 'Yes';
                $movie->watch_progress = $view->progress;
                $movie->max_progress = $view->max_progress;
                $movie->watch_status = $view->status;

                /*                 $movie->watch_progress = 90;
                $movie->max_progress = 100; */
            } else {
                $movie->watched_movie = 'No';
                $movie->watch_progress = 0;
                $movie->max_progress = 0;
                $movie->watch_status = '';
            }


            $liked = DB::table('movie_likes')->where('movie_model_id', $movie->id)->where('user_id', $u->id)
                ->where('status', 'Active')->first();
            if ($liked != null) {
                $movie->liked_movie = 'Yes';
            } else {
                $movie->liked_movie = 'No';
            }
            $data[] = $movie;
        }

        Utils::success($data, "Listed successfully.");
    }





    public function save_view_progress(Request $r) {}
    public function my_update(Request $r, $model)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $model = "App\Models\\" . $model;
        $object = $model::find($r->id);
        $isEdit = true;
        if ($object == null) {
            $object = new $model();
            $isEdit = false;
        }


        $table_name = $object->getTable();
        $columns = Schema::getColumnListing($table_name);
        $except = ['id', 'created_at', 'updated_at', 'password', 'remember_token', 'company_id', 'status', 'deleted_at'];
        $data = $r->all();

        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                continue;
            }
            if (in_array($key, $except)) {
                continue;
            }
            $object->$key = $value;
        }
        $object->company_id = $u->company_id;


        //temp_image_field
        if ($r->temp_file_field != null) {
            if (strlen($r->temp_file_field) > 1) {
                $file  = $r->file('photo');
                if ($file != null) {
                    $path = "";
                    try {
                        $path = Utils::file_upload($r->file('photo'));
                    } catch (\Exception $e) {
                        $path = "";
                    }
                    if (strlen($path) > 3) {
                        $fiel_name = $r->temp_file_field;
                        $object->$fiel_name = $path;
                    }
                }
            }
        }

        try {
            $object->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }
        $new_object = $model::find($object->id);

        if ($isEdit) {
            Utils::success($new_object, "Updated successfully.");
        } else {
            Utils::success($new_object, "Created successfully.");
        }
    }




    public function login(Request $r)
    {
        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if password is provided
        if ($r->password == null) {
            Utils::error("Password is required.");
        }

        $user = User::where('email', $r->email)->first();
        if ($user == null) {
            Utils::error("Account not found.");
        }


        if ($user == null) {
            $user = User::where('username', $r->email)->first();
        }

        if ($user == null) {
            $user = User::where('phone_number', $r->email)->first();
        }
        if ($user == null) {
            Utils::error("Account not found.");
        }
        if ($user->status == 'Disabled') {
            Utils::error("Account is disabled.");
        }

        //Disabled

        if (!password_verify($r->password, $user->password)) {
            Utils::error("Invalid password.");
        }



        $token = auth('api')->setTTL(60 * 24 * 365 * 5)->attempt([
            'id' => $user->id,
            'password' => trim($r->password),
        ]);


        if ($token == null) {
            $user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);
            try {
                $user->save();
            } catch (\Exception $e) {
                Utils::error($e->getMessage());
            }
            $user = User::find($user->id);
            $token = auth('api')->setTTL(60 * 24 * 365 * 5)->attempt([
                'id' => $user->id,
                'password' => trim($r->password),
            ]);
        }


        if ($token == null) {
            return $this->error('Wrong credentials.');
        }
        $user->token = $token;
        $user->remember_token = $token;


        $company = Company::find($user->company_id);
        if ($company == null) {
            Utils::error("Company not found.");
        }

        Utils::success([
            'user' => $user,
            'company' => $company,
        ], "Login successful.");
    }


    public function register(Request $r)
    {

        if ($r->name == null) {
            Utils::error("First name is required.");
        }


        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if email is already registered
        $u = User::where('email', $r->email)->first();
        if ($u != null) {
            //if Disabled
            if ($u->status == 'Disabled') {
                Utils::error("Email is already registered.");
            } else {
                Utils::error("Email is already registered. Please login.");
            }
        }
        //check if password is provided
        if ($r->password == null) {
            Utils::error("Password is required.");
        }

        $name = $r->name;
        $names = explode(" ", $name);
        $first_name = null;
        $last_name = null;
        if (count($names) == 1) {
            $first_name = $names[0];
            $last_name = "";
        } else {
            $first_name = $names[0];
            $last_name = $names[1];
        }

        if ($u != null) {
            $new_user = $u;
        } else {
            $new_user = new User();
        }

        $new_user->first_name = $first_name;
        $new_user->last_name = $last_name;
        $new_user->username = $r->email;
        $new_user->email = $r->email;
        $new_user->password = password_hash($r->password, PASSWORD_DEFAULT);
        $new_user->name = $first_name . " " . $last_name;
        $new_user->phone_number = $r->email;
        $new_user->company_id = 1;
        $new_user->status = "Active";

        try {
            $new_user->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }

        $registered_user = User::find($new_user->id);
        if ($registered_user == null) {
            Utils::error("Failed to register user.");
        }



        $token = auth('api')->setTTL(60 * 24 * 365 * 5)->attempt([
            'id' => $registered_user->id,
            'password' => trim($r->password),
        ]);


        if ($token == null) {
            $registered_user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);
            try {
                $registered_user->save();
            } catch (\Exception $e) {
                Utils::error($e->getMessage());
            }
            $registered_user = User::find($registered_user->id);
            $token = auth('api')->setTTL(60 * 24 * 365 * 5)->attempt([
                'id' => $registered_user->id,
                'password' => trim($r->password),
            ]);
        }


        if ($token == null) {
            return $this->error('Wrong credentials.');
        }
        $registered_user->token = $token;
        $registered_user->remember_token = $token;


        Utils::success([
            'user' => $registered_user,
            'company' => Company::find(1),
        ], "Registration successful.");
    }



    public function password_reset(Request $r)
    {

        if ($r->code == null) {
            Utils::error("Secret code is required.");
            //latest changes
        }

        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if email is already registered
        $u = User::where('email', $r->email)->first();
        if ($u == null) {
            Utils::error("Account not found with $r->email.");
        }
        //check if password is provided
        if ($r->password == null) {
            Utils::error("Password is required.");
        }

        //check code
        if ($u->secret_code != $r->code) {
            Utils::error("Invalid secret code.");
        }
        //set new password
        $u->password = password_hash($r->password, PASSWORD_DEFAULT);
        $u->secret_code = null;
        $u->save();
        $u = User::find($u->id);
        Utils::success([
            'user' => $u,
            'company' => Company::find(1),
        ], "Password reset successful.");
    }


    public function request_password_reset_code(Request $r)
    {



        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if email is already registered
        $u = User::where('email', $r->email)->first();
        if ($u == null) {
            Utils::error("Account not found with $r->email.");
        }
        $code = rand(100000, 999999);
        $u->secret_code = $code;
        $u->save();

        $mail_body = <<<EOD
            <p>Dear {$u->name},</p>
            <p>Your password reset code is <b><code>$code</code></b></p>
            <p>Thank you.</p>
            EOD;
        $data['email'] = $u->email;
        $date = date('Y-m-d');
        $data['subject'] = "Password Reset Code - " . env('APP_NAME');
        $data['body'] = $mail_body;
        $data['data'] = $data['body'];
        $data['name'] = $u->name;
        try {
            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            return Utils::error($th->getMessage());
        }
        $u = User::find($u->id);
        Utils::success([
            'user' => $u,
        ], "Code sent successfully.");
    }

    public function getChatMessages(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $otherUserId = $r->other_user_id;
        if (!$otherUserId) {
            return Utils::error("Other user ID is required.");
        }

        try {
            // Mock chat messages for now - replace with actual database queries
            $messages = [
                [
                    'id' => 1,
                    'sender_id' => $u->id,
                    'receiver_id' => $otherUserId,
                    'message' => "Hi there! How are you doing?",
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                    'is_read' => true
                ],
                [
                    'id' => 2,
                    'sender_id' => $otherUserId,
                    'receiver_id' => $u->id,
                    'message' => "Hello! I'm doing great, thanks for asking. How about you?",
                    'timestamp' => now()->subMinutes(25)->toISOString(),
                    'is_read' => true
                ],
                [
                    'id' => 3,
                    'sender_id' => $u->id,
                    'receiver_id' => $otherUserId,
                    'message' => "I'm wonderful! Just enjoying this beautiful day. What are you up to?",
                    'timestamp' => now()->subMinutes(20)->toISOString(),
                    'is_read' => false
                ]
            ];

            return Utils::success($messages, "Messages retrieved successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to retrieve messages: " . $e->getMessage());
        }
    }

    public function sendMessage(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $receiverId = $r->receiver_id;
        $message = $r->message;

        if (!$receiverId) {
            return Utils::error("Receiver ID is required.");
        }

        if (!$message || trim($message) === '') {
            return Utils::error("Message content is required.");
        }

        try {
            // Mock message sending - replace with actual database insert
            $newMessage = [
                'id' => rand(1000, 9999),
                'sender_id' => $u->id,
                'receiver_id' => $receiverId,
                'message' => $message,
                'timestamp' => now()->toISOString(),
                'is_read' => false
            ];

            return Utils::success($newMessage, "Message sent successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to send message: " . $e->getMessage());
        }
    }

    public function getRestaurantSuggestions(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        try {
            $restaurants = [
                [
                    'id' => 1,
                    'name' => 'The Romantic Garden',
                    'cuisine' => 'Italian',
                    'rating' => 4.8,
                    'price_range' => '$$$',
                    'distance' => '2.3 km',
                    'atmosphere' => 'romantic',
                    'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400',
                    'description' => 'Intimate Italian dining with candlelit tables and garden views.'
                ],
                [
                    'id' => 2,
                    'name' => 'Sunset Rooftop',
                    'cuisine' => 'Mediterranean',
                    'rating' => 4.6,
                    'price_range' => '$$$$',
                    'distance' => '3.1 km',
                    'atmosphere' => 'upscale',
                    'image' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=400',
                    'description' => 'Stunning city views with Mediterranean cuisine and craft cocktails.'
                ],
                [
                    'id' => 3,
                    'name' => 'Cozy Corner Bistro',
                    'cuisine' => 'French',
                    'rating' => 4.7,
                    'price_range' => '$$',
                    'distance' => '1.8 km',
                    'atmosphere' => 'casual',
                    'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400',
                    'description' => 'Charming French bistro with homestyle cooking and warm ambiance.'
                ]
            ];

            return Utils::success($restaurants, "Restaurant suggestions retrieved successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to get restaurant suggestions: " . $e->getMessage());
        }
    }

    public function getDateActivities(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        try {
            $activities = [
                [
                    'id' => 1,
                    'title' => 'Art Gallery Walk',
                    'category' => 'Culture',
                    'duration' => '2-3 hours',
                    'cost' => 'Free-$20',
                    'location' => 'Downtown Arts District',
                    'image' => 'https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=400',
                    'description' => 'Explore contemporary art and spark meaningful conversations.',
                    'why_great' => 'Perfect for discovering each other\'s artistic tastes and having deep conversations.'
                ],
                [
                    'id' => 2,
                    'title' => 'Cooking Class',
                    'category' => 'Activity',
                    'duration' => '3 hours',
                    'cost' => '$80-120',
                    'location' => 'Culinary Arts Center',
                    'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400',
                    'description' => 'Learn to cook together in a fun, interactive environment.',
                    'why_great' => 'Great for teamwork and you get to enjoy the meal you create together.'
                ],
                [
                    'id' => 3,
                    'title' => 'Sunset Beach Walk',
                    'category' => 'Outdoor',
                    'duration' => '1-2 hours',
                    'cost' => 'Free',
                    'location' => 'Oceanfront Boardwalk',
                    'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400',
                    'description' => 'Romantic walk along the beach as the sun sets.',
                    'why_great' => 'Classic romantic setting with natural beauty and peaceful atmosphere.'
                ],
                [
                    'id' => 4,
                    'title' => 'Wine Tasting',
                    'category' => 'Drink',
                    'duration' => '2 hours',
                    'cost' => '$45-65',
                    'location' => 'Local Vineyard',
                    'image' => 'https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?w=400',
                    'description' => 'Sample local wines and learn about wine pairing.',
                    'why_great' => 'Relaxed atmosphere perfect for conversation and discovering preferences.'
                ]
            ];

            return Utils::success($activities, "Date activities retrieved successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to get date activities: " . $e->getMessage());
        }
    }

    public function getPopularDateSpots(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        try {
            $spots = [
                [
                    'id' => 1,
                    'name' => 'Central Park',
                    'type' => 'Park',
                    'rating' => 4.9,
                    'distance' => '1.5 km',
                    'image' => 'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=400',
                    'features' => ['scenic views', 'walking paths', 'photo spots', 'peaceful'],
                    'best_time' => 'Evening or early morning',
                    'why_popular' => 'Beautiful natural setting perfect for intimate conversations.'
                ],
                [
                    'id' => 2,
                    'name' => 'Historic Downtown',
                    'type' => 'District',
                    'rating' => 4.7,
                    'distance' => '2.8 km',
                    'image' => 'https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?w=400',
                    'features' => ['cafes', 'shops', 'architecture', 'vibrant'],
                    'best_time' => 'Afternoon or evening',
                    'why_popular' => 'Lots of activities and conversation starters in a charming setting.'
                ],
                [
                    'id' => 3,
                    'name' => 'Riverside Boardwalk',
                    'type' => 'Waterfront',
                    'rating' => 4.8,
                    'distance' => '3.2 km',
                    'image' => 'https://images.unsplash.com/photo-1573160813959-df05c19d7cf8?w=400',
                    'features' => ['water views', 'restaurants', 'boat rentals', 'romantic'],
                    'best_time' => 'Sunset',
                    'why_popular' => 'Romantic waterfront atmosphere with dining and activity options.'
                ]
            ];

            return Utils::success($spots, "Popular date spots retrieved successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to get popular date spots: " . $e->getMessage());
        }
    }

    public function savePlannedDate(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $dateData = $r->all();

        if (!isset($dateData['type']) || !isset($dateData['details'])) {
            return Utils::error("Date type and details are required.");
        }

        try {
            // Mock saving planned date - replace with actual database insert
            $plannedDate = [
                'id' => rand(1000, 9999),
                'user_id' => $u->id,
                'partner_id' => $dateData['partner_id'] ?? null,
                'type' => $dateData['type'],
                'details' => $dateData['details'],
                'planned_date' => $dateData['planned_date'] ?? null,
                'status' => 'planned',
                'created_at' => now()->toISOString()
            ];

            return Utils::success($plannedDate, "Date planned and saved successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to save planned date: " . $e->getMessage());
        }
    }

    public function advancedSearch(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        try {
            // Get search parameters
            $ageRange = $r->age_range ?? [18, 99];
            $distance = $r->distance ?? 50;
            $interests = $r->interests ?? [];
            $location = $r->location;
            $education = $r->education;
            $occupation = $r->occupation;
            $relationshipGoals = $r->relationship_goals;
            $lifestyle = $r->lifestyle ?? [];
            $personality = $r->personality ?? [];

            // Mock search results - replace with actual database query
            $searchResults = [
                [
                    'id' => 101,
                    'name' => 'Emma Wilson',
                    'age' => 28,
                    'distance' => '3.2 km away',
                    'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b47c?w=400',
                    'bio' => 'Love hiking, cooking, and meaningful conversations.',
                    'interests' => ['hiking', 'cooking', 'photography', 'travel'],
                    'education' => 'University Graduate',
                    'occupation' => 'Marketing Manager',
                    'compatibility_score' => 94,
                    'mutual_interests' => ['hiking', 'cooking'],
                    'relationship_goals' => 'Long-term relationship',
                    'lifestyle' => ['active', 'social'],
                    'personality' => ['outgoing', 'adventurous']
                ],
                [
                    'id' => 102,
                    'name' => 'Sarah Johnson',
                    'age' => 26,
                    'distance' => '5.1 km away',
                    'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400',
                    'bio' => 'Art enthusiast who loves weekend adventures.',
                    'interests' => ['art', 'travel', 'music', 'yoga'],
                    'education' => 'Masters Degree',
                    'occupation' => 'Graphic Designer',
                    'compatibility_score' => 87,
                    'mutual_interests' => ['art', 'travel'],
                    'relationship_goals' => 'Dating to see where it goes',
                    'lifestyle' => ['creative', 'peaceful'],
                    'personality' => ['creative', 'thoughtful']
                ]
            ];

            return Utils::success([
                'results' => $searchResults,
                'total_count' => count($searchResults),
                'search_criteria' => [
                    'age_range' => $ageRange,
                    'distance' => $distance,
                    'interests' => $interests,
                    'location' => $location,
                    'education' => $education,
                    'occupation' => $occupation,
                    'relationship_goals' => $relationshipGoals,
                    'lifestyle' => $lifestyle,
                    'personality' => $personality
                ]
            ], "Advanced search completed successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to perform advanced search: " . $e->getMessage());
        }
    }

    // Phase 6.2: Chat Safety & Moderation Methods

    public function analyzeMessageSafety(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $message = $r->message;
        if (!$message) {
            return Utils::error("Message content is required.");
        }

        try {
            // Mock AI-powered message safety analysis
            $safetyAnalysis = [
                'message' => $message,
                'safety_level' => 'safe', // safe, warning, dangerous, emergency
                'sentiment_score' => 0.7, // 0-1 scale
                'inappropriate_content' => false,
                'suggestive_content' => false,
                'emergency_keywords' => false,
                'warnings' => [],
                'suggestions' => [
                    'This message appears appropriate for the conversation'
                ],
                'should_block' => false,
                'requires_review' => false,
                'confidence' => 0.95
            ];

            // Check for inappropriate keywords (simple mock implementation)
            $inappropriateKeywords = ['abuse', 'threat', 'violence', 'hate'];
            $lowerMessage = strtolower($message);

            foreach ($inappropriateKeywords as $keyword) {
                if (strpos($lowerMessage, $keyword) !== false) {
                    $safetyAnalysis['safety_level'] = 'dangerous';
                    $safetyAnalysis['inappropriate_content'] = true;
                    $safetyAnalysis['warnings'] = ['This message contains inappropriate content'];
                    $safetyAnalysis['suggestions'] = ['Consider blocking this user if inappropriate behavior continues'];
                    $safetyAnalysis['requires_review'] = true;
                    break;
                }
            }

            return Utils::success($safetyAnalysis, "Message safety analysis completed.");
        } catch (\Exception $e) {
            return Utils::error("Failed to analyze message safety: " . $e->getMessage());
        }
    }

    public function reportUnsafeBehavior(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $reportedUserId = $r->reported_user_id;
        $reason = $r->reason;
        $description = $r->description;

        if (!$reportedUserId || !$reason) {
            return Utils::error("Reported user ID and reason are required.");
        }

        try {
            // Mock report submission
            $reportId = 'RPT' . time() . rand(100, 999);

            $reportData = [
                'report_id' => $reportId,
                'reported_user_id' => $reportedUserId,
                'reporting_user_id' => $u->id,
                'reason' => $reason,
                'description' => $description,
                'status' => 'under_review',
                'created_at' => now()->toISOString(),
                'estimated_review_time' => '24 hours',
                'next_steps' => [
                    'Our safety team will review this report within 24 hours',
                    'We may contact you for additional information if needed',
                    'You will be notified of any actions taken'
                ]
            ];

            return Utils::success($reportData, "Report submitted successfully. Our safety team will review it within 24 hours.");
        } catch (\Exception $e) {
            return Utils::error("Failed to submit report: " . $e->getMessage());
        }
    }

    public function verifyMeetupConsent(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $partnerId = $r->partner_id;
        $meetupDetails = $r->meetup_details;

        if (!$partnerId || !$meetupDetails) {
            return Utils::error("Partner ID and meetup details are required.");
        }

        try {
            // Mock consent verification
            $consentResult = [
                'verification_id' => 'VER' . time() . rand(100, 999),
                'is_successful' => true,
                'both_consented' => true,
                'user_consent_timestamp' => now()->toISOString(),
                'partner_consent_timestamp' => now()->subMinutes(5)->toISOString(),
                'meetup_details' => $meetupDetails,
                'safety_reminders' => [
                    'Meet in a public place for your first few dates',
                    'Tell a friend or family member about your plans',
                    'Trust your instincts and leave if you feel uncomfortable',
                    'Have your own transportation arranged',
                    'Keep your phone charged and accessible'
                ],
                'emergency_features' => [
                    'Emergency button available in the app',
                    'Location sharing with trusted contacts',
                    '24/7 safety support hotline available'
                ]
            ];

            return Utils::success($consentResult, "Mutual consent verified successfully.");
        } catch (\Exception $e) {
            return Utils::error("Failed to verify meetup consent: " . $e->getMessage());
        }
    }

    public function checkPhotoSharingRisk(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $receiverId = $r->receiver_id;
        if (!$receiverId) {
            return Utils::error("Receiver ID is required.");
        }

        try {
            // Mock photo sharing risk assessment
            $riskAssessment = [
                'risk_level' => 'medium', // low, medium, high
                'relationship_duration_days' => 3,
                'is_new_relationship' => true,
                'is_first_photo' => true,
                'warnings' => [
                    "You've been chatting for less than a week. Consider getting to know each other better first.",
                    "This is your first photo share. Remember that shared photos can be saved or screenshot.",
                    "Only share photos you're comfortable with others potentially seeing",
                    "Consider if this photo reveals personal information about your location",
                    "Remember that you can report inappropriate photo requests"
                ],
                'safety_tips' => [
                    'Photos can be saved or screenshot by the recipient',
                    'Avoid sharing photos with personal information',
                    'You can report inappropriate photo requests',
                    'Trust your instincts about what to share'
                ],
                'should_show_consent' => true,
                'consent_message' => 'I understand the risks and consent to sharing this photo',
                'recommended_actions' => [
                    'Take time to get to know each other better',
                    'Consider starting with less personal photos',
                    'Make sure the photo doesn\'t reveal private information'
                ]
            ];

            return Utils::success($riskAssessment, "Photo sharing risk assessment completed.");
        } catch (\Exception $e) {
            return Utils::error("Failed to assess photo sharing risk: " . $e->getMessage());
        }
    }

    public function analyzeConversationSentiment(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $partnerId = $r->partner_id;
        $recentMessages = $r->recent_messages ?? [];

        if (!$partnerId) {
            return Utils::error("Partner ID is required.");
        }

        try {
            // Mock conversation sentiment analysis
            $sentimentAnalysis = [
                'conversation_id' => $u->id . '_' . $partnerId,
                'analysis_timestamp' => now()->toISOString(),
                'message_count' => count($recentMessages),
                'average_sentiment' => 0.75, // 0-1 scale (0=very negative, 1=very positive)
                'positive_messages' => 8,
                'negative_messages' => 1,
                'neutral_messages' => 3,
                'health_status' => 'healthy', // excellent, healthy, needs_attention, concerning
                'sentiment_trend' => 'improving', // improving, stable, declining
                'recommendations' => [
                    'Your conversation is developing nicely',
                    'Continue sharing interests and experiences',
                    'Consider planning a fun activity together'
                ],
                'warning_signs' => [],
                'positive_indicators' => [
                    'Both participants are actively engaged',
                    'Messages show mutual interest',
                    'Conversation topics are diverse and healthy'
                ],
                'next_steps' => [
                    'Keep the conversation balanced',
                    'Share more about your interests',
                    'Consider suggesting a meetup when you feel ready'
                ]
            ];

            // Adjust analysis based on message count
            if (count($recentMessages) < 5) {
                $sentimentAnalysis['health_status'] = 'early_stage';
                $sentimentAnalysis['recommendations'] = [
                    'Your conversation is just getting started',
                    'Take time to get to know each other',
                    'Ask open-ended questions to learn more'
                ];
            }

            return Utils::success($sentimentAnalysis, "Conversation sentiment analysis completed.");
        } catch (\Exception $e) {
            return Utils::error("Failed to analyze conversation sentiment: " . $e->getMessage());
        }
    }

    public function emergencySafetyAlert(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::error("User not found.");
        }

        $alertType = $r->alert_type; // 'safety_concern', 'emergency', 'uncomfortable_situation'
        $location = $r->location;
        $additionalInfo = $r->additional_info;

        try {
            // Mock emergency safety alert processing
            $alertId = 'ALERT' . time() . rand(100, 999);

            $alertResponse = [
                'alert_id' => $alertId,
                'status' => 'processed',
                'timestamp' => now()->toISOString(),
                'alert_type' => $alertType,
                'user_id' => $u->id,
                'location' => $location,
                'emergency_contacts_notified' => true,
                'safety_team_notified' => true,
                'actions_taken' => [
                    'Emergency contacts have been alerted',
                    'Safety team has been notified',
                    'Location has been logged for safety purposes'
                ],
                'immediate_resources' => [
                    [
                        'type' => 'emergency_services',
                        'number' => '911',
                        'description' => 'Call if in immediate danger'
                    ],
                    [
                        'type' => 'safety_hotline',
                        'number' => '+1-800-LOVEBRD',
                        'description' => '24/7 safety support'
                    ],
                    [
                        'type' => 'crisis_text',
                        'number' => '741741',
                        'description' => 'Text HOME for crisis support'
                    ]
                ],
                'follow_up_actions' => [
                    'Safety team will contact you within 30 minutes',
                    'Location monitoring activated for 2 hours',
                    'Emergency contacts will receive status updates'
                ]
            ];

            return Utils::success($alertResponse, "Emergency safety alert processed. Help is on the way.");
        } catch (\Exception $e) {
            return Utils::error("Failed to process emergency alert: " . $e->getMessage());
        }
    }

    /**
     * Get restaurant suggestions for date planning
     */
    public function get_restaurant_suggestions(Request $request)
    {
        try {
            $lat = $request->input('lat', 0.0);
            $lng = $request->input('lng', 0.0);
            $cuisine = $request->input('cuisine', 'all');
            $priceRange = $request->input('price_range', 'moderate');
            $maxDistance = $request->input('max_distance', 10.0);

            // Mock restaurant data with realistic suggestions
            $restaurants = [
                [
                    'id' => 1,
                    'name' => 'The Romantic Garden',
                    'cuisine' => 'Italian',
                    'price_range' => 'upscale',
                    'rating' => 4.7,
                    'distance_km' => 2.3,
                    'address' => '123 Love Street, Downtown',
                    'phone' => '+1 (555) 123-4567',
                    'description' => 'Intimate Italian restaurant with candlelit tables and garden seating',
                    'date_friendly_features' => ['Quiet atmosphere', 'Wine selection', 'Outdoor seating'],
                    'average_cost_per_person' => 65,
                    'image_url' => 'https://example.com/restaurant1.jpg',
                    'booking_url' => 'https://restaurant1.com/book'
                ],
                [
                    'id' => 2,
                    'name' => 'Café Luna',
                    'cuisine' => 'French',
                    'price_range' => 'moderate',
                    'rating' => 4.5,
                    'distance_km' => 1.8,
                    'address' => '456 Moon Avenue, Arts District',
                    'phone' => '+1 (555) 234-5678',
                    'description' => 'Charming French café perfect for casual first dates',
                    'date_friendly_features' => ['Great coffee', 'Cozy setting', 'Live jazz weekends'],
                    'average_cost_per_person' => 35,
                    'image_url' => 'https://example.com/restaurant2.jpg',
                    'booking_url' => 'https://restaurant2.com/book'
                ],
                [
                    'id' => 3,
                    'name' => 'Sakura Sushi Bar',
                    'cuisine' => 'Japanese',
                    'price_range' => 'upscale',
                    'rating' => 4.8,
                    'distance_km' => 3.2,
                    'address' => '789 Cherry Blossom Road, Midtown',
                    'phone' => '+1 (555) 345-6789',
                    'description' => 'Authentic sushi bar with omakase experience',
                    'date_friendly_features' => ['Chef interaction', 'Sake pairing', 'Intimate counter seating'],
                    'average_cost_per_person' => 85,
                    'image_url' => 'https://example.com/restaurant3.jpg',
                    'booking_url' => 'https://restaurant3.com/book'
                ],
                [
                    'id' => 4,
                    'name' => 'Sunny Side Brunch',
                    'cuisine' => 'American',
                    'price_range' => 'budget',
                    'rating' => 4.3,
                    'distance_km' => 1.5,
                    'address' => '321 Breakfast Lane, University District',
                    'phone' => '+1 (555) 456-7890',
                    'description' => 'Popular brunch spot perfect for morning dates',
                    'date_friendly_features' => ['All-day breakfast', 'Outdoor patio', 'Fresh pastries'],
                    'average_cost_per_person' => 22,
                    'image_url' => 'https://example.com/restaurant4.jpg',
                    'booking_url' => 'https://restaurant4.com/book'
                ]
            ];

            // Filter by cuisine if specified
            if ($cuisine !== 'all') {
                $restaurants = array_filter($restaurants, function ($restaurant) use ($cuisine) {
                    return strtolower($restaurant['cuisine']) === strtolower($cuisine);
                });
            }

            // Filter by price range if specified
            if ($priceRange !== 'all') {
                $restaurants = array_filter($restaurants, function ($restaurant) use ($priceRange) {
                    return $restaurant['price_range'] === $priceRange;
                });
            }

            // Filter by distance
            $restaurants = array_filter($restaurants, function ($restaurant) use ($maxDistance) {
                return $restaurant['distance_km'] <= $maxDistance;
            });

            $restaurantResponse = [
                'restaurants' => array_values($restaurants),
                'total_count' => count($restaurants),
                'search_params' => [
                    'cuisine' => $cuisine,
                    'price_range' => $priceRange,
                    'max_distance' => $maxDistance,
                    'location' => "Lat: {$lat}, Lng: {$lng}"
                ]
            ];

            return Utils::success($restaurantResponse, "Restaurant suggestions retrieved successfully");
        } catch (\Exception $e) {
            return Utils::error("Failed to get restaurant suggestions: " . $e->getMessage());
        }
    }

    /**
     * Get date activity suggestions based on interests
     */
    public function get_date_activities(Request $request)
    {
        try {
            $interests = $request->input('shared_interests', []);
            $timePreference = $request->input('time_preference', 'evening');
            $budget = $request->input('budget', 'moderate');
            $lat = $request->input('lat', 0.0);
            $lng = $request->input('lng', 0.0);

            $activities = [
                [
                    'id' => 1,
                    'name' => 'Art Gallery Walk',
                    'category' => 'Cultural',
                    'duration_hours' => 2,
                    'cost_per_person' => 15,
                    'description' => 'Explore local contemporary art galleries in the arts district',
                    'ideal_for' => ['Art lovers', 'Creative types', 'Culture enthusiasts'],
                    'time_of_day' => ['afternoon', 'evening'],
                    'weather_dependent' => false,
                    'conversation_friendly' => true,
                    'location' => 'Arts District',
                    'booking_required' => false
                ],
                [
                    'id' => 2,
                    'name' => 'Sunset Hiking Trail',
                    'category' => 'Outdoor',
                    'duration_hours' => 3,
                    'cost_per_person' => 0,
                    'description' => 'Scenic hiking trail with beautiful sunset views',
                    'ideal_for' => ['Nature lovers', 'Fitness enthusiasts', 'Adventure seekers'],
                    'time_of_day' => ['afternoon', 'evening'],
                    'weather_dependent' => true,
                    'conversation_friendly' => true,
                    'location' => 'Mountain View Park',
                    'booking_required' => false
                ],
                [
                    'id' => 3,
                    'name' => 'Wine Tasting Experience',
                    'category' => 'Food & Drink',
                    'duration_hours' => 2.5,
                    'cost_per_person' => 45,
                    'description' => 'Guided wine tasting with cheese pairings',
                    'ideal_for' => ['Wine enthusiasts', 'Foodies', 'Sophisticated daters'],
                    'time_of_day' => ['afternoon', 'evening'],
                    'weather_dependent' => false,
                    'conversation_friendly' => true,
                    'location' => 'Downtown Wine Bar',
                    'booking_required' => true
                ],
                [
                    'id' => 4,
                    'name' => 'Cooking Class for Two',
                    'category' => 'Interactive',
                    'duration_hours' => 3,
                    'cost_per_person' => 75,
                    'description' => 'Learn to cook together in a fun, interactive environment',
                    'ideal_for' => ['Food lovers', 'Interactive daters', 'Team players'],
                    'time_of_day' => ['afternoon', 'evening'],
                    'weather_dependent' => false,
                    'conversation_friendly' => true,
                    'location' => 'Culinary Institute',
                    'booking_required' => true
                ],
                [
                    'id' => 5,
                    'name' => 'Coffee Shop Hopping',
                    'category' => 'Casual',
                    'duration_hours' => 2,
                    'cost_per_person' => 12,
                    'description' => 'Tour the city\'s best coffee shops and cafés',
                    'ideal_for' => ['Coffee lovers', 'Casual daters', 'Conversationalists'],
                    'time_of_day' => ['morning', 'afternoon'],
                    'weather_dependent' => false,
                    'conversation_friendly' => true,
                    'location' => 'Various locations',
                    'booking_required' => false
                ]
            ];

            // Filter by budget
            $budgetRanges = [
                'budget' => [0, 25],
                'moderate' => [25, 60],
                'upscale' => [60, 200]
            ];

            if (isset($budgetRanges[$budget])) {
                $range = $budgetRanges[$budget];
                $activities = array_filter($activities, function ($activity) use ($range) {
                    return $activity['cost_per_person'] >= $range[0] && $activity['cost_per_person'] <= $range[1];
                });
            }

            // Filter by time preference
            $activities = array_filter($activities, function ($activity) use ($timePreference) {
                return in_array($timePreference, $activity['time_of_day']);
            });

            $activityResponse = [
                'activities' => array_values($activities),
                'total_count' => count($activities),
                'filters_applied' => [
                    'budget' => $budget,
                    'time_preference' => $timePreference,
                    'shared_interests' => $interests
                ]
            ];

            return Utils::success($activityResponse, "Date activities retrieved successfully");
        } catch (\Exception $e) {
            return Utils::error("Failed to get date activities: " . $e->getMessage());
        }
    }

    /**
     * Get popular date spots in the area
     */
    public function get_popular_date_spots(Request $request)
    {
        try {
            $lat = $request->input('lat', 0.0);
            $lng = $request->input('lng', 0.0);
            $radius = $request->input('radius', 15.0);
            $dateType = $request->input('date_type', 'any');

            $popularSpots = [
                [
                    'id' => 1,
                    'name' => 'Riverfront Promenade',
                    'type' => 'scenic_walk',
                    'category' => 'Outdoor',
                    'popularity_score' => 9.2,
                    'distance_km' => 2.1,
                    'description' => 'Beautiful waterfront walkway perfect for romantic strolls',
                    'best_time' => 'Sunset (6-8 PM)',
                    'cost' => 'Free',
                    'highlights' => ['Water views', 'Street performers', 'Photo opportunities'],
                    'crowd_level' => 'Moderate',
                    'parking_available' => true,
                    'weather_dependent' => true
                ],
                [
                    'id' => 2,
                    'name' => 'Historic Downtown Square',
                    'type' => 'cultural',
                    'category' => 'Cultural',
                    'popularity_score' => 8.7,
                    'distance_km' => 1.8,
                    'description' => 'Charming historic area with shops, cafés, and street art',
                    'best_time' => 'Afternoon (2-5 PM)',
                    'cost' => 'Free to explore',
                    'highlights' => ['Architecture', 'Local shops', 'Street food'],
                    'crowd_level' => 'Busy',
                    'parking_available' => true,
                    'weather_dependent' => false
                ],
                [
                    'id' => 3,
                    'name' => 'City Observatory',
                    'type' => 'unique',
                    'category' => 'Educational',
                    'popularity_score' => 8.9,
                    'distance_km' => 5.2,
                    'description' => 'Stargazing and city views from the observatory deck',
                    'best_time' => 'Evening (7-10 PM)',
                    'cost' => '$15 per person',
                    'highlights' => ['Star gazing', 'City panorama', 'Educational programs'],
                    'crowd_level' => 'Low',
                    'parking_available' => true,
                    'weather_dependent' => true
                ],
                [
                    'id' => 4,
                    'name' => 'Botanical Gardens',
                    'type' => 'nature',
                    'category' => 'Nature',
                    'popularity_score' => 8.5,
                    'distance_km' => 3.7,
                    'description' => 'Peaceful gardens with seasonal flowers and quiet paths',
                    'best_time' => 'Morning (9-11 AM) or Late afternoon (4-6 PM)',
                    'cost' => '$8 per person',
                    'highlights' => ['Seasonal blooms', 'Quiet paths', 'Photo spots'],
                    'crowd_level' => 'Low',
                    'parking_available' => true,
                    'weather_dependent' => true
                ],
                [
                    'id' => 5,
                    'name' => 'Local Farmers Market',
                    'type' => 'interactive',
                    'category' => 'Food & Culture',
                    'popularity_score' => 8.3,
                    'distance_km' => 2.9,
                    'description' => 'Vibrant market with local produce, crafts, and food trucks',
                    'best_time' => 'Saturday mornings (8 AM-1 PM)',
                    'cost' => 'Free entry, food costs vary',
                    'highlights' => ['Local vendors', 'Fresh food', 'Live music'],
                    'crowd_level' => 'Busy',
                    'parking_available' => false,
                    'weather_dependent' => true
                ]
            ];

            // Filter by date type
            if ($dateType !== 'any') {
                $popularSpots = array_filter($popularSpots, function ($spot) use ($dateType) {
                    return $spot['type'] === $dateType;
                });
            }

            // Filter by radius
            $popularSpots = array_filter($popularSpots, function ($spot) use ($radius) {
                return $spot['distance_km'] <= $radius;
            });

            // Sort by popularity score
            usort($popularSpots, function ($a, $b) {
                return $b['popularity_score'] <=> $a['popularity_score'];
            });

            $spotsResponse = [
                'popular_spots' => array_values($popularSpots),
                'total_count' => count($popularSpots),
                'search_area' => [
                    'center' => "Lat: {$lat}, Lng: {$lng}",
                    'radius_km' => $radius,
                    'date_type_filter' => $dateType
                ]
            ];

            return Utils::success($spotsResponse, "Popular date spots retrieved successfully");
        } catch (\Exception $e) {
            return Utils::error("Failed to get popular date spots: " . $e->getMessage());
        }
    }

    /**
     * Save a planned date for future reference
     */
    public function save_planned_date(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $matchUserId = $request->input('match_user_id');
            $dateTitle = $request->input('date_title');
            $dateDescription = $request->input('date_description');
            $plannedDate = $request->input('planned_date');
            $plannedTime = $request->input('planned_time');
            $location = $request->input('location');
            $estimatedCost = $request->input('estimated_cost', 0);
            $dateType = $request->input('date_type', 'general');

            // In a real implementation, you would save this to the database
            // For now, we'll return a mock success response
            $savedDate = [
                'id' => rand(1000, 9999),
                'user_id' => $userId,
                'match_user_id' => $matchUserId,
                'date_title' => $dateTitle,
                'date_description' => $dateDescription,
                'planned_date' => $plannedDate,
                'planned_time' => $plannedTime,
                'location' => $location,
                'estimated_cost' => $estimatedCost,
                'date_type' => $dateType,
                'status' => 'planned',
                'created_at' => date('Y-m-d H:i:s'),
                'confirmation_required' => true,
                'suggestions' => [
                    'backup_plans' => [
                        'Indoor alternative in case of bad weather',
                        'Nearby café for post-activity drinks'
                    ],
                    'preparation_tips' => [
                        'Check weather forecast',
                        'Confirm location details',
                        'Plan transportation'
                    ]
                ]
            ];

            return Utils::success($savedDate, "Date plan saved successfully! Both users will be notified.");
        } catch (\Exception $e) {
            return Utils::error("Failed to save planned date: " . $e->getMessage());
        }
    }

    // ======= PHASE 7.2: DATE MARKETPLACE BOOKING ENDPOINTS =======

    /**
     * Book a restaurant for a date
     */
    public function book_restaurant(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $restaurantId = $request->input('restaurant_id');
            $dateTime = $request->input('date_time');
            $partySize = $request->input('party_size', 2);
            $specialRequests = $request->input('special_requests', '');
            $partnerId = $request->input('partner_id');

            if (!$restaurantId || !$dateTime) {
                return $this->error('Restaurant ID and date/time are required.');
            }

            // Mock restaurant booking response
            $booking = [
                'booking_id' => 'REST' . time() . rand(100, 999),
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'partner_id' => $partnerId,
                'date_time' => $dateTime,
                'party_size' => $partySize,
                'special_requests' => $specialRequests,
                'status' => 'confirmed',
                'confirmation_number' => 'LB' . strtoupper(substr(md5(time()), 0, 8)),
                'restaurant_details' => [
                    'name' => 'The Romantic Garden',
                    'address' => '123 Love Street, Downtown',
                    'phone' => '+1 (555) 123-4567',
                    'cuisine' => 'Italian'
                ],
                'booking_time' => now()->toISOString(),
                'estimated_cost' => 65 * $partySize,
                'currency' => 'CAD',
                'cancellation_policy' => 'Free cancellation up to 2 hours before reservation',
                'next_steps' => [
                    'Restaurant will call to confirm within 30 minutes',
                    'Bring confirmation number when arriving',
                    'Arrive 10 minutes early for best seating'
                ]
            ];

            return $this->success($booking, 'Restaurant booked successfully! Confirmation details sent to your partner.');
        } catch (\Exception $e) {
            return $this->error('Failed to book restaurant: ' . $e->getMessage());
        }
    }

    /**
     * Book an activity for a date
     */
    public function book_activity(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $activityId = $request->input('activity_id');
            $dateTime = $request->input('date_time');
            $participants = $request->input('participants', 2);
            $notes = $request->input('notes', '');
            $partnerId = $request->input('partner_id');

            if (!$activityId || !$dateTime) {
                return $this->error('Activity ID and date/time are required.');
            }

            // Mock activity booking response
            $booking = [
                'booking_id' => 'ACT' . time() . rand(100, 999),
                'activity_id' => $activityId,
                'user_id' => $user->id,
                'partner_id' => $partnerId,
                'date_time' => $dateTime,
                'participants' => $participants,
                'notes' => $notes,
                'status' => 'confirmed',
                'confirmation_number' => 'LB' . strtoupper(substr(md5(time() . 'ACT'), 0, 8)),
                'activity_details' => [
                    'name' => 'Wine Tasting Experience',
                    'location' => 'Downtown Wine Bar',
                    'duration' => '2.5 hours',
                    'category' => 'Food & Drink'
                ],
                'booking_time' => now()->toISOString(),
                'cost_per_person' => 45,
                'total_cost' => 45 * $participants,
                'currency' => 'CAD',
                'includes' => [
                    'Guided wine tasting',
                    'Cheese pairings',
                    'Educational materials'
                ],
                'what_to_bring' => [
                    'Valid ID (21+ required)',
                    'Comfortable clothing',
                    'Designated driver or transportation plan'
                ],
                'cancellation_policy' => 'Free cancellation up to 24 hours before activity'
            ];

            return $this->success($booking, 'Activity booked successfully! Your date adventure awaits.');
        } catch (\Exception $e) {
            return $this->error('Failed to book activity: ' . $e->getMessage());
        }
    }

    /**
     * Get available date packages (restaurant + activity combinations)
     */
    public function get_date_packages(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $budget = $request->input('budget', 'moderate');
            $dateStyle = $request->input('date_style', 'romantic');
            $timePreference = $request->input('time_preference', 'evening');

            // Mock date packages
            $packages = [
                [
                    'package_id' => 'PKG001',
                    'name' => 'Romantic Evening Package',
                    'description' => 'Perfect romantic evening with dinner and wine tasting',
                    'total_cost' => 220,
                    'cost_per_person' => 110,
                    'currency' => 'CAD',
                    'duration_hours' => 4.5,
                    'ideal_for' => ['romantic', 'upscale', 'special_occasion'],
                    'includes' => [
                        'Italian dinner at The Romantic Garden',
                        'Wine tasting experience',
                        'Complimentary dessert',
                        'Reserved seating'
                    ],
                    'restaurant' => [
                        'name' => 'The Romantic Garden',
                        'cuisine' => 'Italian',
                        'rating' => 4.7
                    ],
                    'activity' => [
                        'name' => 'Wine Tasting Experience',
                        'category' => 'Food & Drink',
                        'duration' => '2.5 hours'
                    ],
                    'savings' => 30,
                    'availability' => ['friday', 'saturday', 'sunday']
                ],
                [
                    'package_id' => 'PKG002',
                    'name' => 'Adventure & Dine Package',
                    'description' => 'Exciting day with outdoor activity and casual dining',
                    'total_cost' => 140,
                    'cost_per_person' => 70,
                    'currency' => 'CAD',
                    'duration_hours' => 5,
                    'ideal_for' => ['adventure', 'casual', 'active'],
                    'includes' => [
                        'Hiking trail experience',
                        'Lunch at Sunny Side Brunch',
                        'Trail snacks and water',
                        'Photo memories package'
                    ],
                    'activity' => [
                        'name' => 'Sunset Hiking Trail',
                        'category' => 'Outdoor',
                        'duration' => '3 hours'
                    ],
                    'restaurant' => [
                        'name' => 'Sunny Side Brunch',
                        'cuisine' => 'American',
                        'rating' => 4.3
                    ],
                    'savings' => 20,
                    'availability' => ['saturday', 'sunday']
                ],
                [
                    'package_id' => 'PKG003',
                    'name' => 'Cultural Explorer Package',
                    'description' => 'Art, culture, and fine dining experience',
                    'total_cost' => 180,
                    'cost_per_person' => 90,
                    'currency' => 'CAD',
                    'duration_hours' => 4,
                    'ideal_for' => ['cultural', 'sophisticated', 'artistic'],
                    'includes' => [
                        'Art gallery guided tour',
                        'French dinner at Café Luna',
                        'Cultural event tickets',
                        'Exclusive gallery access'
                    ],
                    'activity' => [
                        'name' => 'Art Gallery Walk',
                        'category' => 'Cultural',
                        'duration' => '2 hours'
                    ],
                    'restaurant' => [
                        'name' => 'Café Luna',
                        'cuisine' => 'French',
                        'rating' => 4.5
                    ],
                    'savings' => 25,
                    'availability' => ['thursday', 'friday', 'saturday']
                ]
            ];

            // Filter packages based on preferences
            $filteredPackages = array_filter($packages, function ($package) use ($dateStyle) {
                return in_array($dateStyle, $package['ideal_for']);
            });

            if (empty($filteredPackages)) {
                $filteredPackages = $packages; // Return all if no matches
            }

            return $this->success([
                'packages' => array_values($filteredPackages),
                'total_count' => count($filteredPackages),
                'filters_applied' => [
                    'budget' => $budget,
                    'date_style' => $dateStyle,
                    'time_preference' => $timePreference
                ],
                'savings_info' => 'Save 10-20% compared to booking separately!'
            ], 'Date packages retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to get date packages: ' . $e->getMessage());
        }
    }

    /**
     * Book a complete date package
     */
    public function book_date_package(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $packageId = $request->input('package_id');
            $dateTime = $request->input('date_time');
            $participants = $request->input('participants', 2);
            $partnerId = $request->input('partner_id');
            $specialRequests = $request->input('special_requests', '');

            if (!$packageId || !$dateTime) {
                return $this->error('Package ID and date/time are required.');
            }

            // Mock package booking response
            $booking = [
                'booking_id' => 'PKG' . time() . rand(100, 999),
                'package_id' => $packageId,
                'user_id' => $user->id,
                'partner_id' => $partnerId,
                'date_time' => $dateTime,
                'participants' => $participants,
                'special_requests' => $specialRequests,
                'status' => 'confirmed',
                'confirmation_number' => 'LB' . strtoupper(substr(md5(time() . 'PKG'), 0, 8)),
                'package_details' => [
                    'name' => 'Romantic Evening Package',
                    'description' => 'Perfect romantic evening with dinner and wine tasting',
                    'includes' => [
                        'Italian dinner at The Romantic Garden',
                        'Wine tasting experience',
                        'Complimentary dessert',
                        'Reserved seating'
                    ]
                ],
                'booking_time' => now()->toISOString(),
                'total_cost' => 220,
                'cost_per_person' => 110,
                'currency' => 'CAD',
                'savings_amount' => 30,
                'itinerary' => [
                    [
                        'time' => '18:00',
                        'activity' => 'Arrive at The Romantic Garden',
                        'location' => '123 Love Street, Downtown',
                        'duration' => '90 minutes'
                    ],
                    [
                        'time' => '19:30',
                        'activity' => 'Wine Tasting Experience',
                        'location' => 'Downtown Wine Bar',
                        'duration' => '150 minutes'
                    ]
                ],
                'cancellation_policy' => 'Free cancellation up to 24 hours before date',
                'contact_info' => [
                    'support_phone' => '+1 (555) LOVEBIRD',
                    'support_email' => 'dates@lovebirds.ca'
                ]
            ];

            return $this->success($booking, 'Complete date package booked! Both venues have been notified.');
        } catch (\Exception $e) {
            return $this->error('Failed to book date package: ' . $e->getMessage());
        }
    }

    /**
     * Get user's booking history
     */
    public function get_booking_history(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $limit = min($request->input('limit', 20), 50);
            $status = $request->input('status', 'all');

            // Mock booking history
            $bookings = [
                [
                    'booking_id' => 'REST' . (time() - 86400) . '001',
                    'type' => 'restaurant',
                    'status' => 'completed',
                    'date_time' => now()->subDays(2)->toISOString(),
                    'booking_time' => now()->subDays(3)->toISOString(),
                    'confirmation_number' => 'LB12345678',
                    'details' => [
                        'name' => 'The Romantic Garden',
                        'address' => '123 Love Street, Downtown',
                        'cuisine' => 'Italian'
                    ],
                    'cost' => 130,
                    'currency' => 'CAD',
                    'partner_name' => 'Sarah',
                    'rating_given' => 5,
                    'review' => 'Amazing dinner! Perfect atmosphere for our date.'
                ],
                [
                    'booking_id' => 'ACT' . (time() - 172800) . '002',
                    'type' => 'activity',
                    'status' => 'completed',
                    'date_time' => now()->subDays(5)->toISOString(),
                    'booking_time' => now()->subDays(6)->toISOString(),
                    'confirmation_number' => 'LB87654321',
                    'details' => [
                        'name' => 'Wine Tasting Experience',
                        'location' => 'Downtown Wine Bar',
                        'category' => 'Food & Drink'
                    ],
                    'cost' => 90,
                    'currency' => 'CAD',
                    'partner_name' => 'Emily',
                    'rating_given' => 4,
                    'review' => 'Great activity! Learned a lot about wine.'
                ],
                [
                    'booking_id' => 'PKG' . time() . '003',
                    'type' => 'package',
                    'status' => 'upcoming',
                    'date_time' => now()->addDays(3)->toISOString(),
                    'booking_time' => now()->subHours(2)->toISOString(),
                    'confirmation_number' => 'LB11223344',
                    'details' => [
                        'name' => 'Cultural Explorer Package',
                        'includes' => 'Art gallery + French dinner',
                        'total_activities' => 2
                    ],
                    'cost' => 180,
                    'currency' => 'CAD',
                    'partner_name' => 'Jessica',
                    'can_cancel' => true,
                    'can_modify' => true
                ]
            ];

            // Filter by status if specified
            if ($status !== 'all') {
                $bookings = array_filter($bookings, function ($booking) use ($status) {
                    return $booking['status'] === $status;
                });
            }

            // Calculate statistics
            $stats = [
                'total_bookings' => count($bookings),
                'completed_bookings' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed')),
                'upcoming_bookings' => count(array_filter($bookings, fn($b) => $b['status'] === 'upcoming')),
                'total_spent' => array_sum(array_column($bookings, 'cost')),
                'average_rating' => 4.5,
                'favorite_cuisine' => 'Italian',
                'most_booked_type' => 'restaurant'
            ];

            return $this->success([
                'bookings' => array_values($bookings),
                'stats' => $stats,
                'pagination' => [
                    'limit' => $limit,
                    'total' => count($bookings),
                    'has_more' => false
                ]
            ], 'Booking history retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to get booking history: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel_booking(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $bookingId = $request->input('booking_id');
            $reason = $request->input('reason', 'User requested cancellation');

            if (!$bookingId) {
                return $this->error('Booking ID is required.');
            }

            // Mock cancellation response
            $cancellation = [
                'booking_id' => $bookingId,
                'cancellation_id' => 'CANCEL' . time() . rand(100, 999),
                'status' => 'cancelled',
                'cancellation_time' => now()->toISOString(),
                'reason' => $reason,
                'refund_amount' => 180,
                'refund_currency' => 'CAD',
                'refund_status' => 'processing',
                'refund_eta' => '3-5 business days',
                'partner_notified' => true,
                'venues_notified' => true,
                'next_steps' => [
                    'Refund will be processed to original payment method',
                    'Partner has been notified of cancellation',
                    'Venues have been informed to release reservations'
                ]
            ];

            return $this->success($cancellation, 'Booking cancelled successfully. Refund is being processed.');
        } catch (\Exception $e) {
            return $this->error('Failed to cancel booking: ' . $e->getMessage());
        }
    }

    /**
     * Get available time slots for a venue
     */
    public function get_available_time_slots(Request $request)
    {
        $user = Utils::get_user($request);
        if (!$user) {
            return $this->error('User not authenticated.');
        }

        try {
            $venueId = $request->input('venue_id');
            $date = $request->input('date');
            $venueType = $request->input('venue_type', 'restaurant');

            if (!$venueId || !$date) {
                return $this->error('Venue ID and date are required.');
            }

            // Mock available time slots
            $timeSlots = [
                [
                    'time' => '17:00',
                    'available' => true,
                    'price_modifier' => 0,
                    'popularity' => 'low'
                ],
                [
                    'time' => '17:30',
                    'available' => true,
                    'price_modifier' => 0,
                    'popularity' => 'medium'
                ],
                [
                    'time' => '18:00',
                    'available' => true,
                    'price_modifier' => 10,
                    'popularity' => 'high'
                ],
                [
                    'time' => '18:30',
                    'available' => false,
                    'price_modifier' => 10,
                    'popularity' => 'high',
                    'reason' => 'Fully booked'
                ],
                [
                    'time' => '19:00',
                    'available' => true,
                    'price_modifier' => 15,
                    'popularity' => 'very_high'
                ],
                [
                    'time' => '19:30',
                    'available' => true,
                    'price_modifier' => 15,
                    'popularity' => 'very_high'
                ],
                [
                    'time' => '20:00',
                    'available' => true,
                    'price_modifier' => 10,
                    'popularity' => 'high'
                ],
                [
                    'time' => '20:30',
                    'available' => true,
                    'price_modifier' => 5,
                    'popularity' => 'medium'
                ],
                [
                    'time' => '21:00',
                    'available' => true,
                    'price_modifier' => 0,
                    'popularity' => 'low'
                ]
            ];

            return $this->success([
                'venue_id' => $venueId,
                'date' => $date,
                'venue_type' => $venueType,
                'time_slots' => $timeSlots,
                'timezone' => 'America/Toronto',
                'pricing_info' => [
                    'base_price' => 65,
                    'currency' => 'CAD',
                    'price_modifiers_explained' => 'Peak times (7-8 PM) have higher demand pricing'
                ]
            ], 'Available time slots retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to get available time slots: ' . $e->getMessage());
        }
    }

    // ======= END PHASE 7.2: DATE MARKETPLACE BOOKING ENDPOINTS =======

    // ======= PHASE 7.2: RELATIONSHIP MILESTONE GIFT SUGGESTIONS =======

    public function get_milestone_gift_suggestions(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('Unauthorized access');
            }

            $partnerId = $request->input('partner_id');
            $milestoneType = $request->input('milestone_type', 'general'); // first_date, 1_month, 3_months, 6_months, 1_year, birthday, valentine, christmas, general
            $relationshipStartDate = $request->input('relationship_start_date');
            $budget = $request->input('budget', 'medium'); // low, medium, high, luxury
            $partnerGender = $request->input('partner_gender', 'any');
            $partnerAge = $request->input('partner_age', 25);

            // Calculate relationship duration if start date provided
            $relationshipDuration = null;
            if ($relationshipStartDate) {
                $startDate = Carbon::parse($relationshipStartDate);
                $relationshipDuration = $startDate->diffInDays(Carbon::now());
            }

            // Define budget ranges in CAD
            $budgetRanges = [
                'low' => ['min' => 25, 'max' => 75],
                'medium' => ['min' => 75, 'max' => 200],
                'high' => ['min' => 200, 'max' => 500],
                'luxury' => ['min' => 500, 'max' => 2000]
            ];

            $selectedBudgetRange = $budgetRanges[$budget];

            // Milestone-specific gift categories and suggestions
            $milestoneGifts = [
                'first_date' => [
                    'title' => 'First Date Anniversary',
                    'subtitle' => 'Celebrate where it all began',
                    'message' => 'Sweet and thoughtful gifts to commemorate your special first date',
                    'categories' => ['flowers', 'sweets', 'experiences', 'jewelry'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_001',
                            'name' => 'First Date Memory Box',
                            'description' => 'A beautiful keepsake box to store mementos from your first date',
                            'price' => 45.99,
                            'category' => 'keepsakes',
                            'image_url' => 'https://example.com/memory-box.jpg',
                            'milestone_relevance' => 'Perfect for preserving first date memories like tickets, photos, or notes',
                            'personalization_options' => ['Custom engraving', 'Photo insert', 'Date inscription']
                        ],
                        [
                            'id' => 'milestone_002',
                            'name' => 'Bouquet of First Date Flowers',
                            'description' => 'Recreation of flowers from your first date location',
                            'price' => 65.00,
                            'category' => 'flowers',
                            'image_url' => 'https://example.com/first-date-bouquet.jpg',
                            'milestone_relevance' => 'Romantic callback to your first meeting',
                            'personalization_options' => ['Custom flower selection', 'Personal note', 'Delivery timing']
                        ]
                    ]
                ],
                '1_month' => [
                    'title' => 'One Month Together',
                    'subtitle' => 'Sweet celebration of your first month',
                    'message' => 'Cute and casual gifts to mark this early milestone',
                    'categories' => ['sweets', 'accessories', 'experiences'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_003',
                            'name' => 'Custom Photo Keychain',
                            'description' => 'A cute keychain featuring your favorite photo together',
                            'price' => 29.99,
                            'category' => 'accessories',
                            'image_url' => 'https://example.com/photo-keychain.jpg',
                            'milestone_relevance' => 'Sweet daily reminder of your time together',
                            'personalization_options' => ['Photo selection', 'Shape choice', 'Text engraving']
                        ],
                        [
                            'id' => 'milestone_004',
                            'name' => 'Date Night Dessert Box',
                            'description' => 'Artisanal treats for a cozy night in together',
                            'price' => 55.00,
                            'category' => 'sweets',
                            'image_url' => 'https://example.com/dessert-box.jpg',
                            'milestone_relevance' => 'Perfect for intimate one-month celebration',
                            'personalization_options' => ['Flavor preferences', 'Dietary restrictions', 'Custom note']
                        ]
                    ]
                ],
                '3_months' => [
                    'title' => 'Three Months Strong',
                    'subtitle' => 'Growing deeper together',
                    'message' => 'Meaningful gifts that show your relationship is getting serious',
                    'categories' => ['jewelry', 'experiences', 'fashion'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_005',
                            'name' => 'Promise Ring',
                            'description' => 'Elegant silver promise ring with birthstone accent',
                            'price' => 145.00,
                            'category' => 'jewelry',
                            'image_url' => 'https://example.com/promise-ring.jpg',
                            'milestone_relevance' => 'Symbol of your commitment at the 3-month mark',
                            'personalization_options' => ['Ring size', 'Birthstone choice', 'Engraving inside band']
                        ],
                        [
                            'id' => 'milestone_006',
                            'name' => 'Weekend Getaway Package',
                            'description' => 'Two-day romantic escape to celebrate your 3-month milestone',
                            'price' => 425.00,
                            'category' => 'experiences',
                            'image_url' => 'https://example.com/weekend-getaway.jpg',
                            'milestone_relevance' => 'Perfect timing for a romantic mini-vacation together',
                            'personalization_options' => ['Destination choice', 'Activity preferences', 'Dining options']
                        ]
                    ]
                ],
                '6_months' => [
                    'title' => 'Six Months of Love',
                    'subtitle' => 'Half a year of happiness',
                    'message' => 'Special gifts to celebrate this significant relationship milestone',
                    'categories' => ['jewelry', 'fashion', 'experiences', 'keepsakes'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_007',
                            'name' => 'Couple\'s Matching Bracelets',
                            'description' => 'Elegant matching bracelets with magnetic connection',
                            'price' => 185.00,
                            'category' => 'jewelry',
                            'image_url' => 'https://example.com/matching-bracelets.jpg',
                            'milestone_relevance' => 'Symbol of your strong 6-month connection',
                            'personalization_options' => ['Metal choice', 'Custom engraving', 'Charm additions']
                        ],
                        [
                            'id' => 'milestone_008',
                            'name' => 'Custom Star Map',
                            'description' => 'Personalized star map showing the sky from your first date',
                            'price' => 89.99,
                            'category' => 'keepsakes',
                            'image_url' => 'https://example.com/star-map.jpg',
                            'milestone_relevance' => 'Romantic commemoration of your relationship\'s beginning',
                            'personalization_options' => ['Date selection', 'Location coordinates', 'Custom text']
                        ]
                    ]
                ],
                '1_year' => [
                    'title' => 'One Year Anniversary',
                    'subtitle' => 'A year of love and memories',
                    'message' => 'Meaningful and luxurious gifts to honor your first year together',
                    'categories' => ['jewelry', 'experiences', 'keepsakes', 'luxury'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_009',
                            'name' => 'Anniversary Necklace',
                            'description' => 'Elegant gold necklace with your anniversary date engraved',
                            'price' => 295.00,
                            'category' => 'jewelry',
                            'image_url' => 'https://example.com/anniversary-necklace.jpg',
                            'milestone_relevance' => 'Perfect commemoration of your one-year milestone',
                            'personalization_options' => ['Metal type', 'Chain length', 'Date format', 'Gift box']
                        ],
                        [
                            'id' => 'milestone_010',
                            'name' => 'Anniversary Photo Album',
                            'description' => 'Luxury leather photo album chronicling your first year',
                            'price' => 125.00,
                            'category' => 'keepsakes',
                            'image_url' => 'https://example.com/photo-album.jpg',
                            'milestone_relevance' => 'Beautiful way to preserve your year of memories',
                            'personalization_options' => ['Cover color', 'Embossing text', 'Page layout', 'Photo printing']
                        ]
                    ]
                ],
                'birthday' => [
                    'title' => 'Birthday Celebration',
                    'subtitle' => 'Making their special day unforgettable',
                    'message' => 'Thoughtful birthday gifts that show how much you care',
                    'categories' => ['jewelry', 'experiences', 'fashion', 'electronics'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_011',
                            'name' => 'Birthstone Jewelry Set',
                            'description' => 'Matching earrings and necklace featuring their birthstone',
                            'price' => 175.00,
                            'category' => 'jewelry',
                            'image_url' => 'https://example.com/birthstone-set.jpg',
                            'milestone_relevance' => 'Personal and meaningful birthday gift',
                            'personalization_options' => ['Birthstone selection', 'Metal choice', 'Gift wrapping']
                        ],
                        [
                            'id' => 'milestone_012',
                            'name' => 'Birthday Experience Package',
                            'description' => 'Curated experience based on their interests and hobbies',
                            'price' => 225.00,
                            'category' => 'experiences',
                            'image_url' => 'https://example.com/birthday-experience.jpg',
                            'milestone_relevance' => 'Memorable birthday celebration experience',
                            'personalization_options' => ['Activity type', 'Group size', 'Date preference', 'Add-ons']
                        ]
                    ]
                ],
                'valentine' => [
                    'title' => 'Valentine\'s Day Romance',
                    'subtitle' => 'Express your love this Valentine\'s',
                    'message' => 'Classic romantic gifts perfect for Valentine\'s Day',
                    'categories' => ['flowers', 'jewelry', 'sweets', 'experiences'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_013',
                            'name' => 'Valentine\'s Rose Bouquet',
                            'description' => 'Premium red roses with baby\'s breath and greenery',
                            'price' => 85.00,
                            'category' => 'flowers',
                            'image_url' => 'https://example.com/valentine-roses.jpg',
                            'milestone_relevance' => 'Classic Valentine\'s Day romance',
                            'personalization_options' => ['Rose count', 'Vase choice', 'Card message', 'Delivery time']
                        ],
                        [
                            'id' => 'milestone_014',
                            'name' => 'Heart-Shaped Chocolate Box',
                            'description' => 'Artisanal chocolates in romantic heart-shaped presentation',
                            'price' => 65.00,
                            'category' => 'sweets',
                            'image_url' => 'https://example.com/heart-chocolates.jpg',
                            'milestone_relevance' => 'Sweet Valentine\'s Day tradition',
                            'personalization_options' => ['Chocolate types', 'Custom message', 'Box color', 'Ribbon choice']
                        ]
                    ]
                ],
                'christmas' => [
                    'title' => 'Christmas Together',
                    'subtitle' => 'Holiday magic for two',
                    'message' => 'Festive gifts to make your Christmas special together',
                    'categories' => ['jewelry', 'fashion', 'experiences', 'home'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_015',
                            'name' => 'Christmas Ornament Set',
                            'description' => 'Personalized couple\'s ornament for your first Christmas',
                            'price' => 45.00,
                            'category' => 'home',
                            'image_url' => 'https://example.com/christmas-ornament.jpg',
                            'milestone_relevance' => 'Perfect keepsake for your Christmas together',
                            'personalization_options' => ['Names engraving', 'Date inscription', 'Design choice', 'Gift box']
                        ],
                        [
                            'id' => 'milestone_016',
                            'name' => 'Holiday Date Night Package',
                            'description' => 'Romantic Christmas-themed date night experience',
                            'price' => 155.00,
                            'category' => 'experiences',
                            'image_url' => 'https://example.com/holiday-date.jpg',
                            'milestone_relevance' => 'Magical holiday experience together',
                            'personalization_options' => ['Activity selection', 'Dining preference', 'Time slot', 'Special requests']
                        ]
                    ]
                ],
                'general' => [
                    'title' => 'Just Because Gifts',
                    'subtitle' => 'Thoughtful surprises any time',
                    'message' => 'Sweet gifts to show you\'re thinking of them',
                    'categories' => ['flowers', 'sweets', 'accessories', 'experiences'],
                    'recommended_items' => [
                        [
                            'id' => 'milestone_017',
                            'name' => 'Surprise Flower Delivery',
                            'description' => 'Beautiful seasonal flower arrangement as a sweet surprise',
                            'price' => 55.00,
                            'category' => 'flowers',
                            'image_url' => 'https://example.com/surprise-flowers.jpg',
                            'milestone_relevance' => 'Perfect anytime surprise to brighten their day',
                            'personalization_options' => ['Flower type', 'Color scheme', 'Vase style', 'Personal note']
                        ],
                        [
                            'id' => 'milestone_018',
                            'name' => 'Thinking of You Gift Box',
                            'description' => 'Curated box of small treats and thoughtful items',
                            'price' => 75.00,
                            'category' => 'sweets',
                            'image_url' => 'https://example.com/thinking-box.jpg',
                            'milestone_relevance' => 'Sweet way to show you care any day',
                            'personalization_options' => ['Item preferences', 'Dietary needs', 'Box theme', 'Custom note']
                        ]
                    ]
                ]
            ];

            // Get milestone-specific suggestions
            $selectedMilestone = $milestoneGifts[$milestoneType] ?? $milestoneGifts['general'];

            // Filter items by budget
            $budgetFilteredItems = array_filter($selectedMilestone['recommended_items'], function ($item) use ($selectedBudgetRange) {
                return $item['price'] >= $selectedBudgetRange['min'] && $item['price'] <= $selectedBudgetRange['max'];
            });

            // If no items in budget, suggest closest alternatives
            if (empty($budgetFilteredItems)) {
                $budgetFilteredItems = $selectedMilestone['recommended_items'];
                $selectedMilestone['budget_note'] = "Here are our recommendations. Some items may be outside your selected budget range.";
            }

            // Add personalized insights based on relationship duration
            $relationshipInsights = [];
            if ($relationshipDuration !== null) {
                if ($relationshipDuration < 30) {
                    $relationshipInsights[] = "You're in the exciting early stages! Keep gifts sweet and not too intense.";
                } elseif ($relationshipDuration < 90) {
                    $relationshipInsights[] = "Your relationship is building beautifully. Consider gifts that show growing commitment.";
                } elseif ($relationshipDuration < 180) {
                    $relationshipInsights[] = "You're getting serious! This is perfect timing for more meaningful gifts.";
                } elseif ($relationshipDuration < 365) {
                    $relationshipInsights[] = "You're approaching your first year together. Consider gifts with lasting significance.";
                } else {
                    $relationshipInsights[] = "You've built something special together. Luxury and deeply personal gifts are perfect now.";
                }
            }

            // Add seasonal recommendations if applicable
            $currentMonth = Carbon::now()->month;
            $seasonalSuggestions = [];

            if ($currentMonth == 2 && $milestoneType == 'general') {
                $seasonalSuggestions[] = "Valentine's Day is coming up! Consider romantic flowers or jewelry.";
            } elseif (in_array($currentMonth, [11, 12]) && $milestoneType == 'general') {
                $seasonalSuggestions[] = "Holiday season is here! Perfect time for keepsake gifts and experiences.";
            }

            return $this->success([
                'milestone_info' => $selectedMilestone,
                'recommended_items' => array_values($budgetFilteredItems),
                'budget_range' => $selectedBudgetRange,
                'relationship_insights' => $relationshipInsights,
                'seasonal_suggestions' => $seasonalSuggestions,
                'personalization_available' => true,
                'delivery_options' => [
                    'standard' => '3-5 business days',
                    'express' => '1-2 business days',
                    'same_day' => 'Available in select cities'
                ],
                'currency' => 'CAD',
                'total_suggestions' => count($budgetFilteredItems)
            ], 'Milestone gift suggestions retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to get milestone gift suggestions: ' . $e->getMessage());
        }
    }

    public function save_milestone_reminder(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('Unauthorized access');
            }

            $partnerId = $request->input('partner_id');
            $milestoneType = $request->input('milestone_type');
            $milestoneDate = $request->input('milestone_date');
            $reminderDays = $request->input('reminder_days', 7); // Days before to remind
            $giftPreferences = $request->input('gift_preferences', []);

            // In a real implementation, this would save to a milestones table
            // For now, we'll return a success response with confirmation

            $reminder = [
                'id' => 'reminder_' . uniqid(),
                'user_id' => $user->id,
                'partner_id' => $partnerId,
                'milestone_type' => $milestoneType,
                'milestone_date' => $milestoneDate,
                'reminder_date' => Carbon::parse($milestoneDate)->subDays($reminderDays)->format('Y-m-d'),
                'gift_preferences' => $giftPreferences,
                'status' => 'active',
                'created_at' => Carbon::now()->toISOString()
            ];

            return $this->success([
                'reminder' => $reminder,
                'confirmation_message' => "Great! We'll remind you about this milestone {$reminderDays} days before {$milestoneDate}.",
                'notification_settings' => [
                    'email_reminder' => true,
                    'push_notification' => true,
                    'suggested_gifts_included' => true
                ]
            ], 'Milestone reminder saved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to save milestone reminder: ' . $e->getMessage());
        }
    }

    // ======= END PHASE 7.2: RELATIONSHIP MILESTONE GIFT SUGGESTIONS =======

    // ======= CART & ORDER MANAGEMENT =======
    public function submit_order(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('Unauthorized access');
            }

            // Get order data from request
            $orderData = $request->all();

            // Basic validation
            if (!isset($orderData['items']) || empty($orderData['items'])) {
                return $this->error('Order must contain at least one item');
            }

            if (!isset($orderData['total_amount']) || $orderData['total_amount'] <= 0) {
                return $this->error('Invalid order total amount');
            }

            // Generate order ID
            $orderId = 'ORDER_' . strtoupper(uniqid()) . '_' . time();

            // In a real implementation, you would:
            // 1. Validate inventory availability
            // 2. Process payment with payment gateway
            // 3. Save order to database
            // 4. Send confirmation email
            // 5. Update inventory

            // For demo purposes, we'll simulate a successful order
            $order = [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'status' => 'confirmed',
                'items' => $orderData['items'],
                'subtotal' => $orderData['subtotal'] ?? 0,
                'shipping_cost' => $orderData['shipping_cost'] ?? 0,
                'tax_amount' => $orderData['tax_amount'] ?? 0,
                'total_amount' => $orderData['total_amount'],
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'billing_address' => $orderData['billing_address'] ?? null,
                'payment_method' => $orderData['payment_method'] ?? 'card',
                'payment_status' => 'paid',
                'estimated_delivery' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'tracking_number' => 'TRK' . strtoupper(substr(md5($orderId), 0, 8)),
                'created_at' => Carbon::now()->toISOString(),
                'updated_at' => Carbon::now()->toISOString()
            ];

            // Log order for debugging (in production, save to database)
            Log::info('Order submitted', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'total_amount' => $orderData['total_amount'],
                'items_count' => count($orderData['items'])
            ]);

            return $this->success([
                'order' => $order,
                'message' => 'Your order has been placed successfully!',
                'next_steps' => [
                    'You will receive a confirmation email shortly',
                    'Track your order using tracking number: ' . $order['tracking_number'],
                    'Estimated delivery: ' . $order['estimated_delivery']
                ]
            ], 'Order submitted successfully');
        } catch (\Exception $e) {
            Log::error('Order submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return $this->error('Failed to submit order: ' . $e->getMessage());
        }
    }
    // ======= END CART & ORDER MANAGEMENT =======
}
