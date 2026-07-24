# PRODUCT REQUIREMENTS DOCUMENT

# Atribuicao Inteligente

Automacao da distribuicao de chamados por categoria, grupo, disponibilidade e escala no GLPI

| Documento | Valor |
| --- | --- |
| Versao | 1.1.7 - entidades inativas por padrao em instalacoes novas |
| Data | 21 de julho de 2026 |
| Produto | Plugin Atribuicao Inteligente para GLPI 10 |
| Situacao | Funcionalidade implementada; PRD alinhado para validacao documental |
| Objetivo desta versao | Consolidar o entendimento de produto da versao atual e orientar evolucoes futuras sem autorizar mudancas automaticas de codigo. |

> Leitura orientadora: este PRD descreve o produto existente e as evolucoes desejadas. Itens marcados como pendentes, propostos ou em validacao nao devem ser tratados como requisito aprovado para implementacao imediata.

## 1. Visao do produto

O Atribuicao Inteligente e um plugin GLPI 10 para automatizar a distribuicao de chamados a tecnicos a partir da categoria ITIL, do grupo responsavel, da disponibilidade operacional, da escala de atendimento e da entidade do chamado.

O plugin nao substitui o fluxo nativo de chamados do GLPI. Ele acrescenta uma camada administrativa de decisao para reduzir atribuicoes manuais repetitivas, evitar distribuicao para tecnicos indisponiveis e registrar rastreabilidade sobre cada decisao automatica.

## 2. Problema e oportunidade

Ambientes GLPI com grande volume de chamados, equipes e categorias sofrem quando a distribuicao depende somente de acao manual ou de regras muito simples. A oportunidade e usar informacoes ja existentes no GLPI, somadas a configuracoes proprias do plugin, para encaminhar o chamado ao tecnico elegivel com menor atrito operacional.

- Reduzir sobrecarga desigual entre tecnicos.
- Evitar atribuicao para usuarios em ferias, ausentes ou fora da escala.
- Diminuir o tempo entre abertura do chamado e primeira atribuicao.
- Preservar a atribuicao manual quando ela ja tiver sido definida.
- Registrar logs claros sobre tecnico escolhido, tecnicos ignorados e motivo.
- Controlar em quais entidades o plugin atua.

## 3. Objetivos

### 3.1 Objetivos de negocio

- Padronizar a distribuicao inicial de chamados por categoria e grupo.
- Reduzir trabalho manual dos gestores de atendimento.
- Evitar que chamados sejam direcionados a tecnicos indisponiveis.
- Aumentar a previsibilidade da fila tecnica.
- Melhorar auditoria das decisoes de atribuicao.

### 3.2 Objetivos tecnicos

- Manter compatibilidade com GLPI 10.0.x e PHP 7.4+.
- Preservar objetos, telas, status e comportamentos nativos do GLPI sempre que possivel.
- Usar apenas tabelas proprias do plugin.
- Respeitar entidades, perfis, permissoes e validacoes de backend.
- Manter hooks rapidos, previsiveis e auditaveis.
- Evitar interceptacao global de dropdowns nativos.

## 4. Escopo

### 4.1 Capacidades ja existentes no Atribuicao Inteligente

| Area | Capacidades atuais |
| --- | --- |
| Configuracao | Atribuicao de grupo, tipo de distribuicao, modo de distribuicao, exclusao de gerentes, uso do calendario da entidade e atribuicao opcional ao atualizar chamado. |
| Entidades | Habilitacao manual do plugin por entidade, reduzindo processamento e logs fora do escopo desejado. Entidades entram inativas por padrao em instalacoes novas. |
| Categorias | Sincronizacao de categorias ITIL e ativacao manual das categorias controladas pelo plugin. |
| Distribuicao | Atribuicao automatica por balanceamento ou rodizio, usando categoria ITIL e grupo responsavel, na criacao e opcionalmente na atualizacao do chamado. |
| Disponibilidade | Cadastro de ferias, ausencias temporarias, datas especificas e recorrencias semanais. |
| Escala | Cadastro de horarios semanais por tecnico, entidade, periodo de validade e status ativo/inativo. |
| Auditoria | Log paginado de decisoes com chamado, entidade, grupo, tecnico escolhido, tecnicos ignorados e motivo. |
| Protecao backend | Bloqueio server-side de atribuicao manual para tecnico indisponivel quando a entidade esta ativa. |

### 4.2 Escopo proposto para evolucao

