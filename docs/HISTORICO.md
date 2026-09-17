# Historico - Atribuicao Inteligente

Este documento registra o historico tecnico e funcional do plugin
**Atribuicao Inteligente**, usado para atribuicao automatica de chamados no
GLPI 10.

## Resumo geral

O Atribuicao Inteligente e um plugin GLPI derivado do modulo SmartAssign do
NexTool, transformado em plugin independente para GLPI 10. Ele preserva a
logica original de atribuicao automatica e acrescenta regras de
disponibilidade, escala e controle por entidade.

Estado atual: plugin funcional em homologacao, versao **1.3.2**, publicado em
repositorio privado como `glpi-atribuicaointeligente`.

## Alteracoes da versao 1.3.2

Release exclusivamente de seguranca, a partir do Security Release Gate aplicado
a 1.3.1 (relatorio em `docs/atribuicaointeligente/validacoes/security-gate-1.3.1.md`
no workspace). Nenhuma funcionalidade nova.

### Atencao ao atualizar

Dois ajustes apertam permissoes e mudam comportamento visivel para perfis com o
direito do plugin restrito a uma subentidade:

1. Nao e mais possivel criar, editar, ativar/desativar ou excluir
   indisponibilidade e escala com entidade "Todas / global" (`entities_id = 0`)
   nem de entidade ancestral. Isso passa a exigir acesso direto aquela entidade.
   Registros globais continuam visiveis na listagem, mas sem botao de edicao.
2. A aba Categorias passa a listar apenas regras de categorias das entidades
   visiveis, e a acao em massa "Alterar grupo responsavel" exige acesso direto a
   entidade da categoria e um grupo valido para ela.

Quem administra a partir da entidade raiz nao e afetado.

### Corrigido

- Isolamento entre entidades na escrita de indisponibilidades e escalas. O
  helper de entidade usava a flag recursiva de `Session::haveAccessToEntity()`,
  que autoriza entidades ancestrais e a raiz. A flag serve a leitura de item
  recursivo, nao a gravacao. Passa a seguir a mesma assimetria do core, onde
  `CommonDBTM::canViewItem()` usa `checkEntity(true)` e
  `CommonDBTM::canUpdateItem()` usa `checkEntity()`.
- Isolamento entre entidades nas regras de categoria. A tabela de regras nao
  tem `entities_id`, entao `isEntityAssign()` e falso e o Search nao aplicava
  restricao de entidade. Adicionado
  `plugin_atribuicaointeligente_addDefaultWhere()`, que restringe a pesquisa
  pela entidade da categoria ITIL, e `canViewItem()`/`canUpdateItem()` no
  itemtype.
- Acao em massa "Alterar grupo responsavel", que grava em `glpi_itilcategories`
  (tabela nativa), validava apenas o direito global do plugin. Agora valida
  acesso a entidade da categoria e se o grupo escolhido e valido para ela.
- Reconcessao automatica de direitos. `syncCurrentProfileRight()` roda em
  `plugin_init()` a cada requisicao de qualquer usuario autenticado e chamava a
  reparacao de perfis, que executa `UPDATE` em `glpi_profilerights`. Uma
  revogacao deliberada do administrador era desfeita em silencio no acesso
  seguinte. O metodo passa a ser somente leitura; a reparacao acontece apenas
  na instalacao/atualizacao.
- DDL em runtime. `ALTER TABLE`, `SHOW INDEX` e `SHOW COLUMNS` eram executados a
  cada requisicao, inclusive nos hooks de chamado. DDL provoca COMMIT implicito
  no MySQL/MariaDB e podia confirmar trabalho parcial de uma transacao do core,
  alem de travar tabelas. Todo o DDL foi concentrado no passo `schema` da
  instalacao/atualizacao.
- Dado pessoal no log de decisao. A justificativa da ausencia (`comment`) era
  copiada para o motivo da decisao e ficava visivel na aba Logs para qualquer
  perfil com leitura do plugin. O motivo passa a registrar apenas tipo e
  periodo.
- `addslashes()` substituido por `DBmysql::quoteValue()` na montagem de SQL do
  relatorio de distribuicoes e do log. Nao havia injecao explorável, porque os
  valores ja passavam por regex e whitelist, mas `addslashes` nao e o mecanismo
  do GLPI nem e charset-aware.

