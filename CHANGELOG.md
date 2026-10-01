# Changelog

Todas as mudanças relevantes do plugin Atribuição Inteligente.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e as versões
seguem o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

As versões **2.x** são compatíveis **somente com GLPI 11.0.x** (branch `main`), e as
versões **1.x**, **somente com GLPI 10.0.x** (branch `glpi10`).

## [2.0.0-rc.2] - 2026-10-01

Segunda versão candidata para GLPI 11. Corrige o que a validação navegada da rc.1 encontrou e duas
falhas de permissão herdadas da 1.x. Validada em GLPI 11.0.10.

### Segurança

- Um perfil com o direito do plugin restrito a uma subentidade conseguia ligar ou desligar o
  plugin na entidade raiz, pela aba Entidades, inclusive com "Habilitar todas" e "Desabilitar
  todas". A aba agora lista e altera só as entidades ativas do usuário. O problema vinha da 1.x.
- As ações em massa nativas "Atualizar" e "Excluir permanentemente" permitiam que um perfil
  restrito alterasse ou excluísse a regra de uma categoria de entidade superior, à qual ele não
  tem acesso direto. Essas ações agora seguem a mesma restrição de entidade da edição. O problema
  vinha da 1.x.

### Corrigido

- Ao salvar uma indisponibilidade ou uma escala, nova ou editada, o registro era gravado, mas a
  tela mostrava também "Erro ao gravar" e voltava ao formulário. Isso podia levar a cadastros
  duplicados.
- A ação em massa "Modificar" das regras de categoria parava com erro no GLPI 11.
- A aba Sobre dizia que o plugin não altera tabelas nativas. Agora descreve as duas gravações
  intencionais: o grupo encarregado da categoria e os atores e o status do chamado na
  distribuição.

## [2.0.0-rc.1] - 2026-09-30

Primeira versão para GLPI 11, publicada como **versão candidata**. Tem as mesmas
funcionalidades e correções da 1.3.3. A 2.0.0 final sai depois da validação navegada das telas
no GLPI 11. Validada em GLPI 11.0.9 e 11.0.10.

### Atenção ao atualizar

- Requer **GLPI 11.0.x** e **PHP 8.2** ou superior. O GLPI 10 continua na linha 1.x.
- As tabelas do plugin são as mesmas da 1.3.3. Ao levar o GLPI de 10 para 11, instale a
  2.x no lugar da 1.3.3 e execute a atualização do plugin em **Configurar > Plugins**.
  Faça backup do banco antes.

### Alterado

- Compatibilidade declarada: GLPI 11.0.0 a 11.0.99 e PHP 8.2 ou superior.
- CSS e ícones passaram para a pasta `public/`, a única que o GLPI 11 serve ao navegador.
  Os endereços continuam os mesmos.
- Links e formulários das páginas do plugin passaram a usar o endereço explícito do plugin,
  porque no GLPI 11 todas as requisições passam pelo `index.php`.
- Chamadas que o GLPI 11 marcou como obsoletas foram substituídas pelas equivalentes atuais.

### Corrigido

- Na instalação e na atualização, o direito do plugin era gravado duas vezes. O GLPI 11 trata
  isso como erro e interrompe a instalação.
- As regras de categoria não carregavam no GLPI 11 por incompatibilidade de assinatura com o
  core.
- O aviso de técnico indisponível na atribuição manual passou a escapar o nome do calendário,
  porque o GLPI 11 exibe esses avisos sem escape.

## [1.3.3] - 2026-09-24

### Atenção ao atualizar

- **Relatório de distribuições com "Todas" as entidades:** os blocos de distribuidores e de
  técnicos passam a mostrar apenas pessoas com perfil nas entidades que você tem ativas. Quem
  é de fora e distribuiu ou transferiu chamados para as suas entidades deixa de aparecer
  nesses blocos. Os indicadores e os totais de chamados não mudam.
- **Distribuição automática:** técnico sem acesso à entidade do chamado deixa de ser escolhido.
  Ele continua no grupo e aparece na aba Logs entre os técnicos ignorados, com o motivo
  "Sem acesso à entidade do chamado".
- **Aba Logs:** passa a abrir mostrando apenas as decisões em que o plugin atuou ou tentou
  atuar. As demais continuam gravadas e podem ser vistas pelo botão "Todas as decisões".

### Corrigido

- A paginação da aba Logs sempre mostrava a primeira página.
- No relatório de distribuições, com o filtro de entidade em "Todas" e mais de uma entidade
  ativa, os blocos de distribuidores e de técnicos mostravam pessoas de qualquer entidade.
- Ao filtrar o relatório por uma entidade específica, técnicos com perfil recursivo numa
  entidade superior deixavam de aparecer.
- A distribuição automática podia atribuir chamado a técnico sem acesso à entidade do
  chamado, que recebia a notificação sem conseguir abri-lo.

### Melhorado

- O filtro de entidade do relatório de distribuições ganhou busca por digitação.
- Os gráficos em barras verticais passaram a ser uma área única, com linha de base comum, em
  vez de uma caixa separada por coluna.
- O gráfico "Evolução no período" passou a exibir os meses abreviados ("ago", "set") e o ano
  no cabeçalho. Quando o período atravessa a virada do ano, o ano aparece junto ao primeiro
  mês de cada ano.
- O formulário de indisponibilidade deixou de gravar no log do plugin cada abertura e cada
  envio. Tentativas de acesso negado continuam registradas pelo próprio GLPI.
- README reorganizado como apresentação do plugin, com as funcionalidades agrupadas, o fluxo
  de decisão da distribuição e os canais de suporte.

## [1.3.2] - 2026-09-17

Release de segurança. Atualização recomendada para quem delega o direito do plugin a perfis
restritos a subentidades.

### Atenção ao atualizar

- Perfis com o direito do plugin restrito a uma subentidade não conseguem mais criar, editar
  ou excluir indisponibilidade e escala com entidade "Todas / global", nem de entidade
  ancestral. Esses registros continuam visíveis, sem botão de edição.
- A aba Categorias lista apenas as regras das entidades visíveis, e a ação em massa
  "Alterar grupo responsável" exige acesso direto à entidade da categoria.

Quem administra a partir da entidade raiz não é afetado.

### Segurança

- Indisponibilidades e escalas: a verificação de entidade autorizava gravação na raiz e em
  entidades ancestrais.
- Regras de categoria: a listagem não restringia entidade, e a ação em massa podia alterar o
  grupo responsável de categorias de outras entidades.
- O direito do plugin podia ser reconcedido automaticamente, desfazendo uma revogação feita
  pelo administrador.
- Alterações de estrutura de tabela deixaram de ser executadas durante o uso normal e
  passaram a ocorrer só na instalação e na atualização.
- A justificativa da ausência do técnico deixou de aparecer no motivo das decisões da aba
  Logs.
- Removido um endpoint sem uso que era acessível a qualquer usuário com acesso central.

## Versões anteriores

As versões 1.0.0 a 1.3.1 continuam disponíveis como tags do repositório, mas não são
recomendadas: todas têm as falhas de segurança corrigidas na 1.3.2. Para instalar ou
atualizar, use a
[versão mais recente](https://github.com/fabioneres/glpi-atribuicaointeligente/releases/latest).
