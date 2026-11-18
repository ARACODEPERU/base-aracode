<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Performance\Entities\PerformanceImage;
use Modules\Performance\Services\ImageInventoryService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Módulo Rendimiento → Imágenes: el inventario solo revisa lo que suben los
 * usuarios, el navegador envía la imagen ya procesada y el servidor solo la
 * reemplaza, y las copias y la cuarentena se pueden limpiar pasado su plazo.
 */
class PerformanceImagesTest extends TestCase
{
    use RefreshDatabase;

    private string $storageFixture = 'storage/app/public/_testing';

    private string $publicFixture = 'public/img/_testing';

    /** @var array<int, string> */
    private array $backupsAntes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupsAntes = glob(storage_path('app/performance-backups') . '/*', GLOB_ONLYDIR) ?: [];
    }

    protected function tearDown(): void
    {
        PerformanceImage::query()->where('path', 'like', 'storage/app/public/_testing/%')->delete();

        foreach (glob(base_path($this->storageFixture) . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir(base_path($this->storageFixture));

        foreach (glob(base_path($this->publicFixture) . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir(base_path($this->publicFixture));

        foreach (glob(storage_path('app/performance-backups') . '/*', GLOB_ONLYDIR) ?: [] as $folder) {
            if (! in_array($folder, $this->backupsAntes, true)) {
                \Illuminate\Support\Facades\File::deleteDirectory($folder);
            }
        }

        parent::tearDown();
    }

    public function test_el_inventario_solo_lista_imagenes_subidas_a_storage(): void
    {
        $this->imagen($this->storageFixture . '/inventario.jpg', 400, 300);
        $this->imagen($this->publicFixture . '/proyecto.jpg', 400, 300);

        $respuesta = $this->actingAs($this->admin(['performance_images']))
            ->get(route('performance_images_index'));

        $respuesta->assertOk()->assertInertia(function ($page): void {
            $imagenes = collect($page->toArray()['props']['images']['data']);

            $this->assertTrue($imagenes->contains('path', $this->storageFixture . '/inventario.jpg'));
            $this->assertFalse($imagenes->contains(fn (array $imagen): bool => str_starts_with($imagen['path'], 'public/')));
        });

        // El inventario guardado en caché tampoco incluye archivos del proyecto.
        $rutas = array_column(app(ImageInventoryService::class)->all(true), 'path');

        $this->assertContains($this->storageFixture . '/inventario.jpg', $rutas);
        $this->assertNotContains($this->publicFixture . '/proyecto.jpg', $rutas);
    }

    public function test_el_servidor_reemplaza_la_imagen_que_ya_proceso_el_navegador(): void
    {
        $original = $this->imagen($this->storageFixture . '/reemplazo.jpg', 600, 450, 95);
        $reducida = $this->imagen($this->storageFixture . '/reducida.jpg', 300, 225, 70);
        $bytesOriginal = filesize(base_path($original));
        $bytesReducida = filesize(base_path($reducida));

        $respuesta = $this->actingAs($this->admin(['performance_images']))
            ->post(route('performance_images_replace'), [
                'path' => $original,
                'file' => new UploadedFile(base_path($reducida), 'reemplazo.jpg', 'image/jpeg', null, true),
            ]);

        $respuesta->assertOk()->assertJsonPath('success', true)->assertJsonPath('result.status', 'resized');

        clearstatcache();

        $this->assertLessThan($bytesOriginal, filesize(base_path($original)));
        $this->assertSame($bytesReducida, filesize(base_path($original)));
        $this->assertSame([300, 225], array_slice(getimagesize(base_path($original)), 0, 2));

        $estado = PerformanceImage::where('path', $original)->firstOrFail();
        $this->assertNotNull($estado->backup_path);
        $this->assertTrue(is_file(base_path($estado->backup_path)), 'La copia de seguridad debe existir.');
    }

    public function test_el_servidor_descarta_un_archivo_igual_o_mas_pesado(): void
    {
        $original = $this->imagen($this->storageFixture . '/sin-cambio.jpg', 400, 300);
        $bytes = filesize(base_path($original));

        $respuesta = $this->actingAs($this->admin(['performance_images']))
            ->post(route('performance_images_replace'), [
                'path' => $original,
                'file' => new UploadedFile(base_path($original), 'sin-cambio.jpg', 'image/jpeg', null, true),
            ]);

        clearstatcache();

        $respuesta->assertOk()->assertJsonPath('result.status', 'unchanged');
        $this->assertSame($bytes, filesize(base_path($original)));
        $this->assertNull(PerformanceImage::where('path', $original)->value('backup_path'));
    }

    public function test_el_servidor_rechaza_rutas_del_proyecto_y_formatos_distintos(): void
    {
        $publico = $this->imagen($this->publicFixture . '/proyecto.jpg', 300, 300);
        $jpeg = base_path($this->imagen($this->storageFixture . '/origen.jpg', 300, 300));
        $png = base_path($this->imagen($this->storageFixture . '/origen.png', 300, 300));
        $user = $this->admin(['performance_images']);

        $this->actingAs($user)
            ->post(route('performance_images_replace'), [
                'path' => $publico,
                'file' => new UploadedFile($jpeg, 'proyecto.jpg', 'image/jpeg', null, true),
            ])
            ->assertStatus(422);

        // Un PNG enviado para reemplazar un JPG rompería las referencias.
        $this->actingAs($user)
            ->post(route('performance_images_replace'), [
                'path' => 'storage/app/public/_testing/origen.jpg',
                'file' => new UploadedFile($png, 'origen.png', 'image/png', null, true),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El archivo enviado no coincide con el formato de la imagen original.');
    }

    public function test_limpiar_copias_solo_borra_las_que_superaron_la_retencion(): void
    {
        // La retención se mide por la fecha de la carpeta de cada copia.
        $copiaAntigua = storage_path('app/performance-backups/20200101000000000-prueba-antigua');
        $copiaReciente = storage_path('app/performance-backups/' . now()->format('YmdHisv') . '-prueba-reciente');
        $antigua = $copiaAntigua . '/storage/app/public/_testing';
        $reciente = $copiaReciente . '/storage/app/public/_testing';

        foreach ([$antigua, $reciente] as $carpeta) {
            @mkdir($carpeta, 0775, true);
            file_put_contents($carpeta . '/reemplazo.jpg', str_repeat('x', 2048));
        }

        @touch($copiaAntigua, time() - 40 * 86400);

        $respuesta = $this->actingAs($this->admin(['performance_images']))
            ->post(route('performance_backups_purge'));

        $respuesta->assertOk()->assertJsonPath('purged.files', 1);
        $this->assertDirectoryDoesNotExist($copiaAntigua);
        $this->assertDirectoryExists($copiaReciente);

        // El panel recibe el uso actualizado de las copias.
        $this->actingAs($this->admin(['performance_images']))
            ->get(route('performance_images_index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('backups.files')
                ->where('backups.retention_days', (int) config('performance.backup_days', 30)));
    }

    public function test_la_cuarentena_solo_se_puede_eliminar_pasado_el_plazo(): void
    {
        $ruta = 'storage/app/public/_testing/cuarentena.jpg';
        $archivo = base_path('storage/app/performance-quarantine/20200101-prueba/cuarentena.jpg');
        @mkdir(dirname($archivo), 0775, true);
        file_put_contents($archivo, str_repeat('x', 1024));

        $estado = PerformanceImage::create([
            'path'               => $ruta,
            'bytes'              => 1024,
            'quarantine_path'    => 'storage/app/performance-quarantine/20200101-prueba/cuarentena.jpg',
            'quarantined_at'     => now()->subDay(),
        ]);

        $user = $this->admin(['performance_images']);

        $this->actingAs($user)
            ->post(route('performance_images_quarantine_delete'), ['items' => [$ruta]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'error');

        $this->assertTrue(is_file($archivo));
        $this->assertTrue(PerformanceImage::where('path', $ruta)->exists());

        $estado->forceFill(['quarantined_at' => now()->subDays(10)])->save();

        $this->actingAs($user)
            ->post(route('performance_images_quarantine_delete'), ['items' => [$ruta]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'deleted');

        $this->assertFalse(is_file($archivo));
        $this->assertFalse(PerformanceImage::where('path', $ruta)->exists());

        @rmdir(dirname($archivo));
    }

    public function test_la_imagen_en_cuarentena_se_puede_ver_sin_estar_en_public(): void
    {
        $ruta = $this->imagen($this->storageFixture . '/ver-cuarentena.jpg', 240, 180);
        $archivo = base_path('storage/app/performance-quarantine/20200102-prueba/ver-cuarentena.jpg');
        @mkdir(dirname($archivo), 0775, true);
        copy(base_path($ruta), $archivo);

        PerformanceImage::create([
            'path'            => $ruta,
            'bytes'           => (int) filesize($archivo),
            'width'           => 240,
            'height'          => 180,
            'quarantine_path' => 'storage/app/performance-quarantine/20200102-prueba/ver-cuarentena.jpg',
            'quarantined_at'  => now(),
        ]);

        $user = $this->admin(['performance_images']);

        // El listado entrega una URL para la miniatura aunque el archivo ya no
        // esté en storage/app/public.
        $this->actingAs($user)
            ->get(route('performance_images_index', ['tab' => 'cuarentena']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('images.data.0.quarantined', true)
                ->where('images.data.0.url', route('performance_images_quarantine_file', ['path' => $ruta])));

        $this->actingAs($user)
            ->get(route('performance_images_quarantine_file', ['path' => $ruta]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        // La ruta solo sirve archivos registrados en cuarentena.
        $this->actingAs($user)
            ->get(route('performance_images_quarantine_file', ['path' => $ruta . '-inexistente.jpg']))
            ->assertNotFound();

        @unlink($archivo);
        @rmdir(dirname($archivo));
    }

    public function test_las_acciones_exigen_permiso_de_rendimiento(): void
    {
        $ruta = $this->imagen($this->storageFixture . '/permiso.jpg', 200, 200);
        $sinPermiso = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $sinPermiso->assignRole('admin');

        $this->actingAs($sinPermiso)
            ->post(route('performance_images_replace'), [
                'path' => $ruta,
                'file' => new UploadedFile(base_path($ruta), 'permiso.jpg', 'image/jpeg', null, true),
            ])
            ->assertForbidden();
    }

    /**
     * Crea una imagen JPEG de prueba y devuelve su ruta relativa.
     */
    private function imagen(string $relative, int $width, int $height, int $quality = 80): string
    {
        $absolute = base_path($relative);
        @mkdir(dirname($absolute), 0775, true);

        $canvas = imagecreatetruecolor($width, $height);

        for ($i = 0; $i < 400; $i++) {
            $color = imagecolorallocate($canvas, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imagefilledellipse($canvas, random_int(0, $width), random_int(0, $height), random_int(4, 40), random_int(4, 40), $color);
        }

        if (strtolower(pathinfo($relative, PATHINFO_EXTENSION)) === 'png') {
            imagepng($canvas, $absolute);
        } else {
            imagejpeg($canvas, $absolute, $quality);
        }

        return $relative;
    }

    /**
     * Usuario administrador con los permisos indicados.
     *
     * @param  array<int, string>  $permissions
     */
    private function admin(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('admin');

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