### Removido

- `front/available_users.ajax.php`. Endpoint sem nenhuma referencia no plugin,
  acessivel a qualquer usuario com acesso central e fora de `ajax/`, onde o core
  preserva o token CSRF. Movido para `lixeira/2026-09-17/atribuicaointeligente/`
  no workspace.

## Objetivo

Automatizar a atribuicao de chamados no GLPI considerando:

- categoria do chamado;
- grupo encarregado da categoria;
- tecnicos pertencentes ao grupo;
- indisponibilidades cadastradas;
- escala de atendimento;
- calendario da entidade, quando habilitado;
- entidade onde o plugin esta ativo;
- preservacao de atribuicoes manuais ja existentes;
- rastreabilidade de distribuicoes manuais, automaticas e transferencias de entidade.

A ideia central e distribuir chamados automaticamente sem remover tecnicos dos
grupos e sem alterar o core do GLPI.

## Regras de negocio

O plugin so atua quando a entidade do chamado esta habilitada.

A atribuicao automatica ocorre quando:

- o chamado possui categoria ITIL;
- a categoria esta ativa no plugin;
- a categoria possui grupo encarregado;
- o grupo possui tecnicos elegiveis;
- o chamado ainda nao possui tecnico atribuido;
- ha pelo menos um tecnico disponivel.

O plugin ignora tecnicos que estejam:

- em ferias;
- ausentes em uma data especifica;
- em ausencia temporaria;
- indisponiveis em determinado dia da semana;
- fora da escala de atendimento cadastrada;
- fora do calendario da entidade, quando essa opcao estiver habilitada;
- marcados como gerente do grupo, caso a opcao de excluir gerentes esteja ativa.

Atribuicoes manuais existentes nao sao sobrescritas.

Na atribuicao manual, o plugin nao interfere mais diretamente no dropdown nativo
do GLPI, para evitar quebra de buscas e acoes em massa. A protecao ocorre no
backend: se tentarem gravar tecnico indisponivel em entidade habilitada, a
gravacao e bloqueada.

## Decisoes arquiteturais

O plugin foi mantido como plugin GLPI tradicional, sem alterar o core.

A arquitetura preserva o comportamento original do SmartAssign, mas separa
responsabilidades em classes especificas:

- configuracao global;
- configuracao por entidade;
- atribuicao por categoria;
- checagem de disponibilidade;
- indisponibilidades;
- escala de atendimento;
- logs de decisao;
- logs de distribuicao;
- hooks de ticket;
- permissoes por perfil.

Foi evitada interceptacao agressiva de campos nativos do GLPI via JavaScript,
porque isso causava falha nos dropdowns de tecnicos, filtros e acoes em massa.

A decisao atual e mais segura para producao: deixar o GLPI renderizar os campos
nativos e validar regras criticas no backend.

Logs foram limitados a entidades onde o plugin esta ativo, para reduzir volume
desnecessario.

A tela de logs recebeu paginacao para evitar travamentos quando houver muitos
registros.

## Estrutura de banco

Principais tabelas do plugin:

### glpi_plugin_atribuicaointeligente_configs

Armazena configuracao global:

- atribuir grupo automaticamente;
- tipo de atribuicao;
- modo de distribuicao;
- excluir gerentes;
- usar calendario da entidade;
- datas de criacao e alteracao.

### glpi_plugin_atribuicaointeligente_entity_configs

Controla onde o plugin esta habilitado:

- entidade;
- ativo ou inativo;
- datas de criacao e alteracao.

### glpi_plugin_atribuicaointeligente_assignments

Controla categorias habilitadas para atribuicao:

- categoria ITIL;
- ativo ou inativo;
- indice de rodizio.

### glpi_plugin_atribuicaointeligente_unavailabilities

Armazena indisponibilidades:

- tecnico;
- entidade;
- tipo;
- data inicial;
- data final;
- dia da semana;
- observacao;
- ativo ou inativo;
- datas de criacao e alteracao.

### glpi_plugin_atribuicaointeligente_work_schedules

Armazena escala de atendimento:

