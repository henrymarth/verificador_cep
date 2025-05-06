<?php
/**
 * Grava dados (string ou array/objeto) em um arquivo dentro de wp-content.
 *
 * @param mixed  $data     String ou array/objeto a ser gravado.
 * @param string $filename Nome do arquivo (ex: 'meu-log.txt'). Será automaticamente sanitizado.
 * @return bool            True em caso de sucesso, false em caso de falha.
 */
function logador( $data, $filename = 'meu-log.txt' ) {
    // 1) Determina pasta wp-content
    $content_dir = defined( 'WP_CONTENT_DIR' ) 
        ? WP_CONTENT_DIR 
        : ABSPATH . 'wp-content';

    // 2) Sanitiza e monta o caminho completo do arquivo
    $filename = sanitize_file_name( $filename );
    $file = trailingslashit( $content_dir ) . $filename;

    // 3) Prepara o conteúdo a ser escrito
    if ( is_array( $data ) || is_object( $data ) ) {
        $body = print_r( $data, true );
    } else {
        $body = (string) $data;
    }

    // (Opcional) adiciona timestamp antes da mensagem
    $timestamp = date( 'Y-m-d H:i:s' );
    $content   = "{$timestamp} — {$body}\n";

    // 4) Escreve no arquivo em modo append, com lock exclusivo
    $bytes_written = @file_put_contents( $file, $content, FILE_APPEND | LOCK_EX );

    // 5) Retorna false se houver erro
    return ( $bytes_written !== false );
}