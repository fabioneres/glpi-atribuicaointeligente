# Relatorio tecnico - Atribuicao Inteligente

Este relatorio consolida o conhecimento tecnico acumulado sobre o plugin
**Atribuicao Inteligente**, incluindo objetivo, arquitetura, banco de dados,
funcionalidades implementadas, testes realizados, erros conhecidos, correcoes
aplicadas e estado atual do projeto.

## Identificacao

- Plugin: Atribuicao Inteligente
- Diretorio tecnico: `atribuicaointeligente`
- Autor do fork: Fabio Neres
- Versao atual: `1.1.8`
- Ultimo commit publicado conhecido: `2407c65 Adiciona habilitacao por entidade e pagina logs`
- Repositorio GitHub: `fabioneres/glpi-atribuicaointeligente`
- Pasta local inspecionada: `C:\Projetos\glpi\plugins\meusplugins\atribuicaointeligente`
- VM de homologacao: `192.168.159.129`
- GLPI na VM: `/var/www/html/glpi`
- Plugin na VM: `/var/www/html/glpi/plugins/atribuicaointeligente`

## Objetivo

O projeto nasceu como evolucao do modulo SmartAssign do plugin NexTool, com o
objetivo inicial de permitir que a atribuicao automatica de chamados ignorasse
tecnicos indisponiveis sem alterar o core do GLPI.

Durante a evolucao, a decisao arquitetural foi transformar o modulo em um plugin
standalone para GLPI 10, com banco proprio, permissoes, telas administrativas,
logs e regras de disponibilidade.

A proposta final do plugin e:

- atribuir chamados automaticamente a tecnicos;
- usar categoria ITIL como base;
- usar o grupo encarregado da categoria;
- distribuir por balanceamento ou rodizio;
- ignorar tecnicos indisponiveis;
- respeitar escala de atendimento;
- opcionalmente respeitar calendario da entidade;
- opcionalmente atribuir chamado atualizado quando a categoria ativa for informada e ainda nao houver tecnico;
- funcionar apenas em entidades habilitadas;
- registrar logs das decisoes;
- preservar atribuicoes manuais ja existentes;
- nao alterar core do GLPI;
- nao alterar schema de tabelas nativas.

## Base tecnica

- GLPI alvo principal: `10.0.25`
- Compatibilidade minima configurada: `10.0.0`
- Compatibilidade maxima configurada: `10.0.99`
- PHP alvo: `7.4+`
- Arquitetura: plugin GLPI classico
- Estrutura preservada: `setup.php`, `hook.php`, `inc/*.class.php`,
  `front/*.php`, `sql/install.sql`, `sql/uninstall.sql`

O plugin nao usa Composer, namespaces modernos nem pasta `src/`, porque foi
mantido no padrao classico compativel com GLPI 10 e com a origem
SmartAssign/NexTool.

## Arquitetura atual

### setup.php

Responsavel por:

- registrar versao e compatibilidade GLPI;
- carregar classes;
- registrar classes no GLPI;
- registrar hooks;
- registrar menu e pagina de configuracao;
- limpar cache de menu quando necessario;
- ativar CSS do plugin.

Hooks principais:

- `pre_item_add` em `Ticket`;
- `pre_item_add` em `Ticket_User`;
- `item_add` em `Ticket`;
- `item_add`, `item_delete` e `item_purge` em `ITILCategory`;
- `use_massive_action`.

### hook.php

Responsavel por:

- instalacao;
- upgrade;
- uninstall;
- execucao do `sql/install.sql`;
- criacao e reparo de permissoes;
- configuracao default;
- migracao de dados do Nextool/SmartAssign;
- sincronizacao de categorias;
- preferencias de exibicao;
- criacao de primeiro acesso para perfil ativo.

### inc/config.class.php

Classe central de configuracao. Concentra nomes de tabelas, permissoes, tabs,
menu, configuracao global, configuracao por entidade, asserts de permissao,
migracao do SmartAssign, reparo de schema e verificacao de entidade habilitada.

### inc/tickethookhandler.class.php

