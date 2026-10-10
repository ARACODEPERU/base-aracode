{{--
    Preguntas frecuentes.

    Acordeones con <details>/<summary>: funcionan con teclado, con lectores de
    pantalla y sin JavaScript. Solo se publican las preguntas marcadas con
    'publicado' => true, que son las que tienen una respuesta confirmada.
--}}
@php
    $faqs = collect(config('kirafact.faqs'))
        ->filter(fn ($faq) => $faq['publicado'] ?? false)
        ->values();
@endphp

<section id="preguntas" class="kf-surface-mist">
    <div class="kf-container kf-section">
        <div class="max-w-3xl kf-reveal">
            <h2 class="kf-h2">Preguntas frecuentes</h2>
            <p class="kf-lead kf-copy mt-5">
                Las dudas que más nos llegan sobre el producto y sobre el acceso al sistema.
            </p>
        </div>

        <div class="mt-10 max-w-4xl space-y-3">
            @foreach ($faqs as $faq)
                <details class="kf-faq kf-reveal">
                    <summary>{{ $faq['pregunta'] }}</summary>
                    <p class="kf-faq-body">{{ $faq['respuesta'] }}</p>
                </details>
            @endforeach
        </div>

        <p class="kf-slate kf-reveal mt-8 text-sm">
            ¿Tu pregunta no está en la lista?
            <a href="{{ route('kirafact.contacto') }}" class="kf-link">Escríbenos</a>.
        </p>
    </div>
</section>
