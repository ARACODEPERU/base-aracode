<?php

namespace Tests\Unit\Modules\Integrationhub;

use Modules\Integrationhub\Support\TelegramHtml;
use Tests\TestCase;

/**
 * Preparacion de los textos para la API de Telegram.
 *
 * La promesa: un "&" o un "<" suelto se escapan (Telegram rechaza el mensaje
 * entero cuando no puede parsearlo), las etiquetas que Telegram entiende se
 * conservan, y en modo texto no se interpreta ninguna etiqueta.
 */
class TelegramHtmlTest extends TestCase
{
    public function test_en_modo_html_escapa_simbolos_y_conserva_etiquetas(): void
    {
        $texto = 'Avisos <b>novedades</b> de Cursos & Diplomados (2 < 3)';

        $this->assertSame(
            'Avisos <b>novedades</b> de Cursos &amp; Diplomados (2 &lt; 3)',
            TelegramHtml::prepare($texto, true)
        );
    }

    public function test_no_duplica_las_entidades_ya_escritas(): void
    {
        $this->assertSame('Cursos &amp; Diplomados', TelegramHtml::prepare('Cursos &amp; Diplomados', true));
    }

    public function test_escapa_el_href_de_un_enlace(): void
    {
        $this->assertSame(
            '<a href="https://sitio.com/a?x=1&amp;y=2">Ver</a>',
            TelegramHtml::prepare('<a href="https://sitio.com/a?x=1&y=2">Ver</a>', true)
        );
    }

    public function test_conserva_emojis_y_saltos_de_linea(): void
    {
        $texto = "👋 ¡Hola!\n\n📚 Curso: <b>NIIF</b>";

        $this->assertSame($texto, TelegramHtml::prepare($texto, true));
    }

    public function test_descarta_etiquetas_que_telegram_no_entiende(): void
    {
        // <script> no esta en la lista: se escapa y el chat muestra el texto.
        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt; y <b>negrita</b>',
            TelegramHtml::prepare('<script>alert(1)</script> y <b>negrita</b>', true)
        );
    }

    public function test_en_modo_texto_no_se_interpreta_ninguna_etiqueta(): void
    {
        $this->assertSame(
            '&lt;b&gt;Hola&lt;/b&gt; &amp; chau &lt;',
            TelegramHtml::prepare('<b>Hola</b> & chau <', false)
        );
    }
}
