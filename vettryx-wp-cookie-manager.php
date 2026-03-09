<?php
/**
 * Plugin Name: VETTRYX WP Cookie Manager
 *  * Plugin URI:  https://github.com/vettryx/vettryx-wp-cookie-manager
 * Description: Gerenciador de consentimento nativo, focado em performance e integrado à WP Consent API (LGPD).
 * Version:     1.3.0
 * Author:      VETTRYX Tech
 * Author URI:  https://vettryx.com.br
 * License:     GPLv3
 */

// Segurança: Evita acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe principal do plugin
class Vettryx_Cookie_Manager {

    // Nome da opção no banco de dados
    private $option_name = 'vettryx_cookie_settings';

    // Construtor
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_init', [ $this, 'handle_policy_generation' ] );
        } else {
            add_action( 'wp_footer', [ $this, 'render_cookie_banner' ], 99 );
        }

        add_shortcode( 'vettryx_gerenciar_cookies', [ $this, 'render_revoke_shortcode' ] );
        add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
    }

    // Adiciona a página de configurações ao menu
    public function add_submenu_page() {
        add_submenu_page(
            'vettryx-core-modules',
            'Cookie Manager',
            'Cookie Manager',
            'manage_options',
            'vettryx-cookie-manager',
            [ $this, 'render_admin_page' ]
        );
    }

    // Registra as configurações do plugin
    public function register_settings() {
        register_setting( 'vettryx_cookie_group', $this->option_name, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_data' ]
        ] );
    }

    // Sanitiza os dados do plugin
    public function sanitize_data( $input ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return get_option( $this->option_name );
        }
        return [
            'enable_native'  => isset( $input['enable_native'] ) ? '1' : '0',
            'bg_color'       => sanitize_hex_color( $input['bg_color'] ?? '#111827' ),
            'text_color'     => sanitize_hex_color( $input['text_color'] ?? '#f9fafb' ),
            'btn_bg_color'   => sanitize_hex_color( $input['btn_bg_color'] ?? '#2563eb' ),
            'btn_text_color' => sanitize_hex_color( $input['btn_text_color'] ?? '#ffffff' ),
            'banner_text'    => sanitize_textarea_field( $input['banner_text'] ?? 'Utilizamos cookies para melhorar a sua experiência. Ao continuar a navegar, concorda com a nossa Política de Privacidade e Cookies.' ),
            'button_text'    => sanitize_text_field( $input['button_text'] ?? 'Aceitar e Continuar' ),
            'privacy_link'   => esc_url_raw( $input['privacy_link'] ?? '' ),
        ];
    }

    /**
     * Intercepta o clique no botão de gerar página e cria o post no WordPress
     */
    public function handle_policy_generation() {
        if ( isset( $_POST['vettryx_generate_policy'] ) && current_user_can( 'manage_options' ) && check_admin_referer( 'vettryx_policy_nonce' ) ) {
            
            $site_name = get_bloginfo('name');
            $site_url = get_bloginfo('url');
            $admin_email = get_option('admin_email');

            $policy_content = "
<h2>1. Introdução</h2>
<p>O nosso site, <strong>{$site_name}</strong> ({$site_url}), utiliza cookies e outras tecnologias relacionadas (por conveniência, todas as tecnologias são referidas como \"cookies\"). Este documento estabelece o nosso compromisso com a transparência, em conformidade com a Lei Geral de Proteção de Dados Pessoais (LGPD - Lei nº 13.709/2018) e regulamentações internacionais aplicáveis.</p>
<h2>2. O que são cookies?</h2>
<p>Um cookie é um pequeno ficheiro simples enviado junto com as páginas deste site e armazenado pelo seu navegador no disco rígido do seu computador ou dispositivo. As informações armazenadas podem ser devolvidas aos nossos servidores ou aos servidores de terceiros relevantes durante uma visita subsequente.</p>
<h2>3. Como utilizamos os Cookies</h2>
<p><strong>3.1 Cookies Técnicos ou Funcionais:</strong> Alguns cookies garantem que certas partes do site funcionam corretamente e que as suas preferências permanecem conhecidas. Ao colocar cookies funcionais, facilitamos a sua visita ao nosso site. Estes cookies são estritamente necessários e isentos de consentimento prévio.</p>
<p><strong>3.2 Cookies Analíticos e de Estatísticas:</strong> Utilizamos cookies analíticos para otimizar a experiência do site para os nossos utilizadores. Com estes cookies, obtemos informações sobre o uso do nosso site. Pedimos a sua permissão para colocar estes cookies.</p>
<p><strong>3.3 Cookies de Marketing e Rastreamento:</strong> Cookies de marketing são utilizados para criar perfis de utilizadores para exibir publicidade ou para rastrear o utilizador neste site ou em vários sites para fins de marketing semelhantes. Só são ativados após o seu consentimento explícito.</p>
<h2>4. Os seus direitos em relação aos dados pessoais</h2>
<p>Ao abrigo da LGPD, tem o direito de:</p>
<ul>
<li>Confirmar a existência de tratamento de dados;</li>
<li>Aceder aos seus dados;</li>
<li>Corrigir dados incompletos, inexatos ou desatualizados;</li>
<li>Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários;</li>
<li><strong>Revogar o seu consentimento a qualquer momento.</strong></li>
</ul>
<h2>5. Gerenciar e Revogar Consentimento</h2>
<p>Pode alterar as suas preferências de cookies ou revogar o seu consentimento a qualquer momento utilizando a opção abaixo. Se revogar o consentimento, as ferramentas de rastreamento de terceiros serão imediatamente bloqueadas.</p>
<p><strong>[vettryx_gerenciar_cookies texto=\"Clique aqui para Revogar os Cookies e Gerenciar Preferências\"]</strong></p>
<h2>6. Detalhes de Contacto</h2>
<p>Para questões ou comentários sobre a nossa política de cookies e esta declaração, por favor contacte-nos através de:<br>
Site: {$site_url}<br>
E-mail: {$admin_email}</p>
";

            $post_data = [
                'post_title'   => 'Política de Privacidade e Cookies',
                'post_content' => $policy_content,
                'post_status'  => 'draft', // Cria como Rascunho para o cliente revisar
                'post_type'    => 'page',
            ];

            $post_id = wp_insert_post( $post_data );

            if ( $post_id ) {
                add_settings_error( 'vettryx_cookie_messages', 'policy_generated', 'Página de Política de Privacidade gerada com sucesso e salva como Rascunho! Vá em Páginas > Todas as Páginas para publicar.', 'updated' );
            }
        }
    }

    // Renderiza a página de configurações
    public function render_admin_page() {
        $data = get_option( $this->option_name, [
            'enable_native'  => '1',
            'bg_color'       => '#111827',
            'text_color'     => '#f9fafb',
            'btn_bg_color'   => '#2563eb',
            'btn_text_color' => '#ffffff',
            'banner_text'    => 'Utilizamos cookies para melhorar a sua experiência. Ao continuar a navegar, concorda com a nossa Política de Privacidade e Cookies.',
            'button_text'    => 'Aceitar e Continuar',
            'privacy_link'   => ''
        ]);
        ?>
        <div class="wrap">
            <h1><?php _e( 'VETTRYX Tech - Cookie Manager', 'vettryx-wp-core' ); ?></h1>
            
            <?php settings_errors( 'vettryx_cookie_messages' ); ?>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 400px;">
                    <p><?php _e( 'Configure o banner de consentimento (LGPD) ou desative o visual para criá-lo via Elementor/Divi.', 'vettryx-wp-core' ); ?></p>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields( 'vettryx_cookie_group' ); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="enable_native">Usar Banner Nativo</label></th>
                                <td>
                                    <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_native]" id="enable_native" value="1" <?php checked( $data['enable_native'], '1' ); ?>>
                                </td>
                            </tr>
                            <tr><td colspan="2"><hr></td></tr>
                            <tr>
                                <th scope="row"><label for="bg_color">Cor de Fundo</label></th>
                                <td><input type="color" name="<?php echo esc_attr( $this->option_name ); ?>[bg_color]" id="bg_color" value="<?php echo esc_attr( $data['bg_color'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="text_color">Cor do Texto</label></th>
                                <td><input type="color" name="<?php echo esc_attr( $this->option_name ); ?>[text_color]" id="text_color" value="<?php echo esc_attr( $data['text_color'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="btn_bg_color">Cor Botão</label></th>
                                <td><input type="color" name="<?php echo esc_attr( $this->option_name ); ?>[btn_bg_color]" id="btn_bg_color" value="<?php echo esc_attr( $data['btn_bg_color'] ); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="btn_text_color">Texto Botão</label></th>
                                <td><input type="color" name="<?php echo esc_attr( $this->option_name ); ?>[btn_text_color]" id="btn_text_color" value="<?php echo esc_attr( $data['btn_text_color'] ); ?>"></td>
                            </tr>
                            <tr><td colspan="2"><hr></td></tr>
                            <tr>
                                <th scope="row"><label for="banner_text">Texto do Banner</label></th>
                                <td>
                                    <textarea name="<?php echo esc_attr( $this->option_name ); ?>[banner_text]" id="banner_text" rows="3" class="large-text"><?php echo esc_textarea( $data['banner_text'] ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="button_text">Texto do Botão</label></th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_text]" id="button_text" value="<?php echo esc_attr( $data['button_text'] ); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="privacy_link">Link da Política</label></th>
                                <td>
                                    <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[privacy_link]" id="privacy_link" value="<?php echo esc_url( $data['privacy_link'] ); ?>" class="regular-text" placeholder="https://seudominio.com.br/politica">
                                </td>
                            </tr>
                        </table>
                        <?php submit_button( 'Salvar Configurações' ); ?>
                    </form>
                </div>

                <div style="width: 350px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); align-self: flex-start;">
                    <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Automação LGPD</h2>
                    <p style="font-size: 13px; color: #666;">Ainda não tem uma página de Política de Privacidade e Cookies? O VETTRYX Cookie Manager pode gerar uma baseada nos padrões da LGPD/GDPR automaticamente para você.</p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field( 'vettryx_policy_nonce' ); ?>
                        <input type="hidden" name="vettryx_generate_policy" value="1">
                        <?php submit_button( 'Gerar Página de Política Padrão', 'secondary', 'submit', false, ['style' => 'width: 100%; text-align: center;'] ); ?>
                    </form>
                    
                    <p style="font-size: 12px; color: #888; margin-top: 15px;">*A página será criada como <b>Rascunho</b>. Ela já incluirá o seu E-mail, a URL do site e o botão nativo para os utilizadores revogarem o consentimento.</p>
                </div>
            </div>
        </div>
        <?php
    }

    // Renderiza o shortcode para revogar o consentimento
    public function render_revoke_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'texto'  => 'Revogar Consentimento de Cookies',
            'classe' => ''
        ], $atts );
        return '<a href="#" id="vettryx-revoke-cookies" class="' . esc_attr( $atts['classe'] ) . '" style="text-decoration: underline; cursor: pointer;">' . esc_html( $atts['texto'] ) . '</a>';
    }

    // Renderiza o banner de cookies
    public function render_cookie_banner() {
        $data = get_option( $this->option_name, [ 'enable_native' => '1', 'bg_color' => '#111827', 'text_color' => '#f9fafb', 'btn_bg_color' => '#2563eb', 'btn_text_color' => '#ffffff' ] );
        
        if ( $data['enable_native'] === '1' ) {
            $text = !empty($data['banner_text']) ? esc_html($data['banner_text']) : 'Utilizamos cookies para melhorar a sua experiência.';
            $btn  = !empty($data['button_text']) ? esc_html($data['button_text']) : 'Aceitar e Continuar';
            $link = !empty($data['privacy_link']) ? '<a href="'.esc_url($data['privacy_link']).'" style="color: inherit; text-decoration: underline; margin-left: 5px; opacity: 0.8;">Saiba mais</a>' : '';

            $bg_color       = esc_attr( $data['bg_color'] );
            $text_color     = esc_attr( $data['text_color'] );
            $btn_bg_color   = esc_attr( $data['btn_bg_color'] );
            $btn_text_color = esc_attr( $data['btn_text_color'] );

            ?>
            <div id="vettryx-cookie-banner" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 16px 24px; box-sizing: border-box; flex-direction: column; align-items: center; justify-content: center; z-index: 999999; font-family: sans-serif; font-size: 14px; text-align: center; box-shadow: 0 -4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease-out;">
                <div style="max-width: 1200px; width: 100%; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                    <div style="flex: 1; text-align: left; min-width: 250px; line-height: 1.5;">
                        <?php echo $text . $link; ?>
                    </div>
                    <button id="vettryx-accept-cookies" style="background: <?php echo $btn_bg_color; ?>; color: <?php echo $btn_text_color; ?>; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">
                        <?php echo $btn; ?>
                    </button>
                </div>
            </div>
            <?php
        }

        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var banner = document.getElementById('vettryx-cookie-banner');
            var hasConsent = document.cookie.indexOf('wp_consent_marketing=allow') !== -1;
            
            if (!hasConsent && banner) {
                banner.style.display = 'flex';
            }
            
            document.addEventListener('click', function(event) {
                var target = event.target;
                
                if (target.id === 'vettryx-accept-cookies' || target.closest('#vettryx-accept-cookies')) {
                    event.preventDefault();
                    if (banner) {
                        banner.style.transform = 'translateY(100%)';
                        setTimeout(function() { banner.style.display = 'none'; }, 300);
                    }
                    if (typeof wp_set_consent === 'function') {
                        wp_set_consent('marketing', 'allow');
                        wp_set_consent('statistics', 'allow');
                    } else {
                        document.cookie = "wp_consent_marketing=allow; max-age=31536000; path=/; secure; samesite=lax";
                        document.cookie = "wp_consent_statistics=allow; max-age=31536000; path=/; secure; samesite=lax";
                    }
                    var consentEvent = new CustomEvent('wp_consent_type_defined');
                    document.dispatchEvent(consentEvent);
                    window.location.reload();
                }

                if (target.id === 'vettryx-revoke-cookies' || target.closest('#vettryx-revoke-cookies')) {
                    event.preventDefault();
                    if (typeof wp_set_consent === 'function') {
                        wp_set_consent('marketing', 'deny');
                        wp_set_consent('statistics', 'deny');
                    } else {
                        document.cookie = "wp_consent_marketing=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        document.cookie = "wp_consent_statistics=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    }
                    var revokeEvent = new CustomEvent('wp_consent_type_defined');
                    document.dispatchEvent(revokeEvent);
                    window.location.reload();
                }
            });
        });
        </script>
        <?php
    }
}

// Inicializa o plugin
new Vettryx_Cookie_Manager();
