# Historico - Atribuicao Inteligente

Este documento registra o historico tecnico e funcional do plugin
**Atribuicao Inteligente**, usado para atribuicao automatica de chamados no
GLPI 10.

## Resumo geral

O Atribuicao Inteligente e um plugin GLPI derivado do modulo SmartAssign do
NexTool, transformado em plugin independente para GLPI 10. Ele preserva a
logica original de atribuicao automatica e acrescenta regras de
disponibilidade, escala e controle por entidade.

Estado atual: plugin funcional em homologacao, versao **1.1.5**, publicado em
repositorio privado como `glpi-atribuicaointeligente`.

## Objetivo

Automatizar a atribuicao de chamados no GLPI considerando:

- categoria do chamado;
- grupo encarregado da categoria;
- tecnicos pertencentes ao grupo;
- indisponibilidades cadastradas;
- escala de atendimento;
- calendario da entidade, quando habilitado;
- entidade onde o plugin esta ativo;
- preservacao de atribuicoes manuais ja existentes.

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