- tecnico;
- entidade;
- dias da semana;
- horario inicial;
- horario final;
- validade inicial e final;
- observacao;
- ativo ou inativo.

### glpi_plugin_atribuicaointeligente_decision_logs

Armazena decisoes de distribuicao:

- chamado;
- grupo;
- categoria;
- entidade;
- modo;
- tecnico escolhido;
- tecnicos ignorados;
- motivo;
- data e hora.

## Telas

O plugin possui telas integradas ao GLPI.

### Configuracoes

Permite configurar:

- atribuicao de grupo;
- exclusao de gerentes;
- tipo de atribuicao;
- modo de distribuicao;
- uso do calendario da entidade;
- entidades onde o plugin esta ativo.

### Categorias

Permite ativar ou desativar categorias para atribuicao automatica.

As categorias novas entram inativas por padrao, para evitar comportamento
inesperado apos instalacao.

### Indisponibilidades

Permite cadastrar, editar, listar, excluir ou desativar indisponibilidades de
tecnicos.

A tela administrativa separa a consulta por abas:

- ferias;
- ausencia temporaria;
- outras ausencias.

Na aba de ferias, os tecnicos sao agrupados por nome, com acesso aos periodos
cadastrados, proximas ferias do mes atual/proximo mes e sobreposicoes de
ferias ativas ou futuras.

Quando o cadastro recebe apenas a data, sem horario, o plugin considera o
periodo como dia inteiro: inicio `00:00:00` e fim `23:59:59`.

Na visualizacao de ferias, os periodos sao exibidos sem horario e no formato
`DD-MM-AAAA`. Cada tecnico pode ter no maximo 2 anos/cadastros de ferias, com
ate 3 periodos por ano, totalizando ate 6 periodos. No detalhe do tecnico, a
coluna de acoes permite acessar o formulario de edicao de cada periodo quando o
usuario possui permissao e acesso a entidade do registro.

Tipos suportados:

- ferias;
- ausencia temporaria;
- data especifica;
- recorrencia semanal.

### Escala de atendimento

Permite cadastrar dias e horarios em que um tecnico esta disponivel para
atendimento.

### Logs

Lista decisoes de atribuicao com paginacao.

Mostra:

- chamado;
- entidade;
- grupo;
- tecnico selecionado;
- tecnicos ignorados;
- motivo da decisao;
- data e hora.

### Sobre

Explica de forma simples como configurar e utilizar o plugin.

## Pendencias

Pontos que podem evoluir antes de considerar o plugin maduro para producao ampla:

- melhorar a experiencia da escala de atendimento com selecao mais pratica de
  dias;
- criar opcao inversa de disponibilidade, por exemplo tecnico atende apenas
  nesses dias;
- avaliar filtro visual no campo de atribuicao manual sem quebrar dropdowns
  nativos;
- criar testes automatizados ou scripts de validacao mais formais;
- revisar mensagens de erro para administradores;
- avaliar compatibilidade com GLPI 10.0.24 de forma pratica;
- avaliar compatibilidade com GLPI 11 somente se solicitado depois;
- reduzir warnings de instalacao relacionados a `DATETIME`, caso vire exigencia
  futura;
- melhorar documentacao de instalacao, upgrade e rollback;
- definir politica de retencao ou limpeza dos logs de decisao.

## Proximos passos recomendados

1. Validar a versao 1.1.5 em homologacao com chamados reais ou proximos do
   cenario real.
2. Confirmar comportamento por entidade: entidade ativa, entidade inativa e
   multiplas entidades.
3. Testar atribuicao automatica com categorias ativas e inativas.
4. Testar todos os tipos de indisponibilidade.
5. Testar escala de atendimento com tecnicos dentro e fora do horario.
6. Validar que atribuicao manual indisponivel e bloqueada somente onde o plugin
   esta ativo.
7. Validar acoes em massa do GLPI apos remocao do filtro JavaScript.
8. Revisar volume de logs apos alguns dias de uso.
9. Planejar melhoria de escala e disponibilidade semanal como proxima evolucao.

## Situacao atual

O plugin esta em um estagio avancado e funcional. A versao atual pode ser
tratada como pronta para homologacao forte e quase pronta para producao
controlada, especialmente depois de alguns dias validando os cenarios reais do
ambiente.
