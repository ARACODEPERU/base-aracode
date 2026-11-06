<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Sunat\SunatFileStorage;
use Tests\TestCase;
use ZipArchive;

class SunatFileStorageTest extends TestCase
{
    private string $root;

    private SunatFileStorage $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sunat-test-'.uniqid();
        $this->files = new SunatFileStorage($this->root);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            foreach (glob($this->root.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->root);
        }

        parent::tearDown();
    }

    private function cdrZip(): string
    {
        $xml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <ApplicationResponse xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
            <cbc:ResponseCode>0</cbc:ResponseCode>
            <cbc:Description><![CDATA[La Factura numero F001-1, ha sido aceptada]]></cbc:Description>
            <cbc:Note>Leyenda uno</cbc:Note>
            <cbc:Note>Leyenda dos</cbc:Note>
        </ApplicationResponse>
        XML;

        $zipPath = $this->root.DIRECTORY_SEPARATOR.'source.zip';
        @mkdir($this->root, 0777, true);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('R-F001-1.xml', $xml);
        $zip->close();

        $content = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        return $content;
    }

    public function test_guarda_el_xml_firmado(): void
    {
        $path = $this->files->storeXml('F001-1.xml', '<Invoice/>');

        $this->assertNotNull($path);
        $this->assertSame('F001-1.xml', basename($path));
        $this->assertFileExists($path);
        $this->assertSame('<Invoice/>', file_get_contents($path));
    }

    public function test_no_guarda_un_xml_vacio(): void
    {
        $this->assertNull($this->files->storeXml('F001-1', ''));
        $this->assertNull($this->files->storeXml('F001-1', null));
    }

    public function test_descomprime_el_cdr_y_guarda_zip_mas_xml(): void
    {
        $stored = $this->files->storeCdr('F001-1', $this->cdrZip());

        $this->assertNotNull($stored['zip']);
        $this->assertNotNull($stored['xml']);
        $this->assertSame('R-F001-1.zip', basename($stored['zip']));
        $this->assertSame('R-F001-1.xml', basename($stored['xml']));
        $this->assertFileExists($stored['zip']);
        $this->assertFileExists($stored['xml']);

        $this->assertSame('0', $stored['code']);
        $this->assertSame('La Factura numero F001-1, ha sido aceptada', $stored['description']);
        $this->assertSame(['Leyenda uno', 'Leyenda dos'], $stored['notes']);

        // El XML extraido debe poder descargarse junto al ZIP.
        $this->assertSame($stored['xml'], $this->files->cdrXmlPath($stored['zip']));
    }

    public function test_un_cdr_invalido_guarda_el_zip_sin_romper(): void
    {
        $stored = $this->files->storeCdr('F002-2', 'esto-no-es-un-zip');

        $this->assertNotNull($stored['zip']);
        $this->assertNull($stored['xml']);
        $this->assertNull($stored['code']);
        $this->assertSame([], $stored['notes']);
    }

    public function test_normaliza_el_nombre_del_archivo(): void
    {
        $this->assertSame('F001-1', $this->files->baseName('R-F001-1.xml'));
        $this->assertSame('F001-1', $this->files->baseName('R-F001-1.zip'));
        $this->assertSame('F001-1', $this->files->baseName('F001-1'));
        $this->assertNotSame('', $this->files->baseName(''));
    }

    public function test_resuelve_rutas_absolutas_historicas(): void
    {
        $legacy = $this->files->storeXml('F003-3', '<Invoice/>');

        // Las filas historicas guardan la ruta absoluta: debe seguir funcionando.
        $this->assertSame($legacy, $this->files->absolutePath($legacy));
        $this->assertNull($this->files->absolutePath('no-existe.xml'));
        $this->assertNull($this->files->absolutePath(null));
    }
}
