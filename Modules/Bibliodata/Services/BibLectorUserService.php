<?php

namespace Modules\Bibliodata\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class BibLectorUserService
{
    public function readerRoleName(): string
    {
        return config('bibliodata.reader.role', 'Lector');
    }

    public function search(string $search = '', array $excludeIds = [], int $perPage = 10): LengthAwarePaginator
    {
        $roleName = $this->readerRoleName();
        $role = Role::where('name', $roleName)->first();

        $query = User::query()
            ->with(['person:id,document_type_id,number,names,father_lastname,mother_lastname,telephone,email,full_name'])
            ->select('users.id', 'users.name', 'users.email', 'users.person_id')
            ->when($role, fn ($q) => $q->role($roleName))
            ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('users.id', $excludeIds));

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('users.name', 'like', $term)
                    ->orWhere('users.email', 'like', $term)
                    ->orWhereHas('person', function ($pq) use ($term) {
                        $pq->where('number', 'like', $term)
                            ->orWhere('full_name', 'like', $term)
                            ->orWhere('names', 'like', $term);
                    });
            });
        }

        return $query->orderBy('users.name')->paginate($perPage);
    }

    public function findForEdit(int $userId): User
    {
        $user = User::with('person')->findOrFail($userId);

        if (! $user->hasRole($this->readerRoleName())) {
            throw ValidationException::withMessages([
                'user' => 'El usuario no tiene rol Lector.',
            ]);
        }

        return $user;
    }

    public function createOrUpdate(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $fullName = trim(
                ($data['names'] ?? '').' '.
                ($data['father_lastname'] ?? '').' '.
                ($data['mother_lastname'] ?? '')
            );

            $person = Person::updateOrCreate(
                [
                    'document_type_id' => $data['document_type_id'],
                    'number' => $data['number'],
                ],
                [
                    'names' => $data['names'],
                    'father_lastname' => $data['father_lastname'],
                    'mother_lastname' => $data['mother_lastname'] ?? '',
                    'full_name' => $fullName,
                    'email' => $data['email'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'gender' => $data['gender'] ?? null,
                ]
            );

            $userId = $data['user_id'] ?? null;
            $password = ! empty($data['password'])
                ? $data['password']
                : $data['number'];

            if ($userId) {
                $user = User::findOrFail($userId);
                $user->update([
                    'name' => $data['names'],
                    'email' => $data['email'],
                    'person_id' => $person->id,
                    ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
                ]);
            } else {
                $user = User::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['names'],
                        'password' => Hash::make($password),
                        'local_id' => $data['local_id'] ?? 1,
                        'person_id' => $person->id,
                    ]
                );
            }

            if (! $user->hasRole($this->readerRoleName())) {
                $user->assignRole($this->readerRoleName());
            }

            return $user->load('person');
        });
    }

    public function formatMember(User $user): array
    {
        $person = $user->person;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'document_type_id' => $person?->document_type_id,
            'number' => $person?->number,
            'names' => $person?->names,
            'father_lastname' => $person?->father_lastname,
            'mother_lastname' => $person?->mother_lastname,
            'telephone' => $person?->telephone,
            'full_name' => $person?->full_name ?? $user->name,
            'gender' => $person?->gender,
        ];
    }
}
