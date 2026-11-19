<?php

namespace Modules\Health\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealPatient;

/**
 * Punto único de escritura de la auditoría de Salud.
 *
 * Toda mutación y todo acceso sensible pasan por aquí para que el registro
 * tenga siempre el mismo contexto: actor, paciente, IP, petición, huellas del
 * cambio y cadena de hashes que permite detectar alteraciones posteriores.
 */
final class HealthAudit
{
    public const CATEGORY_CLINICAL = 'clinical';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_ADMIN = 'admin';
    public const CATEGORY_ACCESS = 'access';

    /**
     * Claves que nunca deben quedar escritas en la auditoría.
     * Se comparan en minúsculas y por contenido (contiene), no por igualdad.
     */
    private const FORBIDDEN_KEYS = [
        'password',
        'passwd',
        'signature_pin',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'client_secret',
        'secret',
        'authorization',
    ];

    /**
     * @param  array{
     *     subject?: Model|null,
     *     subject_type?: string|null,
     *     subject_id?: int|string|null,
     *     patient_id?: int|null,
     *     patient_display?: string|null,
     *     metadata?: array<string, mixed>,
     *     before?: array<string, mixed>|null,
     *     after?: array<string, mixed>|null,
     *     category?: string,
     *     actor?: Model|null,
     *     actor_display?: string|null
     * }  $data
     */
    public static function record(string $activityType, array $data = []): HealActivity
    {
        $user = $data['actor'] ?? Auth::user();
        $doctor = $user ? self::resolveDoctor($user) : null;

        $subject = $data['subject'] ?? null;
        $subjectType = $data['subject_type'] ?? ($subject ? get_class($subject) : null);
        $subjectId = $data['subject_id'] ?? $subject?->getKey();

        $patientId = $data['patient_id'] ?? null;
        $patientDisplay = $data['patient_display'] ?? self::patientDisplay($patientId);

        $metadata = self::sanitize($data['metadata'] ?? []);
        $before = isset($data['before']) ? self::sanitize($data['before']) : null;
        $after = isset($data['after']) ? self::sanitize($data['after']) : null;

        $metadataJson = self::canonicalJson($metadata);
        $createdAt = now();

        $previousHash = HealActivity::query()->orderByDesc('id')->value('entry_hash');

        $entryHash = hash('sha256', implode('|', [
            (string) $previousHash,
            $activityType,
            (string) $subjectType,
            (string) $subjectId,
            (string) $patientId,
            $metadataJson,
            $createdAt->toDateTimeString(),
        ]));

        return HealActivity::create([
            'actor_type' => $user ? get_class($user) : null,
            'actor_id' => $user?->getKey(),
            'actor_person_id' => $user?->person_id ?? $doctor?->person_id,
            'actor_display' => $data['actor_display']
                ?? $doctor?->person?->full_name
                ?? $user?->name
                ?? 'Sistema',
            'activity_type' => $activityType,
            'category' => $data['category'] ?? self::CATEGORY_CLINICAL,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'patient_id' => $patientId,
            'patient_display' => $patientDisplay,
            'metadata' => $metadata,
            'request_id' => self::requestId(),
            'route' => self::currentRouteName(),
            'http_method' => self::currentMethod(),
            'before_hash' => $before !== null ? hash('sha256', self::canonicalJson($before)) : null,
            'after_hash' => $after !== null ? hash('sha256', self::canonicalJson($after)) : null,
            'previous_hash' => $previousHash,
            'entry_hash' => $entryHash,
            'ip' => self::clientIp(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private static function resolveDoctor(?Model $user): ?HealDoctor
    {
        if (!$user) {
            return null;
        }

        return HealDoctor::query()
            ->when($user->id ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($user->person_id ?? null, fn ($query, $personId) => $query->orWhere('person_id', $personId))
            ->first();
    }

    private static function patientDisplay(?int $patientId): ?string
    {
        if (!$patientId) {
            return null;
        }

        return HealPatient::with('person')->find($patientId)?->person?->full_name;
    }

    /**
     * Deja fuera cualquier dato sensible antes de guardarlo.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && self::isForbiddenKey($key)) {
                continue;
            }

            $clean[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $clean;
    }

    private static function isForbiddenKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::FORBIDDEN_KEYS as $forbidden) {
            if (str_contains($normalized, $forbidden)) {
                return true;
            }
        }

        return false;
    }

    /**
     * JSON estable: el mismo contenido genera siempre la misma huella.
     *
     * @param  array<string, mixed>  $value
     */
    public static function canonicalJson(array $value): string
    {
        return json_encode(
            self::sortRecursive($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortRecursive($item);
            }
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private static function requestId(): ?string
    {
        $request = request();

        if (!$request) {
            return null;
        }

        $requestId = $request->attributes->get('audit_request_id');

        if (!$requestId) {
            $requestId = (string) \Illuminate\Support\Str::uuid();
            $request->attributes->set('audit_request_id', $requestId);
        }

        return $requestId;
    }

    private static function currentRouteName(): ?string
    {
        $request = request();

        return $request?->route()?->getName() ?? $request?->path();
    }

    private static function currentMethod(): ?string
    {
        return request()?->method();
    }

    /**
     * IP del cliente sin confiar ciegamente en X-Forwarded-For: Laravel ya
     * resuelve los proxies declarados en TrustProxies.
     */
    private static function clientIp(): ?string
    {
        return request()?->ip();
    }
}
