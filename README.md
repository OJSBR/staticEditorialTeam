# Static Editorial Team — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.0.2.0-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS / OMP 3.5](https://github.com/OJSBR/staticEditorialTeam/releases/download/1.0.2.0/staticEditorialTeam-1.0.2.0.tar.gz) — or browse all [Releases](../../releases).

A generic plugin for **Open Journal Systems (OJS)** that brings back the **static Editorial
Team page** of earlier OJS versions: the page shows the free text configured in the journal
settings instead of the dynamic listing OJS 3.5 builds from user roles — **without patching
OJS core**.

> **Developed and maintained by [OJSBR](https://ojsbr.com).** See the
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| Application | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x and OMP 3.5.x | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.0.2.0 |

> Since 1.0.2.0 the same package serves OJS and OMP. The former `staticEditorialTeamOmp`
> repository is archived; its releases stay available there.

## The problem

Up to OJS 3.4, the *Editorial Team* page displayed a rich-text field edited in
**Settings → Journal → Masthead** (`editorialTeam`).

OJS 3.5 replaced that page with a **dynamic** one, assembled from the users and roles flagged
for the masthead. The free-text field was renamed to `editorialHistory` ("Editorial History")
by the `I9937_EditorialTeamToEditorialHistory` migration, and now only shows at the bottom of
the new *Editorial History* page.

## What it does

With the plugin enabled, the **Editorial Team** page (`/about/editorialMasthead` — same URL and
same navigation menu item as stock 3.5) shows the journal's **free text** again, instead of the
dynamic user listing. The content keeps being edited in the very same place,
**Settings → Journal → Editorial Team**; the plugin renames that field's label back to
"Editorial Team" and adjusts its description, so it is clear where the text is published.

## Installation

1. Download the release (or clone the branch).
2. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
   into `plugins/generic/` so you get `plugins/generic/staticEditorialTeam/`.
3. Enable **Static Editorial Team** under the *Generic* plugins list.

## Configuration

Open the plugin settings. Every option is per journal:

| Option | Default | Effect |
| --- | --- | --- |
| Static content only | ✔ | Behaviour of earlier versions: only the journal's text. |
| Static content, then the dynamic list | — | Text first, automatic user listing below it. |
| Dynamic list, then the static content | — | Automatic listing first, text below it. |
| Display last year's peer reviewers | ✖ | Keeps the OJS 3.5 reviewers section. |
| Display the link to Editorial History | ✖ | Keeps the paragraph linking to `/about/editorialHistory`. |
| Do not repeat the content on the Editorial History page | ✔ | Avoids the same text on both pages. |
| Rename the field in the journal settings | ✔ | Shows the field as "Editorial Team" in the admin form. |

## How it works (technical)

- A `TemplateManager::display` hook swaps **only the template name** — that variable is passed by
  reference — so headers, the session cookie and the compile id are still handled by OJS.
- The plugin template renders `editorialHistory` (filtered with `strip_unsafe_html`) and,
  depending on the mode, the same markup the core template uses (roles, users, ORCID,
  reviewers), so theme styles keep applying. A theme that replaces the core template of this
  page is not used while the plugin is on. A test compares the copies with the core templates of
  the installed OJS, so a change in a new OJS release shows up.
- The settings field is relabelled through the `Form::config::after` hook on the `masthead` form,
  which is narrower than overriding the locale key globally.
- Nothing is written to the database: the content is the context setting OJS already stores.

### A documented exception to the OJSBR plugin standard

Our own standard asks plugins never to replace a core template. This plugin does replace two —
`frontend/pages/editorialMasthead.tpl` and `frontend/pages/editorialHistory.tpl` — because OJS 3.5
offers no other way: the hooks of `AboutContextHandler` receive the data by value, and the core
templates of these pages call no hook of their own, so there is nowhere to add the journal's text
or to leave out the automatic listing. The exception is deliberate and bounded:

- the copies are the core templates with the plugin's conditions added, and a test compares them
  with the core templates of the installed OJS, so an OJS release that changes those pages is
  caught by the suite instead of silently drifting;
- only these two pages are affected, and only while the plugin is enabled;
- a theme that replaces the core template of these pages is not used while the plugin is on.

## Tests

- **PHPUnit** (`tests/*Test.php`, on `PKP\tests\PKPTestCase`): the classes against the installed
  PKP, the plugin found by PKP's plugin registry, settings falling back to safe defaults, the
  template swapped on the Editorial Team and Editorial History pages according to the settings
  (and left alone elsewhere), only the text field of the masthead form relabelled, the copies
  matching the listings of the installed core templates, the journal text filtered as HTML, the
  templates and the 38 translations. From the OJS root:

  ```bash
  lib/pkp/lib/vendor/bin/phpunit --configuration lib/pkp/tests/phpunit.xml --no-coverage "$PWD/plugins/generic/staticEditorialTeam/tests"
  ```

- **Cypress** (`cypress/tests/functional/StaticEditorialTeam.cy.js`, run by
  [pkp-github-actions](https://github.com/pkp/pkp-github-actions) on every push): enables the
  plugin, sets the journal text (with a script that must not run) and, through the settings
  form, checks the static-only page, the text kept off the Editorial History page, and the text
  after the listing with the history link. The journal text and the settings are put back after
  the run. Each check fails with the part it covers removed.
- Verified on OJS 3.5.0.3 and OMP 3.5.0.5, the same package on both.

Tests are kept in the repository and are not part of the release package.

## Credits & authorship

- **Developed and maintained by** [OJSBR](https://ojsbr.com) — original plugin.
- Distributed under the **GNU GPL v3**.

## AI use

Generative AI (Claude, by Anthropic) was used to write and run tests, improve the code and bring
it in line with PKP standards. Every change is reviewed and tested by OJSBR, which is responsible
for the published releases.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version you
are working against. See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que restabelece a **página estática de
Equipe Editorial** das versões anteriores: a página volta a exibir o texto livre configurado nas
definições da revista, no lugar da lista dinâmica que o OJS 3.5 monta a partir dos papéis de
usuário — **sem alterar o núcleo do OJS**.

> **Desenvolvido e mantido pela [OJSBR](https://ojsbr.com).** Veja a seção
> [Créditos e autoria](#créditos-e-autoria) abaixo.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | `stable-3_5_0` *(padrão)* | 1.0.2.0 |

### O problema

Até o OJS 3.4, a página *Equipe Editorial* exibia um texto livre editado em
**Configurações → Revista → Equipe Editorial** (campo `editorialTeam`).

No OJS 3.5 essa página passou a ser **dinâmica**, montada automaticamente a partir dos usuários
e papéis marcados para o expediente. O campo de texto livre foi renomeado para
`editorialHistory` ("Histórico Editorial") pela migração `I9937_EditorialTeamToEditorialHistory`
e passou a aparecer apenas no rodapé da nova página *Histórico Editorial*.

### O que faz

Com o plugin ativado, a página **Equipe Editorial** (`/about/editorialMasthead`, o mesmo endereço
e o mesmo item de menu do 3.5) volta a exibir o **texto livre da revista** no lugar da lista
dinâmica. O conteúdo continua sendo editado no mesmo lugar de sempre,
**Configurações → Revista → Equipe Editorial** — o plugin devolve a esse campo o rótulo "Equipe
Editorial" e ajusta a descrição, para não haver dúvida sobre onde o texto aparece.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a pasta em
`plugins/generic/` (ficando `plugins/generic/staticEditorialTeam/`). Depois ative a **Equipe
Editorial Estática** na lista de plugins *Genéricos*.

### Configuração

Nas configurações do plugin, por revista:

| Opção | Padrão | Efeito |
| --- | --- | --- |
| Somente o conteúdo estático | ✔ | Comportamento das versões anteriores: só o texto da revista. |
| Estático e, abaixo, a lista dinâmica | — | Texto da revista e a lista automática de usuários. |
| Lista dinâmica e, abaixo, o estático | — | Lista automática e, abaixo, o texto da revista. |
| Exibir pareceristas do ano anterior | ✖ | Mantém a seção de pareceristas do OJS 3.5. |
| Exibir o link para o Histórico Editorial | ✖ | Mantém o parágrafo com link para `/about/editorialHistory`. |
| Não repetir o conteúdo no Histórico Editorial | ✔ | Evita o mesmo texto nas duas páginas. |
| Renomear o campo nas configurações da revista | ✔ | Exibe o campo como "Equipe Editorial" no painel. |

### Como funciona (técnico)

- Um hook `TemplateManager::display` troca **apenas o nome do template** (a variável vem por
  referência), então cabeçalhos, cookie de sessão e compile id continuam por conta do OJS.
- O template do plugin renderiza o `editorialHistory` e, conforme o modo, a mesma marcação do
  template do núcleo (papéis, usuários, ORCID, pareceristas), preservando o tema.
- O rótulo do campo é ajustado pelo hook `Form::config::after` no formulário `masthead` — mais
  preciso do que sobrescrever a chave de tradução globalmente.
- Nada é gravado no banco: o conteúdo é a configuração de contexto que o OJS já armazena.

### Idiomas

Interface do plugin traduzida em **português (Brasil), inglês, espanhol, francês, italiano e
alemão**.

### Uma exceção documentada ao padrão de plugins da OJSBR

Nosso padrão pede que plugin nenhum substitua template do núcleo. Este substitui dois —
`frontend/pages/editorialMasthead.tpl` e `frontend/pages/editorialHistory.tpl` — porque o OJS 3.5
não oferece outro caminho: os hooks do `AboutContextHandler` recebem os dados por valor, e os
templates do núcleo dessas páginas não chamam hook nenhum, então não há onde inserir o texto da
revista nem onde deixar de fora a lista automática. A exceção é deliberada e limitada:

- as cópias são os templates do núcleo com as condições do plugin, e um teste as compara com os
  templates do núcleo do OJS instalado, de modo que uma versão nova do OJS que mexa nessas páginas
  aparece na suíte em vez de divergir calada;
- só essas duas páginas são afetadas, e só enquanto o plugin está ativado;
- um tema que substitua o template do núcleo dessas páginas não é usado com o plugin ligado.

### Testes

PHPUnit em `tests/` (sobre `PKP\tests\PKPTestCase`) e Cypress em `cypress/tests/functional/`
(rodado pelo [pkp-github-actions](https://github.com/pkp/pkp-github-actions) a cada push), com o
comando da seção em inglês. A suíte cobre as classes contra o PKP instalado, o plugin encontrado
pelo registro de plugins, os padrões das configurações, a troca de template nas páginas Equipe
Editorial e Histórico Editorial conforme as opções, só o campo de texto do formulário renomeado, as
cópias batendo com os templates do núcleo instalado, o texto filtrado como HTML, os templates e as
38 traduções. O Cypress liga o plugin, grava o texto da revista (com um script que não pode rodar)
e confere a página só com o texto, o texto fora do Histórico Editorial e o texto depois da lista com
o link do histórico; texto e configurações voltam ao que eram no fim. Verificado no OJS 3.5.0.3 e no OMP 3.5.0.5, com o mesmo pacote.

Os testes ficam no repositório e não fazem parte do pacote da release.

### Créditos e autoria

- **Desenvolvido e mantido pela** [OJSBR](https://ojsbr.com) — plugin autoral.
- Distribuído sob a **GNU GPL v3**.

### Uso de IA

Foi usada IA generativa (Claude, da Anthropic) para escrever e rodar testes, melhorar o código e
alinhá-lo aos padrões da PKP. Toda mudança é revisada e testada pela OJSBR, que responde pelas
releases publicadas.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