Classe principal da atribuicao automatica. Recebe eventos de criacao de ticket,
verifica entidade, categoria e grupo, preserva tecnico ja atribuido, escolhe o
tecnico por balanceamento ou rodizio, atribui tecnico/grupo, atualiza status
quando aplicavel, registra log e bloqueia atribuicao manual indevida no backend.

### inc/availabilitychecker.class.php

Decide se um tecnico esta disponivel. A ordem atual de verificacao e:

1. entidade habilitada;
2. calendario da entidade, quando a opcao esta ativa;
3. indisponibilidade cadastrada;
4. escala de atendimento.

Quando uma regra bloqueia o tecnico, a classe retorna um motivo textual.

### inc/assignmentsentity.class.php

Camada de acesso as regras de categoria/grupo. Sincroniza categorias ITIL,
mantem categorias novas inativas por padrao, busca o grupo encarregado da
categoria, le opcoes globais e atualiza indice de rodizio.

### inc/categoryassignment.class.php

Itemtype pesquisavel do GLPI para categorias. Controla listagem, campos de
busca, exibicao de entidade, grupo, status ativo e acoes em massa.

### inc/technicianunavailability.class.php

Modelo de indisponibilidade. Tipos suportados:

- `vacation`;
- `specific_date`;
- `weekly`;
- `temporary`.

### inc/technicianworkschedule.class.php

Modelo de escala de atendimento. Suporta tecnico, entidade, multiplos dias da
semana, horario inicial/final, validade inicial/final e ativo/inativo.

### inc/assignmentdecisionlog.class.php

Log estruturado das decisoes. Regra importante: so grava log quando a entidade
esta habilitada.

### inc/profile.class.php

Responsavel pela aba de permissoes nos perfis GLPI. Direito criado:
`plugin_atribuicaointeligente`.

## Estrutura de banco

O plugin usa apenas tabelas proprias com prefixo
`glpi_plugin_atribuicaointeligente_`.

### glpi_plugin_atribuicaointeligente_config_display

Tabela auxiliar para exibicao do item principal do plugin.

### glpi_plugin_atribuicaointeligente_configs

Configuracao global:

- `auto_assign_group`;
- `auto_assign_type`;
- `auto_assign_mode`;
- `exclude_managers`;
- `use_entity_calendar`;
- `assign_on_update`;
- `date_creation`;
- `date_mod`.

### glpi_plugin_atribuicaointeligente_entity_configs

Ativacao por entidade:

- `entities_id`;
- `is_active`;
- `date_creation`;
- `date_mod`.

Regra atual:

- entidades existentes sao semeadas como inativas na criacao da tabela para
  evitar distribuicao automatica inesperada em producao;
- entidades sem linha depois disso sao consideradas inativas;
- o plugin so atribui, bloqueia manualmente e grava log se a entidade estiver
  ativa.

### glpi_plugin_atribuicaointeligente_assignments

Categorias controladas pelo plugin:

- `itilcategories_id`;
- `is_active`;
- `last_assignment_index`.

Regra importante: categorias novas entram com `is_active = 0`.

### glpi_plugin_atribuicaointeligente_unavailabilities

Indisponibilidades:

- `users_id`;
- `entities_id`;
- `type`;
- `date_start`;
- `date_end`;
- `weekday`;
- `comment`;
- `is_active`;
- `date_creation`;
- `date_mod`.

### glpi_plugin_atribuicaointeligente_work_schedules

Escala de atendimento:

- `users_id`;
- `entities_id`;
- `weekdays`;
- `time_start`;
- `time_end`;
- `date_start`;
- `date_end`;
- `comment`;
- `is_active`;
- `date_creation`;
- `date_mod`.

Regra atual: tecnico sem escala ativa e considerado disponivel 24/7, salvo
indisponibilidade ou calendario da entidade.

### glpi_plugin_atribuicaointeligente_decision_logs

Logs estruturados:

- `tickets_id`;
- `groups_id`;
- `itilcategories_id`;
- `entities_id`;
- `mode`;
- `selected_users_id`;
- `ignored_users`;
- `reason`;
- `date_creation`.

Indices relevantes:

