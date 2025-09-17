<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function verify(Request $request)
    {
        // Facebook webhook verification
        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === env('FB_VERIFY_TOKEN')
        ) {
            return response($request->get('hub_challenge'), 200);
        }
        return response('Invalid verification token', 403);
    }

    public function handle(Request $request)
    {
        $data = $request->all();
        \Log::info('Messenger Webhook Data:', $data);

        if (!isset($data['entry'][0]['messaging'][0]['message'])) {
            return response('No message', 200);
        }

        $senderId = $data['entry'][0]['messaging'][0]['sender']['id'];
        $message = $data['entry'][0]['messaging'][0]['message']['text'] ?? '';

        // Ignore echo messages from FB
        if (isset($data['entry'][0]['messaging'][0]['message']['is_echo'])) {
            return response('Echo ignored', 200);
        }

        // Check conversation mode
        $mode = Cache::get("conversation_mode_$senderId", 'idle');
        $step = Cache::get("order_step_$senderId", null);

        if ($mode !== 'order') {
            // User has not started order yet
            if (strtolower(trim($message)) === 'yes') {
                Cache::put("conversation_mode_$senderId", 'order');
                Cache::put("order_step_$senderId", 'name');
                $this->sendMessage($senderId, "✅ Great! Let's start your order. What's your name?");
            }
            //  else {
            //     $this->sendMessage($senderId, "Hi! If you want to place an order, type 'order'.");
            // }
            return response('EVENT_RECEIVED', 200);
        }

        // If mode is 'order', continue order steps
        switch ($step) {
            case 'name':
                Cache::put("order_name_$senderId", $message);
                Cache::put("order_step_$senderId", 'phone');
                $this->sendMessage($senderId, "Thanks! Please enter your phone number:");
                break;

            case 'phone':
                Cache::put("order_phone_$senderId", $message);
                Cache::put("order_step_$senderId", 'address');
                $this->sendMessage($senderId, "Got it! Please enter your shipping address:");
                break;

            case 'address':
                Cache::put("order_address_$senderId", $message);
                Cache::put("order_step_$senderId", 'product');
                $this->sendMessage($senderId, "Great! Which product would you like to order?");
                break;

            case 'product':
                Cache::put("order_product_$senderId", $message);

                // Save order in database
                Order::create([
                    'customer_name' => Cache::get("order_name_$senderId"),
                    'phone' => Cache::get("order_phone_$senderId"),
                    'address' => Cache::get("order_address_$senderId"),
                    'product' => Cache::get("order_product_$senderId"),
                    'status' => 'pending',
                ]);

                // Clear all cache
                Cache::forget("order_name_$senderId");
                Cache::forget("order_phone_$senderId");
                Cache::forget("order_address_$senderId");
                Cache::forget("order_product_$senderId");
                Cache::forget("order_step_$senderId");
                Cache::forget("conversation_mode_$senderId");

                $this->sendMessage($senderId, "✅ Thank you! Your order has been placed successfully.");
                break;
        }

        return response('EVENT_RECEIVED', 200);
    }

    // Send message to Messenger user
    private function sendMessage($recipientId, $message)
    {
        $token = env('FB_PAGE_ACCESS_TOKEN');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://graph.facebook.com/v17.0/me/messages?access_token={$token}", [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message]
        ]);

        \Log::info('FB Send Response:', $response->json());
    }
}
