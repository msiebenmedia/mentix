<!DOCTYPE html>
<html lang="de" data-theme="mentix">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Quiztime') }} - @yield('title')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <style>
    [x-cloak] { display: none !important; }
</style>
</head>
<body class="min-h-screen bg-base-200 text-base-content">

<div id="dashboard-shell" class="drawer lg:drawer-open">
    <input id="dashboard-drawer" type="checkbox" class="drawer-toggle" />

    <div class="drawer-content flex flex-col min-h-screen">
        {{-- Topbar --}}
<div class="navbar sticky top-0 z-30 border-b border-base-300/60 bg-base-100/70 backdrop-blur-md supports-[backdrop-filter]:bg-base-100/60">
            <div class="flex-none flex items-center gap-2">
                {{-- Mobile Toggle --}}
                <label for="dashboard-drawer" class="btn btn-square btn-ghost lg:hidden">
                    <i class="ti ti-menu-2 text-xl"></i>
                </label>

                {{-- Desktop Toggle --}}
                <button type="button" id="sidebar-toggle" class="btn btn-square btn-ghost hidden lg:inline-flex">
                    <i id="sidebar-toggle-icon" class="ti ti-layout-sidebar-left-collapse text-xl"></i>
                </button>
            </div>

            <div class="flex-1">
                <h1 class="text-lg font-semibold px-2">
                    @yield('title', 'Dashboard')
                </h1>
            </div>
        </div>

        {{-- Main Content --}}
        <main class="flex-1 p-4 md:p-6">
            @yield('content')
        </main>
    </div>

    {{-- Sidebar --}}
    <div class="drawer-side z-[80] overflow-visible">
        <label for="dashboard-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

        <aside
            id="dashboard-sidebar"
            class="w-72 min-h-full bg-base-100 border-r border-base-300 flex flex-col transition-all duration-300 overflow-visible relative z-[90]"
        >
            {{-- Logo --}}
            <div class="h-16 px-6 flex items-center border-b border-base-300 bg-base-100">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-content shrink-0">
                        <i class="ti ti-bolt text-xl"></i>
                    </div>

                    <div class="sidebar-label min-w-0">
                        <div class="text-sm opacity-70 leading-none">Pandaric</div>
                        <div class="text-lg font-semibold leading-none mt-1">{{ config('app.name', 'Quiztime') }}</div>
                    </div>
                </a>
            </div>

            {{-- Navigation --}}
            <div class="flex-1 p-4 overflow-visible">
                <ul class="menu gap-1  overflow-visible">


                    <li>
                        <a
                            href="{{ route('dashboard') }}"
                            class="sidebar-link tooltip tooltip-right {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            data-tip="Dashboard"
                        >
                            <i class="ti ti-layout-dashboard text-lg shrink-0"></i>
                            <span class="sidebar-label">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="{{ route('profile.edit') }}"
                            class="sidebar-link tooltip tooltip-right {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
                            data-tip="Profil"
                        >
                            <i class="ti ti-user text-lg shrink-0"></i>
                            <span class="sidebar-label">Profil</span>
                        </a>
                    </li>
@role('admin')
<li>
    <a href="{{ route('admin.users.index') }}"
                                class="sidebar-link tooltip tooltip-right {{ request()->routeIs('admin.users.index') ? 'active' : '' }}"
                            data-tip="Rollenverwaltung"
                        >
                            <i class="ti ti-shield text-lg shrink-0"></i>
                            <span class="sidebar-label">Userverwaltung</span>
    </a>
</li>
<li>
    <a href="{{ route('admin.question-catalogs.index') }}"
                                class="sidebar-link tooltip tooltip-right {{ request()->routeIs('admin.question-catalogs.index') ? 'active' : '' }}"
                            data-tip="Fragensets"
                        >
                            <i class="ti ti-library text-lg shrink-0"></i>
                            <span class="sidebar-label">Fragensets</span>
    </a>