- `idx_ticket`;
- `idx_group`;
- `idx_selected_user`;
- `idx_entity_date`;
- `idx_date_creation`.

## Funcionalidades implementadas

### Plugin standalone

O modulo deixou de depender de `nextool/smartassign` e passou a funcionar como
plugin independente em `plugins/atribuicaointeligente`.

### Atribuicao automatica

Fluxo atual:

1. ticket e criado;
2. hook `item_add` de `Ticket` e chamado;
3. plugin verifica entidade habilitada;
4. verifica categoria;
5. verifica se a categoria esta ativa;
6. busca grupo encarregado da categoria;
7. preserva tecnico manual ja atribuido;
8. monta lista de candidatos do grupo;
9. remove gerentes quando configurado;
10. filtra tecnicos indisponiveis;
11. escolhe tecnico por balanceamento ou rodizio;
12. atribui tecnico;
13. atribui grupo quando configurado;
14. atualiza status quando estava como Novo;
15. registra decisao em log.

### Atribuicao opcional ao atualizar chamado

Implementada na versao `1.1.6`.

Quando a opcao global `assign_on_update` esta ativa, o hook `item_update` de
`Ticket` reutiliza a mesma decisao de atribuicao da criacao. A regra so atua
quando:

- a entidade do chamado esta habilitada no plugin;
- a categoria ITIL esta ativa na aba Categorias;
- a categoria possui grupo responsavel configurado;
- o chamado ainda nao possui tecnico atribuido.

Chamados que ja possuem tecnico sao preservados e registrados como `skip` no
log de decisao. A atribuicao executada pelo proprio plugin usa guarda interna
de reentrada para evitar novo disparo recursivo do hook.

### Balanceamento

Implementado em `chooseByBalancing()`. A consulta conta chamados ativos por
tecnico, ignorando chamados solucionados ou fechados. O criterio atual e menor
quantidade de chamados ativos, com desempate por menor `users_id`.

### Rodizio

Implementado em `chooseByRotation()`. Usa `last_assignment_index` e atualiza o
indice a cada atribuicao. Quando o tipo e por categoria, atualiza somente a
categoria; quando e por grupo, atualiza categorias ativas do mesmo grupo.

### Indisponibilidades

Telas e classes:

- `front/unavailabilities.php`;
- `front/unavailabilities.tab.php`;
- `front/unavailability.form.php`;
- `inc/technicianunavailability.class.php`;
- `inc/availabilitychecker.class.php`.

Tipos implementados:

- ferias por periodo;
- ausencia temporaria;
- ausencia em data especifica;
- recorrencia semanal.

### Escala de atendimento

Telas e classes:

- `front/work_schedules.php`;
- `front/work_schedules.tab.php`;
- `front/work_schedule.form.php`;
- `inc/technicianworkschedule.class.php`;
- `inc/availabilitychecker.class.php`.

Permite selecionar tecnico, entidade, dias da semana, horario inicial/final,
validade por periodo e status ativo/inativo.

### Calendario da entidade

Opcao de configuracao: distribuir chamados apenas dentro do calendario de
atendimento da entidade.

Regra:

- opcao desativada: ignora calendario da entidade;
- opcao ativada e entidade com calendario: distribui apenas em horario util;
- entidade sem calendario: continua 24/7.

### Habilitacao por entidade

Implementada na versao `1.1.5`.

Regras:

- em instalacoes novas, entidades entram inativas por padrao a partir da versao
  `1.1.7`;
- a partir da versao `1.1.8`, a tela de configuracao possui acoes rapidas para
  habilitar ou desabilitar todas as entidades visiveis ao usuario;
- entidade inativa nao recebe atribuicao automatica;
- entidade inativa nao bloqueia atribuicao manual;
- entidade inativa nao grava logs;
- entidade ativa aplica todas as regras.

### Logs com paginacao

Implementado em `front/logs.php`, `front/logs.tab.php` e
`inc/assignmentdecisionlog.class.php`.

Usa `Html::printPager()` e respeita `$_SESSION['glpilist_limit']`, com limite
maximo de 100.

### Bloqueio backend de atribuicao manual

Implementado no hook `pre_item_add` de `Ticket_User`.

