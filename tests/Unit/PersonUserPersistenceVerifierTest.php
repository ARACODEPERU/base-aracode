<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Models\User;
use App\Support\PersonUserPersistenceVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PersonUserPersistenceVerifierTest extends TestCase
{
    private const CONNECTION = 'person_user_verifier_test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.'.self::CONNECTION => array_merge(
                config('database.connections.sqlite'),
                ['database' => ':memory:']
            ),
        ]);
        DB::purge(self::CONNECTION);

        $schema = Schema::connection(self::CONNECTION);
        $schema->create('people', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->timestamps();
        });

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect(self::CONNECTION);
        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_repara_y_verifica_los_datos_recientes_de_persona_y_usuario(): void
    {
        $personModel = (new Person())->setConnection(self::CONNECTION);
        $person = $personModel->newQuery()->create([
            'number' => '12345678',
            'email' => 'persona-antigua@example.com',
            'full_name' => 'Nombre anterior',
        ]);
        $userModel = (new User())->setConnection(self::CONNECTION);
        $user = $userModel->newQuery()->create([
            'name' => 'Nombre anterior',
            'email' => 'persona-antigua@example.com',
        ]);

        PersonUserPersistenceVerifier::verify(
            $person,
            $user,
            [
                'number' => '87654321',
                'email' => 'persona@example.com',
                'full_name' => 'Nombre reciente',
            ],
            [
                'name' => 'Nombre reciente',
                'email' => 'persona@example.com',
            ]
        );

        $personRow = DB::connection(self::CONNECTION)
            ->table('people')
            ->where('id', $person->id)
            ->first(['number', 'email', 'full_name']);
        $userRow = DB::connection(self::CONNECTION)
            ->table('users')
            ->where('id', $user->id)
            ->first(['name', 'email', 'person_id']);

        $this->assertSame('87654321', $personRow->number);
        $this->assertSame('persona@example.com', $personRow->email);
        $this->assertSame('Nombre reciente', $personRow->full_name);
        $this->assertSame('Nombre reciente', $userRow->name);
        $this->assertSame('persona@example.com', $userRow->email);
        $this->assertSame((int) $person->id, (int) $userRow->person_id);
    }

    public function test_falla_si_la_fila_de_usuario_no_existe(): void
    {
        $person = (new Person())->setConnection(self::CONNECTION)->newQuery()->create([
            'number' => '12345678',
        ]);
        $user = (new User())->setConnection(self::CONNECTION)->newQuery()->create([
            'name' => 'Persona',
            'email' => 'persona@example.com',
        ]);
        $user->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El usuario no quedó persistido en users.');

        PersonUserPersistenceVerifier::verify($person, $user, [], []);
    }

    public function test_falla_si_la_fila_de_persona_no_existe(): void
    {
        $person = (new Person())->setConnection(self::CONNECTION)->newQuery()->create([
            'number' => '12345678',
        ]);
        $user = (new User())->setConnection(self::CONNECTION)->newQuery()->create([
            'name' => 'Persona',
            'email' => 'persona@example.com',
        ]);
        $person->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La persona no quedó persistida en people.');

        PersonUserPersistenceVerifier::verify($person, $user, [], []);
    }
}
