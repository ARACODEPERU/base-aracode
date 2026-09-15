<?php

namespace Modules\Blog\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Repara, en tiempo de render, las listas mal formadas del contenido de un articulo.
 *
 * TinyMCE reordena el HTML al abrirlo en el editor: mete dentro de <li> lo que quede
 * suelto en la lista, por eso ahi se ve bien. El HTML guardado en la base sigue mal
 * formado y el navegador lo parte al pintarlo en /article/{slug}: textos sin vineta y
 * vinetas sueltas. Este servicio aplica la misma reparacion antes de imprimir el
 * contenido, sin tocar lo que ya esta guardado ni lo que ve el editor.
 */
class ArticleContentCleaner
{
    /**
     * Etiquetas que se descartan al inicio o al final de un item. Un <br> al inicio
     * deja la vineta sola en su propia linea, que es justo el defecto que se veia.
     */
    private const EDGE_NOISE_TAGS = ['br'];

    /** Caracteres que se ignoran al considerar un texto vacio (incluye &nbsp;). */
    private const BLANK_CHARS = " \t\n\r\0\x0B\xC2\xA0";

    public static function cleanLists(?string $html): string
    {
        $html = (string) $html;

        // Sin listas no hay nada que reparar: se devuelve el HTML tal cual estaba.
        if (stripos($html, '<ul') === false && stripos($html, '<ol') === false) {
            return $html;
        }

        $document = self::parse($html);

        if (! $document) {
            return $html;
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return $html;
        }

        $lists = self::findLists($body);

        foreach ($lists as $list) {
            self::wrapLooseContent($list);
        }

        foreach ($lists as $list) {
            self::trimItemEdges($list);
        }

        foreach ($lists as $list) {
            self::mergeWithNextSibling($list);
        }

        $clean = '';

        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        if ($clean === '') {
            return $html;
        }

        // Red de seguridad: con HTML muy raro libxml puede descartar contenido y
        // dejaria el articulo a medias. Si se perdio algo de texto visible se
        // devuelve el original tal cual, que es preferible a perder parrafos.
        if (self::visibleLength($clean) !== self::visibleLength($html)) {
            return $html;
        }

        return $clean;
    }

    /** Cantidad de caracteres visibles (sin etiquetas ni espacios) de un HTML. */
    private static function visibleLength(string $html): int
    {
        $text = strip_tags(html_entity_decode($html, ENT_QUOTES, 'UTF-8'));

        return mb_strlen(preg_replace('/[\s\x{00A0}]+/u', '', $text) ?? '', 'UTF-8');
    }

    private static function parse(string $html): ?DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);

        // El meta y la cabecera XML fijan UTF-8: sin ellos libxml lee el contenido
        // como ISO-8859-1 y los acentos terminan rotos ("categorÃ­a").
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'
            . '<!DOCTYPE html><html><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '</head><body>' . $html . '</body></html>',
            LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document : null;
    }

    /**
     * @return DOMElement[]
     */
    private static function findLists(DOMNode $body): array
    {
        $lists = [];

        foreach ($body->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && in_array($element->nodeName, ['ul', 'ol'], true)) {
                $lists[] = $element;
            }
        }

        return $lists;
    }

    /** Todo lo que este directamente en la lista y no sea un <li> pasa a ser uno. */
    private static function wrapLooseContent(DOMElement $list): void
    {
        foreach (iterator_to_array($list->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'li') {
                continue;
            }

            if (self::isBlank($child)) {
                continue;
            }

            $item = $list->ownerDocument->createElement('li');
            $list->insertBefore($item, $child);
            $item->appendChild($child);
        }
    }

    /** Quita los <br> y espacios sobrantes de los extremos de cada item. */
    private static function trimItemEdges(DOMElement $list): void
    {
        foreach (iterator_to_array($list->childNodes) as $item) {
            if (! $item instanceof DOMElement || $item->nodeName !== 'li') {
                continue;
            }

            while ($item->firstChild && self::isEdgeNoise($item->firstChild)) {
                $item->removeChild($item->firstChild);
            }

            while ($item->lastChild && self::isEdgeNoise($item->lastChild)) {
                $item->removeChild($item->lastChild);
            }

            // Un item que quedo sin contenido solo pintaria una vineta vacia.
            if (! $item->firstChild) {
                $list->removeChild($item);
            }
        }
    }

    /** Une dos listas del mismo tipo que quedaron pegadas (por ejemplo ul + ul). */
    private static function mergeWithNextSibling(DOMElement $list): void
    {
        $next = $list->nextSibling;

        while ($next instanceof DOMText && self::isBlank($next)) {
            $next = $next->nextSibling;
        }

        if (! $next instanceof DOMElement || $next->nodeName !== $list->nodeName) {
            return;
        }

        foreach (iterator_to_array($next->childNodes) as $child) {
            $list->appendChild($child);
        }

        $next->parentNode->removeChild($next);

        self::mergeWithNextSibling($list);
    }

    private static function isEdgeNoise(DOMNode $node): bool
    {
        if ($node instanceof DOMElement) {
            return in_array($node->nodeName, self::EDGE_NOISE_TAGS, true);
        }

        return self::isBlank($node);
    }

    private static function isBlank(DOMNode $node): bool
    {
        if ($node instanceof DOMElement) {
            return false;
        }

        if ($node instanceof DOMText) {
            return trim($node->wholeText, self::BLANK_CHARS) === '';
        }

        // Comentarios y demas nodos sin contenido visible.
        return true;
    }
}
