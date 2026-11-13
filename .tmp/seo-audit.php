<?php
$urls = ['/', '/paginas-web', '/plantillas', '/casos-exito', '/blog', '/contacto'];
foreach ($urls as $u) {
    $html = @file_get_contents('http://base-aracode.test' . $u);
    if ($html === false) { echo "$u ERROR\n"; continue; }
    $d = new DOMDocument();
    libxml_use_internal_errors(true);
    $d->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();
    $xp = new DOMXPath($d);
    $t = fn($q) => $xp->query($q)->item(0)?->getAttribute('content') ?? '';
    $title = trim($xp->query('//title')->item(0)?->textContent ?? '');
    $desc = $t('//meta[@name="description"]');
    $canonical = $xp->query('//link[@rel="canonical"]')->item(0)?->getAttribute('href') ?? '';
    $h1 = $xp->query('//h1')->length;
    $h1t = trim(preg_replace('/\s+/', ' ', $xp->query('//h1')->item(0)?->textContent ?? ''));
    $h2 = $xp->query('//h2')->length;
    $imgs = $xp->query('//img');
    $sinAlt = 0; $lazy = 0; $altVacios = 0;
    foreach ($imgs as $i) {
        if (!$i->hasAttribute('alt')) $sinAlt++;
        elseif (trim($i->getAttribute('alt')) === '') $altVacios++;
        if ($i->getAttribute('loading') === 'lazy') $lazy++;
    }
    $ld = $xp->query('//script[@type="application/ld+json"]')->length;
    $ext = 0;
    foreach ($xp->query('//script[@src]') as $s) { if (!str_starts_with($s->getAttribute('src'), '/')) $ext++; }
    $css = $xp->query('//link[@rel="stylesheet"]')->length;
    $robots = $t('//meta[@name="robots"]');
    printf("%-14s h1=%d h2=%d img=%d sinAlt=%d altVacio=%d lazy=%d ld=%d extJS=%d css=%d\n", $u, $h1, $h2, $imgs->length, $sinAlt, $altVacios, $lazy, $ld, $ext, $css);
    echo "  title(" . mb_strlen($title) . "): $title\n";
    echo "  desc(" . mb_strlen($desc) . "): " . mb_substr($desc, 0, 90) . (mb_strlen($desc) > 90 ? '…' : '') . "\n";
    echo "  canonical: " . ($canonical ?: 'FALTA') . " | robots: " . ($robots ?: '-') . " | h1: " . mb_substr($h1t, 0, 70) . "\n";
}