</li>
<li>
    <a href="{{ route('admin.questions.index') }}"
                                class="sidebar-link tooltip tooltip-right {{ request()->routeIs('admin.questions.index') ? 'active' : '' }}"
                            data-tip="Fragen"
                        >
                            <i class="ti ti-help-octagon text-lg shrink-0"></i>
                            <span class="sidebar-label">Fragen</span>
    </a>
</li>
<li>
    <a href="{{ route('admin.quizzes.index') }}"
                                class="sidebar-link tooltip tooltip-right {{ request()->routeIs('admin.quizzes.index') ? 'active' : '' }}"
                            data-tip="Fragen"
                        >
                            <i class="ti ti-help-octagon text-lg shrink-0"></i>
                            <span class="sidebar-label">Quizverwaltung</span>
    </a>
</li>
@endrole
                </ul>
            </div>

            {{-- Footer --}}
            <div class="p-4 border-t border-base-300">
                <div class="rounded-2xl bg-base-200 p-4 text-sm">
                    <div class="sidebar-label">
                        <div class="font-semibold mb-1">Schnellinfo</div>
                        <div class="text-xs opacity-70">
                            Angemeldet als {{ auth()->user()->username ?? auth()->user()->name }}
                        </div>
                    </div>

                    <div class="sidebar-mini-icon hidden text-center">
                        <i class="ti ti-user text-xl"></i>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<x-fab />

<style>
#dashboard-sidebar .sidebar-link {
    width: 100%;
    min-height: 2.4rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    text-align: left;
}

#dashboard-sidebar .sidebar-link > i,
#dashboard-sidebar .sidebar-link .flex > i {
    width: 1.25rem;
    text-align: center;
}

#dashboard-sidebar .sidebar-link .dropdown-chevron {
    margin-left: auto;
}

    #dashboard-sidebar .sidebar-link > i,
    #dashboard-sidebar .sidebar-link .flex > i {
        width: 1.25rem;
        text-align: center;
    }

    #dashboard-sidebar .menu li > a.active {
        background-color: color-mix(in oklab, var(--color-primary) 12%, transparent);
        color: var(--color-primary);
        font-weight: 600;
    }

    #dashboard-sidebar .menu li > a:hover,
    #dashboard-sidebar .menu li > details > summary:hover {
        background-color: color-mix(in oklab, var(--color-base-content) 6%, transparent);
    }

    #dashboard-sidebar details ul {
        margin-top: 0.35rem;
        padding-left: 0.5rem;
    }

    #dashboard-sidebar details ul li > a {
        min-height: 2.2rem;
        padding: 0.45rem 0.75rem;
        border-radius: 0.6rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    #dashboard-sidebar details ul li > a i {
        width: 1.1rem;
        text-align: center;
    }

    #dashboard-sidebar .dropdown-chevron {
        transition: transform 0.2s ease;
        font-size: 1rem;
        opacity: 0.7;
    }

    #dashboard-sidebar details[open] > summary .dropdown-chevron {
        transform: rotate(180deg);
    }

    @media (min-width: 1024px) {
        #dashboard-shell.sidebar-collapsed #dashboard-sidebar {
            width: 5rem;
        }

        #dashboard-shell.sidebar-collapsed .sidebar-label {
            display: none;
        }

        #dashboard-shell.sidebar-collapsed .sidebar-mini-icon {
            display: block;
        }

        #dashboard-shell:not(.sidebar-collapsed) .sidebar-mini-icon {
            display: none;
        }

        #dashboard-shell.sidebar-collapsed .menu-title {
            display: none;
        }

        #dashboard-shell.sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            gap: 0;
        }

        #dashboard-shell.sidebar-collapsed .sidebar-dropdown details ul {
            display: none !important;
        }

        #dashboard-shell:not(.sidebar-collapsed) .tooltip::before,
        #dashboard-shell:not(.sidebar-collapsed) .tooltip::after {
            display: none !important;
        }

        #dashboard-shell.sidebar-collapsed .tooltip::before,
        #dashboard-shell.sidebar-collapsed .tooltip::after {
            z-index: 9999 !important;
        }
    }
