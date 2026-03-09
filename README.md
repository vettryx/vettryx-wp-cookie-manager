# VETTRYX WP Cookie Manager

> ⚠️ **Atenção:** Este repositório atua exclusivamente como um **Submódulo** do ecossistema principal `VETTRYX WP Core`. Ele não deve ser instalado como um plugin standalone (isolado) nos clientes.

Este submódulo é um gerenciador de consentimento de cookies nativo, 100% gratuito e focado em altíssima performance. Desenvolvido para substituir soluções pesadas como o Complianz, ele exibe um banner minimalista e trabalha em sincronia com a **WP Consent API** para garantir a adequação à LGPD/GDPR sem sacrificar a velocidade do site.

## 🚀 Funcionalidades

* **Integração Declarativa (WP Consent API):** Não usa gambiarras. Ele se comunica com o protocolo oficial do WordPress. Quando o usuário aceita, qualquer plugin de terceiros (Site Kit, WooCommerce, PixelYourSite) reconhece o consentimento automaticamente e libera o rastreamento.
* **À Prova de Cache (LiteSpeed):** O banner é embutido oculto pelo PHP e exibido via JavaScript no front-end, garantindo compatibilidade total com plugins de cache agressivos (como o LiteSpeed Cache) sem causar "piscas" na tela.
* **Performance Extrema:** Um único arquivo PHP ultra leve. Sem requisições desnecessárias ao banco de dados e sem carregar bibliotecas CSS/JS gigantes.
* **Sincronia Nativa:** Trabalha em dupla perfeita com o módulo `Tracking Manager` da VETTRYX, segurando a injeção de scripts (GA4, Meta Pixel) até que o consentimento explícito seja dado.
* **White-Label:** Painel enxuto e limpo, localizado dentro do menu "VETTRYX Tech" do cliente.

## ⚙️ Arquitetura e Deploy (CI/CD)

O fluxo de deploy é 100% centralizado pelo Core:

1. Modificações neste repositório são rastreadas pelo repositório mãe (`vettryx-wp-core`).
2. O repositório do Core atualiza o ponteiro do submódulo na pasta `/modules/cookie-manager/`.
3. O GitHub Actions do Core empacota todo o ecossistema e distribui a atualização automágica (OTA) para os painéis dos clientes.

## 📖 Como Usar

Com o **VETTRYX WP Core** instalado e este módulo ativado:

1. No menu lateral do WordPress, acesse **VETTRYX Tech > Cookie Manager**.
2. Personalize a mensagem de aviso.
3. Defina o texto do botão de aceite.
4. Insira a URL da página de Política de Privacidade do cliente.
5. Salve. O site já estará em conformidade com a LGPD.

---

**VETTRYX Tech**
*Transformando ideias em experiências digitais.*
