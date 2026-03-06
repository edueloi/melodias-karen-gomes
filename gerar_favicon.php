<?php
// Script temporário para gerar favicon ICO
// Acesse via browser uma vez para gerar, depois apague este arquivo

function gerarFaviconICO($src, $dest) {
    $img_orig = imagecreatefrompng($src);
    
    // Cria versões 16x16, 32x32 e 48x48
    $sizes = [16, 32, 48];
    $images = [];
    
    foreach ($sizes as $size) {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $size, $size, $transparent);
        imagealphablending($img, true);
        
        $orig_w = imagesx($img_orig);
        $orig_h = imagesy($img_orig);
        imagecopyresampled($img, $img_orig, 0, 0, 0, 0, $size, $size, $orig_w, $orig_h);
        $images[] = $img;
    }
    
    // Salva como ICO (formato manual)
    $data = '';
    $dir_entries = '';
    $offset = 6 + count($images) * 16;
    
    foreach ($images as $i => $img) {
        $size = $sizes[$i];
        
        // Captura PNG da imagem
        ob_start();
        imagepng($img);
        $png_data = ob_get_clean();
        imagedestroy($img);
        
        $len = strlen($png_data);
        
        // Directory entry
        $dir_entries .= pack('CCCCSSII',
            $size,  // width
            $size,  // height
            0,      // color count (0 = más de 256 cores)
            0,      // reserved
            1,      // color planes
            32,     // bits per pixel
            $len,   // size in bytes
            $offset // offset
        );
        
        $data .= $png_data;
        $offset += $len;
    }
    
    // ICO header
    $header = pack('SSS', 0, 1, count($images));
    
    file_put_contents($dest, $header . $dir_entries . $data);
    return true;
}

$src  = __DIR__ . '/images/favicon.png';
$dest = __DIR__ . '/images/favicon.ico';

if (!file_exists($src)) {
    die('<h2 style="color:red">Erro: favicon.png não encontrado!</h2>');
}

if (gerarFaviconICO($src, $dest)) {
    echo '<h2 style="color:green;font-family:Arial;">✅ favicon.ico gerado com sucesso!</h2>';
    echo '<p style="font-family:Arial;">Arquivo salvo em: <code>' . $dest . '</code></p>';
    echo '<p style="font-family:Arial;">Tamanho: ' . number_format(filesize($dest)) . ' bytes</p>';
    echo '<p style="font-family:Arial;color:#888;">Você pode apagar este arquivo agora: <code>gerar_favicon.php</code></p>';
    echo '<br><a href="painel.php" style="background:#6e2b3a;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;font-family:Arial;">Voltar ao sistema</a>';
} else {
    echo '<h2 style="color:red">Erro ao gerar favicon!</h2>';
}
