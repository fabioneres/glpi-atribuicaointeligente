# Changelog

Todas as mudanças relevantes do plugin Atribuição Inteligente.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e as versões
seguem o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

Esta linha de versões (1.x) é compatível **somente com GLPI 10.0.x**.

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

As notas das versões anteriores estão nas
[releases do GitHub](https://github.com/fabioneres/glpi-atribuicaointeligente/releases).
