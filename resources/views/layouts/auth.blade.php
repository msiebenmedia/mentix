<!DOCTYPE html>
<html lang="de" data-theme="mentix">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Quiztime') }} - @yield('title')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center px-4">

@yield('content')

<x-fab />

</body>
</html>