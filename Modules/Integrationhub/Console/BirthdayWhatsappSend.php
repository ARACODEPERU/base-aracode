<?php

namespace Modules\Integrationhub\Console;

use Illuminate\Console\Command;
use App\Models\Person;
use Modules\Integrationhub\Jobs\ProcessBirthdayWhatsapp;

class BirthdayWhatsappSend extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integrationhub:birthday-whatsapp-send';

    /**
     * The console command description.
     */
    protected $description = 'Envía WhatsApp de cumpleaños a personas que cumplen años hoy';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Formatea un número telefónico agregando el código de país si es necesario.
     *
     * @param string $phone    Número telefónico original
     * @param object|null $country  Modelo del país (con country_code_phone)
     * @return string Número formateado con código de país
     */
    private function formatPhoneWithCountryCode(string $phone, ?object $country): string
    {
        // Limpiar el número: eliminar todo excepto dígitos
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        $countryPhoneCode = $country?->country_code_phone;

        if ($countryPhoneCode) {
            // El número ya tiene el código de país? dejarlo igual
            if (str_starts_with($cleaned, $countryPhoneCode)) {
                return $cleaned;
            }
            // Agregar el código de país
            return $countryPhoneCode . $cleaned;
        }

        // Sin país definido: asumir Perú (51)
        // Si es celular peruano (9 dígitos, empieza con 9)
        if (strlen($cleaned) === 9 && str_starts_with($cleaned, '9')) {
            return '51' . $cleaned;
        }

        // En cualquier otro caso, asumir Perú como default
        return '51' . $cleaned;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Consultando personas que cumplen años hoy...');

        $today = now();

        $people = Person::query()
            ->with(['country', 'user.roles'])
            ->whereNotNull('birthdate')
            ->whereNotNull('telephone')
            ->whereMonth('birthdate', $today->month)
            ->whereDay('birthdate', $today->day)
            ->get(['id', 'telephone', 'email', 'short_name', 'country_id']);

        $count = $people->count();
        $this->info("Se encontraron {$count} persona(s) que cumplen años hoy.");

        foreach ($people as $person) {
            $role = $person->user?->roles->pluck('name')->first();
            $formattedPhone = $this->formatPhoneWithCountryCode($person->telephone, $person->country);
            ProcessBirthdayWhatsapp::dispatch($formattedPhone, $person->email, $person->short_name, $role);
            $this->line("  → Job encolado para teléfono: {$person->telephone} → {$formattedPhone} (rol: {$role})");
        }

        $this->info("{$count} job(s) enviados al queue:work correctamente.");

        return 0;
    }
}