- Politica de retencao de logs.
- Diagnostico detalhado da cadeia de filtros.
- Distribuicao quando grupo for adicionado ao chamado apos a criacao.
- Fonte de grupo configuravel: categoria, grupo do chamado ou fallback.
- Lista administrativa de tecnicos elegiveis por inclusao/exclusao.
- Filtro por perfil permitido.
- Criterio de desempate configuravel.
- Politica configuravel de status apos atribuicao.
- Opcao para exigir escala cadastrada.

### 4.3 Fora de escopo nesta versao

- Classificacao automatica de chamados.
- Uso de IA ou analise semantica de texto.
- Alteracao de tabelas nativas do GLPI.
- Substituicao das regras nativas de categoria, grupo, perfil ou entidade.
- Interceptacao global do dropdown nativo de tecnicos.
- Migracao para arquitetura moderna com Composer, namespaces ou pasta `src/`.
- Compatibilidade com GLPI 11 sem validacao especifica.

## 5. Perfis e responsabilidades

| Perfil | Responsabilidades no processo |
| --- | --- |
| Administrador GLPI | Instala, ativa, configura permissoes, habilita entidades, revisa logs e executa upgrades. |
| Gestor de atendimento | Define categorias que devem receber distribuicao automatica e acompanha equilibrio operacional. |
| Coordenador tecnico | Mantem grupos, tecnicos, indisponibilidades e escalas coerentes com a operacao real. |
| Tecnico | Recebe chamados quando estiver elegivel, disponivel e dentro das regras da entidade/grupo. |
| Auditor / gestor operacional | Consulta logs e valida se as decisoes automaticas seguem as regras configuradas. |

## 6. Fluxo de negocio de referencia

O fluxo abaixo representa o comportamento de referencia da versao atual. Ele podera evoluir conforme validacoes de volume, compatibilidade e operacao multi-entidade.

| Etapa | Ferramenta | Acao | Ator | Artefato |
| --- | --- | --- | --- | --- |
| 1 | GLPI | Abrir chamado com categoria ITIL | Solicitante ou atendente | Ticket |
| 2 | GLPI | Salvar chamado e disparar hooks nativos | GLPI | Ticket |
| 3 | GLPI - Plugin | Verificar se a entidade esta habilitada | Atribuicao Inteligente | Configuracao por entidade |
| 4 | GLPI - Plugin | Verificar categoria e regra ativa | Atribuicao Inteligente | Categoria controlada |
| 5 | GLPI - Plugin | Identificar grupo responsavel pela categoria | Atribuicao Inteligente | Grupo GLPI |
| 6 | GLPI - Plugin | Preservar tecnico manual, se ja existir | Atribuicao Inteligente | Ticket_User |
| 7 | GLPI - Plugin | Montar lista de candidatos do grupo | Atribuicao Inteligente | Usuarios/grupos |
| 8 | GLPI - Plugin | Aplicar filtros de gerencia, calendario, indisponibilidade e escala | Atribuicao Inteligente | Regras do plugin |
| 9 | GLPI - Plugin | Escolher tecnico por balanceamento ou rodizio | Atribuicao Inteligente | Decisao de distribuicao |
| 10 | GLPI - Plugin | Atribuir tecnico e opcionalmente grupo | Atribuicao Inteligente | Ticket atualizado |
| 11 | GLPI - Plugin | Registrar log de decisao | Atribuicao Inteligente | Log paginado |

> Nota de operacao: se nenhum tecnico estiver disponivel, o chamado deve permanecer sem tecnico automatico e a decisao deve ser registrada para auditoria.

## 7. Modelo operacional proposto

### 7.1 Ativacao da atribuicao por entidade e categoria

O plugin deve atuar somente em entidades habilitadas. Dentro dessas entidades, a distribuicao automatica depende de categoria ITIL sincronizada e ativa no plugin. Categorias novas entram inativas por padrao para evitar atribuicoes automaticas inesperadas.

### 7.2 Fonte da decisao de grupo

Na versao atual, a categoria ITIL e a fonte principal para localizar o grupo responsavel. Evolucao futura pode permitir uso do grupo ja atribuido ao chamado, ou fallback entre grupo do chamado e grupo da categoria, sempre com log da fonte utilizada.

### 7.3 Disponibilidade e escala

A elegibilidade do tecnico deve considerar, nesta ordem operacional: entidade habilitada, calendario da entidade quando configurado, indisponibilidade cadastrada e escala de atendimento. Tecnico sem escala ativa permanece disponivel 24/7, salvo bloqueio por indisponibilidade ou calendario.

