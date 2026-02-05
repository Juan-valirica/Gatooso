<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Board</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <script defer src="{{ asset('js/dashboard.js') }}"></script>
</head>
<body>

<!-- HEADER STORIES -->
<header id="storiesBar">
    <div class="stories">
        @for ($i = 1; $i <= 12; $i++)
            <div class="story">
                <img src="https://i.pravatar.cc/100?img={{ $i }}" alt="User">
            </div>
        @endfor
    </div>
</header>

<!-- GRID -->
<main class="grid-container">
    @for ($i = 1; $i <= 40; $i++)
        <div class="grid-item">
            <img src="https://picsum.photos/600/600?random={{ $i }}" alt="Media">
            @if($i % 5 === 0)
                <span class="video-indicator">▶</span>
            @endif
        </div>
    @endfor
</main>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <button class="nav-btn">🏠</button>
    <button class="nav-btn">🕘</button>

    <button class="fab">＋</button>

    <button class="nav-btn">👥</button>
    <button class="nav-btn">ℹ️</button>
</nav>

<!-- ADD MEDIA MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <button>📸 Subir foto</button>
        <button>🎥 Subir video</button>
        <button class="close">Cancelar</button>
    </div>
</div>

</body>
</html>
