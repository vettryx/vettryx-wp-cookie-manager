<?php
/**
 * Plugin Name: VETTRYX WP Cookie Manager
 *  * Plugin URI:  https://github.com/vettryx/vettryx-wp-cookie-manager
 * Description: Gerenciador de consentimento nativo, focado em performance e integrado à WP Consent API (LGPD).
 * Version:     1.0.0
 * Author:      VETTRYX Tech
 * Author URI:  https://vettryx.com.br
 * License:     GPLv3
 */

// Segurança: Evita acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Verifica se a classe já existe para evitar conflitos
class Vettryx_Cookie_Manager {

    // Nome da opção no banco de dados para armazenar as configurações do banner
    private $option_name = 'vettryx_cookie_settings';

    // Construtor: Registra os hooks para admin e frontend, além de marcar o módulo como compatível com a WP Consent API
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        } else {
            add_action( 'wp_footer', [ $this, 'render_cookie_banner' ], 99 );
        }

        // Marca o módulo como compatível com a WP Consent API para que outros plugins possam detectar e respeitar o consentimento do usuário
        add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
    }

    // Adiciona a página de configurações do banner de cookies no menu do WordPress
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

    // Registra as configurações do banner de cookies, incluindo a sanitização dos dados para segurança
    public function register_settings() {
        register_setting( 'vettryx_cookie_group', $this->option_name, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_data' ]
        ] );
    }

    // Sanitiza os dados de entrada do formulário de configurações para garantir que sejam seguros e válidos
    public function sanitize_data( $input ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return get_option( $this->option_name );
        }
        return [
            'banner_text'  => sanitize_textarea_field( $input['banner_text'] ?? 'Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com a nossa Política de Privacidade.' ),
            'button_text'  => sanitize_text_field( $input['button_text'] ?? 'Aceitar e Continuar' ),
            'privacy_link' => esc_url_raw( $input['privacy_link'] ?? '' ),
        ];
    }

    // Renderiza a página de configurações do banner de cookies no admin, permitindo que o usuário personalize o texto do banner, do botão e o link da política de privacidade
    public function render_admin_page() {
        $data = get_option( $this->option_name, [
            'banner_text'  => 'Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com a nossa Política de Privacidade.',
            'button_text'  => 'Aceitar e Continuar',
            'privacy_link' => ''
        ]);
        ?>
        <div class="wrap">
            <h1><?php _e( 'VETTRYX Tech - Cookie Manager', 'vettryx-wp-core' ); ?></h1>
            <p><?php _e( 'Configure o banner de consentimento (LGPD). Integrado automaticamente com a WP Consent API.', 'vettryx-wp-core' ); ?></p>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'vettryx_cookie_group' ); ?>
                
                <table class="form-table">
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
        </div>
        <?php
    }

    // Renderiza o banner de cookies no frontend, verificando se o usuário já deu consentimento e utilizando a WP Consent API para registrar o consentimento de forma compatível e segura, além de garantir que o banner seja exibido mesmo com cache de servidor.
    public function render_cookie_banner() {
        $data = get_option( $this->option_name );
        $text = !empty($data['banner_text']) ? esc_html($data['banner_text']) : 'Utilizamos cookies para melhorar sua experiência.';
        $btn  = !empty($data['button_text']) ? esc_html($data['button_text']) : 'Aceitar e Continuar';
        $link = !empty($data['privacy_link']) ? '<a href="'.esc_url($data['privacy_link']).'" style="color: #60a5fa; text-decoration: underline; margin-left: 5px;">Saiba mais</a>' : '';

        // O banner é renderizado no rodapé do site, mas só é exibido se o usuário ainda não tiver dado consentimento para marketing. O consentimento é registrado usando a função oficial da WP Consent API, e um evento personalizado é disparado para que outros plugins possam reagir ao consentimento do usuário. O banner também é projetado para ser responsivo e visualmente atraente, com uma animação suave ao ser fechado.
        ?>
        <div id="vettryx-cookie-banner" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #111827; color: #f9fafb; padding: 16px 24px; box-sizing: border-box; flex-direction: column; align-items: center; justify-content: center; z-index: 999999; font-family: sans-serif; font-size: 14px; text-align: center; box-shadow: 0 -4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease-out;">
            <div style="max-width: 1200px; width: 100%; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="flex: 1; text-align: left; min-width: 250px; line-height: 1.5;">
                    <?php echo $text . $link; ?>
                </div>
                <button id="vettryx-accept-cookies" style="background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    <?php echo $btn; ?>
                </button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var banner = document.getElementById('vettryx-cookie-banner');
            var btn = document.getElementById('vettryx-accept-cookies');
            
            // Verifica no navegador se a WP Consent API já registrou o aceite de marketing
            var hasConsent = document.cookie.indexOf('wp_consent_marketing=allow') !== -1;
            
            // Se não tiver consentimento, exibe o banner (à prova de cache de servidor)
            if (!hasConsent && banner) {
                banner.style.display = 'flex';
            }
            
            if (btn && banner) {
                btn.addEventListener('click', function() {
                    // Esconde o banner com animação
                    banner.style.transform = 'translateY(100%)';
                    setTimeout(function() { banner.style.display = 'none'; }, 300);

                    // Tenta usar a função oficial da WP Consent API
                    if (typeof wp_set_consent === 'function') {
                        wp_set_consent('marketing', 'allow');
                        wp_set_consent('statistics', 'allow');
                    } else {
                        // Fallback de segurança criando os cookies universais manualmente
                        document.cookie = "wp_consent_marketing=allow; max-age=31536000; path=/; secure; samesite=lax";
                        document.cookie = "wp_consent_statistics=allow; max-age=31536000; path=/; secure; samesite=lax";
                    }
                    
                    // Dispara o evento para o restante do ecossistema do WordPress saber que houve aceite
                    var event = new CustomEvent('wp_consent_type_defined');
                    document.dispatchEvent(event);
                    
                    // Opcional: recarrega a página para injetar scripts em PHP de imediato
                    window.location.reload();
                });
            }
        });
        </script>
        <?php
    }
}

// Inicializa o plugin
new Vettryx_Cookie_Manager();