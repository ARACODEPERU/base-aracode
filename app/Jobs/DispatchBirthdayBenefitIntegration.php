<?php

namespace App\Jobs;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

class DispatchBirthdayBenefitIntegration implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $personId,
        public string $flowId,
        public array $extraVariables = [],
        public bool $trackResults = true,
        public ?string $batchId = null
    ) {
    }

    public function handle(): void
    {
        $person = Person::find($this->personId);

        if (!$person) {
            return;
        }

        $phone = $this->normalizePhone($person->telephone);
        $customFields = $this->customFieldsFromVariables();
        $extraVariables = array_diff_key($this->extraVariables, array_flip([
            'var1',
            'var2',
            'var3',
            'var4',
            'custom_fields',
            'flow_id',
        ]));

        $contactVariables = array_merge($extraVariables, [
            'phone' => $phone,
            'email' => $person->email,
            'first_name' => $person->names,
            'last_name' => trim(($person->father_lastname ?? '') . ' ' . ($person->mother_lastname ?? '')),
            'custom_fields' => $customFields,
        ]);

        $this->executeEndpointByName('create_contact', $contactVariables);

        $this->executeEndpointByName('start_flow', [
            'contact_id' => $phone,
            'phone' => $phone,
            'flow_id' => $this->flowId,
        ]);
    }

    private function executeEndpointByName(string $endpointName, array $variables): void
    {
        $endpoint = IntegrationEndpoint::where('name', $endpointName)->firstOrFail();

        $request = Request::create('', 'POST', [
            'endpoint_id' => $endpoint->id,
            'variables' => $variables,
            'track_results' => $this->trackResults,
            'batch_id' => $this->batchId,
        ]);

        app(IntegrationhubController::class)->execute($request, $endpoint->integration_id);
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $digits = preg_replace('/^00+/', '', $digits);

        if (strlen($digits) === 9) {
            return '51' . $digits;
        }

        return $digits;
    }

    private function customFieldsFromVariables(): array
    {
        $customFieldVariables = $this->extraVariables['custom_fields'] ?? [];

        return [
            'var1' => $customFieldVariables['var1'] ?? $this->extraVariables['var1'] ?? null,
            'var2' => $customFieldVariables['var2'] ?? $this->extraVariables['var2'] ?? null,
            'var3' => $customFieldVariables['var3'] ?? $this->extraVariables['var3'] ?? null,
            'var4' => $customFieldVariables['var4'] ?? $this->extraVariables['var4'] ?? null,
        ];
    }
}
