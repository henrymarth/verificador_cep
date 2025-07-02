<?php
/**
 * Plugin Name: Verificador de CEP
 * Description: Não deixa o Usuário fazer compra com um CEP falso
 * Version:     1.0.0
 * Author:      Rdorval / HDM
 */
defined( 'ABSPATH' ) || exit;

require plugin_dir_path( __FILE__ ) . 'log_maker.php';


add_action( 'plugins_loaded', 'vc_init_verificador_cep' );
function vc_init_verificador_cep() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        logador( 'Verificador de CEP: WooCommerce não detectado' );
        return;
    }
    //logador( 'Verificador de CEP: WooCommerce detectado, registrando hook' );


    add_action( 'woocommerce_checkout_process',           'vc_validar_cep_no_checkout', 10 );
}

function vc_validar_cep_no_checkout() {

    if ( empty( $_POST['billing_postcode'] ) ) {
        logador( 'billing_postcode não definido' );
        return;
    }

    $cep = preg_replace( '/\D/', '', wp_unslash( $_POST['billing_postcode'] ) );

    if ( ! meu_checador_de_cep( $cep ) ) {
        wc_add_notice( 'O CEP informado não é válido.', 'error' );
        logador('O CEP informado não é válido.');
    }
}

/**
 * Exemplo simples de validação
 */
function meu_checador_de_cep( $cep ) {
    $url  = "https://viacep.com.br/ws/{$cep}/json/";
    $resp = wp_remote_get( $url, [ 'timeout' => 5 ] );

    if ( is_wp_error( $resp ) ) {
        logador( 'ViaCEP WP_Error: ' . $resp->get_error_message() . "");


        //começa consulta por apis reservas
        $url = "https://brasilapi.com.br/api/cep/v1/{$cep}";
        $resp = wp_remote_get( $url, [ 'timeout' => 5 ] );
        if(is_wp_error($resp)){
            logador( 'API alternativa 1 WP_Error: ' . $resp->get_error_message() . "");
        }
        else{
            logador('API reserva passou');
            $api_reserva = 1;
        }

        //se nenhuma retornar certo eu registro erro no log e dou return true para seguir e arriscamos um cep errado
        return true; // deixa passar se a API caiu
    }

    $body = wp_remote_retrieve_body( $resp );
    $data = json_decode( $body, true );

    //viacep deu certo
    if(!isset($api_reserva)){    
        if ( isset( $data['erro'] ) && $data['erro'] ) {
            logador( 'ViaCEP retornou erro para o CEP.' );
            return false;
            }
    }

    elseif(isset($api_reserva)){
        if ( isset( $data['erro'] ) && $data['erro'] ) {
            logador( 'BrasilAPI retornou erro para o CEP.' );
            return false;
            }
    }

    //retorno da função se tiver erro nas APIs. Para não travar a compra do cliente
    return true;
}
