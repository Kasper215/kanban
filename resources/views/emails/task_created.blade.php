<!DOCTYPE html>
<html>
<head>
    <title>Новая задача на доске</title>
</head>
<body>
    <h1>Добавлена новая задача!</h1>
    <p><strong>Заголовок:</strong> {{ $task->title }}</p>
    @if($task->description)
        <p><strong>Описание:</strong> {{ $task->description }}</p>
    @endif
    <p><strong>Приоритет:</strong> {{ $task->priority ?? 'Обычный' }}</p>
</body>
</html>