Regra:

- item e `Ticket_User`;
- tipo e `CommonITILActor::ASSIGN`;
- entidade do ticket esta habilitada;
- tecnico esta indisponivel;
- a gravacao e bloqueada com mensagem ao usuario.

Esse bloqueio nao depende de filtro visual no dropdown.

### Dropdown manual

A tentativa de filtrar visualmente tecnicos indisponiveis no dropdown nativo do
GLPI foi revertida.

Estado atual:

- o plugin nao intercepta o dropdown nativo;
- `front/available_users.ajax.php` ainda existe, mas nao e carregado
  globalmente;
- a protecao efetiva fica no backend.

Motivo: a interceptacao JavaScript quebrou dropdowns nativos, filtros e acoes
em massa do GLPI.

### Categorias inativas por padrao

Categorias novas entram com `is_active = 0`; o administrador precisa habilitar
manualmente as categorias desejadas.

## Telas implementadas

- Configuracoes: atribuicao de grupo, exclusao de gerentes, calendario,
  tipo/modo de atribuicao e entidades habilitadas.
- Categorias: listagem, entidade, grupo responsavel, ativo/inativo e acao
  administrativa de grupo.
- Indisponibilidades: listar, adicionar, editar, excluir e ativar/desativar.
- Escala de atendimento: listar, adicionar, editar, excluir e ativar/desativar.
- Logs: data, chamado, grupo, modo, tecnico escolhido, tecnicos ignorados e
  motivo.
- Sobre: guia simples de configuracao, regras e compatibilidade.

## Permissoes

Direito criado: `plugin_atribuicaointeligente`.

Permissoes mapeadas:

- `READ`;
- `CREATE`;
- `UPDATE`;
- `DELETE`;
- `PURGE`.

Tambem existe fallback para usuarios com direito de configuracao do GLPI
(`Config::$rightname`). Isso foi necessario para evitar bloqueios indevidos em
ambientes onde o direito customizado ainda nao estava sincronizado na sessao.

Rotinas relacionadas:

- `installRights()`;
- `repairEmptyRightsForConfigAdmins()`;
- `syncCurrentProfileRight()`;
- `createFirstAccess()`;
- `assertCanView()`;
- `assertCanUpdateConfig()`.

Validacao observada na VM para o perfil Super-Admin:

```sql
SELECT profiles_id, name, rights
FROM glpi_profilerights
WHERE profiles_id = 4
AND name IN ('config', 'plugin_atribuicaointeligente');
```

Resultado:

```text
profiles_id = 4
config = 3
plugin_atribuicaointeligente = 31
```

## Testes realizados

### Sintaxe PHP

Foram executadas validacoes com `php -l` em arquivos como:

- `setup.php`;
- `hook.php`;
- `inc/config.class.php`;
- `inc/profile.class.php`;
- `inc/tickethookhandler.class.php`;
- `inc/availabilitychecker.class.php`;
- `inc/assignmentsentity.class.php`;
- `inc/categoryassignment.class.php`;
- `inc/technicianunavailability.class.php`;
- `inc/technicianworkschedule.class.php`;
- `inc/assignmentdecisionlog.class.php`;
- `front/config.save.php`;
- `front/unavailability.form.php`;
- `front/work_schedule.form.php`;
- `front/available_users.ajax.php`.

Resultado final esperado e observado nas correcoes finais:

```text
No syntax errors detected
```

### Instalacao e ativacao na VM

Comandos usados:

```bash
cd /var/www/html/glpi
sudo -u www-data php bin/console plugin:install atribuicaointeligente -u glpi -f -n
sudo -u www-data php bin/console plugin:activate atribuicaointeligente -n
sudo -u www-data php bin/console cache:clear
```

Observacao: nesse GLPI, o comando `plugin:list` nao existe.

### Banco de dados

Foram validados:

- permissoes do perfil;
- existencia das tabelas;
- existencia de registros de configuracao;
- logs;
- entidades;
- indices.

### Indisponibilidades

Foram testados:

