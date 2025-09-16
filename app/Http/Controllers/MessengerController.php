<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\MessengerMessage;

class MessengerController extends Controller
{
    // Verification endpoint (GET)
    public function verify(Request $request)
    {
        $hubVerifyToken = env('FB_VERIFY_TOKEN', 'faisal');

        Log::info('Verify request:', $request->all());

        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === $hubVerifyToken
        ) {
            return response($request->get('hub_challenge'), 200);
        }

        return response('Invalid verification token', 403);
    }

    // Receive webhook events (POST)
    public function receive(Request $request)
    {
        $data = $request->all();
        Log::info('Messenger Webhook:', $data);

        if (!empty($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                foreach ($entry['messaging'] as $event) {
                    if (isset($event['message'])) {
                        $messageText = $event['message']['text'] ?? '';
                        $senderId = $event['sender']['id'] ?? '';

                        // Save to database
                        MessengerMessage::create([
                            'sender_id' => $senderId,
                            'message' => $messageText,
                        ]);

                        // Send auto-response
                        $this->sendMessage($senderId, "Thanks for your message: $messageText");
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    // Send message back to Messenger user
    private function sendMessage($recipientId, $messageText)
    {
        $pageAccessToken = env('FB_PAGE_ACCESS_TOKEN');

        $url = "https://graph.facebook.com/v16.0/me/messages?access_token={$pageAccessToken}";

        $data = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $messageText],
        ];

        $response = Http::post($url, $data);
        Log::info('Send message response:', $response->json());
    }
}
