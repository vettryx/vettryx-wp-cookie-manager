<?php
/**
 * Plugin Name: VETTRYX WP Cookie Manager
 *  * Plugin URI:  https://github.com/vettryx/vettryx-wp-cookie-manager
 * Description: Gerenciador de consentimento nativo, focado em performance e integrado à WP Consent API (LGPD).
 * Version:     1.1.0
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

    // Nome da opção onde as configurações serão armazenadas no banco de dados
    private $option_name = 'vettryx_cookie_settings';

    // Construtor: Registra os hooks necessários para o funcionamento do plugin
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
        } else {
            add_action( 'wp_footer', [ $this, 'render_cookie_banner' ], 99 );
        }

        add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
    }

    // Adiciona a página de configurações do plugin como um submenu do VETTRYX Core Modules
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

    // Registra as configurações do plugin, definindo o tipo de dado e a função de sanitização
    public function register_settings() {
        register_setting( 'vettryx_cookie_group', $this->option_name, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_data' ]
        ] );
    }

    // Função de sanitização: Garante que os dados salvos sejam seguros e estejam no formato correto
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
            'banner_text'    => sanitize_textarea_field( $input['banner_text'] ?? 'Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com a nossa Política de Privacidade.' ),
            'button_text'    => sanitize_text_field( $input['button_text'] ?? 'Aceitar e Continuar' ),
            'privacy_link'   => esc_url_raw( $input['privacy_link'] ?? '' ),
        ];
    }

    // Renderiza a página de configurações do plugin no painel administrativo
    public function render_admin_page() {
        $data = get_option( $this->option_name, [
            'enable_native'  => '1',
            'bg_color'       => '#111827',
            'text_color'     => '#f9fafb',
            'btn_bg_color'   => '#2563eb',
            'btn_text_color' => '#ffffff',
            'banner_text'    => 'Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com a nossa Política de Privacidade.',
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
                            <span class="description">Desmarque esta opção se você for construir o popup de cookies usando o Elementor (Basta colocar o ID <b>vettryx-accept-cookies</b> no botão do seu Elementor).</span>
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
        </div>
        <?php
    }

    // Renderiza o banner de cookies no rodapé do site, aplicando as configurações definidas pelo usuário
    public function render_cookie_banner() {
        $data = get_option( $this->option_name, [ 'enable_native' => '1', 'bg_color' => '#111827', 'text_color' => '#f9fafb', 'btn_bg_color' => '#2563eb', 'btn_text_color' => '#ffffff' ] );
        
        // Renderiza o banner nativo apenas se a opção estiver habilitada. O JavaScript para controle do consentimento é sempre injetado, garantindo que o botão funcione mesmo com um design personalizado via Elementor/Divi.
        if ( $data['enable_native'] === '1' ) {
            $text = !empty($data['banner_text']) ? esc_html($data['banner_text']) : 'Utilizamos cookies para melhorar sua experiência.';
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

        // JavaScript para controlar o consentimento, funcionando tanto para o banner nativo quanto para um design personalizado via Elementor/Divi (basta usar o ID vettryx-accept-cookies no botão de consentimento do seu popup).
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var banner = document.getElementById('vettryx-cookie-banner');
            var hasConsent = document.cookie.indexOf('wp_consent_marketing=allow') !== -1;
            
            // Exibe o banner nativo apenas se o consentimento ainda não tiver sido dado. Se o banner estiver desativado, presume-se que o site já tem um mecanismo personalizado e o script de consentimento funcionará normalmente.
            if (!hasConsent && banner) {
                banner.style.display = 'flex';
            }
            
            // Adiciona um listener global para cliques, permitindo que o botão funcione tanto no banner nativo quanto em um design personalizado via Elementor/Divi (basta usar o ID vettryx-accept-cookies no botão de consentimento do seu popup).
            document.addEventListener('click', function(event) {
                var target = event.target;
                
                if (target.id === 'vettryx-accept-cookies' || target.closest('#vettryx-accept-cookies')) {
                    event.preventDefault();
                    
                    // Animação de saída suave para o banner nativo, se estiver presente
                    if (banner) {
                        banner.style.transform = 'translateY(100%)';
                        setTimeout(function() { banner.style.display = 'none'; }, 300);
                    }

                    // Define o consentimento usando a WP Consent API, se disponível. Caso contrário, define os cookies manualmente como fallback de segurança.
                    if (typeof wp_set_consent === 'function') {
                        wp_set_consent('marketing', 'allow');
                        wp_set_consent('statistics', 'allow');
                    } else {
                        // Fallback manual: Define os cookies de consentimento por 1 ano, garantindo que o site funcione mesmo sem a WP Consent API (embora seja recomendado usar a API para melhor compatibilidade e controle).
                        document.cookie = "wp_consent_marketing=allow; max-age=31536000; path=/; secure; samesite=lax";
                        document.cookie = "wp_consent_statistics=allow; max-age=31536000; path=/; secure; samesite=lax";
                    }
                    
                    var consentEvent = new CustomEvent('wp_consent_type_defined');
                    document.dispatchEvent(consentEvent);
                    
                    window.location.reload();
                }
            });
        });
        </script>
        <?php
    }
}

// Inicializa o plugin, garantindo que a classe seja instanciada apenas uma vez para evitar conflitos com outros plugins ou temas que possam usar o mesmo nome de classe.
new Vettryx_Cookie_Manager();