- acesso direto ao formulario;
- POST do formulario;
- permissao de criacao;
- gravacao na tabela;
- comportamento quando a tabela nao existia;
- retorno para aba correta;
- ativar/desativar;
- excluir.

### Configuracoes

Foram testadas gravacoes de:

- atribuir grupo;
- excluir gerentes;
- tipo de atribuicao;
- modo de distribuicao;
- calendario da entidade;
- entidades habilitadas.

### URL e menu

Problema validado e corrigido: menu duplicava `/glpi`, gerando 404.

### Ativar/desativar indisponibilidade

Problema validado e corrigido: redirect incorreto gerava URL duplicada e 404.

### Exibicao de entidade

Problema validado e corrigido: nomes de entidade apareciam com `&#62;`.

### Logo

Foram adicionados e validados:

- `atribuicaointeligente.png`;
- `pics/icon.png`;
- `pics/logo.png`.

### Chamados reais/artificiais na VM

Cenarios testados incluiram:

- tecnico disponivel recebe chamado;
- tecnico indisponivel e ignorado;
- indisponibilidade de uma entidade nao bloqueia outra;
- chamado com tecnico manual nao e sobrescrito;
- todos indisponiveis deixa chamado sem tecnico automatico;
- grupo/categoria diferentes respeitam seu proprio grupo;
- logs registram a decisao.

Exemplos de chamados de teste:

- `AI_TEST_TICKET_ENT1_CAT1_QUATRO_INDISPONIVEIS`;
- `AI_TEST_TICKET_ENT1_CAT2_GRUPO_DIFERENTE`;
- `AI_TEST_TICKET_ENT2_INDISPONIBILIDADE_ENT1_NAO_BLOQUEIA`;
- `AI_TEST_TICKET_ENT1_TODOS_INDISPONIVEIS`;
- `AI_TEST_TICKET_ENT1_MANUAL_NAO_SOBRESCREVER`;
- `AI_TEST_TICKET_HOOK_REAL_TICKET_ADD_191441`.

### Dropdown de tecnico

Problema grave validado: a interceptacao visual do dropdown gerava:

```text
Os resultados nao puderam ser carregados.
```

Afetava filtro de pesquisa de chamados, acoes em massa, campo
Atribuido - Tecnico e modais de acao.

Correcao definitiva na `1.1.4`:

- remover carregamento global de `manual_assignment_filter.js`;
- deixar GLPI usar dropdown nativo;
- manter bloqueio server-side via `pre_item_add` em `Ticket_User`;
- manter `available_users.ajax.php` sem acoplamento global.

### Logs por entidade

Na `1.1.5` foi validado:

- entidade desabilitada nao gera log;
- entidade habilitada gera log;
- logs aparecem paginados;
- indice `idx_entity_date` existe ou e criado no upgrade.

## Erros encontrados e correcoes

### Session::reloadCurrentProfile inexistente

Sintoma:

```text
Call to undefined method Session::reloadCurrentProfile()
```

Causa: metodo inexistente no GLPI 10.0.25.

Correcao: remover o uso do metodo e sincronizar a permissao manualmente com
`PluginAtribuicaointeligenteProfile::syncCurrentProfileRight()`.

### Warnings de duplicate primary key no install

Causa: inserts idempotentes ainda geravam warnings.

Correcao: usar padrao `INSERT ... SELECT ... WHERE NOT EXISTS` e tratar etapas
individualmente.

### Super-Admin sem permissao de gravacao

Sintoma:

```text
A acao que voce requisitou nao e permitida.
```

Causa: direito customizado nao estava criado/sincronizado na sessao ativa.

Correcao: reparar direitos, sincronizar sessao e adicionar fallback para direito
de configuracao do GLPI.

### GitHub com senha nao autentica

Sintoma:

```text
Password authentication is not supported for Git operations.
```

Solucao operacional: usar token, release zip ou deploy via SCP/PSCP.

### Git nao instalado na VM

Sintoma:

```text
bash: git: comando nao encontrado
```

Solucao operacional: instalar/configurar Git na VM ou usar deploy por pacote.

### Dubious ownership no Git

Sintoma:

```text
fatal: detected dubious ownership in repository
```

