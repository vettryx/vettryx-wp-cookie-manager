# VETTRYX WP Cookie Manager

> ⚠️ **Atenção:** Este repositório atua exclusivamente como um **Submódulo** do ecossistema principal `VETTRYX WP Core`. Ele não deve ser instalado como um plugin standalone (isolado) nos clientes.

Este submódulo é um gerenciador de consentimento de cookies nativo, 100% gratuito e focado em altíssima performance. Desenvolvido para substituir soluções pesadas como o Complianz, ele trabalha em sincronia com a **WP Consent API** para garantir a adequação à LGPD/GDPR sem sacrificar a velocidade do site.

## 🚀 Funcionalidades

* **Integração Declarativa (WP Consent API):** Não usa gambiarras. Ele se comunica com o protocolo oficial do WordPress. Quando o usuário aceita, qualquer plugin de terceiros (Site Kit, WooCommerce, PixelYourSite) reconhece o consentimento automaticamente e libera o rastreamento.
* **Gerador Automático de Política (LGPD/GDPR):** Cria uma página de Política de Privacidade e Cookies padrão, estruturada e juridicamente adequada com apenas 1 clique, já incluindo o shortcode de revogação de consentimento.
* **Liberdade de Design (Elementor/Divi):** Desative o visual nativo com 1 clique e crie popups de cookies maravilhosos diretamente no seu page builder favorito. Basta adicionar o ID `vettryx-accept-cookies` no botão de aceite do construtor e o módulo gerencia a lógica por trás dos panos.
* **Banner Nativo Personalizável:** Se não quiser usar um construtor, ative o banner nativo ultraleve e altere as cores de fundo, textos e botões direto pelo painel para combinar com o Brandbook do cliente.
* **À Prova de Cache (LiteSpeed):** O visual nativo é embutido oculto pelo PHP e exibido via JavaScript no front-end, garantindo compatibilidade total com plugins de cache agressivos sem causar "piscas" na tela.
* **Sincronia Nativa:** Trabalha em dupla perfeita com o módulo `Tracking Manager` da VETTRYX, segurando a injeção de scripts (GA4, Meta Pixel) até que o consentimento explícito seja dado.

## ⚙️ Arquitetura e Deploy (CI/CD)

O fluxo de deploy é 100% centralizado pelo Core:

1. Modificações neste repositório são rastreadas pelo repositório mãe (`vettryx-wp-core`).
2. O repositório do Core atualiza o ponteiro do submódulo na pasta `/modules/cookie-manager/`.
3. O GitHub Actions do Core empacota todo o ecossistema e distribui a atualização automágica (OTA) para os painéis dos clientes.

## 📖 Como Usar

Com o **VETTRYX WP Core** instalado e este módulo ativado, acesse **VETTRYX Tech > Cookie Manager**:

**Para gerar a Política de Privacidade:**
1. No painel do Cookie Manager, localize a caixa "Automação LGPD" na coluna da direita.
2. Clique em **Gerar Página de Política Padrão**.
3. Vá em "Páginas", revise o Rascunho gerado (ele já puxa o nome e email do seu site) e clique em Publicar.

**Para usar o Banner Nativo:**
1. Marque "Usar Banner Nativo".
2. Defina as cores da marca.
3. Personalize os textos, insira o link da página de Política de Privacidade que você acabou de criar e Salve.

**Para usar com Elementor / Divi / Gutenberg:**
1. Desmarque "Usar Banner Nativo" e Salve.
2. Crie seu popup no construtor de páginas.
3. No botão de aceitar cookies do seu popup, vá nas configurações avançadas e adicione o ID: `vettryx-accept-cookies`.

### ⚖️ Conformidade LGPD (Revogação de Consentimento)

A lei exige que o utilizador possa retirar o seu consentimento a qualquer momento de forma fácil. Para cumprir esta regra sem o peso de widgets flutuantes, o módulo disponibiliza um shortcode. 

Basta inserir o seguinte shortcode num widget de texto no rodapé (footer) do site do cliente *(Nota: a página gerada automaticamente já o inclui)*:

`[vettryx_gerenciar_cookies texto="Gerir Preferências de Cookies"]`

Isto irá gerar um link discreto. Quando clicado, o sistema apaga os cookies de permissão, bloqueia imediatamente o tracking e volta a exibir o banner de consentimento.

---

**VETTRYX Tech**
*Transformando ideias em experiências digitais.*
