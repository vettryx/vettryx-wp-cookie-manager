<?php
/**
 * Plugin Name: VETTRYX WP Cookie Manager
 *  * Plugin URI:  https://github.com/vettryx/vettryx-wp-cookie-manager
 * Description: Gerenciador de consentimento nativo, focado em performance e integrado à WP Consent API (LGPD).
 * Version:     1.2.0
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

    // Nome da opção onde as configurações serão armazenadas no banco de dados
    private $option_name = 'vettryx_cookie_settings';

    // Inicializa o plugin
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        } else {
            add_action( 'wp_footer', [ $this, 'render_cookie_banner' ], 99 );
        }

        // Regista o shortcode para revogação de consentimento (Exigência LGPD)
        add_shortcode( 'vettryx_gerenciar_cookies', [ $this, 'render_revoke_shortcode' ] );

        add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
    }

    // Adiciona a página de configurações no menu do admin
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

    // Sanitiza os dados recebidos do formulário de configurações
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
            'banner_text'    => sanitize_textarea_field( $input['banner_text'] ?? 'Utilizamos cookies para melhorar a sua experiência. Ao continuar a navegar, concorda com a nossa Política de Privacidade.' ),
            'button_text'    => sanitize_text_field( $input['button_text'] ?? 'Aceitar e Continuar' ),
            'privacy_link'   => esc_url_raw( $input['privacy_link'] ?? '' ),
        ];
    }

    // Renderiza a página de configurações no admin
    public function render_admin_page() {
        $data = get_option( $this->option_name, [
            'enable_native'  => '1',
            'bg_color'       => '#111827',
            'text_color'     => '#f9fafb',
            'btn_bg_color'   => '#2563eb',
            'btn_text_color' => '#ffffff',
            'banner_text'    => 'Utilizamos cookies para melhorar a sua experiência. Ao continuar a navegar, concorda com a nossa Política de Privacidade.',
            'button_text'    => 'Aceitar e Continuar',
            'privacy_link'   => ''
        ]);
        ?>
        <div class="wrap">
            <h1><?php _e( 'VETTRYX Tech - Cookie Manager', 'vettryx-wp-core' ); ?></h1>
            <p><?php _e( 'Configure o banner de consentimento (LGPD) ou desative o visual para criá-lo via Elementor/Divi.', 'vettryx-wp-core' ); ?></p>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'vettryx_cookie_group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enable_native">Usar Banner Nativo</label></th>
                        <td>
                            <input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_native]" id="enable_native" value="1" <?php checked( $data['enable_native'], '1' ); ?>>
                            <span class="description">Desmarque esta opção se for construir o popup de cookies usando o Elementor (Basta colocar o ID <b>vettryx-accept-cookies</b> no botão do seu Elementor).</span>
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
                        <th scope="row"><label for="btn_bg_color">Cor de Fundo do Botão</label></th>
                        <td><input type="color" name="<?php echo esc_attr( $this->option_name ); ?>[btn_bg_color]" id="btn_bg_color" value="<?php echo esc_attr( $data['btn_bg_color'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_text_color">Cor do Texto do Botão</label></th>
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
                        <th scope="row"><label for="privacy_link">Link da Política de Privacidade</label></th>
                        <td>
                            <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[privacy_link]" id="privacy_link" value="<?php echo esc_url( $data['privacy_link'] ); ?>" class="regular-text" placeholder="https://seudominio.com.br/politica-de-privacidade">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Salvar Configurações' ); ?>
            </form>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #2563eb; margin-top: 30px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h3 style="margin-top: 0;">Dica de Conformidade LGPD</h3>
                <p>Para permitir que os utilizadores revoguem o consentimento a qualquer momento (exigência legal), adicione o seguinte shortcode no rodapé (footer) do site do cliente:</p>
                <code>[vettryx_gerenciar_cookies texto="Gerir Preferências de Cookies"]</code>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza o shortcode para o utilizador poder revogar os cookies.
     */
    public function render_revoke_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'texto'  => 'Gerir Preferências de Cookies',
            'classe' => ''
        ], $atts );

        return '<a href="#" id="vettryx-revoke-cookies" class="' . esc_attr( $atts['classe'] ) . '">' . esc_html( $atts['texto'] ) . '</a>';
    }

    // Renderiza o banner de cookies no rodapé do site, aplicando as configurações definidas pelo usuário
    public function render_cookie_banner() {
        $data = get_option( $this->option_name, [ 'enable_native' => '1', 'bg_color' => '#111827', 'text_color' => '#f9fafb', 'btn_bg_color' => '#2563eb', 'btn_text_color' => '#ffffff' ] );
        
        // Renderiza o visual APENAS se o banner nativo estiver ativado
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

        // O cérebro JavaScript SEMPRE é injetado, mesmo se o visual nativo estiver desligado
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var banner = document.getElementById('vettryx-cookie-banner');
            var hasConsent = document.cookie.indexOf('wp_consent_marketing=allow') !== -1;
            
            // Exibe o banner nativo (se ele existir no ecrã e não houver consentimento)
            if (!hasConsent && banner) {
                banner.style.display = 'flex';
            }
            
            // Delegação de evento: Escuta cliques na página inteira
            document.addEventListener('click', function(event) {
                var target = event.target;
                
                // 1. Ação de ACEITAR COOKIES
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

                // 2. Ação de REVOGAR COOKIES (Exigência LGPD)
                if (target.id === 'vettryx-revoke-cookies' || target.closest('#vettryx-revoke-cookies')) {
                    event.preventDefault();
                    
                    if (typeof wp_set_consent === 'function') {
                        wp_set_consent('marketing', 'deny');
                        wp_set_consent('statistics', 'deny');
                    } else {
                        // Força a expiração dos cookies no navegador
                        document.cookie = "wp_consent_marketing=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                        document.cookie = "wp_consent_statistics=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    }

                    var revokeEvent = new CustomEvent('wp_consent_type_defined');
                    document.dispatchEvent(revokeEvent);

                    // Recarrega a página para o banner voltar e os scripts serem bloqueados
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
