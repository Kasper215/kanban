<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новая задача на доске</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 32px;
        }
        .task-card {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border-left: 4px solid #6366f1;
        }
        .task-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .task-description {
            color: #475569;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-priority {
            background: #fee2e2;
            color: #ef4444;
        }
        .footer {
            padding: 24px;
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
            background: #f8fafc;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Уведомление Kanban</h1>
        </div>
        <div class="content">
            <h2 style="margin-top: 0; color: #1e293b;">Новая задача создана!</h2>
            <p>Вы получили это сообщение, потому что на вашей доске была создана новая задача.</p>
            
            <div class="task-card">
                <div class="task-title">{{ $task->title }}</div>
                @if($task->description)
                    <div class="task-description">{{ $task->description }}</div>
                @endif
                <div class="badge badge-priority">
                    Приоритет: {{ $task->priority ?? 'Обычный' }}
                </div>
            </div>

            <p>Вы можете просмотреть задачу и внести изменения, перейдя по ссылке ниже:</p>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button">Открыть доску</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </div>
    </div>
</body>
</html>