## 8. Disponibilidade, escala e auditoria

Indisponibilidades e escalas sao dados administrativos do plugin. Elas devem ser mantidas em tabelas proprias, com validacao de permissao e entidade, e utilizadas apenas no momento de decidir se um tecnico pode receber nova atribuicao.

| Recurso | Papel no fluxo | Situacao atual |
| --- | --- | --- |
| Indisponibilidade | Bloqueia tecnico por ferias, ausencia temporaria, data especifica ou recorrencia semanal. | Implementado. |
| Escala de atendimento | Define dias, horarios e validade operacional por tecnico e entidade. | Implementado. |
| Calendario da entidade | Restringe distribuicao automatica ao horario de atendimento da entidade. | Implementado como opcao. |
| Log de decisao | Registra tecnico escolhido, tecnicos ignorados e motivo. | Implementado com paginacao. |
| Diagnostico detalhado | Explica toda a cadeia de filtros para troubleshooting. | Proposto para evolucao. |

### 8.1 Regras de auditoria

- Toda decisao automatica relevante deve ser explicavel por log.
- Entidades desabilitadas nao devem gerar log de decisao.
- O motivo de rejeicao de tecnico deve ser preservado quando possivel.
- Logs devem ser paginados e, em evolucao futura, sujeitos a politica de retencao.
- Mudancas silenciosas no chamado devem ser evitadas.

## 9. Requisitos funcionais iniciais

| ID | Requisito |
| --- | --- |
| RF-01 | Permitir configurar se o plugin esta ativo por entidade. |
| RF-02 | Sincronizar categorias ITIL e permitir ativacao manual por categoria. |
| RF-03 | Distribuir chamado automaticamente quando entidade e categoria estiverem ativas. |
| RF-04 | Identificar grupo responsavel pela categoria ITIL. |
| RF-05 | Preservar tecnico manual ja atribuido ao chamado. |
| RF-06 | Filtrar tecnicos indisponiveis antes da distribuicao. |
| RF-07 | Respeitar escala de atendimento quando houver regra ativa. |
| RF-08 | Opcionalmente respeitar calendario de atendimento da entidade. |
| RF-09 | Permitir distribuicao por balanceamento de carga. |
| RF-10 | Permitir distribuicao por rodizio. |
| RF-11 | Opcionalmente atribuir tambem o grupo encarregado da categoria. |
| RF-12 | Opcionalmente excluir gerentes do grupo da lista de candidatos. |
| RF-13 | Bloquear no backend a atribuicao manual de tecnico indisponivel em entidade ativa. |
| RF-14 | Registrar log de decisao com chamado, entidade, categoria, grupo, tecnico, ignorados e motivo. |
| RF-15 | Exibir logs com paginacao. |

## 10. Requisitos nao funcionais e controles

- Compatibilidade-alvo: GLPI 10.0.x e PHP 7.4+.
- Arquitetura: plugin classico GLPI, preservando `setup.php`, `hook.php`, `inc/`, `front/`, `sql/`, `css/`, `js/`, `pics/` e `locales/`.
- Multi-entidade: comportamento, registros e logs devem respeitar entidade do chamado.
- Permissoes: menus, telas e POSTs administrativos devem validar direitos no backend.
- Seguranca: validar sessao, CSRF quando aplicavel, entrada de dados e acesso direto a arquivos.
- Auditoria: decisoes automaticas devem ser rastreaveis por log.
- Performance: hooks devem ser rapidos; consultas devem usar indices de ticket, grupo, usuario, entidade e data.
- Manutenibilidade: instalacao e upgrade devem ser idempotentes e nao alterar tabelas nativas.

## 11. Regras de permissao propostas

| Direito proposto | Quem utiliza | Finalidade |
| --- | --- | --- |
| Ler Atribuicao Inteligente | Administradores, gestores e auditores autorizados | Visualizar configuracoes, categorias, indisponibilidades, escalas e logs conforme permissao. |
| Criar registros operacionais | Administradores e coordenadores autorizados | Cadastrar indisponibilidades e escalas de atendimento. |
| Atualizar configuracoes | Administradores GLPI | Alterar configuracao global, entidades habilitadas, categorias, indisponibilidades e escalas. |
| Excluir / purgar registros | Administradores autorizados | Remover indisponibilidades e escalas conforme politica operacional. |
| Consultar logs | Gestores e auditores autorizados | Auditar decisoes de distribuicao automatica. |

## 12. Arquitetura e integracoes desejadas

