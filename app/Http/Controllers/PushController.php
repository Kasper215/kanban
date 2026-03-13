<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\PushSubscription;

class PushController extends Controller
{


    public function subscribe(Request $request)
    {
        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->keys['p256dh'],
                'auth_token' => $request->keys['auth'],
                'content_encoding' => 'aesgcm', // всегда так для Chrome/Android
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }


    public function sendTest()
    {
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:index@crm.your-cashman.com',
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        // ВАЖНО: включаем legacy mode
        $webPush = new WebPush($auth, [
            'timeout' => 20,
            'reuseVAPIDHeaders' => true,
            'automaticPadding' => false,
            'localAuthenticationTag' => false,
            'legacy' => true, // <‑‑ вот это спасает OpenServer
        ]);

        $payload = json_encode([
            'title' => 'Тестовое уведомление',
            'body'  => 'Это пуш всем подписанным клиентам',
            'url'   => '/',
        ]);

        foreach (PushSubscription::all() as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding,
            ]);

            $webPush->sendOneNotification($subscription, $payload);
        }

        return response()->json(['status' => 'sent']);
    }



}
