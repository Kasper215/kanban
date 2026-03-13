<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ManifestController extends Controller
{
    public function show(Request $request)
    {
        // Берём board_uuid из сессии или параметров
        $boardUuid = session('board_uuid') ?? $request->query('board_uuid');

        $manifest = [
            "id" => $boardUuid ?? Str::uuid(),
            "name" => "KanbanCRM",
            "short_name" => "Kanban",
            "start_url" => $boardUuid
                ? "/board/$boardUuid?source=pwa"
                : "/?source=pwa",
            "scope" =>  $boardUuid
                ? "/board/"
                : "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#1976d2",
            "orientation" => "portrait",
            "icons" => [
                [
                    "src" => "/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => "/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png"
                ],
                [
                    "src" => "/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ],
                [
                    "src" => "/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ]
            ],
            "screenshots" => [
                [
                    "src" => "/screenshots/kanban-wide.png",
                    "sizes" => "1280x720",
                    "type" => "image/png",
                    "form_factor" => "wide"
                ],
                [
                    "src" => "/screenshots/kanban-mobile.png",
                    "sizes" => "375x667",
                    "type" => "image/png"
                ]
            ],
            "description" => "Управляй задачами и проектами в удобной канбан-доске. Создавай колонки, перемещай карточки, отслеживай прогресс и получай уведомления в реальном времени.",
            "categories" => ["productivity", "organization", "project management"],
            "lang" => "ru"
        ];

        return Response::json($manifest);
    }
}
