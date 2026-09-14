<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Mensaje - Admin ARACODE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.contact-messages.index') }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-gray-900">Detalle del Mensaje</h1>
                </div>
                @if($contactMessage->status === 'pending')
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700">Pendiente</span>
                @elseif($contactMessage->status === 'read')
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-700">Leído</span>
                @else
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700">Respondido</span>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Message Content --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-1">{{ $contactMessage->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $contactMessage->email }}</p>
                    </div>
                    <div class="p-6">
                        <div class="prose max-w-none">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $contactMessage->message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Contact Info --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Información de Contacto</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Email</p>
                            <a href="mailto:{{ $contactMessage->email }}" class="text-sm text-blue-600 hover:underline">{{ $contactMessage->email }}</a>
                        </div>
                        @if($contactMessage->phone)
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Teléfono</p>
                            <a href="tel:{{ $contactMessage->phone }}" class="text-sm text-gray-700 hover:underline">{{ $contactMessage->phone }}</a>
                        </div>
                        @endif
                        @if($contactMessage->company)
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Empresa</p>
                            <p class="text-sm text-gray-700">{{ $contactMessage->company }}</p>
                        </div>
                        @endif
                        @if($contactMessage->service)
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Servicio</p>
                            <p class="text-sm text-gray-700">{{ $contactMessage->service }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Fecha</p>
                            <p class="text-sm text-gray-700">{{ $contactMessage->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Update Status --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Actualizar Estado</h3>
                    <form action="{{ route('admin.contact-messages.update', $contactMessage) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="pending" {{ $contactMessage->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="read" {{ $contactMessage->status === 'read' ? 'selected' : '' }}>Leído</option>
                                    <option value="replied" {{ $contactMessage->status === 'replied' ? 'selected' : '' }}>Respondido</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas internas</label>
                                <textarea name="admin_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Agregar notas...">{{ $contactMessage->admin_notes }}</textarea>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
