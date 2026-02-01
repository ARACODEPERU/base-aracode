<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Elite Cup 2024 | Torneo Oficial</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .text-glow { text-shadow: 0 0 15px rgba(59, 130, 246, 0.5); }
            .bg-card { background: rgba(30, 41, 59, 0.5); }
        </style>
    </head>
    <body class="bg-[#0b0f1a] text-white selection:bg-blue-500/30">

        <!-- Header / Nav -->
        <header class="fixed w-full z-50 bg-[#0b0f1a]/80 backdrop-blur-xl border-b border-white/5">
            <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <i class="fas fa-trophy text-white"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter uppercase italic">Elite<span class="text-blue-500">Cup</span></span>
                </div>

                <nav class="hidden md:flex gap-8 text-sm font-bold uppercase tracking-widest text-gray-400">
                    <a href="#inicio" class="hover:text-blue-500 transition-colors">Inicio</a>
                    <a href="#equipos" class="hover:text-blue-500 transition-colors">Equipos</a>
                    <a href="#fixture" class="hover:text-blue-500 transition-colors">Fixture</a>
                    <a href="#posiciones" class="hover:text-blue-500 transition-colors">Posiciones</a>
                </nav>

                <a href="#contacto" class="bg-blue-600 hover:bg-blue-700 px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-widest transition shadow-lg shadow-blue-600/20">
                    Contacto
                </a>
            </div>
        </header>

        <!-- SECTION 1: HERO -->
        <section id="inicio" class="relative min-h-screen flex items-center pt-20 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=2071" class="w-full h-full object-cover opacity-20" alt="background">
                <div class="absolute inset-0 bg-gradient-to-b from-[#0b0f1a] via-transparent to-[#0b0f1a]"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
                        {{ $edition->name }}
                    </span>
                    <h1 class="text-6xl lg:text-4xl font-black leading-none mb-6">{{ $edition->evento->title }}</h1>
                    <p class="text-gray-400 text-lg max-w-xl mb-10 leading-relaxed mx-auto lg:mx-0">
                        {!! $edition->evento->description !!}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="#contacto" class="px-10 py-4 bg-blue-600 hover:bg-blue-700 rounded-2xl font-black uppercase tracking-widest transition-all hover:scale-105 shadow-xl shadow-blue-600/20 text-center">Ver Contacto</a>
                        <a href="#posiciones" class="px-10 py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl font-black uppercase tracking-widest transition text-center">Ver Posiciones</a>
                    </div>
                </div>
                <!-- Tournament Stats Mini-Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-card border border-white/5 p-8 rounded-3xl backdrop-blur-sm">
                        <i class="fas fa-users text-blue-500 text-2xl mb-4"></i>
                        <div class="text-3xl font-black">{{ count($edition->equipos) }}</div>
                        <div class="text-xs text-gray-500 uppercase font-bold tracking-widest">Equipos</div>
                    </div>
                    <div class="bg-card border border-white/5 p-8 rounded-3xl backdrop-blur-sm mt-8">
                        <i class="fas fa-dollar-sign text-blue-500 text-2xl mb-4"></i>
                        <div class="text-3xl font-black">{{ $edition->inscription_fee }}</div>
                        <div class="text-xs text-gray-500 uppercase font-bold tracking-widest">Premios</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: EQUIPOS -->
        <section id="equipos" class="py-24 bg-[#0d1321]">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <h2 class="text-4xl font-black mb-16 uppercase italic">Equipos <span class="text-blue-500">Participantes</span></h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <!-- Team card repeats -->
                    @foreach ($edition->equipos as $equipo)
                        <div class="p-6 bg-[#161d2f] border border-white/5 rounded-3xl hover:border-blue-500/50 transition group">
                            <div class="w-16 h-16 mx-auto mb-4 bg-slate-900 rounded-2xl flex items-center justify-center group-hover:scale-110 transition">
                                @if($equipo->equipo->logo_path)
                                    <img src="{{ asset('storage/'. $equipo->equipo->logo_path ) }}" alt="{{ $equipo->equipo->name }}" class="w-full h-full object-cover rounded-2xl" />
                                @else
                                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                                @endif
                            </div>
                            <div class="text-sm font-black uppercase tracking-tighter">{{ $equipo->equipo->name }}</div>
                        </div>
                    @endforeach
                    <!-- Agrega más según necesites -->
                </div>
            </div>
        </section>

        <!-- SECTION 3: FIXTURE (Calendario) -->
        <section id="fixture" class="py-24">
            <div class="max-w-4xl mx-auto px-6">
                <div class="flex justify-between items-end mb-12">
                    <h2 class="text-4xl font-black uppercase italic">Próximos <span class="text-blue-500">Encuentros</span></h2>
                    <span class="text-blue-500 font-bold text-sm tracking-widest uppercase">Semana 1</span>
                </div>

                <div class="space-y-12">
                    @php
                        $translations = [
                            'league' => 'Fase de Grupos / Liga',
                            'quarterfinals' => 'Cuartos de Final',
                            'semifinals' => 'Semifinales',
                            'third_place' => 'Tercer Puesto',
                            'final' => 'Gran Final'
                        ];
                    @endphp
                    @foreach($matches as $phase => $rounds)
                        <div>
                            <h2 class="text-2xl font-black uppercase mb-6 text-gray-800 border-l-4 border-red-600 pl-4 dark:text-white">
                                {{ $translations[$phase] ?? $phase }}
                            </h2>
                            <div class="space-y-4">
                                @foreach($rounds as $round => $roundMatches)
                                    <details class="bg-[#0d1321] border border-white/5 rounded-3xl overflow-hidden">
                                        <summary class="cursor-pointer p-6 text-lg font-black uppercase tracking-widest text-blue-500 hover:text-blue-400 transition flex items-center justify-between">
                                            Fecha {{ $round }}
                                            <i class="fas fa-chevron-down text-blue-500 transition-transform duration-200"></i>
                                        </summary>
                                        <div class="px-6 pb-6 space-y-4">
                                            @foreach($roundMatches as $match)
                                                <div class="p-6 bg-white/5 border border-white/5 rounded-3xl flex justify-between items-center group hover:bg-white/10 transition">
                                                    <div class="flex items-center gap-4 flex-1">
                                                        @if($match->equipolocal && $match->equipolocal)
                                                            @if($match->equipolocal->logo_path)
                                                                <img src="{{ asset('storage/'. $match->equipolocal->logo_path) }}" alt="{{ $match->equipolocal->name }}" class="w-8 h-8 rounded-lg" />
                                                            @else
                                                                <i class="fas fa-shield-alt text-gray-400 text-xl"></i>
                                                            @endif
                                                            <span class="font-bold uppercase tracking-tighter">{{ $match->equipolocal->name }}</span>
                                                        @else
                                                            <i class="fas fa-question text-gray-400 text-xl"></i>
                                                            <span class="font-bold uppercase tracking-tighter text-gray-400">Por definir</span>
                                                        @endif
                                                    </div>
                                                     <div class="flex flex-col items-center flex-none px-8">
                                                         @if(in_array($match->status, ['finished', 'closed']))
                                                             <div class="text-2xl font-black italic mb-1">{{ $match->score_h ?? 0 }} - {{ $match->score_a ?? 0 }}</div>
                                                             <div class="text-[10px] text-green-400 font-bold uppercase tracking-widest">Finalizado</div>
                                                         @elseif($match->status == 'live')
                                                             <div class="text-2xl font-black italic mb-1 text-red-500">EN VIVO</div>
                                                             <div class="text-[10px] text-red-400 font-bold uppercase tracking-widest">Jugando</div>
                                                         @else
                                                             <span class="text-xs font-black text-gray-600">VS</span>
                                                             <span class="text-sm font-black text-blue-500">{{ $match->match_date ? $match->match_date->format('d/m H:i') : 'Fecha por definir' }}</span>
                                                         @endif
                                                     </div>
                                                    <div class="flex items-center gap-4 flex-1 justify-end">
                                                        @if($match->equipovisitante)
                                                            <span class="font-bold uppercase tracking-tighter">{{ $match->equipovisitante->name }}</span>
                                                            @if($match->equipovisitante->logo_path)
                                                                <img src="{{ asset('storage/'. $match->equipovisitante->logo_path) }}" alt="{{ $match->equipovisitante->name }}" class="w-8 h-8 rounded-lg" />
                                                            @else
                                                                <i class="fas fa-shield-alt text-yellow-500 text-xl"></i>
                                                            @endif
                                                        @else
                                                            <span class="font-bold uppercase tracking-tighter text-gray-400">Por definir</span>
                                                            <i class="fas fa-question text-gray-400 text-xl"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- SECTION 4: POSICIONES & RANKING -->
        <section id="posiciones" class="py-24 bg-[#0d1321]">
            <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-3 gap-12">

                <!-- Tabla de Posiciones -->
                <div class="lg:col-span-2">
                    <h3 class="text-2xl font-black uppercase italic mb-8">Tabla de <span class="text-blue-500">Posiciones</span></h3>
                    <div class="overflow-x-auto bg-[#161d2f] border border-white/5 rounded-3xl">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <tr>
                                    <th class="p-5">Rk</th>
                                    <th class="p-5">Equipo</th>
                                    <th class="p-5 text-center">PTS</th>
                                    <th class="p-5 text-center">PJ</th>
                                    <th class="p-5 text-center">PG</th>
                                    <th class="p-5 text-center">PE</th>
                                    <th class="p-5 text-center">PP</th>
                                    <th class="p-5 text-center">DG</th>
                                    <th class="p-5 text-center">GF</th>
                                    <th class="p-5 text-center">GC</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-bold">
                                @foreach ($currentEquipment as $index => $team)
                                <tr class="border-t border-white/5 hover:bg-white/5 transition">
                                    <td class="p-5 {{ $index === 0 ? 'text-blue-500' : 'text-gray-500' }}">#{{ $index + 1 }}</td>
                                    <td class="p-5 flex items-center gap-3 {{ $index > 2 ? 'text-gray-300' : '' }}">
                                        @if($team->equipo->logo_path)
                                            <img src="{{ asset('storage/' . $team->equipo->logo_path) }}" alt="{{ $team->equipo->name }}" class="w-8 h-8 rounded-lg" />
                                        @else
                                            <i class="fas fa-shield-alt text-gray-400 text-lg"></i>
                                        @endif
                                        {{ $team->equipo->name }}
                                    </td>
                                    <td class="p-5 text-center text-lg font-black italic text-blue-400">{{ $team->points }}</td>
                                    <td class="p-5 text-center">{{ $team->matches_played }}</td>
                                    <td class="p-5 text-center text-green-400">{{ $team->matches_won }}</td>
                                    <td class="p-5 text-center text-yellow-400">{{ $team->matches_drawn }}</td>
                                    <td class="p-5 text-center text-red-400">{{ $team->matches_lost }}</td>
                                    <td class="p-5 text-center {{ $team->goal_difference >= 0 ? 'text-green-400' : 'text-red-400' }}">{{ $team->goal_difference }}</td>
                                    <td class="p-5 text-center">{{ $team->goals_for }}</td>
                                    <td class="p-5 text-center">{{ $team->goals_against }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <!-- Jugadores Ranking -->
                    <div>
                        <h3 class="text-2xl font-black uppercase italic mb-8">Jugadores <span class="text-blue-500">Top</span></h3>
                        <div class="space-y-4">
                        @foreach($playersRanking as $index => $player)
                            <div class="p-4 {{ $index === 0 ? 'bg-gradient-to-r from-blue-600/20 to-transparent border border-blue-500/20' : 'bg-white/5 border border-white/5' }} rounded-2xl flex items-center gap-4">
                                <div class="relative">
                                    @if($player['player']['person']->image)
                                        <img src="{{ asset('storage/' . $player['player']['person']->image) }}" class="w-12 h-12 rounded-full border-2 {{ $index === 0 ? 'border-blue-500' : 'border-gray-500' }} p-0.5" alt="player">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ $player['player']['person']->full_name }}" class="w-12 h-12 rounded-full border-2 {{ $index === 0 ? 'border-blue-500' : 'border-gray-500' }} p-0.5" alt="player">
                                    @endif
                                    <span class="absolute -bottom-1 -right-1 w-5 h-5 {{ $index === 0 ? 'bg-blue-600' : 'bg-gray-600' }} rounded-full flex items-center justify-center text-[10px] font-black">{{ $index + 1 }}</span>
                                </div>
                                <div>
                                    <div class="font-black tracking-tight leading-none uppercase">{{ $player['player']['person']->full_name }}</div>
                                    <div class="text-[10px] {{ $index === 0 ? 'text-blue-400' : 'text-gray-400' }} font-bold uppercase tracking-widest mt-1">
                                        {{ $player['stats']['goals'] }} Goles, {{ $player['stats']['assists'] }} Asistencias, {{ $player['stats']['mvp'] }} MVP
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>

                    <!-- Porteros Ranking -->
                    <div class="mt-12">
                        <h3 class="text-2xl font-black uppercase italic mb-8">Porteros <span class="text-blue-500">Top</span></h3>
                        <div class="space-y-4">
                        @foreach($goalkeepersRanking as $index => $gk)
                            <div class="p-4 {{ $index === 0 ? 'bg-gradient-to-r from-green-600/20 to-transparent border border-green-500/20' : 'bg-white/5 border border-white/5' }} rounded-2xl flex items-center gap-4">
                                <div class="relative">
                                    @if($gk['player']['person']->image)
                                        <img src="{{ asset('storage/' . $gk['player']['person']->image) }}" class="w-12 h-12 rounded-full border-2 {{ $index === 0 ? 'border-green-500' : 'border-gray-500' }} p-0.5" alt="goalkeeper">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ $gk['player']['person']->full_name }}" class="w-12 h-12 rounded-full border-2 {{ $index === 0 ? 'border-green-500' : 'border-gray-500' }} p-0.5" alt="goalkeeper">
                                    @endif
                                    <span class="absolute -bottom-1 -right-1 w-5 h-5 {{ $index === 0 ? 'bg-green-600' : 'bg-gray-600' }} rounded-full flex items-center justify-center text-[10px] font-black">{{ $index + 1 }}</span>
                                </div>
                                <div>
                                    <div class="font-black tracking-tight leading-none uppercase">{{ $gk['player']['person']->full_name }}</div>
                                    <div class="text-[10px] {{ $index === 0 ? 'text-green-400' : 'text-gray-400' }} font-bold uppercase tracking-widest mt-1">
                                        {{ $gk['stats']['saves'] }} Atajadas, {{ $gk['stats']['clean_sheet'] }} Valla Invicta, {{ $gk['stats']['mvp'] }} MVP
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 5: CONTACTO -->
         <section id="contacto" class="py-24 bg-gradient-to-br from-slate-900 to-slate-800">
             <div class="max-w-6xl mx-auto px-6">
                 <div class="text-center mb-16">
                     <h2 class="text-4xl lg:text-5xl font-black mb-8 uppercase italic leading-none text-white">Contacto <span class="text-blue-500">del Torneo</span></h2>
                     <p class="text-gray-300 font-medium text-lg max-w-2xl mx-auto">Para más información sobre el torneo, contacta con el organizador. ¡Estamos aquí para ayudarte!</p>
                 </div>

                 <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                     <!-- Información del Contacto -->
                     <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 text-center hover:bg-white/15 transition-all duration-300">
                         <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-600/30">
                             <i class="fas fa-user text-white text-2xl"></i>
                         </div>
                         <h3 class="text-xl font-black text-white mb-2 uppercase">Nombre del Contacto</h3>
                         <p class="text-blue-300 font-bold text-lg">{{ $edition->contact_name ?? 'Juan Pérez' }}</p>
                         <p class="text-gray-400 text-sm mt-2">Organizador Principal</p>
                     </div>

                     <!-- Teléfono Celular -->
                     <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 text-center hover:bg-white/15 transition-all duration-300">
                         <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-600/30">
                             <i class="fas fa-phone text-white text-2xl"></i>
                         </div>
                         <h3 class="text-xl font-black text-white mb-2 uppercase">Teléfono Celular</h3>
                         <p class="text-green-300 font-bold text-lg">{{ $edition->contact_phone ?? '+51 999 999 999' }}</p>
                         <p class="text-gray-400 text-sm mt-2">Disponible 24/7</p>
                     </div>

                     <!-- Información Adicional del Torneo -->
                     <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-3xl p-8 text-center hover:bg-white/15 transition-all duration-300 md:col-span-2 lg:col-span-1">
                         <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-purple-600/30">
                             <i class="fas fa-info-circle text-white text-2xl"></i>
                         </div>
                         <h3 class="text-xl font-black text-white mb-2 uppercase">Información del Torneo</h3>
                         <div class="text-purple-300 font-bold text-sm space-y-1">
                             <p>Premio: S/ {{ $edition->inscription_fee ?? '500' }}</p>
                             <p>Equipos: {{ count($edition->equipos) }}</p>
                             <p>Edición: {{ $edition->name }}</p>
                         </div>
                     </div>
                 </div>

                 <!-- Llamado a la Acción -->
                 <div class="text-center mt-16">
                     <p class="text-gray-300 mb-6">¿Listo para participar? ¡Contáctanos ahora!</p>
                     <a href="tel:{{ $edition->contact_phone ?? '+51 999 999 999' }}" class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-widest rounded-2xl transition shadow-lg shadow-blue-600/30">
                         <i class="fas fa-phone mr-3"></i> Llamar Ahora
                     </a>
                 </div>
             </div>
         </section>

        <!-- Footer -->
        <footer class="py-12 border-t border-white/5 bg-[#0b0f1a]">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                        <i class="fas fa-trophy text-white text-xs"></i>
                    </div>
                    <span class="text-xl font-black tracking-tighter uppercase italic">Elite<span class="text-blue-500">Cup</span></span>
                </div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    © 2024 Elite Cup Series • Todos los derechos reservados
                </div>
                <div class="flex gap-6 text-gray-400">
                    <a href="#" class="hover:text-white transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fab fa-discord"></i></a>
                    <a href="#" class="hover:text-white transition"><i class="fab fa-twitch"></i></a>
                </div>
            </div>
        </footer>

    </body>
</html>