O plugin deve manter a arquitetura classica GLPI e integrar-se aos objetos nativos por hooks, classes `CommonDBTM`/`CommonGLPI` quando aplicavel e tabelas proprias. A regra principal e nao alterar core nem schema de tabelas nativas.

- `setup.php` deve concentrar metadados, classes, menus, hooks e compatibilidade.
- `hook.php` deve concentrar instalacao, upgrade, uninstall e callbacks.
- `inc/` deve concentrar configuracoes, modelos, disponibilidade, atribuicao e logs.
- `front/` deve concentrar telas e formularios administrativos.
- `sql/` deve conter instalacao e uninstall idempotentes.
- Hooks de chamados devem evitar processamento pesado, loops recursivos e conflitos com outros plugins.
- O dropdown nativo de tecnicos deve permanecer sob controle do GLPI.

## 13. Riscos e pontos de atencao

| Tema | Risco / decisao necessaria | Tratamento proposto |
| --- | --- | --- |
| Dropdown nativo | Interceptacao global pode quebrar filtros, pesquisas e acoes em massa. | Manter dropdown nativo e aplicar regra critica no backend. |
| Hooks de tickets | Outros plugins podem atuar nos mesmos eventos. | Validar coexistencia com Fields, FormCreator, Escalade, Behaviors, Inventory e similares. |
| Logs | Volume pode crescer e afetar consulta/armazenamento. | Manter paginacao e criar politica de retencao. |
| Entidades | Configuracao incorreta pode impedir atribuicao esperada. | Exibir claramente entidades ativas e registrar ausencia de processamento quando aplicavel. |
| Escala | Tecnico sem escala e considerado disponivel por padrao. | Avaliar opcao futura para exigir escala cadastrada. |
| Instalacao forcada | Warnings idempotentes podem ser confundidos com falha. | Documentar procedimento e validar estado final das tabelas. |
| Compatibilidade futura | Mudancas no GLPI podem alterar metodos e APIs disponiveis. | Validar antes de suportar versoes fora de GLPI 10.0.x. |

## 14. Ordem recomendada de implementacao

1. Fechar validacao operacional da versao 1.1.5 em ambiente real.
2. Validar volume de logs, entidades, indisponibilidades e escalas.
3. Implementar politica de retencao de logs.
4. Implementar diagnostico detalhado da cadeia de filtros.
5. Implementar distribuicao quando grupo for adicionado ao chamado.
6. Implementar fonte de grupo configuravel.
7. Implementar lista de inclusao/exclusao de tecnicos elegiveis.
8. Implementar filtro por perfil, desempate configuravel e politica de status.

## 15. Decisoes registradas e pendencias de validacao

| Tema | Estado | Registro |
| --- | --- | --- |
| Plugin standalone | Confirmado | O modulo SmartAssign/NexTool foi transformado em plugin independente. |
| Core GLPI | Confirmado | Nao alterar core nem tabelas nativas. |
| Arquitetura classica | Confirmado | Manter padrao GLPI 10 sem Composer/namespaces. |
| Dropdown nativo | Confirmado | Nao interceptar globalmente; regra critica fica no backend. |
| Categorias novas | Confirmado | Entram inativas por padrao. |
| Entidades | Confirmado | Entidade inativa nao atribui, nao bloqueia manualmente e nao grava log. |
| Retencao de logs | Pendente | Definir periodo, acao de limpeza e configuracao administrativa. |
| GLPI 11 | Fora do escopo | Avaliar apenas mediante demanda especifica. |

## 16. Criterios de aceite desta fase de validacao

Este PRD sera considerado alinhado para orientar evolucoes quando a area confirmar, no minimo:

- Escopo atual da versao 1.1.5 esta descrito corretamente.
- Fora de escopo deixa claro que classificacao automatica pertence a outro produto/plugin.
- Regras de entidade, categoria, grupo, indisponibilidade e escala refletem a operacao esperada.
- Riscos de dropdown, hooks, logs e multi-entidade estao registrados.
- Ordem de evolucao esta coerente com a prioridade operacional.
- Nao ha autorizacao implicita para alterar codigo, SQL, hooks ou telas apenas por este documento.

## 17. Proximos passos

Apos validacao deste PRD, o proximo artefato recomendado e uma especificacao funcional detalhada da proxima evolucao priorizada. Essa especificacao deve conter campos, telas, regras de transicao, impactos em banco, permissoes, testes, rollback e riscos antes de qualquer alteracao estrutural no plugin.
