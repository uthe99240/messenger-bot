<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        if (isset($data['entry'][0]['messaging'][0]['message'])) {
            $message = $data['entry'][0]['messaging'][0]['message']['text'] ?? '';
            $senderId = $data['entry'][0]['messaging'][0]['sender']['id'];

            // Save to DB or do something
            \Log::info("Message from {$senderId}: {$message}");
        }

        return response('EVENT_RECEIVED', 200);
    }
}