Solucao operacional usada na VM:

```bash
git config --global --add safe.directory /var/www/html/glpi/plugins/atribuicaointeligente
```

No Windows local, a mesma situacao pode ocorrer em
`C:/Projetos/glpi/plugins/meusplugins/atribuicaointeligente`.

### Menu duplicando /glpi

Sintoma: `/glpi/glpi/plugins/atribuicaointeligente/...`.

Correcao: ajuste de URL com `Plugin::getWebDir()` e limpeza de cache de menu.

### Botao Ativar/Desativar gerando 404

Sintoma: URL duplicada com
`/plugins/atribuicaointeligente/front/plugins/atribuicaointeligente/front/...`.

Correcao: redirects usando `PluginAtribuicaointeligenteConfig::getFormURL(true)`.

### Entidades com &#62;

Correcao: `html_entity_decode()` antes de `htmlspecialchars()`.

Arquivos afetados:

- `front/unavailabilities.php`;
- `front/logs.php`;
- `inc/categoryassignment.class.php`.

### Dropdown nativo quebrado

Causa: JavaScript customizado interceptava dropdowns nativos.

Correcao: remover interceptacao global e manter validacao backend.

### Categorias ativas por padrao

Correcao: default de `is_active` alterado para `0` e sync de categorias
passou a inserir novas categorias inativas.

## Releases e commits relevantes

Commits recentes importantes:

- `2407c65 Adiciona habilitacao por entidade e pagina logs`;
- `ebec6dd Atualiza aba sobre com guia de configuracao`;
- `7d8b71e Remove interceptacao global do dropdown de tecnicos`;
- `2764b45 Corrige retorno do dropdown de tecnicos disponiveis`;
- `00697dc Ajusta atribuicao em massa e categorias inativas por padrao`;
- `f6b271c Filtra tecnicos indisponiveis na atribuicao manual`;
- `8226216 Adiciona opcao de calendario da entidade`;
- `4525b33 Adiciona escala de atendimento de tecnicos`;
- `7a1ac10 Corrige exibicao de nomes com entidades HTML`;
- `d4c4534 Corrige retorno da aba de indisponibilidades`;
- `b6c38da Exibe logo no card de plugins`;
- `8f04e35 Adiciona logo do plugin`.

Versoes publicadas:

- `1.0.0`;
- `1.0.1`;
- `1.0.2`;
- `1.1.0`;
- `1.1.1`;
- `1.1.2`;
- `1.1.3`;
- `1.1.4`;
- `1.1.5`.

## Estado atual

Funciona hoje:

- instalacao;
- ativacao;
- menu;
- permissoes;
- configuracao global;
- habilitacao por entidade;
- categorias inativas por padrao;
- atribuicao automatica por balanceamento;
- atribuicao automatica por rodizio;
- exclusao de gerentes;
- preservacao de atribuicao manual existente;
- indisponibilidades;
- escala de atendimento;
- calendario da entidade opcional;
- logs estruturados;
- logs paginados;
- bloqueio backend de tecnico indisponivel na atribuicao manual;
- logo;
- tela Sobre explicativa.

Nao faz hoje:

- nao filtra visualmente o dropdown nativo de tecnico;
- nao possui politica automatica de retencao de logs;
- nao possui testes automatizados formais;
- nao possui compatibilidade validada manualmente em GLPI 10.0.24;
- nao possui compatibilidade avaliada com GLPI 11;
- nao possui multiplos intervalos avancados por dia em um unico registro;
- nao possui opcao global para exigir escala cadastrada.

## Riscos conhecidos

### Dropdown manual

A tentativa de filtrar visualmente tecnicos indisponiveis causou regressao
importante. A decisao atual e manter dropdown nativo e bloquear no backend.

Se essa funcionalidade voltar, deve ser feita em ponto especifico do formulario
de ticket, sem interceptar dropdowns globais do GLPI.

### Logs

A paginacao reduziu risco de travamento, mas ainda faltam:

- politica de limpeza;
- limite de retencao;
- opcao de debug detalhado.

### Entidades

