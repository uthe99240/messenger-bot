<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\BotManFactory;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    public function handle(Request $request)
    {
        $config = [
            'facebook' => [
                'token' => env('FACEBOOK_TOKEN'),
                'app_secret' => env('FACEBOOK_APP_SECRET'),
                'verification' => env('FACEBOOK_VERIFY_TOKEN'),
            ]
        ];

        DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookDriver::class);
        $botman = BotManFactory::create($config);

        // Sample command
        $botman->hears('hi', function(BotMan $bot) {
            $bot->reply('Hello! 👋 I am your Laravel Messenger bot.');
        });

        $botman->hears('bye', function(BotMan $bot) {
            $bot->reply('Goodbye! Have a great day 🌟');
        });

        $botman->fallback(function(BotMan $bot) {
            $bot->reply("Sorry, I didn't understand that. Try saying 'hi'.");
        });

        $botman->listen();
    }
}
