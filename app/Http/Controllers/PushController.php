<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\PushSubscription;

class PushController extends Controller
{


    public function subscribe(Request $request)
    {
        $boardUuid = $request->board_uuid ?? Session::get("board_uuid") ?? null;

        $board = Board::query()
            ->where("uuid", $boardUuid)
            ->first();

        $data = $request->subscription;

        PushSubscription::updateOrCreate(
            ['endpoint' => $data->endpoint],
            [
                'board_id'=>$board->id ?? null,
                'public_key' => $data->keys['p256dh'],
                'auth_token' => $data->keys['auth'],
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