Entidades existentes sao semeadas como ativas quando a tabela nasce para
preservar comportamento anterior. Em instalacao nova, pode ser desejavel revisar
se todas devem realmente ficar ativas.

### Permissoes

Qualquer alteracao futura em telas deve reutilizar os asserts existentes em
`PluginAtribuicaointeligenteConfig`.

### Instalacao forcada

O uso de `plugin:install atribuicaointeligente -f` e util para upgrade/reparo,
mas pode gerar warnings esperados. Nao deve ser confundido com falha se a
instalacao termina corretamente.

## Upgrade recomendado no servidor

Para atualizar sem perder configuracoes, usar release zip e nao desinstalar.

Exemplo para `1.1.5`:

```bash
cd /tmp
wget https://github.com/fabioneres/glpi-atribuicaointeligente/releases/download/v1.1.5/atribuicaointeligente-1.1.5.zip
unzip atribuicaointeligente-1.1.5.zip
sudo rsync -a atribuicaointeligente/ /var/www/html/glpi/plugins/atribuicaointeligente/
sudo chown -R www-data:www-data /var/www/html/glpi/plugins/atribuicaointeligente
cd /var/www/html/glpi
sudo -u www-data php bin/console plugin:install atribuicaointeligente -u glpi -f -n
sudo -u www-data php bin/console plugin:activate atribuicaointeligente -n
sudo -u www-data php bin/console cache:clear
```

Importante: nao usar `plugin:uninstall` para upgrade, pois isso remove tabelas
do plugin.

## Estado local observado

Na inspecao local, o Git mostrou:

```text
M README.md
?? docs/
```

Ou seja, `README.md` tem alteracao local nao commitada e `docs/` ainda nao esta
rastreado.

## Documentacao complementar

Documentos criados para manter aderencia as recomendacoes da skill GLPI Plugins
GLPI 10:

- `docs/HISTORICO.md`: historico resumido do projeto;
- `docs/RELATORIO_TECNICO.md`: este relatorio tecnico consolidado;
- `docs/ANTIPADROES.md`: antipadroes observados, evitados e pendentes;
- `docs/CHECKLIST_DOCUMENTACAO_GLPI10.md`: checklist documental, limitacoes,
  rollback e proximos passos.

As licoes aprendidas devem ser mantidas na base global do workspace, com um
arquivo por licao:

- `../../../docs/licoes-aprendidas/README.md`
- `../../../docs/licoes-aprendidas/LA-001-dropdown-nativo-interceptado.md`
- `../../../docs/licoes-aprendidas/LA-013-licoes-aprendidas-no-local-errado.md`

## Proximas evolucoes recomendadas

Prioridade 1:

- validar `1.1.5` em homologacao real por alguns dias;
- verificar acoes em massa;
- confirmar logs em volume real;
- confirmar entidades ativas/inativas;
- testar todos os tipos de indisponibilidade;
- testar escala com multiplas entidades.

Prioridade 2:

- politica de retencao de logs;
- tela de diagnostico de decisao;
- melhor experiencia para escala semanal;
- opcao para exigir escala cadastrada;
- multiplos intervalos por dia;
- filtro visual especifico e seguro no formulario de ticket.

Prioridade 3:

- fonte de grupo configuravel;
- atribuicao quando grupo for adicionado depois da criacao do chamado;
- lista permanente de inclusao/exclusao de tecnicos elegiveis;
- filtro por perfil;
- desempate configuravel;
- politica de status apos atribuicao.

## Conclusao

O plugin saiu de uma ideia de evolucao do SmartAssign para um plugin GLPI
independente, com arquitetura propria, banco proprio, permissoes, telas, logs e
regras de disponibilidade.

O principal aprendizado do projeto e que a logica critica deve ficar no
backend. A tentativa de melhorar visualmente o dropdown de tecnicos causou
regressoes em areas nativas do GLPI; por isso, a solucao atual preserva o
comportamento nativo da interface e aplica a regra de negocio no momento da
gravacao.

Estado recomendado:

- pronto para homologacao forte;
- viavel para producao controlada apos validacao operacional;
- ainda nao considerado blindado para producao ampla sem alguns dias de uso real
  e revisao dos logs.