</style>
<style>
    #dashboard-sidebar .menu > li,
    #dashboard-sidebar .menu > li > a,
    #dashboard-sidebar .menu > li > details,
    #dashboard-sidebar .menu > li > details > summary,
    #dashboard-sidebar .menu li ul li,
    #dashboard-sidebar .menu li ul li > a {
        width: 100%;
    }

    #dashboard-sidebar .sidebar-link {
        width: 100%;
        min-height: 2.4rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.75rem;
        cursor: pointer;
        text-align: left;
        box-sizing: border-box;
    }

    #dashboard-sidebar .sidebar-link > i,
    #dashboard-sidebar .sidebar-link .flex > i {
        width: 1.25rem;
        text-align: center;
    }

    #dashboard-sidebar .sidebar-link .dropdown-chevron {
        margin-left: auto;
    }

    #dashboard-sidebar details ul {
        margin-top: 0.35rem;
        padding-left: 0.5rem;
        width: 100%;
        box-sizing: border-box;
    }

    #dashboard-sidebar details ul li > a {
        width: 100%;
        min-height: 2.2rem;
        padding: 0.45rem 0.75rem;
        border-radius: 0.6rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        box-sizing: border-box;
    }
        #dashboard-sidebar .menu {
        width: 100%;
    }
</style>
<script>
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    }

    (() => {
        const savedTheme = localStorage.getItem('theme') || 'mentix';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();

    (() => {
        const shell = document.getElementById('dashboard-shell');
        const toggle = document.getElementById('sidebar-toggle');
        const icon = document.getElementById('sidebar-toggle-icon');
        const storageKey = 'dashboard-sidebar-collapsed';

        function updateIcon() {
            if (!icon) return;

            if (shell.classList.contains('sidebar-collapsed')) {
                icon.className = 'ti ti-layout-sidebar-left-expand text-xl';
            } else {
                icon.className = 'ti ti-layout-sidebar-left-collapse text-xl';
            }
        }

        function expandSidebar() {
            if (window.innerWidth < 1024) return;

            shell.classList.remove('sidebar-collapsed');
            localStorage.setItem(storageKey, 'false');
            updateIcon();
        }

        function collapseSidebar() {
            if (window.innerWidth < 1024) return;

            shell.classList.add('sidebar-collapsed');
            localStorage.setItem(storageKey, 'true');
            updateIcon();
        }

        function applyState() {
            if (window.innerWidth < 1024) {
                shell.classList.remove('sidebar-collapsed');
                updateIcon();
                return;
            }

            const collapsed = localStorage.getItem(storageKey) === 'true';

            if (collapsed) {
                collapseSidebar();
            } else {
                expandSidebar();
            }
        }

        applyState();

        toggle?.addEventListener('click', () => {
            if (window.innerWidth < 1024) return;

            const collapsed = shell.classList.contains('sidebar-collapsed');

            if (collapsed) {
                expandSidebar();
            } else {
                collapseSidebar();
            }
        });

        // WICHTIG:
        // Wenn die Sidebar eingeklappt ist und auf ein Dropdown geklickt wird,
        // dann zuerst die GESAMTE Sidebar ausklappen.
        document.querySelectorAll('#dashboard-sidebar .sidebar-dropdown summary').forEach(summary => {
            summary.addEventListener('click', (e) => {
                if (window.innerWidth < 1024) return;

                if (shell.classList.contains('sidebar-collapsed')) {
                    e.preventDefault(); // verhindert das direkte Öffnen des details im eingeklappten Zustand
                    expandSidebar();

                    // nach dem Ausklappen das Dropdown direkt öffnen
                    const details = summary.closest('details');
                    if (details) {
                        details.open = true;
                    }
                }
            });
        });

        window.addEventListener('resize', applyState);
    })();
</script>
</body>
</html>